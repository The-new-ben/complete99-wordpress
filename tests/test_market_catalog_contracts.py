import json
import subprocess
import unittest
from pathlib import Path
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[1]
SEEDS = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "data"
    / "catalog-product-seeds.php"
)


def load_registry():
    path = SEEDS.as_posix().replace("'", "\\'")
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


class MarketCatalogContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.registry = load_registry()
        cls.products = cls.registry["products"]
        cls.by_code = {item["product_code"]: item for item in cls.products}

    def test_registry_is_private_evaluation_only(self):
        self.assertEqual(
            "complete99-catalog-product-seeds/v1", self.registry["schema"]
        )
        self.assertEqual("2026-07-31", self.registry["registry_reviewed_at"])
        self.assertEqual("ILS", self.registry["currency"])
        self.assertEqual("private_market_benchmark", self.registry["price_scope"])
        self.assertEqual(1, self.registry["stock_policy"]["evaluation_quantity"])
        self.assertFalse(self.registry["stock_policy"]["public_stock_claim"])
        self.assertFalse(self.registry["stock_policy"]["public_sale"])

    def test_all_26_products_have_unique_price_stock_and_sources(self):
        self.assertEqual(26, len(self.products))
        self.assertEqual(26, len(self.by_code))
        for product in self.products:
            with self.subTest(product=product["product_code"]):
                self.assertRegex(
                    product["product_code"], r"^product-[a-z0-9]+(?:-[a-z0-9]+)*$"
                )
                self.assertRegex(
                    product["ingredient_code"],
                    r"^ingredient-[a-z0-9]+(?:-[a-z0-9]+)*$",
                )
                self.assertGreater(product["evaluation_price_ils"], 0)
                self.assertEqual(1, product["evaluation_stock"])
                self.assertEqual(
                    "private_evaluation_only",
                    product["evaluation_stock_scope"],
                )
                self.assertFalse(product["public_sale_eligible"])
                self.assertEqual("held_until_acceptance", product["sale_state"])
                self.assertTrue(product["name"]["he"])
                self.assertTrue(product["name"]["en"])
                observation = product["market_observation"]
                parsed = urlparse(observation["source_url"])
                self.assertEqual("https", parsed.scheme)
                self.assertTrue(parsed.hostname)
                self.assertEqual("2026-07-31", observation["checked_at"])
                self.assertGreater(observation["observed_price_ils"], 0)
                self.assertGreaterEqual(
                    observation["range_high_ils"], observation["range_low_ils"]
                )
                self.assertTrue(product["acceptance_gates"])
                self.assertFalse(any(product["acceptance_gates"].values()))

    def test_researched_reference_prices_are_exact(self):
        expected = {
            "product-tahini-500g": 12.90,
            "product-eggs-l-12": 14.24,
            "product-chicken-breast-1kg": 44.90,
            "product-ground-beef-1kg": 64.90,
            "product-olive-oil-750ml": 46.90,
            "product-chicken-liver-1kg": 24.90,
        }
        for code, price in expected.items():
            with self.subTest(product=code):
                self.assertAlmostEqual(price, self.by_code[code]["evaluation_price_ils"])

    def test_sensitive_foods_are_not_resale_candidates(self):
        sensitive = {
            "product-chicken-breast-1kg",
            "product-ground-beef-1kg",
            "product-tilapia-fillet-1kg",
            "product-beef-shank-1kg",
            "product-chicken-liver-1kg",
        }
        for code in sensitive:
            with self.subTest(product=code):
                product = self.by_code[code]
                self.assertEqual(
                    "chilled_or_frozen_sensitive", product["classification"]
                )
                self.assertFalse(product["resale_candidate"])
                self.assertIn("cold_chain", product["acceptance_gates"])
                self.assertFalse(product["acceptance_gates"]["cold_chain"])

    def test_candidate_links_never_become_verified_links(self):
        cases = {
            "product-parsley-100g": "ingredient-herbs-unspecified",
            "product-ground-beef-1kg": "ingredient-meat-unspecified",
            "product-tilapia-fillet-1kg": "ingredient-fish",
            "product-beef-shank-1kg": "ingredient-beef",
        }
        for code, candidate in cases.items():
            with self.subTest(product=code):
                relations = self.by_code[code]["relations"]
                self.assertIn(candidate, relations["candidate_ingredient_codes"])
                self.assertNotIn(candidate, relations["verified_ingredient_codes"])

    def test_bound_evaluation_images_exist_but_remain_held(self):
        generated = (
            ROOT
            / "plugin"
            / "complete99-platform"
            / "assets"
            / "images"
            / "generated"
        )
        bound = [item for item in self.products if item["image_asset"]]
        self.assertEqual(26, len(bound))
        for product in bound:
            with self.subTest(product=product["product_code"]):
                self.assertEqual(
                    "evaluation_asset_held_for_review", product["image_state"]
                )
                self.assertTrue((generated / product["image_asset"]).is_file())

    def test_no_em_dash_or_public_availability_claims(self):
        source = SEEDS.read_text(encoding="utf-8")
        self.assertNotIn("\u2014", source)
        self.assertNotIn("'public_sale_eligible'       => true", source)
        self.assertNotIn("'public_stock_claim'  => true", source)

    def test_release_builder_keeps_png_sources_out_of_plugin_zip(self):
        builder = (
            ROOT / "scripts" / "build-plugin-zip.py"
        ).read_text(encoding="utf-8")
        self.assertIn('GENERATED_SOURCE_ROOT = PurePath("assets/images/generated")', builder)
        self.assertIn('relative.suffix.casefold() == ".png"', builder)


if __name__ == "__main__":
    unittest.main()
