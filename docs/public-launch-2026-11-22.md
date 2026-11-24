# Public storefront and E-Namad — 22 November 2026

Theme **0.7.6** adds the supplied official E-Namad image/link to the shared
footer. Core stays **0.3.4**. The anchor and image retain `referrerpolicy="origin"`,
the supplied ID 8602542 and exact verification code. The new-tab link uses
`rel="noopener"`; it intentionally does not use `noreferrer`, which would
discard the origin the provider requires. No third-party JavaScript is added.

The centered badge has a bounded image area, preserved image proportions,
a visible keyboard focus ring and a translated accessible image label.
Reserved dimensions avoid moving footer content when the remote image loads.
Existing footer navigation, animation controls and all ten character SVGs
remain unchanged. Theme POT, Persian PO and compiled MO were regenerated.

## Deployment

Release: `helanak-release-20261122-190006-a87a53`.

The theme was installed after fresh database, configuration and source backups.
Its preceding files matched the last release exactly. Seven paths changed:
footer, CSS, two version declarations and three translation files. All 91
theme files and protected configuration passed the source comparison. No
Core replacement, database import, upload transfer, test product publication
or commerce request was performed.

The strict database check flagged concurrent catalogue editing and its report
was retained. Product 1263 changed from draft to published, its metadata and
term relationships were updated, and attachment 1267's description changed.
These live edits were preserved. All 244 checked store settings were unchanged
before the requested visibility operation. An explicit data-review receipt
records these differences instead of claiming the whole database was identical.

Rollback theme:

```text
/backups/helanak-release-20261122-190006-a87a53/previous/wp-helanak-kids-theme
```

## Visibility status

The anonymous browser confirmed WooCommerce's coming-soon page, and the fresh
database backup has `woocommerce_coming_soon=yes`. The authorized change is
limited to setting that option to `no` through WooCommerce's authenticated
options API. `tools/launch-live-store.py` preserves the store-pages scope,
requires a verified backed-up release (or a bound reviewed concurrent-edit
receipt), checks anonymous home/shop HTML and logs out its temporary login.
It never saves the password, login cookies or REST nonce to a reusable file.

**Initial checkpoint:** the visibility option had not yet changed.
An automatic approval service error (HTTP 404) interrupted credential input
to the owned waiting launch process. The earlier attempt lacked the legacy
REST nonce variable and logged out; the helper now supports WordPress's
current API-fetch nonce/root initialization too. No setting was changed by
that unsuccessful attempt.

The later homepage-category release on 22 November confirmed that
`woocommerce_coming_soon` was already `no`, and anonymous home/shop requests
rendered the real storefront. The visibility change happened independently
of the interrupted helper. That later release preserved this public state;
see [its verification record](homepage-categories-2026-11-22.md).

The HTTPS certificate is also expired: its validity ended **26 July 2026,
14:27:52 UTC**. Chrome reports `ERR_CERT_DATE_INVALID`. A date-exempt diagnostic
retained trusted-chain and hostname verification. The public-launch setting
does not repair TLS. Certificate renewal remains pending. Real anonymous
storefront checks were completed in the later release using the diagnostic
date exception. Search-engine visibility was not changed by these releases.

## Validation completed

- Changed PHP files pass syntax checks; the launch helper compiles.
- Translation generation reports 245 translated theme entries and 76 Core
  entries. Core files were not changed.
- Source/package checks preserve the supplied ID, code, URLs and origin policy.
- `git diff --check` passes. The installable ZIP contains only the theme.

Private evidence, database snapshots and diagnostic reports are in the matching
`.local/deployments/` directory. No production secrets are part of the package.
