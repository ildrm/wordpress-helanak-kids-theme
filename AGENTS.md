# Repository Guidelines

## Project Structure & Module Organization

This repository contains two installable WordPress components:

- `wp-helanak-kids-theme/` is the presentation layer. Root PHP files are WordPress templates, `inc/` contains feature classes and template helpers, `template-parts/` contains reusable page sections, `woocommerce/` holds WooCommerce overrides, and `assets/css/` and `assets/js/` contain browser assets.
- `helanak-core-plugin/` owns business logic such as age taxonomies, product safety fields, badges, language switching, and HPOS compatibility. Its entry point is `helanak-core.php`; implementation classes live in `includes/`.
- Each component keeps translations in `languages/`. Update both source catalogs and compiled `.mo` files when changing user-facing strings.

Keep commerce data and rules in the plugin; keep markup, styling, and visual behavior in the theme.

## Development, Validation, and Local Setup

There is no compilation step or committed package-manager configuration. Install WordPress 6.5+, PHP 8.1+, and WooCommerce, then link or copy both component directories into `wp-content/`.

Useful WP-CLI commands:

```sh
wp plugin activate woocommerce helanak-core
wp theme activate wp-helanak-kids-theme
```

Check changed PHP files with `php -l path/to/file.php`. If WordPress Coding Standards is installed globally, run:

```sh
phpcs --standard=WordPress wp-helanak-kids-theme helanak-core-plugin
```

## Coding Style & Naming Conventions

Follow WordPress conventions already present: tabs for PHP indentation, spaces inside parentheses, escaped output, sanitized request data, and translation functions with the correct text domain. Name classes with `Helanak_Theme_` or `Helanak_Core_`; class files use `class-kebab-case.php`. Prefix global functions, hooks, CSS classes, and JavaScript settings with `helanak`. Keep JavaScript ES5-compatible where surrounding files use IIFEs and `var`.

## Testing Guidelines

No automated test suite or coverage threshold is currently configured. Before submitting, lint every changed PHP file and smoke-test activation, the storefront, product/archive pages, cart interactions, Customizer settings, AJAX features, and both English and Persian RTL layouts. Verify WooCommerce works with the core plugin active and that missing-dependency admin notices remain non-fatal.

## Commit & Pull Request Guidelines

History is minimal and uses short imperative subjects such as `init commit`; use a clearer equivalent, for example `Fix RTL product filter spacing`. Keep commits focused. Pull requests should explain the user-visible impact, list validation performed, link relevant issues, and include before/after screenshots for layout or Customizer changes. Call out translation, WooCommerce-template, or backward-compatibility effects explicitly.
