# Public group-enquiry transport1.2.4

## Observed fault

Native live form action is `/wp-admin/admin-post.php`. Unauthenticated negative
POSTs with an invalid nonce and nonempty honeypot returned nginx404, both
multipart and URL-encoded. They contained no customer information and created
no lead. A matching POST to `/request-proposal/` returned200 and the form, without
reaching its handler. No host security setting was changed.

## Implementation

- Render the existing group's form action as its own exact canonical HE/EN
  public proposal URL, including a marker for the client enhancement.
- Only a published, singular proposal page on the exact existing canonical path
  qualifies. Drafts, unrelated forms, languages, paths and GETs are untouched.
- A `template_redirect` POST dispatcher calls the original public native lead
  handler. Nonce, consent, rate limits, sanitization and private storage remain
  owned by that handler, not copied into the companion.
- Server-rendered form works without JavaScript. The existing client recovery
  also accepts the explicitly marked same-origin canonical action.
- Core>=1.24 retains this transport independently of the older editorial guards.
- No platform migration, public-content rewrite, privacy-setting change or new
  endpoint with administrative capability. Existing SEO and links are retained.

## Tests and release boundary

CLI contracts run the actual handler from both current source and archived
live1.22.1, with memory-only WordPress persistence. HE/EN success, invalid nonce,
missing consent and dispatch exclusions are covered. The native old form's
organisation/message requirements are not removed by this transport change.
JavaScript executes success/error recovery against both allowed transports and
rejects unmarked, external, query-bearing or mismatched public actions.

Real Chrome local canonical-route form: Hebrew error preserved entered values,
followed by controlled success. These responses are simulated; no customer or
operator email was sent. Live storage and operator delivery are not proven by
local tests or by a negative live probe. After deployment, a negative POST must
reach native403, not nginx404 or untreated200. Record that distinction in the
handoff. Native storage-failure cleanup and actual notification remain open.

Candidate until protected-main deployment and rendered verification. Predecessor
is exact1.2.3:e397da7b04a43bdf5bea174b39462a8d810e2335b91489fec3fe46d44eb8bbba.
