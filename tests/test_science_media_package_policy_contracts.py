from __future__ import annotations

import copy
import hashlib
import importlib.util
import json
import os
import sys
import tempfile
import unittest
import zipfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
POLICY_PATH = (
    ROOT
    / "release-policies"
    / "complete99-platform-1.22.0-science-media.json"
)
HELD_STEMS = {
    "c99-science-aleppan-jewish-foodways-v01",
    "c99-science-aleppine-kibbeh-family-v01",
    "c99-science-aleppo-pepper-v01",
    "c99-science-freekeh-v01",
    "c99-science-jas-1703-shoyu-standard-v01",
    "c99-science-kioke-wooden-barrel-v01",
    "c99-science-koji-enzymatic-hydrolysis-v01",
    "c99-science-koji-enzymes-hydrolysis-guide-v01",
    "c99-science-pomegranate-molasses-v01",
    "c99-science-shoyu-koji-substrate-v01",
    "c99-science-sumac-v01",
    "c99-science-syrian-aleppo-table-v01",
    "c99-science-syrian-bulgur-hydration-v01",
    "c99-science-syrian-bulgur-v01",
    "c99-science-syrian-cooking-methods-v01",
    "c99-science-syrian-kibbeh-cooking-v01",
    "c99-science-syrian-lamb-beef-family-v01",
    "c99-science-syrian-pantry-foundations-v01",
}


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


BUILD = load_module(
    "complete99_science_policy_builder",
    ROOT / "scripts" / "build-plugin-zip.py",
)
VALIDATE = load_module(
    "complete99_science_policy_validator",
    ROOT / "scripts" / "validate-package.py",
)


class ScienceMediaPackagePolicyContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.policy = json.loads(POLICY_PATH.read_text(encoding="utf-8"))
        cls.builder_contract = BUILD.science_media_policy_contract("1.22.0")
        cls.validator_contract = VALIDATE.science_media_policy_contract("1.22.0")

    def write_policy(self, directory: Path, policy: dict[str, object]) -> Path:
        path = directory / "science-media-policy.json"
        path.write_text(
            json.dumps(policy, ensure_ascii=False, separators=(",", ":")),
            encoding="utf-8",
        )
        return path

    def make_minimal_source(self, directory: Path) -> Path:
        source = directory / "complete99-platform"
        science = source / "assets" / "images" / "science"
        data = source / "data"
        science.mkdir(parents=True)
        data.mkdir(parents=True)
        approval_name = "culinary-science-publication-approvals.php"
        (data / approval_name).write_bytes((BUILD.SOURCE / "data" / approval_name).read_bytes())
        return source

    def assert_policy_rejected_by_both(
        self, policy: dict[str, object], expected_message: str
    ) -> None:
        with tempfile.TemporaryDirectory(prefix="complete99-science-policy-") as temp:
            policy_path = self.write_policy(Path(temp), policy)
            for module in (BUILD, VALIDATE):
                with self.subTest(module=module.__name__):
                    with self.assertRaises(SystemExit) as caught:
                        module.science_media_policy_contract(
                            "1.22.0",
                            policy_path=policy_path,
                        )
                    self.assertIn(expected_message, str(caught.exception))

    def test_policy_is_repository_only_and_both_consumers_resolve_identically(self) -> None:
        self.assertFalse(POLICY_PATH.is_relative_to(BUILD.SOURCE))
        self.assertEqual(
            self.builder_contract["policy_sha256"],
            self.validator_contract["policy_sha256"],
        )
        self.assertEqual(
            self.builder_contract["delivery_paths"],
            self.validator_contract["delivery_paths"],
        )
        self.assertEqual(
            {
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
            },
            self.builder_contract["counts"],
        )

    def test_exact_stem_and_file_states_are_bounded(self) -> None:
        stems = self.policy["stems"]
        files = self.policy["files"]
        held = {item["stem"] for item in stems if item["state"] == "held_repository_only"}
        self.assertEqual(HELD_STEMS, held)
        self.assertEqual(
            {"pending_owner_publication_receipt": 12, "legacy_private_editorial_review": 6},
            {
                reason: sum(item["reason"] == reason for item in stems)
                for reason in {
                    "pending_owner_publication_receipt",
                    "legacy_private_editorial_review",
                }
            },
        )
        self.assertEqual(
            {"c99-science-culinary-science-museum-v01"},
            {
                item["stem"]
                for item in stems
                if item["state"] == "approved_archive_repository_only"
            },
        )
        self.assertEqual(
            {
                "public_delivery": 70,
                "held_repository_only": 78,
                "source_evidence_repository_only": 24,
                "approved_archive_repository_only": 3,
            },
            {
                state: sum(item["state"] == state for item in files)
                for state in {
                    "public_delivery",
                    "held_repository_only",
                    "source_evidence_repository_only",
                    "approved_archive_repository_only",
                }
            },
        )
        self.assertTrue(
            all(
                item["relative_path"].endswith(".png")
                for item in files
                if item["state"] == "source_evidence_repository_only"
            )
        )
        self.assertEqual(
            {"pending_owner_publication_receipt": 60, "legacy_private_editorial_review": 18},
            {
                reason: sum(item["reason"] == reason for item in files)
                for reason in {
                    "pending_owner_publication_receipt",
                    "legacy_private_editorial_review",
                }
            },
        )

    def test_builder_selects_only_the_exact_70_delivery_receipts(self) -> None:
        selected = {
            path.relative_to(BUILD.SOURCE).as_posix()
            for path in BUILD.source_files()
            if path.relative_to(BUILD.SOURCE).parent.as_posix()
            == BUILD.SCIENCE_SOURCE_ROOT.as_posix()
        }
        self.assertEqual(self.builder_contract["delivery_paths"], selected)
        self.assertEqual(70, len(selected))
        self.assertFalse(any(path.endswith(".png") for path in selected))

    def test_registry_receipt_hash_and_state_drift_fail_in_both_consumers(self) -> None:
        registry_drift = copy.deepcopy(self.policy)
        registry_drift["approval_registry"]["sha256"] = "0" * 64
        self.assert_policy_rejected_by_both(registry_drift, "registry receipt drifted")

        hash_drift = copy.deepcopy(self.policy)
        hash_drift["files"][0]["sha256"] = "0" * 64
        self.assert_policy_rejected_by_both(hash_drift, "source receipt drifted")

        state_drift = copy.deepcopy(self.policy)
        state_drift["files"][0]["state"] = "public_delivery"
        self.assert_policy_rejected_by_both(state_drift, "Held Science media stem")

    def test_unclassified_source_file_fails_in_both_consumers(self) -> None:
        unclassified = copy.deepcopy(self.policy)
        unclassified["files"] = []
        with tempfile.TemporaryDirectory(prefix="complete99-science-unclassified-") as temp:
            temp_root = Path(temp)
            source = self.make_minimal_source(temp_root)
            policy_path = self.write_policy(temp_root, unclassified)
            (source / "assets" / "images" / "science" / "unclassified.webp").write_bytes(
                b"unclassified"
            )
            for module in (BUILD, VALIDATE):
                with self.subTest(module=module.__name__):
                    with self.assertRaises(SystemExit) as caught:
                        module.science_media_policy_contract(
                            "1.22.0",
                            source_root=source,
                            policy_path=policy_path,
                        )
                    self.assertIn(
                        "missing or unclassified source files",
                        str(caught.exception),
                    )

    def test_missing_source_file_fails_in_both_consumers(self) -> None:
        with tempfile.TemporaryDirectory(prefix="complete99-science-missing-") as temp:
            temp_root = Path(temp)
            source = self.make_minimal_source(temp_root)
            for module in (BUILD, VALIDATE):
                with self.subTest(module=module.__name__):
                    with self.assertRaises(SystemExit) as caught:
                        module.science_media_policy_contract(
                            "1.22.0",
                            source_root=source,
                            policy_path=POLICY_PATH,
                        )
                    self.assertIn("source is missing", str(caught.exception))

    def test_science_source_inventory_rejects_special_entries(self) -> None:
        for module in (BUILD, VALIDATE):
            with self.subTest(module=module.__name__):
                with tempfile.TemporaryDirectory(prefix="complete99-science-special-") as temp:
                    source = Path(temp)
                    science = source / "assets" / "images" / "science"
                    science.mkdir(parents=True)
                    (science / "nested").mkdir()
                    with self.assertRaises(SystemExit) as caught:
                        module.science_source_inventory(source)
                    self.assertIn("indirect or special", str(caught.exception))

    def test_science_source_inventory_rejects_symlink_escape(self) -> None:
        with tempfile.TemporaryDirectory(prefix="complete99-science-link-") as temp:
            source = Path(temp) / "source"
            science = source / "assets" / "images" / "science"
            science.mkdir(parents=True)
            outside = Path(temp) / "outside.webp"
            outside.write_bytes(b"outside")
            link = science / "c99-science-link-v01.webp"
            try:
                os.symlink(outside, link)
            except (OSError, NotImplementedError):
                self.skipTest("Local platform does not permit symlink creation")
            for module in (BUILD, VALIDATE):
                with self.subTest(module=module.__name__):
                    with self.assertRaises(SystemExit) as caught:
                        module.science_source_inventory(source)
                    self.assertIn("indirect or special", str(caught.exception))

    def test_integrity_projection_is_exact_and_bounded(self) -> None:
        expected = VALIDATE.expected_science_media_integrity_fields(
            self.validator_contract
        )
        VALIDATE.validate_science_media_integrity_fields(dict(expected), self.validator_contract)

        drifted = dict(expected)
        drifted["science_media_policy_sha256"] = "0" * 64
        with self.assertRaises(SystemExit) as caught:
            VALIDATE.validate_science_media_integrity_fields(
                drifted,
                self.validator_contract,
            )
        self.assertIn("integrity field drifted", str(caught.exception))

        unbounded = dict(expected)
        unbounded["science_media_policy_records"] = []
        with self.assertRaises(SystemExit) as caught:
            VALIDATE.validate_science_media_integrity_fields(
                unbounded,
                self.validator_contract,
            )
        self.assertIn("not bounded", str(caught.exception))

    def test_archive_gate_rejects_tamper_case_collision_and_similar_prefix(self) -> None:
        relative = "assets/images/science/c99-science-probe-v01.webp"
        contents = b"approved"
        contract = {
            "delivery_receipts": {
                relative: (len(contents), hashlib.sha256(contents).hexdigest())
            }
        }
        with tempfile.TemporaryDirectory(prefix="complete99-science-zip-") as temp:
            artifact = Path(temp) / "probe.zip"

            with zipfile.ZipFile(artifact, "w") as archive:
                archive.writestr(f"{VALIDATE.SLUG}/{relative}", contents)
            with zipfile.ZipFile(artifact) as archive:
                VALIDATE.validate_science_media_archive(archive, contract)

            with zipfile.ZipFile(artifact, "w") as archive:
                archive.writestr(
                    f"{VALIDATE.SLUG}/assets/images/Science/c99-science-probe-v01.webp",
                    contents,
                )
            with zipfile.ZipFile(artifact) as archive:
                with self.assertRaises(SystemExit):
                    VALIDATE.validate_science_media_archive(archive, contract)

            with zipfile.ZipFile(artifact, "w") as archive:
                archive.writestr(
                    f"{VALIDATE.SLUG}/assets/images/science-private/probe.webp",
                    contents,
                )
            with zipfile.ZipFile(artifact) as archive:
                with self.assertRaises(SystemExit):
                    VALIDATE.validate_science_media_archive(archive, contract)

            with zipfile.ZipFile(artifact, "w") as archive:
                archive.writestr(f"{VALIDATE.SLUG}/{relative}", b"tampered")
            with zipfile.ZipFile(artifact) as archive:
                with self.assertRaises(SystemExit) as caught:
                    VALIDATE.validate_science_media_archive(archive, contract)
                self.assertIn("receipt drifted", str(caught.exception))

            with zipfile.ZipFile(artifact, "w") as archive:
                archive.writestr(f"{VALIDATE.SLUG}/{relative}", contents)
                archive.writestr(
                    f"{VALIDATE.SLUG}/assets/images/Science/c99-science-probe-v01.webp",
                    contents,
                )
            with zipfile.ZipFile(artifact) as archive:
                with self.assertRaises(SystemExit) as caught:
                    VALIDATE.validate_science_media_archive(archive, contract)
                self.assertIn("case-colliding", str(caught.exception))


if __name__ == "__main__":
    unittest.main()
