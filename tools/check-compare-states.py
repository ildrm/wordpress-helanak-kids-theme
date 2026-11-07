"""Check comparison empty/single states and anonymous visibility protections locally."""

import json
import re
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    directory = Path(__file__).resolve().parents[1] / ".local/audit/seven-issues"
    fixture = json.loads((directory / "catalog-fixtures.json").read_text(encoding="utf-8"))
    ids = fixture["ids"]
    page = urllib.request.urlopen("http://localhost/wp/?lang=fa", timeout=40).read().decode("utf-8")
    settings = json.loads(re.search(r"var helanakSettings\s*=\s*(\{[^\n]+\});", page).group(1))
    assert urllib.parse.urlsplit(settings["ajaxUrl"]).hostname == "localhost"
    checks = []

    def check(*, name, value, expected):
        data = urllib.parse.urlencode({"action": "helanak_compare", "nonce": settings["compareNonce"], "product_ids": value}).encode()
        with urllib.request.urlopen(urllib.request.Request(settings["ajaxUrl"], data=data), timeout=40) as response:
            result = json.load(response)
        assert result["success"], name
        markup = result["data"]["html"]
        actual = re.search(r'data-helanak-compare-ids="([^"]*)"', markup).group(1)
        assert actual == ",".join(str(ids[key]) for key in expected), (name, actual)
        assert markup.count("data-helanak-compare-remove") == len(expected), name
        if not expected:
            assert "data-helanak-compare-close" in markup, name
        checks.append(name)

    check(name="Two products have independent removal controls", value=str(ids["alpha"]) + "," + str(ids["gamma"]), expected=["alpha", "gamma"])
    check(name="Single product remains removable", value=str(ids["alpha"]), expected=["alpha"])
    check(name="Empty list offers continue shopping", value="", expected=[])
    check(name="Hidden and stale IDs are omitted", value=str(ids["hidden"]) + ",999999999," + str(ids["alpha"]), expected=["alpha"])
    check(name="Duplicate product is returned once", value=str(ids["alpha"]) + "," + str(ids["alpha"]), expected=["alpha"])
    data = urllib.parse.urlencode({"action": "helanak_compare", "nonce": "invalid", "product_ids": str(ids["alpha"])}).encode()
    try:
        urllib.request.urlopen(urllib.request.Request(settings["ajaxUrl"], data=data), timeout=40)
        raise AssertionError("Invalid nonce was accepted")
    except urllib.error.HTTPError as error:
        assert error.code == 403, error.code
    checks.append("Anonymous invalid nonce is rejected")
    (directory / "compare-endpoint-checks.json").write_text(json.dumps({"passed": checks}, indent=2), encoding="utf-8")
    print(json.dumps({"passed": checks}, indent=2), flush=True)


if __name__ == "__main__":
    main()
