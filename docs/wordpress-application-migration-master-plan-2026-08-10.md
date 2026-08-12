# Complete99 application migration into WordPress

Date: 2026-08-10

Authority: `owner-authorization-wordpress-migration-2026-08-10.md`

## Required outcome

Complete99 will operate as one WordPress-owned system. Staff may need a
WordPress account appropriate to their role, but no staff member will need a
ChatGPT account, a Codex task, a ChatGPT Sites session or an open agent
conversation to run the business.

WordPress will own the authenticated application shell, role and location
access, operational storage, commands, audit history, editorial workflows,
public discovery, culinary knowledge, WooCommerce relationships, release
health and rollback evidence. Provider-specific services may remain connected
services, but they will not be the application authority.

## Where the system is now

The live WordPress installation is release 1.20.0, database 1.20.0,
deployment `c99-prod-31429662234-1`. It already owns:

- the bilingual public food site and plugin-rendered public theme;
- 12 public dish journeys and the 36-product WooCommerce catalog;
- catalog/cart behavior, with checkout and payments still held;
- culinary-science data and museum rendering;
- SEO route, canonical, hreflang, schema and sitemap logic;
- lead capture, launch controls and deterministic deployment;
- administrator-only Review Lab and Entity Studio surfaces.

The 1.20 science, cross-domain, approval and media-quarantine work is now live
from the exact protected-main artifact. Twelve Syrian and Japanese editorial
candidates remain held, cross-domain decisions remain unresolved, and
checkout and payments remain disabled. Those gates are deliberate truth
boundaries, not evidence that the application migration is complete.

The 1.21 source candidate now implements the first bounded migration slice. It
adds a WordPress-authenticated, read-only Complete99 OS Today shell, a dedicated
operations capability, a nonce-protected private status API and seven durable
tables for locations, memberships, tasks, issues, commands, mutation receipts
and append-only audit events. The deployment bridge now snapshots and rolls
back those exact tables with first-install, retry, historical-journal and
zero-residue proofs. This is implemented source and package work, not a claim
that 1.21 is already live or that legacy external records have been imported.

The complete operational application is still primarily outside WordPress in
`complete99-os-v7` and `complete99-os`. Its React application shell, command
modules, responsive theme, service worker, offline queues and much of its
operational data model have not been ported. It still contains ChatGPT-header
authentication, Cloudflare D1/R2 state and IndexedDB/browser queues. The
deployed WordPress settings still retain exact legacy defaults pointing
to a public ChatGPT Sites host. The 1.21 source candidate replaces only those
known defaults with WordPress-owned destinations while preserving any custom
owner-configured HTTPS value.

## Why the complete migration was not previously completed

This was not blocked by owner permission. The implementation deliberately
selected a split architecture: WordPress was treated as the public and
commercial authority, while kiosk, POS, mobile, kitchen, operations and
campaign applications remained connected clients. Work then concentrated on
the public site, WooCommerce, culinary science, SEO, catalog materialization,
deployment safety and editorial boundaries.

The external OS received a signed WordPress read-model heartbeat instead of a
WordPress-native replacement. No complete importer was built for D1, R2,
IndexedDB, memberships, revisions, receipts, uploads and audit events. The
external app still authenticates through ChatGPT-specific headers. The current
WordPress work also remained uncommitted and was stopped on the owner's prior
instruction, so production correctly stayed on 1.18.2 until the reviewed 1.20
infrastructure release was committed, merged, rollback-tested and deployed on
2026-08-10.

That history explains the gap; it does not justify keeping it. The migration
now proceeds under the recorded owner charter.

## Migration contract

Every module moves through the same contract:

1. identify the external source, source schema and immutable export receipt;
2. define the WordPress table, WordPress identity and capability boundary;
3. implement a dry-run importer with counts, digests and collision reporting;
4. import idempotently and record the source and result receipts;
5. expose the module only through authenticated, capability-checked WordPress
   UI and REST routes;
6. prove stale-revision, duplicate-command, location-isolation, logout and
   rollback behavior;
7. retire the external authority only after parity and live acceptance.

Public publication is a separate decision. Moving a record into private
WordPress storage does not approve its facts, images, price, stock, safety,
rights or Google indexability.

## Phased migration

### P1: WordPress-native operating foundation

- Complete99 OS top-level private WordPress application shell.
- WordPress users, sessions, nonces and owner/manager/worker/marketing
  capabilities.
- Locations, memberships, tasks, issues, commands, mutation receipts and
  append-only audit-event storage.
- Versioned import preview, input digests, idempotent import receipts and
  rollback proof.
- Role-filtered Today dashboard and private health/status API.
- Exact-value migration away from the ChatGPT Sites app and asset defaults.

### P2: Daily command workflow and offline behavior

- Complete-task and report-issue commands with revision conflict handling.
- Plugin-owned responsive application theme, manifest, service worker and
  offline command queue.
- Reconnect retry, duplicate suppression, session-expiry and logout data wipe.

### P3: Kitchen, inventory, staff and protected files

- Recipe/version/step and kitchen workflows.
- Inventory movements and waste linked to exact WooCommerce identities.
- Shifts, assignments and time events.
- Documents, acknowledgements, protected downloads and R2 byte/hash imports.
- Import the 43 reviewed OS originals; keep private Wolt references outside
  public media.

### P4: Campaign, media, SEO and connectors

- Campaign Studio and attachment rights/provenance decisions.
- SEO Studio tied to canonical query owners and Search Console receipts.
- Connector jobs, retries and provider receipts.

### P5: Science, binding and commerce editing

- Science/source/editorial tables and round-trip registry parity.
- Binding and owner-publication decisions inside WordPress.
- WooCommerce remains the product, price, stock and checkout authority; no
  duplicate catalog is created.

### P6: Finance, projects and provider-neutral assistance

- Finance packets, projects and protected exports.
- A provider-neutral private assistant that creates sourced drafts only. It
  may use an approved AI provider behind WordPress, but staff never authenticate
  through ChatGPT and no generated draft bypasses human publication controls.

### P7: Cutover and retirement

- Port any remaining unique React/public-preview sections into plugin-owned
  WordPress templates and assets.
- Redirect or archive the competing public preview host.
- Retire the external heartbeat and external OS authority after parity.
- Complete protected-main release, rollback rehearsal, real-browser HE/EN
  acceptance and Google indexing submission.

## First implementation slice implemented in the 1.21 source candidate

The first slice is the P1 foundation: a WordPress-native Complete99 OS menu and
dashboard, WordPress capability boundary, operational schema foundation,
private status API and exact migration away from the hardcoded ChatGPT Sites
defaults. That slice is read-only: it intentionally does not claim that every
operational module or historic record has already moved. It was independently
proven for 1.21 and remains an authenticated part
of the 1.22 candidate; neither source candidate is called live without its own
exact deployment and production evidence.

## Completion definition

The migration is complete only when:

- no public or private runtime link requires a ChatGPT Sites host;
- no staff workflow authenticates through ChatGPT;
- every external state source has an exact import, parity or explicit retired
  disposition;
- every recurring business workflow can be completed from WordPress on mobile
  and desktop under the correct role;
- every mutation has an accountable WordPress actor and append-only receipt;
- private/editorial data remain inaccessible anonymously;
- approved public pages are crawlable, canonical, internally linked and in the
  WordPress sitemap;
- WooCommerce and payment readiness remain truthful and independently tested;
- the exact protected-main artifact is deployed and rollback-tested.
