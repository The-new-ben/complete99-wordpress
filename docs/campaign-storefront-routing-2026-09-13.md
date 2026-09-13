# Campaign storefront routing correction

Private release evidence. This is not customer-facing copy.

## Production observation

The existing WooCommerce shop alias `/shop/` responds with HTTP302 to the
Complete99 `/store/` page. The Campaign placement resolver used the former,
while Commerce intentionally redirects visitors to the latter. The Campaign
absence proof correctly rejects redirects. Its renderer previously admitted
only the front page and WooCommerce `is_shop()`, excluding the owned custom store.

## Implemented in 1.24.1

- Resolve the Hebrew store placement through the same published translation
  ownership registry as the existing storefront, not a hardcoded slug or the
  WooCommerce shop option.
- Render that placement only on the exact published store ID. Preserve the
  existing home placement. Exclude admin, previews, unrelated pages and aliases.
- Reject missing, ambiguous or off-origin store ownership. Preserve redirect
  rejection, Campaign lifecycle checks, immutable receipts and historical proof URLs.
- Retain the complete 1.24.0 editorial homepage implementation in the package.
- Preserve the already-deployed provider-receipt width repair (32 bytes) in
  schema creation, schema invariants and bounded aggregate receipt writes.
  This was previously represented only by the recovery bridge and live repair,
  not by release source. Shipping the former source would regress live repair.

## Verification and limits

Local focused suite: 165 passed, 1 skipped, 30 subtests. Full source PHP lint,
secret scan and reproducible packaging passed. Executable PHP tests exercise the
actual class, including a custom slug and a WooCommerce alias that must never be
read. CI and independent review remain required before deployment.

This is not deployment recovery completion. Production remains1.22.1 with an
interrupted activation journal. The old journal must not be overwritten or
treated as a new baseline. Historical redirect origins and the separately
observed date/catalog-receipt drift need explicit forward recovery handling.
This patch does not broaden multilingual Campaign placement coverage or change
any public page, canonical, redirect, index policy or transaction setting.
