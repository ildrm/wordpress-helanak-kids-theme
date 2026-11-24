# Viewport bird flybys

Theme version: **0.7.11**. Core remains **0.3.5**.

The bird previously lived inside the footer illustration strip, and its single flyby started only when that strip intersected the viewport. It now lives in a separate fixed, clipped, pointer-transparent decorative layer outside the footer. The footer cats and all ten original animal SVG assets remain intact.

The first flight starts after 1.8 seconds. Each flight takes nine seconds, followed by a random 24–38 second rest. Each pass chooses a vertical position within the current visible viewport below the sticky header; resize and visual viewport changes recalculate that position. Normal scrolling requires no flight scroll handler. The Persian flight starts off the right edge and moves left; the English flight starts off the left edge and moves right. The existing RTL animation previously had the wrong starting edge, which was also corrected.

Existing animation pause controls, reduced-motion preferences, tab visibility, and character/idle settings govern scheduling. Disabling motion removes the current flight and cancels its timers. The layer remains below navigation and dialogs, outside keyboard/screen-reader navigation, and cannot receive pointer events.

## Validation

- PHP lint: `footer.php`, `functions.php`; JavaScript syntax: `assets/js/theme.js`; `git diff --check` passed.
- Local homepage, shop archive, and variable product passed viewport geometry checks at three scroll positions in six scenarios: Persian 390px home, 1440px shop, 320px product, 844×390 landscape home; English 390px product and 1440px home.
- Checks cover visible flight bounds, constant screen position while scrolling, header stacking, pointer pass-through, no horizontal page overflow, and an ARIA-hidden stage outside the footer.
- The unaccelerated local lifecycle test observed a flight of 8.991 seconds and a subsequent pass after a 32.389 second rest while scrolled midway down the page. Pause/resume and reduced-motion behavior passed.
- Screenshots use Chromium viewport emulation; physical-phone testing was not performed.

Only six installable theme files differ from 0.7.10: `assets/css/theme.css`, `assets/css/rtl.css`, `assets/js/theme.js`, `footer.php`, `functions.php`, and `style.css`. No user-facing strings or catalogs changed.

Private QA artifacts: `.local/audit/viewport-bird/`.
Private deployment backup and integrity evidence: `.local/deployments/helanak-release-20261123-164423-5cc9a7/`.

This release deploys theme code only, with no local database or test content import.

Deployment completed successfully. All six geometry scenarios also passed on the public site (18 scroll-position checks), along with resizing during an active flight, pause/resume, and reduced motion. Both served stylesheets and the served theme script match the reviewed source hashes. All 91 theme files and 19 unchanged Core files were verified; protected configuration, seven content tables, 245 store settings, semantic post metadata, and product prices match the deployment backup. Both task-owned browser sessions were closed.
