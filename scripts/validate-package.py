#!/usr/bin/env python3
"""Validate WordPress package shape, metadata, integrity and forbidden content."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import stat
import zipfile
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
SLUG = "complete99-platform"
SOURCE = ROOT / "plugin" / SLUG
RAW_REPOSITORY_ROOT = "https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/main"
SCIENCE_SOURCE_ROOT = PurePosixPath("assets/images/science")
SCIENCE_MEDIA_POLICY_SCHEMA = "complete99-science-media-package-policy/v1"
SCIENCE_MEDIA_POLICY_DIR = ROOT / "release-policies"
SCIENCE_MEDIA_STEM_STATES = {
    "public_delivery",
    "held_repository_only",
    "approved_archive_repository_only",
}
SCIENCE_MEDIA_FILE_STATES = SCIENCE_MEDIA_STEM_STATES | {
    "source_evidence_repository_only",
}
SCIENCE_MEDIA_EXPECTED_COUNTS = {
    "stem_count": 47,
    "public_delivery_stem_count": 28,
    "held_repository_only_stem_count": 18,
    "approved_archive_repository_only_stem_count": 1,
    "source_file_count": 175,
    "delivery_file_count": 70,
    "held_repository_file_count": 78,
    "source_evidence_repository_file_count": 24,
    "repository_only_file_count": 105,
    "superseded_archive_file_count": 3,
}
VERIFIED_ORDER_URLS = (
    b"https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete",
    b"https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete",
)
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


def canonical_contents(path: Path) -> bytes:
    """Match the builder's portable installed-byte representation."""
    raw = path.read_bytes()
    if b"\0" in raw:
        return raw
    try:
        raw.decode("utf-8")
    except UnicodeDecodeError:
        return raw
    return raw.replace(b"\r\n", b"\n").replace(b"\r", b"\n")


def reject_duplicate_json_object(pairs: list[tuple[str, object]]) -> dict[str, object]:
    """Reject duplicate keys before independently interpreting release policy."""
    result: dict[str, object] = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"Duplicate JSON key: {key}")
        result[key] = value
    return result


def science_media_policy_path(version: str) -> Path:
    return SCIENCE_MEDIA_POLICY_DIR / f"{SLUG}-{version}-science-media.json"


def science_media_policy_digest(policy: dict[str, object]) -> str:
    canonical = json.dumps(
        policy,
        ensure_ascii=False,
        sort_keys=True,
        separators=(",", ":"),
    ).encode("utf-8")
    return hashlib.sha256(
        SCIENCE_MEDIA_POLICY_SCHEMA.encode("ascii") + b"\0" + canonical
    ).hexdigest()


def _exact_keys(value: object, expected: set[str], label: str) -> dict[str, object]:
    if not isinstance(value, dict) or set(value) != expected:
        raise SystemExit(f"Invalid {label} keys")
    return value


def science_source_inventory(source_root: Path) -> dict[str, Path]:
    """Independently reject indirect/special Science media filesystem entries."""
    science_root = source_root / Path(*SCIENCE_SOURCE_ROOT.parts)
    try:
        root_stat = science_root.lstat()
        resolved_root = science_root.resolve(strict=True)
    except OSError as error:
        raise SystemExit("Science media source root is missing") from error
    reparse_flag = getattr(stat, "FILE_ATTRIBUTE_REPARSE_POINT", 0x400)
    if (
        not stat.S_ISDIR(root_stat.st_mode)
        or science_root.is_symlink()
        or bool(getattr(root_stat, "st_file_attributes", 0) & reparse_flag)
    ):
        raise SystemExit("Science media source root is indirect or not a directory")
    inventory: dict[str, Path] = {}
    try:
        entries = list(science_root.iterdir())
    except OSError as error:
        raise SystemExit("Science media source root could not be enumerated") from error
    for path in entries:
        try:
            path_stat = path.lstat()
            resolved = path.resolve(strict=True)
        except OSError as error:
            raise SystemExit("Science media source entry could not be resolved safely") from error
        if (
            not stat.S_ISREG(path_stat.st_mode)
            or path.is_symlink()
            or bool(getattr(path_stat, "st_file_attributes", 0) & reparse_flag)
            or resolved.parent != resolved_root
        ):
            raise SystemExit("Science media source contains an indirect or special entry")
        relative = path.relative_to(source_root).as_posix()
        inventory[relative] = path
    return inventory


def science_media_policy_contract(
    version: str,
    *,
    source_root: Path | None = None,
    policy_path: Path | None = None,
) -> dict[str, object]:
    """Independently validate the complete default-deny Science media policy."""
    if not re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+", version):
        raise SystemExit("Science media release version is invalid")
    source_root = SOURCE if source_root is None else source_root
    policy_path = science_media_policy_path(version) if policy_path is None else policy_path
    try:
        policy = json.loads(
            policy_path.read_text(encoding="utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise SystemExit("Science media package policy could not be read safely") from error
    policy = _exact_keys(
        policy,
        {
            "schema",
            "release_version",
            "plugin_slug",
            "approval_registry",
            "expected_counts",
            "stems",
            "files",
        },
        "Science media policy",
    )
    expected_counts = _exact_keys(
        policy["expected_counts"],
        set(SCIENCE_MEDIA_EXPECTED_COUNTS),
        "Science media expected counts",
    )
    if (
        policy["schema"] != SCIENCE_MEDIA_POLICY_SCHEMA
        or policy["release_version"] != version
        or policy["plugin_slug"] != SLUG
        or any(type(value) is not int for value in expected_counts.values())
        or expected_counts != SCIENCE_MEDIA_EXPECTED_COUNTS
    ):
        raise SystemExit("Science media package policy identity/count contract is invalid")

    source_inventory = science_source_inventory(source_root)

    approval = _exact_keys(
        policy["approval_registry"],
        {"relative_path", "bytes", "sha256"},
        "Science media approval registry receipt",
    )
    if approval["relative_path"] != "data/culinary-science-publication-approvals.php":
        raise SystemExit("Science media approval registry path is invalid")
    approval_path = source_root / str(approval["relative_path"])
    try:
        approval_contents = canonical_contents(approval_path)
    except OSError as error:
        raise SystemExit("Science media approval registry is missing") from error
    if (
        not isinstance(approval["bytes"], int)
        or isinstance(approval["bytes"], bool)
        or approval["bytes"] != len(approval_contents)
        or not isinstance(approval["sha256"], str)
        or not re.fullmatch(r"[a-f0-9]{64}", approval["sha256"])
        or approval["sha256"] != hashlib.sha256(approval_contents).hexdigest()
    ):
        raise SystemExit("Science media approval registry receipt drifted")

    stems = policy["stems"]
    files = policy["files"]
    if not isinstance(stems, list) or not isinstance(files, list):
        raise SystemExit("Science media policy collections are invalid")
    stem_records: dict[str, dict[str, object]] = {}
    previous_stem = ""
    for raw_stem in stems:
        stem = _exact_keys(
            raw_stem,
            {"stem", "state", "binding_id", "reason"},
            "Science media stem",
        )
        stem_name = stem["stem"]
        if (
            not isinstance(stem_name, str)
            or not re.fullmatch(r"c99-science-[a-z0-9-]+-v[0-9]{2}", stem_name)
            or stem_name <= previous_stem
            or not isinstance(stem["state"], str)
            or stem["state"] not in SCIENCE_MEDIA_STEM_STATES
            or not isinstance(stem["binding_id"], str)
            or not re.fullmatch(r"[a-z0-9][a-z0-9:-]+", stem["binding_id"])
            or not isinstance(stem["reason"], str)
            or not re.fullmatch(r"[a-z0-9][a-z0-9_]+", stem["reason"])
        ):
            raise SystemExit("Science media stem contract is invalid or unsorted")
        stem_records[stem_name] = stem
        previous_stem = stem_name

    policy_paths: set[str] = set()
    delivery_paths: set[str] = set()
    delivery_receipts: dict[str, tuple[int, str]] = {}
    state_counts = {state: 0 for state in SCIENCE_MEDIA_FILE_STATES}
    superseded_archive_count = 0
    previous_path = ""
    allowed_filenames = {
        stem_name: {
            f"{stem_name}.png",
            f"{stem_name}.webp",
            f"{stem_name}.avif",
            f"{stem_name}-768.webp",
            f"{stem_name}-768.avif",
        }
        for stem_name in stem_records
    }
    for raw_file in files:
        record = _exact_keys(
            raw_file,
            {"stem", "relative_path", "bytes", "sha256", "state", "reason"},
            "Science media file",
        )
        relative = record["relative_path"]
        stem_name = record["stem"]
        if (
            not isinstance(relative, str)
            or not isinstance(stem_name, str)
            or stem_name not in stem_records
            or relative <= previous_path
        ):
            raise SystemExit("Science media file contract is invalid or unsorted")
        posix = PurePosixPath(relative)
        if (
            posix.is_absolute()
            or ".." in posix.parts
            or posix.parent != SCIENCE_SOURCE_ROOT
            or posix.name not in allowed_filenames[stem_name]
            or relative in policy_paths
            or not isinstance(record["state"], str)
            or record["state"] not in SCIENCE_MEDIA_FILE_STATES
            or not isinstance(record["reason"], str)
            or not re.fullmatch(r"[a-z0-9][a-z0-9_]+", record["reason"])
            or not isinstance(record["bytes"], int)
            or isinstance(record["bytes"], bool)
            or record["bytes"] < 1
            or not isinstance(record["sha256"], str)
            or not re.fullmatch(r"[a-f0-9]{64}", record["sha256"])
        ):
            raise SystemExit("Science media file receipt is invalid")
        stem_state = stem_records[stem_name]["state"]
        file_state = record["state"]
        if stem_state == "held_repository_only" and file_state != stem_state:
            raise SystemExit("Held Science media stem contains a deliverable file")
        if stem_state == "approved_archive_repository_only" and file_state != stem_state:
            raise SystemExit("Archived Science media stem contains a deliverable file")
        if stem_state == "public_delivery":
            if posix.suffix == ".png" and file_state != "source_evidence_repository_only":
                raise SystemExit("Public Science PNG source is not repository-only evidence")
            if posix.suffix != ".png" and file_state != "public_delivery":
                raise SystemExit("Public Science delivery derivative is not allowlisted")
        if record["reason"] == "superseded_public_asset":
            if file_state != "approved_archive_repository_only":
                raise SystemExit("Superseded Science media is not archive-only")
            superseded_archive_count += 1
        source_path = source_inventory.get(relative)
        if source_path is None:
            raise SystemExit(f"Science media source is missing: {relative}")
        try:
            raw = source_path.read_bytes()
        except OSError as error:
            raise SystemExit(f"Science media source is missing: {relative}") from error
        if len(raw) != record["bytes"] or hashlib.sha256(raw).hexdigest() != record["sha256"]:
            raise SystemExit(f"Science media source receipt drifted: {relative}")
        policy_paths.add(relative)
        state_counts[str(file_state)] += 1
        if file_state == "public_delivery":
            delivery_paths.add(relative)
            delivery_receipts[relative] = (int(record["bytes"]), str(record["sha256"]))
        previous_path = relative

    actual_paths = set(source_inventory)
    if actual_paths != policy_paths:
        raise SystemExit("Science media policy has missing or unclassified source files")
    counts = {
        "stem_count": len(stem_records),
        "public_delivery_stem_count": sum(
            record["state"] == "public_delivery" for record in stem_records.values()
        ),
        "held_repository_only_stem_count": sum(
            record["state"] == "held_repository_only" for record in stem_records.values()
        ),
        "approved_archive_repository_only_stem_count": sum(
            record["state"] == "approved_archive_repository_only"
            for record in stem_records.values()
        ),
        "source_file_count": len(policy_paths),
        "delivery_file_count": len(delivery_paths),
        "held_repository_file_count": state_counts["held_repository_only"],
        "source_evidence_repository_file_count": state_counts[
            "source_evidence_repository_only"
        ],
        "repository_only_file_count": (
            state_counts["held_repository_only"]
            + state_counts["source_evidence_repository_only"]
            + state_counts["approved_archive_repository_only"]
        ),
        "superseded_archive_file_count": superseded_archive_count,
    }
    if counts != SCIENCE_MEDIA_EXPECTED_COUNTS:
        raise SystemExit("Science media resolved counts do not match the release contract")
    return {
        "policy": policy,
        "policy_sha256": science_media_policy_digest(policy),
        "delivery_paths": frozenset(delivery_paths),
        "delivery_receipts": delivery_receipts,
        "counts": counts,
        "approval_registry_sha256": approval["sha256"],
    }


def expected_science_media_integrity_fields(contract: dict[str, object]) -> dict[str, object]:
    counts = contract["counts"]
    return {
        "science_media_approval_registry_sha256": contract["approval_registry_sha256"],
        "science_media_approved_archive_repository_only_stem_count": counts[
            "approved_archive_repository_only_stem_count"
        ],
        "science_media_delivery_file_count": counts["delivery_file_count"],
        "science_media_held_repository_only_stem_count": counts[
            "held_repository_only_stem_count"
        ],
        "science_media_held_repository_file_count": counts["held_repository_file_count"],
        "science_media_policy_schema": SCIENCE_MEDIA_POLICY_SCHEMA,
        "science_media_policy_sha256": contract["policy_sha256"],
        "science_media_public_delivery_stem_count": counts["public_delivery_stem_count"],
        "science_media_repository_only_file_count": counts["repository_only_file_count"],
        "science_media_source_evidence_repository_file_count": counts[
            "source_evidence_repository_file_count"
        ],
        "science_media_source_file_count": counts["source_file_count"],
        "science_media_stem_count": counts["stem_count"],
        "science_media_superseded_archive_file_count": counts[
            "superseded_archive_file_count"
        ],
    }


def validate_science_media_integrity_fields(
    metadata: dict[str, object], contract: dict[str, object]
) -> None:
    expected = expected_science_media_integrity_fields(contract)
    actual_names = {name for name in metadata if name.startswith("science_media_")}
    if actual_names != set(expected):
        raise SystemExit("Science media integrity field set is not bounded")
    for name, value in expected.items():
        if metadata[name] != value:
            raise SystemExit(f"Science media integrity field drifted: {name}")


def validate_science_media_archive(
    archive: zipfile.ZipFile, contract: dict[str, object]
) -> None:
    """Enforce the exact 1.20 delivery allowlist and every delivery receipt."""
    science_root = f"{SLUG}/{SCIENCE_SOURCE_ROOT.as_posix()}"
    folded_root = science_root.casefold()
    actual_list = [
        name
        for name in archive.namelist()
        if name.casefold().startswith(folded_root)
    ]
    if len(actual_list) != len(set(actual_list)) or len(actual_list) != len(
        {name.casefold() for name in actual_list}
    ):
        raise SystemExit("ZIP Science media contains duplicate or case-colliding names")
    actual = set(actual_list)
    receipts = contract["delivery_receipts"]
    expected = {f"{SLUG}/{relative}" for relative in receipts}
    if actual != expected:
        raise SystemExit("ZIP Science media set differs from the default-deny allowlist")
    for relative, receipt in receipts.items():
        contents = archive.read(f"{SLUG}/{relative}")
        expected_bytes, expected_sha256 = receipt
        if len(contents) != expected_bytes or hashlib.sha256(contents).hexdigest() != expected_sha256:
            raise SystemExit(f"ZIP Science media receipt drifted: {relative}")


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


def installed_digest(archive: zipfile.ZipFile) -> str:
    """Hash archive files exactly as the bridge hashes the installed directory."""
    entries: list[bytes] = []
    for info in archive.infolist():
        if info.is_dir():
            continue
        relative = (
            PurePosixPath(info.filename)
            .relative_to(SLUG)
            .as_posix()
            .encode("utf-8")
        )
        file_digest = hashlib.sha256(archive.read(info)).hexdigest().encode("ascii")
        entries.append(relative + b"\0" + file_digest)
    return hashlib.sha256(b"\n".join(sorted(entries))).hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", type=Path, default=ROOT / "plugin-dist")
    args = parser.parse_args()
    update_manifest_path = args.dist / f"{SLUG}.json"
    integrity_path = args.dist / f"{SLUG}-integrity.json"
    try:
        update_manifest = json.loads(
            update_manifest_path.read_text(encoding="utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
        metadata = json.loads(
            integrity_path.read_text(encoding="utf-8"),
            object_pairs_hook=reject_duplicate_json_object,
        )
    except (OSError, UnicodeDecodeError, json.JSONDecodeError, ValueError) as error:
        raise SystemExit("Package metadata could not be read safely") from error
    if not isinstance(update_manifest, dict) or not isinstance(metadata, dict):
        raise SystemExit("Package metadata roots must be objects")
    science_media = science_media_policy_contract(str(metadata.get("version", "")))
    validate_science_media_integrity_fields(metadata, science_media)
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
    assert re.fullmatch(
        r"[a-f0-9]{64}", metadata["installed_sha256"]
    ), "Installed digest is invalid"
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
        validate_science_media_archive(archive, science_media)
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
        assert installed_digest(archive) == metadata["installed_sha256"], (
            "Installed digest differs from ZIP"
        )

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

        for name in names:
            assert "wolt" not in name.casefold(), f"Third-party branded asset path found: {name}"
            if name.endswith("/"):
                continue
            contents = archive.read(name).lower()
            without_verified_order_url = contents
            for verified_order_url in VERIFIED_ORDER_URLS:
                without_verified_order_url = without_verified_order_url.replace(
                    verified_order_url, b""
                )
            assert b"wolt.com" not in without_verified_order_url, (
                f"Unapproved Wolt destination found in production package: {name}"
            )

    print(f"validated {artifact.name} sha256={actual} entries={len(names)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
