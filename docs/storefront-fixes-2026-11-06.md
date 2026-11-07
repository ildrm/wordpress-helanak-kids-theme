# Storefront interaction and catalogue fixes — 6–7 November 2026

Theme **0.7.1** and Helanak Core **0.3.4** address the seven reported storefront
issues. This release retains the preceding Quick Edit variation-price feature.

## Customer-visible changes

1. Comparison reads **مقایسه (2 محصول)** and remains available with one item.
   Click an active card button again to remove its product, or use the clearly
   labelled removal button above that product in the comparison dialog. The
   dialog handles two, one and zero items, restores keyboard focus, prunes
   unavailable products and synchronizes other tabs. A fourth selection prompts
   the shopper to remove an item first instead of discarding an existing choice.
2. Native WooCommerce hover zoom and PhotoSwipe are retained. The zoom image
   previously intercepted clicks on the main-image link; disabling pointer
   events on that overlay restores click-to-enlarge while retaining hover pan.
3. Variation selectors and their options inherit the site's Persian/English
   typography.
4. The shop shows populated category, brand, age, badge and global-attribute
   facets, plus price, in-stock and discounted-product controls. The actual
   Persian attributes for color, size and clothing pieces are supported, even
   when their public archive flag is disabled. Multiple choices within a facet
   use OR; different facets combine with AND.
5. A labelled product-name search appears above the shop filters, including
   when the mobile filter panel is collapsed. Search, sorting, wishlist and
   category context are preserved when appropriate. Empty price inputs no
   longer accidentally restrict the result to zero-priced products.
6. The category section is now **خرید بر اساس دسته‌بندی**. Physical left/right
   arrows agree with scrolling in both RTL and LTR, with disabled edge controls.
7. The gallery has its own stacking context, keeping the magnifier below the
   sticky header while scrolling.

No animal illustration or character animation was removed. No additional
viewer, frontend framework or animation dependency was introduced.

## Validation and practical limits

- All 56 component PHP files and eight theme JavaScript files pass syntax checks.
- Twenty local HTTP catalogue checks cover Persian attributes and term slugs,
  raw/encoded native and checkbox URLs, native AND preservation, facet OR/AND,
  variable-product discounts, wishlist intersections, native price ranges,
  title-only search, blank price controls and malformed input.
- Six additional Persian/English checks cover blank, whitespace-only and real
  keywords. Empty submissions retain the saved shop title and ordinary catalogue
  sorting; actual searches retain their query heading and relevance option.
- Six anonymous comparison checks cover two/one/zero products, hidden and stale
  IDs, deduplication and nonce rejection. Missing-WooCommerce and missing-Core
  fallback checks returned HTTP 200 without fatal errors, and restored the
  original local plugin state. All owned disposable products and terms were
  removed after fixture-dependent browser checks.
- Browser checks cover comparison removal through zero items, focus, stale IDs,
  storage sync, the three-product limit, actual carousel motion in both
  directions, Persian/English selector fonts, desktop/mobile layouts, hover
  zoom, alternate slides, native lightbox and header hit testing on scroll.
- Local product 143 has 2560px sources. Live product 933 has 800px sources,
  displayed around 545px on desktop: native magnification is approximately
  1.47×. Stronger detail requires higher-resolution original photographs;
  upscaling would not recover detail.
- Legacy per-product custom attributes are not automatically converted to
  global WooCommerce attributes. The global color/size/piece filters match
  products assigned those global terms. No product-data normalization was
  performed in this code-only release.

All three independent design review rounds passed with no material remaining issues. They used
a separate evaluator session. All available agent models are from the same
provider; this is not a cross-provider review.

## Release evidence

Local evidence is stored privately in `.local/audit/seven-issues/`:
`catalog-filter-checks.json`, `ui-validation.md`, and the desktop/mobile
screenshots. Local disposable fixtures are excluded from installable packages
and are removed after evaluation.

## Online release — 7 November 2026

Full component release: `helanak-release-20261107-030443-c138ce`.

Persian-slug correction: `helanak-release-20261107-032036-584fb3`.

Final Core release: `helanak-release-20261107-033010-265196`.

The 88 theme files and 19 plugin files were deployed from verified ZIPs after
fresh production database/configuration/component backups. Production code was
unchanged from the previous release before replacement. The existing Quick Edit
implementation and all ten animal SVG assets are byte-identical to the prior
online release. A short maintenance window protected component replacement;
automatic rollback was available if installation failed.

Authenticated read-only HTTP checks confirmed the Persian heading/count,
comparison with two/one/zero/stale IDs, every populated catalogue control,
product 933 in Persian/English, matching and unmatched name searches, and served
CSS/JavaScript hashes. Quick Edit still renders and loads the 26 existing
variations of product 143; zero price-save requests were made.

The final real-product check caught a Persian-slug conversion bug that ASCII
fixture values had masked. WooCommerce's `wc_clean()` removes percent-encoded
octets. Core 0.3.3 passes decoded UTF-8 term slugs to WooCommerce, retaining
native lookup queries and their AND/OR behavior. The bug was reproduced locally
with Persian fixture slugs before correction, then all 20 cases passed. Online,
raw/encoded/native/checkbox color URLs now return the same eight gray products,
including product 933; size ۱ plus clothing piece شلوار returns the same nine
products as native WooCommerce. No live reindex or attribute-data conversion
was needed. Only the plugin entry file and catalogue-filter class changed in
this correction; the theme and Quick Edit implementation remained identical.

Core 0.3.4 also treats an empty keyword as a catalogue visit. This prevents a
normal filter submission from displaying an empty-search heading or selecting
relevance sorting. Persian/English checks and the final independent review
confirmed the ordinary shop title, while nonempty searches remain unchanged.

Only the theme and companion plugin were in the package. Database imports,
uploads, preview content and local configuration were excluded. The existing
Coming Soon setting was retained.

Server rollback copies:

```text
/backups/helanak-release-20261107-030443-c138ce/previous/helanak-core-plugin
/backups/helanak-release-20261107-030443-c138ce/previous/wp-helanak-kids-theme
/backups/helanak-release-20261107-032036-584fb3/previous/helanak-core-plugin
/backups/helanak-release-20261107-033010-265196/previous/helanak-core-plugin
```

Private production backups, manifests and runtime evidence:

```text
.local/deployments/helanak-release-20261107-030443-c138ce/
.local/deployments/helanak-release-20261107-032036-584fb3/
.local/deployments/helanak-release-20261107-033010-265196/
```

The final stack verification matched all **107 online files**: theme 0.7.1 and
Core 0.3.4. The before/after comparisons show identical product/page rows,
publication states, taxonomy relationships, terms, comments and all semantic
post metadata, including every regular/sale/active price and sale schedule.
Production retains 32 published products, three draft products, 537 published
variations and 259 attachments. Protected configuration hashes and storefront
options are unchanged, with zero QA/local-preview markers.

The final correction also preserved all 244 existing store settings. The first
component release changed only WooCommerce's runtime `woocommerce_tracker_ua`
value among those settings; no authored content or commerce data changed in
any release.

Actual live checkbox-and-button submissions returned eight gray products on
desktop and nine size-۱/شلوار products at 390px. Selected values survived and
neither layout had horizontal overflow. Native gallery hover, click-to-open,
header stacking and the Persian variation font passed on actual product 933.

Final live screenshots are in `.local/audit/seven-issues/`:

- `live-home-fa-desktop.png`
- `live-product-fa-desktop.png`
- `live-shop-fa-desktop.png`
- `live-shop-fa-phone.png`
- `live-compare-fa-phone.png`

The final verification login was logged out, temporary authentication files were
deleted, and the task's named browser sessions were closed.
