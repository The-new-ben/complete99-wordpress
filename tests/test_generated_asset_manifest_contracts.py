import hashlib
import json
import re
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MANIFEST = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "data"
    / "generated-asset-manifest.php"
)
GENERATED = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "assets"
    / "images"
    / "generated"
)


def load_manifest():
    path = MANIFEST.as_posix().replace("'", "\\'")
    completed = subprocess.run(
        [
            "php",
            "-r",
            (
                "define('ABSPATH', __DIR__);"
                f"$data=require '{path}';"
                "echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);"
            ),
        ],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
    )
    return json.loads(completed.stdout)


def sha256(path):
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


class GeneratedAssetManifestContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.manifest = load_manifest()
        cls.assets = cls.manifest["assets"]

    def test_manifest_covers_exactly_50_source_and_delivery_pairs(self):
        self.assertEqual(
            "complete99-generated-asset-manifest/v1", self.manifest["schema"]
        )
        self.assertEqual("2026-07-31", self.manifest["reviewed_at"])
        self.assertEqual(50, len(self.assets))

        source_names = {asset["source_filename"] for asset in self.assets}
        delivery_names = {asset["filename"] for asset in self.assets}
        self.assertEqual(
            {path.name for path in GENERATED.glob("*.png")}, source_names
        )
        self.assertEqual(
            {path.name for path in GENERATED.glob("*.webp")}, delivery_names
        )
        self.assertEqual(
            {Path(name).stem for name in source_names},
            {Path(name).stem for name in delivery_names},
        )

    def test_hashes_paths_and_dimensions_are_exact(self):
        for asset in self.assets:
            with self.subTest(asset=asset["slug"]):
                source = GENERATED / asset["source_filename"]
                delivery = GENERATED / asset["filename"]
                self.assertEqual(asset["source_sha256"], sha256(source))
                self.assertEqual(asset["sha256"], sha256(delivery))
                self.assertEqual(
                    f"assets/images/generated/{source.name}",
                    asset["source_relative_path"],
                )
                self.assertEqual(
                    f"assets/images/generated/{delivery.name}",
                    asset["relative_path"],
                )
                self.assertGreaterEqual(asset["width"], 1024)
                self.assertGreaterEqual(asset["height"], 768)
                self.assertGreater(source.stat().st_size, 10_000)
                self.assertGreater(delivery.stat().st_size, 10_000)
                self.assertEqual(b"\x89PNG\r\n\x1a\n", source.read_bytes()[:8])
                self.assertEqual(b"RIFF", delivery.read_bytes()[:4])
                self.assertEqual(b"WEBP", delivery.read_bytes()[8:12])

    def test_assets_are_bilingual_held_and_not_product_claims(self):
        slugs = set()
        stable_slugs = set()
        for asset in self.assets:
            with self.subTest(asset=asset["slug"]):
                self.assertNotIn(asset["slug"], slugs)
                self.assertNotIn(asset["stable_slug"], stable_slugs)
                slugs.add(asset["slug"])
                stable_slugs.add(asset["stable_slug"])
                self.assertRegex(asset["slug"], r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
                self.assertRegex(
                    asset["stable_slug"], r"^[a-z0-9]+(?:-[a-z0-9]+)*$"
                )
                self.assertRegex(asset["label"]["he"], r"[\u0590-\u05ff]")
                self.assertRegex(asset["visual_caveat"]["he"], r"[\u0590-\u05ff]")
                self.assertTrue(asset["label"]["en"].strip())
                self.assertTrue(asset["visual_caveat"]["en"].strip())
                self.assertEqual("evaluation", asset["review_state"])
                self.assertEqual("held", asset["usage_state"])
                self.assertEqual(
                    "illustrative_evaluation_only",
                    asset["presentation_scope"],
                )
                self.assertFalse(asset["actual_product_presentation"])

    def test_manifest_has_no_local_paths_sessions_or_em_dash(self):
        source = MANIFEST.read_text(encoding="utf-8")
        self.assertNotIn("\u2014", source)
        self.assertNotRegex(source, re.compile(r"[A-Za-z]:[\\/]|generated_images"))
        self.assertNotRegex(
            source,
            re.compile(
                r"\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b",
                re.IGNORECASE,
            ),
        )


if __name__ == "__main__":
    unittest.main()
