# Security, privacy and integration boundary

## Public enquiry data

The form validates a nonce, honeypot, consent and required fields; rate limits by a
salted one-way network-address hash; stores a private `c99_lead` record; and performs
no external send. Do not request medical information, employee records, identity
documents or payment data.

Define a retention period with the business owner before public launch. Until a
policy is approved, access is restricted to WordPress administrators and deletion is
manual and auditable.

## OS read-model signature

The OS sends:

- `X-Complete99-Timestamp`: current Unix time;
- `X-Complete99-Nonce`: 16–128 URL-safe random characters;
- `X-Complete99-Signature`: lowercase HMAC-SHA256.

Canonical message:

```text
<timestamp>\n<nonce>\n<sha256-of-exact-request-body>
```

The shared secret is 32+ random characters entered in WordPress administration and
the Sites secret store. It is never rendered by WordPress. Requests expire after
five minutes and a used nonce is rejected for ten minutes.

Only public branches, menu sections/items and public campaigns are accepted. Unknown
fields are dropped, record counts and bytes are capped, image names must use the
owned `c99-` asset namespace, and only records explicitly marked `published` appear
at `/wp-json/complete99/v1/public-catalog`.

## Cameras, devices and robots

WordPress is not a telemetry or video plane. Future integrations require a device
registry, per-device credentials, signed events, heartbeat, offline queue, retention,
role checks and an adapter owned by Complete99 OS. WordPress may receive a public
status statement only when it is explicitly approved for publication.

## Social networks and AI

A campaign remains draft/approved until the real platform OAuth flow and account
permissions are complete. “Sent” requires a provider receipt. AI output is a proposal
with source links and an approval gate; it cannot approve its own public claim,
supplier order or regulated instruction.

## Deployment secrets

Application Passwords, route tokens, sync secrets and authorization headers never
enter Git, ZIPs, logs or audit artifacts. The temporary route exists only for one
transaction and requires both a WordPress administrator capability and an
unpredictable deployment token.
