"""Lint the WordPress components without requiring a build system."""

import json
import subprocess
from pathlib import Path


def main():
    files = [p for component in ("wp-helanak-kids-theme", "helanak-core-plugin") for p in Path(component).rglob("*.php")]
    failures = []
    for path in files:
        result = subprocess.run(["C:/xampp/php/php.exe", "-l", str(path)], capture_output=True, text=True, encoding="utf-8", errors="replace", check=False)
        if result.returncode:
            failures.append({"file": str(path), "output": result.stdout + result.stderr})
    scripts = list(Path("wp-helanak-kids-theme/assets/js").glob("*.js"))
    for path in scripts:
        result = subprocess.run(["node", "--check", str(path)], capture_output=True, text=True, encoding="utf-8", errors="replace", check=False)
        if result.returncode:
            failures.append({"file": str(path), "output": result.stdout + result.stderr})
    print(json.dumps({"php_files": len(files), "javascript_files": len(scripts), "failures": failures}, indent=2))
    raise SystemExit(bool(failures))


if __name__ == "__main__":
    main()
