# Independent editorial presentation release

This is a publication lane, not a replacement platform or completion of the full
food portal objective. The core Campaign upgrade remains pending separately.

The package is generated from the reviewed homepage, CSS, search script and
consumer renderer, with separately sourced short nutrition modules. No CMS writes.
Home and ingredient/knowledge shell overrides apply to platform versions 1.22.1
through 1.23.x and are disabled on 1.24.0 or newer. Shared CSS and the local-search
bootstrap have separate enqueue hooks and do not become inert with those shells.
The menu compatibility script is tested and enabled only for the actual live
1.22.1 core. PR99's P2 finding requires a tested 1.23.x compatibility extension
before a core upgrade. Ingredient notes reuse stable card identities and retain
original content, images and links; they do not calculate product nutrient values.

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

The installer records HTTP installation verification separately from release
acceptance. It checks preserved canonical/language tags, internal destinations,
image loading and exact public asset hashes. Browser search/facet behavior and
desktop/mobile screenshots remain explicitly pending until separately tested;
the installer never reports those as complete.
