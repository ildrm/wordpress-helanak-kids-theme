# Variation image selection

Theme release: **0.7.13**. Core remains **0.3.5**.

Both reported products have a wildcard variation before their design-specific variations. WooCommerce resolves the wildcard first, so the main gallery receives the generic image even when a matching design-specific photo exists. Reproduction confirmed product 1185 resolved variation 1198 for design 10; product 1161 likewise starts with wildcard variation 1162.

The fix is limited to the product-image payload. A compact, form-scoped JSON map contains visible variations' attributes and explicitly assigned image IDs, with deduplicated WooCommerce image properties. This also supports products whose native variation lookup uses AJAX. The theme's existing variation-picker script selects the most specific fully matching combination for its image and passes an image-only copy of the native result to WooCommerce's original gallery updater. Ties retain native order. Incomplete selections, reset, and combinations without a dedicated image retain the native fallback. The original matched variation object, variation ID, prices, stock, submitted attributes, and product records are not changed.

Native gallery updates continue to handle thumbnails, responsive image sources, zoom and lightbox targets. No new viewer or image-switching UI was added. No CSS, character artwork, animation behavior, or translation strings changed.

## Validation

- Both changed PHP files passed `php -l`; the picker passed `node --check`; `git diff --check` passed.
- An isolated browser prototype exercised all ten Mothercare designs and all five rattle designs at desktop and mobile widths: 30 selections plus reset. Each expected image and lightbox target matched, and the original variation ID, submitted attributes and price output remained intact.
- Rapid design changes left the final selected image visible.
- A local AJAX product with two attributes passed complete-combination specificity, partial-selection, missing-image fallback and reset tests. Fixtures existed only in the browser/process; no database records were created or edited. The real PHP image-map renderer was exercised with existing local attachment data.

Private QA evidence: `.local/audit/variation-images/`.
Private source/configuration/database backups and deployment evidence: `.local/deployments/helanak-release-20261123-220047-53fae3/`.

Only four installable theme files differ from 0.7.12: `assets/js/variation-picker.js`, `inc/class-single-product-hooks.php`, `functions.php`, and `style.css`. The latter two are cache-version declarations. Deployment is theme-only with no local content import.

Deployment completed successfully. The real server-rendered map passed all 30 live desktop/mobile design selections and four resets, plus an English selection. The served picker script matches the reviewed source hash. All 91 theme files, 19 unchanged Core files, and protected configuration were verified. Content/settings and semantic metadata audits found no changes, including no price changes; 248 existing store settings matched. Both task-owned browsers were closed. Final selected-design screenshot: `.local/audit/variation-images/live-selected-design.png`.
