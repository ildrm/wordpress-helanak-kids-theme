# Production HTTPS repair

The live failure on 24 November was a **hostname mismatch**. A recently installed certificate was time-valid but covered only `mail.helanak.com` and `www.helanak.com`, while storefront pages and media use `helanak.com`. Normal TLS validation rejected the apex hostname. This differs from the expired certificate observed in the previous SEO audit.

DirectAdmin ACME was enabled, but `helanak.com` was explicitly present in `skipDNSNames`. The repair removed only that exclusion, preserving the existing provider, key type, DNS provider, and wildcard preference. A dry run showed one pending certificate for `helanak.com` and `*.helanak.com`, with no failed challenges, CAA errors, or rate-limit condition.

One certificate provisioning request was submitted through the hosting server's documented API. The client response timed out after 90 seconds, but the server continued the same request. No second certificate order was submitted. DirectAdmin ACME log 65 confirms DNS-01 validation succeeded and issuance completed after 193 seconds.

The installed Let's Encrypt certificate covers `helanak.com` and `*.helanak.com`, uses ECDSA P-256 and the ISRG Root X1 preferred chain, and expires **21 February 2027 at 19:45:33 UTC**. The domain's automatic renewal remains enabled with an empty exclusion list.

HTTP requests previously returned storefront content without upgrading to HTTPS. A minimal HTTPS block was added before the existing WordPress block in production `.htaccess`. It uses a permanent 308 redirect to preserve methods and queries and exempts the ACME HTTP challenge path. Existing WordPress rules were preserved byte-for-byte. The original file is backed up privately on the server at `/backups/helanak-ssl-20261124-ee0e3c/htaccess-before.txt` and in the private local audit folder.

## Validation

- Normal certificate verification passed for apex and www with both TLS 1.2 and TLS 1.3. No certificate error bypass was used for public verification.
- Fresh Chrome tests passed for homepage, shop, and the Mothercare product page in desktop and iPhone 12 emulation: six page scenarios and 172 rendered image checks, with no failed site images.
- Public HTML references across the homepage, shop, and product page yielded 314 site image/script/stylesheet URLs, all successfully requested through verified HTTPS. No HTTP image/script/stylesheet references were detected in this sample.
- Apex and www HTTPS URLs return the storefront; www redirects to the apex. HTTP apex and www now upgrade to HTTPS.
- Redirect checks also verify encoded Persian category paths and shop query parameters. The final rule uses Apache's normal URI escaping so Location headers remain ASCII-safe; percent-escape letter case is treated as equivalent during validation.
- The iPhone test used a Chrome device profile, not a physical iPhone or the Safari engine.

The work changed domain SSL settings, the managed server certificate, and the HTTPS redirect block only. It did not deploy theme/plugin code or import local data. Private evidence is under `.local/audit/ssl-2026-11-24/`; private keys and login passwords were not persisted in the audit.
