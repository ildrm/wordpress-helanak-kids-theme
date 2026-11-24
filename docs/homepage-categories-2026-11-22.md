# Homepage category cards and brand spelling — 22 November 2026

The category-selection and spelling changes below remain current. The initial
content-driven card heights were subsequently replaced by the wider, aligned
layout in the [geometry release](category-card-geometry-2026-11-22.md).

Theme **0.7.7** limits the homepage category carousel to nonempty top-level
WooCommerce categories. `parent=0` excludes subcategories; `number=0` removes
the previous ten-term cap. The existing WooCommerce order and hierarchical
nonempty handling remain in place. Category archives still show all their
direct children, including empty children, using the shared card renderer.

Homepage-only CSS removes flex stretching to the longest title, reduces
padding and makes the product count a block with a compact line height.
Names retain automatic wrapping and content-driven height. No line clamping,
fixed text height or clipped names were added. Typical single-line cards
decreased from about 192px to 155px; the measured gap below product counts
decreased from about 37px to 9px. Category artwork, gradients, links, counts,
carousel controls and existing animations are preserved.

Four active theme translations were corrected in the newsletter, blog intro
and Customizer text. An obsolete theme translation was corrected too. Core
**0.3.5** corrects two badge-management labels. Core business logic and its
database schema version are unchanged; its PHP changes only declare the new
release version. Canonical translation mappings, source catalogs, Persian PO
files and compiled MO files were updated together.

## Validation

- Changed PHP files pass syntax checks and `git diff --check` passes.
- The real local WordPress renderer returns every qualifying root category
  and excludes children; both corrected Persian catalogs load successfully.
  No disposable products or categories were created.
- Live browser checks pass Persian/English at 1440×900 and 390×844. All six
  current nonempty roots appear, names fit, count spacing is 9px, mobile
  Previous/Next controls work in RTL/LTR, and no page overflow occurs.
- Anonymous HTTP checks pass home in both languages, shop, blog and category
  pages. Accessories retains its three empty children; the current bedding
  category is a leaf and correctly has no empty subcategory section.
- The fresh production snapshot has no matching brand misspellings in stored
  pages, posts, products, options, metadata or term labels. The checked public
  pages contain none after deployment. Served CSS matches the release source.

## Deployment and data integrity

Release: `helanak-release-20261122-195908-a00ac6`.

Fresh database, configuration and component backups preceded replacement.
Both previous components matched their recorded releases. Seven theme paths
and four Core paths changed; all **110 installed files** were verified.
All ten character SVGs and protected configuration are unchanged.

All seven compared content tables, 245 checked store settings and semantic
post metadata are unchanged between snapshots. There remain 62 published
products, 675 published variations and 387 attachments. No local data,
test content, product edits, price saves, forms or cart writes were deployed.
The already-public WooCommerce visibility setting remained unchanged.

Rollback copies:

```text
/backups/helanak-release-20261122-195908-a00ac6/previous/wp-helanak-kids-theme
/backups/helanak-release-20261122-195908-a00ac6/previous/helanak-core-plugin
```

Private deployment evidence is in the corresponding `.local/deployments/`
directory. Before/after screenshots and browser reports are in
`.local/audit/home-categories/`.
