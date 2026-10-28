"""Run the installed browser binary directly, preserving arguments on Windows."""

import subprocess
import sys
from pathlib import Path


def main():
    binaries = list((Path.home() / "AppData/Local/npm-cache/_npx").glob("*/node_modules/agent-browser/bin/agent-browser-win32-x64.exe"))
    if not binaries:
        raise SystemExit("Install agent-browser with npx before using this wrapper.")
    arguments = sys.argv[1:]
    script = None
    if "--eval-file" in arguments:
        index = arguments.index("--eval-file")
        script = Path(arguments[index + 1]).read_text(encoding="utf-8")
        del arguments[index:index + 2]
        arguments.extend(["eval", "--stdin"])
    result = subprocess.run([str(binaries[-1]), *arguments], input=script, text=True, check=False)
    raise SystemExit(result.returncode)


if __name__ == "__main__":
    main()
