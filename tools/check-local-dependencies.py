"""Check missing-dependency storefront behavior, always restoring local plugin state."""

import json
import subprocess
import urllib.request
from pathlib import Path


def main():
    root = Path(__file__).resolve().parents[1]
    wrapper = root / "tools/wp-local.ps1"

    def wp(arguments):
        result = subprocess.run(["powershell", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", str(wrapper), *arguments], capture_output=True, text=True, encoding="utf-8", errors="replace", check=False)
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        return result.stdout

    raw = wp(["--skip-plugins", "--skip-themes", "option", "get", "active_plugins", "--format=json"])
    original = json.loads(raw[raw.index("["):])
    (root / ".local/audit/plugins-before-dependency-check.json").write_text(json.dumps(original), encoding="utf-8")
    checks = []
    try:
        for plugin in ("woocommerce", "helanak-core-plugin"):
            wp(["plugin", "deactivate", plugin])
            for path in ("/", "/?page_id=410", "/?page_id=409"):
                with urllib.request.urlopen("http://localhost/wp" + path, timeout=30) as response:
                    html = response.read().decode("utf-8")
                    assert response.status == 200 and "Fatal error" not in html and 'id="helanak-main"' in html
            wp(["plugin", "activate", plugin])
            checks.append({"missing_plugin": plugin, "home_contact_offers": "HTTP 200 without fatal errors"})
            print(json.dumps(checks[-1]), flush=True)
    finally:
        print(wp(["--skip-plugins", "--skip-themes", "eval-file", str(root / "tools/restore-local-plugins.php")]), flush=True)
    (root / ".local/audit/dependency-checks.json").write_text(json.dumps(checks, indent=2), encoding="utf-8")


if __name__ == "__main__":
    main()
