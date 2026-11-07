# Helanak Kids Store

Persian/English WooCommerce theme and companion plugin, developed against the
local XAMPP clone at **http://localhost/wp/**.

- `wp-helanak-kids-theme/` — presentation, accessible shopping interactions,
  responsive layouts, and animated animal companions. Version **0.7.3**.
- `helanak-core-plugin/` — product taxonomies, safety data, badges, language
  metadata, shipping eligibility, store forms, and generated image formats.
  Version **0.3.4**.

The XAMPP installation uses junctions to these two source directories. Saved
changes appear locally without a build step. With the user's explicit approval,
the two production components were updated on 28 October 2026; local preview
data and settings were excluded. Core was subsequently updated on 6 November
2026 to add variation prices to Quick Edit. See the
[original deployment record](docs/deployment-2026-10-28.md) and
[Quick Edit release](docs/quick-edit-prices-2026-11-06.md). Both components were
updated on 7 November 2026 with comparison removal, native gallery fixes,
expanded catalogue filters, product-name search, typography and RTL navigation
corrections; see the [storefront release](docs/storefront-fixes-2026-11-06.md).
The shop filter panel was subsequently updated with complete accordions and
independent scrolling; see the [filter panel release](docs/filter-panel-2026-11-07.md).

Read the [local development guide](docs/local-development.md),
[audit and validation report](docs/audit-2026-10-28.md), and
[installed skill inventory](docs/installed-skills.md).

PHP 8.1+, WordPress 6.5+, and WooCommerce are required for the full store.
The checked local runtime versions are recorded in the audit. Follow
[AGENTS.md](AGENTS.md) for repository conventions.
