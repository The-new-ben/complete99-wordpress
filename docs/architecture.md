# Architecture

## Product boundary

Complete99 is intentionally two connected products rather than a monolithic
WordPress installation.

1. **Public WordPress site** — searchable pages, service and sector explanation,
   public dish research, proposal capture and brand presentation.
2. **Private Complete99 OS on Sites** — role-specific Today views, opening
   workflows, BOM and cost, inventory, files, campaigns, audit and future device
   adapters.
3. **Public read-model bridge** — the OS sends only explicitly allowed public
   fields to WordPress. The payload is signed; WordPress rejects stale timestamps,
   reused nonces, invalid signatures, oversized data and unknown fields.

Raw camera streams, telemetry, employee data, supplier costs, private production
specifications, tokens and social credentials do not belong in WordPress.

## WordPress content model

| Content type | Purpose |
| --- | --- |
| `c99_service` | What is delivered and how |
| `c99_industry` | Who it is for and the sector constraints |
| `c99_platform_feature` | Truthful application capability and demo state |
| `c99_dish` | Public dish research and tested recipe when approved |
| `c99_ingredient` | Ingredient source and use records |
| `c99_location` | A real verified branch only |
| `c99_guide` | Research-backed knowledge |
| `c99_case_study` | Permissioned evidence and measured result |
| `c99_team_member` | Permissioned expert profile |

Taxonomies model service families, sectors, operating domains, dish courses, food
traditions, dietary notes and regions. Thin taxonomy archives are not part of the
launch surface.

## Identity and language

Hebrew is the default root. English pages live under `/en/`. Custom content has
parallel deterministic paths such as:

- `/services/institutional-catering/`
- `/en/services/institutional-catering/`

Every pair carries a translation key, self-canonical, reciprocal `hreflang` and an
`x-default` pointing to Hebrew. No automatic geo/language redirect is used.

## Idempotent seeding

Each seed stores a key, seed version and SHA-256 of title, excerpt and content.
On upgrade:

- missing records are created;
- an untouched prior seed may receive a reviewed seed update;
- an editor-modified record is preserved;
- deletion is never automatic.

The six dish records start as drafts with `verification_required`. They are
deliberately excluded from public Recipe schema until review is complete.

## Deployment trust boundaries

Reviewed source → deterministic ZIP and digest → GitHub production environment →
WordPress Application Password → temporary Code Snippets route → preflight →
digest-verified upload → isolated backup → overwrite install → independent health
check → finalize → snippet deletion → route-404 proof.

The plugin also vendors Plugin Update Checker 5.6 and reads the versioned package
URL from `plugin-dist/complete99-platform.json`. That is a wp-admin fallback; it
does not replace the reviewed, digest-verified deployment path above. Artifact
integrity metadata is isolated in `complete99-platform-integrity.json`.

The package is uploaded directly by the authenticated workflow. No mutable public
raw-branch ZIP is used.
