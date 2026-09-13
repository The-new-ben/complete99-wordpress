# Editorial 1.2.3: group enquiry recovery

Deployed from23d07c2c31f6be20d481d21275c23b2d45154321 in run34779861825.
Exact artifact:e397da7b04a43bdf5bea174b39462a8d810e2335b91489fec3fe46d44eb8bbba.
Installed files, predecessor restore/redeploy and cleanup verified by deployment
audit. Subsequent negative live POST probes found the native admin-post path
returns nginx404. This is not a working live submission release. Public canonical
POST receives200 but is untreated. Transport correction follows in1.2.4.

## Changed

- A separate script on the existing Hebrew/English proposal page enhances only
  the existing `complete99_submit_lead` group-order form and same-origin endpoint.
- Native required fields, nonce, consent, rate limits, backend storage and private
  access remain unchanged. Organisation/message remain required on live core1.22.1.
- Failed requests retain entered values in the current DOM and focus a readable
  bilingual message. No personal data is copied to web storage or analytics.
- Pending submission disables the button, prevents a second request and times out
  after45seconds. A timeout/network failure is ambiguous and never replayed.
- Success requires an actual successful fetch with a redirect to the current path
  or root on the same origin and the existing native `c99_sent=1` marker.
  Direct page visits are not treated as conversion evidence by this enhancement.
- Unsupported browsers and unrelated forms retain native submission.
- Existing menu compatibility, nutrition notes, imagery and SEO checks stay intact.

## Evidence before deployment

49 focused Python tests pass, including the shipped JavaScript runtime contracts.
Real Chrome against the isolated local router showed Hebrew and English errors
without leaving the form. Hebrew success showed only after a simulated accepted
POST and redirect. No live enquiry, database write or email was sent.

The first real-browser test caught named-input shadowing of `form.action` that
the minimal test adapter missed. The implementation now uses getAttribute and
the test adapter models this actual browser behaviour.

Local screenshot: `C:/Users/777/Downloads/codexmanager/complete99-enquiry-recovery-local-he.png`.
The local router is not in the release package. Its responses are demonstrations,
not proof of live storage, email delivery or customer activity.

## Still open

Native core recovery, optional group fields, dish context carry-over, fresh nonce
renewal, verified operator notification and cleanup under total storage failure.
Expired forms currently retain values and explain copying them before refresh.
The broader food portal and scientific nutrition rollout are not complete.
