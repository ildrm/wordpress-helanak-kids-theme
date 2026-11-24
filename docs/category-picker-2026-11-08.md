# Category palette and variation typography — 8 November 2026

Theme **0.7.4** addresses the category-header white panel and product-option
font mismatch. Helanak Core remains **0.3.4**.

The category title/description panel now uses `--background-color`, matching
the page instead of a fixed translucent white. The rule applies to every
viewport and follows the existing theme palette and Customizer colors.

Variable-product pages use WooCommerce's bundled SelectWoo for the opened
variation choices. The menu is rendered in the document, so the self-hosted
Vazirmatn/Poppins fonts apply to the actual option text. Native OS-rendered
select popups have styling limitations, as described in the
[MDN customizable-select background](https://developer.mozilla.org/en-US/docs/Learn_web_development/Extensions/Forms/Customizable_select#background).

The real `<select>` IDs, names, values and WooCommerce change events are kept.
Attribute labels are connected to the visible controls for assistive tools.
The existing variation matching, price/image updates and Reset link continue
to use WooCommerce's handlers. No add-to-cart or price-save requests are part
of verification. The picker assets load only on variable PDPs; category pages
do not receive them. No new translated messages or font files were introduced.

## Validation

- Changed PHP and JavaScript pass syntax checks; `git diff --check` passes.
- Local category checks cover Persian desktop 1440×900, phone 390×844 and
  English desktop. Panel and body backgrounds match, with no horizontal
  overflow, and follow a temporary DOM palette preview without saving settings.
- Opened picker text matches the body font in Persian/English. Chrome's
  rendered-font inspection confirms the custom font is used for glyphs.
- Pointer/touch selection, matching a real variation, native FormData values,
  Reset and keyboard selection/Escape are checked on existing products.

## Deployment

Release: `helanak-release-20261108-050734-5f6519`.

Only the 89 theme files were replaced after fresh database/configuration/theme
backups. Five paths changed: the CSS, new variation-picker script, asset
registration and two version declarations. The preceding online theme matched
the last release before replacement. All ten character SVGs remain identical.

Rollback copy:

```text
/backups/helanak-release-20261108-050734-5f6519/previous/wp-helanak-kids-theme
```

Private backups and runtime evidence are in
`.local/deployments/helanak-release-20261108-050734-5f6519/`.
Browser screenshots and results are in `.local/audit/category-option/`.

The final live browser checks were completed on theme **0.7.5**, which also
adds [subcategory navigation](subcategories-2026-11-08.md). The actual Persian
option glyphs render in self-hosted Vazirmatn at desktop and phone sizes.
English controls inherit the Poppins stack; existing Persian product data
uses the same system fallback as other Persian text in English mode.
Product 933 matched variation 935 through pointer/touch and keyboard selection,
with unchanged native submitted attribute values. Reset and Escape passed.
No cart or price-save requests were made. The temporary verification logins
were revoked and their saved authentication files removed.
