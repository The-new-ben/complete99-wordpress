# Legacy public site and Campaign Studio migration

Date: 2026-08-11

## Exact legacy surfaces

- Public legacy site: `https://complete99-public.benben777.chatgpt.site/`
- Private legacy OS: `https://complete99-os.benben777.chatgpt.site/`
- Canonical WordPress site: `https://complete99.co.il/`

The public legacy site is a separate ChatGPT Sites marketing and menu build. A
fresh anonymous audit on 2026-08-11 found that its Hebrew and English surfaces
return HTTP 200, declare `index, follow`, use self-canonicals and advertise their
own sitemap. The sitemap exposes 36 localized URLs. It is therefore a live,
crawlable competing surface, not a retired preview and not part of WordPress.

The private legacy OS returns HTTP 401 to anonymous visitors and requires a
ChatGPT identity. It is the source of prototype Campaign Studio behavior, but it
is not an acceptable long-term runtime because Complete99 staff must operate in
WordPress without a ChatGPT login.

## What the public legacy site contains

The public site contains the bilingual roots, menu and twelve dish pages plus
institutions, platform, tour and proposal narratives. It does not contain the
private Campaign Studio interface and is not a campaign publishing provider.

WordPress 1.22 already has first-party public roots, dishes, Museum pages and a
real persisted group-meal request flow. The legacy institutions, platform and
tour narratives do not yet have truthful WordPress equivalents. The legacy
proposal categories for operations consulting and other partnerships also are
not present in the narrower WordPress group-meal form.

## Campaign source truth

The strongest legacy Campaign Studio code is an uncommitted prototype in
`complete99-os-v7`. Its checked-in seed contains two demo campaigns, nine
playbook templates, zero approved campaigns, zero scheduled jobs and zero
provider-verified receipts. External Meta, TikTok, WhatsApp and Google Business
publishing is not implemented. Those channels create manual handoff packages.
Wolt is the only verified outbound destination.

No D1, R2 or IndexedDB campaign export is present in the workspace. Demo seeds
may be migrated only as private templates or clearly marked legacy demo drafts.
They must never be relabeled as real approvals, schedules, publications or
results.

The 1.22 WordPress candidate now implements the governed Campaign boundary
below: WordPress authentication and location scope, durable versioned records,
immutable approvals and prepared packages, owned-site scheduling and readback,
receipts, observed results, privacy-safe aggregate events and moderation. It
does not import the prototype's demo campaigns, playbooks or historic state.
Meta, TikTok, WhatsApp and Google Business remain manual handoff packages; no
provider is called published without an exact external identifier, evidence
receipt and independent verification.

## WordPress Campaign Studio acceptance boundary

Campaign operations are considered migrated only when WordPress provides:

1. WordPress users, capabilities, nonces and location-scoped authorization.
2. Durable versioned campaign, revision, package, receipt, result, placement and
   privacy-safe aggregate event records.
3. Objective, audience, claim sources, approved asset, landing intent owner,
   channel account, budget owner, approver, UTM values, schedule and timezone,
   consent basis, metric source, moderation owner and escalation owner.
4. Immutable approval and prepared-package digests that become stale after any
   material edit.
5. A real WordPress-owned campaign path from draft through approval, scheduled
   placement, public readback, system-verified receipt, aggregate UTM events and
   expiry.
6. Manual external channel packages that never claim publication.
7. Provider-verified status only after a real provider account, scoped adapter,
   external identifier, evidence receipt and independent readback exist.
8. Versioned deployment, exact database rollback, audit logs and private proof
   access.

## Retirement prerequisites

The public legacy host may be retired only after:

1. Its source, media and route inventory are frozen with checksums.
2. Every retained page is mapped to a real WordPress equivalent; intentionally
   retired pages receive a deliberate response instead of a silent loss.
3. Permanent host-level redirects are installed for roots, menu, dishes and
   proposal routes.
4. The legacy sitemap and self-canonicals are removed.
5. Every old route and asset is rechecked anonymously after the cutover.
6. The WordPress sitemap and canonical selection are monitored in Google Search
   Console.

Changing only `robots.txt` is not a migration or retirement. Until those steps
are proven, the legacy public host remains a known duplicate-risk surface.
