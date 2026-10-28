"""Back up, stage, replace and verify only the two Helanak production components.

Credentials are read without echo. No database import, content publishing,
configuration replacement, media upload or local-preview setup is supported.
"""

import argparse
import base64
import datetime
import getpass
import hashlib
import io
import json
import re
import secrets
import ssl
import time
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path, PurePosixPath


ROOT = Path(__file__).resolve().parents[1]
DOCUMENT_ROOT = "/domains/helanak.com/public_html"
COMPONENTS = {
    "helanak-core-plugin": "wp-content/plugins/helanak-core-plugin",
    "wp-helanak-kids-theme": "wp-content/themes/wp-helanak-kids-theme",
}
OPTION_KEYS = {
    "home", "siteurl", "active_plugins", "stylesheet", "template",
    "woocommerce_coming_soon", "show_on_front", "page_on_front",
    "page_for_posts", "blog_public", "woocommerce_currency",
    "woocommerce_default_country",
}


def digest(data):
    return hashlib.sha256(data).hexdigest()


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise RuntimeError("Unexpected hosting API redirect; credentials were not forwarded.")


class DirectAdmin:
    def __init__(self):
        password = getpass.getpass("DirectAdmin password: ")
        self.authorization = "Basic " + base64.b64encode(("helanakc:" + password).encode()).decode()
        context = ssl.create_default_context()
        # OpenSSL X509_V_FLAG_NO_CHECK_TIME: retain trusted-chain and hostname checks.
        # Both hosts have the already-observed expired certificate.
        context.verify_flags |= 2_097_152
        self.opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), NoRedirect())

    def request(self, *, endpoint, method="GET", payload=None, body=None, content_type=None):
        if not endpoint.startswith(("api/", "CMD_API_FILE_MANAGER")) or "://" in endpoint:
            raise RuntimeError("Unexpected API endpoint.")
        headers = {"Authorization": self.authorization, "User-Agent": "HelanakComponentRelease/1.0"}
        if payload is not None:
            body = json.dumps(payload).encode()
            content_type = "application/json"
        if content_type:
            headers["Content-Type"] = content_type
        request = urllib.request.Request("https://di101.limoodns.com:2222/" + endpoint, data=body, headers=headers, method=method)
        try:
            with self.opener.open(request, timeout=90) as response:
                data = response.read()
                status = response.status
        except urllib.error.HTTPError as error:
            raise RuntimeError(f"Hosting API {method} {endpoint.split('?')[0]} returned HTTP {error.code}.") from None
        if status not in (200, 201, 204):
            raise RuntimeError("Unexpected hosting API status: " + str(status))
        return data

    def listing(self, path):
        data = json.loads(self.request(endpoint="api/filemanager/list?" + urllib.parse.urlencode({"path": path, "limit": 0})))
        if data["canonicalPath"].strip("/") != path.strip("/"):
            raise RuntimeError("Resolved hosting directory differs from the intended path.")
        return data

    def download(self, path):
        return self.request(endpoint="api/filemanager/download?" + urllib.parse.urlencode({"path": path}))

    def archive(self, path):
        data = self.request(endpoint="api/filemanager/download-archive?" + urllib.parse.urlencode({"path": path, "type": "zip"}))
        with zipfile.ZipFile(io.BytesIO(data)) as archive:
            if archive.testzip():
                raise RuntimeError("Remote archive integrity failure.")
        return data

    def mkdir(self, path):
        self.request(endpoint="api/filemanager-actions/mkdir", method="POST", payload={"path": path})

    def upload(self, *, directory, name, data):
        boundary = "HelanakRelease" + secrets.token_hex(12)
        body = (
            f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\n'
            "Content-Type: application/octet-stream\r\n\r\n"
        ).encode() + data + f"\r\n--{boundary}--\r\n".encode()
        endpoint = "api/filemanager-actions/upload?" + urllib.parse.urlencode({"dir": directory, "name": name, "overwrite": "false", "perm": "0644"})
        self.request(endpoint=endpoint, method="POST", body=body, content_type="multipart/form-data; boundary=" + boundary)

    def move(self, *, source, destination):
        try:
            self.request(endpoint="api/filemanager-actions/move", method="POST", payload={"source": source, "destination": destination, "overwrite": False})
        except Exception:
            source_path = PurePosixPath(source)
            destination_path = PurePosixPath(destination)
            source_exists = any(item["name"] == source_path.name for item in self.listing(str(source_path.parent))["files"])
            destination_exists = any(item["name"] == destination_path.name for item in self.listing(str(destination_path.parent))["files"])
            if source_exists or not destination_exists:
                raise


def sql_rows(values):
    """Split mysqldump VALUES without splitting escaped commas or parentheses."""
    quoted = False
    escaped = False
    start = None
    fields = []
    field_start = 0
    for index, character in enumerate(values):
        if escaped:
            escaped = False
            continue
        if quoted and character == "\\":
            escaped = True
            continue
        if character == "'":
            quoted = not quoted
            continue
        if quoted:
            continue
        if character == "(" and start is None:
            start = index
            field_start = index + 1
            fields = []
        elif character == "," and start is not None:
            fields.append(values[field_start:index])
            field_start = index + 1
        elif character == ")" and start is not None:
            fields.append(values[field_start:index])
            yield fields, values[start:index + 1]
            start = None
    if quoted or start is not None:
        raise RuntimeError("Incomplete SQL row while inventorying the backup.")


def inventory(*, data, prefix):
    text = data.decode("utf-8")
    posts = {}
    counts = {}
    options = {}
    for table, values in re.findall(r"^INSERT INTO `([^`]+)` VALUES (.*);$", text, re.MULTILINE):
        if table not in (prefix + "posts", prefix + "options"):
            continue
        for fields, raw in sql_rows(values):
            if table == prefix + "posts":
                if len(fields) != 23:
                    raise RuntimeError("Unexpected WordPress posts schema.")
                posts[fields[0]] = digest(raw.encode())
                key = fields[20].strip("'") + "/" + fields[7].strip("'")
                counts[key] = counts.get(key, 0) + 1
            elif fields[1].strip("'") in OPTION_KEYS:
                options[fields[1].strip("'")] = fields[2]
    if not posts or not OPTION_KEYS.issubset(options):
        raise RuntimeError("The database inventory could not be verified.")
    return {
        "posts": posts,
        "counts": counts,
        "options": options,
        "qa_markers": text.count("_helanak_qa"),
        "local_preview_markers": text.count("helanak_local_preview_originals"),
    }


def archive_files(*, data, component):
    files = {}
    with zipfile.ZipFile(io.BytesIO(data)) as archive:
        for item in archive.infolist():
            if item.is_dir():
                continue
            path = PurePosixPath(item.filename)
            if path.is_absolute() or ".." in path.parts:
                raise RuntimeError("Unexpected archive path.")
            parts = path.parts[1:] if path.parts[0] == component else path.parts
            name = "/".join(parts)
            if not name or name in files:
                raise RuntimeError("Unexpected duplicate archive member.")
            files[name] = digest(archive.read(item))
    return files


def save(state):
    target = Path(state["local_directory"]) / "state.json"
    temporary = target.with_suffix(".tmp")
    temporary.write_text(json.dumps(state, indent=2), encoding="utf-8")
    temporary.replace(target)


def prepare(client):
    release = "helanak-release-" + datetime.datetime.now(datetime.timezone.utc).strftime("%Y%m%d-%H%M%S") + "-" + secrets.token_hex(3)
    directory = ROOT / ".local/deployments" / release
    directory.mkdir(parents=True)
    state = {"release": release, "local_directory": str(directory), "remote_directory": "/backups/" + release, "phase": "preparing", "components": {}}
    save(state)
    root_listing = client.listing(DOCUMENT_ROOT)
    (directory / "root-before.json").write_text(json.dumps(root_listing, indent=2), encoding="utf-8")
    names = {item["name"] for item in root_listing["files"]}
    if ".maintenance" in names:
        raise RuntimeError("A production maintenance marker already exists; it was not overwritten.")
    config = client.download(DOCUMENT_ROOT + "/wp-config.php")
    (directory / "wp-config-before.php").write_bytes(config)
    database = re.search(rb"define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)", config).group(1).decode()
    prefix = re.search(rb"\$table_prefix\s*=\s*['\"]([^'\"]+)", config).group(1).decode()
    if database != "helanakc_wpdb":
        raise RuntimeError("The active production database changed from the audited target.")
    state.update({"database": database, "prefix": prefix, "protected": {"wp-config.php": digest(config)}})
    if ".htaccess" in names:
        htaccess = client.download(DOCUMENT_ROOT + "/.htaccess")
        (directory / "htaccess-before.txt").write_bytes(htaccess)
        state["protected"][".htaccess"] = digest(htaccess)
    database_bytes = client.request(endpoint="api/db-manage/databases/" + database + "/export")
    (directory / "database-before.sql").write_bytes(database_bytes)
    state["before"] = inventory(data=database_bytes, prefix=prefix)
    if state["before"]["options"]["home"].strip("'").rstrip("/") != "https://helanak.com":
        raise RuntimeError("Database home URL is not the intended production domain.")
    if state["before"]["options"]["stylesheet"] != "'wp-helanak-kids-theme'":
        raise RuntimeError("The intended theme is not the production stylesheet.")
    if "helanak-core-plugin/helanak-core.php" not in state["before"]["options"]["active_plugins"]:
        raise RuntimeError("The intended Core plugin is not active on production.")
    manifest = json.loads((ROOT / ".local/artifacts/manifest.json").read_text())
    for component, relative in COMPONENTS.items():
        target = DOCUMENT_ROOT + "/" + relative
        backup = client.archive(target)
        (directory / (component + "-before.zip")).write_bytes(backup)
        record = next(item for item in manifest if item["component"] == component)
        package = (ROOT / ".local/artifacts" / record["file"]).read_bytes()
        if digest(package) != record["sha256"]:
            raise RuntimeError("Package hash differs from the release manifest.")
        files = archive_files(data=package, component=component)
        for name, expected in files.items():
            if digest((ROOT / component / name).read_bytes()) != expected:
                raise RuntimeError("Package differs from current source: " + component + "/" + name)
        if any(".local" in name or "qa-" in name or "wp-config" in name for name in files):
            raise RuntimeError("Unexpected development/configuration file in a release package.")
        state["components"][component] = {"target": target, "version": record["version"], "package": record["file"], "sha256": record["sha256"], "files": files, "previous_files": archive_files(data=backup, component=component)}
    client.mkdir(state["remote_directory"] + "/staged")
    client.mkdir(state["remote_directory"] + "/previous")
    for component, record in state["components"].items():
        package = (ROOT / ".local/artifacts" / record["package"]).read_bytes()
        client.upload(directory=state["remote_directory"], name=record["package"], data=package)
        client.request(endpoint="api/filemanager-actions/extract-archive", method="POST", payload={
            "source": state["remote_directory"] + "/" + record["package"],
            "destinationDir": state["remote_directory"] + "/staged", "members": [], "mergeAndOverwrite": False,
        })
        stage = state["remote_directory"] + "/staged/" + component
        if archive_files(data=client.archive(stage), component=component) != record["files"]:
            raise RuntimeError("Staged component does not match the reviewed package.")
    state["phase"] = "prepared"
    save(state)
    print(json.dumps({"phase": state["phase"], "state": str(directory / "state.json"), "production_counts": state["before"]["counts"], "staged_versions": {name: item["version"] for name, item in state["components"].items()}, "production_components_replaced": False}, indent=2), flush=True)


def load_state(path):
    path = Path(path).resolve()
    if not path.is_relative_to((ROOT / ".local/deployments").resolve()):
        raise RuntimeError("Release state must be in the private deployment directory.")
    state = json.loads(path.read_text(encoding="utf-8"))
    if state["remote_directory"] != "/backups/" + state["release"] or not state["release"].startswith("helanak-release-") or "/" in state["release"]:
        raise RuntimeError("Unexpected backup directory.")
    if set(state["components"]) != set(COMPONENTS):
        raise RuntimeError("Unexpected deployment components.")
    for component, relative in COMPONENTS.items():
        if state["components"][component]["target"] != DOCUMENT_ROOT + "/" + relative:
            raise RuntimeError("Unexpected production component destination.")
    return state


def deploy(*, client, state):
    if state["phase"] != "prepared":
        raise RuntimeError("Only a prepared release can be deployed.")
    if any(item["name"] == ".maintenance" for item in client.listing(DOCUMENT_ROOT)["files"]):
        raise RuntimeError("Existing production maintenance marker was not overwritten.")
    for component, record in state["components"].items():
        client.listing(record["target"])
        stage = state["remote_directory"] + "/staged/" + component
        directories = {""}
        for name in record["files"]:
            directories.update(str(parent) for parent in PurePosixPath(name).parents if str(parent) != ".")
        for relative in sorted(directories):
            listing = client.listing(stage + ("/" + relative if relative else ""))
            for item in listing["files"]:
                if item["symlink"] or item["unixMode"] & 0o022:
                    raise RuntimeError("Unsafe staged symlink or writable permissions: " + component + "/" + item["name"])
    maintenance = f"<?php /* {state['release']} */ $upgrading = {int(time.time())}; ?>\n".encode()
    state["maintenance_sha256"] = digest(maintenance)
    client.upload(directory=DOCUMENT_ROOT, name=".maintenance", data=maintenance)
    state["phase"] = "replacing"
    state["moved_previous"] = []
    state["installed"] = []
    save(state)
    try:
        for component, record in state["components"].items():
            # A fresh comparison prevents replacing code changed after staging.
            if archive_files(data=client.archive(record["target"]), component=component) != record["previous_files"]:
                raise RuntimeError("Production component changed after its backup: " + component)
            client.move(source=record["target"], destination=state["remote_directory"] + "/previous/" + component)
            state["moved_previous"].append(component)
            save(state)
            client.move(source=state["remote_directory"] + "/staged/" + component, destination=record["target"])
            state["installed"].append(component)
            save(state)
        for component, record in state["components"].items():
            if archive_files(data=client.archive(record["target"]), component=component) != record["files"]:
                raise RuntimeError("Installed component failed verification: " + component)
        state["phase"] = "deployed"
    except Exception:
        for component in reversed(state["moved_previous"]):
            if component in state["installed"]:
                client.move(source=state["components"][component]["target"], destination=state["remote_directory"] + "/failed-" + component)
            client.move(source=state["remote_directory"] + "/previous/" + component, destination=state["components"][component]["target"])
        state["phase"] = "rolled-back"
        raise
    finally:
        if state["phase"] not in ("deployed", "rolled-back"):
            state["phase"] = "rollback-needs-investigation"
            save(state)
            raise RuntimeError("Rollback did not finish; the owned maintenance marker was retained.")
        if digest(client.download(DOCUMENT_ROOT + "/.maintenance")) != state["maintenance_sha256"]:
            save(state)
            raise RuntimeError("Maintenance marker changed; it was not removed.")
        client.move(source=DOCUMENT_ROOT + "/.maintenance", destination=state["remote_directory"] + "/maintenance-completed.php")
        save(state)
    print(json.dumps({"phase": state["phase"], "installed_versions": {name: item["version"] for name, item in state["components"].items()}, "server_backup": state["remote_directory"] + "/previous"}, indent=2), flush=True)


def verify(*, client, state):
    if state["phase"] not in ("deployed", "verified"):
        raise RuntimeError("Only a deployed release can be verified.")
    failures = []
    for component, record in state["components"].items():
        actual = archive_files(data=client.archive(record["target"]), component=component)
        if actual != record["files"]:
            failures.append("Production source differs: " + component)
    for name, expected in state["protected"].items():
        if digest(client.download(DOCUMENT_ROOT + "/" + name)) != expected:
            failures.append("Protected configuration changed: " + name)
    data = client.request(endpoint="api/db-manage/databases/" + state["database"] + "/export")
    (Path(state["local_directory"]) / "database-after.sql").write_bytes(data)
    after = inventory(data=data, prefix=state["prefix"])
    for name in ("posts", "options", "qa_markers", "local_preview_markers"):
        if after[name] != state["before"][name]:
            failures.append("Production content/settings inventory changed: " + name)
    report = {"release": state["release"], "component_files_verified": sum(len(item["files"]) for item in state["components"].values()), "versions": {name: item["version"] for name, item in state["components"].items()}, "post_counts_before": state["before"]["counts"], "post_counts_after": after["counts"], "content_and_site_options_unchanged": not any("inventory" in failure for failure in failures), "test_data_imported": False, "failures": failures, "server_code_backup": state["remote_directory"] + "/previous"}
    (Path(state["local_directory"]) / "verification.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    state["phase"] = "verified" if not failures else "verification-failed"
    save(state)
    print(json.dumps(report, indent=2), flush=True)
    if failures:
        raise RuntimeError("Production verification requires investigation.")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=("prepare", "deploy", "verify"))
    parser.add_argument("--state")
    args = parser.parse_args()
    if args.action != "prepare" and not args.state:
        parser.error("--state is required for an existing release.")
    client = DirectAdmin()
    if args.action == "prepare":
        prepare(client)
    else:
        state = load_state(args.state)
        if args.action == "deploy":
            deploy(client=client, state=state)
        else:
            verify(client=client, state=state)


if __name__ == "__main__":
    main()
