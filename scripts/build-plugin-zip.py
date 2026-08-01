#!/usr/bin/env python3
"""Build the Complete99 WordPress plugin as byte-for-byte reproducible ZIP."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import tempfile
import zipfile
from pathlib import Path, PurePath

ROOT = Path(__file__).resolve().parents[1]
SLUG = "complete99-platform"
SOURCE = ROOT / "plugin" / SLUG
MAIN = SOURCE / f"{SLUG}.php"
DEFAULT_DIST = ROOT / "plugin-dist"
UPDATE_MANIFEST_NAME = f"{SLUG}.json"
INTEGRITY_METADATA_NAME = f"{SLUG}-integrity.json"
RAW_REPOSITORY_ROOT = "https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/main"
RELEASE_LAST_UPDATED = "2026-08-01 01:50:00"
FIXED_TIME = (1980, 1, 1, 0, 0, 0)
EXCLUDED_NAMES = {".DS_Store", "Thumbs.db"}
EXCLUDED_PARTS = {".git", ".github", "tests", "node_modules", "__pycache__"}
GENERATED_SOURCE_ROOT = PurePath("assets/images/generated")
FORBIDDEN_SECRET_SUFFIXES = {".pem", ".key", ".p12", ".pfx"}
FORBIDDEN_SECRET_EXACT_NAMES = {"id_rsa", "id_ed25519"}
FORBIDDEN_JSON_NAME = re.compile(
    r"(?:credential|service[-_. ]?account|client[-_. ]?secret|secret[-_. ]?key)",
    re.IGNORECASE,
)
CREDENTIAL_SIGNATURES: tuple[tuple[str, re.Pattern[bytes]], ...] = (
    (
        "private-key material",
        re.compile(
            rb"-----BEGIN (?:RSA |EC |DSA |OPENSSH |ENCRYPTED )?PRIVATE KEY-----",
            re.IGNORECASE,
        ),
    ),
    ("GitHub access token", re.compile(rb"\bgh(?:p|o|u|s|r)_[A-Za-z0-9]{20,}\b")),
    ("GitHub fine-grained token", re.compile(rb"\bgithub_pat_[A-Za-z0-9_]{20,}\b")),
    (
        "OpenAI API key",
        re.compile(rb"\bsk-(?:proj-|svcacct-)?[A-Za-z0-9_-]{20,}\b"),
    ),
    ("AWS access key ID", re.compile(rb"\b(?:AKIA|ASIA)[A-Z0-9]{16}\b")),
    ("Google API key", re.compile(rb"\bAIza[0-9A-Za-z_-]{35}\b")),
    (
        "Slack access token",
        re.compile(rb"\bxox(?:a|b|p|r|s)-[0-9A-Za-z-]{20,}\b"),
    ),
    ("Stripe live secret key", re.compile(rb"\bsk_live_[0-9A-Za-z]{16,}\b")),
    ("npm access token", re.compile(rb"\bnpm_[0-9A-Za-z]{20,}\b")),
)


def forbidden_secret_path_reason(path: PurePath) -> str | None:
    """Return a policy label for paths that must never enter a public package."""
    for component in path.parts:
        name = component.casefold().rstrip(" .")
        if name == ".env" or name.startswith(".env."):
            return "environment file"
        if Path(name).suffix in FORBIDDEN_SECRET_SUFFIXES:
            return "private key/certificate container"
        if name in FORBIDDEN_SECRET_EXACT_NAMES:
            return "private key filename"
        if re.search(r"\.json(?:$|[._-])", name) and FORBIDDEN_JSON_NAME.search(name):
            return "credential JSON filename"
    return None


def credential_signature_label(contents: bytes) -> str | None:
    """Identify a credential signature without returning or logging its value."""
    for label, pattern in CREDENTIAL_SIGNATURES:
        if pattern.search(contents):
            return label
    return None


def canonical_contents(path: Path) -> bytes:
    """Normalize text line endings so Windows and Linux produce identical ZIP bytes."""
    raw = path.read_bytes()
    if b"\0" in raw:
        return raw
    try:
        raw.decode("utf-8")
    except UnicodeDecodeError:
        return raw
    return raw.replace(b"\r\n", b"\n").replace(b"\r", b"\n")


def version_contract() -> tuple[str, str]:
    text = MAIN.read_text(encoding="utf-8")
    header = re.search(r"^\s*\*\s*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$", text, re.MULTILINE)
    constant = re.search(r"define\(\s*'COMPLETE99_PLATFORM_VERSION'\s*,\s*'([^']+)'\s*\)", text)
    deployment = re.search(r"define\(\s*'COMPLETE99_PLATFORM_DEPLOYMENT_ID'\s*,\s*'([^']+)'\s*\)", text)
    if not header or not constant or not deployment:
        raise SystemExit("Missing plugin version/deployment contract")
    if header.group(1) != constant.group(1):
        raise SystemExit(f"Version mismatch: header={header.group(1)} constant={constant.group(1)}")
    expected_deployment = f"c99-wp-{header.group(1)}"
    if deployment.group(1) != expected_deployment:
        raise SystemExit(
            f"Deployment ID mismatch: expected={expected_deployment} actual={deployment.group(1)}"
        )
    return header.group(1), deployment.group(1)


def source_files() -> list[Path]:
    files: list[Path] = []
    for path in SOURCE.rglob("*"):
        if not path.is_file():
            continue
        relative = path.relative_to(SOURCE)
        if any(part in EXCLUDED_PARTS for part in relative.parts):
            continue
        if (
            relative.parent == GENERATED_SOURCE_ROOT
            and relative.suffix.casefold() == ".png"
        ):
            continue
        forbidden_reason = forbidden_secret_path_reason(relative)
        if forbidden_reason:
            raise SystemExit(
                f"Refusing to package forbidden secret-like path: "
                f"{relative.as_posix()} ({forbidden_reason})"
            )
        if path.name in EXCLUDED_NAMES:
            continue
        signature = credential_signature_label(path.read_bytes())
        if signature:
            raise SystemExit(
                f"Refusing to package credential signature in "
                f"{relative.as_posix()} ({signature})"
            )
        files.append(path)
    return sorted(files, key=lambda path: path.relative_to(SOURCE).as_posix())


def build_bytes(target: Path) -> None:
    target.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(target, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in source_files():
            relative = path.relative_to(SOURCE).as_posix()
            info = zipfile.ZipInfo(f"{SLUG}/{relative}", FIXED_TIME)
            info.create_system = 3
            info.external_attr = (0o100644 & 0xFFFF) << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            info.flag_bits |= 0x800
            archive.writestr(
                info,
                canonical_contents(path),
                compress_type=zipfile.ZIP_DEFLATED,
                compresslevel=9,
            )


def source_digest() -> str:
    digest = hashlib.sha256()
    for path in source_files():
        relative = path.relative_to(SOURCE).as_posix().encode("utf-8")
        raw = canonical_contents(path)
        digest.update(len(relative).to_bytes(8, "big"))
        digest.update(relative)
        digest.update(len(raw).to_bytes(8, "big"))
        digest.update(raw)
    return digest.hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", type=Path, default=DEFAULT_DIST)
    parser.add_argument("--verify-reproducible", action="store_true")
    args = parser.parse_args()

    version, deployment_id = version_contract()
    dist = args.dist.resolve()
    artifact = dist / f"{SLUG}-{version}.zip"

    if args.verify_reproducible:
        with tempfile.TemporaryDirectory(prefix="complete99-repro-") as temp:
            first = Path(temp) / "first.zip"
            second = Path(temp) / "second.zip"
            build_bytes(first)
            build_bytes(second)
            first_digest = hashlib.sha256(first.read_bytes()).hexdigest()
            second_digest = hashlib.sha256(second.read_bytes()).hexdigest()
            if first_digest != second_digest or first.read_bytes() != second.read_bytes():
                raise SystemExit("Reproducibility check failed")

    build_bytes(artifact)
    raw = artifact.read_bytes()
    digest = hashlib.sha256(raw).hexdigest()
    integrity = {
        "artifact": artifact.name,
        "deployment_id": deployment_id,
        "sha256": digest,
        "size": len(raw),
        "slug": SLUG,
        "source_sha256": source_digest(),
        "type": "plugin",
        "version": version,
    }
    update_manifest = {
        "name": "Complete99 Platform",
        "slug": SLUG,
        "version": version,
        "author": "Complete99",
        "homepage": "https://complete99.co.il/",
        "requires": "6.4",
        "tested": "7.0",
        "requires_php": "8.0",
        "download_url": f"{RAW_REPOSITORY_ROOT}/plugin-dist/{artifact.name}",
        "last_updated": RELEASE_LAST_UPDATED,
        "sections": {
            "changelog": (
                f"<h4>{version}</h4>"
                "<ul>"
                "<li>Made pantry filters visually hide every nonmatching product card while keeping the result count and URL state synchronized.</li>"
                "</ul>"
                "<h4>1.3.10</h4>"
                "<ul>"
                "<li>Kept signed catalog identity independent of WooCommerce customer-language and session filters.</li>"
                "<li>Kept the Hebrew and English pantry available after cart-language changes and item removal.</li>"
                "<li>Read stock identity and immediate catalog verification from the raw WooCommerce edit context.</li>"
                "</ul>"
                "<h4>1.3.9</h4>"
                "<ul>"
                "<li>Published a bilingual, food-first pantry catalog with 26 linked ingredient products and opening stock.</li>"
                "<li>Presented approved dish and product images normally without archive notices or unusual public treatment.</li>"
                "<li>Separated public catalog and cart readiness from the electronic payment launch gate.</li>"
                "<li>Added exact WooCommerce dependency verification and fail-closed recovery for dependency and catalog materialization.</li>"
                "<li>Normalized WooCommerce page option types across cache flushes so strict catalog readback stays deterministic.</li>"
                "<li>Kept the approved WordPress presentation authoritative, applied synchronized publication controls per dish, set WooCommerce visibility to live and purged host page caches after commit.</li>"
                "</ul>"
            )
        },
    }
    dist.mkdir(parents=True, exist_ok=True)
    (dist / UPDATE_MANIFEST_NAME).write_text(
        json.dumps(update_manifest, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    (dist / INTEGRITY_METADATA_NAME).write_text(
        json.dumps(integrity, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    (dist / f"{artifact.name}.sha256").write_text(f"{digest}  {artifact.name}\n", encoding="ascii", newline="\n")
    print(json.dumps(integrity, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
