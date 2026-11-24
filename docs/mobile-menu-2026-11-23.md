# Complete mobile navigation — 23 November 2026

Theme **0.7.9** fixes the incomplete and cramped hamburger menu. Core remains
**0.3.5**. The live site previously fell back to only Home and Shop. Its mobile
layout used two columns, expanded child lists and no independent height limit.

## Resulting behavior

- The fallback includes the real nonempty product-category hierarchy in the
  existing WooCommerce order. Page-only custom primary menus retain their
  links and receive the same category tree. Custom menus containing native
  product-category entries remain authoritative, avoiding a second copy.
  No stored menu items, locations, category records or settings are rewritten.
- Category/page titles remain real links. Separate 44px-wide disclosure
  buttons open child lists, with translated names, `aria-controls` and
  `aria-expanded`. Hidden branches stay hidden even under hover/focus rules.
- One-column rows have at least 48px height. The panel sits below the sticky
  header, without making the header itself taller. Its height follows the
  visible viewport, orientation, browser UI and header position; it scrolls
  independently and contains scroll chaining.
- The hamburger becomes a close icon. Escape restores focus to it; focus or
  interaction outside dismisses the panel. Touch dismissal checks coordinates
  because a browser can retain the old event target during momentum scrolling.
  This case was reproduced and tested through native touch events.
- Floating cart/comparison controls are temporarily hidden while navigation
  is open. Header cart and search remain available, close the menu correctly
  and retain their existing behavior. Desktop resizing restores desktop nav.
- Empty item IDs intentionally removed by another plugin stay empty, avoiding
  the duplicate suffixed IDs observed in the local custom menu.

The hierarchy is server rendered and immediately available; no remote menu
request or new library is required. The new accessible submenu label is in
the POT, Persian PO and compiled MO. The theme has 246 translated entries.
Category-card geometry, root-only homepage selection, animals and commerce
logic are unchanged.

## Validation

Local and anonymous live Chromium checks pass Persian at 390×844, 320×568,
360×640 and 844×390, and English at 390×844 and 844×390. Checks cover viewport
bounds, horizontal overflow, visible hit targets, nested disclosure buttons,
keyboard Enter/Escape, focus restoration, toggle closing and desktop resizing.
The local five-page custom menu is preserved alongside seven category links.
The live menu contains six category roots and three child categories, in
addition to Home and Shop.

Native touch emulation passes portrait and landscape: opening, disclosure,
swiping the overflowing panel without moving the underlying page, dismissal
after momentum scrolling and real category-link navigation. Cart open/close,
focus return and search focus were checked without adding items or ordering.
These are emulation results, not a claim of physical-device Safari testing.

Changed PHP and JavaScript pass syntax checks and `git diff --check` passes.
The served CSS/JS match the release. Before/after screenshots were inspected;
owned test browsers were closed. The existing HTTPS date-validity error needed
the established test-browser exception; this UI release does not repair TLS.

## Deployment and integrity

Release: `helanak-release-20261123-050911-aded0a`.

Fresh database/configuration/theme backups preceded replacement. The previous
theme matched the recorded **0.7.8** release. Only nine theme paths changed:
CSS, main JS, assets registration, template helpers, three language catalogs
and two version declarations. All 91 theme files and 19 unchanged Core files
were verified. All ten character SVGs and protected configuration are identical.

All seven compared content tables, 245 checked store settings and semantic
post metadata remained unchanged. Snapshots retain 62 published products,
682 published variations and 395 attachments. There was no data import,
test publishing, price change or add-to-cart/order operation.

Rollback:

```text
/backups/helanak-release-20261123-050911-aded0a/previous/wp-helanak-kids-theme
```

Private deployment evidence is in the matching `.local/deployments/` directory.
Screenshots and browser/touch results are in `.local/audit/mobile-menu/`.
