#!/usr/bin/env python3
"""Require a strictly newer plugin version whenever production plugin source changes."""

from __future__ import annotations

import argparse
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MAIN_RELATIVE = Path("plugin/complete99-platform/complete99-platform.php")


def run_git(*args: str, check: bool = True) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        ["git", "-C", str(ROOT), *args],
        check=check,
        capture_output=True,
        text=True,
        encoding="utf-8",
    )


def parse_version(text: str) -> tuple[int, int, int]:
    match = re.search(r"^\s*\*\s*Version:\s*(\d+)\.(\d+)\.(\d+)\s*$", text, re.MULTILINE)
    if not match:
        raise SystemExit("Could not read the Complete99 plugin version")
    return tuple(int(part) for part in match.groups())


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-ref", default="")
    args = parser.parse_args()
    base = args.base_ref.strip()

    if not base or set(base) == {"0"}:
        print("release discipline: initial history, no prior plugin version")
        return 0
    if run_git("cat-file", "-e", f"{base}^{{commit}}", check=False).returncode:
        raise SystemExit(
            "Release discipline cannot run because the declared base commit is unavailable"
        )
    if run_git("cat-file", "-e", f"{base}:{MAIN_RELATIVE.as_posix()}", check=False).returncode:
        print("release discipline: first Complete99 plugin release")
        return 0

    changed = run_git("diff", "--name-only", f"{base}...HEAD", "--", "plugin").stdout.splitlines()
    if not changed:
        print("release discipline: plugin source unchanged")
        return 0

    previous_text = run_git("show", f"{base}:{MAIN_RELATIVE.as_posix()}").stdout
    current_text = (ROOT / MAIN_RELATIVE).read_text(encoding="utf-8")
    previous = parse_version(previous_text)
    current = parse_version(current_text)
    if current <= previous:
        before = ".".join(str(part) for part in previous)
        after = ".".join(str(part) for part in current)
        raise SystemExit(
            f"Plugin source changed without a strictly newer version: base={before} current={after}"
        )
    print(
        "release discipline: plugin source changed and version advanced "
        f"{'.'.join(map(str, previous))} -> {'.'.join(map(str, current))}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
