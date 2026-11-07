"""Read-only authenticated check of the online Quick Edit price extension."""

import argparse
import getpass
import hashlib
import html
import http.cookiejar
import json
import re
import ssl
import urllib.parse
import urllib.request
from pathlib import Path


class SameSiteRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        if urllib.parse.urlsplit(newurl).hostname != "helanak.com":
            raise RuntimeError("Unexpected off-site authentication redirect.")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--directory", required=True)
    parser.add_argument("--product", type=int, default=143)
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    version = re.search(r"Version:\s*(\S+)", (root / "helanak-core-plugin/helanak-core.php").read_text(encoding="utf-8")).group(1)
    output = Path(args.directory).resolve()
    if not output.is_relative_to((root / ".local/deployments").resolve()):
        raise RuntimeError("A private deployment directory is required.")
    password = getpass.getpass("WordPress administrator password: ")
    context = ssl.create_default_context()
    context.verify_flags |= 2_097_152
    cookies = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), urllib.request.HTTPCookieProcessor(cookies), SameSiteRedirect())
    opener.addheaders = [("User-Agent", "HelanakQuickEditVerification/" + version)]
    base = "https://helanak.com"
    opener.open(base + "/wp-login.php", timeout=45).read()
    request = urllib.request.Request(base + "/wp-login.php", data=urllib.parse.urlencode({
        "log": "helanak_admin", "pwd": password, "wp-submit": "Log In", "testcookie": "1",
        "redirect_to": base + "/wp-admin/edit.php?post_type=product",
    }).encode())
    password = None
    with opener.open(request, timeout=60) as response:
        page = response.read().decode("utf-8")
    if not any(cookie.name.startswith("wordpress_logged_in_") for cookie in cookies):
        raise RuntimeError("The supplied WordPress administrator login was not accepted.")
    (output / "admin-products-after.html").write_text(page, encoding="utf-8")
    logout = re.search(r'''href=["']([^"']*wp-login\.php\?action=logout[^"']*)["']''', page)
    try:
        match = re.search(r"var helanakQuickPrices\s*=\s*(\{[^\n]+\});", page)
        if not match or 'class="helanak-quick-prices"' not in page:
            raise RuntimeError("The online administrator page did not render the new price extension.")
        settings = json.loads(match.group(1))
        for file in ("assets/js/quick-edit-prices.js", "assets/css/quick-edit-prices.css"):
            url = base + "/wp-content/plugins/helanak-core-plugin/" + file + "?ver=" + version
            with opener.open(url, timeout=45) as response:
                data = response.read()
            if hashlib.sha256(data).digest() != hashlib.sha256((root / "helanak-core-plugin" / file).read_bytes()).digest():
                raise RuntimeError("A live Quick Edit asset does not match the release: " + file)
        request = urllib.request.Request(base + "/wp-admin/admin-ajax.php", data=urllib.parse.urlencode({
            "action": "helanak_quick_edit_prices", "product_id": args.product, "nonce": settings["nonce"],
        }).encode())
        with opener.open(request, timeout=45) as response:
            prices = json.load(response)
        if not prices.get("success") or not prices.get("data", {}).get("rows"):
            raise RuntimeError("The live read-only variation-price request did not succeed.")
        report = {
            "admin_price_panel_rendered": True,
            "live_js_and_css_match_release": True,
            "read_only_price_endpoint_succeeded": True,
            "product_id": args.product,
            "variation_rows_loaded": len(prices["data"]["rows"]),
            "price_save_requests_sent": 0,
            "test_products_created": 0,
        }
        (output / "quick-edit-runtime-verification.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
        print(json.dumps(report, indent=2), flush=True)
    finally:
        if logout:
            with opener.open(html.unescape(logout.group(1)), timeout=45) as response:
                response.read()


if __name__ == "__main__":
    main()
