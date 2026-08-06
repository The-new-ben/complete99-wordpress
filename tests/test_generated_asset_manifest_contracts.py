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
LIVE_CATALOG = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "data"
    / "live-catalog-products.php"
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


def load_live_product_codes():
    path = LIVE_CATALOG.as_posix().replace("'", "\\'")
    completed = subprocess.run(
        [
            "php",
            "-r",
            (
                "define('ABSPATH', __DIR__);"
                f"$data=require '{path}';"
                "echo json_encode(array_keys($data['products']), JSON_UNESCAPED_SLASHES);"
            ),
        ],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
    )
    return set(json.loads(completed.stdout))


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
        cls.live_product_codes = load_live_product_codes()

    def test_manifest_covers_exactly_54_source_and_delivery_pairs(self):
        self.assertEqual(
            "complete99-generated-asset-manifest/v1", self.manifest["schema"]
        )
        self.assertEqual("2026-08-06", self.manifest["reviewed_at"])
        self.assertEqual(54, len(self.assets))

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

    def test_assets_are_bilingual_and_catalog_images_have_normal_public_treatment(self):
        slugs = set()
        stable_slugs = set()
        public_product_codes = set()
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
                self.assertTrue(asset["label"]["en"].strip())
                self.assertRegex(asset["alt"]["he"], r"[\u0590-\u05ff]")
                self.assertTrue(asset["alt"]["en"].strip())
                self.assertEqual("openai-imagegen", asset["generation_model"])
                self.assertEqual("2026-08-06", asset["generation_reviewed_at"])
                self.assertFalse(asset["actual_product_presentation"])

                if asset["usage_state"] == "public":
                    self.assertEqual("owner_approved", asset["review_state"])
                    self.assertEqual(
                        "public_catalog_illustration",
                        asset["presentation_scope"],
                    )
                    self.assertIn(
                        asset["owner_authorized_at"],
                        {"2026-07-31", "2026-08-06"},
                    )
                    self.assertEqual({"he": "", "en": ""}, asset["visual_caveat"])
                    self.assertEqual([], asset["visual_caveats"])
                    self.assertNotIn("archive", asset["filename"].lower())
                    self.assertEqual(1, len(asset["related_product_codes"]))
                    public_product_codes.add(asset["related_product_codes"][0])
                else:
                    self.assertRegex(
                        asset["visual_caveat"]["he"], r"[\u0590-\u05ff]"
                    )
                    self.assertTrue(asset["visual_caveat"]["en"].strip())
                    self.assertEqual("evaluation", asset["review_state"])
                    self.assertEqual("held", asset["usage_state"])
                    self.assertEqual(
                        "illustrative_evaluation_only",
                        asset["presentation_scope"],
                    )

        self.assertEqual(30, len(public_product_codes))
        self.assertEqual(self.live_product_codes, public_product_codes)

        by_stable_slug = {asset["stable_slug"]: asset for asset in self.assets}
        for stable_slug in ("kioke-shoyu-500ml", "kito-yuzu-juice-100ml"):
            with self.subTest(asset=stable_slug):
                asset = by_stable_slug[stable_slug]
                self.assertTrue(asset["prompt_en"].strip())
                self.assertTrue(asset["negative_prompt_en"].strip())

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
