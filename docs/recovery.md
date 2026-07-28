# Recovery

## Pipeline failure while REST still works

The deployer calls rollback before deleting the temporary bridge, verifies the exact
database fingerprint and prior plugin-directory digest, checks the restored public
surface, then permanently removes the bridge row and proves 404. Preserve the non-secret audit
artifact and stop promotion.

If the process disappeared during a transitional phase, wait for the recovery lease.
The normal workflow does this automatically. To recover the same deployment from a
new agent process, use:

```powershell
python scripts/recover-wordpress.py --discover
```

Use `--deployment-id <original-deployment-id>` when that exact ID is already known.
Discovery creates an authenticated probe, reads only the lock owner returned by
WordPress, removes the probe, and recovers that exact owner.

This recreates a temporary route with a new random token. The encrypted journal key
is recoverable only inside the same WordPress installation because it is derived from
the deployment ID and the site’s auth salt. The command either rolls back an
uncommitted mutation or finishes cleanup for an already committed release; it never
rolls back a committed release.

## Site returns 500 and REST is unavailable

In UPress File Manager:

1. open `wp-content/plugins/`;
2. rename `complete99-platform` to `complete99-platform.off`;
3. verify the public site and `/wp-json/` return;
4. inspect the PHP error log without exposing credentials;
5. repair reviewed source, build a new deterministic package, and deploy through the
   normal transaction.

Do not make a second unreviewed live snippet to compensate for the first failure.

## Code Snippets runaway

If a temporary snippet remains active and breaks requests, enable
`CODE_SNIPPETS_SAFE_MODE` in `wp-config.php` through the UPress file manager, remove
the offending snippet, then remove safe mode. If necessary, deactivate snippets in
the plugin’s storage with a documented host/database recovery. Mirror any emergency
change into reviewed source immediately.

## Lost deployment credential

Revoke that one Application Password in the dedicated WordPress user, rotate the
GitHub environment secret, run an authenticated dry preflight, and review WordPress
user/activity logs. Do not reuse the owner’s personal password.

## Unresolved cleanup

If commit cleanup fails, keep the release decision: do not roll back a committed
version. Run `recover-wordpress.py` with the original ID until backup removal and
lock release are proven. The recovery command proves its own snippet row is absent
and its route is 404. A release is failed until `state_removed`, `lock_released`,
`row_absence_verified` and `route_404` are true, even when the new public version
appears healthy.
