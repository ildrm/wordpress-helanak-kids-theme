# Mobile product mascot placement

Theme release: **0.7.10**. Core remains **0.3.5**.

The product-summary monkey was absolutely positioned at `top: -20px`, causing it to cover product headings on small screens. The summary price callback now renders WooCommerce's original price template and the existing monkey inside one wrapper. At widths up to 782px, that wrapper becomes a flex row below the title, reserving an 82px column for the character. The price can wrap independently. Desktop placement, character artwork, animation behavior, and decorative accessibility attributes remain intact.

Only four installable theme files changed from 0.7.9: `assets/css/theme.css`, `inc/class-single-product-hooks.php`, `functions.php`, and `style.css`. No new strings or translation changes were needed.

## Validation

- PHP lint passed for both changed PHP files; `git diff --check` passed.
- Local variable product with a price range and the live Mothercare towel product passed geometry checks at Persian widths 320, 360, 390, 782, and 1440px, and English widths 320, 390, and 1440px.
- Each summary contains exactly one original WooCommerce price and one monkey. Mobile title/mascot and price/mascot bounds do not overlap; price and character share a row. No horizontal document overflow was detected.
- Screenshots were inspected at mobile and desktop widths. Validation used Chromium viewport emulation, not physical phones.
- All ten character SVG files are identical to the previous deployed release.

Private screenshots and measurements: `.local/audit/pdp-monkey/`.
Private backup and deployment evidence: `.local/deployments/helanak-release-20261123-162810-9a6b12/`.

The update deploys theme code only; it does not import a local database or test products.

Deployment completed and was verified against all 91 theme files and 19 unchanged Core files. The live stylesheet hash matches the local source. Seven content tables, 245 existing store settings, semantic post metadata, and product prices match the before-deployment backup. Both task-owned browser sessions were closed. Final viewport screenshot: `.local/audit/pdp-monkey/after-live-mobile.png`.
