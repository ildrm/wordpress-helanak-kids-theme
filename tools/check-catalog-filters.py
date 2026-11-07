"""Check actual local product-query results for combined catalogue filters."""

import json
import re
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    root = Path(__file__).resolve().parents[1]
    directory = root / ".local/audit/seven-issues"
    fixture = json.loads((directory / "catalog-fixtures.json").read_text(encoding="utf-8"))
    ids = fixture["ids"]
    slugs = fixture["slugs"]
    base = {"post_type": "product", "helanak_category[]": slugs["category"]}
    checks = []

    def check(*, name, fields, expected):
        url = "http://localhost/wp/?" + urllib.parse.urlencode({**base, **fields}, doseq=True)
        with urllib.request.urlopen(url, timeout=40) as response:
            page = response.read().decode("utf-8")
            assert response.status == 200 and "Fatal error" not in page and "Warning:" not in page, name
        actual = sorted(set(int(identifier) for identifier in re.findall(r'data-helanak-product-id="(\d+)"', page)))
        assert actual == sorted(ids[key] for key in expected), (name, actual, expected)
        checks.append(name)
        print("PASS " + name, flush=True)

    check(name="Category scope excludes hidden products", fields={}, expected=["alpha", "beta", "gamma"])
    check(name="Brand facet", fields={"helanak_brand[]": slugs["brand_b"]}, expected=["beta"])
    check(name="Age facet", fields={"helanak_age[]": slugs["age_b"]}, expected=["gamma"])
    check(name="Highlight facet", fields={"helanak_badge[]": slugs["badge"]}, expected=["alpha", "gamma"])
    check(name="Unicode global colour facet", fields={fixture["attribute_key"] + "[]": slugs["cyan"]}, expected=["alpha", "beta"])
    check(name="Own facet accepts raw Persian slugs", fields={fixture["attribute_key"] + "[]": urllib.parse.unquote(slugs["cyan"])}, expected=["alpha", "beta"])
    check(name="OR within a colour facet", fields={fixture["attribute_key"] + "[]": [slugs["cyan"], slugs["magenta"]]}, expected=["alpha", "beta", "gamma"])
    check(name="Stock and colour combine with AND", fields={fixture["attribute_key"] + "[]": slugs["cyan"], "helanak_stock": "instock"}, expected=["alpha"])
    check(name="Sale includes a discounted variable parent", fields={"helanak_sale": "1"}, expected=["alpha", "gamma"])
    check(name="Wishlist and sale intersect", fields={"helanak_sale": "1", "helanak_wishlist": str(ids["beta"]) + "," + str(ids["gamma"])}, expected=["gamma"])
    check(name="Empty wishlist remains empty", fields={"helanak_wishlist": "0"}, expected=[])
    check(name="Native price range", fields={"min_price": "70", "max_price": "110"}, expected=["alpha"])
    check(name="Product-name search excludes description-only matches", fields={"s": "Helanak Catalog QA Alpha"}, expected=["alpha"])
    check(name="Keyword plus contradictory brand yields no results", fields={"s": "Helanak Catalog QA Alpha", "helanak_brand[]": slugs["brand_b"]}, expected=[])
    check(name="Nested malicious facet values are ignored safely", fields={"helanak_age[0][nested]": "bad", fixture["attribute_key"] + "[0][nested]": "bad"}, expected=["alpha", "beta", "gamma"])
    check(name="Native encoded Persian attribute URL compatibility", fields={"filter_رنگ": slugs["magenta"], "query_type_رنگ": "or"}, expected=["gamma"])
    check(name="Native raw Persian attribute URL compatibility", fields={"filter_رنگ": urllib.parse.unquote(slugs["magenta"]), "query_type_رنگ": "or"}, expected=["gamma"])
    check(name="Native AND query type is preserved", fields={"filter_رنگ": slugs["cyan"] + "," + slugs["magenta"], "query_type_رنگ": "and"}, expected=[])
    check(name="Blank keyword and price controls preserve results", fields={"s": "", "min_price": "", "max_price": ""}, expected=["alpha", "beta", "gamma"])
    check(name="Malformed keyword and price values do not crash", fields={"s[nested]": "bad", "min_price[nested]": "bad", "max_price[nested]": "bad"}, expected=["alpha", "beta", "gamma"])
    (directory / "catalog-filter-checks.json").write_text(json.dumps({"passed": checks}, indent=2), encoding="utf-8")


if __name__ == "__main__":
    main()
