"""Exercise anonymous local AJAX endpoints and malformed archive requests."""

import json
import re
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    base = "http://localhost/wp/"
    page = urllib.request.urlopen(base, timeout=30).read().decode("utf-8")
    settings = json.loads(re.search(r"var helanakSettings\s*=\s*(\{[^\n]+\});", page).group(1))
    assert urllib.parse.urlsplit(settings["ajaxUrl"]).hostname == "localhost"
    fixtures = json.loads(Path(".local/audit/qa-products.json").read_text())
    checks = []

    def post(*, action, nonce, fields):
        data = urllib.parse.urlencode({"action": action, "nonce": nonce, **fields}).encode()
        try:
            with urllib.request.urlopen(urllib.request.Request(settings["ajaxUrl"], data=data), timeout=30) as response:
                return response.status, json.loads(response.read())
        except urllib.error.HTTPError as error:
            return error.code, json.loads(error.read())

    for kind in ("protected", "hidden"):
        status, body = post(action="helanak_quick_view", nonce=settings["quickViewNonce"], fields={"product_id": fixtures[kind]})
        assert status == 404 and not body["success"], (kind, status, body)
        checks.append("Quick view denies " + kind + " products")
    status, body = post(action="helanak_quick_view", nonce="invalid", fields={"product_id": fixtures["simple"]})
    assert status == 403, status
    checks.append("Quick view rejects an invalid nonce")
    status, body = post(action="helanak_quick_view", nonce=settings["quickViewNonce"], fields={"product_id": fixtures["variable"]})
    assert status == 200 and body["success"] and "variations_form" in body["data"]["html"]
    checks.append("Public variable-product quick view returns its variation form")
    status, body = post(action="helanak_compare", nonce=settings["compareNonce"], fields={"product_ids": ",".join(str(i) for i in [*fixtures["public"][:2], fixtures["protected"]])})
    assert status == 200 and body["success"] and "HELANAK_QA_RESTRICTED_MARKER" not in body["data"]["html"]
    checks.append("Compare omits a password-protected product")
    status, body = post(action="helanak_compare", nonce=settings["compareNonce"], fields={"product_ids": str(fixtures["simple"]) + "," + str(fixtures["variable"])})
    assert status == 200 and body["success"]
    html = body["data"]["html"]
    assert html.count("ajax_add_to_cart") == 1, html
    checks.append("Compare uses AJAX cart behavior only for its simple purchasable product")
    for query in ("?post_type=product&helanak_wishlist=0", "?post_type=product&helanak_age[0][nested]=bad&helanak_color[0][nested]=bad"):
        with urllib.request.urlopen(base + query, timeout=30) as response:
            html = response.read().decode("utf-8")
            assert response.status == 200 and "Fatal error" not in html
            if "wishlist" in query:
                assert 'class="helanak-product-card' not in html
    checks.append("Empty wishlist and malformed nested filters render safely")
    Path(".local/audit/http-checks.json").write_text(json.dumps(checks, indent=2))
    print(json.dumps({"passed": checks}, indent=2))


if __name__ == "__main__":
    main()
