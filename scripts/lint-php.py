#!/usr/bin/env python3
"""Run php -l over every production and bridge PHP file."""

from __future__ import annotations

import shutil
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def main() -> int:
    php = shutil.which("php")
    if not php:
        raise SystemExit("php executable is required")
    files = sorted((ROOT / "plugin").rglob("*.php")) + sorted((ROOT / "deploy").rglob("*.php"))
    failed = False
    for path in files:
        result = subprocess.run([php, "-l", str(path)], text=True, capture_output=True)
        print(result.stdout.strip() or result.stderr.strip())
        failed = failed or result.returncode != 0
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
