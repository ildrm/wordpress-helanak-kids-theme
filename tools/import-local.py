"""Import the downloaded live database into a dedicated, explicitly local database."""

import subprocess
from pathlib import Path


def main():
    root = Path(__file__).resolve().parents[1]
    source = root / ".local/migration/helanakc_wpdb.sql"
    sql = source.read_bytes()
    if sql.startswith(b"/*M!999999"):
        sql = sql.split(b"\n", 1)[1]
    result = subprocess.run(
        ["C:/xampp/mysql/bin/mysql.exe", "--host=127.0.0.1", "--user=root", "--default-character-set=utf8mb4", "helanak_local"],
        input=sql,
        check=False,
    )
    raise SystemExit(result.returncode)


if __name__ == "__main__":
    main()
