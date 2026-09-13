# Shared internal-page presentation, version 1.1.0

Status: built and locally inspected, NOT deployed. Public home1.0.0 remains live.
This is an incremental shared visual layer, not completion of the portal goal.

`editorial-release/plugin/assets/site-editorial.css` belongs to the companion
presentation plugin, not the core platform. It scopes every rule to the existing
public consumer body. It covers header/navigation, menu cards, dish and hub hero
layouts, editorial reading, ingredients and group-enquiry forms. No content,
URLs, canonical tags, index rules, form validation, visibility or order states
change. Existing photographs and current copy remain attached to their pages.

The extra stylesheet remains available when core eventually gains the native
homepage. The existing homepage replacement retains its original version gate.
No manual copy of the consumer renderer was introduced.

Chrome tested local captures of the current public WordPress pages at `/dishes/`,
`/menu/beet-kubbeh/`, `/ingredients/` and `/request-proposal/`. Each390px iframe had
375px available content width with scrollbars and equal document scroll width.
These prove narrow-layout behavior, not physical-device touch or live deployment.
Dish desktop/mobile screenshots emitted in chat. Browser viewport override itself
did not change the real window, so an explicit local iframe was used instead.

`tests/fixtures/site-editorial-preview.php` serves only local GET routes and
captured public HTML. It removes scripts/analytics, reloads only menu/filter JS,
and blocks form submission using form-action none. Private capture directory:
`C:/Users/777/Downloads/codexmanager/complete99-editorial-internal-preview`.
Do not ship fixture or private captures to WordPress.

Five focused package/gate/public-verification/style tests pass. Package source
PHP and generated PHP pass lint. The1.1.0 deterministic artifact has9files.

Next required implementation: extend the existing presentation installer from
first-install-only to an exact1.0.0-to1.1.0 update with a recorded prior package,
scoped backup, file-digest checks, rollback to1.0.0 and same-artifact redeploy.
The old first-install script now explicitly refuses1.1.0, preventing accidental
overwrite before that update path is ready. Do not add another companion plugin
or bypass the core recovery journal to publish the stylesheet.

Functional gaps remain separate: core1.24.1 already contains optional company
and message fields for family/group enquiries, but is not live. Preserve that
work instead of duplicating it. Broader discovery, location/business data,
editorial expansion, SEO and all-page acceptance remain active requirements.
