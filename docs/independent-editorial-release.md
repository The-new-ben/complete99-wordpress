# Independent homepage presentation release

This is a publication lane, not a replacement platform or completion of the full
food portal objective. The core Campaign upgrade remains pending separately.

The package is generated from the reviewed homepage, CSS, search script and
consumer renderer. No manually forked marketing copy or new CMS content.
It changes only the existing Hebrew/English home rendering on platform versions
1.22.1 through 1.23.x. On 1.24.0 or newer it becomes inert because the same
homepage is already native. All other pages retain their existing renderer.

The deployed platform directory, content, URLs, metadata hooks, database version,
Campaign workers, original encrypted journals and old deployment lock are not
modified. WordPress activation adds only the new presentation plugin membership.
That membership must be accounted for in any subsequent core recovery review;
it must never be hidden from the old recovery diagnostics.

Release requires protected-main CI, deterministic package and exact file hashes,
a separately reviewed admin-gated installer, no concurrent core deployment,
current-state backup, cache purge and independent public verification. The new
plugin is reversible by deactivation. No old snapshot restoration is involved.

Public verification must show the new body marker and absence of the old hero,
both languages, real dish links/images, combined search/facets, group enquiry,
canonical/hreflang continuity and mobile/desktop screenshots. Preparing this
package alone is not a live release.
