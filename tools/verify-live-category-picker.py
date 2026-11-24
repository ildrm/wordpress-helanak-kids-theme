"""Read-only checks of category navigation, palette and product variation pickers."""

import argparse
import getpass
import hashlib
import http.cookiejar
import json
import re
import runpy
import ssl
import urllib.request
import urllib.parse
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--directory", required=True)
    parser.add_argument("--browser-state", action="store_true")
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    directory = Path(args.directory).resolve()
    if not directory.is_relative_to((root / ".local/deployments").resolve()):
        raise RuntimeError("Private deployment directory required.")
    helpers = runpy.run_path(str(root / "tools/logout-live-verification.py"))
    version = re.search(r"Version:\s*(\S+)", (root / "wp-helanak-kids-theme/style.css").read_text(encoding="utf-8")).group(1)
    base = "https://helanak.com"
    context = ssl.create_default_context()
    context.verify_flags |= 2_097_152
    cookies = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), urllib.request.HTTPCookieProcessor(cookies), helpers["SameSiteRedirect"]())
    opener.addheaders = [("User-Agent", "HelanakCategoryPickerVerification/" + version)]
    password = getpass.getpass("WordPress administrator password: ")
    opener.open(base + "/wp-login.php", timeout=45).read()
    data = urllib.parse.urlencode({"log": "helanak_admin", "pwd": password, "wp-submit": "Log In", "testcookie": "1", "redirect_to": base + "/wp-admin/"}).encode()
    password = None
    with opener.open(urllib.request.Request(base + "/wp-login.php", data=data), timeout=60) as response:
        admin = response.read().decode("utf-8")
    if not any(cookie.name.startswith("wordpress_logged_in_") for cookie in cookies):
        raise RuntimeError("Login was not accepted.")
    links = helpers["LogoutLink"]()
    links.feed(admin)
    retained = False
    try:
        def page(path):
            with opener.open(base + path, timeout=45) as response:
                markup = response.read().decode("utf-8")
            assert "Fatal error" not in markup
            return markup

        category = page("/product-category/%d8%b1%d8%ae%d8%aa%d8%ae%d9%88%d8%a7%d8%a8-%d8%af%d9%88%d8%b1%d9%be%db%8c%da%86/?lang=fa")
        assert "helanak-category-hero" in category and "رختخواب" in category
        assert "variation-picker.js" not in category and "/selectWoo/" not in category
        (directory / "category-after.html").write_text(category, encoding="utf-8")
        accessory_path = "/product-category/%d8%a7%da%a9%d8%b3%d8%b3%d9%88%d8%b1%db%8c/"
        for lang in ("fa", "en"):
            accessory = page(accessory_path + "?lang=" + lang)
            section = re.search(r'<section class="helanak-subcategories".*?</section>', accessory, re.DOTALL)
            assert section, "Accessory subcategories missing."
            assert 'data-helanak-parent-category="53"' in section.group(0)
            assert sorted(re.findall(r'data-helanak-category-id="(\d+)"', section.group(0))) == ["54", "55", "56"]
            assert section.start() < accessory.index('class="helanak-shop-search"')
            assert ("زیردسته‌ها" if lang == "fa" else "Subcategories") in section.group(0)
            (directory / ("subcategories-after-" + lang + ".html")).write_text(accessory, encoding="utf-8")
        leaf = page("/product-category/%d8%aa%d9%84-%d9%88-%d8%aa%d9%88%d8%b1%d8%a8%d8%a7%d9%86/?lang=fa")
        assert 'class="helanak-subcategories"' not in leaf
        assert 'class="helanak-subcategories"' not in page("/shop/?lang=fa")
        for lang in ("fa", "en"):
            product = page("/?post_type=product&p=933&lang=" + lang)
            assert 'class="variations_form cart"' in product
            assert 'id="helanak-variation-picker-js"' in product and "/selectWoo/" in product
            assert "variation-picker.js?ver=" + version in product
            (directory / ("product-after-" + lang + ".html")).write_text(product, encoding="utf-8")
        for relative in ("assets/css/theme.css", "assets/js/variation-picker.js", "assets/js/theme.js"):
            with opener.open(base + "/wp-content/themes/wp-helanak-kids-theme/" + relative + "?ver=" + version, timeout=45) as response:
                actual = response.read()
            assert hashlib.sha256(actual).digest() == hashlib.sha256((root / "wp-helanak-kids-theme" / relative).read_bytes()).digest(), relative
        report = {"theme_version": version, "requested_category_rendered": True, "direct_children_before_product_results": [54, 55, 56], "empty_children_visible": True, "leaf_and_shop_have_no_empty_section": True, "variation_assets_only_on_variable_pdp": True, "persian_english_product_pages_rendered": True, "served_css_and_scripts_match": True, "commerce_write_requests": 0, "test_data_created": 0}
        (directory / "category-picker-runtime-verification.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
        if args.browser_state:
            state = {"cookies": [{"name": cookie.name, "value": cookie.value, "domain": cookie.domain, "path": cookie.path, "expires": cookie.expires or -1, "httpOnly": cookie.has_nonstandard_attr("HttpOnly"), "secure": cookie.secure, "sameSite": "Lax"} for cookie in cookies], "origins": []}
            (directory / "temporary-browser-auth.json").write_text(json.dumps(state), encoding="utf-8")
            retained = True
        print(json.dumps(report, indent=2), flush=True)
    finally:
        if links.url and not retained:
            opener.open(links.url, timeout=45).read()


if __name__ == "__main__":
    main()
