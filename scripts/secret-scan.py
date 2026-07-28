#!/usr/bin/env python3
"""Conservative repository secret scan without printing matched values."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SKIP = {"plugin-dist", "__pycache__", ".git"}
PATTERNS = {
    "private-key": re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    "github-token": re.compile(r"\bgh[opsu]_[A-Za-z0-9]{30,}\b"),
    "openai-key": re.compile(r"\bsk-(?:proj-)?[A-Za-z0-9_-]{24,}\b"),
    "aws-key": re.compile(r"\bAKIA[0-9A-Z]{16}\b"),
    "basic-auth-url": re.compile(r"https://[^/\s:@]+:[^/\s@]+@"),
}


def main() -> int:
    findings: list[tuple[str, str]] = []
    for path in sorted(ROOT.rglob("*")):
        if not path.is_file() or any(part in SKIP for part in path.relative_to(ROOT).parts):
            continue
        try:
            text = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue
        for name, pattern in PATTERNS.items():
            if pattern.search(text):
                findings.append((path.relative_to(ROOT).as_posix(), name))
    if findings:
        for path, kind in findings:
            print(f"{path}: potential {kind}")
        return 1
    print("secret scan passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
