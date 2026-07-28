# Pipeline acceptance evidence

Date: 2026-07-28

Release: `complete99-platform` 1.0.2

Artifact SHA-256: `612d9d5fb23a52fa4e96f86c31cbe5359931782e4e72f59fe9ccb350f0f0ecc9`

Source SHA-256: `7a470eff16f59147d3843777000aea5b98475dd2c6d4b44ff52cc961c0157bd0`

## Test environment

- WordPress 6.9.4
- PHP 8.3.31
- Code Snippets 3.9.6
- SQLite Database Integration 2.2.23
- real WordPress Application Password authentication
- real Code Snippets REST create/deactivate/delete lifecycle
- loopback-only native WordPress; no UPress staging or temporary UPress site

## Executed proofs

| Deployment ID | Scenario | Observed result |
|---|---|---|
| `c99-canonical-release-1` | Canonical cross-platform ZIP → rollback → redeploy | Artifact/source digests exact; database/plugin restored; health and database version OK; route 404 |
| `c99-dry-native-txn-1` | Authenticated dry preflight | Passed; lock released, snippet inactive, route 404 |
| `c99-native-rollback-txn-1` | Install → health/body → rollback → redeploy | Deployed; identical database fingerprint, exact plugin digest, health OK |
| `c99-fault-db-capture-1` | Forced database capture failure | Failed closed before lock/mutation; route 404 |
| `c99-fault-after-prepare-1` | Request loss after encrypted backup preparation | Automatic rollback; database and files exact; finalized; route 404 |
| `c99-fault-after-install-1` | Request loss after plugin installation/activation | Automatic rollback; prior health restored; finalized; route 404 |
| `c99-fault-during-rollback-1` | Request loss after atomic target displacement | Resumed rollback, exact restore, redeployed, finalized; route 404 |
| `c99-fault-after-commit-1` | Cleanup failure after commit decision | Idempotent cleanup retry; deployment retained; route 404 |
| `c99-first-install-fault-1` | First install interrupted after activation | Database exact; plugin REST/health absent; plugin directory absent; route 404 |
| `c99-first-install-success-1` | Normal first install | Deployed; health and database version 1.0.2; body marker exact; route 404 |
| `c99-recreated-route-recovery-1` | Original route deleted in stale `installing` | Fresh bridge decrypted journal, rolled back exactly, finalized, route 404 |
| `c99-recreated-commit-cleanup-1` | Original route deleted in `cleanup_failed` | Fresh bridge preserved committed release, completed cleanup, route 404 |

The concurrency exercise held `c99-concurrency-owner-2` in `reserved` while
`c99-concurrency-contender-2` attempted preflight. The contender received the
expected `409 c99_deploy_locked`; the owner route remained intact. After owner
finalization, the contender acquired the lock. Both routes were then deleted and
independently returned 404.

After all exercises, both native WordPress installations reported:

- no `complete99_deploy_lock` option;
- zero deployment state directories;
- zero `.complete99-restore-*` or `.complete99-displaced-*` directories;
- all audited temporary routes returned 404.

## Static release gates

- 26/26 contract tests pass.
- PHP lint passes for every plugin and bridge PHP file.
- Python compilation passes for deploy, recovery, build and release scripts.
- secret scan passes.
- two package builds are byte-for-byte identical.
- Windows and Linux text inputs canonicalize to the same LF package bytes.
- independent archive validation passes with 130 entries.
- checked manifest targets WordPress 6.9 and requires PHP 8.0 / WordPress 6.4.

## Evidence boundary

This document proves the package and end-to-end deployment machinery on disposable
native WordPress. It is not evidence of a production UPress deployment. Production
acceptance still requires the final `complete99.co.il` site, HTTPS, REST access,
dedicated Application Password, GitHub production secrets and a live dry-run plus
deployment audit against that exact origin. No staging site is required or allowed.
