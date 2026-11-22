# Complete99 games

Owner task: HAD-807. Sources live in this repository. Deployment changes only the three allowlisted WordPress game pages, never the platform plugin or campaigns.

## Design and research, 22 November 2026

Design project: https://claude.ai/design/p/3c6e7283-5b8b-43ca-8c2e-37b91d08597a

The submitted brief uses stainless steel #DFE8EC, navy #17293D, beet #AA174C, turquoise #008D99 and white. Heebo and Archivo Black are the requested faces, with offline system fallbacks. Kitchen counters, order tickets and street signs supply the forms. This is deliberately different from cream-and-gold AI templates. Original art only, no GTA assets or claims of Wolt affiliation.

Sources actually read:

* [Get That Order!, developer page](https://erk.itch.io/grab-that-order): preparation, cooking and service create pressure together. The author's fullscreen-only limitation is a reason to test our games in a small embedded viewport, not just fullscreen.
* [Godot developer discussion on a cooking prototype](https://www.reddit.com/r/godot/comments/18ziusr/the_first_core_mechanic_of_my_cooking_game_is/): build complete playable slices. Each new shift should reuse earlier skills and introduce a decision, instead of disconnected feature demonstrations.
* [Phaser's mobile guide](https://phaser.io/tutorials/bring-your-phaser-game-to-ios-and-android-with-capacitor): touch controls, viewport resizing and safe areas. We use these browser principles without adding a native-app wrapper.
* [Google's noindex documentation](https://developers.google.com/search/docs/crawling-indexing/block-indexing): the parent page must emit noindex and remain crawlable. An iframe's own tag is insufficient. Removal from search is subject to recrawling.
* [DOOM DELIVERY, developer's post-jam analysis](https://ryanpond.itch.io/doom-delivery/devlog/1680150/doom-delivery-after-the-jam-what-comes-next): players learned momentum and better routes, but several did not understand the objective. Apply clear pickup, parking, locking and delivery prompts, visible destination guidance, and a real cost to sharp turns. This was a prototype with reported weaknesses, not an award-winning benchmark.
* [Delivered, developer's progression notes](https://evengy.itch.io/delivered/devlog): the author removed a route-planning screen because it interrupted a chaotic delivery loop. Keep our minimap and target visible during play rather than requiring a planning menu before each delivery.

These are research inputs, not evidence of having played or reproduced award-winning games. Acceptance is based on observed behavior in our own builds.

## Intended game loops

* Kitchen: read the group ticket, calculate portions and pot capacity, manage burners, serve. Six increasingly difficult shifts, with replay and feedback on errors. Game quantities are scenarios, not restaurant recipes or food-safety advice.
* Courier: collect, secure the pot, accelerate and steer, preserve the food, park, lock, deliver. Five original arcade missions. Speed trades off against spill risk; bike recovery and inspectors add narrative consequences. The stylized map is not navigation guidance.

## Publication and verification

Fixed pages: `/game/`, `/kitchen-game/`, `/delivery-game/`. The existing game source must remain unchanged when applying noindex. The platform's existing REST-exposed managed-page metadata provides the parent robots gate. Public readback must confirm the iframe source and a robots noindex tag inside the parent head.

Sources are standalone HTML with no runtime CDN dependency. Sharing a URL in a social browser is distinct from acceptance as a native playable advertisement. No such ad acceptance is claimed.

Published and independently verified on 23 November 2026 (Asia/Jerusalem):

* [Kitchen: משמרת על האש](https://complete99.co.il/kitchen-game/), WordPress page 975.
* [Courier: משלוח 99](https://complete99.co.il/delivery-game/), WordPress page 977.
* [Existing Plate Master](https://complete99.co.il/game/), WordPress page 970. Its game source is unchanged.

All three parent pages returned HTTP 200, a robots `noindex` in the parent head, the expected iframe source hash and the canonical Wolt URL. The homepage indexing state was preserved. This verifies the indexing instruction, not removal from Google's existing results.

The [guarded publication run](https://github.com/The-new-ben/complete99-wordpress/actions/runs/45077223017) succeeded at main commit `371dd188fc663a21cb77957275f0d2c57589f39c`, following [PR 104](https://github.com/The-new-ben/complete99-wordpress/pull/104) and passing required CI. Final PR CI reported 1266 passed, 1 skipped and 1129 subtests. An initial read-only dry run timed out on the identity check before any mutation; the same failed job was rerun and passed before publishing.

Native Chrome public-page QA observed kitchen shift selection and briefing, courier mission selection, and the existing Plate Master starting. Development viewport and gameplay coverage, with its limits, is described below. Local evidence is in `C:/Users/777/Downloads/codexmanager/tasks/complete99-games-HAD-807/`: `publication-receipt/receipt.json`, `public-readback.json` and `qa-receipt.json`. The handoff is recorded in [HAD-807](https://linear.app/hadmaia/issue/HAD-807) and the existing Notion HQ line.

## Verified development QA

The actual kitchen HTML was executed in jsdom through all six shifts (4, 10, 14, 16, 20 and 24 diners), each with a complete three-star result. Tests also cover wrong scaling answers, pause, burning, clearing a pot, failure and replay. The quantity quiz was added after the Claude export; 0.75 is displayed exactly, not rounded to 0.8.

The actual courier HTML, with a no-op canvas only in the test fixture, completed all five mission state flows including the two-stop finale. Movement, brakes, tap pickup, slosh loss, theft/recovery, lock prevention, failure, pause and stale-timer replay are covered. Fixture repositioning validates transitions, not driving every route. Native Chrome testing separately observed city rendering, traffic, acceleration, pickup, securing, parking, locking and walking. A complete courier route has not yet been manually driven.

Native Chrome kitchen testing observed a completed first shift (two of four diners served, one star) and the second shift unlocking. Widths 320 and 390 CSS pixels were inspected in an iframe, including quantity selection. The narrow score layout and fractional display were corrected. This is browser viewport QA, not a claim of testing a physical phone or Facebook/TikTok's in-app browsers.

Untouched Claude exports are in `design-source/`; shipping sources are in `kitchen/` and `courier/`. Production changes add embedded OFL-licensed Heebo, input refinements, quantity decisions, failure handling and replay safeguards. Font license and original source are in `fonts/`. The font subset is generated by `scripts/embed-game-font.py`. No GTA, Wolt or third-party game art is copied.
