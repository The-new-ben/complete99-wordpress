import hashlib
import json
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
MANIFEST = PLUGIN / "data" / "generated-asset-manifest.php"
VISUAL_DOC = ROOT / "docs" / "culinary-science-visual-assets.md"
RIGHTS_DOC = ROOT / "docs" / "media-rights-register.md"

EXPECTED = {
    "ingredient-shoyu-koji": {
        "stem": "c99-science-shoyu-koji-substrate-v01",
        "source": "5cff891c9801e1bcd3c274284f82c580134a841246da444737d41058ca6b509a",
        "webp": "1213fee79dbd9dfe3d597aaddb0011e57ed0bc014fdd13a83a23ccdf478f1319",
    },
    "equipment-kioke": {
        "stem": "c99-science-kioke-wooden-barrel-v01",
        "source": "43508327f310be0c1875d3dd00a9d35daa0502152c659c9b706ff6fa81413ddd",
        "webp": "bafac22402602dbda38f512754a701a4388b262e89dda7e7cac68d6dd2616a23",
    },
    "guide-koji-hydrolysis": {
        "stem": "c99-science-koji-enzymes-hydrolysis-guide-v01",
        "source": "69fa18417864c71b7d2b95c30c9febacb87d7430e8da6155b5484b05c307e06a",
        "webp": "6bb6f6becd75c7e4fdeed3e76f70e616ed6fc8713b94163475f585c4ac0d1a77",
    },
    "reaction-koji-enzymatic-hydrolysis": {
        "stem": "c99-science-koji-enzymatic-hydrolysis-v01",
        "source": "737f0c74b8f0abce9921231e2c9185d73d33a74c836c029452085724e5e7a357",
        "webp": "5978859d2161cbb6a41daddf52cc7402952a63068b9a0c0f684166237eee66a5",
    },
    "standard-jas-shoyu-1703": {
        "stem": "c99-science-jas-1703-shoyu-standard-v01",
        "source": "4f0781828ba648106456db31012c6c664ed633104deea972493020925ba80672",
        "webp": "5a099802acabf8e704a03e5b254844519d2e32df0d8358d277b8594889e16ebf",
    },
}


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


def japanese_assets():
    return [
        asset
        for asset in load_manifest()["science_assets"]
        if asset["related_entity_code"] in EXPECTED
    ]


def test_exact_five_japanese_editorial_assets_are_separate_and_held():
    manifest = load_manifest()
    assert len(manifest["assets"]) == 60
    assert len(manifest["science_assets"]) == 12
    assets = japanese_assets()
    assert len(assets) == 5
    assert {asset["related_entity_code"] for asset in assets} == set(EXPECTED)

    for asset in assets:
        expected = EXPECTED[asset["related_entity_code"]]
        assert asset["slug"] == expected["stem"]
        assert asset["source_sha256"] == expected["source"]
        assert asset["sha256"] == expected["webp"]
        assert asset["related_entity_codes"] == [asset["related_entity_code"]]
        assert asset["related_product_codes"] == []
        assert asset["asset_type"] == "science_editorial"
        assert asset["review_state"] == "evaluation"
        assert asset["usage_state"] == "held"
        assert asset["presentation_scope"] == "illustrative_evaluation_only"
        assert asset["actual_product_presentation"] is False
        assert asset["generation_interface"] == "built-in-image_gen"
        assert asset["generation_model"] == "openai-imagegen"
        assert asset["prompt_record_type"] == (
            "reviewed_generation_specification_pending_owner_publication_receipt"
        )
        assert asset["publication_approval_state"] == (
            "held_pending_owner_approval"
        )
        assert asset["publication_approval_receipt_id"] == ""
        assert asset["conversion"] == {
            "engine": "ImageMagick 7",
            "webp_quality": 68,
            "avif_quality": 40,
            "responsive": "Lanczos 768x512",
        }
        assert asset["alt"]["he"].strip()
        assert asset["alt"]["en"].strip()
        assert asset["visual_caveat"]["he"].strip()
        assert asset["visual_caveat"]["en"].strip()


def test_all_twenty_five_japanese_files_match_receipts_and_dimensions():
    for asset in japanese_assets():
        assert set(asset["files"]) == {
            "png",
            "webp",
            "avif",
            "webp_768",
            "avif_768",
        }
        for kind, receipt in asset["files"].items():
            path = PLUGIN / receipt["relative_path"]
            assert path.is_file(), path
            assert path.stat().st_size == receipt["bytes"]
            assert sha256(path) == receipt["sha256"]
            expected_size = (768, 512) if kind.endswith("_768") else (1536, 1024)
            assert (receipt["width"], receipt["height"]) == expected_size

            signature = path.read_bytes()[:16]
            if kind == "png":
                assert signature[:8] == b"\x89PNG\r\n\x1a\n"
            elif kind.startswith("webp"):
                assert signature[:4] == b"RIFF"
                assert signature[8:12] == b"WEBP"
            else:
                assert signature[4:12] == b"ftypavif"

        full_webp = asset["files"]["webp"]
        receipt = asset["public_delivery_receipt"]
        assert receipt["sha256"] == full_webp["sha256"]
        assert receipt["bytes"] == full_webp["bytes"]
        assert asset["rights_receipt_digest"] == ""
        assert asset["files"]["png"]["sha256"] == asset["source_sha256"]
        assert asset["files"]["png"]["bytes"] == asset["source_bytes"]


def test_generated_visuals_do_not_claim_product_or_certification_truth():
    banned = ("actual product", "certified product", "laboratory result", "tested lot")
    for asset in japanese_assets():
        combined = " ".join(
            (
                asset["prompt_en"],
                asset["negative_prompt_en"],
                asset["visual_caveat"]["en"],
            )
        ).lower()
        assert all(claim not in combined for claim in banned)
        assert "not a specific product" in combined or "not a particular" in combined or "not an exact" in combined or "no product" in combined or "not a molecular" in combined


def test_every_receipt_and_agent_review_boundary_is_documented():
    visual = VISUAL_DOC.read_text(encoding="utf-8")
    rights = RIGHTS_DOC.read_text(encoding="utf-8")
    assert "019faa9f-cb38-7c22-9bd5-9fcdf3d37b3b" in visual
    assert "not public media" in visual
    assert "does not authorize public use" in rights
    for asset in japanese_assets():
        assert asset["slug"] in visual
        assert asset["source_sha256"] in visual
        assert asset["sha256"] in visual
        assert asset["sha256"] in rights
        for receipt in asset["files"].values():
            assert receipt["filename"] in visual
            assert receipt["sha256"] in visual
