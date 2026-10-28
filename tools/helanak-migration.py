"""Read DirectAdmin inventories and download migration artifacts without logging secrets."""

import argparse
import base64
import getpass
import json
import os
import re
import ssl
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=["inventory", "get", "clone"])
    parser.add_argument("--endpoint", default="")
    parser.add_argument("--output", default="")
    parser.add_argument("--allow-certificate-date-error", action="store_true")
    args = parser.parse_args()
    password = os.environ.get("HELANAK_DA_PASSWORD") or getpass.getpass("DirectAdmin password: ")
    auth = base64.b64encode(f"helanakc:{password}".encode()).decode()
    context = ssl._create_unverified_context() if args.allow_certificate_date_error else ssl.create_default_context()
    root = Path(__file__).resolve().parents[1] / ".local" / "migration"
    root.mkdir(parents=True, exist_ok=True)
    endpoints = [args.endpoint] if args.action == "get" else [
        "CMD_API_SHOW_DOMAINS",
        "CMD_API_DATABASES",
        "CMD_API_FILE_MANAGER?path=/backups",
        "CMD_API_FILE_MANAGER?path=/domains/helanak.com/public_html",
    ]
    if args.action == "clone":
        endpoints = [
            "api/filemanager/download?path=/domains/helanak.com/public_html/wp-config.php",
            "api/db-manage/databases/helanakc_db/export",
            "api/db-manage/databases/helanakc_wpdb/export",
            "api/filemanager/download-archive?path=/domains/helanak.com/public_html&type=zip",
        ]
    clone_names = ["live-wp-config.php", "helanakc_db.sql", "helanakc_wpdb.sql", "live-public-html.zip"]
    for index, endpoint in enumerate(endpoints):
        request = urllib.request.Request(
            f"https://di101.limoodns.com:2222/{endpoint}",
            headers={"Authorization": f"Basic {auth}", "User-Agent": "HelanakLocalMigration/1.0"},
        )
        try:
            with urllib.request.urlopen(request, context=context, timeout=120) as response:
                name = clone_names[index] if args.action == "clone" else f"inventory-{index}.txt"
                output = Path(args.output) if args.output else root / name
                partial = output.with_suffix(output.suffix + ".part")
                total = 0
                with partial.open("wb") as target:
                    while chunk := response.read(1_048_576):
                        target.write(chunk)
                        total += len(chunk)
                        if total % 67_108_864 < len(chunk):
                            print(json.dumps({"file": output.name, "downloaded_mb": round(total / 1_048_576)}), flush=True)
                partial.replace(output)
                print(json.dumps({"endpoint": endpoint, "status": response.status, "bytes": output.stat().st_size, "saved": str(output)}))
                if args.action == "clone" and index == 0:
                    config = output.read_text(encoding="utf-8")
                    match = re.search(r"define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)", config)
                    print(json.dumps({"wordpress_database": match.group(1) if match else "unknown"}), flush=True)
                if args.action == "inventory":
                    body = output.read_text(encoding="utf-8", errors="replace")
                    data = urllib.parse.parse_qs(body)
                    print(json.dumps(data, ensure_ascii=False)[:8_000])
        except urllib.error.HTTPError as error:
            print(json.dumps({"endpoint": endpoint, "status": error.code}))


if __name__ == "__main__":
    main()
