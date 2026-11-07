# Quick Edit variation prices — 6 November 2026

Helanak Core **0.3.1** is deployed on https://helanak.com/. The theme remains
0.7.0. This update adds pricing controls to the native product Quick Edit form
for variable products; simple/external products retain WooCommerce's existing
regular and sale price fields.

## Using the fields

Open **Products → Quick Edit → Variation prices**. Each existing size/model
has separate regular and sale price inputs, in the store's configured currency
(shown in the heading). Edit the relevant rows and use the normal **Update**
button. Clear a sale price to remove that discount. Persian/Arabic digits and
configured decimal/group separators are accepted. Existing sale schedules are
retained, and sale prices must be lower than their corresponding regular price.

Only edited rows are submitted. The server validates the entire submitted
price batch before WordPress saves the product, verifies the parent/variation
relationship and editing permissions, and checks nonces. If another editor
changed a submitted variation's prices after the panel opened, the save is
rejected with a reload message instead of overwriting those prices.

Variable products with no variations show instructions to define their sizes
or models in the full editor. Products with over 200 variations use the full
editor. The largest live product currently has 37 variations.

## Validation

- All 55 component PHP files and the new plugin JavaScript passed syntax checks.
- Local disposable-product tests covered Persian/Arabic digits, configured
  separators, zero prices, malformed/negative values, invalid discounts,
  unauthorized users, foreign child IDs, stale snapshots, unchanged sibling
  prices, and future sale schedules.
- Native Quick Edit browser saves worked for both simple and variable
  products. Saving/reopening prices and clearing/reopening a sale price were
  verified. Disposable fixtures were removed afterward.
- Invalid save nonces and invalid sale prices were rejected before saving;
  invalid read nonces returned 403 and unauthenticated price reads were denied.
- The 390px RTL panel has explicit visible labels and zero reported violations
  in the targeted axe WCAG A/AA check. This is not full WCAG certification.
- Authenticated production verification confirmed the panel is rendered,
  both live assets match the release, and the read-only endpoint loads 26
  existing variations for product 143. **No production price-save requests or
  test-product creation requests were sent.** The verification session logged out.

## Deployment and data integrity

Only the **18 plugin files** were replaced. Source hashes match the staged
release. Fresh component and database backups were taken, then the plugin was
replaced during a brief maintenance window. The theme, WordPress core, uploads,
other plugins and local development guard were excluded.

The before/after exports confirm **all existing regular, sale, active and
scheduled-sale price values are unchanged**, as are product/page content and
publication states. Current content includes 32 published products, three
draft products and 537 published variations. No local preview/test data was
imported. Configuration hashes and the deployment's protected storefront
options are unchanged.

The extended comparison observed two runtime changes: an `_edit_lock` for
post 933, and WooCommerce's `woocommerce_tracker_ua` option. No other semantic
post metadata changed; neither change is a product-price modification.

Release: `helanak-release-20261106-181959-93e627`.

Server rollback copy:

```text
/backups/helanak-release-20261106-181959-93e627/previous/helanak-core-plugin
```

Private local evidence and database backups:

```text
.local/deployments/helanak-release-20261106-181959-93e627/
.local/audit/quick-edit-prices/
```

The deployment directory includes file verification, the authenticated
read-only runtime check, extended setting checks and semantic metadata/price
comparison. Database/configuration backups and authenticated HTML contain
private site data and must remain outside public packages/repositories.
