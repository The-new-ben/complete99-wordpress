# Chunked WordPress artifact staging, 2026-08-08

## Why this transport exists

The exact Complete99 Platform 1.18.1 artifact passed local validation, CI,
reproducible build verification and the live dry-run gate. Two production
installation attempts then failed at the same synchronous `/run` boundary:

- GitHub Actions run `31235343006`, attempt 1, deployment
  `c99-prod-31235343006-1`, returned HTTP 500 after 282 seconds.
- The single approved retry, attempt 2, deployment
  `c99-prod-31235343006-2`, returned HTTP 500 after 275 seconds.

Both attempts used commit `e50fb3688524efdcabe9e99d3bd09ca5c4102637`
and the same immutable release identity:

- artifact: `complete99-platform-1.18.1.zip`
- bytes: `31339837`
- artifact SHA-256:
  `90c4ac2bf89616bee0084cce8973dee362408418381912c36f136479f8f29fec`
- installed-tree SHA-256:
  `31129adb05da14216499b6951bcdc070f9746aeef53c9146696ad5ac5708de00`
- packaged-source SHA-256:
  `122484c4b03b8da9ddf9216c09c16ed02b1996a242ad7f50c560a7a5f983fbd6`

The prior transport placed the full ZIP in one base64 JSON field. That turned
the 31,339,837-byte artifact into a request body of more than 41 MB before
WordPress parsed, decoded, copied and installed it. The production audit did
not contain the hosting PHP error log, so it cannot prove whether the final
trigger was a request-time ceiling, memory pressure or another managed-host
execution failure. The repeated duration and boundary prove that another blind
retry is not acceptable.

## Recovery truth

Both failed attempts crossed the guarded mutation edge and then completed the
normal fail-closed rollback. Attempt 2 proved the following restored state:

- active version and database version: `1.18.0`
- deployment: `c99-prod-31217684760-1`
- installed plugin tree SHA-256:
  `8216376a993505e18bf616362df1db6318d9382319d53d70e58390bcdb60becc`
- database fingerprint:
  `4976f887006e5432140fcfa9a0fb6c1dcfadc1ec8f94f7a7f181d316e33e04ff`
- robots SHA-256:
  `b25fdd90cfd62119544ff19ddecf01bd33f94e66e8645190f03c06cd32229b7e`
- rendered-home SHA-256:
  `bd07d8b5753ceb806e80ac6792d01e4b68129bf822401b8cbc4c9031bf4af59d`
- deployment state removed, lock released and process lock available
- WooCommerce materialization skipped

The public site therefore remained on the exact healthy 1.18.0 release. The
1.18.1 consumer shelf was not partially published.

## First staging attempt and exact correction

Production run `31237751794` reached the new staging route, but its first exact
1 MiB chunk was rejected with `c99_stage_chunk_encoding`. The chunk was valid
canonical base64. The failure was caused by applying one anchored PCRE pattern
to the complete encoded chunk, about 1.4 MB, which exhausted the PHP PCRE JIT
stack. The rejection happened before the chunk write and before `/run`, so it
did not install or partially publish the candidate.

The bounded correction removes only that whole-string regular expression.
Canonical validation now requires a nonempty value, the existing encoded-size
ceiling, a length divisible by four, strict `base64_decode`, a decoded-size
ceiling and exact equality with `base64_encode` of the decoded bytes. Chunk and
whole-artifact SHA-256 checks remain unchanged. A PHP runtime test sends an
exact 1 MiB chunk and separately rejects invalid alphabet, noncanonical pad bits
and invalid-length encodings without exposing the token or payload in evidence.

## New bounded transport

The deployment bridge now receives the immutable ZIP before `/run` through a
dedicated authenticated staging route. The release SHA-256, exact byte count,
version and installed-tree SHA-256 are embedded in the single-use bridge.

Each request carries at most 1 MiB of decoded artifact data and includes:

- the exact deployment ID and one-time bridge token;
- the embedded artifact SHA-256 and total byte count;
- the exact sequential offset;
- the chunk SHA-256 and base64 chunk bytes;
- a final-chunk boolean that must agree with the exact total.

The server accepts only the next exact offset. A lost response can replay only
the identical last chunk, and the server verifies that chunk against the bytes
already stored. Gaps, overlaps, changed replays, malformed base64, oversized
chunks, unsafe paths and incorrect digests fail closed.

The final chunk triggers a complete streaming SHA-256 and byte-count check. The
archive is also checked for one allowlisted plugin root, the expected main
plugin file, duplicate or escaping paths, symbolic links, special entries and
excessive extraction size. Only a completed staging receipt can enter `/run`.

`/run` no longer accepts `package_base64`. It receives a small `staged=true`
request, rechecks the staged artifact, claims the existing reserved deployment
lease, rechecks the exact file again and moves it to the WordPress installer.
The installed directory must equal the immutable installed-tree SHA-256 from
release metadata.

## Cleanup and recovery boundary

Staging is isolated under one deployment-specific directory outside the
rollback-state directory. The directory is protected against direct web
access, symbolic links, traversal and unexpected entries.

The staged artifact and metadata are removed on successful consumption.
Reserved-lock finalization, failed install rollback, normal finalization and
recreated-bridge recovery also remove only the exact deployment staging
directory. Cleanup refuses a symbolic link or any unknown residue rather than
deleting an unreviewed path.

## Required acceptance before another live attempt

1. PHP lint the raw bridge and every default, normal-release and recovery
   rendering.
2. Prove an exact 1 MiB chunk without a payload-wide regular expression, plus
   sequential chunks, exact replay, gap, overlap, changed replay, malformed and
   noncanonical base64, size, digest, unsafe path, ZIP expansion and
   incomplete-stage behavior.
3. Prove `/run` contains no full-package base64 field and cannot run before the
   final complete receipt.
4. Prove the artifact is unchanged before and after lease claim and that the
   installed tree equals release metadata.
5. Prove partial staging is removed by reserved finalization, rollback,
   finalization and stale recovery.
6. Run the full repository suite, reproducible package build, package validator,
   ZIP PHP lint, secret scan and diff check.
7. Merge through a reviewed PR and green CI. Do not deploy from an unmerged
   branch.
8. Perform one normal production deployment with the exact CI artifact. If it
   fails again, preserve the audit and stop rather than retrying blindly.
