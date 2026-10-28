"""Build local installable component ZIPs without site data or configuration."""

import hashlib
import json
import re
import zipfile
from pathlib import Path


def main():
    root = Path(__file__).resolve().parents[1]
    output = root / ".local/artifacts"
    output.mkdir(parents=True, exist_ok=True)
    components = {
        "wp-helanak-kids-theme": "style.css",
        "helanak-core-plugin": "helanak-core.php",
    }
    manifest = []
    for component, header in components.items():
        source = root / component
        version = re.search(r"^\s*\*?\s*Version:\s*(\S+)", (source / header).read_text(encoding="utf-8"), re.MULTILINE).group(1)
        target = output / (component + "-" + version + ".zip")
        files = sorted(path for path in source.rglob("*") if path.is_file() and not any(part.startswith(".") or part == "__pycache__" for part in path.relative_to(source).parts))
        with zipfile.ZipFile(target, "w", compression=zipfile.ZIP_DEFLATED) as archive:
            for path in files:
                archive.write(path, path.relative_to(root).as_posix())
        with zipfile.ZipFile(target) as archive:
            failed = archive.testzip()
            if failed:
                raise RuntimeError("Archive verification failed: " + failed)
        manifest.append({
            "component": component,
            "version": version,
            "file": target.name,
            "files": len(files),
            "bytes": target.stat().st_size,
            "sha256": hashlib.sha256(target.read_bytes()).hexdigest(),
        })
    (output / "manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
    print(json.dumps(manifest, indent=2))


if __name__ == "__main__":
    main()
