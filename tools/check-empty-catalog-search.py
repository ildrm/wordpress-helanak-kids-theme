"""Check that filter submissions with an empty keyword remain shop visits."""

import html
import json
import re
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    checks = []
    for lang in ("fa", "en"):
        baseline_url = "http://localhost/wp/?" + urllib.parse.urlencode({"post_type": "product", "lang": lang})
        baseline = urllib.request.urlopen(baseline_url, timeout=40).read().decode("utf-8")
        shop_title = html.unescape(re.search(r'<h1[^>]*>(.*?)</h1>', baseline, re.DOTALL).group(1)).strip()
        for keyword in ("", "   ", "سرویس"):
            url = "http://localhost/wp/?" + urllib.parse.urlencode({"post_type": "product", "lang": lang, "s": keyword, "min_price": "", "max_price": ""})
            with urllib.request.urlopen(url, timeout=40) as response:
                markup = response.read().decode("utf-8")
            title = html.unescape(re.search(r'<h1[^>]*>(.*?)</h1>', markup, re.DOTALL).group(1)).strip()
            if keyword.strip():
                assert "سرویس" in title and 'value="relevance"' in markup, title
            else:
                assert title == shop_title, title
                assert 'value="relevance"' not in markup
                assert 'data-helanak-product-id="' in markup
            checks.append({"language": lang, "keyword": keyword, "title": title})
    target = Path(__file__).resolve().parents[1] / ".local/audit/seven-issues/empty-search-checks.json"
    target.write_text(json.dumps({"passed": checks}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({"passed": checks}, ensure_ascii=False, indent=2), flush=True)


if __name__ == "__main__":
    main()
