"""Read or disable Helanak's WooCommerce coming-soon mode through its admin API."""

import argparse
import getpass
import hashlib
import http.cookiejar
import json
import re
import runpy
import ssl
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--directory", required=True)
    parser.add_argument("--go-live", action="store_true", help="Set only woocommerce_coming_soon to no.")
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    directory = Path(args.directory).resolve()
    if not directory.is_relative_to((root / ".local/deployments").resolve()):
        raise RuntimeError("Private deployment directory required.")
    state = json.loads((directory / "state.json").read_text(encoding="utf-8"))
    if state["before"]["options"]["home"] != "'https://helanak.com'":
        raise RuntimeError("Unexpected production site.")
    if args.go_live and state["phase"] != "verified":
        if state["phase"] != "verified-with-concurrent-content-edits":
            raise RuntimeError("Verify the backed-up theme release before making the site public.")
        review = json.loads((directory / "concurrent-data-review.json").read_text(encoding="utf-8"))
        if review.get("concurrent_changes_reviewed") is not True or review.get("verification_report_sha256") != hashlib.sha256((directory / "verification.json").read_bytes()).hexdigest():
            raise RuntimeError("Concurrent content changes require a matching reviewed data audit.")
    helpers = runpy.run_path(str(root / "tools/logout-live-verification.py"))
    context = ssl.create_default_context()
    # Match the established hosting client: keep hostname and trusted-chain checks.
    context.verify_flags |= 2_097_152
    cookies = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), urllib.request.HTTPCookieProcessor(cookies), helpers["SameSiteRedirect"]())
    opener.addheaders = [("User-Agent", "HelanakPublicLaunch/0.7.6")]
    anonymous = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), helpers["SameSiteRedirect"]())
    base = "https://helanak.com"
    password = getpass.getpass("WordPress administrator password: ")
    opener.open(base + "/wp-login.php", timeout=45).read()
    data = urllib.parse.urlencode({"log": "helanak_admin", "pwd": password, "wp-submit": "Log In", "testcookie": "1", "redirect_to": base + "/wp-admin/"}).encode()
    password = None
    with opener.open(urllib.request.Request(base + "/wp-login.php", data=data), timeout=60) as response:
        admin = response.read().decode("utf-8")
    if not any(cookie.name.startswith("wordpress_logged_in_") for cookie in cookies):
        raise RuntimeError("Administrator login was not accepted.")
    logout = helpers["LogoutLink"]()
    logout.feed(admin)
    try:
        with opener.open(base + "/wp-admin/admin.php?page=wc-settings&tab=site-visibility", timeout=60) as response:
            settings = response.read().decode("utf-8")
        match = re.search(r"var\s+wpApiSettings\s*=\s*(\{.*?\});", settings, re.DOTALL)
        if match:
            api = json.loads(match.group(1))
        else:
            nonce = re.search(r'wp\.apiFetch\.createNonceMiddleware\(\s*("[a-zA-Z0-9]+")\s*\)', settings)
            api_root = re.search(r'wp\.apiFetch\.createRootURLMiddleware\(\s*("[^"\n]+")\s*\)', settings)
            if not nonce or not api_root:
                (directory / "visibility-settings-diagnostic.html").write_text(settings, encoding="utf-8")
                raise RuntimeError("WordPress REST authentication settings not found; visibility was not changed.")
            api = {"nonce": json.loads(nonce.group(1)), "root": json.loads(api_root.group(1))}
        if api["root"].rstrip("/") != base + "/wp-json":
            raise RuntimeError("Unexpected WordPress API root.")
        endpoint = api["root"] + "wc-admin/options"
        query = "?options=woocommerce_coming_soon,woocommerce_store_pages_only"

        def options():
            request = urllib.request.Request(endpoint + query, headers={"X-WP-Nonce": api["nonce"]})
            with opener.open(request, timeout=45) as response:
                return json.load(response)

        before = options()
        if before.get("woocommerce_coming_soon") not in ("yes", "no"):
            raise RuntimeError("Unexpected coming-soon setting.")
        (directory / "visibility-before.json").write_text(json.dumps(before, indent=2), encoding="utf-8")
        if not args.go_live:
            print(json.dumps({"coming_soon": before["woocommerce_coming_soon"], "changed": False}), flush=True)
            return
        if before["woocommerce_coming_soon"] == "yes":
            request = urllib.request.Request(endpoint, method="POST", data=json.dumps({"woocommerce_coming_soon": "no"}).encode(), headers={"X-WP-Nonce": api["nonce"], "Content-Type": "application/json"})
            with opener.open(request, timeout=45) as response:
                result = json.load(response)
            if result.get("woocommerce_coming_soon") is not True:
                raise RuntimeError("Visibility update was not confirmed; inspect the saved state before retrying.")
        after = options()
        assert after["woocommerce_coming_soon"] == "no"
        assert after.get("woocommerce_store_pages_only") == before.get("woocommerce_store_pages_only")
        (directory / "visibility-after.json").write_text(json.dumps(after, indent=2), encoding="utf-8")
        checked = []
        for label, path in (("home", "/"), ("shop", "/shop/")):
            with anonymous.open(base + path, timeout=60) as response:
                markup = response.read().decode("utf-8")
                assert response.status == 200
            (directory / ("public-" + label + "-after.html")).write_text(markup, encoding="utf-8")
            assert 'class="helanak-site-footer"' in markup and 'class="helanak-enamad"' in markup, "Public storefront missing: " + label
            assert "Fatal error" not in markup
            checked.append(path)
        report = {"coming_soon_before": before["woocommerce_coming_soon"], "coming_soon_after": after["woocommerce_coming_soon"], "changed_option": "woocommerce_coming_soon", "store_pages_scope_unchanged": True, "anonymous_public_pages_checked": checked, "enamad_footer_present": True, "test_data_imported": False}
        (directory / "public-launch-verification.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
        print(json.dumps(report, indent=2), flush=True)
    finally:
        if not logout.url:
            raise RuntimeError("Authenticated logout link missing.")
        opener.open(logout.url, timeout=45).read()
        with opener.open(base + "/wp-admin/", timeout=45) as response:
            assert urllib.parse.urlsplit(response.url).path == "/wp-login.php", "Verification login is still active."
        (directory / "launch-login-cleanup.json").write_text(json.dumps({"owned_session_logged_out": True, "authentication_files_created": False}), encoding="utf-8")


if __name__ == "__main__":
    main()
