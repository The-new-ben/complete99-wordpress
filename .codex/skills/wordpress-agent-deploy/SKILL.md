---
name: wordpress-agent-deploy
description: MANDATORY for any WordPress install, update, deploy, or hotfix. Ship reviewed plugin code to a live managed-hosting site with zero manual clicks and no FTP/SSH by using deterministic Git artifacts and a temporary admin-gated REST route running Plugin_Upgrader. Covers protected-branch release gates, UPress WAF-safe REST transport, Windows self-hosted runners, rollback proof, cache discipline, verification, cleanup, and emergency recovery.
---

# WordPress Agent Deploy Pipeline

This contract is proven across many production releases on managed WordPress
hosting, including a zero-touch first install, rollback exercise, and redeploy.
For UPress transport and Windows runner details, read
`references/upress-windows-runner-and-waf.md`.

## The architecture in one line
Reviewed code -> required CI on protected `main` -> deterministic
`plugin-dist/<slug>-<ver>.zip` plus SHA-256 -> immutable release source -> a
TEMPORARY admin-only REST route runs
`Plugin_Upgrader->install($zip, ['overwrite_package'=>true])` -> independently
verify -> permanently DELETE the route and its exact Code Snippets row.

Build the artifact once from the exact reviewed commit. Deploy that artifact;
never rebuild after approval. Record the source commit, artifact SHA-256,
release version, deployment ID, and verification evidence together.

## Reference implementation facts (clone these on a new site)
- Update library: **plugin-update-checker v5 (v5p6) by YahnisElsts**, vendored at
  `<plugin>/lib/plugin-update-checker/`, booted on `init` prio 5 with
  `PucFactory::buildUpdateChecker( MANIFEST_URL, __FILE__, '<slug>' )`.
- Manifest: `https://raw.githubusercontent.com/<OWNER>/<REPO>/main/plugin-dist/<slug>.json`
  with fields: `name, slug, version, author, homepage, requires, tested,
  requires_php, download_url, last_updated, sections{changelog}`.
- Primary update trigger: the agent's temp REST route (seconds after merge).
  PUC/wp-admin is the human-fallback path; WP's `auto_update_plugins` cron path
  works too (lands within <=12h) but keep it OFF for deliberate deploys.
  The deliberate deploy URL must be pinned to the reviewed commit SHA or an
  immutable release asset; `main` is acceptable for the fallback manifest only.
- Cache handling after install, inside the same route: `do_action('litespeed_purge_all')`
  (no-op when absent) + `wp_cache_flush()`; plus every asset enqueued with a
  version CONSTANT that equals the plugin header version (never hardcode `?ver=`).
- Theme-level changes: the theme does NOT auto-update. ALL ongoing behavior
  ships inside the plugin via hooks (`the_content`, `wp_head`/`wp_footer`,
  `wp_add_inline_style/script`, shortcodes, REST). The theme stays static chrome.
- A direct-live release is allowed when staging is unavailable, but only after
  local and CI gates pass, the recovery probe succeeds, a scoped backup exists,
  the deploy lock is held, and the tested rollback can restore both plugin files
  and plugin-owned transactional data. "No staging" never means "no tests."

## One-time setup on a new site
1. wp-admin -> Plugins -> install + activate **Code Snippets** (free).
2. wp-admin -> Users -> your admin -> **Application Passwords** -> create one.
   Store as env vars (never in the repo): `WP_BASE_URL`, `WP_USER`, `WP_APP_PASSWORD`.
   Authenticate with standard Basic auth, normal `User-Agent`, and JSON only.
   Do not add custom deploy headers or post raw HTML.
3. Settings -> Permalinks -> "Post name" (custom REST namespaces need it).
4. Create the ops plugin in Git: main file with header `Version:` + a
   `<SLUG>_VERSION` constant (kept equal), a public GET `/healthcheck` REST route
   returning the version, and the vendored plugin-update-checker boot (facts above).
5. Create or reuse one canonical `scripts/build-plugin-zip.py` that emits a
   deterministic archive, uses forward slashes only, and asserts version sync.
   Create `plugin-dist/<slug>.json`.
6. Install the plugin the FIRST time by any means (wp-admin upload of the zip is
   fine), or bootstrap it through the temporary route. Every later install uses
   the same audited loop.
7. Protect `main`. Require the validation workflow before merge. Run production
   deployment only from the exact protected-main commit.
8. If hosted runners cannot reach the managed host, register a repo-scoped
   Windows self-hosted runner under `C:\actions-runner\<repo>` and follow the
   reference. Do not place it in Documents, OneDrive, or a working repo.

## The release ritual (every version, in this order, no skips)
1. Bump the version in BOTH places (header + constant) with one sed.
2. `php -l` EVERY changed file. A syntax error deployed is an outage.
3. `python3 scripts/build-plugin-zip.py <ver>`.
4. Open the zip and ASSERT the changed code is inside it (marker string), plus
   both version strings. Extract and lint the PHP files from the zip. Never
   deploy on faith.
5. Compute SHA-256 and update the manifest JSON (`version`, `download_url`,
   changelog, and checksum when the driver supports it).
6. Commit and merge only after required CI passes. Confirm the immutable/raw zip
   URL returns 200, download it independently, and assert its SHA-256 equals the
   reviewed artifact.
7. Probe live health and the emergency recovery channel before making a write.
   Acquire a deployment lock and capture the plugin-file plus scoped database
   backup described in the reference.

## The deploy loop (create -> call -> verify -> delete)
Snippet PHP (the only thing that ever runs privileged code):
```php
add_action( 'rest_api_init', function () {
  register_rest_route( 'agentdeploy/v1', '/run', array(
    'methods' => 'POST',
    'permission_callback' => function () { return current_user_can( 'update_plugins' ); },
    'callback' => function () {
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/misc.php';
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
      require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      $plugin = 'SLUG/SLUG.php';
      $zip = 'https://raw.githubusercontent.com/OWNER/REPO/SOURCE_SHA/plugin-dist/SLUG-VERSION.zip?nlcb=' . time();
      $skin = new WP_Ajax_Upgrader_Skin();
      $up = new Plugin_Upgrader( $skin );
      $ok = $up->install( $zip, array( 'overwrite_package' => true ) );
      if ( ! is_plugin_active( $plugin ) ) { activate_plugin( $plugin ); }
      do_action( 'litespeed_purge_all' ); wp_cache_flush();
      return array( 'result' => is_wp_error( $ok ) ? $ok->get_error_message() : var_export( $ok, true ),
                     'messages' => $skin->get_upgrade_messages(), 'active' => is_plugin_active( $plugin ) );
    },
  ) );
} );
```
Drive it over REST (Code Snippets API):
```bash
# create (scope global + active so rest_api_init fires)
SID=$(curl -s -u "$WP_USER:$WP_APP_PASSWORD" -H "Content-Type: application/json" \
  -d "$(python3 -c "import json;print(json.dumps({'name':'tmp-deploy','code':open('deploy-snippet.php').read(),'scope':'global','active':True}))")" \
  "$WP_BASE_URL/wp-json/code-snippets/v1/snippets" | python3 -c "import sys,json;print(json.load(sys.stdin)['id'])")
# call
curl -s -u "$WP_USER:$WP_APP_PASSWORD" -X POST "$WP_BASE_URL/wp-json/agentdeploy/v1/run" --max-time 180
# verify version flipped
curl -s "$WP_BASE_URL/wp-json/SLUG/v1/healthcheck"
# DELETE the snippet, then confirm the route 404s
curl -s -u "$WP_USER:$WP_APP_PASSWORD" -X DELETE "$WP_BASE_URL/wp-json/code-snippets/v1/snippets/$SID"
curl -s -u "$WP_USER:$WP_APP_PASSWORD" -X POST "$WP_BASE_URL/wp-json/agentdeploy/v1/run" -o /dev/null -w "%{http_code}\n"  # want 404
```

Do not hardcode pretty REST URLs in the driver. Generate both transports from
one route:

- preferred: `<base>/wp-json/<namespace>/<route>`
- UPress fallback: `<base>/?rest_route=/<namespace>/<route>`

Use the fallback **only** when the preferred request returns HTTP 403 and the
response is demonstrably non-JSON host/nginx/WAF HTML. Repeat the same HTTP
method, Basic auth, JSON body, and normal headers. Never fall back after a JSON
WordPress 401/403 or any WordPress REST authorization error. That is an auth or
capability failure and must remain closed.

## Fresh-request migration stabilization

An active-plugin overwrite can finish in one PHP request while the new plugin's
`init` migration cannot run until the next request. Never snapshot the
installer-request database state and immediately treat it as the final forward
state.

1. End the installer request in a dedicated clean
   `installed_pending_stabilization` state after its package temp file is gone.
2. Call a separate admin-gated stabilization route. That fresh request loads the
   new plugin and runs its migration before the callback.
3. Require the exact recorded directory SHA-256, plugin header version, loaded
   runtime version constant, active state, durable database version, successful
   migration invariants, empty temp path, and absence of rollback swap artifacts.
4. Persist the runtime deployment ID, read it back directly from `wp_options`,
   purge caches, capture the post-migration database fingerprint, and atomically
   record `stabilized=true`.
5. Re-read status independently. The current fingerprint must equal the recorded
   post-migration fingerprint before health, body verification, or finalization.
6. Finalization must refuse an installed release without `stabilized=true`.

An idempotent retry compares the current fingerprint with the recorded one and
returns it without recapturing or mutating state. Never forward-stabilize a
generic `failed` or `rollback_failed` state. A migration preserves an existing
bridge-owned runtime deployment ID; use the plugin build ID only when the option
is genuinely absent on first activation.

## Verification and evidence contract

A deploy is not complete until an independent audit proves all of these:

1. Public healthcheck reports the intended version and deployment marker.
2. The rendered `<body>` contains a new release-specific marker and the old
   marker is absent. Do not search the whole HTML; head assets can lie.
3. The installed plugin is active and its on-disk version matches the artifact.
4. The exact source commit and independently downloaded artifact SHA-256 match
   the protected-main release.
5. Cache purge ran (`litespeed_purge_all` when present plus `wp_cache_flush`).
6. The temporary route now returns 404.
7. The exact Code Snippets row ID is absent, not merely inactive or trashed.
8. The deploy lock, state row, backup, and temporary files were released or
   removed as appropriate.
9. A fresh screenshot or browser inspection shows the intended live page.

Treat proxy response bodies as hints. Independent GETs and exact state checks
are the source of truth.

## Mandatory rollback exercise

Before calling a new-site pipeline production-ready, exercise this sequence on
the live site during a controlled window:

1. Verify all scoped plugin-owned tables use a transactional engine such as
   InnoDB. Capture a scoped database snapshot and a restorable archive of the
   currently active plugin directory.
2. Deploy the candidate and pass the full evidence contract.
3. Restore the prior plugin archive and plugin-owned database snapshot as one
   rollback operation. Fail closed if either half cannot be restored.
4. Independently verify the prior health version, rendered-body marker,
   database marker/schema state, plugin activation, and cache state.
5. Redeploy the exact same reviewed candidate artifact (same SHA-256), pass all
   evidence checks again, and remove temporary rollback material.

Do not call a backup "tested" until this restore-and-redeploy loop succeeds.
Do not attempt transaction semantics across non-transactional tables.

## Hard rules (violations have caused real outages)
0. **THE LINT GATE IS A CHAIN, NOT A LINE (outage 2026-07-12).** `php -l` printed
   a parse error and the deploy STILL ran, because lint / build / deploy were
   separate lines in one Bash call - the failure did not stop the sequence, and
   the site went down hard (every WP entry point 500s; with outbound mail dead
   the WP recovery email never arrives - there is NO self-service recovery).
   The ONLY acceptable shape is one `&&` chain from lint to the deploy call:
   `php -l a.php && php -l b.php && build && assert-zip && upload && deploy`.
   Additionally: extract the changed file(s) FROM the built zip and `php -l`
   them again before upload - the zip is what ships, not the working tree.
   Root cause that day: an apostrophe in a CSS comment that lived inside a
   single-quoted PHP string. Watch every apostrophe near PHP strings.
1. **NEVER top-level privileged code in a global snippet.** Global snippets run
   on EVERY request at plugins_loaded; one fatal (e.g. `wp_update_post` before
   `init`) 500s the whole site including the REST API you'd fix it with.
   One-shot code goes INSIDE an admin-gated route callback, always.
2. **Never leave the deploy route active.** Create, call, delete - one deploy.
3. **Prefer `install(overwrite_package)`** over forcing the `update_plugins`
   transient + `upgrade()`: vendored PUC rewrites that transient and the deploy
   silently no-ops ("plugin is at the latest version").
4. **Always `?nlcb=<time()>` on the zip URL** - GitHub raw caches ~5 minutes.
5. **Verify the RENDERED BODY, not whole-HTML substrings.** Head assets contain
   your class names; slice from `<body>` and also assert the OLD markup is ABSENT.
6. **Zip only via the canonical Python builder** - ad-hoc Windows zips poison
   paths with backslashes and WordPress mis-extracts silently.
7. **Never print `WP_APP_PASSWORD`** to output/logs/commits. Public repo = the
   zip must contain no secrets. Mask credentials in CI, never echo auth headers,
   and never include secrets in artifacts, audit bundles, screenshots, or URLs.
8. Response bodies lie behind some proxies (404 body on a successful write) -
   truth is the independent GET verification, always.
9. **The route permission check is mandatory.** Every privileged callback must
   use `current_user_can('update_plugins')`; authentication alone is not enough.
10. **One deployment, one bridge.** Permanently delete the exact snippet row,
    verify row absence, then verify the route is 404. Inactive is insufficient.
11. **Never bypass a WordPress JSON denial.** Query-transport fallback is for a
    proven host/WAF HTML 403 only.
12. **Deploy only protected-main bytes.** Artifact/source mismatch, missing
    required CI, failed recovery probe, missing backup, or failed lock stops the
    release before a live write.
13. **Checkpoint after the new-code request.** Plugin/database version changes
    can occur on the request after `Plugin_Upgrader` returns. Require the
    fresh-request stabilization contract above; otherwise health can fail and a
    correct rollback can refuse unknown database drift.

## Emergency recovery (memorize before you need it)
- Site 500 and REST dead: host File Manager -> `wp-content/plugins/` -> rename the
  offending plugin folder to `<name>.off` -> site returns instantly.
- Runaway Code Snippets specifically: add `define('CODE_SNIPPETS_SAFE_MODE', true);`
  to wp-config.php right after `<?php`, rename the folder back, delete bad
  snippets via REST, remove the define. Or SQL: `UPDATE wp_snippets SET active=0;`.
- After recovery: re-apply the intended change as reviewed plugin code, never as
  another live snippet. Mirror any emergency edit back into Git immediately.
- Keep the host recovery procedure tested and reachable before every direct-live
  deploy. If it cannot be reached, stop before changing production.
