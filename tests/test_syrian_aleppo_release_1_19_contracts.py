from __future__ import annotations

import hashlib
import json
import subprocess
import zipfile
from pathlib import Path, PurePosixPath

import pytest


ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "plugin-dist"
FROZEN_ARTIFACT = DIST / "complete99-platform-1.19.0.zip"
FROZEN_CHECKSUM = DIST / "complete99-platform-1.19.0.zip.sha256"
FROZEN_INTEGRITY = DIST / "complete99-platform-1.19.0-integrity.json"
BUILD_SCRIPT = ROOT / "scripts" / "build-plugin-zip.py"

FROZEN_ARTIFACT_SHA256 = (
    "41934b671178c16ed36eea58b4fc3b1b23968e85e86f7b60b918e452043ef95e"
)
FROZEN_CHECKSUM_SHA256 = (
    "b92b17cd8a5c2b93e8f86a1c90b4ca2382adf8c4b055b2b81ff016b04cf526c6"
)
FROZEN_INTEGRITY_SHA256 = (
    "25ae840e2c731128539c5638b8b0c13727e07b8ec854c7ddadc79c4db5b0e652"
)

SCIENCE_VERSION = "culinary-science-2026.08.08.v19"
COMMERCE_VERSION = "culinary-commerce-2026.08.08.v13"

SYRIAN_PUBLIC_IDS = {
    "cuisine-syrian-regional",
    "region-syria-aleppo",
    "hub-aleppine-kibbeh-family",
    "ingredient-syrian-bulgur",
    "ingredient-syrian-red-meat",
    "technique-syrian-bulgur-hydration",
    "technique-syrian-kibbeh-cooking",
    "tradition-aleppan-jewish-foodways",
}

NEW_PUBLIC_IDS = SYRIAN_PUBLIC_IDS - {"cuisine-syrian-regional"}

PUBLIC_RELATION_IDS = {
    "edge-cuisine-syrian-regional-contains-1",
    "edge-hub-aleppine-kibbeh-family-part_of-1",
    "edge-hub-aleppine-kibbeh-family-requires-2",
    "edge-hub-aleppine-kibbeh-family-requires-3",
    "edge-technique-syrian-bulgur-hydration-requires-2",
    "edge-technique-syrian-kibbeh-cooking-requires-2",
    "edge-technique-syrian-kibbeh-cooking-requires-3",
}

CANONICAL_PATHS = {
    "region-syria-aleppo": {
        "he": "/museum/syrian-culinary-science/aleppo/",
        "en": "/en/museum/syrian-culinary-science/aleppo/",
    },
    "hub-aleppine-kibbeh-family": {
        "he": "/museum/syrian-culinary-science/aleppo/aleppine-kibbeh-family/",
        "en": "/en/museum/syrian-culinary-science/aleppo/aleppine-kibbeh-family/",
    },
    "ingredient-syrian-bulgur": {
        "he": "/ingredients/syrian-bulgur/",
        "en": "/en/ingredients/syrian-bulgur/",
    },
    "ingredient-syrian-red-meat": {
        "he": "/ingredients/lamb-and-beef-in-syrian-cooking/",
        "en": "/en/ingredients/lamb-and-beef-in-syrian-cooking/",
    },
    "technique-syrian-bulgur-hydration": {
        "he": "/knowledge/how-to-hydrate-bulgur-for-kibbeh/",
        "en": "/en/knowledge/how-to-hydrate-bulgur-for-kibbeh/",
    },
    "technique-syrian-kibbeh-cooking": {
        "he": "/knowledge/how-to-cook-kibbeh-safely/",
        "en": "/en/knowledge/how-to-cook-kibbeh-safely/",
    },
    "tradition-aleppan-jewish-foodways": {
        "he": "/traditions/aleppan-jewish-foodways/",
        "en": "/en/traditions/aleppan-jewish-foodways/",
    },
}

SEMANTIC_ALLOWLISTS = {
    "cuisine-syrian-regional": {
        "museum-culinary-science",
        "cuisine-lebanese-regional",
        "region-syria-aleppo",
        "hub-aleppine-kibbeh-family",
        "ingredient-syrian-bulgur",
        "ingredient-syrian-red-meat",
        "technique-syrian-bulgur-hydration",
        "technique-syrian-kibbeh-cooking",
        "tradition-aleppan-jewish-foodways",
    },
    "region-syria-aleppo": {
        "cuisine-syrian-regional",
        "hub-aleppine-kibbeh-family",
        "ingredient-syrian-bulgur",
        "ingredient-syrian-red-meat",
        "technique-syrian-bulgur-hydration",
        "technique-syrian-kibbeh-cooking",
        "tradition-aleppan-jewish-foodways",
    },
    "hub-aleppine-kibbeh-family": {
        "cuisine-syrian-regional",
        "region-syria-aleppo",
        "ingredient-syrian-bulgur",
        "ingredient-syrian-red-meat",
        "technique-syrian-bulgur-hydration",
        "technique-syrian-kibbeh-cooking",
    },
    "ingredient-syrian-bulgur": {
        "cuisine-syrian-regional",
        "region-syria-aleppo",
        "hub-aleppine-kibbeh-family",
        "technique-syrian-bulgur-hydration",
        "technique-syrian-kibbeh-cooking",
    },
    "ingredient-syrian-red-meat": {
        "cuisine-syrian-regional",
        "region-syria-aleppo",
        "hub-aleppine-kibbeh-family",
        "technique-syrian-kibbeh-cooking",
    },
    "technique-syrian-bulgur-hydration": {
        "cuisine-syrian-regional",
        "region-syria-aleppo",
        "hub-aleppine-kibbeh-family",
        "ingredient-syrian-bulgur",
        "technique-syrian-kibbeh-cooking",
    },
    "technique-syrian-kibbeh-cooking": {
        "cuisine-syrian-regional",
        "region-syria-aleppo",
        "hub-aleppine-kibbeh-family",
        "ingredient-syrian-bulgur",
        "ingredient-syrian-red-meat",
        "technique-syrian-bulgur-hydration",
    },
    "tradition-aleppan-jewish-foodways": {
        "cuisine-syrian-regional",
        "region-syria-aleppo",
    },
}

SCIENCE_ASSET_SHAS = {
    "region-syria-aleppo": "12be0704e4211ec5b39686ff291ee1b1c162cde2d93e6fc66b83c3316c1fd6c6",
    "hub-aleppine-kibbeh-family": "ad8b1b3fdfc59eb22b7e06e443b1477ec152ab4f00985d44533ff7245d1f8d5b",
    "ingredient-syrian-bulgur": "0abf067b8a84796b002103da6839b549c47adf37bd7821d35b5c0a66b15ab620",
    "ingredient-syrian-red-meat": "e87dc0d2d74bffe2b48156838a11f704fe35d0480380bd965b186df0affb67db",
    "technique-syrian-bulgur-hydration": "762a91a1a3c00483b1c5a572f17b4659a328ee86cf832a281ef360aace6eecd1",
    "technique-syrian-kibbeh-cooking": "c28467bb07a6900d22618b17791ae6c5eefee7d73bf3c5f1a75b4d4661db2d03",
    "tradition-aleppan-jewish-foodways": "1160071fd8b80a9d11ad2792ed8b53643e2b086a4db3d81772ddedf98bba2797",
}

NEW_SOURCE_IDS = {
    "unesco-ancient-city-aleppo",
    "georgetown-making-levantine-cuisine",
    "simon-schuster-aleppo-cookbook",
    "bulgur-hydration-cereal-chemistry",
}


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


@pytest.fixture(scope="module")
def frozen_plugin(tmp_path_factory: pytest.TempPathFactory) -> Path:
    extraction_root = tmp_path_factory.mktemp("complete99-1.19.0")
    with zipfile.ZipFile(FROZEN_ARTIFACT) as archive:
        names = archive.namelist()
        assert names
        for name in names:
            path = PurePosixPath(name)
            assert path.parts[0] == "complete99-platform"
            assert ".." not in path.parts
            assert "\\" not in name
        archive.extractall(extraction_root)
    plugin = extraction_root / "complete99-platform"
    assert plugin.is_dir()
    return plugin


@pytest.fixture(scope="module")
def release_payload(frozen_plugin: Path) -> dict:
    script = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '__PLUGIN__/');
$science = require COMPLETE99_PLATFORM_DIR . 'data/culinary-science-pilot.php';
$commerce = require COMPLETE99_PLATFORM_DIR . 'data/culinary-commerce-pilot.php';
$assets = require COMPLETE99_PLATFORM_DIR . 'data/generated-asset-manifest.php';
$relations = require COMPLETE99_PLATFORM_DIR . 'data/live-catalog-relations.php';
$prices = require COMPLETE99_PLATFORM_DIR . 'data/live-catalog-prices.php';

$public_ids = array();
$owner_ids = array();
$route_count = 0;
$indexable_count = 0;
$syrian_ids = array();
$syrian_public_ids = array();
$public_relation_ids = array();
$semantic = array();
$canonicals = array();
$syrian_offer_ids = array();
$public_mesh_links = array();
$mesh = array();

foreach ($science['entities'] as $entity) {
    $is_public = !empty($entity['publication']['public_api'])
        && !empty($entity['publication']['public_page']);
    $is_syrian = 'cluster-syrian-regional-cuisine' === $entity['seo']['cluster_id'];

    if ($is_public) {
        $public_ids[] = $entity['id'];
        if ('standalone' === $entity['seo']['route_mode']) {
            $owner_ids[] = $entity['id'];
            foreach ($entity['seo']['canonical_path'] as $path) {
                if (is_string($path) && '' !== $path) {
                    ++$route_count;
                }
            }
        }
        if (!empty($entity['publication']['search_index'])) {
            ++$indexable_count;
        }
    }

    if ($is_syrian) {
        $syrian_ids[] = $entity['id'];
        if ($is_public) {
            $syrian_public_ids[] = $entity['id'];
            $semantic[$entity['id']] = $entity['seo']['semantic_entity_ids'];
            $canonicals[$entity['id']] = $entity['seo']['canonical_path'];
            if (!empty($entity['commerce']['public_offer_allowed'])) {
                $syrian_offer_ids[$entity['id']] = $entity['commerce']['woo_product_code'];
            }
            foreach ($entity['relations'] as $relation) {
                if (!empty($relation['public_safe'])) {
                    $public_relation_ids[] = $relation['id'];
                    if ('dish-kibbeh-meshwiyyeh' === $relation['target_id']) {
                        $public_mesh_links[] = $entity['id'] . ':relation';
                    }
                }
            }
            foreach ($entity['seo']['link_plan'] as $link) {
                if (!empty($link['public_safe'])
                    && 'dish-kibbeh-meshwiyyeh' === $link['target_id']) {
                    $public_mesh_links[] = $entity['id'] . ':link';
                }
            }
            if (in_array('dish-kibbeh-meshwiyyeh', $entity['seo']['semantic_entity_ids'], true)) {
                $public_mesh_links[] = $entity['id'] . ':semantic';
            }
        }
    }

    if ('dish-kibbeh-meshwiyyeh' === $entity['id']) {
        $mesh = array(
            'surface_class' => $entity['surface_class'],
            'publication' => $entity['publication'],
            'culinary_test_status' => $entity['review']['culinary_test_status'],
            'schema_type' => $entity['seo']['schema_type'],
        );
    }
}

$science_asset_shas = array();
$science_asset_states = array();
foreach ($assets['science_assets'] as $asset) {
    $entity_id = $asset['related_entity_code'];
    $science_asset_shas[$entity_id] = $asset['sha256'];
    $science_asset_states[$entity_id] = array(
        'asset_type' => $asset['asset_type'],
        'usage_state' => $asset['usage_state'],
        'actual_product_presentation' => $asset['actual_product_presentation'],
        'width' => $asset['width'],
        'height' => $asset['height'],
    );
}

$product_identity_ids = array_keys($relations['products']);
foreach ($commerce['products'] as $product) {
    $product_identity_ids[] = $product['id'];
}
$product_identity_ids = array_values(array_unique($product_identity_ids));

sort($public_ids);
sort($owner_ids);
sort($syrian_ids);
sort($syrian_public_ids);
sort($public_relation_ids);
sort($public_mesh_links);
sort($product_identity_ids);

echo json_encode(array(
    'science_version' => $science['version'],
    'science_generated_at' => $science['generated_at'],
    'science_entity_count' => count($science['entities']),
    'source_count' => count($science['sources']),
    'source_ids' => array_keys($science['sources']),
    'public_ids' => $public_ids,
    'owner_ids' => $owner_ids,
    'route_count' => $route_count,
    'indexable_count' => $indexable_count,
    'syrian_ids' => $syrian_ids,
    'syrian_public_ids' => $syrian_public_ids,
    'public_relation_ids' => $public_relation_ids,
    'semantic' => $semantic,
    'canonicals' => $canonicals,
    'syrian_offer_ids' => $syrian_offer_ids,
    'public_mesh_links' => $public_mesh_links,
    'mesh' => $mesh,
    'commerce_version' => $commerce['version'],
    'commerce_generated_at' => $commerce['generated_at'],
    'knowledge_registry_version' => $commerce['knowledge_registry_version'],
    'live_product_count' => count($relations['products']),
    'product_identity_count' => count($product_identity_ids),
    'private_planning_identity_count' => count(array_diff(
        $product_identity_ids,
        array_keys($relations['products'])
    )),
    'bulgur_relation' => $relations['products']['product-bulgur-fine-500g'],
    'bulgur_price' => $prices['prices']['product-bulgur-fine-500g'],
    'catalog_asset_count' => count($assets['assets']),
    'science_asset_count' => count($assets['science_assets']),
    'science_asset_shas' => $science_asset_shas,
    'science_asset_states' => $science_asset_states,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
""".replace("__PLUGIN__", _php_path(frozen_plugin))
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=45,
    )
    return json.loads(completed.stdout)


def test_frozen_artifact_receipts_are_byte_immutable() -> None:
    assert hashlib.sha256(FROZEN_ARTIFACT.read_bytes()).hexdigest() == (
        FROZEN_ARTIFACT_SHA256
    )
    assert hashlib.sha256(FROZEN_CHECKSUM.read_bytes()).hexdigest() == (
        FROZEN_CHECKSUM_SHA256
    )
    assert hashlib.sha256(FROZEN_INTEGRITY.read_bytes()).hexdigest() == (
        FROZEN_INTEGRITY_SHA256
    )

    assert FROZEN_CHECKSUM.read_text(encoding="ascii") == (
        f"{FROZEN_ARTIFACT_SHA256}  complete99-platform-1.19.0.zip\n"
    )
    integrity = json.loads(FROZEN_INTEGRITY.read_text(encoding="utf-8"))
    assert integrity == {
        "artifact": "complete99-platform-1.19.0.zip",
        "deployment_id": "c99-wp-1.19.0",
        "installed_sha256": (
            "3b501cd5d8bf51112e12b435c112feaa9d39d36141c0d977394299febf2b970a"
        ),
        "sha256": FROZEN_ARTIFACT_SHA256,
        "size": 33223233,
        "slug": "complete99-platform",
        "source_sha256": (
            "83fbda17d28d9ea50ecac1099651a00406428acd280952b953dafd6f8e364e67"
        ),
        "type": "plugin",
        "version": "1.19.0",
    }


def test_current_changelog_describes_frozen_1_19_without_claiming_it_was_live() -> None:
    source = BUILD_SCRIPT.read_text(encoding="utf-8")
    assert '"<h4>1.19.0</h4>"' in source
    assert (
        "The frozen 1.19.0 artifact encoded seven Syrian public/noindex "
        "candidates but was never owner-approved or deployed"
    ) in source
    assert (
        "Kept the science registry at 672 identities and Entity Studio at 728 "
        "subjects, expanded the source register to 374"
    ) in source
    assert (
        "Kept the untested kibbeh meshwiyyeh dish private and pending"
    ) in source


def test_release_identity_and_all_registry_envelopes_are_v19(
    frozen_plugin: Path,
) -> None:
    main = (frozen_plugin / "complete99-platform.php").read_text(encoding="utf-8")
    assert "* Version:     1.19.0" in main
    assert "define( 'COMPLETE99_PLATFORM_VERSION', '1.19.0' );" in main
    assert "define( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-wp-1.19.0' );" in main

    cuisines = frozen_plugin / "data" / "culinary-science" / "cuisines"
    cuisine_files = sorted(cuisines.glob("*.php"))
    assert len(cuisine_files) == 11
    for path in cuisine_files:
        source = path.read_text(encoding="utf-8")
        assert SCIENCE_VERSION in source, path.name
        assert "culinary-science-2026.08.07.v18" not in source, path.name


def test_release_registry_counts_and_public_ownership(release_payload: dict) -> None:
    assert release_payload["science_version"] == SCIENCE_VERSION
    assert release_payload["science_generated_at"] == "2026-08-08"
    assert release_payload["science_entity_count"] == 672
    assert release_payload["source_count"] == 374
    assert NEW_SOURCE_IDS <= set(release_payload["source_ids"])
    assert len(release_payload["public_ids"]) == 34
    assert len(release_payload["owner_ids"]) == 26
    assert release_payload["route_count"] == 52
    assert release_payload["indexable_count"] == 0


def test_release_syrian_boundary_is_exact(release_payload: dict) -> None:
    assert len(release_payload["syrian_ids"]) == 282
    assert set(release_payload["syrian_public_ids"]) == SYRIAN_PUBLIC_IDS
    assert len(release_payload["syrian_ids"]) - len(
        release_payload["syrian_public_ids"]
    ) == 274
    assert set(release_payload["public_relation_ids"]) == PUBLIC_RELATION_IDS
    assert {
        entity_id: set(targets)
        for entity_id, targets in release_payload["semantic"].items()
    } == SEMANTIC_ALLOWLISTS
    assert {
        entity_id: paths
        for entity_id, paths in release_payload["canonicals"].items()
        if entity_id in NEW_PUBLIC_IDS
    } == CANONICAL_PATHS


def test_pending_meshwiyyeh_cannot_leak_into_public_graph(
    release_payload: dict,
) -> None:
    mesh = release_payload["mesh"]
    assert mesh["surface_class"] == "editorial_draft"
    assert mesh["publication"]["state"] == "private_preview"
    assert mesh["publication"]["public_api"] is False
    assert mesh["publication"]["public_page"] is False
    assert mesh["publication"]["search_index"] is False
    assert mesh["culinary_test_status"] == "pending"
    assert mesh["schema_type"] == "Article"
    assert release_payload["public_mesh_links"] == []


def test_only_exact_bulgur_offer_is_connected(release_payload: dict) -> None:
    assert release_payload["commerce_version"] == COMMERCE_VERSION
    assert release_payload["commerce_generated_at"] == "2026-08-08"
    assert release_payload["knowledge_registry_version"] == SCIENCE_VERSION
    assert release_payload["live_product_count"] == 36
    assert release_payload["product_identity_count"] == 56
    assert release_payload["private_planning_identity_count"] == 20
    assert release_payload["syrian_offer_ids"] == {
        "ingredient-syrian-bulgur": "product-bulgur-fine-500g"
    }
    assert release_payload["bulgur_relation"]["science_entity_id"] == (
        "ingredient-syrian-bulgur"
    )
    assert release_payload["bulgur_price"] == "5.90"


def test_editorial_assets_are_separate_from_catalog_assets(
    release_payload: dict,
) -> None:
    assert release_payload["catalog_asset_count"] == 60
    assert release_payload["science_asset_count"] == 7
    assert release_payload["science_asset_shas"] == SCIENCE_ASSET_SHAS
    assert set(release_payload["science_asset_states"]) == NEW_PUBLIC_IDS
    for state in release_payload["science_asset_states"].values():
        assert state == {
            "asset_type": "science_editorial",
            "usage_state": "public",
            "actual_product_presentation": False,
            "width": 1536,
            "height": 1024,
        }
