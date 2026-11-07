"""Revoke one owned verification login and remove its temporary auth files."""

import argparse
import http.cookiejar
import json
import ssl
import urllib.parse
import urllib.request
from html.parser import HTMLParser
from pathlib import Path


class SameSiteRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        if urllib.parse.urlsplit(newurl).hostname != "helanak.com":
            raise RuntimeError("Off-site redirect refused.")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


class LogoutLink(HTMLParser):
    def __init__(self):
        super().__init__()
        self.url = None

    def handle_starttag(self, tag, attrs):
        if tag != "a":
            return
        url = dict(attrs).get("href", "")
        parsed = urllib.parse.urlsplit(url)
        if parsed.hostname == "helanak.com" and parsed.path == "/wp-login.php" and urllib.parse.parse_qs(parsed.query).get("action") == ["logout"]:
            self.url = url


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--directory", required=True)
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    directory = Path(args.directory).resolve()
    if not directory.is_relative_to((root / ".local/deployments").resolve()):
        raise RuntimeError("Private deployment directory required.")
    state = json.loads((directory / "temporary-browser-auth.json").read_text(encoding="utf-8"))
    jar = http.cookiejar.CookieJar()
    for item in state["cookies"]:
        if item["domain"].lstrip(".") != "helanak.com":
            raise RuntimeError("Unexpected authentication cookie domain.")
        jar.set_cookie(http.cookiejar.Cookie(version=0, name=item["name"], value=item["value"], port=None, port_specified=False, domain=item["domain"], domain_specified=True, domain_initial_dot=item["domain"].startswith("."), path=item["path"], path_specified=True, secure=item["secure"], expires=item["expires"] if item["expires"] > 0 else None, discard=item["expires"] <= 0, comment=None, comment_url=None, rest={}, rfc2109=False))
    context = ssl.create_default_context()
    context.verify_flags |= 2_097_152
    opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=context), urllib.request.HTTPCookieProcessor(jar), SameSiteRedirect())
    with opener.open("https://helanak.com/wp-admin/", timeout=60) as response:
        admin = response.read().decode("utf-8")
        already_logged_out = urllib.parse.urlsplit(response.url).path == "/wp-login.php"
    if not already_logged_out:
        links = LogoutLink()
        links.feed(admin)
        if not links.url:
            raise RuntimeError("Fresh authenticated logout link not found; auth files retained.")
        with opener.open(links.url, timeout=60) as response:
            response.read()
    with opener.open("https://helanak.com/wp-admin/", timeout=60) as response:
        assert urllib.parse.urlsplit(response.url).path == "/wp-login.php", "Login still active; auth files retained."
        assert 'id="loginform"' in response.read().decode("utf-8")
    for name in ("temporary-browser-auth.json", "temporary-browser-logout.txt"):
        (directory / name).unlink(missing_ok=True)
    report = {"owned_session_logged_out": True, "temporary_auth_files_deleted": True}
    (directory / "verification-session-cleanup.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    print(json.dumps(report), flush=True)


if __name__ == "__main__":
    main()
