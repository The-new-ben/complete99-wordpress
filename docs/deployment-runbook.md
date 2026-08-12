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

Provision the deploy runner outside the checkout, the user's Documents folder
and every synchronized directory, for example
`C:\actions-runner\complete99-deploy`. Register it only to this repository and
run it as a Windows service under the least-privileged dedicated account. If
service installation is unavailable, use one hidden Startup launcher for that
same runner directory; never run a second interactive listener. Before a release,
prove exactly one runner listener/worker process, an online repository runner and
the four exact labels above. The checkout remains disposable; runner credentials,
diagnostics and work directories are not release inputs.

Campaign zero-traffic reconciliation uses a separate repository-scoped Windows
runner under `C:\actions-runner\complete99-monitor` with exact labels
`self-hosted`, `Windows`, `X64` and `complete99-monitor`. It may access only the
`campaign-monitor` environment and its dedicated WordPress Application Password.
The fixed workflow runs at minutes 7, 22, 37 and 52 and may also be dispatched
manually. GitHub schedules are not real-time guarantees: an offline or busy
self-hosted runner queues the job. Keep it continuously online, alert on a queued
or failed monitor, and require a fresh durable Campaign heartbeat before enabling
owned placements. Do not share the production administrator credential with this
runner.

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
3. For release 1.18.1 and later, require the Entity Studio contracts to prove private
   post-type and REST boundaries, capability denial, advisory-lock failure,
   transaction rollback with cache invalidation, exact revision and source-base
   conflicts, explicit draft-only rebase, history-chain tamper detection,
   bounded API pagination, orphan-dossier audit access and fail-closed
    observation identity collisions. Require the cumulative 672-entity,
    375-source `culinary-science-2026.08.08.v20` schema-v6 registry, the
    `culinary-commerce-2026.08.08.v14` registry and the exact 728-subject Entity
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
   preserved private market records. Require exactly one Syrian public record,
   the pre-existing cuisine root, and exactly 281 private records. Confirm the
   seven Aleppo editorial candidates remain private. All public records remain
   `noindex,follow`. Confirm the
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
    standalone page owners per language and 38 bilingual routes. Require zero
    indexable science records and an empty science sitemap. Confirm the Syrian
    bulgur-to-product relation remains private, with no public edge, new offer,
    price or stock record. Confirm the Japanese cluster contains exactly 24
    public and 60 private records. Require the five exact Japanese koji and
    shoyu candidates to remain private, including their four proposed
    standalone owners and one proposed section owner. Require exactly three
    verified literature-context assay ranges with their unit, method, specimen
    scope, conditions and source to remain private. Require cross-domain v3 to
    contain exactly 95 unresolved census records plus 11 private Woo candidates
    and five literally empty verified or public indexes. Require its valid
    private decision overlay to report zero decisions and zero recognized
    reviewer authorities.
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

### Protected operations and Campaign Studio rollback gate

The encrypted journal uses exact manifest generations. Historical v1 omits both
protected-table cohorts; v2 adds exactly seven `c99_ops_*` tables; v3 adds the
exact seven Campaign Studio tables. Each component binds transactional engine,
normalized schema digest and rows ordered by `id`. Capture is bounded to 5,000
rows per table and 8 MiB independently across each seven-table cohort. The
operations and Campaign schema markers are journaled in their generation.

Before preflight or `/run`, Campaign Studio must be wholly absent, or its marker
must equal `complete99-campaign-schema/v1` and all seven tables must exist. A
marker-only, table-only or partial-table state is schema drift and fails before
the plugin changes. Historical v1/v2 JSON and SHA-256 are validated in their
original shape before absent components are synthesized for internal comparison.

Rollback reconstructs and fingerprints operations and Campaign forward state
separately, then atomically renames every candidate-created table from both
cohorts into one deployment-specific quarantine boundary. WordPress options,
posts and postmeta restore in one transaction. Only after the full normalized
baseline reads back exactly may cleanup drop the two proven quarantine cohorts
in one bounded operation. Retry requires both recorded forward digests and can
rejoin both cohorts only in one atomic rename. Any ambiguity, mutation, partial
cohort, residue or missing proof fails closed.

Status reports reserved quarantine residue. Preflight, `/run` and finalization
all refuse to proceed while any `c99rb_*` table remains, so a redeploy cannot
hide or inherit an unfinished rollback. A table that existed at baseline may
not change schema or rows during this release; it is never inferred disposable.

Campaign and Ops mutation paths must keep exact bridge-compatible capacity below
4,500 rows per table and 4 MiB per seven-table cohort, with `writeReady` reserving
64 rows per table and 256 KiB per cohort for lifecycle/compensation work. The
anonymous event table is additionally limited to 2,500 rows and 2.5 MiB with
five-hour claim/budget retention. Confirm private status reports both cohorts and
headroom; anonymous endpoints must return only bounded 429/503 errors, never row
counts or canonical-byte totals.

Immediately before the real `/run` baseline, the bridge must acquire the same
`rollback-capacity` advisory lock used by Campaign writers, capture while held,
and release it with no residue. Test both sides of the race: a writer that began
before reservation drains before baseline, while a writer beginning after the
durable reservation is rejected before advisory contention.

### Campaign Studio cron and redeploy acceptance

The serialized WordPress `cron` option is derived runtime state and is neither
journaled nor mutated by the bridge. Schema preparation and migration must call
no WordPress scheduling API. A schedule request first commits the governed
campaign row with `reconcile_pending`, version and schedule digest; reconciliation
then uses exact arguments `[campaignId, scheduledVersion, scheduleDigest]` and
reads both activation and expiry timestamps back.

On normal deactivation, Campaign Studio keyset-drains every scheduled or active
row, snapshots exact activation/expiry/readback triggers, persists `suspended`
before unscheduling, compensates state and hooks on failure, and proves zero
unsuspended rows. On activation/redeploy, normal `init` performs only an
enqueue-only durable due-job check. The generic no-argument cron worker processes
at most two 50-row keyset batches or five seconds, then schedules the earliest
remaining due or future attempt. It includes scheduled watchdog rows, revalidates
package/authority, repairs exact timestamps, and cannot strand a later retry
behind an earlier worker. Acceptance must model at least 250 reconciliation rows
across repeated invocations and 1,205 suspension rows to cross batch bounds.

### Campaign Studio operator and public-truth acceptance

The private operator surface must preview, copy and download the exact persisted
SHA-bound prepared artifact; upload and stream protected campaign/location/owner
bound evidence; show human-attested provider receipts, observed (not verified)
results, anonymous-unverified aggregates and campaign-linked moderation history/
SLA; and provide idempotent optimistic resolve/escalate/outcome transitions.
Double submission reuses one logical Idempotency-Key, controls stay disabled in
flight, and cancelled/blank/non-finite prompts cannot become numeric zero.

Evidence upload must be command/audit receipted, content-addressed, campaign-
bound, digest-verified and bounded by per-file, per-campaign and total quotas.
An insert failure may delete bytes only when that request created the target; it
must never delete a shared existing digest target.

Public readback and event claims must acquire the shared transaction boundary,
lock/reselect placement, current campaign and exact prepared package, and require
active/unexpired window plus exact lifecycle, scheduled version, package,
approval, schedule and authority bindings. Cancel/expiry races therefore return
no verified response and commit no anonymous event. Generic public event paths
must never call global object-cache flush; owned placement activation/readback/
cancel/expiry/suppression uses the explicit page-cache lifecycle instead.

Before accepting Campaign assets, verify the exact
`complete99-consumer-media-rights/v1` overlay: thirteen ordered archive stems,
pinned canonical digest, illustrative-public-use only, false paid/Campaign use
and empty photographer/contract receipts. The frozen consumer-menu and
cross-domain registry bytes must remain unchanged. Missing/tampered rights data
must empty the bundled projection and fail Campaign invariants; none of the
archive dish photos may appear as a Campaign choice. The packaged brand
illustration must still pass its exact byte digest.

Managed-host requests use a normal browser request signature with only standard
`Accept`, `Authorization`, `Content-Type` and `User-Agent` headers. Do not add an
`X-*` deployment header or send HTML/multipart bodies: UPress can reject those at
nginx before WordPress receives the request.

### Bounded artifact staging

The production driver never sends the full plugin ZIP inside `/run`. After
preflight reserves the exact deployment lock, the driver uploads the immutable
CI artifact through the authenticated staging route in sequential chunks of at
most 1 MiB. Every chunk carries its exact offset and SHA-256. The bridge accepts
only the next chunk or an identical replay of the last chunk after an ambiguous
transport loss.

Canonical base64 validation is deliberately non-regex at this boundary. It
uses the encoded-size ceiling, length modulo four, strict decoding and exact
re-encoding equality so an exact 1 MiB chunk cannot exhaust the PHP PCRE JIT
stack.

The bridge embeds the expected artifact SHA-256, exact byte count, release
version and installed-tree SHA-256. The final chunk must produce the exact whole
artifact digest and size before `/run` can begin. `/run` accepts only
`staged=true`, refuses the former `package_base64` field, rechecks the file before
and after claiming the lease and verifies the installed tree against release
metadata. Reserved finalization, rollback, finalization and recreated-bridge
recovery remove only the exact deployment staging directory.

The evidence and threat boundary for this transport are recorded in
`docs/chunked-artifact-staging-2026-08-08.md`.

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

## Release 1.22.1 Campaign bootstrap proof recovery

Release 1.22.1 is the corrective Campaign release. It does not adopt a failed
1.22 forward candidate whose Campaign invariants are false. Before deployment:

1. Confirm the plugin header and constants are exactly `1.22.1` and
   `c99-wp-1.22.1`, and the versioned Science policy is named for `1.22.1` while
   retaining the reviewed 47-stem, 175-file, 70-delivery and 105-repository-only
   boundary.
2. Confirm a fresh activation-recovery install seeds lifecycle `suspending`,
   completes the bounded aggregate cleanup, cron-empty and public-absence
   receipts, reaches receipt-bound `inactive`, and only then transitions active.
   Receipt-less direct inactive-to-active bootstrap is forbidden.
3. Confirm exact legacy `inactive` generation 1 and `active` generation 2 states
   are repaired only while the activation owner holds both lifecycle and worker
   fences and every generation-1 lifecycle receipt identity is wholly empty.
   Any partial, duplicate or conflicting provider/audit evidence fails closed.
4. Confirm inactive capacity binds receipt generation `G-1`, while resumed active
   capacity binds receipt generation `G-2` and its `inactiveGeneration=G-1`.
   Direct active generation 1 remains the only receipt-free non-recovery genesis.
5. For locked deployment `c99-prod-31598196288-1`, use the normal exact-main
   workflow recovery preflight. Stabilization must fail on the recorded Campaign
   invariant, trigger authenticated rollback to the exact reviewed 1.21 baseline,
   and verify that rollback before 1.22.1 mutation begins. Do not create or use a
   v2 forward-adoption proof for that unhealthy candidate.
6. After the corrected deployment, require Campaign capacity, lifecycle, cron,
   evidence and suppression invariants to be inspectable and ready; then require
   the dedicated worker's fresh 15-minute heartbeat before enabling an owned
   placement.

## Release 1.22.0 Campaign Studio baseline (historical)

Release 1.22.0 adds the reviewed WordPress-native Campaign boundary without
claiming that legacy Campaign data, external provider publication or production
deployment already exists. Before accepting the source candidate or building the
artifact:

1. Confirm the plugin header and constants are exactly `1.22.0` and
   `c99-wp-1.22.0`; all frozen Campaign, Platform, bridge, deployment, Ops,
   consumer UI, monitor and workflow inputs must retain their audited digests.
2. Confirm the versioned Science media policy is named for `1.22.0`, has raw
   SHA-256 `53b7932009c3f0af233feb6d485cf4774e8bfe6a715934b2d7a19ea7ab33c3f9`
   and schema-bound canonical digest
   `ff64987efd5fa169c4f9ece5fd8691d3b2d6f8a05df56bc2e76d11c89653e857`.
   It must retain exactly 47 stems, 175 repository files, 70 delivery files and
   105 repository-only files.
3. Confirm the generated changelog starts with a new 1.22 block and retains the
   complete literal 1.21 block before 1.20. Reproducible builds must agree on ZIP,
   source and integrity digests; the source candidate itself is not deployment
   evidence.
4. Confirm bridge v3 authenticates the exact seven Campaign tables and lifecycle
   reservation, and that fresh-request activation continuation, rollback,
   recovery and finalization preserve v1/v2 compatibility and reject mixed or
   ambiguous state.
5. Confirm Campaign mutations require WordPress capability, nonce, location
   scope, idempotency, exact version and durable audit truth. Owned-site placement
   requires immutable approval/package bindings, deterministic readback and a
   system receipt; external channels remain prepared manual packages.
6. Confirm deactivation, activation, quarantine, cleanup and generation recovery
   remain behind the shared deploy/lifecycle/worker/authority lock order. Public
   render and events fail closed during lifecycle or quarantine transitions, and
   exact cache/public-absence receipts precede terminal lifecycle truth.
7. Provision and prove both self-hosted runners as described above. The Campaign
   monitor must complete through its fixed authenticated route and report a fresh
   inspectable heartbeat without exposing credentials or protected row counts.
8. Confirm the legacy prototype's two demo campaigns, nine playbooks and any
   historic external-OS state are not imported as approvals, schedules,
   publications or results. Meta, TikTok, WhatsApp and Google Business remain
   manual packages; the legacy public host remains until route mapping,
   redirects, sitemap removal and anonymous cutover checks are complete.
9. Reprove the unchanged consumer boundary: 36 WooCommerce products, prices and
   stock authority, disabled payments, frozen consumer UI and search allowlist.
   No held Science asset, legacy Campaign record or external provider claim may
   enter the artifact or public truth.

After controlled deployment, run the owner-authenticated Campaign worker and
private invariants, verify the public health version/deployment ID and durable
heartbeat, exercise one reviewed owned-site schedule/readback/expiry lifecycle,
and recheck all legacy and external-provider negative cases. Keep credentials,
private evidence and provider payloads out of the audit bundle.

## Release 1.21.0 WordPress migration and search activation verification

Release 1.21.0 begins the owner-authorized application migration without
claiming that the external operating system or its historic records are already
ported. Before accepting the artifact locally:

1. Confirm the plugin header, update manifest, deployment marker and integrity
   metadata all report `1.21.0`, and confirm every frozen 1.20 artifact,
   checksum, integrity record and media policy remains byte-identical.
2. Confirm the private Complete99 OS Today shell uses WordPress authentication,
   the exact operations capability and a REST nonce, remains read-only, and
   reports all seven required operations tables with schema
   `complete99-ops-schema/v1`.
3. Confirm fresh/status/interrupted-forward/stabilize/finalize checkpoints call
   both operations and Culinary Science invariants, so missing operations state
   or a fail-closed search overlay cannot finalize after a version short circuit.
4. Confirm database manifest v2 captures only the exact seven operations tables
   and schema marker within its row/byte ceilings. Exercise first-install
   quarantine cleanup, unchanged baseline-present retry, historical v1 recovery
   and residue refusal; no `c99rb_*` table may remain before redeploy or finalize.
5. Confirm the search overlay schema/version/digest are
   `complete99-culinary-science-search-activation/v1`,
   `culinary-science-search-activation-2026.08.11.v1` and
   `0b191bef1612e56f2e97c1e4e5d15ab4f651d8e658e2eb742aea72cc2a2ac6e7`,
   pinned to unchanged v20 digest
   `677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54`.
6. Confirm effective activation is exactly 18 standalone owners and 36 unique
   Hebrew/English canonical sitemap URLs. Keep `preparation-ichiban-dashi`
   noindex, all eight section entities owner-canonical-only, query/filter states
   noindex and every held/private record excluded.
7. Confirm held products retain Product schema but emit no Offer until checkout
   and cart readiness both pass. Confirm all 36 WooCommerce products, prices,
   stock authority, cart behavior and disabled payment state remain unchanged.
8. Confirm exact legacy ChatGPT Sites defaults move to WordPress-owned app and
   asset destinations while any owner-configured canonical HTTPS value remains
   untouched.

After controlled deployment and cache purge, verify the private OS surface with
an authorized WordPress account, verify the 36 activated canonical routes and
sitemap entries anonymously, verify dashi/query/private negative cases, and run
fresh Hebrew/English Chrome acceptance. Search Console submission and coverage
measurement are post-deploy operational evidence, not package assumptions.

## Release 1.20.0 infrastructure and held-publication verification

The frozen 1.20 source was an infrastructure release candidate, not authorization
to publish the seven Syrian or five Japanese editorial candidates. No trusted
owner key or receipt exists. It may proceed only through protected `main`, a
green required CI result and the controlled WordPress workflow, and only while
the default-deny package keeps all held content private. This runbook records no
deployment or live-publication claim.

Before accepting the held candidate locally:

1. Confirm the artifact, manifest and deployment marker all report `1.20.0`.
2. Confirm science schema v6 and v20 contain exactly 672 entities and 375
   sources, commerce v14 binds to science v20, and Entity Studio resolves 728
   subjects.
3. Confirm the anonymous public projection remains exactly 27 science records,
   19 standalone owners per language and 38 bilingual routes, with zero
   indexable records.
4. Confirm the Japanese cluster is split into 24 public and 60 private records,
   and that the exact five koji and shoyu candidates expose no route, bundle,
   relation, measurement or asset.
5. Confirm the Syrian cluster is split into one public cuisine root and 281
   private records, and that the exact seven Aleppo candidates expose no route,
   bundle, relation or asset.
6. Confirm exactly three verified literature-context ranges remain private:
   neutral protease 500-700 U/g, acidic protease 50-150 U/g and leucine
   aminopeptidase 50-250 U/g.
7. Confirm the generated-asset manifest, which is an editorial evidence registry
   rather than an installed-file inventory, contains 60 catalog entries and
   twelve separately classified, held Science editorial entries. A file receipt
   must not be treated as publication approval.
8. Confirm the default-deny Science media policy inventories exactly 47 stems
   and 175 repository files: 28 public-delivery stems, 18 held repository-only
   stems and one approved superseded archive stem. The ZIP must contain exactly
   70 public delivery files and exclude all 105 repository-only files: 78 held,
   24 active public PNG source-evidence and three archive files. Confirm all 60
   formerly packaged held derivatives are absent while all 78 held files remain
   available as repository evidence.
9. Confirm cross-domain v3 contains exactly 95 unresolved census records and 11
   private reciprocal Woo candidates. Require all five verified or public
   indexes to be literally empty and the valid private decision overlay to
   report zero decisions and zero recognized reviewer authorities.
10. Confirm the exact 36 products, 56 product identities, 20 private planning
   prices, stock, cart and disabled payment state are unchanged.

Before publishing any held Science candidate in the future:

1. Establish the trusted owner key and record a valid approval-v2 receipt for
   each candidate proposed for publication. The receipt must bind the exact PNG
   source evidence separately from all four deployable WebP/AVIF variants and
   the complete Hebrew and English content.
2. Update the explicit publication-decision records and prove that unapproved,
   missing, malformed or stale receipts fail closed.
3. Build and independently validate a newly reviewed artifact containing the
   four exact approved delivery variants. Do not reinterpret an earlier held
   package as approved.
4. Deploy only through the controlled WordPress workflow, then verify anonymous
   health, exact approved routes, negative private-route evidence, asset
   identity, schema, sitemap, WooCommerce and disabled-payment state.
5. Run real Chrome acceptance in Hebrew and English at desktop and 390 CSS
   pixels, and prove bridge deletion, row absence and route 404.

## Preserved release 1.19.0 artifact audit, not live publication

The immutable 1.19.0 artifact may be retained and hash-audited, but it is not
evidence that its seven Syrian candidates were approved, installed or live. Do
not deploy or modify the historical artifact. An audit may confirm that it
encoded science v19 with 672 identities and 374 sources, commerce v13, 728
Entity Studio subjects, 34 public/noindex entities, 26 standalone owners per
language, 52 bilingual routes, a Syrian split of 8 public/noindex and 274
private records, seven science asset entries and the unchanged 60 catalog
assets. The absence of an owner receipt and live deployment means the
authoritative live boundary remained the 1.18 baseline of 27 entities, 19
standalone owners per language and 38 bilingual routes. Current v20 holds the
seven Syrian candidates and bulgur relation. The exact 36 products, prices,
stock, cart and disabled payment state were unchanged.

## Preserved release 1.18.2 live verification

Before installation:

1. Confirm the artifact, manifest and deployment marker all report `1.18.2`.
2. Confirm the deployment audit records a complete chunked-staging receipt with
   the exact artifact byte count and SHA-256 before the install gate begins.
3. Confirm the package changes no product seed, price, stock, supplier,
   WooCommerce materialization contract or payment gateway state.
4. Confirm the shelf contract uses twelve products per page and one validated
   allowlist for `product-type` and `product-page`.
5. Confirm every first-party product continuation uses the central page-aware
   product URL helper.
6. Confirm the schema graph emits only the current shelf page products and
   filtered states are `noindex,follow`.

After installation and cache purge:

1. Verify anonymous health returns version `1.18.2`, deployment ID
   `c99-wp-1.18.2` and the expected database version.
2. Verify Hebrew and English unfiltered stores expose three pages of twelve
   unique products with no omission or duplicate.
3. Verify the Japanese pantry filter exposes exactly nine products through
   server-rendered links and survives reload and language switching.
4. Verify a page-two product continuation focuses its exact stable anchor and
   add-to-cart returns to page two with cart feedback.
5. Verify canonical, hreflang, robots and Product schema match each tested
   state, and filtered utility states remain `noindex,follow`.
6. Run Chrome at 1440 by 1000 and 390 by 844. Require the first card inside the
   initial viewport, 44 by 44 controls, no horizontal overflow, no broken image
   and no first-party console warning or error.
7. Confirm the exact 36 products, prices, stock, cart and disabled payment state
   remain unchanged, then prove bridge deletion, row absence and route 404.

## Preserved release 1.18.0 live verification

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
- exact registry receipts for culinary science schema v6 and v20 and culinary
  commerce v14, with 672 science identities, 375 sources, 56 product identities and 728
  Entity Studio subjects;
- exact 56 of 56 product price-basis coverage, comprising 36 live prices and 20
  private planning prices, with no product, supplier, stock or checkout
  activation added by 1.21.0 and no Offer schema while readiness is held;
- exact 282-identity Syrian type counts, including one public cuisine root and
  281 private identities, the 86 release-1.16 private identities and four exact
  held safety records, with the seven Aleppo editorial candidates and bulgur
  relation private and no new price, product, offer, supplier or stock record;
- exact 96-identity Iraqi type counts and proof that all 96 remain private,
  noindex and reference-only, with no price observation, offer, stock, supplier
  or public projection;
- exact 203-identity Lebanese type counts, including the 121 new private
  identities and twelve exact held records, proof that the reviewed cuisine
  root is the only public noindex gateway, and proof that the other 202 remain
  private, noindex and reference-only;
- six dated Lebanese retail observations with no product identity, offer,
  stock, supplier, import route, landed cost or public price projection;
- exactly 27 raw public science entities, 19 standalone page owners per language
  and 38 bilingual routes, with the 84-identity Japanese cluster split into 24
  public and 60 private records; effective activation must be exactly 18 owners
  and 36 canonical sitemap routes, with dashi, eight section entities, query
  states and held/private records excluded;
- positive public evidence only for the pre-existing Syrian cuisine root and
  reviewed Lebanese root; negative evidence for the seven Aleppo and five
  Japanese editorial candidates, all 281 private Syrian records and every
  Lebanese child across API, search, sitemap, rendered routes, public catalog,
  POS and structured data;
- twelve exact held Science editorial evidence records, including seven Syrian
  and five Japanese sets, kept separate from the unchanged 60-entry generated
  catalog register and never interpreted as owner approval;
- approval-v2 status proving that no trusted owner key or receipt exists and
  that the PNG source-evidence receipts are distinct from the four deployable
  WebP/AVIF receipts and complete bilingual content;
- the exact default-deny Science media inventory of 47 stems and 175 files, with
  70 delivery files in the ZIP and 105 repository-only files absent, including
  all 78 held files, 24 active public PNG sources and the three-file superseded
  archive;
- exactly three private verified literature-context ranges with complete method
  and source scope, and zero public measurement rendering from the held cohort;
- cross-domain v3 with exactly 95 unresolved census records, 11 private Woo
  candidates, five literally empty verified or public indexes, zero decisions
  and zero recognized reviewer authorities;
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
