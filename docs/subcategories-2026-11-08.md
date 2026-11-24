# Category archive navigation — 8 November 2026

Theme **0.7.5** displays the current product category's direct children above
the product search, filters and results. Core remains **0.3.4**.

The homepage and archives share `template-parts/category-rail.php`, preserving
category images, visual metadata, localized labels, product counts and existing
icon animations. The homepage's query and Customizer visibility remain intact.
Each carousel owns its controls, with RTL/LTR direction, disabled endpoints and
the existing reduced-motion preference. Mobile users can scroll the card rail.

`template-parts/archive-subcategories.php` queries only the current category's
immediate children, in WooCommerce category order, including empty children.
For example, Accessories currently has three zero-product children: headbands,
socks and hats. Leaf categories and the main shop omit the entire section.
No categories, images, products, prices or parent relationships were created or
changed. The new heading is included in the source catalog and Persian PO/MO.

## Validation

- All changed/new PHP files and browser scripts pass syntax checks.
- Local and live browser checks pass Persian and English at desktop 1440×900
  and phone 390×844. All three direct children render, the section precedes
  product results, links reach the correct child, leaf/shop sections are absent,
  mobile arrows work in both directions, and no page overflow occurs.
- The local homepage's seven category cards match the pre-refactor content,
  image and link snapshot exactly. The live homepage's six visible category
  cards and carousel remain functional; live catalogue data differs from local.
- Live HTTP checks confirm the translated markup and exact served CSS/scripts.
  The earlier category-palette and variation-picker fixes remain included.

## Deployment and data review

Release: `helanak-release-20261108-193154-4cac7b`.

Only the theme was replaced after fresh database/configuration/theme backups.
The preceding theme matched the last release exactly. All **91 theme files**
and **19 unchanged Core files** were verified against their source manifests.
All ten character SVGs are unchanged. Protected configuration is unchanged.
No database import, media upload, activation, test-product publishing, price
save or add-to-cart request was used.

The initial strict data comparison flagged concurrent production edits and is
retained in the private evidence. Review found modified timestamps on products
496/528, a new revision for product 496, edit locks and product 528's SKU change.
These edits were preserved. Prices, the 244 checked store settings, taxonomy
tables and media inventory stayed unchanged. There were 32 published products
and 563 published variations in both snapshots. Complete-stack verification
explicitly records the concurrent content edits; it does not claim the entire
database is identical.

Rollback copy:

```text
/backups/helanak-release-20261108-193154-4cac7b/previous/wp-helanak-kids-theme
```

Private deployment evidence is in the matching `.local/deployments/` directory.
Screenshots and browser results are in `.local/audit/category-option/`.
