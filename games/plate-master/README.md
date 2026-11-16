# Plate Master

Owner-supplied game for Complete99, with sabich, beet kubbeh and ijah stages.
Owner authorized the website page only on 16 November 2026. Tracking: HAD-762,
child of HAD-665. Social publication and campaign scheduling remain paused.

The supplied illustration and layout are retained. The existing palette is dark
counter blue with food red, green and yellow; the existing system Hebrew font
stack is retained. This is integration of supplied artwork, not a new visual brief.

Source received at:
`C:/Users/777/.gemini/antigravity/scratch/complete99-campaign/complete99-plate-master-game.html`.
The untouched original is preserved in the local HAD-762 task's source directory.

Website corrections: canonical Wolt restaurant link, no unsupported stock claim,
mobile overflow, keyboard buttons, pointer cancellation and multi-pointer guards,
level-transition guard, sound toggle, clipboard failure fallback and game share URL.

The single HTML document has no external runtime assets. It is embedded in a
WordPress Custom HTML block using an isolated iframe document. The publisher
creates a draft, independently verifies retained source bytes, then publishes and
reads the public `/game/` page. It refuses to overwrite unrelated content.
The existing site's plugins, measurement, permissions and automations are untouched.

Prepare a manual block:
`python scripts/publish-plate-master.py --build-content game-block.html`

The manual `Publish Complete99 game page` workflow reuses the existing production
environment. It requires main with successful exact-commit CI. Default mode is
read-only; `publish=true` is the single page publication. It adds no scheduled job,
new credentials, plugin install or hosting service. If the host denies the existing
REST account, the workflow fails without changing access or security settings.

Meta Playable validation and ad delivery are outside this website-only release.
