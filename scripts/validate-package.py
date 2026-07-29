#!/usr/bin/env python3
"""Validate WordPress package shape, metadata, integrity and forbidden content."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import zipfile
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
SLUG = "complete99-platform"
RAW_REPOSITORY_ROOT = "https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/main"
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


def forbidden_secret_path_reason(path: PurePosixPath) -> str | None:
    """Return a policy label for paths that must never enter a public package."""
    for component in path.parts:
        name = component.casefold().rstrip(" .")
        if name == ".env" or name.startswith(".env."):
            return "environment file"
        if PurePosixPath(name).suffix in FORBIDDEN_SECRET_SUFFIXES:
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


def validate_archive_safety(archive: zipfile.ZipFile) -> None:
    """Fail closed on secret-like archive paths and credential signatures."""
    for name in archive.namelist():
        path = PurePosixPath(name)
        forbidden_reason = forbidden_secret_path_reason(path)
        if forbidden_reason:
            raise SystemExit(
                f"Forbidden secret-like archive path: {name} ({forbidden_reason})"
            )
        if name.endswith("/"):
            continue
        signature = credential_signature_label(archive.read(name))
        if signature:
            raise SystemExit(
                f"Credential signature found in archive entry: {name} ({signature})"
            )


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", type=Path, default=ROOT / "plugin-dist")
    args = parser.parse_args()
    update_manifest_path = args.dist / f"{SLUG}.json"
    integrity_path = args.dist / f"{SLUG}-integrity.json"
    update_manifest = json.loads(update_manifest_path.read_text(encoding="utf-8"))
    metadata = json.loads(integrity_path.read_text(encoding="utf-8"))
    artifact = args.dist / metadata["artifact"]
    raw = artifact.read_bytes()
    actual = hashlib.sha256(raw).hexdigest()
    assert actual == metadata["sha256"], "Artifact digest differs from metadata"
    assert len(raw) == metadata["size"], "Artifact size differs from metadata"
    checksum_path = args.dist / f"{artifact.name}.sha256"
    checksum_parts = checksum_path.read_text(encoding="ascii").strip().split()
    assert checksum_parts == [
        actual,
        artifact.name,
    ], "Artifact checksum sidecar differs from the verified package"
    assert re.fullmatch(r"[a-f0-9]{64}", metadata["source_sha256"]), "Source digest is invalid"
    required_update_fields = {
        "name",
        "slug",
        "version",
        "author",
        "homepage",
        "requires",
        "tested",
        "requires_php",
        "download_url",
        "last_updated",
        "sections",
    }
    assert required_update_fields.issubset(update_manifest), "Update manifest is incomplete"
    assert update_manifest["name"] == "Complete99 Platform"
    assert update_manifest["slug"] == SLUG
    assert update_manifest["version"] == metadata["version"], "Manifest and artifact versions differ"
    assert update_manifest["requires"] == "6.4"
    assert update_manifest["tested"] == "7.0"
    assert update_manifest["requires_php"] == "8.0"
    assert update_manifest["download_url"] == (
        f"{RAW_REPOSITORY_ROOT}/plugin-dist/{artifact.name}"
    )
    assert re.fullmatch(r"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}", update_manifest["last_updated"])
    assert isinstance(update_manifest["sections"], dict)
    assert update_manifest["sections"].get("changelog"), "Update changelog is empty"

    with zipfile.ZipFile(artifact) as archive:
        names = archive.namelist()
        assert names, "ZIP is empty"
        assert names == sorted(names), "ZIP entries are not sorted"
        validate_archive_safety(archive)
        for name in names:
            assert "\\" not in name, f"Backslash in ZIP path: {name}"
            path = PurePosixPath(name)
            assert path.parts[0] == SLUG, f"Wrong package root: {name}"
            assert ".." not in path.parts, f"Traversal in ZIP path: {name}"
            assert not any(part in {".git", "tests", "node_modules", "__pycache__"} for part in path.parts)

        source_digest = hashlib.sha256()
        for name in names:
            relative = PurePosixPath(name).relative_to(SLUG).as_posix().encode("utf-8")
            contents = archive.read(name)
            source_digest.update(len(relative).to_bytes(8, "big"))
            source_digest.update(relative)
            source_digest.update(len(contents).to_bytes(8, "big"))
            source_digest.update(contents)
        assert source_digest.hexdigest() == metadata["source_sha256"], "Source digest differs from ZIP"

        main_name = f"{SLUG}/{SLUG}.php"
        assert main_name in names, "Main plugin file missing"
        main_text = archive.read(main_name).decode("utf-8")
        assert re.search(rf"Version:\s*{re.escape(metadata['version'])}\b", main_text)
        assert f"COMPLETE99_PLATFORM_VERSION', '{metadata['version']}'" in main_text
        assert metadata["deployment_id"] in main_text
        assert f"{RAW_REPOSITORY_ROOT}/plugin-dist/{SLUG}.json" in main_text

        puc_root = f"{SLUG}/lib/plugin-update-checker"
        puc_main = archive.read(f"{puc_root}/plugin-update-checker.php").decode("utf-8")
        puc_loader = archive.read(f"{puc_root}/load-v5p6.php").decode("utf-8")
        provenance = archive.read(f"{puc_root}/UPSTREAM.md").decode("utf-8")
        assert "Plugin Update Checker Library 5.6" in puc_main
        assert "Puc/v5p6" in puc_loader
        assert "a2db6871deec989a74e1f90fafc6d58ae526a879" in provenance
        assert f"{puc_root}/license.txt" in names

        joined = b"\n".join(archive.read(name) for name in names if not name.endswith("/"))
        assert b"wolt" not in joined.lower(), "Wolt reference found in production package"

    print(f"validated {artifact.name} sha256={actual} entries={len(names)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
