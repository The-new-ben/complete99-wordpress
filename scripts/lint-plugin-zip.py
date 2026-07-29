#!/usr/bin/env python3
"""Run ``php -l`` against every PHP file in the exact release ZIP."""

from __future__ import annotations

import argparse
import json
import shutil
import subprocess
import tempfile
import zipfile
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", type=Path, default=ROOT / "plugin-dist")
    args = parser.parse_args()

    php = shutil.which("php")
    if not php:
        raise SystemExit("php executable is required")

    integrity_path = args.dist / "complete99-platform-integrity.json"
    metadata = json.loads(integrity_path.read_text(encoding="utf-8"))
    artifact = args.dist / metadata["artifact"]
    if not artifact.is_file():
        raise SystemExit(f"release artifact is missing: {artifact}")

    failed = False
    linted = 0
    with zipfile.ZipFile(artifact) as archive, tempfile.TemporaryDirectory() as temp:
        temp_root = Path(temp)
        for index, name in enumerate(sorted(archive.namelist())):
            if name.endswith("/") or not name.lower().endswith(".php"):
                continue
            if "\\" in name:
                raise SystemExit(f"backslash in ZIP path: {name}")
            archive_path = PurePosixPath(name)
            if archive_path.is_absolute() or ".." in archive_path.parts:
                raise SystemExit(f"unsafe ZIP path: {name}")

            linted += 1
            local_path = temp_root / f"{index:04d}-{archive_path.name}"
            local_path.write_bytes(archive.read(name))
            result = subprocess.run(
                [php, "-l", str(local_path)],
                text=True,
                capture_output=True,
            )
            message = (result.stdout or result.stderr).strip()
            print(f"{name}: {message}")
            failed = failed or result.returncode != 0

    if linted == 0:
        raise SystemExit("release ZIP contains no PHP files")
    print(f"linted {linted} PHP files from {artifact.name}")
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
