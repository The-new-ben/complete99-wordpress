import hashlib
import json
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
MANIFEST = PLUGIN / "data" / "generated-asset-manifest.php"
ASSETS = PLUGIN / "assets" / "images" / "science"

EXPECTED = {
    "region-syria-aleppo": (
        "c99-science-syrian-aleppo-table-v01",
        "region-syria-aleppo-source.png",
        "90e354e00d4a5e63f661f162284b1b5d0dc21fcada53e2f34d5f937fdd784042",
    ),
    "hub-aleppine-kibbeh-family": (
        "c99-science-aleppine-kibbeh-family-v01",
        "hub-aleppine-kibbeh-family-source.png",
        "8ce08587469c4d1c6f18f3cd399c41b5fbfb2f6cc72b23a3046bca167ac8fbdc",
    ),
    "ingredient-syrian-bulgur": (
        "c99-science-syrian-bulgur-v01",
        "ingredient-syrian-bulgur-source.png",
        "765a8b844ce6b12448e81e612a74cbd97e8a2e86506002260b1a29af833050a2",
    ),
    "ingredient-syrian-red-meat": (
        "c99-science-syrian-lamb-beef-family-v01",
        "ingredient-syrian-red-meat-source.png",
        "f2da86bc9b38544c42a1608103ad8b78294ab7385ee4bc93851f5e4716a8337e",
    ),
    "technique-syrian-bulgur-hydration": (
        "c99-science-syrian-bulgur-hydration-v01",
        "technique-syrian-bulgur-hydration-source.png",
        "0839e05df007410bc9ef224683241ac653da4cd35bafe2adc17ac84a12674ccb",
    ),
    "technique-syrian-kibbeh-cooking": (
        "c99-science-syrian-kibbeh-cooking-v01",
        "technique-syrian-kibbeh-cooking-source.png",
        "0c062a2d04ae4a0307f3af4366869f63e295d64bc99d10712fc016627693f0c3",
    ),
    "tradition-aleppan-jewish-foodways": (
        "c99-science-aleppan-jewish-foodways-v01",
        "tradition-aleppan-jewish-foodways-source-v2.png",
        "e3e3da6e28de043fa5f97e3803441c8e84798fab3ac8b810d70cccfe90c35418",
    ),
}


def load_manifest():
    manifest_path = MANIFEST.as_posix().replace("'", "\\'")
    completed = subprocess.run(
        [
            "php",
            "-r",
            (
                "define('ABSPATH', __DIR__);"
                f"$data=require '{manifest_path}';"
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


def syrian_assets():
    return [
        asset
        for asset in load_manifest()["science_assets"]
        if asset["related_entity_code"] in EXPECTED
    ]


def test_seven_syrian_editorial_assets_have_exact_entity_bindings():
    manifest = load_manifest()
    assert manifest["science_asset_schema"] == (
        "complete99-generated-science-asset-manifest/v1"
    )
    assert manifest["science_reviewed_at"] == "2026-08-08"
    assets = syrian_assets()
    assert len(assets) == 7
    assert {asset["related_entity_code"] for asset in assets} == set(EXPECTED)

    for asset in assets:
        entity_id = asset["related_entity_code"]
        stem, source_name, source_digest = EXPECTED[entity_id]
        assert asset["slug"] == stem
        assert asset["generation_source_filename"] == source_name
        assert asset["source_sha256"] == source_digest
        assert asset["related_entity_codes"] == [entity_id]
        assert asset["related_product_code"] == ""
        assert asset["related_product_codes"] == []
        assert asset["review_state"] == "evaluation"
        assert asset["usage_state"] == "held"
        assert asset["presentation_scope"] == "illustrative_evaluation_only"
        assert asset["actual_product_presentation"] is False
        assert asset["generation_reviewed_at"] == "2026-08-08"
        assert asset["provenance"] == "openai-imagegen-session"
        assert asset["generation_interface"] == "built-in-image_gen"
        assert asset["generation_model"] == "openai-imagegen"
        assert asset["prompt_record_type"] == (
            "reviewed_generation_specification_pending_owner_publication_receipt"
        )
        assert asset["publication_approval_state"] == (
            "held_pending_owner_approval"
        )
        assert asset["publication_approval_receipt_id"] == ""
        assert asset["prompt_en"].strip()
        assert asset["negative_prompt_en"].strip()
        assert asset["alt"]["he"].strip()
        assert asset["alt"]["en"].strip()
        assert asset["visual_caveat"]["he"].strip()
        assert asset["visual_caveat"]["en"].strip()


def test_every_registered_file_has_exact_bytes_hash_and_signature():
    assets = syrian_assets()
    expected_names = set()
    for asset in assets:
        assert set(asset["files"]) == {
            "png",
            "webp",
            "avif",
            "webp_768",
            "avif_768",
        }
        for key, record in asset["files"].items():
            path = PLUGIN / record["relative_path"]
            expected_names.add(path.name)
            assert path.is_file(), path
            assert path.stat().st_size == record["bytes"]
            assert sha256(path) == record["sha256"]
            expected_dimensions = (768, 512) if key.endswith("_768") else (1536, 1024)
            assert (record["width"], record["height"]) == expected_dimensions

            header = path.read_bytes()[:16]
            if key == "png":
                assert header[:8] == b"\x89PNG\r\n\x1a\n"
            elif key.startswith("webp"):
                assert header[:4] == b"RIFF"
                assert header[8:12] == b"WEBP"
            else:
                assert header[4:12] == b"ftypavif"

        receipt = asset["public_delivery_receipt"]
        full_webp = asset["files"]["webp"]
        assert receipt["filename"] == full_webp["filename"]
        assert receipt["relative_path"] == full_webp["relative_path"]
        assert receipt["sha256"] == full_webp["sha256"]
        assert receipt["bytes"] == full_webp["bytes"]
        assert asset["rights_receipt_digest"] == ""

    present_names = {
        path.name
        for path in ASSETS.iterdir()
        if any(path.name.startswith(stem) for stem, _, _ in EXPECTED.values())
    }
    assert present_names == expected_names


def test_project_pngs_are_the_preserved_generation_sources():
    assets = syrian_assets()
    for asset in assets:
        png = asset["files"]["png"]
        assert png["sha256"] == asset["source_sha256"]
        assert png["bytes"] == asset["source_bytes"]
        assert png["relative_path"] == asset["source_relative_path"]
