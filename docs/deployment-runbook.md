# UPress and GitHub deployment runbook

## Live-site production preparation

Do not create an UPress staging or duplicate test site for Complete99. Until
`complete99.co.il` is attached, the same final live WordPress installation may be
addressed through the exact transitional UPress hostname
`a235232-tmp.s1242.upress.link`. This is an alias for the live installation, not a
second environment. It is accepted only when explicitly listed in
`WP_ALLOWED_DEPLOY_HOSTS`; wildcards and other UPress hostnames are rejected.

1. Confirm the transitional alias points to the intended final UPress installation.
   When `complete99.co.il` is available, attach it to this same installation and
   complete DNS/SSL.
2. In UPress security, enable the WordPress REST API for the production site.
3. In WordPress set **Settings → Permalinks → Post name**.
4. Install and activate the approved free **Code Snippets** plugin from
   WordPress.org. The deployer can bootstrap it only when
   `--bootstrap-code-snippets` is explicitly supplied and the core plugin REST
   controller is available.
5. Create a dedicated WordPress deployment administrator. Do not use the owner’s
   daily personal account.
6. Create one site-specific Application Password for that identity. Store it only
   in the GitHub `production` environment; never in this repository.
7. Generate a separate 32–4096-character random read-model sync secret. Store the
   identical value in the GitHub `production` environment as
   `COMPLETE99_WORDPRESS_SYNC_SECRET` and in the Complete99 OS server-side secret
   store. Do not pass it on a command line or record it in evidence.
8. Confirm automatic updates are disabled for
   `complete99-platform/complete99-platform.php`. Deliberate releases are verified
   by this pipeline; Plugin Update Checker remains a human fallback.

Credential creation is a persistent security action. At the final creation control,
confirm that it grants plugin-update authority on this one WordPress site.

## GitHub production environment

Create environment `production`, add any desired reviewer gate, and add:

- `WP_BASE_URL` - exact HTTPS origin, with no trailing WordPress admin path.
- `WP_DEPLOY_USER` - dedicated deployment username.
- `WP_APP_PASSWORD` - site-specific WordPress Application Password.
- `COMPLETE99_WORDPRESS_SYNC_SECRET` - the exact 32–4096-character value held by
  the Complete99 OS server-side secret store.

Set the repository variable `WP_PRODUCTION_READY` to `false` during setup. Change
it to exactly `true` only after the selected live origin checks above pass. Protect
`main` and require the **WordPress CI / validate** check. The production workflow also
queries GitHub and refuses any commit that does not have its own successful CI run.
Repository Actions permission remains read-only; the deploy workflow adds only
`actions: read`. Both workflows pin referenced third-party actions to full SHAs.

Leave the `production` environment variable `WP_ALLOWED_DEPLOY_HOSTS` empty after
the final domain is connected. During the live-alias transition, set it to exactly
`a235232-tmp.s1242.upress.link` and set `WP_BASE_URL` to the matching clean HTTPS
origin.

The deployment bridge initializes the WordPress sync option only when it is
absent or empty. It accepts an already configured value only when it exactly
matches the environment secret and refuses rotation. Resolve any mismatch
through a separately reviewed credential-rotation procedure before dispatching
production; repeated deployment attempts cannot reconcile it.

## WAF-safe deployment runner

UPress accepts the public REST root from standard GitHub-hosted runners but rejects
authenticated traffic from their changing Azure egress addresses. GitHub itself
recommends a self-hosted runner or a larger runner with static egress when a target
is protected by an IP/WAF boundary.

Only the production `deploy` job uses the repository-scoped Windows runner with
the exact labels `self-hosted`, `Windows`, `X64` and `complete99-deploy`. CI,
branch/readiness gates and pull-request checks remain on GitHub-hosted runners.
The self-hosted job is reachable only from the manual, `main`-restricted workflow
after exact-commit CI and the `production` environment gate pass. No pull-request
workflow targets the self-hosted label. GitHub injects the environment secrets at
job runtime; they are not stored in the runner directory.

Keep that narrowly scoped runner online and allow its automatic runner updates.
If it is offline, the deploy job remains queued and no WordPress mutation occurs.
The Windows runner uses the already-installed Python 3.11 runtime. Do not add
`actions/setup-python` to this non-elevated job: first-time Windows tool-cache
installation requires administrator privileges.

Some UPress policies also reject an authenticated pretty REST path such as
`/wp-json/wp/v2/users/me` with an HTML nginx 403 while permitting WordPress's
standard `/?rest_route=/wp/v2/users/me` transport. The client retries only that
exact non-JSON 403 signature, then keeps using query transport for the run. A JSON
403 from WordPress is never bypassed and remains a hard failure.

## Release ritual

1. Bump the plugin header and `COMPLETE99_PLATFORM_VERSION`; set
   `COMPLETE99_PLATFORM_DEPLOYMENT_ID` to exactly `c99-wp-<version>`.
2. Run PHP lint, contract tests and the secret scan.
3. For release 1.18.0, require the Entity Studio contracts to prove private
   post-type and REST boundaries, capability denial, advisory-lock failure,
   transaction rollback with cache invalidation, exact revision and source-base
   conflicts, explicit draft-only rebase, history-chain tamper detection,
   bounded API pagination, orphan-dossier audit access and fail-closed
   observation identity collisions. Require the cumulative 672-entity
   `culinary-science-2026.08.07.v18` registry, the
   `culinary-commerce-2026.08.07.v12` registry and the exact 728-subject Entity
   Studio index: 672 science identities plus 56 product identities. Require
   exact evidence chronology and evidence-class alignment with each source
   type. Require the Lebanese expansion to contain exactly 121 identities in
   two modules: 61 coastal and northern, and 60 Bekaa, southern and community
   identities. Require the new type distribution to be 12 topic hubs, 31
   dishes, 15 ingredients, 3 molecules, 4 reactions, 14 techniques, 6
   equipment records, 18 traditions, 4 markets, 10 culinary institutions, 3
   restaurants and 1 guide.
4. Require the public read-model integrity tests to prove
   recursive canonical hashing, exclusion of the top-level `digest` field,
   `hash_equals` verification and fail-closed handling of missing, malformed,
   arbitrary or content-mismatched digests.
5. Require the shared 810-byte fixture to produce SHA-256
   `b183d09588cb21c1374b5ec75d6d90fac836a49f5e1dbe030f01aa9d85d35410`,
   and verify exact UTC millisecond generation timestamps plus byte-identical
   item timestamps.
6. Exercise the narrow 1.7 migration gate against the known 12-item live
   fallback state. Confirm canonical ID and slug ownership remains protected.
7. Confirm the public catalog allowlist and receipt contain exactly 36 items.
   Require the four 1.9.0 additions to have exact product codes, prices, one
   unit of opening stock, disabled backorders, image hashes, relations and
   dated market evidence. Confirm all 32 earlier products keep their current
   operational stock rather than being reset. Confirm all 17 earlier planning
   offers remain private drafts and all 12 Japanese candidates retain planning
   stock zero and no WooCommerce code. Confirm the three Syrian product
   identities are private held market observations only, with no WooCommerce
   code, channel offer, stock, supplier, landed-cost or margin claim. Require
   zero new active or draft offers and exact 56 of 56 price-basis coverage: 36
   live prices plus 20 private planning prices. Preserve the release 1.13.0
   Syrian graph at exactly 282 identities, including 77 dishes, 69 ingredients,
   25 regional or topic hubs, 30 techniques, 29 traditions, 16 preparations,
   10 guides, 11 culinary institutions, 5 markets, 6 restaurants and the three
   preserved private market records. One safe Syrian consumer gateway remains
   `noindex,follow`; the other 281 Syrian entities remain private. Confirm the
   86 new records contain no price, supplier, stock, product, offer or public
   route, and confirm the four exact held identities. Confirm all
   202 Lebanese and all 96 Iraqi identities remain private, noindex and
   reference-only. Confirm the reviewed Lebanese cuisine root is the only
   public noindex gateway. Confirm the two Lebanese expansion modules contain exactly
   61 and 60 identities, all 77 new source records are disjoint and used, all
   121 new records contain no price, supplier, stock, product, offer or public
   route, and the twelve exact held identities remain fail closed. Confirm
   the six dated Lebanese retail observations create no product identity,
   WooCommerce price, offer, stock, supplier, import route, landed-cost or POS
   row. Confirm only
   the exact explicit `public_market_projection=public` value can enter the
   public projection, and require 27 public science entities across 19
   canonical page owners per language.
   Require the Lebanon contracts to prove the March 2026 direct and indirect
   trade boundary, separate shared Levantine identities, no raw-meat recipe or
   consumption guidance, validated-process gates for fermentation and
   preservation, and the no-foraging-instruction boundary. Require the Iraq
   contracts to prove the current trade boundary, no price observations, shared
   regional family separation, existing sabich and amba ownership, regional and
   community scope, and the fish, rice, offal, overnight cooking, fermentation,
   dairy, date syrup, open-fire and wild-plant safety gates.
8. Build twice and require identical bytes:

   `python scripts/build-plugin-zip.py --verify-reproducible`

9. Inspect and validate the artifact:

   `python scripts/validate-package.py`

10. Confirm `plugin-dist/complete99-platform.json` is the public PUC update
   manifest and `plugin-dist/complete99-platform-integrity.json` contains the
   independently checked artifact digest and size.
11. Review source and metadata, then merge to `main`.

The deliberate deploy path remains the authenticated temporary bridge. Vendored
Plugin Update Checker 5.6 reads the public manifest on `main` only as the normal
wp-admin/human fallback.

## Isolated local pipeline proof

Before the final domain exists, use a disposable native or containerized WordPress
site on the loopback interface to exercise Application Password authentication,
Code Snippets bootstrap, bridge creation, package integrity, activation, health,
rollback, finalization and cleanup. Pass
`--local-test` explicitly. That flag accepts only `http://127.0.0.1`,
`http://localhost` or `http://[::1]`; it cannot authorize a remote, UPress
temporary or staging host.

Production runs must omit `--local-test`. They remain locked to HTTPS on
`complete99.co.il`, `www.complete99.co.il`, or the single transitional live alias
above when that exact alias is explicitly configured. Home URL, site URL and REST
identity must all resolve to the selected origin before a lock can be reserved.

## First installation

The first installation has no prior plugin to restore, so it cannot prove a
healthy-version rollback. Run a normal deployment first:

```powershell
python scripts/deploy-wordpress.py
```

The deployer authenticates, creates a unique temporary route and reserves a site-wide
mutation lock. Preflight requires direct filesystem access, sufficient free space,
transactional WordPress tables and target auto-update disabled. The server verifies
the package SHA-256, encrypts and read-backs the database rollback journal, copies and
hashes the prior plugin, installs only the allowlisted slug, and verifies public
health plus exact release markers in the anonymous homepage body. Commit removes the
backup, releases the lock, permanently deletes the temporary Code Snippets database
row through the protected bridge, independently proves that exact row is absent,
and then proves the route returns 404.

Managed-host requests use a normal browser request signature with only standard
`Accept`, `Authorization`, `Content-Type` and `User-Agent` headers. Do not add an
`X-*` deployment header or send HTML/multipart bodies: UPress can reject those at
nginx before WordPress receives the request.

## Mandatory live canary/rollback exercise

After the first release is healthy, run:

```powershell
python scripts/deploy-wordpress.py --rollback-exercise
```

This uses the encrypted transaction journal on the final live site; it does not create
an UPress staging site. It deploys the reviewed package, verifies health, restores the
exact prior database fingerprint and plugin-directory digest through an atomic
directory swap, independently verifies the prior public surface, redeploys the target
package, verifies it again, finalizes and proves route cleanup.

## Dry preflight

```powershell
python scripts/deploy-wordpress.py --dry-run
```

Dry-run still creates and permanently retires the temporary bridge, so it verifies
authentication, Code Snippets, direct filesystem mode, allowlist, exact-row cleanup
and route 404 without installing the plugin.

## Interrupted-request recovery

Every lock carries a recovery lease. If PHP or the network disappears during
preparation, install, rollback or commit cleanup, the driver waits until the lease is
safe, then restores from the validated journal. Rollback stages the prior plugin in a
sibling directory and swaps it atomically.

If the original process is gone and its temporary route has already been deleted,
recreate an admin-gated bridge for the same deployment ID:

```powershell
python scripts/recover-wordpress.py --discover
```

Use `--deployment-id <original-deployment-id>` when that exact owner is already
known. Discovery uses a short-lived authenticated probe, accepts only a syntactically
valid Complete99 lock owner returned by WordPress, and permanently retires the probe before
recovering that exact deployment. The workflow performs discovery before every new
manual deployment.

The recovery command derives the journal key from the site’s WordPress auth salt and
the deployment ID, so it never needs the discarded route token. It independently
chooses only between finishing an already committed cleanup and rolling back an
uncommitted mutation. It then proves its row absent and its route 404. The production
workflow runs this command automatically after a failed deployment step.

For the reviewed interrupted-forward v2 proof, `--recovery-only` is also safe to
rerun after platform finalization. When no failed-run lock remains, recovery keeps
a fresh read-only probe reservation, proves the failed state, rollback artifacts,
plugin/runtime identity, full database fingerprint and manifest, transactional
storage, deployment marker, sync state and managed robots identity exactly, then
finalizes only that probe. The audit result is `already-recovered`; the workflow
continues through dry-run and commerce materialization. Any intervening database
change, including commerce data, rejects this pre-commerce attestation. If an
earlier probe-finalize response was lost, the next run waits for that unstarted
probe lease, releases only its exact state-free reservation, and repeats the full
attestation under a new probe.

## Release 1.18.0 live verification

Before installation:

1. Confirm the artifact, manifest and deployment marker all report `1.18.0`.
2. Confirm contract readback reports 672 science identities, 370 sources, 56
   product identities, 728 Entity Studio subjects, 36 public WooCommerce
   products, 27 public science identities and 19 public page owners per language.
3. Confirm the Japanese cluster contains 84 identities split into 24 public and
   60 private records, and that the foundations collection contains 18 members.
4. Confirm controlled dashi extraction is owned by the existing ichiban-dashi
   page and L-glutamate plus inosine monophosphate are owned by the existing
   umami page, with no new canonical route or public offer.
5. Confirm all 27 public science records remain noindex and outside the sitemap.

After installation and cache purge:

1. Verify anonymous health returns version `1.18.0`, deployment ID
   `c99-wp-1.18.0` and the expected database version.
2. Verify public science contains exactly 27 records, 19 page owners per
   language and 38 bilingual routes. Verify the three new section images,
   bilingual alternative text, source citations and natural internal links.
3. Request the Hebrew and English ichiban-dashi, umami, kombu, katsuobushi and
   Japanese foundations pages with cache busting. Confirm the new sections are
   visible and no editorial, prompt, supplier, price-planning or workflow data
   appears.
4. Verify the exact 36-product store, cart, stock authority, nofollow
   add-to-cart links and disabled payment state are unchanged.
5. Run real Chrome acceptance at desktop and 390 CSS pixels, including
   direction, canonical, hreflang, overflow, keyboard focus, visual loading and
   first-party console errors.
6. Confirm deployment cleanup returns route 404, leaves no bridge row or active
   snippet, and preserves rollback evidence.

## Release 1.17.0 live verification

Before installation:

1. Confirm the artifact, manifest and deployment marker all report `1.17.0`.
2. Confirm contract readback reports 672 science identities, 56 product
   identities, 728 Entity Studio subjects, 36 public WooCommerce products, 24
   public science identities and 19 public page owners per language.
3. Confirm the Lebanese cluster reports exactly 203 identities. Confirm the 121
   new identities and their 61 and 60 module split with the documented type
   distribution.
4. Confirm all 121 new identities are private, noindex and reference-only, and
   contain no offer, price observation, stock, supplier, product or public
   route. Confirm the twelve exact held identities remain fail closed.
5. Confirm the Syrian cluster remains 282 and the Iraqi cluster remains 96.

After installation and cache purge:

1. Verify anonymous health returns version `1.17.0`, deployment ID
   `c99-wp-1.17.0` and the expected database version.
2. Verify public science contains exactly 24 and public page ownership contains
   exactly 19 per language. The reviewed Lebanese cuisine root must appear as
   noindex, with no Product or Offer schema, while every child remains private.
3. Request the Hebrew and English Lebanese cuisine gateway and representative
   private child candidates with cache busting. The gateway must render and the
   child candidates must remain unavailable while route mode is private.
4. Verify the exact 36-product store, cart, stock authority and disabled payment
   state are unchanged.
5. Run real Chrome acceptance on Hebrew and English home and store pages at
   desktop and 390 CSS pixels, including direction, canonical, hreflang,
   overflow, keyboard focus and first-party console errors.
6. Confirm deployment cleanup returns route 404, leaves no bridge row or active
   snippet, and preserves rollback evidence.

## Historical release 1.15.0 live verification

Before installation:

1. Confirm the artifact, manifest and deployment marker all report `1.15.0`.
2. Confirm contract readback reports 465 science identities, 56 product
   identities, 521 Entity Studio subjects, 36 public WooCommerce products, 23
   public science entities and 18 public page owners per language.
3. Confirm the Iraqi cluster reports exactly 96 identities with the documented
   type distribution, all private, `noindex` and reference-only.
4. Confirm every Iraqi entity references the central trade rule, and that no
   Iraqi record contains an offer, stock, supplier, price observation or public
   route.
5. Confirm the Syrian cluster remains 196 and the Lebanese cluster remains 82.

After installation and cache purge:

1. Verify anonymous health returns version `1.15.0`, deployment ID
   `c99-wp-1.15.0` and the expected database version.
2. Verify public science remains exactly 23 and public page ownership remains
   exactly 18 per language. No Iraqi identity may appear in public API, search,
   sitemap, catalog, POS projection or structured data.
3. Request Hebrew and English Iraqi canonical candidates with cache busting.
   They must remain unavailable while route mode is private.
4. Verify the exact 36-product store, cart, stock authority and disabled payment
   state are unchanged.
5. Run real Chrome acceptance on Hebrew and English home and store pages at
   desktop and 390 CSS pixels, including direction, canonical, hreflang,
   overflow, keyboard focus and first-party console errors.
6. Confirm deployment cleanup returns route 404, leaves no bridge row or active
   snippet, and preserves rollback evidence.

## Historical release 1.14.0 live verification

Before installation:

1. Confirm the reviewed artifact and manifest both report `1.14.0`, the
   deployment ID is `c99-wp-1.14.0`, and the package integrity metadata matches
   the deterministic ZIP.
2. Confirm contract readback reports 369 science identities, 56 product
   identities, 425 Entity Studio subjects, 36 public WooCommerce products, 23
   public science entities and 18 public page owners per language.
3. Confirm the Lebanese cluster reports exactly 82 identities and the exact
   type distribution documented above. Confirm the Syrian cluster remains 196
   identities.
4. Confirm every Lebanese entity has `editorial_draft`, `noindex_private`,
   `private_preview`, private route and `reference_only` state, with no public
   API, public page, WooCommerce code, offer, stock, supplier or public
   taxonomy projection.
5. Confirm all six retail listings share the observation time
   `2026-08-07T12:00:00+03:00`, have non-comparable external price evidence and
   create no active or draft offer.

After installation and cache purge:

1. Verify anonymous health returns version `1.14.0`, deployment ID
   `c99-wp-1.14.0` and the expected database version.
2. Verify anonymous public science output still contains exactly 23 entities
   and the public ownership registry still contains 18 page owners per
   language. `cuisine-lebanese-regional` and every other Lebanese identity must
   be absent.
3. Verify `robots.txt`, WordPress search, the managed sitemap, museum sitemap
   provider, public catalog, POS projection and page source contain no
   Lebanese private identity, benchmark price, visual prompt, supplier claim,
   Product schema or Offer schema.
4. Request the Hebrew and English Lebanon canonical candidates with cache
   busting. They must not render a public Lebanese foundation page while route
   mode remains private. No redirect may expose a private child entity.
5. Verify the six observed prices are visible only through an authenticated
   private review path and are labeled as dated external observations rather
   than Complete99 prices, products or offers.
6. Verify the 36-product public store, cart, stock authority, filters and POS
   projection are unchanged. Payment remains in its pre-existing held state.
7. Run real Chrome acceptance on the existing Hebrew and English home,
   culinary museum and pantry at desktop and 390 CSS pixels. Check direction,
   canonical and hreflang, keyboard navigation, focus, overflow, images and
   first-party console errors.
8. Confirm the deployment bridge row is absent, its route returns 404, final
   cache-busting requests show the 1.14.0 release marker and rollback evidence
   remains recoverable.

## Completion evidence

The single interrupted 1.18.0 production run is governed by
`docs/interrupted-forward-recovery-1.18.0.md`. Its two-step observation and
proof-gated forward-adoption process must be used instead of a normal deploy
or rollback while `c99-prod-31217684760-1` owns the stale production lock.

A production release is complete only when the non-secret audit JSON shows:

- artifact digest and version;
- authenticated deployment role;
- direct-filesystem preflight;
- transactional-storage preflight and run-time database baseline;
- expected public health version and deployment ID;
- matching health `database_version`;
- a public read-model freshness result that is causally bound to its stored
  recursive canonical digest, with negative evidence for missing, malformed,
  arbitrary and content-mismatched digest states;
- an exact 36-item public catalog receipt and allowlist, including the four
  version 1.9.0 additions;
- a private Entity Studio registration and invariant check with no public route,
  no new role and no WooCommerce write;
- exact registry receipts for culinary science v18 and culinary commerce v12,
  with 672 science identities, 56 product identities and 728 Entity Studio
  subjects;
- exact 56 of 56 product price-basis coverage, comprising 36 live prices and 20
  private planning prices, with zero active or draft offer added by 1.18.0;
- exact 282-identity Syrian type counts, including the 86 new private identities
  and four exact held records, with no price, product, offer, supplier, stock or
  public projection;
- exact 96-identity Iraqi type counts and proof that all 96 remain private,
  noindex and reference-only, with no price observation, offer, stock, supplier
  or public projection;
- exact 203-identity Lebanese type counts, including the 121 new private
  identities and twelve exact held records, proof that the reviewed cuisine
  root is the only public noindex gateway, and proof that the other 202 remain
  private, noindex and reference-only;
- six dated Lebanese retail observations with no product identity, offer,
  stock, supplier, import route, landed cost or public price projection;
- 27 public science entities, 19 public canonical page owners per language and
  38 bilingual routes, with the 84-identity Japanese cluster split into 24
  public and 60 private records;
- negative public evidence for every Syrian expansion record and every Lebanon
  child across API, search, sitemap, rendered routes, public catalog, POS and
  structured data, with positive evidence only for the reviewed Lebanon root;
- `sync_configured: true` after an exact secret checkpoint, without exposing the
  value;
- anonymous `robots.txt` content and SHA-256 matching the managed policy;
- anonymous homepage body markers for version and deployment;
- successful finalize;
- `row_absence_verified: true`, snippet deletion and `route_404: true`;
- exact database/plugin rollback and rollback-health evidence for the canary exercise.

An installer response alone is never treated as proof.

Physical `robots.txt` management, sync-secret initialization, rollback and the
closing audit are capabilities of the deliberate production pipeline. A
Plugin Update Checker/wp-admin ZIP update changes plugin files only and must not
be described as a complete production release.
