from __future__ import annotations

import base64
import hashlib
import importlib.util
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path
from typing import Any
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


COMMERCE = load_module(
    "complete99_woocommerce_materialization",
    ROOT / "scripts" / "materialize-woocommerce.py",
)


DIGEST_A = "a" * 64
DIGEST_B = "b" * 64
DIGEST_C = "c" * 64
DIGEST_D = "d" * 64
TEST_DEPLOYMENT_ID = "c99-commerce-test-deployment"
TEST_MUTATION_ID = "12345678-1234-1234-1234-123456789abc"


def bridge_state() -> dict[str, Any]:
    return {
        "plugin": COMMERCE.WOOCOMMERCE_PLUGIN,
        "expected_version": COMMERCE.WOOCOMMERCE_VERSION,
        "header_version": COMMERCE.WOOCOMMERCE_VERSION,
        "active": True,
        "runtime_loaded": True,
        "runtime_version": COMMERCE.WOOCOMMERCE_VERSION,
        "product_post_type": True,
        "rest_namespace": True,
        "tree_file_count": 6194,
        "tree_bytes": 58583620,
        "tree_sha256": "420913d80fc318742815b98b7c41cc58a67e686cb72b389e00013c08fd0cca02",
        "install_recovery_marker": {"exists": False, "valid": False},
    }


def install_recovery_evidence(recovered: bool = False) -> dict[str, Any]:
    evidence = {
        "prior_marker": {
            "exists": recovered,
            "valid": recovered,
        },
        "target": {
            "configured": "/srv/wordpress/wp-content/plugins/woocommerce",
            "resolved": "/srv/wordpress/wp-content/plugins/woocommerce",
        },
        "cleanup": {
            "attempted": recovered,
            "verified": recovered,
        },
        "marker_cleared": True,
    }
    if recovered:
        evidence["prior_marker"].update(
            {
                "package_url": COMMERCE.WOOCOMMERCE_PACKAGE_URL,
                "package_sha256": COMMERCE.WOOCOMMERCE_PACKAGE_SHA256,
            }
        )
        evidence["partial_tree_proof"] = {
            "package_url": COMMERCE.WOOCOMMERCE_PACKAGE_URL,
            "package_sha256": COMMERCE.WOOCOMMERCE_PACKAGE_SHA256,
            "package_bytes": 20545768,
            "verified_file_count": 417,
            "verified_file_bytes": 3097152,
            "subset_manifest_sha256": DIGEST_A,
            "byte_exact_subset": True,
            "unknown_files": 0,
            "mismatched_files": 0,
            "symlinks": 0,
        }
        evidence["predelete_snapshot"] = {
            "tree_sha256": DIGEST_A,
            "verified": True,
        }
    return evidence


def dry_run_response() -> dict[str, Any]:
    return {
        "schema": COMMERCE.CATALOG_STATUS_SCHEMA,
        "mode": "dry_run",
        "write_performed": False,
        "product_count": COMMERCE.EXPECTED_PRODUCT_COUNT,
        "registry_digest": DIGEST_A,
        "price_digest": DIGEST_B,
        "asset_digest": DIGEST_C,
        "relation_digest": DIGEST_D,
        "actions": {
            code: {
                "product": "create",
                "attachment": "import",
                "sku": code,
                "stock_action": "initialize",
                "initial_stock": 1,
                "backorders": "no",
                "price_ils": COMMERCE.EXPECTED_ILS_PRICES[code],
                "asset_sha256": DIGEST_D,
            }
            for index, code in enumerate(COMMERCE.EXPECTED_PRODUCT_CODES)
        },
    }


def product_ids() -> dict[str, int]:
    return {
        code: 1000 + index
        for index, code in enumerate(COMMERCE.EXPECTED_PRODUCT_CODES)
    }


def apply_response(deployment_id: str = TEST_DEPLOYMENT_ID) -> dict[str, Any]:
    bindings = product_ids()
    stock_receipts = {
        code: {
            "product_id": bindings[code],
            "policy_quantity": 1,
            "initialized": True,
            "initialized_now": True,
            "readback": {
                "managing_stock": True,
                "quantity": 1,
                "status": "instock",
                "backorders": "no",
            },
        }
        for code in COMMERCE.EXPECTED_PRODUCT_CODES
    }
    return {
        "schema": COMMERCE.CATALOG_STATUS_SCHEMA,
        "mode": "apply",
        "write_performed": True,
        "ready": True,
        "product_count": COMMERCE.EXPECTED_PRODUCT_COUNT,
        "product_ids": bindings,
        "page_cache_purge": {
            "upress": {"detected": True, "request_completed": True},
            "litespeed": {"listener_detected": True, "signal_sent": True},
            "attempts": 1,
        },
        "receipt": {
            "schema": COMMERCE.CATALOG_RECEIPT_SCHEMA,
            "status": "verified",
            "deployment_id": deployment_id,
            "mutation_id": TEST_MUTATION_ID,
            "product_count": COMMERCE.EXPECTED_PRODUCT_COUNT,
            "registry_digest": DIGEST_A,
            "price_digest": DIGEST_B,
            "asset_digest": DIGEST_C,
            "relation_digest": DIGEST_D,
            "configuration_digest": DIGEST_A,
            "bindings_digest": DIGEST_D,
            "initial_stock_digest": DIGEST_C,
            "product_ids": bindings,
            "product_digests": {
                code: DIGEST_A for code in COMMERCE.EXPECTED_PRODUCT_CODES
            },
            "initial_stock_receipts": stock_receipts,
            "materialized_at": "2026-07-31T12:00:00Z",
            "materialized_by": 7,
        },
    }


def status_response(deployment_id: str = TEST_DEPLOYMENT_ID) -> dict[str, Any]:
    return {
        "schema": COMMERCE.CATALOG_STATUS_SCHEMA,
        "ready": True,
        "reason": "",
        "product_count": COMMERCE.EXPECTED_PRODUCT_COUNT,
        "product_ids": product_ids(),
        "strict": True,
        "receipt": {
            "schema": COMMERCE.CATALOG_RECEIPT_SCHEMA,
            "status": "verified",
            "deployment_id": deployment_id,
            "mutation_id": TEST_MUTATION_ID,
            "materialized_at": "2026-07-31T12:00:00Z",
            "bindings_digest": DIGEST_D,
            "initial_stock_digest": DIGEST_C,
        },
    }


class WooCommerceMaterializationPipelineTests(unittest.TestCase):
    def test_dependency_and_catalog_allowlists_are_exactly_pinned(self) -> None:
        self.assertEqual("woocommerce/woocommerce.php", COMMERCE.WOOCOMMERCE_PLUGIN)
        self.assertEqual(
            "woocommerce/woocommerce",
            COMMERCE.WOOCOMMERCE_PLUGIN_REST_ID,
        )
        self.assertEqual("10.9.4", COMMERCE.WOOCOMMERCE_VERSION)
        self.assertEqual(
            "https://downloads.wordpress.org/plugin/woocommerce.10.9.4.zip",
            COMMERCE.WOOCOMMERCE_PACKAGE_URL,
        )
        self.assertEqual(
            "6e58fc3ba9b18d1c9aee6b0227d3c3c09e4fe2c1332823bd2e0ac54ffcff64a9",
            COMMERCE.WOOCOMMERCE_PACKAGE_SHA256,
        )
        self.assertEqual(36, COMMERCE.EXPECTED_PRODUCT_COUNT)
        self.assertEqual(36, COMMERCE.DEPLOY.EXPECTED_CATALOG_PRODUCT_COUNT)
        self.assertEqual(
            COMMERCE.DEPLOY.EXPECTED_CATALOG_PRODUCT_COUNT,
            len(COMMERCE.DEPLOY.CATALOG_PRODUCT_CODES),
        )
        self.assertEqual(36, len(COMMERCE.EXPECTED_PRODUCT_CODES))
        self.assertEqual(36, len(set(COMMERCE.EXPECTED_PRODUCT_CODES)))
        self.assertEqual(
            {
                "product-tahini-500g": "11.00",
                "product-amba-500g": "14.90",
                "product-hot-sauce-60ml": "12.90",
                "product-pita-12x50g": "14.90",
                "product-aubergine-1kg": "6.90",
                "product-eggs-l-12": "14.24",
                "product-potato-white-1kg": "4.90",
                "product-tomato-1kg": "6.90",
                "product-cucumber-1kg": "6.90",
                "product-onion-dry-1kg": "4.90",
                "product-parsley-100g": "5.90",
                "product-chickpeas-dry-500g": "8.90",
                "product-beetroot-1kg": "4.90",
                "product-bulgur-fine-500g": "5.90",
                "product-couscous-1kg": "11.90",
                "product-chicken-breast-1kg": "39.90",
                "product-breadcrumbs-500g": "8.90",
                "product-ground-beef-1kg": "64.90",
                "product-tilapia-fillet-1kg": "38.90",
                "product-tomato-sauce-400g": "9.90",
                "product-rice-persian-1kg": "11.90",
                "product-beef-shank-1kg": "69.90",
                "product-hawayej-soup-100g": "8.90",
                "product-olive-oil-750ml": "44.90",
                "product-pickles-brine-320g": "14.90",
                "product-chicken-liver-1kg": "17.90",
                "product-rishiri-kombu-100g": "89.00",
                "product-honkarebushi-200g": "219.00",
                "product-yamaroku-tsurubishio-500ml": "149.00",
                "product-kito-yuzu-juice-100ml": "64.00",
                "product-fresh-japanese-wasabi-250g": "399.00",
                "product-hagane-zame-large": "699.00",
                "product-koshihikari-uozu-2kg": "149.00",
                "product-hishiroku-dried-rice-koji-500g": "119.00",
                "product-hishiroku-chouhaku-kin-20g": "109.00",
                "product-fresh-wasabi-50-60g": "119.00",
            },
            COMMERCE.EXPECTED_ILS_PRICES,
        )
        self.assertEqual(
            set(COMMERCE.EXPECTED_PRODUCT_CODES),
            set(COMMERCE.EXPECTED_ILS_PRICES),
        )

    def test_package_digest_verification_is_exact_and_bounded(self) -> None:
        raw = b"official-package-test-fixture"
        digest = hashlib.sha256(raw).hexdigest()
        self.assertEqual(
            {"bytes": len(raw), "sha256": digest},
            COMMERCE.verify_package_bytes(raw, digest),
        )
        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.verify_package_bytes(raw, "0" * 64)
        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.verify_package_bytes(b"", hashlib.sha256(b"").hexdigest())

    def test_bridge_token_is_deterministic_per_secret_and_deployment(self) -> None:
        first = COMMERCE.derive_bridge_token("app-password-fixture", "c99-commerce-run-1")
        second = COMMERCE.derive_bridge_token("app-password-fixture", "c99-commerce-run-1")
        other = COMMERCE.derive_bridge_token("app-password-fixture", "c99-commerce-run-2")
        self.assertEqual(first, second)
        self.assertNotEqual(first, other)
        self.assertNotIn("app-password-fixture", first)
        self.assertRegex(first, r"^[A-Za-z0-9_-]{43}$")

    def test_rendered_bridge_is_admin_gated_hash_pinned_and_php_valid(self) -> None:
        token = "T" * 48
        deployment_id = "c99-commerce-test-1234"
        code = COMMERCE.render_bridge(token, deployment_id, "complete99.co.il")
        self.assertNotIn("__C99_WOO_", code)
        self.assertTrue(code.startswith(f"/* {COMMERCE.BRIDGE_SOURCE_HEADER} "))
        contract = COMMERCE.verify_rendered_bridge_contract(
            code,
            token,
            deployment_id,
            "complete99.co.il",
            COMMERCE.DEPLOY.snippet_name(deployment_id),
        )
        self.assertTrue(contract["hmac_verified"])
        self.assertTrue(contract["current_template_exact"])
        self.assertIn("current_user_can( 'update_plugins' )", code)
        self.assertNotIn(token, code)
        self.assertIn(hashlib.sha256(token.encode("ascii")).hexdigest(), code)
        self.assertIn(
            "hash_equals( $config['token_sha256'], hash( 'sha256', $provided_token ) )",
            code,
        )
        self.assertIn(COMMERCE.WOOCOMMERCE_PACKAGE_URL, code)
        self.assertIn(COMMERCE.WOOCOMMERCE_PACKAGE_SHA256, code)
        self.assertIn("download_url( $config['package_url']", code)
        self.assertIn("hash_file( 'sha256', $temporary_file )", code)
        self.assertIn("'overwrite_package'  => false", code)
        self.assertIn("activate_plugin( $config['plugin']", code)
        self.assertIn("defined( 'WC_VERSION' )", code)
        self.assertIn("in_array( 'wc/v3', $namespaces, true )", code)
        self.assertIn("Code_Snippets\\delete_snippet", code)
        self.assertNotIn("update_option( 'woocommerce_", code)

        php = shutil.which("php")
        if php:
            with tempfile.TemporaryDirectory() as temp:
                rendered = Path(temp) / "rendered-woocommerce-bridge.php"
                rendered.write_text("<?php\n" + code, encoding="utf-8")
                lint = subprocess.run(
                    [php, "-l", str(rendered)],
                    capture_output=True,
                    check=False,
                    text=True,
                )
                self.assertEqual(0, lint.returncode, lint.stdout + lint.stderr)

    def test_versioned_bridge_contract_authenticates_old_source_without_template_equality(self) -> None:
        app_password = "old-release-app-password"
        deployment_id = "c99-commerce-old-release-42"
        target_host = "complete99.co.il"
        name = COMMERCE.DEPLOY.snippet_name(deployment_id)
        token = COMMERCE.derive_bridge_token(app_password, deployment_id)
        current = COMMERCE.render_bridge(token, deployment_id, target_host)
        header, body = current.split("\n", 1)
        self.assertIn(COMMERCE.BRIDGE_SOURCE_HEADER, header)
        old_body = body + "\n/* authenticated old release source fixture */\n"
        payload = json.dumps(
            {
                "contract": COMMERCE.BRIDGE_SOURCE_CONTRACT,
                "deployment_id": deployment_id,
                "snippet_name": name,
                "source_sha256": hashlib.sha256(old_body.encode("utf-8")).hexdigest(),
                "target_host": target_host,
            },
            ensure_ascii=True,
            separators=(",", ":"),
            sort_keys=True,
        ).encode("ascii")
        encoded = base64.urlsafe_b64encode(payload).rstrip(b"=").decode("ascii")
        mac = COMMERCE._bridge_contract_mac(token, payload)
        old_code = (
            f"/* {COMMERCE.BRIDGE_SOURCE_HEADER} {encoded} {mac} */\n{old_body}"
        )

        contract = COMMERCE.verify_rendered_bridge_contract(
            old_code,
            token,
            deployment_id,
            target_host,
            name,
        )
        self.assertTrue(contract["hmac_verified"])
        self.assertFalse(contract["current_template_exact"])

        deleted = False

        class RecoveryClient:
            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                nonlocal deleted
                if path.endswith("/activate"):
                    return 200, {}
                if path.endswith(f"/{deployment_id}/retire"):
                    deleted = True
                    return 200, {"permanently_deleted": [71]}
                if path.endswith(f"/{deployment_id}/status"):
                    return 404, {"code": "rest_no_route", "data": {"status": 404}}
                raise AssertionError((method, path, payload, expected))

        row = {"id": 71, "name": name, "code": old_code, "active": False}
        with (
            mock.patch.object(
                COMMERCE.DEPLOY,
                "active_snippets",
                side_effect=[[{"id": 71, "name": name, "active": False}], []],
            ),
            mock.patch.object(
                COMMERCE.DEPLOY,
                "get_snippet_by_id",
                side_effect=lambda client, snippet_id: None if deleted else row,
            ),
        ):
            result = COMMERCE.recover_all_interrupted_commerce_bridges(
                RecoveryClient(),
                app_password,
                target_host,
            )
        self.assertEqual(1, len(result["removed"]))
        self.assertTrue(result["removed"][0]["contract"]["hmac_verified"])
        self.assertFalse(result["removed"][0]["contract"]["current_template_exact"])

    def test_bridge_recovery_never_activates_unauthenticated_source(self) -> None:
        app_password = "recovery-app-password"
        deployment_id = "c99-commerce-unauthenticated-9"
        target_host = "complete99.co.il"
        name = COMMERCE.DEPLOY.snippet_name(deployment_id)
        token = COMMERCE.derive_bridge_token(app_password, deployment_id)
        code = COMMERCE.render_bridge(token, deployment_id, target_host)
        header, body = code.split("\n", 1)
        old_mac = header.rsplit(" ", 2)[1]
        tampered_body = body.replace(
            "No privileged work runs",
            "Tampered code runs",
            1,
        )
        payload = json.loads(
            base64.urlsafe_b64decode(
                header.split(" ", 3)[2] + "=" * (-len(header.split(" ", 3)[2]) % 4)
            ).decode("ascii")
        )
        payload["source_sha256"] = hashlib.sha256(
            tampered_body.encode("utf-8")
        ).hexdigest()
        forged_payload = json.dumps(
            payload,
            ensure_ascii=True,
            separators=(",", ":"),
            sort_keys=True,
        ).encode("ascii")
        forged_encoded = base64.urlsafe_b64encode(forged_payload).rstrip(b"=").decode(
            "ascii"
        )
        tampered = (
            f"/* {COMMERCE.BRIDGE_SOURCE_HEADER} {forged_encoded} {old_mac} */\n"
            f"{tampered_body}"
        )
        row = {"id": 72, "name": name, "code": tampered, "active": False}

        class NoExecutionClient:
            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                raise AssertionError("an unauthenticated row must never be activated or executed")

        with (
            mock.patch.object(
                COMMERCE.DEPLOY,
                "active_snippets",
                return_value=[{"id": 72, "name": name, "active": False}],
            ),
            mock.patch.object(
                COMMERCE.DEPLOY,
                "get_snippet_by_id",
                return_value=row,
            ),
        ):
            with self.assertRaises(COMMERCE.DeployError):
                COMMERCE.recover_all_interrupted_commerce_bridges(
                    NoExecutionClient(),
                    app_password,
                    target_host,
                )

    def test_plugin_installer_has_durable_owned_partial_directory_recovery(self) -> None:
        source = (
            ROOT / "deploy" / "temporary-woocommerce-bridge.php"
        ).read_text(encoding="utf-8")
        self.assertIn(
            "'complete99_woocommerce_install_recovery'",
            source,
        )
        self.assertIn("complete99-woocommerce-install-recovery/v1", source)
        self.assertIn("hash_hmac( 'sha256', $encoded, wp_salt( 'auth' ) )", source)
        self.assertIn("$verify_plugin_target", source)
        self.assertIn("is_link( $plugin_root )", source)
        self.assertIn("if ( ! empty( $current['active'] ) )", source)
        self.assertIn("if ( empty( $marker['valid'] )", source)
        self.assertIn("if ( $candidate->isLink() )", source)
        self.assertIn("download_url( $payload['package_url']", source)
        self.assertIn("hash_equals( $payload['package_sha256'], $package_sha )", source)
        self.assertIn("$zip->locateName( $entry, 0 )", source)
        self.assertIn("$existing_size !== (int) $stat['size']", source)
        self.assertIn("hash_file( 'sha256', $path )", source)
        self.assertIn("'complete99_woocommerce_recovery_unknown_file'", source)
        self.assertIn("'complete99_woocommerce_recovery_file_digest'", source)
        self.assertIn("'byte_exact_subset'   => true", source)
        self.assertIn("'complete99_woocommerce_recovery_tree_changed'", source)
        self.assertIn("$predelete_state = $inspect()", source)
        self.assertIn("if ( ! WP_Filesystem() )", source)
        self.assertIn("'direct' !== $wp_filesystem->method", source)
        self.assertIn("$wp_filesystem->delete( $plugin_root, true, 'd' )", source)
        self.assertIn("'recovered_partial_reinstall'", source)

        marker_persist = source.index(
            "update_option( $install_marker_option, $new_marker, false )"
        )
        subset_proof = source.index(
            "$partial_tree_proof = $verify_partial_tree_subset( $marker['payload'] )"
        )
        recursive_delete = source.index(
            "$wp_filesystem->delete( $plugin_root, true, 'd' )"
        )
        download = source.index("download_url( $config['package_url']")
        install = source.index("$upgrader->install(")
        exact_tree = source.index("$installed_state = $inspect()")
        activate = source.index("activate_plugin( $config['plugin']", install)
        final_readback = source.index("$final_state = $inspect()")
        clear_marker = source.index("delete_option( $install_marker_option )", install)
        self.assertLess(subset_proof, recursive_delete)
        self.assertLess(marker_persist, download)
        self.assertLess(download, install)
        self.assertLess(install, exact_tree)
        self.assertLess(exact_tree, activate)
        self.assertLess(activate, final_readback)
        self.assertLess(final_readback, clear_marker)

    def test_python_requires_recovery_evidence_and_absent_marker_readback(self) -> None:
        deployment_id = "c99-commerce-recovered-partial-1"
        token = "V" * 48
        target_host = "complete99.co.il"

        class RuntimeClient:
            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                if path.endswith(f"/{deployment_id}/install"):
                    return 200, {
                        "installed_pending_fresh_status": True,
                        "installation_action": "recovered_partial_reinstall",
                        "package_sha256": COMMERCE.WOOCOMMERCE_PACKAGE_SHA256,
                        "site_identity": {
                            "home": target_host,
                            "siteurl": target_host,
                            "rest": target_host,
                        },
                        "install_recovery": install_recovery_evidence(recovered=True),
                        "state": bridge_state(),
                    }
                if path.endswith(f"/{deployment_id}/status"):
                    return 200, {
                        "site_identity": {
                            "home": target_host,
                            "siteurl": target_host,
                            "rest": target_host,
                        },
                        "state": bridge_state(),
                    }
                if path == COMMERCE.WOOCOMMERCE_PLUGIN_REST_PATH:
                    return 200, {
                        "plugin": COMMERCE.WOOCOMMERCE_PLUGIN_REST_ID,
                        "status": "active",
                        "version": COMMERCE.WOOCOMMERCE_VERSION,
                    }
                if path == COMMERCE.WOOCOMMERCE_SYSTEM_STATUS_PATH:
                    return 200, {
                        "environment": {"version": COMMERCE.WOOCOMMERCE_VERSION}
                    }
                if path == COMMERCE.WOOCOMMERCE_GATEWAYS_PATH:
                    return 200, [{"id": "bacs", "enabled": False}]
                raise AssertionError((method, path, payload, expected))

        result = COMMERCE.install_and_verify_woocommerce(
            RuntimeClient(), token, deployment_id, target_host
        )
        self.assertEqual("recovered_partial_reinstall", result["installation_action"])
        self.assertTrue(result["install_recovery"]["cleanup"]["verified"])

        class UnknownFileProofClient(RuntimeClient):
            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                status, response = super().request(method, path, payload, expected)
                if path.endswith(f"/{deployment_id}/install"):
                    response["install_recovery"]["partial_tree_proof"][
                        "unknown_files"
                    ] = 1
                return status, response

        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.install_and_verify_woocommerce(
                UnknownFileProofClient(), token, deployment_id, target_host
            )

        stale_state = bridge_state()
        stale_state["install_recovery_marker"] = {
            "exists": True,
            "valid": True,
        }

        class StaleMarkerClient(RuntimeClient):
            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                status, response = super().request(method, path, payload, expected)
                if path.endswith(f"/{deployment_id}/status"):
                    response["state"] = stale_state
                return status, response

        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.install_and_verify_woocommerce(
                StaleMarkerClient(), token, deployment_id, target_host
            )

    def test_catalog_dry_run_requires_all_32_stock_price_and_asset_actions(self) -> None:
        verified = COMMERCE.verify_catalog_dry_run(dry_run_response())
        self.assertEqual(36, verified["product_count"])
        self.assertFalse(verified["write_performed"])

        wrong_count = dry_run_response()
        wrong_count["product_count"] = 27
        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.verify_catalog_dry_run(wrong_count)

        wrong_stock = dry_run_response()
        first = COMMERCE.EXPECTED_PRODUCT_CODES[0]
        wrong_stock["actions"][first]["initial_stock"] = 2
        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.verify_catalog_dry_run(wrong_stock)

        missing_asset = dry_run_response()
        missing_asset["actions"][first]["asset_sha256"] = ""
        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.verify_catalog_dry_run(missing_asset)

    def test_catalog_calls_dry_run_then_apply_then_strict_status(self) -> None:
        class CatalogClient:
            def __init__(self) -> None:
                self.calls: list[tuple[str, str, Any]] = []

            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, Any] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, Any]:
                self.calls.append((method, path, payload))
                if len(self.calls) == 1:
                    return 200, dry_run_response()
                if len(self.calls) == 2:
                    return 200, apply_response()
                if len(self.calls) == 3:
                    return 200, status_response()
                raise AssertionError("unexpected extra request")

        client = CatalogClient()
        result = COMMERCE.materialize_catalog(client, TEST_DEPLOYMENT_ID)
        self.assertEqual(36, result["status"]["product_count"])
        self.assertTrue(
            result["apply"]["page_cache_purge"]["upress"]["request_completed"]
        )
        self.assertTrue(
            result["apply"]["page_cache_purge"]["litespeed"]["signal_sent"]
        )
        self.assertEqual(
            [
                (
                    "POST",
                    COMMERCE.CATALOG_ROUTE,
                    {
                        "mode": "dry_run",
                        "confirm": False,
                        "deployment_id": TEST_DEPLOYMENT_ID,
                    },
                ),
                (
                    "POST",
                    COMMERCE.CATALOG_ROUTE,
                    {
                        "mode": "apply",
                        "confirm": True,
                        "deployment_id": TEST_DEPLOYMENT_ID,
                    },
                ),
                ("GET", COMMERCE.CATALOG_ROUTE, None),
            ],
            client.calls,
        )

    def test_dry_run_mismatch_stops_before_catalog_apply(self) -> None:
        class MismatchClient:
            def __init__(self) -> None:
                self.calls = 0

            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                self.calls += 1
                if self.calls != 1:
                    raise AssertionError("apply must not run after a dry-run mismatch")
                response = dry_run_response()
                response["product_count"] = 25
                return 200, response

        client = MismatchClient()
        with self.assertRaises(COMMERCE.CatalogMaterializationError) as raised:
            COMMERCE.materialize_catalog(client, TEST_DEPLOYMENT_ID)
        self.assertEqual("dry_run", raised.exception.phase)
        self.assertEqual(1, client.calls)

    def test_catalog_status_must_match_the_apply_mutation_identity(self) -> None:
        class MutationMismatchClient:
            def __init__(self) -> None:
                self.calls = 0

            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                self.calls += 1
                if self.calls == 1:
                    return 200, dry_run_response()
                if self.calls == 2:
                    return 200, apply_response()
                if self.calls == 3:
                    response = status_response()
                    response["receipt"]["mutation_id"] = "abcdefab-cdef-abcd-efab-cdefabcdefab"
                    return 200, response
                raise AssertionError("unexpected extra request")

        client = MutationMismatchClient()
        with self.assertRaises(COMMERCE.CatalogMaterializationError) as raised:
            COMMERCE.materialize_catalog(client, TEST_DEPLOYMENT_ID)
        self.assertEqual("status", raised.exception.phase)
        self.assertIn("mutation identity changed", str(raised.exception))
        self.assertEqual(3, client.calls)

    def test_catalog_failure_keeps_only_validated_structured_diagnostics(self) -> None:
        self.assertEqual(
            set(COMMERCE.EXPECTED_PRODUCT_CODES),
            set(COMMERCE.DEPLOY.CATALOG_PRODUCT_CODES),
        )
        catalog_source = (
            ROOT
            / "plugin"
            / "complete99-platform"
            / "includes"
            / "class-complete99-live-catalog.php"
        ).read_text(encoding="utf-8")
        runtime_causes = set(COMMERCE.DEPLOY.CATALOG_RUNTIME_MESSAGE_CAUSE.values())
        for cause in COMMERCE.DEPLOY.CATALOG_CAUSE_STAGE:
            if cause not in runtime_causes:
                self.assertIn(f"'{cause}'", catalog_source)
        for message, cause in COMMERCE.DEPLOY.CATALOG_RUNTIME_MESSAGE_CAUSE.items():
            self.assertIn(f"'{message}'", catalog_source)
            self.assertIn(cause, COMMERCE.DEPLOY.CATALOG_CAUSE_STAGE)
        self.assertIn(
            "'Catalog recovery is required after an unverified mutation boundary: '",
            catalog_source,
        )
        original = COMMERCE.DEPLOY.HTTPDeployError(
            "catalog apply failed",
            status=500,
            code="complete99_live_catalog_apply_failed",
            data={
                "catalog_stage": "secret_token",
                "catalog_cause_code": "complete99_live_catalog_asset_upload_failed",
                "catalog_product_code": "product-tahini-500g",
                "server_path": "/home/example/public_html",
                "unsafe_stage": "attachment/../../etc",
            },
        )
        error = COMMERCE.CatalogMaterializationError("apply", original)

        self.assertEqual(
            {
                "catalog_stage": "attachment",
                "catalog_cause_code": "complete99_live_catalog_asset_upload_failed",
                "catalog_product_code": "product-tahini-500g",
            },
            error.diagnostic,
        )
        self.assertNotIn("server_path", error.diagnostic)
        self.assertNotIn("unsafe_stage", error.diagnostic)

        invalid_original = COMMERCE.DEPLOY.HTTPDeployError(
            "catalog apply failed",
            status=500,
            code="complete99_live_catalog_apply_failed",
            data={
                "catalog_stage": "secret_token",
                "catalog_cause_code": "complete99_live_catalog_secretvalue",
                "catalog_product_code": "product-secretvalue",
            },
        )
        invalid = COMMERCE.CatalogMaterializationError("apply", invalid_original)
        self.assertEqual({}, invalid.diagnostic)

    def test_runtime_and_rest_checks_precede_catalog_and_gateways_are_read_only(self) -> None:
        deployment_id = "c99-commerce-test-5678"
        token = "U" * 48
        target_host = "complete99.co.il"

        class RuntimeClient:
            def __init__(self, enabled: bool = False) -> None:
                self.calls: list[tuple[str, str, Any]] = []
                self.enabled = enabled

            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                self.calls.append((method, path, payload))
                if path.endswith(f"/{deployment_id}/install"):
                    return 200, {
                        "installed_pending_fresh_status": True,
                        "installation_action": "fresh_install",
                        "package_sha256": COMMERCE.WOOCOMMERCE_PACKAGE_SHA256,
                        "site_identity": {
                            "home": target_host,
                            "siteurl": target_host,
                            "rest": target_host,
                        },
                        "install_recovery": install_recovery_evidence(),
                        "state": bridge_state(),
                    }
                if path.endswith(f"/{deployment_id}/status"):
                    return 200, {
                        "site_identity": {
                            "home": target_host,
                            "siteurl": target_host,
                            "rest": target_host,
                        },
                        "state": bridge_state(),
                    }
                if path == COMMERCE.WOOCOMMERCE_PLUGIN_REST_PATH:
                    return 200, {
                        "plugin": COMMERCE.WOOCOMMERCE_PLUGIN_REST_ID,
                        "status": "active",
                        "version": COMMERCE.WOOCOMMERCE_VERSION,
                    }
                if path == COMMERCE.WOOCOMMERCE_SYSTEM_STATUS_PATH:
                    return 200, {
                        "environment": {"version": COMMERCE.WOOCOMMERCE_VERSION}
                    }
                if path == COMMERCE.WOOCOMMERCE_GATEWAYS_PATH:
                    return 200, [{"id": "bacs", "enabled": self.enabled}]
                raise AssertionError((method, path, payload, expected))

        client = RuntimeClient()
        result = COMMERCE.install_and_verify_woocommerce(
            client,
            token,
            deployment_id,
            target_host,
        )
        self.assertEqual(0, result["gateway_configuration"]["configured_count"])
        gateway_calls = [call for call in client.calls if call[1] == COMMERCE.WOOCOMMERCE_GATEWAYS_PATH]
        self.assertEqual([("GET", COMMERCE.WOOCOMMERCE_GATEWAYS_PATH, None)], gateway_calls)

        class PhpSuffixIdentityClient(RuntimeClient):
            def request(
                self,
                method: str,
                path: str,
                payload=None,
                expected=(200, 201),
            ):
                status, response = super().request(method, path, payload, expected)
                if path == COMMERCE.WOOCOMMERCE_PLUGIN_REST_PATH:
                    response["plugin"] = COMMERCE.WOOCOMMERCE_PLUGIN
                return status, response

        with self.assertRaisesRegex(
            COMMERCE.DeployError,
            "wrong WooCommerce plugin identity",
        ):
            COMMERCE.install_and_verify_woocommerce(
                PhpSuffixIdentityClient(),
                token,
                deployment_id,
                target_host,
            )

        with self.assertRaises(COMMERCE.DeployError):
            COMMERCE.install_and_verify_woocommerce(
                RuntimeClient(enabled=True),
                token,
                deployment_id,
                target_host,
            )

    def test_catalog_failure_evidence_is_read_only_and_never_reapplies(self) -> None:
        class RecoveryClient:
            def __init__(self) -> None:
                self.calls: list[tuple[str, Any]] = []

            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                self.calls.append((method, payload))
                if method == "GET":
                    return 200, {
                        "schema": COMMERCE.CATALOG_STATUS_SCHEMA,
                        "ready": False,
                        "reason": "receipt_missing",
                        "product_count": 0,
                        "product_ids": {},
                        "strict": True,
                    }
                if payload == {
                    "mode": "dry_run",
                    "confirm": False,
                    "deployment_id": TEST_DEPLOYMENT_ID,
                }:
                    return 200, dry_run_response()
                raise AssertionError("recovery must never call apply")

        client = RecoveryClient()
        evidence = COMMERCE.capture_catalog_failure_evidence(
            client,
            "apply",
            TEST_DEPLOYMENT_ID,
        )
        self.assertFalse(evidence["automatic_apply_retry"])
        self.assertTrue(evidence["strict_status_probe"]["verified"])
        self.assertTrue(evidence["dry_run_probe"]["verified"])
        self.assertNotIn(
            {"mode": "apply", "confirm": True},
            [payload for _, payload in client.calls],
        )

    def test_interrupted_bridge_recovery_proves_no_row_and_route_404(self) -> None:
        deployment_id = "c99-commerce-recovery-1"

        class RecoveryClient:
            def request(self, method: str, path: str, payload=None, expected=(200, 201)):
                if path.endswith(f"/{deployment_id}/status"):
                    self_response = {"code": "rest_no_route", "data": {"status": 404}}
                    return 404, self_response
                raise AssertionError((method, path, payload, expected))

        with mock.patch.object(
            COMMERCE.DEPLOY,
            "find_active_snippet_ids",
            return_value=[],
        ):
            result = COMMERCE.recover_interrupted_bridge(
                RecoveryClient(),
                "R" * 48,
                deployment_id,
                "complete99.co.il",
            )
        self.assertTrue(result["row_absence_verified"])
        self.assertTrue(result["route_404"])
        self.assertEqual([], result["removed_ids"])

    def test_workflow_runs_commerce_after_platform_cleanup_and_uploads_audit(self) -> None:
        workflow = (
            ROOT / ".github" / "workflows" / "wordpress-deploy.yml"
        ).read_text(encoding="utf-8")
        platform = workflow.index(
            "- name: Deploy the exact CI artifact with independent verification"
        )
        marker_cleanup = workflow.index("- name: Remove the runner-local mutation marker")
        commerce = workflow.index("- name: Install pinned WooCommerce and materialize the live catalog")
        recovery = workflow.index("- name: Recover an interrupted WooCommerce bridge")
        fail_closed = workflow.index("- name: Fail closed after an audited commerce failure")
        audit = workflow.index("- name: Upload non-secret deployment audit")
        self.assertLess(platform, marker_cleanup)
        self.assertLess(marker_cleanup, commerce)
        self.assertLess(commerce, recovery)
        self.assertLess(recovery, fail_closed)
        self.assertLess(fail_closed, audit)
        commerce_block = workflow[commerce:audit]
        self.assertIn("scripts/materialize-woocommerce.py", commerce_block)
        self.assertIn("continue-on-error: true", commerce_block)
        self.assertIn("--recover-bridge", commerce_block)
        self.assertIn("steps.commerce.outcome == 'failure'", commerce_block)
        self.assertIn("WP_BASE_URL: ${{ secrets.WP_BASE_URL }}", commerce_block)
        self.assertIn("WP_DEPLOY_USER: ${{ secrets.WP_DEPLOY_USER }}", commerce_block)
        self.assertIn("WP_APP_PASSWORD: ${{ secrets.WP_APP_PASSWORD }}", commerce_block)
        self.assertNotIn("PAYMENT", commerce_block.upper())
        self.assertIn("commerce-audit/*.json", workflow)

    def test_new_orchestration_files_contain_no_em_dash(self) -> None:
        for relative in (
            "deploy/temporary-woocommerce-bridge.php",
            "scripts/materialize-woocommerce.py",
            "tests/test_woocommerce_materialization_pipeline.py",
        ):
            self.assertNotIn("\u2014", (ROOT / relative).read_text(encoding="utf-8"))


if __name__ == "__main__":
    unittest.main()
