# UPress, Windows runner, WAF, and rollback reference

Read this reference when deploying to UPress or another managed host that blocks
cloud-runner traffic, rewrites REST responses, or offers no staging capacity.
The parent `SKILL.md` remains the release contract.

## Protected release topology

Use a protected default branch with a required validation workflow. Validation
must lint source PHP, build the deterministic zip, inspect its paths and marker
content, extract and lint the PHP that will actually ship, test the driver, and
publish the artifact plus SHA-256. The production workflow must accept only the
exact protected-main commit and must not rebuild the zip.

Record at least:

- source commit SHA;
- artifact filename, version, and SHA-256;
- deployment ID and UTC time;
- target base URL without credentials;
- preflight/recovery-probe result;
- install, activation, cache, rollback, and cleanup results;
- healthcheck and rendered-body positive/negative assertions;
- route 404 and exact temporary-snippet-row absence.

Keep the audit bundle free of credentials and authorization headers.

## Windows self-hosted runner without a reboot

Use this only when a hosted runner cannot reliably reach or authenticate to the
managed host.

1. Install the current GitHub Actions runner in
   `C:\actions-runner\<repo-or-site>`. Avoid Documents, OneDrive, network
   folders, and the checked-out repository; Worker IPC and file synchronization
   can fail there.
2. Register it repo-scoped (or narrowly scoped at the organization level) with
   the required labels. Keep registration tokens and generated runner
   credentials out of Git.
3. Prefer the runner service when available. If service installation requires
   an unavailable elevation/reboot, create a hidden Startup launcher at
   `%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\<name>.vbs` that
   starts the runner's `run.cmd` with its working directory and window style
   hidden. Run that launcher immediately; a reboot is not required.
4. Confirm GitHub reports the runner online and execute a read-only diagnostic
   workflow before dispatching production.
5. Allow only one runner process for that registration and working directory.
   Preserve other active agents and processes.

Example launcher shape (substitute explicit validated paths):

```vbscript
Set shell = CreateObject("WScript.Shell")
shell.CurrentDirectory = "C:\actions-runner\example"
shell.Run """C:\actions-runner\example\run.cmd""", 0, False
```

On a non-admin Windows runner, `actions/setup-python` may attempt a first-time
all-users tool-cache install and hang or fail. If compatible system runtimes are
already installed, do not mutate the machine:

1. resolve `python`/`py` and `php` from PATH;
2. print versions only (never environment secrets);
3. assert supported version ranges and required extensions;
4. fail the workflow before any live write if a runtime is absent or wrong.

Pin workflow actions by trusted version or commit according to repository
policy. Keep the runner updated and monitor its online state.

## UPress REST transport and WAF classification

Send ordinary HTTPS requests:

- Application Password through standard HTTP Basic authentication;
- normal `User-Agent`;
- `Content-Type: application/json` for JSON bodies;
- no custom deploy/security headers;
- no multipart body or raw HTML payload unless an unavoidable, separately
  reviewed endpoint requires it.

UPress/nginx/WAF can reject a pretty REST URL before WordPress executes. A safe
driver distinguishes this from a real WordPress denial:

1. Send the request to `/wp-json/<namespace>/<route>`.
2. If it succeeds, use the response.
3. If it returns 403, inspect `Content-Type` and parse the body.
4. Only when the body is non-JSON host/nginx/WAF HTML, retry the same route as
   `/?rest_route=/<namespace>/<route>`.
5. Preserve method, Basic auth, JSON body, and standard headers.
6. If the first response is WordPress JSON (for example `rest_forbidden`), or
   the fallback fails, stop. Never convert an authorization failure into a
   transport retry loop or bypass.

Implement this as a small, tested transport function. Tests must cover JSON
WordPress 401/403, HTML host 403, malformed content types, success, timeout, and
fallback failure. Logs may name the chosen transport but must not include the
Authorization value.

## Temporary Code Snippets bridge

Create one globally active snippet whose top level does nothing except register
an authenticated REST route. Place all one-shot privileged work inside the route
callback. Its permission callback must require
`current_user_can('update_plugins')`.

The driver lifecycle is:

1. create snippet and retain its exact numeric ID;
2. call the admin-gated route once while holding the deploy lock;
3. verify the live result independently;
4. permanently delete that exact row, including any trash state;
5. query state to prove the exact row is absent;
6. call the route again and require 404.

Use `Plugin_Upgrader->install($zip_url, ['overwrite_package' => true])`, activate
only the expected plugin path, purge LiteSpeed when available, and flush object
cache. Add a unique cache-buster to the immutable/raw download URL, but verify
the independently downloaded bytes against the expected SHA-256 before install.

## Recovery probe and direct-live discipline

No staging is acceptable only when local and CI tests are strong and rollback
has been exercised. Before every write:

- verify public health and administrative authentication;
- verify the host recovery path is reachable;
- acquire an expiring deployment lock;
- confirm enough disk space for active, incoming, and backup plugin copies;
- capture a restorable plugin archive;
- inventory plugin-owned options/tables and confirm transactional engines;
- capture a scoped database snapshot;
- record the current active version and rendered-body marker.

Keep live changes narrow, backward compatible, and deterministic. Do not combine
an infrastructure migration, large content rewrite, and visual redesign in one
unrecoverable release.

## Post-install migration checkpoint incident

On 2026-07-28 a protected-main UPress release installed the exact reviewed ZIP,
but its first independent health request loaded the new plugin and ran the
content migration. The installer-request database fingerprint was therefore
stale, and the migration temporarily replaced the dynamic production deployment
marker with the plugin build marker. Health correctly failed on deployment
identity; rollback correctly refused the now-unknown database fingerprint.

The durable fix is a fresh-request stabilization checkpoint:

- migration preserves any existing runtime deployment marker;
- the installer ends in a clean forward-pending state;
- a separate privileged request proves runtime constant, header version,
  directory digest, active state, migration invariants, durable database version,
  temp cleanup, and absence of rollback artifacts;
- only that request writes the production marker and post-migration database
  fingerprint;
- status independently proves the fingerprint and `stabilized=true`;
- finalization rejects an unstabilized install;
- retries compare the recorded checkpoint and cannot absorb later drift.

Acceptance includes an injected interruption after the forward install and
before temp cleanup, followed by a full rollback and identical-artifact redeploy.

## Rollback proof

For a first launch or pipeline change, exercise rollback rather than simulating
it:

1. install the reviewed candidate and verify health, body, active plugin,
   database state, cache, source SHA, and artifact SHA;
2. restore the prior plugin directory from its archive;
3. restore plugin-owned InnoDB data from the scoped snapshot inside a checked
   transaction; abort and retain recovery material on any error;
4. verify the prior version and marker are back and the candidate marker is
   absent;
5. reinstall the identical candidate artifact and verify the candidate again;
6. remove backup, temporary, state, and lock material only after final success.

Filesystem changes are not part of a SQL transaction, so the driver must treat
plugin files and database restoration as two explicit halves of one audited
rollback operation. If either half fails, report recovery-required and preserve
all remaining evidence/material.

## Evidence that closes the deployment

The closing audit must independently prove:

- expected healthcheck version and deployment marker;
- rendered `<body>` contains the new marker and lacks the old marker;
- plugin is active at the expected path;
- downloaded artifact SHA-256 and source commit match the release;
- rollback exercise passed when required;
- cache flush completed;
- snippet ID is absent from storage;
- temporary route is 404;
- lock/state/temp artifacts are gone;
- desktop/mobile browser inspection or screenshots reflect the intended live UI.

A successful installer response alone is never deployment proof.
