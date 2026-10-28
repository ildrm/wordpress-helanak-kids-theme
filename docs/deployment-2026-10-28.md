# Production component update — 28 October 2026

The user explicitly requested updating the online theme and plugin while
excluding local test data. The update is complete on **https://helanak.com/**.

| Component | Before | Deployed |
| --- | --- | --- |
| Helanak Kids theme | 0.6.0 | **0.7.0** |
| Helanak Core plugin | 0.2.0 | **0.3.0** |

Only the two component directories were replaced. Existing WordPress core,
third-party plugins, uploads, configuration, site content and storefront
settings were retained. No database import, local preview setup, product
publication, page creation, image regeneration, or local development guard was
run on production.

## Procedure and checks

1. Downloaded fresh backups of both live component directories, the active
   database and protected configuration before replacement.
2. Uploaded the reviewed packages to a private directory outside the public
   document root. Extracted and verified staged file hashes, paths and safe
   permissions before touching the active components.
3. Applied a brief WordPress maintenance marker, moved the old directories to
   the private rollback location, and installed the new directories. Verified
   file hashes before removing the owned maintenance marker.
4. Checked the homepage, shop and login page: all returned HTTP 200 without
   detected fatal/parse errors. The existing coming-soon screen remains active.
5. Downloaded a second database export for comparison, without importing it.

Verification results:

- All **103 deployed files** match the reviewed source packages.
- All original rows in posts, post metadata, terms, term metadata, term
  taxonomy, term relationships and comments are unchanged.
- **242 existing store, theme, menu and widget settings** are unchanged.
- The active theme/plugin selections, home/shop configuration, indexing and
  coming-soon options are unchanged.
- `wp-config.php` and `.htaccess` have unchanged SHA-256 hashes.
- No QA markers or local-preview markers were introduced.
- The Core plugin performed its normal forms-schema upgrade, adding the
  `helanak_forms_db_version` option and subscriber-table schema. This did not
  import local subscriber data or other local records.

The online catalogue therefore still has **12 draft products**, **199 existing
variations**, **five published pages**, and **two draft pages**. The five
additional local preview pages and locally published/featured product states
were not transferred. Existing production images were not regenerated or
replaced.

## Backups and evidence

Release: `helanak-release-20261028-163353-22ed76`.

Server rollback directories, outside the web root:

```text
/backups/helanak-release-20261028-163353-22ed76/previous/helanak-core-plugin
/backups/helanak-release-20261028-163353-22ed76/previous/wp-helanak-kids-theme
```

Private local backups and evidence:

```text
.local/deployments/helanak-release-20261028-163353-22ed76/
```

That directory contains the original component ZIPs, before/after database
exports, configuration backups, deployment state, `verification.json`,
`extended-data-verification.json`, and `http-checks.json`. Keep it private; the
database and configuration backups contain sensitive site information.

The live certificate's previously observed expiration was not changed.
Deployment API connections retained certificate-chain and hostname validation
with a narrowly scoped certificate-date exception. Real email delivery and
gateway transactions were not tested or submitted during deployment.
