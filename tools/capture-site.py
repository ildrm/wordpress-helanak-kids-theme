"""Capture read-only storefront screenshots and accessibility evidence."""

import argparse
import json
import subprocess
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--session", required=True)
    parser.add_argument("--base", required=True)
    parser.add_argument("--label", required=True)
    parser.add_argument("--mobile", action="store_true")
    parser.add_argument("--lang", choices=["en", "fa"])
    parser.add_argument("--extended", action="store_true")
    parser.add_argument("--only", nargs="+", help="Refresh selected pages while retaining the other completed results.")
    parser.add_argument("--reveal-lazy", action="store_true", help="Scroll through the page to load images before full-page capture.")
    args = parser.parse_args()
    binary = list((Path.home() / "AppData/Local/npm-cache/_npx").glob("*/node_modules/agent-browser/bin/agent-browser-win32-x64.exe"))[-1]
    output = Path(__file__).resolve().parents[1] / ".local/audit" / args.label
    output.mkdir(parents=True, exist_ok=True)

    def run(*, command):
        return subprocess.run([str(binary), "--session", args.session, *command], capture_output=True, text=True, encoding="utf-8", errors="replace", timeout=50)

    run(command=["set", "viewport", "390" if args.mobile else "1440", "844" if args.mobile else "1000"])
    pages = {"home": "/", "shop": "/?page_id=6", "cart": "/?page_id=7", "checkout": "/?page_id=8", "account": "/?page_id=9", "sample": "/?page_id=2"}
    if args.extended:
        pages.update({"about": "/?page_id=408", "contact": "/?page_id=409", "offers": "/?page_id=410", "journal": "/?page_id=411", "product": "/?post_type=product&p=143"})
    results = []
    if args.only:
        unknown = set(args.only) - set(pages)
        if unknown:
            parser.error("Unknown pages: " + ", ".join(sorted(unknown)))
        summary = output / "summary.json"
        if summary.is_file():
            results = json.loads(summary.read_text(encoding="utf-8"))
        pages = {name: path for name, path in pages.items() if name in args.only}
    for name, path in pages.items():
        url = args.base.rstrip("/") + path
        if args.lang:
            url += ("&" if "?" in url else "?") + "lang=" + args.lang
        navigation = run(command=["open", url])
        if args.reveal_lazy:
            run(command=["eval", "(async function(){var start=scrollY;for(var position=0,step=0;position<document.documentElement.scrollHeight && step<50;position+=innerHeight,step++){scrollTo(0,position);await new Promise(function(resolve){requestAnimationFrame(function(){requestAnimationFrame(resolve);});});var visible=Array.from(document.images).filter(function(image){var rect=image.getBoundingClientRect();return rect.bottom>=0 && rect.top<=innerHeight;});await Promise.race([Promise.all(visible.map(function(image){return image.complete?Promise.resolve():new Promise(function(resolve){image.addEventListener('load',resolve,{once:true});image.addEventListener('error',resolve,{once:true});});})),new Promise(function(resolve){setTimeout(resolve,3000);})]);}scrollTo(0,start);return true;})()"])
        readiness = run(command=["eval", "(async function(){await document.fonts.ready; await Promise.all(Array.from(document.images).filter(function(image){return image.loading !== 'lazy';}).map(function(image){return image.complete ? Promise.resolve() : new Promise(function(resolve){image.addEventListener('load',resolve,{once:true});image.addEventListener('error',resolve,{once:true});});}));await Promise.race([Promise.allSettled(document.getAnimations().filter(function(animation){return animation.effect && animation.effect.getTiming().iterations !== Infinity;}).map(function(animation){return animation.finished;})),new Promise(function(resolve){setTimeout(resolve,3000);})]);return {ready:document.readyState,viewport:innerWidth,scrollWidth:document.documentElement.scrollWidth,url:location.href};})()", "--json"])
        screenshot = run(command=["screenshot", "--full", str(output / (name + ".png"))])
        snapshot = run(command=["snapshot", "-i"])
        (output / (name + ".txt")).write_text(snapshot.stdout, encoding="utf-8")
        audit = run(command=["a11y", "--tags", "wcag2a,wcag2aa", "--json"])
        (output / (name + "-a11y.json")).write_text(audit.stdout, encoding="utf-8")
        audit_error = None
        try:
            payload = json.loads(audit.stdout)
            if audit.returncode or not payload.get("success"):
                audit_error = payload.get("error") or audit.stderr or "Accessibility audit failed."
            data = payload.get("data") or {}
            if not data:
                audit_error = audit_error or "Accessibility audit returned no data."
            violations = [{"id": v["id"], "impact": v.get("impact"), "nodes": v.get("nodeCount")} for v in data.get("violations", [])]
        except json.JSONDecodeError:
            violations = []
            audit_error = audit.stdout or audit.stderr or "Accessibility audit returned no JSON."
        errors = run(command=["errors", "--json"])
        (output / (name + "-errors.json")).write_text(errors.stdout, encoding="utf-8")
        try:
            dimensions = (json.loads(readiness.stdout).get("data") or {}).get("result")
        except json.JSONDecodeError:
            dimensions = None
        result = {"page": name, "navigation_exit": navigation.returncode, "navigation_error": navigation.stdout + navigation.stderr if navigation.returncode else None, "screenshot_exit": screenshot.returncode, "screenshot_error": screenshot.stdout + screenshot.stderr if screenshot.returncode else None, "audit_exit": audit.returncode, "audit_error": audit_error, "dimensions": dimensions, "violations": violations}
        results = [previous for previous in results if previous["page"] != name]
        results.append(result)
        (output / "summary.json").write_text(json.dumps(results, indent=2), encoding="utf-8")
        print(json.dumps(result), flush=True)
    (output / "summary.json").write_text(json.dumps(results, indent=2), encoding="utf-8")
    run(command=["close"])


if __name__ == "__main__":
    main()
