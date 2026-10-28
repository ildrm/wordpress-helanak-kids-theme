"""Summarize complete final local browser evidence, treating tool errors as failures."""

import json
from pathlib import Path


def main():
    base = Path(__file__).resolve().parents[1] / ".local/audit"
    failures = []
    sets = []
    total = 0
    for label in ("fa-desktop", "fa-mobile", "en-desktop", "en-mobile"):
        directory = base / ("final-" + label)
        summary = directory / "summary.json"
        if not summary.is_file():
            failures.append({"set": label, "error": "Missing completed capture summary."})
            continue
        pages = json.loads(summary.read_text(encoding="utf-8"))
        if len(pages) != 11:
            failures.append({"set": label, "error": "Expected eleven page navigations."})
        for page in pages:
            total += 1
            errors_file = directory / (page["page"] + "-errors.json")
            errors = json.loads(errors_file.read_text(encoding="utf-8"))
            audit = json.loads((directory / (page["page"] + "-a11y.json")).read_text(encoding="utf-8"))
            dimensions = page.get("dimensions")
            failed = (
                page["navigation_exit"] != 0
                or page["screenshot_exit"] != 0
                or page.get("audit_exit") != 0
                or page.get("audit_error")
                or page["violations"]
                or not audit.get("success")
                or not audit.get("data")
                or (audit.get("data") or {}).get("violations")
                or not dimensions
                or dimensions["scrollWidth"] > dimensions["viewport"]
                or not errors.get("success")
                or (errors.get("data") or {}).get("errors")
            )
            if failed:
                failures.append({"set": label, "page": page, "audit_error": audit.get("error"), "browser_errors": (errors.get("data") or {}).get("errors")})
        sets.append({"set": label, "navigations": len(pages)})
    report = {
        "sets": sets,
        "navigations": total,
        "failures": failures,
        "note": "Checkout with an empty cart redirects to cart. Populated cart/checkout are checked separately. Automated results do not establish full WCAG conformance.",
    }
    (base / "final-browser-summary.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    print(json.dumps(report, indent=2))
    raise SystemExit(bool(failures))


if __name__ == "__main__":
    main()
