# Complete99 1.18.0 interrupted-forward recovery

## Purpose

This record governs the single interrupted production deployment
`c99-prod-31217684760-1`. It does not authorize a general rollback, a new
plugin installation or a change to the payment state.

The public site currently serves Complete99 Platform 1.18.0, but the original
deployment request ended before the durable forward checkpoint and final
cleanup. The failed recovery then refused to restore the 1.17.0 database
snapshot because the current database fingerprint no longer matched the
reviewed baseline. A blind retry or forced rollback is therefore forbidden.

## Bound release identities

- Failed GitHub Actions run: `31217684760`
- Failed deployment: `c99-prod-31217684760-1`
- Source commit: `cd87bc3fc266e8262f23443ca9c9f4c438c5c47e`
- Version: `1.18.0`
- ZIP SHA-256: `6471075a4391c34a573e6c04ccac3b707c005119bd4c618c669a7eb9888d31b0`
- Installed plugin SHA-256: `8216376a993505e18bf616362df1db6318d9382319d53d70e58390bcdb60becc`
- Source SHA-256: `3bcc9c6834a0079d74f6d9e7d7b044af4db68937618611dc8480df8765ab7b0b`
- Reviewed 1.17.0 database baseline:
  `a20aaab75137c773bf9176fa88826553c6c67ca490a597e5704ac1541425f9a6`
- Reviewed current database fingerprint from the failed recovery:
  `4976f887006e5432140fcfa9a0fb6c1dcfadc1ec8f94f7a7f181d316e33e04ff`
- Reviewed current database manifest SHA-256:
  `3dd22e12eb14581b6820f27265f88623c12ecf4595c12c45d42a02f494674374`
- Managed robots SHA-256:
  `b25fdd90cfd62119544ff19ddecf01bd33f94e66e8645190f03c06cd32229b7e`

The exact historical proof is
`docs/recovery-proofs/c99-prod-31217684760-1.json`. Its source audits are kept
under `docs/recovery-proofs/observations/` and are bound by SHA-256.

## Two-step recovery

### Step 1: observation only

Run the production workflow from `main` with:

```text
interrupted_forward_proof=docs/recovery-proofs/c99-prod-31217684760-1.json
interrupted_forward_observe_only=true
recovery_only=false
orphaned_rollback_proof=
orphaned_rollback_observe_only=false
```

This mode may create and delete its temporary authenticated bridge. It may
only read the stale owner state, plugin tree, WordPress runtime, database
snapshot and manifest, transactional storage, migration invariants, sync
configuration, robots file, public health and rendered homepage. It must not
stabilize, finalize, roll back, deploy, purge caches or materialize commerce.

The resulting non-secret recovery audit must be copied into
`docs/recovery-proofs/observations/` without changing its bytes. A reviewed v2
proof then binds that audit, its workflow commit and run ID, the complete
database manifest, storage identity and every current release identity.

### Step 2: proof-gated forward adoption

After the reviewed v2 proof is merged to `main`, run the workflow with:

```text
interrupted_forward_proof=docs/recovery-proofs/c99-prod-31217684760-1-v2.json
interrupted_forward_observe_only=false
recovery_only=true
orphaned_rollback_proof=
orphaned_rollback_observe_only=false
```

The recovery bridge may adopt the forward release only when every live value
still equals the reviewed observation. This includes the artifact identity,
installed tree, version, active state, deployment marker, database version,
database fingerprint, database manifest, transactional tables, configured
sync state, migration invariants, robots file, prior rollback journals and the
absence of rollback or swap artifacts.

The bridge records `adopted_forward_no_rollback: true` before finalization.
After that checkpoint, rollback is categorically refused. Recovery can only
verify the same adopted release and complete final cleanup. This prevents the
old 1.17.0 snapshot from deleting database changes created by the active
1.18.0 migration.

The same workflow then performs a new dry-run acceptance and materializes the
exact 36-product WooCommerce catalog. Payment remains disabled. A successful
run must produce a verified commerce audit as well as platform recovery,
health, homepage, robots, lock release, state removal, bridge deletion and
route-404 evidence.

If platform finalization already completed but its response or a later stage
was lost, the same v2 recovery-only run uses a fresh reserved probe for a
strict, read-only final-state attestation. It accepts only the exact reviewed
plugin/runtime, full database fingerprint and manifest, transactional storage,
deployment marker, sync state and managed robots identity, with the failed
state, lock and rollback artifacts absent. It finalizes only the probe and
records `result: already-recovered`. Any commerce-created database change
rejects this route, so it is valid only before commerce materialization. A
leaked earlier probe reservation is released only after its lease is stale and
its status proves reserved, state-free, unadopted and mutation-free; the full
attestation is then repeated under a new fresh probe.

## Stop conditions

Stop without mutation if any reviewed digest, version, deployment ID,
database row, database manifest component, storage engine, sync state,
migration invariant, plugin state, robots digest, journal digest, bridge proof
digest or public acceptance result differs.

Do not rerun the original 31 MB inline installation. Do not force the old
rollback. Do not edit the live database marker manually. Do not activate a
payment gateway during recovery.

## Required closing evidence

Recovery is complete only when all of the following are true:

1. The recovery audit result is either `recovered` with decision
   `adopt_interrupted_forward`, or `already-recovered` with decision
   `attest_interrupted_forward_finalized` and the exact v2 attestation receipt.
2. The adoption receipt and status both contain the exact v2 proof digest,
   installed plugin digest and reviewed database manifest digest.
3. Health and rendered-home checks report version and database version 1.18.0
   with deployment `c99-prod-31217684760-1`.
4. Finalization reports `finalized`, `lock_released` and `state_removed` as
   true.
5. The temporary bridge row is absent and its route returns 404.
6. The dry-run audit passes from the same `main` commit.
7. WooCommerce materialization verifies the exact 36 products, stock and
   disabled payment state.
8. Hebrew and English Chrome acceptance passes on the affected museum,
   knowledge and store routes.
