"""Download the live document root in small read-only archives, with resumable local staging."""

import argparse
import base64
import concurrent.futures
import getpass
import hashlib
import http.client
import json
import os
import shutil
import ssl
import time
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path, PurePosixPath


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--only-failed", action="store_true")
    args = parser.parse_args()
    password = os.environ.get("HELANAK_DA_PASSWORD") or getpass.getpass("DirectAdmin password: ")
    auth = base64.b64encode(f"helanakc:{password}".encode()).decode()
    context = ssl._create_unverified_context()
    base = "https://di101.limoodns.com:2222/"
    remote_root = PurePosixPath("/domains/helanak.com/public_html")
    root = Path(__file__).resolve().parents[1] / ".local/migration"
    stage = root / "live-files"
    archives = root / "archives"
    stage.mkdir(parents=True, exist_ok=True)
    archives.mkdir(parents=True, exist_ok=True)
    cache = root / "listing-cache"
    cache.mkdir(parents=True, exist_ok=True)

    def request(*, endpoint):
        req = urllib.request.Request(base + endpoint, headers={"Authorization": "Basic " + auth})
        for attempt in range(3):
            try:
                return urllib.request.urlopen(req, context=context, timeout=40)
            except (TimeoutError, urllib.error.URLError):
                if attempt == 2:
                    raise
                print(json.dumps({"retry": endpoint, "attempt": attempt + 2}), flush=True)
                time.sleep(1)

    tasks = []
    pending = [remote_root]
    if args.only_failed:
        failed_paths = {item["path"] for item in json.loads((root / "download-failures.json").read_text(encoding="utf-8"))}
        tasks = [item for item in json.loads((root / "live-file-manifest.json").read_text(encoding="utf-8")) if item["path"] in failed_paths]
        pending = []
    while pending:
        directory = pending.pop()
        listing = cache / (hashlib.sha256(str(directory).encode()).hexdigest()[:20] + ".json")
        if listing.exists():
            items = json.loads(listing.read_text(encoding="utf-8"))
        else:
            with request(endpoint="CMD_API_FILE_MANAGER?" + urllib.parse.urlencode({"path": str(directory)})) as response:
                items = urllib.parse.parse_qs(response.read().decode())
            listing.write_text(json.dumps(items), encoding="utf-8")
        for key, value in items.items():
            path = PurePosixPath(key)
            if path.parent != directory or path == remote_root:
                continue
            meta = {k: v[0] for k, v in urllib.parse.parse_qs(value[0], keep_blank_values=True).items()}
            if meta.get("islink") == "1":
                print(json.dumps({"symlink_skipped": key}), flush=True)
                continue
            kind = meta.get("type")
            size = int(meta.get("size", "0"))
            if kind == "dir" and size > 8_388_608:
                pending.append(path)
            elif kind in ("file", "dir"):
                tasks.append({"path": key, "kind": kind, "size": size})
        print(json.dumps({"listed": str(directory), "tasks": len(tasks)}), flush=True)
    if not args.only_failed:
        (root / "live-file-manifest.json").write_text(json.dumps(tasks, indent=2), encoding="utf-8")
    print(json.dumps({"download_tasks": len(tasks), "uncompressed_bytes": sum(t["size"] for t in tasks)}), flush=True)

    def download(*, task):
        path = PurePosixPath(task["path"])
        destination = stage.joinpath(*path.relative_to(remote_root).parts)
        if task["kind"] == "dir":
            output = archives / (hashlib.sha256(str(path).encode()).hexdigest()[:20] + ".zip")
            endpoint = "api/filemanager/download-archive?" + urllib.parse.urlencode({"path": str(path), "type": "zip"})
        else:
            output = destination
            endpoint = "api/filemanager/download?" + urllib.parse.urlencode({"path": str(path)})
        output.parent.mkdir(parents=True, exist_ok=True)
        if not output.exists() or (task["kind"] == "file" and output.stat().st_size != task["size"]):
            partial = output.with_suffix(output.suffix + ".part")
            for attempt in range(3):
                try:
                    with request(endpoint=endpoint) as response, partial.open("wb") as target:
                        shutil.copyfileobj(response, target, length=65_536)
                    break
                except (TimeoutError, urllib.error.URLError, http.client.RemoteDisconnected, ConnectionError):
                    if attempt == 2:
                        raise
                    time.sleep(1)
            partial.replace(output)
        if task["kind"] == "dir":
            destination.mkdir(parents=True, exist_ok=True)
            with zipfile.ZipFile(output) as archive:
                entries = archive.infolist()
                if archive.testzip() is not None:
                    raise ValueError("Archive integrity failure: " + str(path))
                for item in entries:
                    parts = PurePosixPath(item.filename).parts
                    if parts and parts[0] == path.name:
                        parts = parts[1:]
                    if not parts:
                        continue
                    if ".." in parts or PurePosixPath(item.filename).is_absolute():
                        raise ValueError("Unsafe archive path")
                    target = destination.joinpath(*parts)
                    if not target.resolve().is_relative_to(destination.resolve()):
                        raise ValueError("Archive path outside staging directory")
                    if item.is_dir():
                        target.mkdir(parents=True, exist_ok=True)
                    else:
                        target.parent.mkdir(parents=True, exist_ok=True)
                        with archive.open(item) as source, target.open("wb") as sink:
                            shutil.copyfileobj(source, sink)
        return {"downloaded": str(path.relative_to(remote_root)), "bytes": output.stat().st_size}

    failures = []
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        jobs = {executor.submit(download, task=task): task for task in tasks}
        for job in concurrent.futures.as_completed(jobs):
            try:
                print(json.dumps(job.result()), flush=True)
            except Exception as error:
                failure = {"path": jobs[job]["path"], "error": str(error)}
                failures.append(failure)
                print(json.dumps(failure), flush=True)
    (root / "download-failures.json").write_text(json.dumps(failures, indent=2), encoding="utf-8")
    if failures:
        raise SystemExit(1)
    print("All website file groups downloaded and verified.", flush=True)


if __name__ == "__main__":
    main()
