# Product mascot beside price on desktop

Theme version: **0.7.12**. Core remains **0.3.5**.

The product price-row layout from the mobile fix now applies at every viewport width. The old absolute positioning above the summary was removed. The monkey has a separate 82px column beside the original WooCommerce price, centered vertically, below the title. A longer title therefore increases its own height and moves the entire price row down, without text/mascot overlap. Prices can wrap independently.

Only three installable files changed from 0.7.11: `assets/css/theme.css`, `functions.php`, and `style.css`. Artwork, animation scripts, WooCommerce price output, and translations did not change.

## Validation

- PHP lint passed for the changed PHP version constant; `git diff --check` passed.
- Eight local viewport/language cases passed: Persian 1440, 1024, 783, 782, 390, and 320px; English 1440 and 390px. The local desktop cases used a browser-only two-line title stress case, without changing any stored product data.
- Geometry checks require the mascot below the full heading, beside the price without overlap, exactly one price and mascot, decorative pointer/screen-reader behavior, and no horizontal overflow.
- Before/after screenshots and measurements are private under `.local/audit/pdp-monkey-desktop/`.
- The user's public example is `/product/rangin-kaman-cream-baby-bedding-set/`.

Private source/database backups and staged release evidence: `.local/deployments/helanak-release-20261123-213956-03e3c2/`. The release identifier uses UTC; the user-facing work date is 24 November in Tehran.

Deployment is theme-only, with no local database or test data import.

Deployment completed and all eight live viewport/language scenarios passed, including the real example's two-line title at 1024px and 783px. The served stylesheet hash matches the reviewed source. All 91 theme files, 19 unchanged Core files, and protected configuration were verified. Store-setting and metadata checks found no business-content or price changes; the only semantic metadata change was a routine `_edit_lock` refresh on post 372 during the work. Both task-owned browser sessions were closed. Final screenshot: `.local/audit/pdp-monkey-desktop/after-live-desktop.png`.
