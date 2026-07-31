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
3. Build twice and require identical bytes:

   `python scripts/build-plugin-zip.py --verify-reproducible`

4. Inspect and validate the artifact:

   `python scripts/validate-package.py`

5. Confirm `plugin-dist/complete99-platform.json` is the public PUC update
   manifest and `plugin-dist/complete99-platform-integrity.json` contains the
   independently checked artifact digest and size.
6. Review source and metadata, then merge to `main`.

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

## Completion evidence

A production release is complete only when the non-secret audit JSON shows:

- artifact digest and version;
- authenticated deployment role;
- direct-filesystem preflight;
- transactional-storage preflight and run-time database baseline;
- expected public health version and deployment ID;
- matching health `database_version`;
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
