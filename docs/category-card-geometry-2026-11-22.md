# Complete category artwork and aligned captions — 22 November 2026

Theme **0.7.8** fixes cropped category artwork and uneven homepage card heights.
Core stays **0.3.5** and was not replaced.

The preceding layout used 150px cards, a 96px image frame and cropped square
thumbnails with `object-fit: cover`. Its content-driven caption height made
single-line cards approximately 155px tall and two-line cards approximately
176px tall. Homepage cards now measure 210px wide on desktop and 190px below
600px. Their consistent square artwork frames use `object-fit: contain`.

The homepage requests WordPress's uncropped `medium` image variant through the
shared renderer's optional image-size argument. Existing media and responsive
image attributes are reused; no image regeneration, upload replacement or
third-party image service was added. Landscape/portrait artwork retains its
intrinsic proportions with the existing category accent behind any spare area.

The shared card caption is grouped into a body span. Homepage bodies use a
minimum 80px area with flex centering vertically and horizontally. Cards
stretch to the tallest natural caption in the rail, so long titles can wrap
and grow without clipping while all cards stay aligned. There is no fixed
maximum caption height or line clamp. The existing archive layout keeps its
150px cards, 96px frames, block captions and thumbnail image preset.

The root-category filter, unlimited category count, WooCommerce order, links,
product counts, corrected brand spelling and animations are unchanged. No
user-facing strings changed, so translation catalogs were not modified.

## Validation

- Changed PHP files pass syntax checks; `git diff --check` passes.
- Real local and anonymous live browser checks pass Persian and English at
  1440×900 and 390×844, plus Persian at 320×800. All cards have equal heights
  within each case; caption-center offsets are below 1px, names fit, images
  use contain sizing, carousel arrows work in RTL/LTR and the page has no
  horizontal overflow.
- Synthetic long titles were applied only to the browser DOM and restored.
  All cards grew together while the text remained available and centered.
- Live Persian cards measure 210×290px on desktop and 190×270px on mobile.
  Two-line English captions grow the whole mobile rail to approximately
  278px tall. The six existing homepage category IDs remain unchanged.
- Accessories retains its three direct-child cards with the original archive
  geometry. The served CSS matches the source package exactly.
- Before/after screenshots were inspected; temporary browsers were closed.

## Deployment

Release: `helanak-release-20261122-202339-611b79`.

Only the theme was replaced after fresh database, configuration and theme
backups. Its preceding source matched the last release. Five paths changed:
CSS, the shared rail, homepage rail arguments and two version declarations.
All 91 theme files and 19 unchanged Core files were verified. All ten character
SVGs and protected configuration remain identical. No local data or test
content was imported and no forms, cart or price-save requests were made.

The snapshots retain 62 published products, 682 published variations and 387
attachments. Product records and protected site options passed comparison;
the detailed metadata/store-setting comparison is in the private evidence.

Rollback theme:

```text
/backups/helanak-release-20261122-202339-611b79/previous/wp-helanak-kids-theme
```

Private backup and deployment evidence is in the matching `.local/deployments/`
directory. Browser geometry reports and before/after screenshots are in
`.local/audit/category-geometry/`.
