#!/usr/bin/env python3
"""Install pinned WooCommerce and materialize the exact Complete99 catalog."""

from __future__ import annotations

import argparse
import base64
import hashlib
import hmac
import importlib.util
import json
import os
import re
import secrets
import ssl
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
DEPLOY_SCRIPT = ROOT / "scripts" / "deploy-wordpress.py"
BRIDGE_TEMPLATE = ROOT / "deploy" / "temporary-woocommerce-bridge.php"

WOOCOMMERCE_PLUGIN = "woocommerce/woocommerce.php"
WOOCOMMERCE_PLUGIN_REST_ID = "woocommerce/woocommerce"
WOOCOMMERCE_VERSION = "10.9.4"
WOOCOMMERCE_PACKAGE_URL = (
    "https://downloads.wordpress.org/plugin/woocommerce.10.9.4.zip"
)
WOOCOMMERCE_PACKAGE_SHA256 = (
    "6e58fc3ba9b18d1c9aee6b0227d3c3c09e4fe2c1332823bd2e0ac54ffcff64a9"
)
WOOCOMMERCE_TREE_FILE_COUNT = 6194
WOOCOMMERCE_TREE_BYTES = 58583620
WOOCOMMERCE_TREE_SHA256 = "420913d80fc318742815b98b7c41cc58a67e686cb72b389e00013c08fd0cca02"
WOOCOMMERCE_MAX_PACKAGE_BYTES = 96 * 1024 * 1024
WOOCOMMERCE_PLUGIN_REST_PATH = (
    "/wp-json/wp/v2/plugins/woocommerce/woocommerce?context=edit"
)
WOOCOMMERCE_SYSTEM_STATUS_PATH = (
    "/wp-json/wc/v3/system_status?_fields=environment"
)
WOOCOMMERCE_GATEWAYS_PATH = (
    "/wp-json/wc/v3/payment_gateways?_fields=id,enabled"
)
BRIDGE_NAMESPACE = "complete99-woocommerce-deploy/v1"
BRIDGE_SOURCE_CONTRACT = "complete99-woocommerce-bridge-source/v1"
BRIDGE_SOURCE_HEADER = "COMPLETE99_WOOCOMMERCE_BRIDGE_SOURCE_V1"
CATALOG_ROUTE = "/wp-json/complete99/v1/store/catalog-materialization"
CATALOG_STATUS_SCHEMA = "complete99-live-catalog-status/v1"
CATALOG_RECEIPT_SCHEMA = "complete99-live-catalog-receipt/v1"
EXPECTED_PRODUCT_COUNT = 28
EXPECTED_PRODUCT_CODES = (
    "product-tahini-500g",
    "product-amba-500g",
    "product-hot-sauce-60ml",
    "product-pita-12x50g",
    "product-aubergine-1kg",
    "product-eggs-l-12",
    "product-potato-white-1kg",
    "product-tomato-1kg",
    "product-cucumber-1kg",
    "product-onion-dry-1kg",
    "product-parsley-100g",
    "product-chickpeas-dry-500g",
    "product-beetroot-1kg",
    "product-bulgur-fine-500g",
    "product-couscous-1kg",
    "product-chicken-breast-1kg",
    "product-breadcrumbs-500g",
    "product-ground-beef-1kg",
    "product-tilapia-fillet-1kg",
    "product-tomato-sauce-400g",
    "product-rice-persian-1kg",
    "product-beef-shank-1kg",
    "product-hawayej-soup-100g",
    "product-olive-oil-750ml",
    "product-pickles-brine-320g",
    "product-chicken-liver-1kg",
    "product-rishiri-kombu-100g",
    "product-honkarebushi-200g",
)
EXPECTED_ILS_PRICES = {
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
}


def load_deploy_module() -> Any:
    """Load the canonical transport and Code Snippets helpers."""

    module_name = "complete99_woocommerce_shared_deployer"
    existing = sys.modules.get(module_name)
    if existing is not None:
        return existing
    spec = importlib.util.spec_from_file_location(module_name, DEPLOY_SCRIPT)
    if not spec or not spec.loader:
        raise RuntimeError("The canonical WordPress deployer could not be loaded")
    module = importlib.util.module_from_spec(spec)
    sys.modules[module_name] = module
    spec.loader.exec_module(module)
    return module


DEPLOY = load_deploy_module()
DeployError = DEPLOY.DeployError


class CatalogMaterializationError(DeployError):
    """A catalog phase failed and no automatic apply retry is allowed."""

    def __init__(self, phase: str, error: Exception) -> None:
        diagnostic: dict[str, str] = {}
        raw_data = getattr(error, "data", {})
        if isinstance(raw_data, dict):
            cause = raw_data.get("catalog_cause_code")
            if isinstance(cause, str) and cause in DEPLOY.CATALOG_CAUSE_STAGE:
                diagnostic["catalog_cause_code"] = cause
                diagnostic["catalog_stage"] = DEPLOY.CATALOG_CAUSE_STAGE[cause]
            product_code = raw_data.get("catalog_product_code")
            if (
                isinstance(product_code, str)
                and product_code in DEPLOY.CATALOG_PRODUCT_CODES
            ):
                diagnostic["catalog_product_code"] = product_code
        super().__init__(f"The live catalog {phase} phase failed: {error}")
        self.phase = phase
        self.original_error = error
        self.diagnostic = diagnostic


def derive_bridge_token(app_password: str, deployment_id: str) -> str:
    """Derive a run-scoped token so an interrupted workflow can recover."""

    if not app_password:
        raise DeployError("WP_APP_PASSWORD is required for bridge recovery")
    if not re.fullmatch(r"[A-Za-z0-9._-]{8,96}", deployment_id):
        raise DeployError("Deployment ID must contain 8-96 safe characters")
    digest = hmac.new(
        app_password.encode("utf-8"),
        f"complete99-woocommerce-bridge:{deployment_id}".encode("ascii"),
        hashlib.sha256,
    ).digest()
    return base64.urlsafe_b64encode(digest).rstrip(b"=").decode("ascii")


def _require_dict(value: Any, message: str) -> dict[str, Any]:
    if not isinstance(value, dict):
        raise DeployError(message)
    return value


def _require_bool(value: Any, expected: bool, message: str) -> None:
    if type(value) is not bool or value is not expected:
        raise DeployError(message)


def _require_positive_id(value: Any, message: str) -> int:
    if type(value) is not int or value <= 0:
        raise DeployError(message)
    return value


def _require_digest(value: Any, message: str) -> str:
    if not isinstance(value, str) or not re.fullmatch(r"[a-f0-9]{64}", value):
        raise DeployError(message)
    return value


def _render_bridge_body(
    token: str,
    deployment_id: str,
    target_host: str,
) -> str:
    """Render the executable body before its authenticated source contract."""

    if not re.fullmatch(r"[A-Za-z0-9_-]{32,128}", token):
        raise DeployError("The temporary WooCommerce bridge token is invalid")
    if not re.fullmatch(r"[A-Za-z0-9._-]{8,96}", deployment_id):
        raise DeployError("Deployment ID must contain 8-96 safe characters")
    if target_host not in (
        DEPLOY.ALLOWED_PRODUCTION_HOSTS
        | DEPLOY.SUPPORTED_TRANSITIONAL_HOSTS
        | DEPLOY.ALLOWED_LOCAL_TEST_HOSTS
    ):
        raise DeployError("The WooCommerce bridge target host is not allowlisted")

    code = BRIDGE_TEMPLATE.read_text(encoding="utf-8")
    if code.startswith("<?php"):
        code = code.split("\n", 1)[1]
    replacements = {
        "__C99_WOO_TOKEN_SHA256__": hashlib.sha256(token.encode("ascii")).hexdigest(),
        "__C99_WOO_DEPLOYMENT_ID__": deployment_id,
        "__C99_WOO_SNIPPET_NAME__": DEPLOY.snippet_name(deployment_id),
        "__C99_WOO_TARGET_HOST__": target_host,
        "__C99_WOO_PLUGIN__": WOOCOMMERCE_PLUGIN,
        "__C99_WOO_VERSION__": WOOCOMMERCE_VERSION,
        "__C99_WOO_PACKAGE_URL__": WOOCOMMERCE_PACKAGE_URL,
        "__C99_WOO_PACKAGE_SHA256__": WOOCOMMERCE_PACKAGE_SHA256,
        "__C99_WOO_TREE_FILE_COUNT__": str(WOOCOMMERCE_TREE_FILE_COUNT),
        "__C99_WOO_TREE_BYTES__": str(WOOCOMMERCE_TREE_BYTES),
        "__C99_WOO_TREE_SHA256__": WOOCOMMERCE_TREE_SHA256,
    }
    for marker, value in replacements.items():
        if code.count(marker) != 1:
            raise DeployError(
                f"Temporary WooCommerce bridge must contain exactly one {marker} marker"
            )
        code = code.replace(marker, value)
    if re.search(r"__C99_WOO_[A-Z0-9_]+__", code):
        raise DeployError("The temporary WooCommerce bridge has an unresolved marker")
    return code


def _bridge_contract_mac(token: str, payload: bytes) -> str:
    """Authenticate one canonical bridge contract with a run-derived key."""

    contract_key = hmac.new(
        token.encode("ascii"),
        b"complete99-woocommerce-bridge-contract-key/v1",
        hashlib.sha256,
    ).digest()
    return hmac.new(contract_key, payload, hashlib.sha256).hexdigest()


def _urlsafe_b64decode_exact(value: str) -> bytes:
    if not re.fullmatch(r"[A-Za-z0-9_-]+", value):
        raise DeployError("The WooCommerce bridge source contract encoding is invalid")
    try:
        decoded = base64.urlsafe_b64decode(value + "=" * (-len(value) % 4))
    except Exception as error:
        raise DeployError(
            "The WooCommerce bridge source contract could not be decoded"
        ) from error
    canonical = base64.urlsafe_b64encode(decoded).rstrip(b"=").decode("ascii")
    if not secrets.compare_digest(canonical, value):
        raise DeployError("The WooCommerce bridge source contract is not canonical")
    return decoded


def render_bridge(
    token: str,
    deployment_id: str,
    target_host: str,
) -> str:
    """Render a one-use bridge with a versioned authenticated source contract."""

    body = _render_bridge_body(token, deployment_id, target_host)
    payload = json.dumps(
        {
            "contract": BRIDGE_SOURCE_CONTRACT,
            "deployment_id": deployment_id,
            "snippet_name": DEPLOY.snippet_name(deployment_id),
            "source_sha256": hashlib.sha256(body.encode("utf-8")).hexdigest(),
            "target_host": target_host,
        },
        ensure_ascii=True,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("ascii")
    encoded = base64.urlsafe_b64encode(payload).rstrip(b"=").decode("ascii")
    mac = _bridge_contract_mac(token, payload)
    return f"/* {BRIDGE_SOURCE_HEADER} {encoded} {mac} */\n{body}"


def verify_rendered_bridge_contract(
    code: str,
    token: str,
    deployment_id: str,
    target_host: str,
    snippet_name: str,
) -> dict[str, Any]:
    """Verify an old rendered row without depending on today's template bytes."""

    if not isinstance(code, str) or not code or len(code.encode("utf-8")) > 1024 * 1024:
        raise DeployError("The WooCommerce bridge source row is invalid")
    header = re.match(
        rf"\A/\* {re.escape(BRIDGE_SOURCE_HEADER)} "
        r"([A-Za-z0-9_-]+) ([a-f0-9]{64}) \*/\n",
        code,
    )
    if header is None:
        raise DeployError("The WooCommerce bridge source contract is missing")
    payload_bytes = _urlsafe_b64decode_exact(header.group(1))
    try:
        payload = json.loads(payload_bytes.decode("ascii"))
    except (UnicodeDecodeError, json.JSONDecodeError) as error:
        raise DeployError("The WooCommerce bridge source contract payload is invalid") from error
    if not isinstance(payload, dict) or set(payload) != {
        "contract",
        "deployment_id",
        "snippet_name",
        "source_sha256",
        "target_host",
    }:
        raise DeployError("The WooCommerce bridge source contract fields differ")
    canonical_payload = json.dumps(
        payload,
        ensure_ascii=True,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("ascii")
    if not secrets.compare_digest(payload_bytes, canonical_payload):
        raise DeployError("The WooCommerce bridge source contract payload is not canonical")
    expected_values = {
        "contract": BRIDGE_SOURCE_CONTRACT,
        "deployment_id": deployment_id,
        "snippet_name": snippet_name,
        "target_host": target_host,
    }
    for field, expected in expected_values.items():
        if not isinstance(payload.get(field), str) or not secrets.compare_digest(
            payload[field], expected
        ):
            raise DeployError(f"The WooCommerce bridge source contract {field} differs")
    source_sha256 = payload.get("source_sha256")
    _require_digest(
        source_sha256,
        "The WooCommerce bridge source contract digest is invalid",
    )
    body = code[header.end() :]
    actual_source_sha256 = hashlib.sha256(body.encode("utf-8")).hexdigest()
    if not secrets.compare_digest(source_sha256, actual_source_sha256):
        raise DeployError("The WooCommerce bridge source body digest differs")
    expected_mac = _bridge_contract_mac(token, payload_bytes)
    if not secrets.compare_digest(expected_mac, header.group(2)):
        raise DeployError("The WooCommerce bridge source contract authentication failed")
    current_code = render_bridge(token, deployment_id, target_host)
    return {
        "contract": BRIDGE_SOURCE_CONTRACT,
        "deployment_id": deployment_id,
        "snippet_name": snippet_name,
        "target_host": target_host,
        "source_sha256": actual_source_sha256,
        "hmac_verified": True,
        "current_template_exact": secrets.compare_digest(code, current_code),
    }


def verify_package_bytes(raw: bytes, expected_sha256: str) -> dict[str, Any]:
    """Verify a bounded package body against one exact SHA256."""

    if not raw or len(raw) > WOOCOMMERCE_MAX_PACKAGE_BYTES:
        raise DeployError("The WooCommerce package size is outside the approved bounds")
    actual = hashlib.sha256(raw).hexdigest()
    if not re.fullmatch(r"[a-f0-9]{64}", expected_sha256):
        raise DeployError("The pinned WooCommerce package SHA256 is invalid")
    if not secrets.compare_digest(expected_sha256, actual):
        raise DeployError("The official WooCommerce package SHA256 did not match")
    return {"bytes": len(raw), "sha256": actual}


def fetch_verified_official_package() -> dict[str, Any]:
    """Independently verify the official package before any live write."""

    request = urllib.request.Request(
        WOOCOMMERCE_PACKAGE_URL,
        headers={
            "Accept": "application/zip,application/octet-stream;q=0.9,*/*;q=0.1",
            "User-Agent": DEPLOY.USER_AGENT,
        },
        method="GET",
    )
    opener = urllib.request.build_opener(
        urllib.request.HTTPSHandler(context=ssl.create_default_context()),
        DEPLOY.RejectRedirects(),
    )
    try:
        with opener.open(request, timeout=300) as response:
            if response.status != 200 or response.geturl() != WOOCOMMERCE_PACKAGE_URL:
                raise DeployError(
                    "The official WooCommerce package request did not return the exact pinned URL"
                )
            content_length = response.headers.get("Content-Length", "")
            if content_length:
                try:
                    declared = int(content_length)
                except ValueError as error:
                    raise DeployError(
                        "The official WooCommerce package length header is invalid"
                    ) from error
                if declared <= 0 or declared > WOOCOMMERCE_MAX_PACKAGE_BYTES:
                    raise DeployError(
                        "The official WooCommerce package length is outside the approved bounds"
                    )
            chunks: list[bytes] = []
            total = 0
            while True:
                chunk = response.read(1024 * 1024)
                if not chunk:
                    break
                total += len(chunk)
                if total > WOOCOMMERCE_MAX_PACKAGE_BYTES:
                    raise DeployError(
                        "The official WooCommerce package exceeded the download ceiling"
                    )
                chunks.append(chunk)
    except DeployError:
        raise
    except Exception as error:
        reason = getattr(error, "reason", type(error).__name__)
        raise DeployError(
            f"The official WooCommerce package could not be verified: {reason}"
        ) from error

    evidence = verify_package_bytes(b"".join(chunks), WOOCOMMERCE_PACKAGE_SHA256)
    evidence["url"] = WOOCOMMERCE_PACKAGE_URL
    evidence["version"] = WOOCOMMERCE_VERSION
    return evidence


def verify_owner_identity(client: Any) -> dict[str, Any]:
    """Require both deployment and catalog-owner capabilities."""

    authenticated = DEPLOY.authenticate(client)
    _, raw_user = client.request(
        "GET",
        "/wp-json/wp/v2/users/me?context=edit&_fields=id,roles,capabilities",
    )
    user = _require_dict(raw_user, "WordPress returned an invalid owner identity")
    capabilities = user.get("capabilities")
    if not isinstance(capabilities, dict):
        raise DeployError("WordPress did not return owner capabilities")
    if capabilities.get("update_plugins") is not True:
        raise DeployError("The WordPress identity cannot install plugins")
    if capabilities.get("manage_options") is not True:
        raise DeployError("The WordPress identity cannot materialize the owner-only catalog")
    return {
        "id": _require_positive_id(user.get("id"), "The WordPress owner ID is invalid"),
        "roles": list(user.get("roles", [])),
        "site_identity": authenticated["site_identity"],
        "update_plugins": True,
        "manage_options": True,
    }


def bridge_call(
    client: Any,
    action: str,
    token: str,
    deployment_id: str,
    **fields: Any,
) -> dict[str, Any]:
    if action not in {"install", "status", "retire"}:
        raise DeployError("The WooCommerce bridge action is not allowlisted")
    payload = {"token": token, "deployment_id": deployment_id}
    payload.update(fields)
    route_id = urllib.parse.quote(deployment_id, safe="")
    _, response = client.request(
        "POST",
        f"/wp-json/{BRIDGE_NAMESPACE}/{route_id}/{action}",
        payload,
    )
    return _require_dict(
        response,
        f"The WooCommerce bridge {action} response is invalid",
    )


def _verify_site_hosts(value: Any, target_host: str) -> dict[str, str]:
    identity = _require_dict(
        value,
        "The WooCommerce bridge did not return a site identity",
    )
    expected = {"home", "siteurl", "rest"}
    if set(identity) != expected:
        raise DeployError("The WooCommerce bridge site identity fields differ")
    for field in sorted(expected):
        if identity[field] != target_host:
            raise DeployError("The WooCommerce bridge site identity differs from the target")
    return {field: str(identity[field]) for field in sorted(expected)}


def _verify_bridge_state(value: Any) -> dict[str, Any]:
    state = _require_dict(value, "The WooCommerce bridge state is invalid")
    if state.get("plugin") != WOOCOMMERCE_PLUGIN:
        raise DeployError("The WooCommerce bridge returned the wrong plugin identity")
    if state.get("expected_version") != WOOCOMMERCE_VERSION:
        raise DeployError("The WooCommerce bridge expected version differs")
    if state.get("header_version") != WOOCOMMERCE_VERSION:
        raise DeployError("The installed WooCommerce header version differs")
    if state.get("runtime_version") != WOOCOMMERCE_VERSION:
        raise DeployError("The loaded WooCommerce runtime version differs")
    if state.get("tree_file_count") != WOOCOMMERCE_TREE_FILE_COUNT:
        raise DeployError("The installed WooCommerce file count differs")
    if state.get("tree_bytes") != WOOCOMMERCE_TREE_BYTES:
        raise DeployError("The installed WooCommerce tree size differs")
    if state.get("tree_sha256") != WOOCOMMERCE_TREE_SHA256:
        raise DeployError("The installed WooCommerce tree digest differs")
    for field in ("active", "runtime_loaded", "product_post_type", "rest_namespace"):
        _require_bool(
            state.get(field),
            True,
            f"The WooCommerce runtime gate {field} did not pass",
        )
    marker = _require_dict(
        state.get("install_recovery_marker"),
        "The WooCommerce install recovery marker readback is invalid",
    )
    _require_bool(
        marker.get("exists"),
        False,
        "The WooCommerce install recovery marker remains after verification",
    )
    _require_bool(
        marker.get("valid"),
        False,
        "The absent WooCommerce install recovery marker state is invalid",
    )
    return {
        "plugin": WOOCOMMERCE_PLUGIN,
        "header_version": WOOCOMMERCE_VERSION,
        "runtime_version": WOOCOMMERCE_VERSION,
        "active": True,
        "runtime_loaded": True,
        "product_post_type": True,
        "rest_namespace": True,
        "tree_file_count": WOOCOMMERCE_TREE_FILE_COUNT,
        "tree_bytes": WOOCOMMERCE_TREE_BYTES,
        "tree_sha256": WOOCOMMERCE_TREE_SHA256,
        "install_recovery_marker": {"exists": False, "valid": False},
    }


def read_gateway_snapshot(client: Any, require_all_disabled: bool = True) -> list[dict[str, Any]]:
    """Read the exact public payment-gateway enablement state without mutation."""

    _, raw_gateways = client.request("GET", WOOCOMMERCE_GATEWAYS_PATH)
    if not isinstance(raw_gateways, list):
        raise DeployError("The WooCommerce gateway readback response is invalid")
    snapshot: list[dict[str, Any]] = []
    seen: set[str] = set()
    for raw_gateway in raw_gateways:
        gateway = _require_dict(
            raw_gateway,
            "The WooCommerce gateway readback contains an invalid row",
        )
        gateway_id = gateway.get("id")
        if not isinstance(gateway_id, str) or not gateway_id or gateway_id in seen:
            raise DeployError("The WooCommerce gateway readback contains an invalid ID")
        enabled = gateway.get("enabled")
        if type(enabled) is not bool:
            raise DeployError(f"The WooCommerce gateway {gateway_id} has an invalid state")
        if require_all_disabled and enabled:
            raise DeployError(f"The WooCommerce gateway {gateway_id} is already enabled")
        seen.add(gateway_id)
        snapshot.append({"id": gateway_id, "enabled": enabled})
    return sorted(snapshot, key=lambda row: row["id"])


def install_and_verify_woocommerce(
    client: Any,
    token: str,
    deployment_id: str,
    target_host: str,
) -> dict[str, Any]:
    """Install exact bytes, then verify them in independent fresh requests."""

    installed = bridge_call(client, "install", token, deployment_id)
    _require_bool(
        installed.get("installed_pending_fresh_status"),
        True,
        "The WooCommerce installer did not reach fresh-request verification",
    )
    if installed.get("package_sha256") != WOOCOMMERCE_PACKAGE_SHA256:
        raise DeployError("The WordPress-side WooCommerce package SHA256 differs")
    installation_action = installed.get("installation_action")
    if installation_action not in {
        "fresh_install",
        "reuse_verified",
        "recovered_partial_reinstall",
    }:
        raise DeployError("The WooCommerce installation action is invalid")
    _verify_site_hosts(installed.get("site_identity"), target_host)
    install_state = _require_dict(
        installed.get("state"),
        "The WooCommerce installer state is invalid",
    )
    if install_state.get("plugin") != WOOCOMMERCE_PLUGIN:
        raise DeployError("The WooCommerce installer returned the wrong plugin")
    if install_state.get("header_version") != WOOCOMMERCE_VERSION:
        raise DeployError("The WooCommerce installer header version differs")
    if install_state.get("tree_sha256") != WOOCOMMERCE_TREE_SHA256:
        raise DeployError("The WooCommerce installer tree digest differs")
    _require_bool(
        install_state.get("active"),
        True,
        "WooCommerce was not active at the end of installation",
    )
    install_marker = _require_dict(
        install_state.get("install_recovery_marker"),
        "The WooCommerce installer marker state is invalid",
    )
    _require_bool(
        install_marker.get("exists"),
        False,
        "The WooCommerce installer left its durable recovery marker",
    )
    _require_bool(
        install_marker.get("valid"),
        False,
        "The WooCommerce installer returned an invalid absent marker state",
    )
    recovery = _require_dict(
        installed.get("install_recovery"),
        "The WooCommerce install recovery evidence is invalid",
    )
    _require_bool(
        recovery.get("marker_cleared"),
        True,
        "The WooCommerce installer did not prove marker clearance",
    )
    cleanup = _require_dict(
        recovery.get("cleanup"),
        "The WooCommerce install cleanup evidence is invalid",
    )
    recovered_partial = installation_action == "recovered_partial_reinstall"
    _require_bool(
        cleanup.get("attempted"),
        recovered_partial,
        "The WooCommerce install cleanup attempt differs from its action",
    )
    _require_bool(
        cleanup.get("verified"),
        recovered_partial,
        "The WooCommerce install cleanup proof differs from its action",
    )
    if recovered_partial:
        prior_marker = _require_dict(
            recovery.get("prior_marker"),
            "The WooCommerce recovered install lacks prior marker evidence",
        )
        _require_bool(
            prior_marker.get("exists"),
            True,
            "The recovered WooCommerce install did not have a prior marker",
        )
        _require_bool(
            prior_marker.get("valid"),
            True,
            "The recovered WooCommerce install prior marker was not authenticated",
        )
        subset = _require_dict(
            recovery.get("partial_tree_proof"),
            "The recovered WooCommerce install lacks a partial-tree proof",
        )
        _require_bool(
            subset.get("byte_exact_subset"),
            True,
            "The recovered WooCommerce directory was not a byte-exact package subset",
        )
        for field in ("unknown_files", "mismatched_files", "symlinks"):
            if type(subset.get(field)) is not int or subset[field] != 0:
                raise DeployError(
                    f"The recovered WooCommerce partial-tree proof reported {field}"
                )
        if (
            subset.get("package_sha256") != prior_marker.get("package_sha256")
            or subset.get("package_url") != prior_marker.get("package_url")
        ):
            raise DeployError(
                "The recovered WooCommerce subset proof differs from its authenticated marker"
            )
        _require_digest(
            subset.get("subset_manifest_sha256"),
            "The recovered WooCommerce subset manifest digest is invalid",
        )
        predelete = _require_dict(
            recovery.get("predelete_snapshot"),
            "The recovered WooCommerce install lacks a pre-delete snapshot",
        )
        _require_bool(
            predelete.get("verified"),
            True,
            "The recovered WooCommerce pre-delete snapshot was not verified",
        )
        if predelete.get("tree_sha256") != subset.get("subset_manifest_sha256"):
            raise DeployError(
                "The recovered WooCommerce directory changed after package proof"
            )
        if type(subset.get("package_bytes")) is not int or subset["package_bytes"] < 1:
            raise DeployError(
                "The recovered WooCommerce subset proof package_bytes is invalid"
            )
        for field in ("verified_file_count", "verified_file_bytes"):
            if type(subset.get(field)) is not int or subset[field] < 0:
                raise DeployError(
                    f"The recovered WooCommerce subset proof {field} is invalid"
                )

    fresh = bridge_call(client, "status", token, deployment_id)
    _verify_site_hosts(fresh.get("site_identity"), target_host)
    bridge_state = _verify_bridge_state(fresh.get("state"))

    _, raw_plugin = client.request("GET", WOOCOMMERCE_PLUGIN_REST_PATH)
    plugin = _require_dict(raw_plugin, "The WordPress plugin readback is invalid")
    if plugin.get("plugin") != WOOCOMMERCE_PLUGIN_REST_ID:
        raise DeployError("WordPress returned the wrong WooCommerce plugin identity")
    if plugin.get("status") != "active":
        raise DeployError("WordPress did not report WooCommerce as active")
    if plugin.get("version") != WOOCOMMERCE_VERSION:
        raise DeployError("WordPress reported a different WooCommerce version")

    _, raw_system_status = client.request("GET", WOOCOMMERCE_SYSTEM_STATUS_PATH)
    system_status = _require_dict(
        raw_system_status,
        "The WooCommerce REST system status response is invalid",
    )
    environment = _require_dict(
        system_status.get("environment"),
        "The WooCommerce REST environment response is invalid",
    )
    rest_version = environment.get("version", environment.get("wc_version", ""))
    if rest_version != WOOCOMMERCE_VERSION:
        raise DeployError("The WooCommerce REST runtime version differs")

    gateway_snapshot = read_gateway_snapshot(client, require_all_disabled=True)

    return {
        "plugin": WOOCOMMERCE_PLUGIN,
        "version": WOOCOMMERCE_VERSION,
        "status": "active",
        "bridge": bridge_state,
        "rest_available": True,
        "rest_runtime_version": WOOCOMMERCE_VERSION,
        "package_sha256": WOOCOMMERCE_PACKAGE_SHA256,
        "installation_action": installation_action,
        "install_recovery": recovery,
        "gateway_configuration": {
            "configured_count": 0,
            "read_only_verification": True,
            "snapshot": gateway_snapshot,
            "inspected_ids": [row["id"] for row in gateway_snapshot],
        },
    }


def verify_catalog_dry_run(value: Any) -> dict[str, Any]:
    response = _require_dict(value, "The live catalog dry-run response is invalid")
    if response.get("schema") != CATALOG_STATUS_SCHEMA:
        raise DeployError("The live catalog dry-run schema differs")
    if response.get("mode") != "dry_run":
        raise DeployError("The live catalog dry-run mode differs")
    _require_bool(
        response.get("write_performed"),
        False,
        "The live catalog dry-run reported a write",
    )
    if type(response.get("product_count")) is not int or response["product_count"] != EXPECTED_PRODUCT_COUNT:
        raise DeployError("The live catalog dry-run product count differs")
    actions = _require_dict(
        response.get("actions"),
        "The live catalog dry-run actions are invalid",
    )
    if set(actions) != set(EXPECTED_PRODUCT_CODES):
        raise DeployError("The live catalog dry-run product allowlist differs")
    for code in EXPECTED_PRODUCT_CODES:
        action = _require_dict(actions[code], f"The dry-run action for {code} is invalid")
        if action.get("sku") != code:
            raise DeployError(f"The dry-run SKU for {code} differs")
        if action.get("product") not in {"create", "update"}:
            raise DeployError(f"The dry-run product action for {code} differs")
        if action.get("attachment") not in {"import", "reuse"}:
            raise DeployError(f"The dry-run attachment action for {code} differs")
        if action.get("product") == "create":
            if action.get("stock_action") != "initialize":
                raise DeployError(f"The dry-run stock initialization for {code} differs")
            if type(action.get("initial_stock")) is not int or action["initial_stock"] != 1:
                raise DeployError(f"The dry-run initial stock for {code} differs")
        else:
            if action.get("stock_action") != "preserve" or action.get("initial_stock") is not None:
                raise DeployError(f"The dry-run stock preservation for {code} differs")
        if action.get("backorders") != "no":
            raise DeployError(f"The dry-run backorder policy for {code} differs")
        if action.get("price_ils") != EXPECTED_ILS_PRICES[code]:
            raise DeployError(f"The dry-run market price for {code} differs")
        _require_digest(
            action.get("asset_sha256"),
            f"The dry-run asset SHA256 for {code} is invalid",
        )
    return {
        "schema": CATALOG_STATUS_SCHEMA,
        "mode": "dry_run",
        "write_performed": False,
        "product_count": EXPECTED_PRODUCT_COUNT,
        "registry_digest": _require_digest(
            response.get("registry_digest"),
            "The live catalog registry digest is invalid",
        ),
        "price_digest": _require_digest(
            response.get("price_digest"),
            "The live catalog price digest is invalid",
        ),
        "asset_digest": _require_digest(
            response.get("asset_digest"),
            "The live catalog asset digest is invalid",
        ),
        "relation_digest": _require_digest(
            response.get("relation_digest"),
            "The live catalog relation digest is invalid",
        ),
    }


def _verify_exact_product_ids(value: Any, context: str) -> dict[str, int]:
    product_ids = _require_dict(value, f"The {context} product IDs are invalid")
    if set(product_ids) != set(EXPECTED_PRODUCT_CODES):
        raise DeployError(f"The {context} product ID allowlist differs")
    normalized = {
        code: _require_positive_id(
            product_ids[code],
            f"The {context} product ID for {code} is invalid",
        )
        for code in EXPECTED_PRODUCT_CODES
    }
    if len(set(normalized.values())) != EXPECTED_PRODUCT_COUNT:
        raise DeployError(f"The {context} product IDs are not unique")
    return normalized


def verify_catalog_apply(value: Any, expected_deployment_id: str | None = None) -> dict[str, Any]:
    response = _require_dict(value, "The live catalog apply response is invalid")
    if response.get("schema") != CATALOG_STATUS_SCHEMA or response.get("mode") != "apply":
        raise DeployError("The live catalog apply contract differs")
    _require_bool(
        response.get("write_performed"),
        True,
        "The live catalog apply did not report its write",
    )
    _require_bool(response.get("ready"), True, "The live catalog apply is not ready")
    if type(response.get("product_count")) is not int or response["product_count"] != EXPECTED_PRODUCT_COUNT:
        raise DeployError("The live catalog apply product count differs")
    product_ids = _verify_exact_product_ids(response.get("product_ids"), "apply")
    receipt = _require_dict(response.get("receipt"), "The live catalog receipt is invalid")
    if receipt.get("schema") != CATALOG_RECEIPT_SCHEMA or receipt.get("status") != "verified":
        raise DeployError("The live catalog receipt contract differs")
    deployment_id = receipt.get("deployment_id")
    if not isinstance(deployment_id, str) or not re.fullmatch(r"[A-Za-z0-9._-]{8,96}", deployment_id):
        raise DeployError("The live catalog receipt deployment ID is invalid")
    if expected_deployment_id is not None and deployment_id != expected_deployment_id:
        raise DeployError("The live catalog receipt belongs to a different deployment")
    mutation_id = receipt.get("mutation_id")
    if not isinstance(mutation_id, str) or not re.fullmatch(r"[A-Za-z0-9-]{16,64}", mutation_id):
        raise DeployError("The live catalog receipt mutation ID is invalid")
    if type(receipt.get("product_count")) is not int or receipt["product_count"] != EXPECTED_PRODUCT_COUNT:
        raise DeployError("The live catalog receipt product count differs")
    if _verify_exact_product_ids(receipt.get("product_ids"), "receipt") != product_ids:
        raise DeployError("The live catalog apply and receipt bindings differ")
    product_digests = _require_dict(
        receipt.get("product_digests"),
        "The live catalog product digests are invalid",
    )
    if set(product_digests) != set(EXPECTED_PRODUCT_CODES):
        raise DeployError("The live catalog product digest allowlist differs")
    for code in EXPECTED_PRODUCT_CODES:
        _require_digest(
            product_digests[code],
            f"The live catalog product digest for {code} is invalid",
        )
    stock_receipts = _require_dict(
        receipt.get("initial_stock_receipts"),
        "The live catalog initial stock receipts are invalid",
    )
    if set(stock_receipts) != set(EXPECTED_PRODUCT_CODES):
        raise DeployError("The live catalog initial stock receipt allowlist differs")
    for code in EXPECTED_PRODUCT_CODES:
        stock = _require_dict(
            stock_receipts[code],
            f"The live catalog initial stock receipt for {code} is invalid",
        )
        if stock.get("product_id") != product_ids[code]:
            raise DeployError(f"The live catalog initial stock product for {code} differs")
        if type(stock.get("policy_quantity")) is not int or stock["policy_quantity"] != 1:
            raise DeployError(f"The live catalog initial stock policy for {code} differs")
        _require_bool(stock.get("initialized"), True, f"The live catalog stock marker for {code} differs")
        if type(stock.get("initialized_now")) is not bool:
            raise DeployError(f"The live catalog stock initialization flag for {code} is invalid")
        if stock["initialized_now"]:
            readback = _require_dict(
                stock.get("readback"),
                f"The live catalog initial stock readback for {code} is invalid",
            )
            _require_bool(readback.get("managing_stock"), True, f"The live catalog managed stock for {code} differs")
            if type(readback.get("quantity")) is not int or readback["quantity"] != 1:
                raise DeployError(f"The live catalog initial quantity for {code} differs")
            if readback.get("status") != "instock" or readback.get("backorders") != "no":
                raise DeployError(f"The live catalog initial stock status for {code} differs")
    for field in (
        "registry_digest",
        "price_digest",
        "asset_digest",
        "relation_digest",
        "configuration_digest",
        "bindings_digest",
        "initial_stock_digest",
    ):
        _require_digest(receipt.get(field), f"The live catalog receipt {field} is invalid")
    if type(receipt.get("materialized_by")) is not int or receipt["materialized_by"] <= 0:
        raise DeployError("The live catalog receipt owner is invalid")
    if not isinstance(receipt.get("materialized_at"), str) or not receipt["materialized_at"]:
        raise DeployError("The live catalog receipt timestamp is invalid")
    page_cache = _require_dict(
        response.get("page_cache_purge"),
        "The live catalog page-cache purge receipt is invalid",
    )
    if set(page_cache) != {"upress", "litespeed", "attempts"}:
        raise DeployError("The live catalog page-cache purge receipt differs")
    upress = _require_dict(
        page_cache.get("upress"),
        "The live catalog UPress page-cache receipt is invalid",
    )
    litespeed = _require_dict(
        page_cache.get("litespeed"),
        "The live catalog LiteSpeed page-cache receipt is invalid",
    )
    if set(upress) != {"detected", "request_completed"} or set(litespeed) != {
        "listener_detected",
        "signal_sent",
    }:
        raise DeployError("The live catalog page-cache provider receipt differs")
    if type(upress.get("detected")) is not bool or type(upress.get("request_completed")) is not bool:
        raise DeployError("The live catalog UPress page-cache receipt has invalid flags")
    if upress["detected"] and not upress["request_completed"]:
        raise DeployError("The live catalog UPress page-cache purge was not completed")
    if type(litespeed.get("listener_detected")) is not bool or litespeed.get("signal_sent") is not True:
        raise DeployError("The live catalog LiteSpeed page-cache purge was not signalled")
    if type(page_cache.get("attempts")) is not int or page_cache["attempts"] not in {1, 2}:
        raise DeployError("The live catalog page-cache purge attempt count is invalid")
    return {
        "schema": CATALOG_STATUS_SCHEMA,
        "mode": "apply",
        "ready": True,
        "write_performed": True,
        "product_count": EXPECTED_PRODUCT_COUNT,
        "product_ids": product_ids,
        "bindings_digest": receipt["bindings_digest"],
        "registry_digest": receipt["registry_digest"],
        "price_digest": receipt["price_digest"],
        "asset_digest": receipt["asset_digest"],
        "relation_digest": receipt["relation_digest"],
        "configuration_digest": receipt["configuration_digest"],
        "initial_stock_digest": receipt["initial_stock_digest"],
        "deployment_id": deployment_id,
        "mutation_id": mutation_id,
        "page_cache_purge": page_cache,
    }


def verify_catalog_status(value: Any, expected_deployment_id: str | None = None) -> dict[str, Any]:
    response = _require_dict(value, "The live catalog status response is invalid")
    if response.get("schema") != CATALOG_STATUS_SCHEMA:
        raise DeployError("The live catalog status schema differs")
    _require_bool(response.get("ready"), True, "The live catalog status is not ready")
    _require_bool(response.get("strict"), True, "The live catalog status is not strict")
    if response.get("reason") != "":
        raise DeployError("The live catalog status contains a failure reason")
    if type(response.get("product_count")) is not int or response["product_count"] != EXPECTED_PRODUCT_COUNT:
        raise DeployError("The live catalog status product count differs")
    product_ids = _verify_exact_product_ids(response.get("product_ids"), "status")
    receipt = _require_dict(
        response.get("receipt"),
        "The live catalog status receipt is invalid",
    )
    if receipt.get("schema") != CATALOG_RECEIPT_SCHEMA or receipt.get("status") != "verified":
        raise DeployError("The live catalog status receipt differs")
    deployment_id = receipt.get("deployment_id")
    if not isinstance(deployment_id, str) or not re.fullmatch(r"[A-Za-z0-9._-]{8,96}", deployment_id):
        raise DeployError("The live catalog status deployment ID is invalid")
    if expected_deployment_id is not None and deployment_id != expected_deployment_id:
        raise DeployError("The live catalog status belongs to a different deployment")
    mutation_id = receipt.get("mutation_id")
    if not isinstance(mutation_id, str) or not re.fullmatch(r"[A-Za-z0-9-]{16,64}", mutation_id):
        raise DeployError("The live catalog status mutation ID is invalid")
    binding_digest = _require_digest(
        receipt.get("bindings_digest"),
        "The live catalog status binding digest is invalid",
    )
    initial_stock_digest = _require_digest(
        receipt.get("initial_stock_digest"),
        "The live catalog status initial stock digest is invalid",
    )
    return {
        "schema": CATALOG_STATUS_SCHEMA,
        "ready": True,
        "strict": True,
        "product_count": EXPECTED_PRODUCT_COUNT,
        "product_ids": product_ids,
        "bindings_digest": binding_digest,
        "initial_stock_digest": initial_stock_digest,
        "deployment_id": deployment_id,
        "mutation_id": mutation_id,
        "materialized_at": str(receipt.get("materialized_at", "")),
    }


def materialize_catalog(client: Any, deployment_id: str) -> dict[str, Any]:
    """Run dry-run, apply and strict status in that fail-closed order."""

    try:
        _, raw_dry_run = client.request(
            "POST",
            CATALOG_ROUTE,
            {"mode": "dry_run", "confirm": False, "deployment_id": deployment_id},
        )
        dry_run = verify_catalog_dry_run(raw_dry_run)
    except Exception as error:
        raise CatalogMaterializationError("dry_run", error) from error

    try:
        _, raw_apply = client.request(
            "POST",
            CATALOG_ROUTE,
            {"mode": "apply", "confirm": True, "deployment_id": deployment_id},
        )
        applied = verify_catalog_apply(raw_apply, deployment_id)
        for digest_field in (
            "registry_digest",
            "price_digest",
            "asset_digest",
            "relation_digest",
        ):
            if applied[digest_field] != dry_run[digest_field]:
                raise DeployError(
                    f"The live catalog apply {digest_field} differs from dry-run"
                )
    except Exception as error:
        raise CatalogMaterializationError("apply", error) from error

    try:
        _, raw_status = client.request("GET", CATALOG_ROUTE)
        status = verify_catalog_status(raw_status, deployment_id)
    except Exception as error:
        raise CatalogMaterializationError("status", error) from error
    if applied["product_ids"] != status["product_ids"]:
        raise CatalogMaterializationError(
            "status",
            DeployError("The live catalog apply and strict status bindings differ"),
        )
    if applied["bindings_digest"] != status["bindings_digest"]:
        raise CatalogMaterializationError(
            "status",
            DeployError("The live catalog apply and strict status digests differ"),
        )
    if applied["initial_stock_digest"] != status["initial_stock_digest"]:
        raise CatalogMaterializationError(
            "status",
            DeployError("The live catalog initial stock receipt changed after apply"),
        )
    if applied["mutation_id"] != status["mutation_id"]:
        raise CatalogMaterializationError(
            "status",
            DeployError("The live catalog mutation identity changed after apply"),
        )
    return {"dry_run": dry_run, "apply": applied, "status": status}


def capture_catalog_failure_evidence(
    client: Any,
    failed_phase: str,
    expected_deployment_id: str,
) -> dict[str, Any]:
    """Capture read-only recovery evidence and never retry the catalog apply."""

    evidence: dict[str, Any] = {
        "failed_phase": failed_phase,
        "automatic_apply_retry": False,
        "strict_status_probe": {"attempted": True, "verified": False},
        "dry_run_probe": {"attempted": True, "verified": False},
    }
    try:
        _, raw_status = client.request("GET", CATALOG_ROUTE)
        status = _require_dict(
            raw_status,
            "The recovery status response is invalid",
        )
        if status.get("ready") is True:
            verified_status = verify_catalog_status(status, expected_deployment_id)
            evidence["strict_status_probe"] = {
                "attempted": True,
                "verified": True,
                "ready": True,
                "product_count": verified_status["product_count"],
                "bindings_digest": verified_status["bindings_digest"],
                "deployment_id": verified_status["deployment_id"],
                "current_deployment_verified": True,
            }
        else:
            if status.get("schema") != CATALOG_STATUS_SCHEMA:
                raise DeployError("The recovery status schema differs")
            _require_bool(
                status.get("ready"),
                False,
                "The recovery status ready flag is invalid",
            )
            _require_bool(
                status.get("strict"),
                True,
                "The recovery status is not strict",
            )
            product_count = status.get("product_count")
            if type(product_count) is not int or not 0 <= product_count <= EXPECTED_PRODUCT_COUNT:
                raise DeployError("The recovery status product count is invalid")
            reason = status.get("reason")
            if not isinstance(reason, str) or not reason:
                raise DeployError("The recovery status failure reason is invalid")
            evidence["strict_status_probe"] = {
                "attempted": True,
                "verified": True,
                "ready": False,
                "product_count": product_count,
                "reason": reason,
            }
    except Exception as error:
        evidence["strict_status_probe"] = {
            "attempted": True,
            "verified": False,
            "error_type": type(error).__name__,
        }

    try:
        _, raw_dry_run = client.request(
            "POST",
            CATALOG_ROUTE,
            {
                "mode": "dry_run",
                "confirm": False,
                "deployment_id": expected_deployment_id,
            },
        )
        verified_dry_run = verify_catalog_dry_run(raw_dry_run)
        evidence["dry_run_probe"] = {
            "attempted": True,
            "verified": True,
            "write_performed": False,
            "product_count": verified_dry_run["product_count"],
            "registry_digest": verified_dry_run["registry_digest"],
            "price_digest": verified_dry_run["price_digest"],
            "asset_digest": verified_dry_run["asset_digest"],
            "relation_digest": verified_dry_run["relation_digest"],
        }
    except Exception as error:
        evidence["dry_run_probe"] = {
            "attempted": True,
            "verified": False,
            "error_type": type(error).__name__,
        }
    return evidence


def retire_bridge(
    client: Any,
    snippet_id: int,
    token: str,
    deployment_id: str,
) -> dict[str, Any]:
    """Permanently delete the exact bridge row and independently prove 404."""

    recovered_response = False
    try:
        bridge_call(
            client,
            "retire",
            token,
            deployment_id,
            snippet_ids=[snippet_id],
        )
    except DeployError:
        if DEPLOY.get_snippet_by_id(client, snippet_id) is not None:
            try:
                client.request(
                    "POST",
                    f"/wp-json/code-snippets/v1/snippets/{snippet_id}/deactivate",
                    expected=(200, 404),
                )
            finally:
                raise
        recovered_response = True

    if DEPLOY.get_snippet_by_id(client, snippet_id) is not None:
        raise DeployError("The temporary WooCommerce bridge row remains present")
    prove_bridge_route_404(client, token, deployment_id)
    return {
        "snippet_id": snippet_id,
        "row_absence_verified": True,
        "route_404": True,
        "response_recovered": recovered_response,
    }


def prove_bridge_route_404(client: Any, token: str, deployment_id: str) -> None:
    """Prove that no temporary WooCommerce route is still executable."""

    route_id = urllib.parse.quote(deployment_id, safe="")
    status_code, response = client.request(
        "POST",
        f"/wp-json/{BRIDGE_NAMESPACE}/{route_id}/status",
        {"token": token, "deployment_id": deployment_id},
        expected=(404,),
    )
    rest_code = response.get("code", "") if isinstance(response, dict) else ""
    if status_code != 404 or rest_code not in {"", "rest_no_route"}:
        raise DeployError("The temporary WooCommerce bridge route did not retire")


def recover_interrupted_bridge(
    client: Any,
    token: str,
    deployment_id: str,
    target_host: str,
) -> dict[str, Any]:
    """Recover only contract-authenticated rows from an interrupted run."""

    name = DEPLOY.snippet_name(deployment_id)
    # The canonical helper name is historical. It enumerates status=all and
    # intentionally returns inactive rows too, so recovery proves deletion.
    matches = DEPLOY.find_active_snippet_ids(client, name)
    removed: list[dict[str, Any]] = []
    for snippet_id in matches:
        row = DEPLOY.get_snippet_by_id(client, snippet_id)
        if row is None:
            continue
        if str(row.get("name", "")) != name:
            raise DeployError("Bridge recovery read back a different snippet name")
        contract = verify_rendered_bridge_contract(
            str(row.get("code", "")),
            token,
            deployment_id,
            target_host,
            name,
        )
        if not bool(row.get("active")):
            client.request(
                "POST",
                f"/wp-json/code-snippets/v1/snippets/{snippet_id}/activate",
            )
        bridge_call(
            client,
            "retire",
            token,
            deployment_id,
            snippet_ids=[snippet_id],
        )
        if DEPLOY.get_snippet_by_id(client, snippet_id) is not None:
            raise DeployError("Bridge recovery could not prove exact row deletion")
        removed.append(
            {
                "snippet_id": snippet_id,
                "contract": contract,
                "row_absence_verified": True,
            }
        )

    if DEPLOY.find_active_snippet_ids(client, name):
        raise DeployError("Bridge recovery left an exact-name snippet row")
    prove_bridge_route_404(client, token, deployment_id)
    return {
        "exact_name": name,
        "removed_ids": [item["snippet_id"] for item in removed],
        "authenticated_rows": removed,
        "row_absence_verified": True,
        "route_404": True,
    }


def recover_all_interrupted_commerce_bridges(
    client: Any,
    app_password: str,
    target_host: str,
) -> dict[str, Any]:
    """Delete every authenticated Complete99 commerce bridge, including old releases."""

    commerce_prefix = DEPLOY.SNIPPET_PREFIX + "c99-commerce-"
    candidates: list[tuple[int, str]] = []
    for summary in DEPLOY.active_snippets(client):
        name = str(summary.get("name", ""))
        snippet_id = int(summary.get("id", 0) or 0)
        if name.startswith(commerce_prefix) and snippet_id > 0:
            candidates.append((snippet_id, name))

    removed: list[dict[str, Any]] = []
    for snippet_id, name in sorted(set(candidates)):
        deployment_id = name[len(DEPLOY.SNIPPET_PREFIX) :]
        if not re.fullmatch(r"c99-commerce-[A-Za-z0-9._-]{1,82}", deployment_id):
            raise DeployError("Commerce bridge recovery found an invalid deployment ID")
        token = derive_bridge_token(app_password, deployment_id)
        row = DEPLOY.get_snippet_by_id(client, snippet_id)
        if row is None:
            continue
        if str(row.get("name", "")) != name:
            raise DeployError("Commerce bridge recovery read back a different row name")
        contract = verify_rendered_bridge_contract(
            str(row.get("code", "")),
            token,
            deployment_id,
            target_host,
            name,
        )
        if not bool(row.get("active")):
            client.request(
                "POST",
                f"/wp-json/code-snippets/v1/snippets/{snippet_id}/activate",
            )
        bridge_call(
            client,
            "retire",
            token,
            deployment_id,
            snippet_ids=[snippet_id],
        )
        if DEPLOY.get_snippet_by_id(client, snippet_id) is not None:
            raise DeployError("Commerce bridge recovery left an exact row")
        prove_bridge_route_404(client, token, deployment_id)
        removed.append(
            {
                "snippet_id": snippet_id,
                "deployment_id": deployment_id,
                "contract": contract,
                "row_absence_verified": True,
                "route_404": True,
            }
        )

    remaining = [
        str(row.get("name", ""))
        for row in DEPLOY.active_snippets(client)
        if str(row.get("name", "")).startswith(commerce_prefix)
    ]
    if remaining:
        raise DeployError("Commerce bridge recovery left an orphan row")
    return {
        "prefix": commerce_prefix,
        "removed": removed,
        "orphan_count": 0,
    }


def write_audit(directory: Path, audit: dict[str, Any]) -> Path:
    directory.mkdir(parents=True, exist_ok=True)
    audit_id = str(audit.get("audit_id", audit["deployment_id"]))
    if not re.fullmatch(r"[A-Za-z0-9._-]{8,128}", audit_id):
        raise DeployError("The commerce audit ID is invalid")
    path = directory / f"{audit_id}.json"
    path.write_text(
        json.dumps(audit, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    return path


def _safe_error(error: Exception, secrets_to_redact: list[str]) -> str:
    message = str(error)
    for secret in secrets_to_redact:
        if secret:
            message = message.replace(secret, "[redacted]")
    return message[:1000]


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default=os.environ.get("WP_BASE_URL", ""))
    parser.add_argument(
        "--user",
        default=os.environ.get("WP_DEPLOY_USER", os.environ.get("WP_USER", "")),
    )
    parser.add_argument(
        "--allowed-deploy-hosts",
        default=os.environ.get("WP_ALLOWED_DEPLOY_HOSTS", ""),
    )
    parser.add_argument("--deployment-id", default="")
    parser.add_argument("--bootstrap-code-snippets", action="store_true")
    parser.add_argument("--recover-bridge", action="store_true")
    parser.add_argument("--local-test", action="store_true")
    parser.add_argument("--audit-dir", type=Path, default=ROOT / "commerce-audit")
    args = parser.parse_args()

    app_password = os.environ.get("WP_APP_PASSWORD", "")
    if not args.base_url or not args.user or not app_password:
        raise DeployError("WP_BASE_URL, WP_DEPLOY_USER and WP_APP_PASSWORD are required")
    target = DEPLOY.validate_target_url(
        args.base_url,
        args.local_test,
        args.allowed_deploy_hosts,
    )
    target_host = (target.hostname or "").lower()
    deployment_id = args.deployment_id or (
        f"c99-commerce-{int(time.time())}-{secrets.token_hex(4)}"
    )
    if not re.fullmatch(r"[A-Za-z0-9._-]{8,96}", deployment_id):
        raise DeployError("Deployment ID must contain 8-96 safe characters")

    token = derive_bridge_token(app_password, deployment_id)
    client = DEPLOY.Client(
        args.base_url,
        args.user,
        app_password,
        allow_local_http=args.local_test,
        allowed_deploy_hosts=args.allowed_deploy_hosts,
    )

    if args.recover_bridge:
        recovery_audit: dict[str, Any] = {
            "schema": "complete99-woocommerce-bridge-recovery-audit/v1",
            "audit_id": f"{deployment_id}-recovery",
            "deployment_id": deployment_id,
            "commit": os.environ.get("GITHUB_SHA", "").strip(),
            "target": args.base_url.rstrip("/"),
            "started_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
            "result": "started",
        }
        recovery_error: Exception | None = None
        try:
            recovery_audit["owner_identity"] = verify_owner_identity(client)
            DEPLOY.ensure_code_snippets(client, False)
            recovery_audit["all_run_cleanup"] = recover_all_interrupted_commerce_bridges(
                client,
                app_password,
                target_host,
            )
            recovery_audit["cleanup"] = recover_interrupted_bridge(
                client,
                token,
                deployment_id,
                target_host,
            )
            recovery_audit["result"] = "verified"
        except Exception as error:
            recovery_error = error
            recovery_audit["result"] = "failed"
            recovery_audit["error"] = {
                "type": type(error).__name__,
                "message": _safe_error(error, [app_password, token]),
            }
        recovery_audit["finished_at"] = time.strftime(
            "%Y-%m-%dT%H:%M:%SZ",
            time.gmtime(),
        )
        recovery_path = write_audit(args.audit_dir.resolve(), recovery_audit)
        if recovery_error is not None:
            print(f"WooCommerce bridge recovery failed. Audit: {recovery_path}", file=sys.stderr)
            return 1
        print(f"WooCommerce bridge recovery verified. Audit: {recovery_path}")
        return 0

    audit: dict[str, Any] = {
        "schema": "complete99-woocommerce-materialization-audit/v1",
        "deployment_id": deployment_id,
        "commit": os.environ.get("GITHUB_SHA", "").strip(),
        "target": args.base_url.rstrip("/"),
        "woocommerce_plugin": WOOCOMMERCE_PLUGIN,
        "woocommerce_version": WOOCOMMERCE_VERSION,
        "woocommerce_package_url": WOOCOMMERCE_PACKAGE_URL,
        "woocommerce_package_sha256": WOOCOMMERCE_PACKAGE_SHA256,
        "expected_product_count": EXPECTED_PRODUCT_COUNT,
        "started_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "result": "started",
    }
    snippet_id: int | None = None
    gate = "package"
    primary_error: Exception | None = None
    try:
        audit["official_package"] = fetch_verified_official_package()
        gate = "owner-authentication"
        audit["owner_identity"] = verify_owner_identity(client)
        gate = "code-snippets"
        DEPLOY.ensure_code_snippets(client, args.bootstrap_code_snippets)
        gate = "bridge-all-run-recovery"
        audit["all_run_recovery"] = recover_all_interrupted_commerce_bridges(
            client,
            app_password,
            target_host,
        )
        gate = "bridge-precreate-recovery"
        audit["precreate_recovery"] = recover_interrupted_bridge(
            client,
            token,
            deployment_id,
            target_host,
        )
        gate = "bridge-create"
        code = render_bridge(token, deployment_id, target_host)
        audit["temporary_bridge_source_contract"] = verify_rendered_bridge_contract(
            code,
            token,
            deployment_id,
            target_host,
            DEPLOY.snippet_name(deployment_id),
        )
        snippet_id = DEPLOY.create_snippet(client, code, deployment_id)
        audit["temporary_snippet_id"] = snippet_id
        gate = "woocommerce-install-and-runtime"
        audit["woocommerce"] = install_and_verify_woocommerce(
            client,
            token,
            deployment_id,
            target_host,
        )
        gate = "catalog-dry-run-apply-status"
        try:
            audit["catalog"] = materialize_catalog(client, deployment_id)
        except CatalogMaterializationError as error:
            gate = f"catalog-{error.phase}"
            audit["catalog_failure_recovery"] = capture_catalog_failure_evidence(
                client,
                error.phase,
                deployment_id,
            )
            recovery = audit["catalog_failure_recovery"].get("strict_status_probe", {})
            if not bool(recovery.get("current_deployment_verified")):
                raise
            audit["catalog"] = {
                "response_recovered": True,
                "status": recovery,
            }
        gate = "gateway-post-apply-readback"
        gateway_before = audit["woocommerce"]["gateway_configuration"]["snapshot"]
        gateway_after = read_gateway_snapshot(client, require_all_disabled=True)
        if gateway_after != gateway_before:
            raise DeployError(
                "WooCommerce payment gateway enablement changed during catalog materialization"
            )
        audit["gateway_post_apply"] = {
            "unchanged": True,
            "read_only_verification": True,
            "snapshot": gateway_after,
        }
        audit["result"] = "verified"
    except Exception as error:
        primary_error = error
        audit["result"] = "failed"
        audit["failure_gate"] = gate
        audit["error"] = {
            "type": type(error).__name__,
            "message": _safe_error(error, [app_password, token]),
        }
        if isinstance(error, CatalogMaterializationError) and error.diagnostic:
            audit["error"]["diagnostic"] = error.diagnostic
        if snippet_id is not None:
            try:
                audit["failure_bridge_inspect"] = bridge_call(
                    client,
                    "status",
                    token,
                    deployment_id,
                )
            except Exception as inspect_error:
                audit["failure_bridge_inspect_error"] = {
                    "type": type(inspect_error).__name__,
                    "message": _safe_error(inspect_error, [app_password, token]),
                }
    finally:
        if snippet_id is not None:
            try:
                audit["cleanup"] = retire_bridge(
                    client,
                    snippet_id,
                    token,
                    deployment_id,
                )
            except Exception as cleanup_error:
                audit["result"] = "failed"
                audit["cleanup_error"] = {
                    "type": type(cleanup_error).__name__,
                    "message": _safe_error(cleanup_error, [app_password, token]),
                }
                if primary_error is None:
                    primary_error = cleanup_error
        audit["finished_at"] = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
        audit_path = write_audit(args.audit_dir.resolve(), audit)

    if primary_error is not None:
        print(
            f"WooCommerce materialization failed at {audit.get('failure_gate', 'cleanup')}. "
            f"Audit: {audit_path}",
            file=sys.stderr,
        )
        return 1
    if audit.get("result") != "verified":
        print(f"WooCommerce materialization did not verify. Audit: {audit_path}", file=sys.stderr)
        return 1
    print(
        f"WooCommerce {WOOCOMMERCE_VERSION} and {EXPECTED_PRODUCT_COUNT} products verified. "
        f"Audit: {audit_path}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
