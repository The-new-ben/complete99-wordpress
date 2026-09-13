# Group enquiry journey: observed state and executable tests

## Scope

Existing conversion owner: `/request-proposal/` and its English counterpart.
No new page, keyword owner, redirect, privacy setting or production code changed.
This is private release evidence, not public copy.

## Production observation

Chrome opened the live Hebrew page on 2026-09-13. The actual organisation
and additional-message fields still have `required` attributes. The repository
already makes these two fields optional for group orders; that change is not live.
The page still uses the existing group meal fields and links to the dish library,
telephone and Wolt. No form was submitted and no real lead was created.

Screenshot on the owner computer:
`C:/Users/777/Downloads/codexmanager/complete99-group-order-baseline-20260913.png`.

## Test improvement

The old executable form fixture stopped at `wp_insert_post`, proving validation
only. It now has a CLI-only `persist` mode that executes the real handler with
in-memory WordPress adapters. No network, real database, email or production
credentials are used. The browser preview remains GET-only and cannot persist.

The focused suite passes 39 tests, including 13 new storage scenarios:

- Hebrew and English successful private records, exact 16 metadata fields and fresh readback;
- quotes, backslashes and Hebrew text survive the storage boundary;
- post creation failure, early/late metadata failure and corruption on fresh readback;
- partial-record cleanup, including a failed post deletion with successful metadata cleanup;
- a false update return with identical stored metadata is accepted;
- source query and fragment removal, external-origin rejection;
- rate limit and honeypot never create a successful record.

Command: `python -m pytest -q tests/test_group_request_friction.py tests/test_group_lead_storage.py tests/test_lead_operator_workflow.py`.
Result: **39 passed**, plus PHP syntax passed for the fixture.

## Important boundaries and remaining work

- These memory-adapter tests do not prove production database durability, actual
  operator visibility or email delivery. WordPress sanitization is approximated
  by the fixture, not an integration test of WordPress itself.
- PR101 review 4000673595 identified an important omitted fault: if both post
  deletion and a metadata deletion fail, the native handler leaves that metadata
  in a private failed record. Added explicit fault injection and a test documenting
  the actual residual email, not a false cleanup guarantee. Native cleanup repair
  is still open; the frontend recovery release does not claim to resolve it.
- The inspected repository handler saves and redirects; it does not send mail.
  A separate installed notification integration has not been ruled out. Do not
  describe a received email or a notified operator as verified.
- Repository errors still use standalone `wp_die` responses. Inline recovery and
  retained customer input need an implementation and a real browser test.
- Success presentation currently depends on `c99_sent=1`; it is not a unique
  server-bound receipt. Do not treat page views of this state as verified leads.
- Native core deployment recovery remains separate. Do not clear its journal or
  overwrite later CMS edits to publish the optional-field change.
- Before core 1.24 promotion, port the currently live editorial nutrition modules
  into the native path or preserve their version compatibility. Do not lose
  the source-linked chickpea, tahini and olive-oil information during recovery.

Next implementation batch: optional-field release, error recovery, dish context
retention, and a verified existing operator destination. Keep scientific nutrition
expansion batched and source-bound, rather than individual micro-releases.
