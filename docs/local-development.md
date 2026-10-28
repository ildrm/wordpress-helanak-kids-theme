# Local development

The working preview is **http://localhost/wp/** and administration is
**http://localhost/wp/wp-admin/**. Start Apache and MySQL in XAMPP to use it.
Use the local administrator account supplied for this project; credentials are
not stored in this documentation.

## Source and runtime

| Item | Location |
| --- | --- |
| Repository | `D:\prj\wordpress-helanak-kids-theme` |
| WordPress runtime | `C:\xampp\htdocs\wp` |
| Dedicated cloned database | `helanak_local` |
| Original local database, retained | `wp` |
| Active theme junction | `C:\xampp\htdocs\wp\wp-content\themes\wp-helanak-kids-theme` |
| Active plugin junction | `C:\xampp\htdocs\wp\wp-content\plugins\helanak-core-plugin` |

Both junctions target the matching component directories in the repository.
Edit the repository sources, then reload the browser. Local asset versions use
file modification times to avoid stale CSS/JavaScript. Production uses release
versions. There is no frontend compilation step.

Commerce data and rules belong in the Core plugin; templates and visual
behavior belong in the theme. The older inactive `helanak-core` folder is
retained from the original local installation; `helanak-core-plugin` is active.

## Local safeguards

`wp-config.php` marks this installation as `local`, disables scheduled WP-Cron,
and logs debugging without displaying it publicly. The MU plugin
`wp-content/mu-plugins/helanak-local-development.php` comes from
`tools/local-development-guard.php` and:

- Suppresses WordPress email delivery and WooCommerce webhooks.
- Rejects outgoing WordPress HTTP API calls except loopback hosts.
- Limits available gateways to cash on delivery, bank transfer, or cheque when
  those gateways are configured.

This guard covers these WordPress hooks; it is not an operating system firewall
for arbitrary third-party code. The guard also blocks update checks and online
service calls, so related local admin notices are expected. Contact submissions
show an accurate delivery failure locally. No real payment or email delivery
has been verified. The MU guard is a local runtime file and is excluded from
the component ZIPs.

## Preview data

The live database contained 12 draft products and no published products. With
the user's explicit approval, those 12 products are published **in this local
clone**. The six products with usable variation prices are locally featured.
Six products still need real variation prices: IDs `63, 80, 93, 100, 105, 126`.
Their prices were not invented. Original statuses, feature flags, and preview
options are retained in the `helanak_local_preview_originals` option.

The preview adds Home, About, Contact, Offers, and Journal pages and a local
menu. About and Contact have short editable introductory copy. Imported
product names/descriptions remain authored Persian content when English UI is
selected; the language switcher translates interface strings and explicit menu
labels, not an entire product-content database. Legal policy drafts, the
original sample page, and the original starter post are retained for editorial
review. Temporary QA products and posts have been removed.

Public contact information is configured in the Customizer and defaults to
empty until real details are supplied. The administrator's email is used as
the message recipient and is not automatically exposed as public contact data.
Newsletter signup saves subscribers locally; connecting an email service and
implementing the store's consent/retention policy remain separate work.

## Backups and recovery

Private migration material is ignored by Git under `.local/migration/`:

| Backup | Purpose |
| --- | --- |
| `live-files/` | Complete downloaded live file snapshot |
| `helanakc_wpdb.sql` | Active live database export used for the clone |
| `helanakc_db.sql` | Separate legacy database export, archived without merging |
| `verification.json` | Transfer counts, size verification, and SQL SHA-256 hashes |
| `local-before.sql` | Original local database export |
| `local-files-before/` | Original local WordPress files |
| `local-wp-config-before.php` | Original local configuration |
| `local-wp-content-before.zip` | Original local content archive |
| `repository-components-before/` | Components before this development session |
| `runtime-components-before/` | Runtime components replaced by source junctions |

These files contain private site data and configuration. Keep them out of
public repositories, installable packages, and shared screenshots.

For rollback, stop Apache, preserve any new local work, and choose the desired
backup. Restore original local files/configuration from `local-files-before/`
to reconnect to the retained `wp` database, or restore the live snapshot into a
separate development database. Account for the two junctions explicitly during
restoration so file operations do not overwrite repository source through them.
Migration/import scripts are setup utilities that replace local state; daily
development requires only the existing XAMPP installation.

## Validation commands

From the repository in PowerShell:

```powershell
rtk proxy python -X utf8 tools/lint-source.py
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tools/wp-local.ps1 eval-file tools/verify-local.php
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tools/wp-local.ps1 eval-file tools/report-local-state.php
```

The integration script refuses other environments and cleans its temporary
records/options in a `finally` block. PHP CLI currently emits a duplicate
OpenSSL-module startup warning from the existing XAMPP configuration; source
lint still reports its actual result. PHPCS/WPCS is not installed in this
environment.

For endpoint regression checks, create scoped fixtures, run the checks, and
remove the tracked records afterward:

```powershell
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tools/wp-local.ps1 eval-file tools/qa-products.php
rtk proxy python -X utf8 tools/check-local-http.py
rtk proxy powershell -NoProfile -ExecutionPolicy Bypass -File tools/wp-local.ps1 eval-file tools/cleanup-qa.php
```

The cleanup script verifies QA ownership before deleting records. Dependency
checks temporarily deactivate local plugins and restore their exact original
active list; run them when another person is not using the preview.

Browser evidence is kept in `.local/audit/`. On this machine Chrome automation
needs execution outside the restricted sandbox. Use a named browser session
with `tools/helanak-browser.py`; keep production and local sessions separate.

## Translation updates

Theme and plugin use separate text domains. Regenerate both POT catalogs with
WP-CLI after adding strings, update `tools/translations-fa.json`, and run
`tools/update-translations.py` to merge PO files and compile MO files. The
helper currently uses the locally staged `polib` module in
`.local/tools/python/`. Check both Persian RTL and English layouts after edits.

## Installable component packages

Build local ZIP files with:

```powershell
rtk proxy python -X utf8 tools/package-components.py
```

Packages and their SHA-256 manifest are written to `.local/artifacts/`. They
contain only the two installable components, including fonts/licenses and
translation files. Database exports, uploads, site configuration, development
tools, and the local MU guard are outside those packages. The two component
packages were deployed under the user's subsequent explicit instruction; see
the [deployment record](deployment-2026-10-28.md). Local preview data and the
local MU guard were excluded.
