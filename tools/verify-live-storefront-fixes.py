"""Verify the storefront release with read-only authenticated requests."""

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
    parser.add_argument("--browser-state", action="store_true", help="Retain a temporary login for the named QA browser; log out after browser checks.")
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    theme_version = re.search(r"Version:\s*(\S+)", (root / "wp-helanak-kids-theme/style.css").read_text(encoding="utf-8")).group(1)
    core_version = re.search(r"Version:\s*(\S+)", (root / "helanak-core-plugin/helanak-core.php").read_text(encoding="utf-8")).group(1)
    output = Path(args.directory).resolve()
    if not output.is_relative_to((root / ".local/deployments").resolve()):
        raise RuntimeError("A private deployment directory is required.")
    base = "https://helanak.com"
    password = getpass.getpass("WordPress administrator password: ")
    context = ssl.create_default_context()
    context.verify_flags |= 2_097_152
    cookies = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), urllib.request.HTTPCookieProcessor(cookies), SameSiteRedirect())
    opener.addheaders = [("User-Agent", "HelanakStorefrontVerification/" + theme_version)]
    opener.open(base + "/wp-login.php", timeout=45).read()
    request = urllib.request.Request(base + "/wp-login.php", data=urllib.parse.urlencode({
        "log": "helanak_admin", "pwd": password, "wp-submit": "Log In", "testcookie": "1",
        "redirect_to": base + "/wp-admin/",
    }).encode())
    password = None
    with opener.open(request, timeout=60) as response:
        admin = response.read().decode("utf-8")
    if not any(cookie.name.startswith("wordpress_logged_in_") for cookie in cookies):
        raise RuntimeError("Administrator login was not accepted.")
    logout = re.search(r'''href=["']([^"']*wp-login\.php\?action=logout[^"']*)["']''', admin)
    retained = False
    checks = []

    def page(path):
        with opener.open(base + path, timeout=60) as response:
            result = response.read().decode("utf-8")
        if "Fatal error" in result or not re.search(r"<main\b", result):
            raise RuntimeError("Storefront did not render: " + path)
        return result

    def compare(*, ids, nonce):
        data = urllib.parse.urlencode({"action": "helanak_compare", "nonce": nonce, "product_ids": ids}).encode()
        with opener.open(urllib.request.Request(base + "/wp-admin/admin-ajax.php?lang=fa", data=data), timeout=45) as response:
            result = json.load(response)
        if not result.get("success"):
            raise RuntimeError("Read-only comparison failed.")
        return result["data"]["html"]

    try:
        home = page("/?lang=fa")
        (output / "storefront-home-fa.html").write_text(home, encoding="utf-8")
        assert "خرید بر اساس دسته‌بندی" in home and "خرید بر اساس دنیا" not in home
        settings = json.loads(re.search(r"var helanakSettings\s*=\s*(\{[^\n]+\});", home).group(1))
        assert settings["compareCountMany"] == "مقایسه (%d محصول)"
        ids = list(dict.fromkeys(re.findall(r'data-helanak-compare-toggle[^>]*data-product-id="(\d+)"', home)))
        if len(ids) < 2:
            raise RuntimeError("Two published product cards are required for the read-only comparison check.")
        pair = compare(ids=",".join(ids[:2]), nonce=settings["compareNonce"])
        assert pair.count("data-helanak-compare-remove") == 2
        single = compare(ids=ids[0], nonce=settings["compareNonce"])
        assert single.count("data-helanak-compare-remove") == 1
        empty = compare(ids="", nonce=settings["compareNonce"])
        assert 'data-helanak-compare-ids=""' in empty and "data-helanak-compare-close" in empty
        stale = compare(ids="999999999", nonce=settings["compareNonce"])
        assert 'data-helanak-compare-ids=""' in stale
        checks.extend(["Persian category heading and comparison count", "Comparison two, one, empty and stale selections"])

        shop = page("/shop/?lang=fa")
        (output / "storefront-shop-fa.html").write_text(shop, encoding="utf-8")
        for control in ("helanak-shop-search", "helanak_category[]", "helanak_brand[]", "helanak_age[]", "helanak_badge[]", "helanak_attr_رنگ[]", "helanak_attr_سایز[]", "helanak_attr_تکه-مورد-نظر[]", "helanak_stock", "helanak_sale", "min_price", "max_price"):
            assert control in shop, control
        assert shop.count('name="post_type"') >= 1
        checks.append("Live shop exposes search and every populated catalogue facet")
        assert 'class="helanak-filters__scroll"' in shop
        assert 'class="helanak-filter-group helanak-filter-price"' in shop
        assert 'class="helanak-filter-group helanak-filter-availability"' in shop
        active_panels = page("/shop/?lang=fa&min_price=1&helanak_stock=instock")
        for group in ("price", "availability"):
            assert re.search(r'<details class="helanak-filter-group helanak-filter-' + group + r'"\s+open', active_panels)
        checks.append("Filter scroll region and price/availability accordions render; active groups reopen")
        blank_search = page("/shop/?" + urllib.parse.urlencode({"lang": "fa", "post_type": "product", "s": "", "min_price": "", "max_price": ""}))
        assert re.search(r'<h1[^>]*>\s*فروشگاه\s*</h1>', blank_search)
        assert 'value="relevance"' not in blank_search
        checks.append("Blank keyword submits as the shop, without empty-search heading or relevance sorting")

        for lang in ("fa", "en"):
            product = page("/?post_type=product&p=933&lang=" + lang)
            assert "woocommerce-product-gallery" in product and 'class="variations_form cart"' in product
            assert "photo_2026-11-01_18-23-14" in product
            assert 'ver=' + theme_version in product
            (output / ("storefront-product-" + lang + ".html")).write_text(product, encoding="utf-8")
        checks.append("Actual product933 renders native gallery and variations in Persian/English")

        searched = page("/shop/?" + urllib.parse.urlencode({"lang": "fa", "post_type": "product", "s": "قصه حیوانات", "min_price": "", "max_price": ""}))
        assert "helanak-shop-search" in searched and 'data-helanak-product-id="' in searched
        checks.append("Product-name search with submitted blank price controls returns live products")
        impossible = page("/shop/?" + urllib.parse.urlencode({"lang": "fa", "post_type": "product", "s": "helanak-no-such-product-7f021a"}))
        assert "helanak-empty-state" in impossible and 'data-helanak-product-id="' not in impossible
        checks.append("Unmatched search retains a usable empty state")

        def product_ids(markup):
            return sorted(set(re.findall(r'data-helanak-product-id="(\d+)"', markup)))

        def filtered(fields):
            return product_ids(page("/shop/?" + urllib.parse.urlencode({"lang": "fa", **fields})))

        gray_slug = urllib.parse.quote("طوسی", safe="").lower()
        gray_ids = filtered({"filter_رنگ": "طوسی", "query_type_رنگ": "or"})
        assert "933" in gray_ids
        for fields in ({"filter_رنگ": gray_slug}, {"helanak_attr_رنگ[]": "طوسی"}, {"helanak_attr_رنگ[]": gray_slug}):
            assert filtered(fields) == gray_ids, fields
        native_size_piece = filtered({"filter_سایز": "۱", "filter_تکه-مورد-نظر": "شلوار"})
        own_size_piece = filtered({"helanak_attr_سایز[]": urllib.parse.quote("۱", safe="").lower(), "helanak_attr_تکه-مورد-نظر[]": urllib.parse.quote("شلوار", safe="").lower()})
        assert native_size_piece and own_size_piece == native_size_piece
        checks.append("Real Persian color raw/encoded/native/own URLs agree and include product933")
        checks.append("Real Persian size plus clothing-piece filters agree with native WooCommerce")

        for file in ("assets/css/theme.css", "assets/js/theme.js", "assets/js/compare.js"):
            with opener.open(base + "/wp-content/themes/wp-helanak-kids-theme/" + file + "?ver=" + theme_version, timeout=45) as response:
                data = response.read()
            assert hashlib.sha256(data).digest() == hashlib.sha256((root / "wp-helanak-kids-theme" / file).read_bytes()).digest(), file
        checks.append("Served CSS and interaction scripts match the release byte for byte")
        report = {"checks": checks, "real_gray_product_count": len(gray_ids), "real_size_piece_product_count": len(native_size_piece), "test_products_created": 0, "commerce_write_requests": 0, "theme_version": theme_version, "core_version": core_version}
        (output / "storefront-runtime-verification.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
        if args.browser_state:
            state = {"cookies": [{"name": cookie.name, "value": cookie.value, "domain": cookie.domain, "path": cookie.path, "expires": cookie.expires or -1, "httpOnly": cookie.has_nonstandard_attr("HttpOnly"), "secure": cookie.secure, "sameSite": "Lax"} for cookie in cookies], "origins": []}
            (output / "temporary-browser-auth.json").write_text(json.dumps(state), encoding="utf-8")
            (output / "temporary-browser-logout.txt").write_text(html.unescape(logout.group(1)) if logout else "", encoding="utf-8")
            retained = True
        print(json.dumps(report, indent=2), flush=True)
    finally:
        if logout and not retained:
            with opener.open(html.unescape(logout.group(1)), timeout=45) as response:
                response.read()


if __name__ == "__main__":
    main()
