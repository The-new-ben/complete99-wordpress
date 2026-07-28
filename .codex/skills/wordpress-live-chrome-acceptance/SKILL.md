---
name: wordpress-live-chrome-acceptance
description: Use after any WordPress release that changes the public UI, navigation, responsive behavior, language direction, SEO chrome, images, forms, or accessibility. Proves the exact live release in the user's real Chrome with desktop/mobile and bilingual screenshots, geometry and target-size checks, keyboard interaction, lazy-image loading, schema/canonical verification, route crawling, browser-log review, and honest live/ready/gated reporting.
---

# WordPress Live Chrome Acceptance

This skill is the visual and public-surface completion gate after
`wordpress-agent-deploy`. A green CI job and a successful upgrader response are
necessary, but they do not prove that the visitor received the intended page.

## Completion law

A release is complete only when all of these are true:

1. The deploy audit proves the immutable artifact, exact source commit,
   database version, runtime version and deployment identity.
2. The deploy transaction is finalized: lock and state removed, temporary ZIP
   removed, snippet row absent and privileged route independently returns 404.
3. A fresh public GET proves the new body markers are present and the previous
   deployment marker is absent.
4. The user's real Chrome renders the exact new deployment at the required
   viewports and languages.
5. Every material visual finding is either fixed in a new reviewed release or
   reported as an explicit remaining gap. Never bless a known regression.

Do not substitute a headless browser when the user asked for their real Chrome.
Do not create a staging site when the production-only constraint is explicit.

## Required evidence

Create one bounded evidence directory per final release:

```text
.codex-tmp/<site>-live-<version>/
  <site>-<version>-desktop-1440.jpg
  <site>-<version>-mega-menu.jpg
  <site>-<version>-mobile-390.jpg
  <site>-<version>-mobile-menu-open.jpg
  <site>-<version>-english-1440.jpg
```

Keep the non-secret deploy audit separately. Record:

- commit SHA;
- artifact SHA-256 and source SHA-256;
- plugin, database and deployment versions;
- stabilization/finalization results;
- cleanup and route-404 proof;
- viewport dimensions and public body markers.

Never store credentials, application passwords, authorization headers, nonces,
cookies or private customer data in screenshots or audit files.

## Acceptance matrix

Run at least:

| Surface | Viewport | Required checks |
|---|---:|---|
| Primary language home | 1440×1000 | exact version/deployment, header, hero, no overflow, targets, schema |
| Alternate language home | 1440×1000 | `lang`, `dir`, copy, canonical/hreflang, long-label header geometry |
| Desktop mega menu | 1440×1000 | open state, visible links, no clipping, Escape, focus return |
| Primary language home | 390×844 | no overflow, target sizes, hierarchy, readable hero |
| Mobile menu | 390×844 | open/closed state, body scroll lock, Escape, focus return |
| Major hubs | desktop | H1, canonical, breadcrumbs, index policy and truthful gating |

For a desktop navigation breakpoint, also inspect the narrowest desktop width,
one common laptop width, and the full acceptance width. Long English labels
often reveal collisions that do not exist in Hebrew.

## Exact public identity

Before visual judgment, assert from the rendered body:

- expected `data-*-version`;
- expected `data-*-deployment`;
- old deployment marker absent;
- one H1;
- expected `html[lang]` and `html[dir]`;
- canonical URL without QA query strings;
- reciprocal language alternates, including `x-default` where intended;
- parseable JSON-LD with the expected graph types.

The health endpoint alone is insufficient. Cache-bust the public GET and inspect
the body, not the complete HTML: old selector names can remain in head assets.

## Geometry and interaction checks

### Horizontal overflow

For every acceptance viewport:

```js
document.documentElement.scrollWidth > innerWidth
```

must be false. Test with menus closed and open.

### Effective target size

Audit visible links, buttons, inputs, selects, textareas and explicit
`role="button"` controls. Use 44×44 CSS pixels as the product target.

For a checkbox or radio inside a clickable `<label>`, measure the enclosing
label as the effective target. A 20×44 checkbox inside a 405×49 label passes;
blindly measuring only the glyph creates a false finding.

When a target fails:

1. preview the CSS change in Chrome;
2. confirm it does not introduce overflow or visual imbalance;
3. commit the change with an exact regression test;
4. bump the plugin version and rebuild the deterministic package;
5. deploy through the full pipeline;
6. repeat acceptance against the new deployment.

Never leave a devtools-only style as the claimed fix.

### Header collision

Measure bounding rectangles for the brand, navigation groups, CTA group and
language switch. Adjacent rectangles must retain a positive gap and must not
overlap. Repeat for both directions and long-label languages.

Use geometry plus screenshots. A screenshot can look tight without overlap;
geometry can pass while the composition still looks crowded.

### Menus and keyboard

For desktop mega menus and mobile navigation:

- control starts with `aria-expanded="false"`;
- click changes it to `true`;
- associated panel is visible;
- internal links are present and within the viewport;
- Escape closes it;
- focus returns to the disclosure control;
- mobile open state locks background scrolling;
- closing restores the page state.

## Images and browser runtime

Scroll through the page in bounded steps so lazy images load, then assert:

- every image completes with non-zero natural dimensions;
- no broken images;
- intended production assets are first-party;
- responsive AVIF/WebP sources actually load where supported;
- captions and rights-sensitive labels are truthful;
- browser console and page logs contain no first-party warnings or errors.

Take native viewport screenshots and verify their pixel dimensions. Some browser
wrappers return thumbnails or time out for `fullPage`; do not treat a tiny
placeholder as evidence.

## Navigation and content truth

Crawl every unique first-party link surfaced by the header, mega menu, body and
footer. All intended public routes must return 200 without unexpected redirects.

Verify:

- visible breadcrumbs and structured breadcrumbs agree;
- store or commerce previews stay `noindex` until real merchant, tax, payment,
  shipping, inventory, returns and support facts exist;
- draft dishes remain non-public until research, kitchen, allergen, media and
  editorial gates pass;
- capability previews do not imply live cameras, suppliers, campaigns, branches
  or integrations that have not been connected;
- forms use required fields, consent, anti-spam controls and safe language;
- an empty-form click focuses the first required field without sending data.

Do not submit fake leads, create social accounts, or invent contact details as
part of acceptance.

## Benchmark review

Use a contact sheet or side-by-side screenshots of relevant premium competitors
to judge:

- hierarchy and information density;
- typography and whitespace;
- food imagery and appetite appeal;
- institutional clarity;
- content-to-commerce transitions;
- navigation depth;
- modal and sticky-header collisions;
- mobile priority.

Borrow patterns, never assets or copy. The final judgment must be against the
live page, not a local mockup.

## Final browser handoff

After all Chrome work:

1. restore a normal desktop viewport;
2. navigate to the best live deliverable page with the final cache-buster;
3. leave only the intended deliverable tab;
4. finalize the Chrome session;
5. do not issue another browser command after finalization.

## Handoff language

Report three states separately:

- **Live:** independently proven on the public site.
- **Ready:** built and governed but intentionally gated.
- **Needs owner/external action:** domain/DNS, verified business facts, payment
  account, social OAuth, legal approval or other authority the agent cannot
  truthfully invent.

Include the live URL, exact release/deployment, deploy-run link, evidence paths,
test counts and screenshots. Never call gated commerce, campaigns, suppliers or
integrations live.

