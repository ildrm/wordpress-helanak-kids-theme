# Shop filter panel — 7 November 2026

Theme **0.7.3** gives every shop filter group a native accordion, including price
and availability/offers. Active groups reopen after a filter request. Selected
values remain in the form when their accordions are closed.

The panel has one vertical scrolling region for its filter groups. Individual
option lists no longer create nested scrollbars. The heading, Clear link and
Apply filters button sit outside that region and remain accessible. Desktop
panel height follows the visible space below the sticky header, including the
WordPress admin bar and responsive header changes. The mobile panel remains
collapsible and its expanded form also has a bounded independent scroll area.
Its height also accounts for the mobile toggle and admin-bar spacing so the
Apply button remains within the visible viewport.

Scroll containment prevents wheel/touch gestures at the region's boundaries
from scrolling the product results. Gestures outside the panel still scroll
the page normally. Keyboard users can focus the labelled region and use
Page Down; native summaries support Enter/Space. Invalid price input reveals
its accordion and receives focus before native validation feedback.

## Validation

- PHP syntax checks passed for the archive template, and the changed JavaScript
  passed `node --check`. `git diff --check` passed.
- Local checks covered Persian desktop 1440×900, tablet 900×700, mobile 390×844
  and English desktop 1440×900, with no horizontal overflow.
- Trusted Chrome input events confirmed independent wheel scrolling before the
  end of results, containment at both panel boundaries, Page Down scrolling,
  mobile touch panning and normal page scrolling outside the panel.
- Native Enter/Space toggled the price accordion. A negative price in a closed
  accordion reopened it and focused the input; no form was submitted.
- Existing translation messages were reused; no user-facing strings were added.

## Deployment

Initial filter release: `helanak-release-20261107-034908-8d3b40`.

Final mobile-fit release: `helanak-release-20261107-035805-38f479`.

Only the 88 theme files were replaced after fresh database/configuration/theme
backups. The five changed files are the archive template, layout CSS, theme
JavaScript and the two version declarations. Helanak Core remains **0.3.4**;
all ten animal SVG assets are byte-identical to the preceding release.

Server rollback copy:

```text
/backups/helanak-release-20261107-034908-8d3b40/previous/wp-helanak-kids-theme
/backups/helanak-release-20261107-035805-38f479/previous/wp-helanak-kids-theme
```

Private backups and verification results are kept in
`.local/deployments/helanak-release-20261107-034908-8d3b40/`.
The final snapshot is in
`.local/deployments/helanak-release-20261107-035805-38f479/`.
Local input-test evidence is in `.local/audit/filter-panel/`.

## Online verification

All 107 final theme/plugin file hashes match the expected releases. Core 0.3.4
and the ten character assets are unchanged. The before/after database exports
show identical content tables and all semantic post metadata/prices. Of 244
tracked store options, 243 were unchanged; `woocommerce_db_version` advanced
from 11.2.1 to 11.2.2 while the installed `woocommerce_version` remained 11.2.2.
This version-marker change is recorded separately from product content. No
test data was imported.

Live desktop and mobile input checks confirmed independent wheel scrolling at
both boundaries, keyboard operation and mobile touch panning. The final mobile
fit check measured the Apply button bottom at 810px in an 844px viewport,
including the actual WordPress admin header; the options retained a 422px
independent scroll area. Filter/search results and reopening active price/stock
accordions also passed authenticated HTTP checks.

Final screenshots: `.local/audit/filter-panel/live-desktop.png` and
`.local/audit/filter-panel/live-mobile.png`. Task browsers were closed, the final
verification login was logged out, and temporary auth files were deleted.
