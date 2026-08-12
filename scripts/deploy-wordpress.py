#!/usr/bin/env python3
"""Transactional Complete99 plugin deployer using a temporary authenticated bridge."""

from __future__ import annotations

import argparse
import base64
import hashlib
import http.client
import io
import json
import os
import re
import secrets
import ssl
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from dataclasses import dataclass
from pathlib import Path, PurePosixPath
from typing import Any

ROOT = Path(__file__).resolve().parents[1]
SLUG = "complete99-platform"
BRIDGE_TEMPLATE = ROOT / "deploy" / "temporary-bridge.php"
USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/150.0.0.0 Safari/537.36"
)
ALLOWED_PRODUCTION_HOSTS = {"complete99.co.il", "www.complete99.co.il"}
SUPPORTED_TRANSITIONAL_HOSTS = {"a235232-tmp.s1242.upress.link"}
ALLOWED_LOCAL_TEST_HOSTS = {"127.0.0.1", "localhost", "::1"}
PLUGIN_REST_PATH = "/wp-json/wp/v2/plugins/complete99-platform/complete99-platform?context=edit"
REST_IDENTITY_PATH = "/wp-json/?_fields=home,url"
SNIPPET_PREFIX = "tmp-complete99-deploy-"
BOOTSTRAP_SNIPPET_NAME = "c99-deploy-bootstrap"
BOOTSTRAP_SNIPPET_KNOWN_ID = 5
PUBLIC_READ_ATTEMPTS = 3
PUBLIC_READ_TIMEOUT_SECONDS = 30
PUBLIC_READ_RETRY_DELAYS_SECONDS = (2, 5)
PUBLIC_READ_RETRYABLE_HTTP = frozenset({502, 503, 504})
MIN_PACKAGE_UPLOAD_BYTES = 2 * 1024 * 1024
MAX_PACKAGE_UPLOAD_BYTES = 32 * 1024 * 1024
PACKAGE_UPLOAD_HEADROOM_BYTES = 64 * 1024
ARTIFACT_STAGE_CHUNK_BYTES = 1024 * 1024
ARTIFACT_STAGE_TRANSPORT_ATTEMPTS = 3
EXPECTED_CATALOG_PRODUCT_COUNT = 36
CATALOG_PRODUCT_CODES = frozenset(
    {
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
        "product-yamaroku-tsurubishio-500ml",
        "product-kito-yuzu-juice-100ml",
        "product-fresh-japanese-wasabi-250g",
        "product-hagane-zame-large",
        "product-koshihikari-uozu-2kg",
        "product-hishiroku-dried-rice-koji-500g",
        "product-hishiroku-chouhaku-kin-20g",
        "product-fresh-wasabi-50-60g",
    }
)
if len(CATALOG_PRODUCT_CODES) != EXPECTED_CATALOG_PRODUCT_COUNT:
    raise RuntimeError("The deployment catalog diagnostic allowlist count differs")
_CATALOG_CAUSES_BY_STAGE = {
    "request": {
        "complete99_live_catalog_confirmation_required",
        "complete99_live_catalog_deployment_id",
    },
    "dependency": {"complete99_live_catalog_woocommerce_required"},
    "registry": {"complete99_live_catalog_registry_invalid"},
    "preflight": {
        "complete99_live_catalog_unallowlisted_managed_product",
        "complete99_live_catalog_product_binding_conflict",
        "complete99_live_catalog_unowned_product_conflict",
        "complete99_live_catalog_asset_binding_conflict",
        "complete99_live_catalog_unowned_asset_conflict",
        "complete99_live_catalog_query_failed",
    },
    "transaction": {
        "complete99_live_catalog_transaction_driver",
        "complete99_live_catalog_transaction_engine",
        "complete99_live_catalog_lock_driver",
        "complete99_live_catalog_locked",
        "complete99_live_catalog_runtime_transaction_start",
        "complete99_live_catalog_runtime_transaction_commit",
    },
    "configuration": {
        "complete99_live_catalog_option_readback_failed",
        "complete99_live_catalog_option_type_invalid",
        "complete99_live_catalog_address_readback_failed",
        "complete99_live_catalog_public_page_missing",
        "complete99_live_catalog_native_shop_api_missing",
        "complete99_live_catalog_native_shop_page_invalid",
        "complete99_live_catalog_woocommerce_page_missing",
        "complete99_live_catalog_cart_page_write_failed",
        "complete99_live_catalog_cart_page_readback_failed",
        "complete99_live_catalog_tax_api_missing",
        "complete99_live_catalog_tax_rate_conflict",
        "complete99_live_catalog_tax_rate_readback_failed",
        "complete99_live_catalog_shipping_api_missing",
        "complete99_live_catalog_pickup_binding_conflict",
        "complete99_live_catalog_pickup_conflict",
        "complete99_live_catalog_pickup_write_failed",
        "complete99_live_catalog_pickup_binding_invalid",
        "complete99_live_catalog_pickup_enable_failed",
        "complete99_live_catalog_pickup_readback_failed",
        "complete99_live_catalog_configuration_readback_failed",
        "complete99_live_catalog_cart_or_pickup_readback_failed",
    },
    "taxonomy": {
        "complete99_live_catalog_term_conflict",
        "complete99_live_catalog_term_write_failed",
    },
    "attachment": {
        "complete99_live_catalog_asset_readback_failed",
        "complete99_live_catalog_asset_source_failed",
        "complete99_live_catalog_asset_upload_failed",
        "complete99_live_catalog_asset_upload_hash_failed",
        "complete99_live_catalog_attachment_write_failed",
        "complete99_live_catalog_attachment_metadata_failed",
        "complete99_live_catalog_asset_binding_invalid",
    },
    "product": {
        "complete99_live_catalog_product_type_failed",
        "complete99_live_catalog_product_write_failed",
        "complete99_live_catalog_initial_stock_failed",
        "complete99_live_catalog_product_readback_failed",
    },
    "readback": {
        "complete99_live_catalog_runtime_precommit_cache_flush",
        "complete99_live_catalog_runtime_postcommit_cache_flush",
        "complete99_live_catalog_runtime_page_cache_purge",
        "complete99_live_catalog_page_cache",
        "complete99_live_catalog_runtime_strict_readback",
        "complete99_live_catalog_strict_readback_receipt_missing",
        "complete99_live_catalog_strict_readback_registry_invalid",
        "complete99_live_catalog_strict_readback_woocommerce_dependency",
        "complete99_live_catalog_strict_readback_recovery_required",
        "complete99_live_catalog_strict_readback_recovery_unknown",
        "complete99_live_catalog_strict_readback_receipt_invalid",
        "complete99_live_catalog_strict_readback_store_configuration_mismatch",
        "complete99_live_catalog_strict_readback_product_binding_invalid",
        "complete99_live_catalog_strict_readback_product_readback_mismatch",
        "complete99_live_catalog_strict_readback_product_count_mismatch",
        "complete99_live_catalog_strict_readback_receipt_identity_mismatch",
    },
    "recovery": {
        "complete99_live_catalog_recovery_cache",
        "complete99_live_catalog_recovery_unknown",
        "complete99_live_catalog_recovery_baseline",
        "complete99_live_catalog_recovery_owner",
        "complete99_live_catalog_recovery_marker",
        "complete99_live_catalog_recovery_restore_failed",
        "complete99_live_catalog_recovery_ambiguous",
        "complete99_live_catalog_recovery_upload_path",
        "complete99_live_catalog_recovery_upload_scan",
        "complete99_live_catalog_recovery_upload_name",
        "complete99_live_catalog_recovery_upload_entry",
        "complete99_live_catalog_recovery_upload_read",
        "complete99_live_catalog_recovery_uploads",
        "complete99_live_catalog_recovery_file_scope",
        "complete99_live_catalog_recovery_reference_query",
        "complete99_live_catalog_recovery_journal_unknown",
        "complete99_live_catalog_recovery_uploads_changed",
        "complete99_live_catalog_recovery_baseline_missing",
        "complete99_live_catalog_recovery_baseline_changed",
        "complete99_live_catalog_recovery_file_ambiguous",
        "complete99_live_catalog_recovery_file_referenced",
        "complete99_live_catalog_recovery_delete_unavailable",
        "complete99_live_catalog_recovery_delete_failed",
        "complete99_live_catalog_recovery_cleanup_readback",
        "complete99_live_catalog_runtime_recovery_marker_readback",
        "complete99_live_catalog_runtime_postcommit_boundary",
    },
}
CATALOG_CAUSE_STAGE = {
    cause: stage
    for stage, causes in _CATALOG_CAUSES_BY_STAGE.items()
    for cause in causes
}
CATALOG_RUNTIME_MESSAGE_CAUSE = {
    "The durable catalog recovery marker failed readback.": (
        "complete99_live_catalog_runtime_recovery_marker_readback"
    ),
    "The catalog database transaction could not start.": (
        "complete99_live_catalog_runtime_transaction_start"
    ),
    "The public catalog cache could not be flushed before strict transactional readback.": (
        "complete99_live_catalog_runtime_precommit_cache_flush"
    ),
    "The committed public catalog cache could not be flushed.": (
        "complete99_live_catalog_runtime_postcommit_cache_flush"
    ),
    "The committed public catalog page cache could not be purged.": (
        "complete99_live_catalog_runtime_page_cache_purge"
    ),
    "The catalog database transaction could not commit.": (
        "complete99_live_catalog_runtime_transaction_commit"
    ),
    "The committed catalog could not clear its recovery boundary.": (
        "complete99_live_catalog_runtime_postcommit_boundary"
    ),
}
CATALOG_RECOVERY_MESSAGE_PREFIX = (
    "Catalog recovery is required after an unverified mutation boundary: "
)


class DeployError(RuntimeError):
    pass


class NetworkDeployError(DeployError):
    """A transport failure where no trusted HTTP response was received."""


def reject_duplicate_json_object(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    """Reject ambiguous JSON objects before deployment state is trusted."""
    result: dict[str, Any] = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"Duplicate JSON key: {key}")
        result[key] = value
    return result


def package_upload_ceiling(package_size: int) -> int:
    """Return a bounded bridge ceiling that safely contains the exact package."""

    if type(package_size) is not int or package_size <= 0:
        raise DeployError("Release package size must be a positive integer")
    ceiling = min(
        max(package_size + PACKAGE_UPLOAD_HEADROOM_BYTES, MIN_PACKAGE_UPLOAD_BYTES),
        MAX_PACKAGE_UPLOAD_BYTES,
    )
    if package_size > ceiling:
        raise DeployError(
            "Release package exceeds the bounded deployment upload ceiling"
        )
    return ceiling


class HTTPDeployError(DeployError):
    """A sanitized WordPress REST error with safe structured recovery metadata."""

    def __init__(
        self,
        message: str,
        *,
        status: int,
        code: str,
        data: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status = status
        self.code = code
        self.data = data or {}


class FinalizeCommittedError(DeployError):
    """Final code is committed; cleanup failed and rollback is no longer safe."""


class RejectRedirects(urllib.request.HTTPRedirectHandler):
    """Never forward deployment credentials or mutate POST semantics across redirects."""

    def redirect_request(
        self,
        req: urllib.request.Request,
        fp: Any,
        code: int,
        msg: str,
        headers: Any,
        newurl: str,
    ) -> None:
        return None


def parse_allowed_deploy_hosts(value: str) -> set[str]:
    configured: set[str] = set()
    for item in re.split(r"[\s,]+", value.strip()):
        if not item:
            continue
        host = item.lower()
        if (
            "*" in host
            or "://" in host
            or "/" in host
            or ":" in host
            or not re.fullmatch(
                r"(?=.{1,253}\Z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?",
                host,
            )
        ):
            raise DeployError("WP_ALLOWED_DEPLOY_HOSTS must contain exact DNS hostnames only")
        configured.add(host)
    unsupported = configured - ALLOWED_PRODUCTION_HOSTS - SUPPORTED_TRANSITIONAL_HOSTS
    if unsupported:
        raise DeployError("WP_ALLOWED_DEPLOY_HOSTS contains an unapproved deployment hostname")
    return configured


def validate_target_url(
    base_url: str,
    local_test: bool,
    allowed_deploy_hosts: str = "",
) -> urllib.parse.ParseResult:
    if base_url != base_url.strip():
        raise DeployError("WP_BASE_URL may not contain surrounding whitespace")
    parsed = urllib.parse.urlparse(base_url)
    try:
        port = parsed.port
    except ValueError as error:
        raise DeployError("WP_BASE_URL contains an invalid port") from error
    hostname = (parsed.hostname or "").lower()
    clean_root = (
        parsed.username is None
        and parsed.password is None
        and parsed.path in {"", "/"}
        and not parsed.params
        and not parsed.query
        and not parsed.fragment
    )
    if local_test:
        if (
            parsed.scheme != "http"
            or hostname not in ALLOWED_LOCAL_TEST_HOSTS
            or not clean_root
        ):
            raise DeployError("--local-test accepts only a clean HTTP loopback WordPress origin")
    elif (
        parsed.scheme != "https"
        or hostname
        not in (ALLOWED_PRODUCTION_HOSTS | parse_allowed_deploy_hosts(allowed_deploy_hosts))
        or port not in {None, 443}
        or not clean_root
    ):
        raise DeployError(
            "This pipeline accepts only the clean HTTPS production origin "
            "https://complete99.co.il or https://www.complete99.co.il."
        )
    return parsed


@dataclass
class Client:
    base_url: str
    username: str
    app_password: str
    allow_local_http: bool = False
    allowed_deploy_hosts: str = ""
    timeout: int = 180

    def __post_init__(self) -> None:
        validate_target_url(
            self.base_url,
            self.allow_local_http,
            self.allowed_deploy_hosts,
        )
        self.base_url = self.base_url.rstrip("/")
        credential = f"{self.username}:{self.app_password}".encode()
        self.authorization = "Basic " + base64.b64encode(credential).decode("ascii")
        self.ssl_context = ssl.create_default_context()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPSHandler(context=self.ssl_context),
            RejectRedirects(),
        )
        self.use_query_rest_transport = False

    @staticmethod
    def query_rest_path(path: str) -> str | None:
        """Return WordPress's standard query transport for a pretty REST path."""
        parsed = urllib.parse.urlsplit(path)
        if (
            parsed.fragment
            or not parsed.path.startswith("/wp-json/")
            or parsed.path.startswith("//")
        ):
            return None
        route = parsed.path[len("/wp-json") :]
        query = "rest_route=" + urllib.parse.quote(route, safe="/")
        if parsed.query:
            query += "&" + parsed.query
        return "/?" + query

    def _request_once(
        self,
        method: str,
        path: str,
        body: bytes | None,
        headers: dict[str, str],
        *,
        network_timeout: int | None = None,
    ) -> tuple[int, bytes]:
        url = self.base_url + path
        request = urllib.request.Request(url, data=body, headers=headers, method=method)
        try:
            with self.opener.open(
                request,
                timeout=self.timeout if network_timeout is None else network_timeout,
            ) as response:
                if response.geturl() != url:
                    raise DeployError("Deployment requests may not follow redirects")
                return response.status, response.read()
        except urllib.error.HTTPError as error:
            try:
                return error.code, error.read()
            except (http.client.HTTPException, TimeoutError, OSError) as read_error:
                raise NetworkDeployError(
                    f"Network request failed while reading the HTTP error body: "
                    f"{type(read_error).__name__}"
                ) from read_error
        except (
            urllib.error.URLError,
            http.client.HTTPException,
            TimeoutError,
            OSError,
        ) as error:
            reason = getattr(error, "reason", type(error).__name__)
            raise NetworkDeployError(f"Network request failed: {reason}") from error

    @staticmethod
    def _parse_json_response(raw: bytes) -> Any:
        try:
            return (
                json.loads(
                    raw.decode("utf-8"),
                    object_pairs_hook=reject_duplicate_json_object,
                )
                if raw
                else {}
            )
        except (UnicodeDecodeError, json.JSONDecodeError, ValueError):
            try:
                stripped = raw.decode("utf-8").lstrip().lower()
            except UnicodeDecodeError:
                stripped = ""
            if stripped.startswith("<html") or stripped.startswith("<!doctype html"):
                return {
                    "html_response": True,
                    "non_json_response": True,
                    "length": len(raw),
                }
            return {"invalid_json_response": True, "length": len(raw)}

    def _bounded_public_read(
        self,
        request: urllib.request.Request,
        url: str,
        max_bytes: int,
        redirect_error: str,
        network_error: str,
    ) -> tuple[int, bytes]:
        """Retry only bounded anonymous reads that cannot mutate WordPress."""
        last_network_error: Exception | None = None
        for attempt in range(PUBLIC_READ_ATTEMPTS):
            try:
                with self.opener.open(
                    request,
                    timeout=min(self.timeout, PUBLIC_READ_TIMEOUT_SECONDS),
                ) as response:
                    if response.geturl() != url:
                        raise DeployError(redirect_error)
                    return response.status, response.read(max_bytes + 1)
            except urllib.error.HTTPError as error:
                status = error.code
                try:
                    raw = error.read(max_bytes + 1)
                except (http.client.HTTPException, TimeoutError, OSError) as read_error:
                    last_network_error = read_error
                    if attempt + 1 < PUBLIC_READ_ATTEMPTS:
                        time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
                        continue
                    raise NetworkDeployError(
                        f"{network_error}: {type(read_error).__name__}"
                    ) from read_error
                if (
                    status in PUBLIC_READ_RETRYABLE_HTTP
                    and attempt + 1 < PUBLIC_READ_ATTEMPTS
                ):
                    time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
                    continue
                return status, raw
            except (
                urllib.error.URLError,
                http.client.HTTPException,
                TimeoutError,
                OSError,
            ) as error:
                last_network_error = error
                if attempt + 1 < PUBLIC_READ_ATTEMPTS:
                    time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
                    continue
                reason = getattr(error, "reason", type(error).__name__)
                raise NetworkDeployError(f"{network_error}: {reason}") from error
        reason = (
            getattr(last_network_error, "reason", type(last_network_error).__name__)
            if last_network_error is not None
            else "retry budget exhausted"
        )
        raise NetworkDeployError(f"{network_error}: {reason}")

    def _request_transport_with_safe_retries(
        self,
        method: str,
        path: str,
        body: bytes | None,
        headers: dict[str, str],
        network_timeout: int | None,
    ) -> tuple[int, bytes]:
        """Retry one authenticated transport only when the HTTP method is safe."""

        attempts = PUBLIC_READ_ATTEMPTS if method == "GET" else 1
        for attempt in range(attempts):
            try:
                status, raw = self._request_once(
                    method,
                    path,
                    body,
                    headers,
                    network_timeout=(
                        min(self.timeout, PUBLIC_READ_TIMEOUT_SECONDS)
                        if method == "GET" and network_timeout is None
                        else network_timeout
                    ),
                )
            except NetworkDeployError:
                if attempt + 1 >= attempts:
                    raise
                time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
                continue
            if (
                status in PUBLIC_READ_RETRYABLE_HTTP
                and attempt + 1 < attempts
            ):
                time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
                continue
            return status, raw
        raise NetworkDeployError("Authenticated read retry budget was exhausted")

    def request(
        self,
        method: str,
        path: str,
        payload: dict[str, Any] | None = None,
        expected: tuple[int, ...] = (200, 201),
        *,
        network_timeout: int | None = None,
    ) -> tuple[int, Any]:
        if not path.startswith("/") or path.startswith("//"):
            raise DeployError("Deployment request path must be site-relative")
        body = None
        headers = {
            "Accept": "application/json",
            "Authorization": self.authorization,
            "User-Agent": USER_AGENT,
        }
        if payload is not None:
            body = json.dumps(payload, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
            headers["Content-Type"] = "application/json"
        alternate_path = self.query_rest_path(path)
        effective_path = (
            alternate_path
            if self.use_query_rest_transport and alternate_path is not None
            else path
        )
        status, raw = self._request_transport_with_safe_retries(
            method,
            effective_path,
            body,
            headers,
            network_timeout,
        )
        parsed = self._parse_json_response(raw)

        # UPress can reject pretty wp-json paths in nginx before WordPress runs.
        # Retry only the exact HTML-403 signature through WordPress's standard
        # rest_route transport. A JSON 403 remains a real WordPress refusal.
        if (
            effective_path == path
            and alternate_path is not None
            and status == 403
            and isinstance(parsed, dict)
            and parsed.get("html_response") is True
        ):
            status, raw = self._request_transport_with_safe_retries(
                method,
                alternate_path,
                body,
                headers,
                network_timeout,
            )
            parsed = self._parse_json_response(raw)
            if not (
                status == 403
                and isinstance(parsed, dict)
                and parsed.get("html_response") is True
            ):
                self.use_query_rest_transport = True
        if (
            isinstance(parsed, dict)
            and parsed.get("invalid_json_response") is True
        ):
            raise DeployError(
                f"Deployment request {method} {path} returned invalid JSON"
            )
        if status not in expected:
            raw_code = (
                str(parsed.get("code", "http_error"))
                if isinstance(parsed, dict)
                else "http_error"
            )
            code = (
                raw_code
                if re.fullmatch(r"[A-Za-z0-9_.-]{1,80}", raw_code)
                else "http_error"
            )
            safe_data: dict[str, Any] = {}
            raw_data = parsed.get("data", {}) if isinstance(parsed, dict) else {}
            if isinstance(raw_data, dict):
                for key in (
                    "current_database_version",
                    "current_version",
                    "database_error",
                    "database_version_match",
                    "deployment_id",
                    "lock_age_seconds",
                    "migration_failed",
                    "phase",
                    "plugin_active",
                    "plugin_digest_match",
                    "plugin_header_match",
                    "recovery_lease_seconds",
                    "retryable_forward_mismatch",
                    "runtime_loaded",
                    "runtime_version",
                    "status",
                ):
                    value = raw_data.get(key)
                    if isinstance(value, (bool, int, float, str)) or value is None:
                        safe_data[key] = value
            cause_candidates: set[str] = set()
            if code in CATALOG_CAUSE_STAGE:
                cause_candidates.add(code)
            product_candidates: set[str] = set()
            if isinstance(raw_data, dict):
                raw_cause = raw_data.get("catalog_cause_code")
                if isinstance(raw_cause, str) and raw_cause in CATALOG_CAUSE_STAGE:
                    cause_candidates.add(raw_cause)
                raw_product = raw_data.get("catalog_product_code")
                if isinstance(raw_product, str) and raw_product in CATALOG_PRODUCT_CODES:
                    product_candidates.add(raw_product)
            raw_message = parsed.get("message") if isinstance(parsed, dict) else None
            if (
                code.startswith("complete99_live_catalog_")
                and isinstance(raw_message, str)
                and raw_message == raw_message.strip()
                and 1 <= len(raw_message) <= 512
                and not any(ord(character) < 32 for character in raw_message)
            ):
                cause_message = raw_message
                if cause_message.startswith(CATALOG_RECOVERY_MESSAGE_PREFIX):
                    cause_message = cause_message[
                        len(CATALOG_RECOVERY_MESSAGE_PREFIX) :
                    ]
                cause_match = re.match(
                    r"\A(complete99_live_catalog_[a-z0-9_]{1,64})(?::|\Z)",
                    cause_message,
                )
                if (
                    cause_match is not None
                    and cause_match.group(1) in CATALOG_CAUSE_STAGE
                ):
                    cause_candidates.add(cause_match.group(1))
                runtime_cause = CATALOG_RUNTIME_MESSAGE_CAUSE.get(cause_message)
                if runtime_cause is not None:
                    cause_candidates.add(runtime_cause)
                product_candidates.update(
                    CATALOG_PRODUCT_CODES.intersection(
                        re.findall(
                            r"(?<![a-z0-9-])product-[a-z0-9]+(?:-[a-z0-9]+)*(?![a-z0-9-])",
                            raw_message,
                        )
                    )
                )
            if len(cause_candidates) == 1:
                catalog_cause = cause_candidates.pop()
                safe_data["catalog_cause_code"] = catalog_cause
                safe_data["catalog_stage"] = CATALOG_CAUSE_STAGE[catalog_cause]
            if len(product_candidates) == 1:
                safe_data["catalog_product_code"] = product_candidates.pop()
            error_message = f"{method} {path} failed with HTTP {status} ({code})"
            diagnostics = " ".join(
                f"{key}={safe_data[key]}"
                for key in (
                    "catalog_stage",
                    "catalog_cause_code",
                    "catalog_product_code",
                )
                if key in safe_data
            )
            if diagnostics:
                error_message += f" [{diagnostics}]"
            raise HTTPDeployError(
                error_message,
                status=status,
                code=str(code),
                data=safe_data,
            )
        return status, parsed

    def request_anonymous_html(self, path: str, expected: tuple[int, ...] = (200,)) -> tuple[int, str]:
        if not path.startswith("/") or path.startswith("//") or "?" in path or "#" in path:
            raise DeployError("Anonymous render verification requires an exact site-relative path")
        url = self.base_url + path
        request = urllib.request.Request(
            url,
            headers={
                "Accept": "text/html,application/xhtml+xml",
                "User-Agent": USER_AGENT,
            },
            method="GET",
        )
        status, raw = self._bounded_public_read(
            request,
            url,
            5 * 1024 * 1024,
            "Anonymous render verification may not follow redirects",
            "Anonymous render verification failed",
        )
        if len(raw) > 5 * 1024 * 1024:
            raise DeployError("Anonymous homepage exceeded the verification size ceiling")
        if status not in expected:
            raise DeployError(f"Anonymous GET {path} failed with HTTP {status}")
        return status, raw.decode("utf-8", errors="replace")

    def request_anonymous_bytes(
        self,
        path: str,
        expected: tuple[int, ...] = (200,),
        max_bytes: int = 65536,
    ) -> tuple[int, bytes]:
        if (
            not path.startswith("/")
            or path.startswith("//")
            or "?" in path
            or "#" in path
            or max_bytes < 1
            or max_bytes > 65536
        ):
            raise DeployError("Anonymous byte verification requires an exact bounded site-relative path")
        url = self.base_url + path
        request = urllib.request.Request(
            url,
            headers={
                "Accept": "text/plain",
                "User-Agent": USER_AGENT,
            },
            method="GET",
        )
        status, raw = self._bounded_public_read(
            request,
            url,
            max_bytes,
            "Anonymous byte verification may not follow redirects",
            "Anonymous byte verification failed",
        )
        if len(raw) > max_bytes:
            raise DeployError("Anonymous byte verification exceeded the size ceiling")
        if status not in expected:
            raise DeployError(f"Anonymous GET {path} failed with HTTP {status}")
        return status, raw

    def request_public_json(
        self,
        path: str,
        expected: tuple[int, ...] = (200,),
    ) -> tuple[int, Any]:
        if not path.startswith("/") or path.startswith("//") or "#" in path:
            raise DeployError("Public verification path must be site-relative")
        alternate_path = self.query_rest_path(path)
        effective_path = (
            alternate_path
            if self.use_query_rest_transport and alternate_path is not None
            else path
        )
        url = self.base_url + effective_path
        request = urllib.request.Request(
            url,
            headers={
                "Accept": "application/json",
                "User-Agent": USER_AGENT,
            },
            method="GET",
        )
        status, raw = self._bounded_public_read(
            request,
            url,
            1024 * 1024,
            "Public verification may not follow redirects",
            "Public verification failed",
        )
        if len(raw) > 1024 * 1024:
            raise DeployError("Public JSON verification exceeded the size ceiling")
        parsed = self._parse_json_response(raw)
        if status not in expected:
            code = parsed.get("code", "http_error") if isinstance(parsed, dict) else "http_error"
            raise DeployError(f"Public GET {path} failed with HTTP {status} ({code})")
        if isinstance(parsed, dict) and (
            parsed.get("invalid_json_response") is True
            or parsed.get("non_json_response") is True
        ):
            raise DeployError(f"Public GET {path} returned invalid JSON")
        return status, parsed


def installed_digest(raw: bytes) -> str:
    """Hash ZIP files exactly as the bridge hashes the installed directory."""
    entries: list[bytes] = []
    seen: set[str] = set()
    try:
        with zipfile.ZipFile(io.BytesIO(raw)) as archive:
            for info in archive.infolist():
                if info.is_dir():
                    continue
                path = PurePosixPath(info.filename)
                if (
                    len(path.parts) < 2
                    or path.parts[0] != SLUG
                    or ".." in path.parts
                ):
                    raise DeployError("Package installed digest path is invalid")
                relative = path.relative_to(SLUG).as_posix()
                if relative in seen:
                    raise DeployError("Package installed digest path is duplicated")
                seen.add(relative)
                file_digest = (
                    hashlib.sha256(archive.read(info)).hexdigest().encode("ascii")
                )
                entries.append(relative.encode("utf-8") + b"\0" + file_digest)
    except (KeyError, OSError, RuntimeError, zipfile.BadZipFile) as error:
        raise DeployError("Package installed digest could not be computed") from error
    if not entries:
        raise DeployError("Package installed digest requires at least one file")
    return hashlib.sha256(b"\n".join(sorted(entries))).hexdigest()


def load_artifact(dist: Path) -> tuple[dict[str, Any], Path, bytes]:
    metadata = json.loads((dist / f"{SLUG}-integrity.json").read_text(encoding="utf-8"))
    if metadata.get("slug") != SLUG or metadata.get("type") != "plugin":
        raise DeployError("Package metadata is not allowlisted")
    artifact = dist / str(metadata["artifact"])
    raw = artifact.read_bytes()
    digest = hashlib.sha256(raw).hexdigest()
    if digest != metadata.get("sha256") or len(raw) != metadata.get("size"):
        raise DeployError("Local artifact integrity check failed")
    expected_installed = metadata.get("installed_sha256")
    if (
        not isinstance(expected_installed, str)
        or re.fullmatch(r"[a-f0-9]{64}", expected_installed) is None
        or installed_digest(raw) != expected_installed
    ):
        raise DeployError("Local installed plugin integrity check failed")
    return metadata, artifact, raw


def arm_live_mutation_recovery(marker: Path | None, deployment_id: str) -> None:
    """Create a local recovery marker at the exact edge before the first live write."""
    if marker is None:
        return
    if not marker.is_absolute() or not marker.parent.is_dir():
        raise DeployError("The live mutation recovery marker requires an existing absolute parent")
    try:
        with marker.open("x", encoding="ascii", newline="\n") as handle:
            handle.write(f"{deployment_id}\n")
    except (FileExistsError, OSError) as error:
        raise DeployError("The live mutation recovery marker could not be armed safely") from error


def verify_rest_identity(client: Client) -> dict[str, str]:
    _, root = client.request_public_json(REST_IDENTITY_PATH)
    target = validate_target_url(
        client.base_url,
        client.allow_local_http,
        client.allowed_deploy_hosts,
    )
    target_port = target.port or (80 if target.scheme == "http" else 443)
    identity: dict[str, str] = {}
    for field in ("home", "url"):
        value = root.get(field) if isinstance(root, dict) else None
        if not isinstance(value, str) or not value:
            raise DeployError(f"WordPress REST identity did not expose {field}")
        parsed = urllib.parse.urlparse(value)
        try:
            port = parsed.port or (80 if parsed.scheme == "http" else 443)
        except ValueError as error:
            raise DeployError("WordPress REST identity contains an invalid port") from error
        if (
            parsed.scheme != target.scheme
            or (parsed.hostname or "").lower() != (target.hostname or "").lower()
            or port != target_port
            or parsed.username is not None
            or parsed.password is not None
            or parsed.path not in {"", "/"}
            or parsed.params
            or parsed.query
            or parsed.fragment
        ):
            raise DeployError(
                "WordPress home, site URL and REST origin must match WP_BASE_URL exactly"
            )
        identity[field] = value.rstrip("/")
    return identity


def authenticate(client: Client) -> dict[str, Any]:
    site_identity = verify_rest_identity(client)
    _, user = client.request("GET", "/wp-json/wp/v2/users/me?context=edit&_fields=id,roles,capabilities")
    roles = user.get("roles", []) if isinstance(user, dict) else []
    capabilities = user.get("capabilities", {}) if isinstance(user, dict) else {}
    if "administrator" not in roles and not capabilities.get("update_plugins"):
        raise DeployError("The deployment identity lacks the update_plugins capability")
    return {"id": user.get("id"), "roles": roles, "site_identity": site_identity}


def ensure_code_snippets(client: Client, bootstrap: bool) -> None:
    try:
        client.request("GET", "/wp-json/code-snippets/v1/snippets?per_page=1")
        return
    except DeployError:
        if not bootstrap:
            raise DeployError(
                "Code Snippets REST is unavailable. Activate the approved wordpress.org Code Snippets plugin "
                "or rerun with --bootstrap-code-snippets."
            )
    try:
        client.request(
            "POST",
            "/wp-json/wp/v2/plugins",
            {"slug": "code-snippets", "status": "active"},
            expected=(200, 201),
        )
    except DeployError:
        # Managed proxies can report a failure after WordPress committed the install.
        pass
    client.request("GET", "/wp-json/code-snippets/v1/snippets?per_page=1")


def render_bridge(
    token: str,
    deployment_id: str,
    max_bytes: int,
    local_test: bool,
    test_fault: str = "",
    target_host: str = "",
    allowed_hosts: set[str] | None = None,
    *,
    expected_artifact_sha256: str = "",
    expected_artifact_size: int = 0,
    expected_plugin_sha256: str = "",
    expected_version: str = "",
    interrupted_forward_adoption_schema: str = "",
    interrupted_forward_proof_sha256: str = "",
    interrupted_forward_finalized_attestation: bool = False,
    interrupted_forward_target_deployment_id: str = "",
    reviewed_database_fingerprint: str = "",
    reviewed_database_manifest: dict[str, Any] | None = None,
    reviewed_database_manifest_sha256: str = "",
    reviewed_database_storage: dict[str, Any] | None = None,
    reviewed_safe_status: dict[str, Any] | None = None,
    reviewed_safe_status_sha256: str = "",
    candidate_repair_schema: str = "",
    candidate_source_before_sha256: str = "",
    candidate_source_after_sha256: str = "",
    candidate_plugin_before_sha256: str = "",
    candidate_plugin_after_sha256: str = "",
    prior_database_fingerprint: str = "",
    prior_plugin_sha256: str = "",
    prior_deployment_id: str = "",
    prior_version: str = "",
    prior_robots_sha256: str = "",
) -> str:
    if not re.fullmatch(r"[A-Za-z0-9._-]{8,96}", deployment_id):
        raise DeployError("Deployment ID must contain 8-96 safe characters")
    allowed_faults = {
        "",
        "db_capture",
        "after_prepare",
        "after_install",
        "during_rollback",
        "after_commit",
    }
    if test_fault not in allowed_faults or (test_fault and not local_test):
        raise DeployError("Temporary bridge fault mode is invalid")
    if not target_host:
        target_host = "localhost" if local_test else "complete99.co.il"
    target_host = target_host.lower()
    exact_hosts = set(allowed_hosts or {target_host})
    approved_hosts = (
        ALLOWED_LOCAL_TEST_HOSTS
        if local_test
        else ALLOWED_PRODUCTION_HOSTS | SUPPORTED_TRANSITIONAL_HOSTS
    )
    if target_host not in exact_hosts or not exact_hosts <= approved_hosts:
        raise DeployError("Temporary bridge target host is not exactly allowlisted")
    digest_identities = {
        "expected artifact": expected_artifact_sha256,
        "expected plugin": expected_plugin_sha256,
        "interrupted-forward proof": interrupted_forward_proof_sha256,
        "reviewed database": reviewed_database_fingerprint,
        "reviewed database manifest": reviewed_database_manifest_sha256,
        "reviewed safe status": reviewed_safe_status_sha256,
        "candidate source before": candidate_source_before_sha256,
        "candidate source after": candidate_source_after_sha256,
        "candidate plugin before": candidate_plugin_before_sha256,
        "candidate plugin after": candidate_plugin_after_sha256,
        "prior database": prior_database_fingerprint,
        "prior plugin": prior_plugin_sha256,
        "prior robots": prior_robots_sha256,
    }
    for label, value in digest_identities.items():
        if value and re.fullmatch(r"[a-f0-9]{64}", value) is None:
            raise DeployError(f"Temporary bridge {label} identity is invalid")
    if type(max_bytes) is not int or max_bytes < 1:
        raise DeployError("Temporary bridge upload ceiling is invalid")
    if type(expected_artifact_size) is not int or expected_artifact_size < 0:
        raise DeployError("Temporary bridge expected artifact size is invalid")
    legacy_recovery_identity = bool(
        expected_artifact_sha256
        and 0 == expected_artifact_size
        and interrupted_forward_proof_sha256
    )
    if (
        (
            expected_artifact_sha256
            and expected_artifact_size < 1
            and not legacy_recovery_identity
        )
        or (not expected_artifact_sha256 and 0 != expected_artifact_size)
        or expected_artifact_size > max_bytes
    ):
        raise DeployError("Temporary bridge expected artifact identity is incomplete")
    for label, value in (
        ("expected version", expected_version),
        ("prior version", prior_version),
    ):
        if value and re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+", value) is None:
            raise DeployError(f"Temporary bridge {label} identity is invalid")
    reviewed_storage = reviewed_database_storage or {}
    if reviewed_storage and (
        set(reviewed_storage) != {"engine", "tables"}
        or reviewed_storage.get("engine")
        not in {"INNODB", "XTRADB", "INNODB,XTRADB"}
        or type(reviewed_storage.get("tables")) is not int
        or reviewed_storage.get("tables") != 3
    ):
        raise DeployError("Temporary bridge reviewed database storage is invalid")
    reviewed_manifest = reviewed_database_manifest or {}
    if reviewed_manifest and not isinstance(reviewed_manifest, dict):
        raise DeployError("Temporary bridge reviewed database manifest is invalid")
    if interrupted_forward_adoption_schema not in {
        "",
        "complete99-interrupted-forward-adoption/v1",
        "complete99-interrupted-forward-adoption/v2",
        "complete99-interrupted-forward-adoption/v3",
        "complete99-interrupted-forward-adoption/v4",
        "complete99-interrupted-forward-adoption/v5",
    }:
        raise DeployError("Temporary bridge interrupted-forward adoption schema is invalid")
    reviewed_status = reviewed_safe_status or {}
    if reviewed_status and not isinstance(reviewed_status, dict):
        raise DeployError("Temporary bridge reviewed safe status is invalid")
    reviewed_status_json = json.dumps(
        reviewed_status,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    )
    if (
        interrupted_forward_adoption_schema
        in {
            "complete99-interrupted-forward-adoption/v4",
            "complete99-interrupted-forward-adoption/v5",
        }
        and (
            not reviewed_status
            or re.fullmatch(r"[a-f0-9]{64}", reviewed_safe_status_sha256)
            is None
            or not secrets.compare_digest(
                hashlib.sha256(reviewed_status_json.encode("utf-8")).hexdigest(),
                reviewed_safe_status_sha256,
            )
        )
    ):
        raise DeployError("Temporary bridge reviewed repair status is invalid")
    if interrupted_forward_adoption_schema == "complete99-interrupted-forward-adoption/v5" and (
        candidate_repair_schema
        != "complete99-campaign-provider-receipt-width-repair/v1"
        or any(
            re.fullmatch(r"[a-f0-9]{64}", value) is None
            for value in (
                candidate_source_before_sha256,
                candidate_source_after_sha256,
                candidate_plugin_before_sha256,
                candidate_plugin_after_sha256,
            )
        )
        or candidate_source_before_sha256 == candidate_source_after_sha256
        or candidate_plugin_before_sha256 == candidate_plugin_after_sha256
        or candidate_plugin_after_sha256 != expected_plugin_sha256
    ):
        raise DeployError("Temporary bridge candidate repair identity is invalid")
    reviewed_manifest_json = json.dumps(
        reviewed_manifest,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    )
    if reviewed_manifest and (
        not reviewed_database_manifest_sha256
        or not secrets.compare_digest(
            hashlib.sha256(reviewed_manifest_json.encode("utf-8")).hexdigest(),
            reviewed_database_manifest_sha256,
        )
    ):
        raise DeployError("Temporary bridge reviewed database manifest is invalid")
    if type(interrupted_forward_finalized_attestation) is not bool:
        raise DeployError("Temporary bridge finalized attestation mode is invalid")
    if interrupted_forward_finalized_attestation and (
        not interrupted_forward_target_deployment_id
        or re.fullmatch(
            r"[A-Za-z0-9._-]{8,96}",
            interrupted_forward_target_deployment_id,
        )
        is None
        or not interrupted_forward_target_deployment_id.startswith("c99-")
        or interrupted_forward_target_deployment_id == deployment_id
        or not reviewed_manifest
        or not reviewed_storage
        or any(
            not value
            for label, value in digest_identities.items()
            if label
            not in {
                "reviewed safe status",
                "candidate source before",
                "candidate source after",
                "candidate plugin before",
                "candidate plugin after",
            }
        )
        or not expected_version
        or not prior_version
        or not prior_deployment_id
    ):
        raise DeployError(
            "Temporary bridge finalized attestation identities are incomplete"
        )
    if prior_deployment_id and (
        re.fullmatch(r"[A-Za-z0-9._-]{8,96}", prior_deployment_id) is None
        or not prior_deployment_id.startswith("c99-")
    ):
        raise DeployError("Temporary bridge prior deployment identity is invalid")
    code = BRIDGE_TEMPLATE.read_text(encoding="utf-8")
    if code.startswith("<?php"):
        code = code.split("\n", 1)[1]
    replacements = {
        "__C99_TOKEN__": token,
        "__C99_DEPLOYMENT_ID__": deployment_id,
        "__C99_MAX_BYTES__": str(max_bytes),
        "__C99_MIN_FREE_BYTES__": str(max(64 * 1024 * 1024, max_bytes * 8)),
        "__C99_LOCAL_TEST__": "true" if local_test else "false",
        "__C99_TEST_FAULT__": test_fault,
        "__C99_TARGET_HOST__": target_host,
        "__C99_ALLOWED_HOSTS__": json.dumps(
            sorted(exact_hosts),
            ensure_ascii=True,
            separators=(",", ":"),
        ),
        "__C99_EXPECTED_ARTIFACT_SHA256__": expected_artifact_sha256,
        "__C99_EXPECTED_ARTIFACT_SIZE__": str(expected_artifact_size),
        "__C99_EXPECTED_PLUGIN_SHA256__": expected_plugin_sha256,
        "__C99_EXPECTED_VERSION__": expected_version,
        "__C99_INTERRUPTED_FORWARD_PROOF_SHA256__": (
            interrupted_forward_proof_sha256
        ),
        "__C99_INTERRUPTED_FORWARD_ADOPTION_SCHEMA__": (
            interrupted_forward_adoption_schema
        ),
        "__C99_INTERRUPTED_FORWARD_FINALIZED_ATTESTATION__": (
            "true" if interrupted_forward_finalized_attestation else "false"
        ),
        "__C99_INTERRUPTED_FORWARD_TARGET_DEPLOYMENT_ID__": (
            interrupted_forward_target_deployment_id
        ),
        "__C99_REVIEWED_DATABASE_FINGERPRINT__": reviewed_database_fingerprint,
        "__C99_REVIEWED_DATABASE_MANIFEST_BASE64__": base64.b64encode(
            reviewed_manifest_json.encode("utf-8")
        ).decode("ascii"),
        "__C99_REVIEWED_DATABASE_MANIFEST_SHA256__": (
            reviewed_database_manifest_sha256
        ),
        "__C99_REVIEWED_DATABASE_STORAGE_BASE64__": base64.b64encode(
            json.dumps(
                reviewed_storage,
                ensure_ascii=True,
                separators=(",", ":"),
                sort_keys=True,
            ).encode("ascii")
        ).decode("ascii"),
        "__C99_REVIEWED_SAFE_STATUS_BASE64__": base64.b64encode(
            reviewed_status_json.encode("utf-8")
        ).decode("ascii"),
        "__C99_REVIEWED_SAFE_STATUS_SHA256__": reviewed_safe_status_sha256,
        "__C99_CANDIDATE_REPAIR_SCHEMA__": candidate_repair_schema,
        "__C99_CANDIDATE_SOURCE_BEFORE_SHA256__": candidate_source_before_sha256,
        "__C99_CANDIDATE_SOURCE_AFTER_SHA256__": candidate_source_after_sha256,
        "__C99_CANDIDATE_PLUGIN_BEFORE_SHA256__": candidate_plugin_before_sha256,
        "__C99_CANDIDATE_PLUGIN_AFTER_SHA256__": candidate_plugin_after_sha256,
        "__C99_PRIOR_DATABASE_FINGERPRINT__": prior_database_fingerprint,
        "__C99_PRIOR_PLUGIN_SHA256__": prior_plugin_sha256,
        "__C99_PRIOR_DEPLOYMENT_ID__": prior_deployment_id,
        "__C99_PRIOR_VERSION__": prior_version,
        "__C99_PRIOR_ROBOTS_SHA256__": prior_robots_sha256,
    }
    for marker, value in replacements.items():
        if code.count(marker) != 1:
            raise DeployError(f"Temporary bridge must contain exactly one {marker} placeholder")
        code = code.replace(marker, value)
    if re.search(r"__C99_[A-Z0-9_]+__", code):
        raise DeployError("Unresolved temporary bridge marker")
    return code


def snippet_name(deployment_id: str) -> str:
    return f"{SNIPPET_PREFIX}{deployment_id}"


def active_snippets(client: Client) -> list[dict[str, Any]]:
    """Return every site snippet, including inactive rows.

    The historical function name is retained for compatibility. Cleanup must
    inspect inactive rows too: deactivation proves that a route stopped
    executing, but it does not prove that the temporary code was deleted.
    """

    page_size = 100
    snippets_by_id: dict[int, dict[str, Any]] = {}
    for page in range(1, 101):
        cache_buster = secrets.token_hex(8)
        try:
            _, response = client.request(
                "GET",
                "/wp-json/code-snippets/v1/snippets"
                f"?status=all&per_page={page_size}&page={page}&c99cb={cache_buster}",
            )
        except HTTPDeployError as error:
            if (
                page > 1
                and error.status == 400
                and error.code in {"rest_post_invalid_page_number", "rest_invalid_param"}
            ):
                break
            raise
        items: Any = response
        if isinstance(response, dict) and isinstance(response.get("data"), list):
            items = response["data"]
        if not isinstance(items, list):
            raise DeployError("Code Snippets list returned an invalid response")
        if not items:
            break

        new_ids = 0
        for item in items:
            if not isinstance(item, dict):
                raise DeployError("Code Snippets list contained an invalid row")
            value = item.get("id")
            if not (isinstance(value, int) or str(value).isdigit()):
                raise DeployError("Code Snippets list contained a row without an ID")
            snippet_id = int(value)
            if snippet_id not in snippets_by_id:
                new_ids += 1
            snippets_by_id[snippet_id] = {
                "id": snippet_id,
                "name": str(item.get("name", "")),
                "active": bool(item.get("active")),
            }
        if len(items) < page_size or new_ids == 0:
            break
    else:
        raise DeployError("Code Snippets collection exceeded the cleanup page ceiling")

    return [snippets_by_id[key] for key in sorted(snippets_by_id)]


def get_snippet_by_id(client: Client, snippet_id: int) -> dict[str, Any] | None:
    """Independently read one snippet, including inactive or trashed rows."""

    cache_buster = secrets.token_hex(8)
    status, response = client.request(
        "GET",
        f"/wp-json/code-snippets/v1/snippets/{snippet_id}?c99cb={cache_buster}",
        expected=(200, 404, 500),
    )
    if status in {404, 500}:
        code = response.get("code", "") if isinstance(response, dict) else ""
        if code != "rest_cannot_get":
            raise DeployError(
                "Code Snippets exact-row deletion proof returned an untrusted error"
            )
        return None

    item: Any = response
    if isinstance(response, dict) and isinstance(response.get("data"), dict):
        item = response["data"]
    value = item.get("id") if isinstance(item, dict) else None
    if not (isinstance(value, int) or str(value).isdigit()):
        raise DeployError("Code Snippets exact-row read returned an invalid response")
    if int(value) != snippet_id:
        raise DeployError("Code Snippets exact-row read returned the wrong snippet")
    return item


def find_active_snippet_ids(client: Client, name: str) -> list[int]:
    return sorted(
        {
            int(item["id"])
            for item in active_snippets(client)
            if item["name"] == name
        }
    )


def find_active_snippet_ids_by_prefix(client: Client, prefix: str) -> list[int]:
    return sorted(
        {
            int(item["id"])
            for item in active_snippets(client)
            if str(item["name"]).startswith(prefix)
        }
    )


def retire_snippet_ids(
    client: Client,
    token: str,
    deployment_id: str,
    snippet_ids: set[int],
) -> list[int]:
    """Permanently delete allowlisted snippet rows through the live bridge."""

    targets = sorted({int(value) for value in snippet_ids if int(value) > 0})
    if not targets:
        return []
    first_error: DeployError | None = None
    for _ in range(2):
        try:
            bridge_call(
                client,
                "retire",
                token,
                deployment_id,
                snippet_ids=targets,
            )
        except DeployError as error:
            if first_error is None:
                first_error = error
        remaining = [
            snippet_id
            for snippet_id in targets
            if get_snippet_by_id(client, snippet_id) is not None
        ]
        if not remaining:
            return targets

    # If permanent deletion could not be proven, stop execution of every
    # remaining bridge before failing closed. The row must still be recovered.
    for snippet_id in remaining:
        try:
            client.request(
                "POST",
                f"/wp-json/code-snippets/v1/snippets/{snippet_id}/deactivate",
                expected=(200, 404),
            )
        except DeployError:
            pass
    if first_error is not None:
        raise first_error
    raise DeployError("Temporary Code Snippets bridge row remains after deletion")


def deactivate_and_delete_snippet(
    client: Client,
    snippet_id: int,
    token: str,
    deployment_id: str,
) -> None:
    retire_snippet_ids(client, token, deployment_id, {snippet_id})


def remove_named_snippets(
    client: Client,
    name: str,
    token: str,
    deployment_id: str,
) -> list[int]:
    matches = find_active_snippet_ids(client, name)
    removed = retire_snippet_ids(client, token, deployment_id, set(matches))
    remaining = find_active_snippet_ids(client, name)
    if remaining:
        raise DeployError("Temporary Code Snippets bridge row remains after cleanup")
    return sorted(set(removed))


def remove_prefixed_snippets(
    client: Client,
    prefix: str,
    token: str,
    deployment_id: str,
    exclude_ids: set[int] | None = None,
) -> list[int]:
    excluded = exclude_ids or set()
    removed: list[int] = []
    for _ in range(2):
        matches = [
            snippet_id
            for snippet_id in find_active_snippet_ids_by_prefix(client, prefix)
            if snippet_id not in excluded
        ]
        if not matches:
            return sorted(set(removed))
        removed.extend(
            retire_snippet_ids(client, token, deployment_id, set(matches))
        )
    remaining = [
        snippet_id
        for snippet_id in find_active_snippet_ids_by_prefix(client, prefix)
        if snippet_id not in excluded
    ]
    if remaining:
        raise DeployError("A stale Complete99 deployment bridge remains active")
    return sorted(set(removed))


def remove_bootstrap_snippet(
    client: Client,
    token: str,
    deployment_id: str,
) -> dict[str, Any]:
    """Remove the one-time live bootstrap without trusting its historical ID alone."""

    matches = set(find_active_snippet_ids(client, BOOTSTRAP_SNIPPET_NAME))
    known = get_snippet_by_id(client, BOOTSTRAP_SNIPPET_KNOWN_ID)
    known_name = str(known.get("name", "")) if known else ""
    if known_name == BOOTSTRAP_SNIPPET_NAME:
        matches.add(BOOTSTRAP_SNIPPET_KNOWN_ID)

    retire_snippet_ids(client, token, deployment_id, matches)
    for snippet_id in sorted(matches):
        if get_snippet_by_id(client, snippet_id) is not None:
            raise DeployError("The one-time deployment bootstrap row remains present")
    if find_active_snippet_ids(client, BOOTSTRAP_SNIPPET_NAME):
        raise DeployError("The one-time deployment bootstrap name remains present")

    known_after = get_snippet_by_id(client, BOOTSTRAP_SNIPPET_KNOWN_ID)
    if known_name == BOOTSTRAP_SNIPPET_NAME and known_after is not None:
        raise DeployError("The known one-time deployment bootstrap row remains present")
    return {
        "exact_name": BOOTSTRAP_SNIPPET_NAME,
        "known_id": BOOTSTRAP_SNIPPET_KNOWN_ID,
        "known_id_matched": known_name == BOOTSTRAP_SNIPPET_NAME,
        "removed_ids": sorted(matches),
        "row_absence_verified": True,
    }


def create_snippet(client: Client, code: str, deployment_id: str) -> int:
    name = snippet_name(deployment_id)
    existing = set(find_active_snippet_ids(client, name))
    for snippet_id in sorted(existing):
        try:
            client.request(
                "POST",
                f"/wp-json/code-snippets/v1/snippets/{snippet_id}/deactivate",
                expected=(200, 404),
            )
        except DeployError:
            pass
    response: Any = {}
    create_error: DeployError | None = None
    try:
        _, response = client.request(
            "POST",
            "/wp-json/code-snippets/v1/snippets",
            {
                "name": name,
                "code": code,
                "scope": "global",
                "active": True,
            },
        )
    except DeployError as error:
        create_error = error
    snippet_id = response.get("id") if isinstance(response, dict) else None
    if not snippet_id and isinstance(response, dict) and isinstance(response.get("data"), dict):
        snippet_id = response["data"].get("id")
    matches = find_active_snippet_ids(client, name)
    new_matches = sorted(set(matches) - existing)
    if isinstance(snippet_id, int) or str(snippet_id).isdigit():
        recovered = int(snippet_id)
        if recovered in new_matches:
            return recovered
    if len(new_matches) == 1:
        return new_matches[0]
    if new_matches:
        for candidate in new_matches:
            try:
                client.request(
                    "POST",
                    f"/wp-json/code-snippets/v1/snippets/{candidate}/deactivate",
                    expected=(200, 404),
                )
            except DeployError:
                pass
        raise DeployError("Code Snippets create produced ambiguous duplicate bridges")
    if create_error is not None:
        raise create_error
    raise DeployError("Code Snippets did not persist a recoverable snippet ID")


def bridge_call(client: Client, action: str, token: str, deployment_id: str, **fields: Any) -> dict[str, Any]:
    payload = {"token": token, "deployment_id": deployment_id}
    payload.update(fields)
    route_id = urllib.parse.quote(deployment_id, safe="")
    read_only_actions = {"status"}
    attempts = PUBLIC_READ_ATTEMPTS if action in read_only_actions else 1
    for attempt in range(attempts):
        try:
            if action in read_only_actions and isinstance(client, Client):
                _, response = client.request(
                    "POST",
                    f"/wp-json/complete99-deploy/v1/{route_id}/{action}",
                    payload,
                    network_timeout=min(
                        client.timeout,
                        PUBLIC_READ_TIMEOUT_SECONDS,
                    ),
                )
            else:
                _, response = client.request(
                    "POST",
                    f"/wp-json/complete99-deploy/v1/{route_id}/{action}",
                    payload,
                )
            break
        except NetworkDeployError:
            if attempt + 1 >= attempts:
                raise
        except HTTPDeployError as error:
            if (
                error.status not in PUBLIC_READ_RETRYABLE_HTTP
                or attempt + 1 >= attempts
            ):
                raise
        time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
    if (
        not isinstance(response, dict)
        or response.get("non_json_response") is True
    ):
        raise DeployError(f"Bridge action {action} returned an invalid response")
    return response


def verify_stage_receipt(
    response: dict[str, Any],
    deployment_id: str,
    expected_artifact_sha256: str,
    expected_artifact_size: int,
    offset: int,
    chunk_size: int,
    final: bool,
) -> dict[str, Any]:
    """Validate one exact, non-secret receipt from the staging bridge."""

    required_fields = {
        "deployment_id",
        "accepted_offset",
        "next_offset",
        "total_bytes",
        "complete",
        "artifact_sha256",
    }
    if not isinstance(response, dict) or set(response) != required_fields:
        raise DeployError("Artifact staging returned an unexpected receipt schema")
    if response.get("deployment_id") != deployment_id:
        raise DeployError("Artifact staging receipt deployment identity mismatch")
    accepted_offset = response.get("accepted_offset")
    next_offset = response.get("next_offset")
    total_bytes = response.get("total_bytes")
    complete = response.get("complete")
    artifact_sha256 = response.get("artifact_sha256")
    if (
        type(accepted_offset) is not int
        or type(next_offset) is not int
        or type(total_bytes) is not int
        or type(complete) is not bool
        or not isinstance(artifact_sha256, str)
    ):
        raise DeployError("Artifact staging receipt contains invalid value types")
    expected_next_offset = offset + chunk_size
    if accepted_offset != offset or next_offset != expected_next_offset:
        raise DeployError("Artifact staging receipt offset mismatch")
    if total_bytes != expected_next_offset:
        raise DeployError("Artifact staging receipt byte count mismatch")
    if complete is not final:
        raise DeployError("Artifact staging receipt completion state mismatch")
    if final:
        if next_offset != expected_artifact_size:
            raise DeployError("Completed artifact staging receipt size mismatch")
        if not secrets.compare_digest(artifact_sha256, expected_artifact_sha256):
            raise DeployError("Completed artifact staging receipt digest mismatch")
    elif next_offset >= expected_artifact_size or artifact_sha256 != "":
        raise DeployError("Incomplete artifact staging receipt is not fail-closed")
    return response


def is_ambiguous_stage_transport_error(error: DeployError) -> bool:
    """Identify responses that may have been lost after an idempotent chunk write."""

    if isinstance(error, NetworkDeployError):
        return True
    return (
        isinstance(error, HTTPDeployError)
        and (
            error.status in PUBLIC_READ_RETRYABLE_HTTP
            or (
                error.status == 500
                and error.code in {"http_error", "internal_server_error"}
            )
        )
    )


def stage_artifact_chunk(
    client: Client,
    token: str,
    deployment_id: str,
    expected_artifact_sha256: str,
    expected_artifact_size: int,
    offset: int,
    chunk: bytes,
    final: bool,
) -> dict[str, Any]:
    """Stage one sequential chunk, retrying only the identical ambiguous write."""

    if (
        not re.fullmatch(r"[a-f0-9]{64}", expected_artifact_sha256)
        or type(expected_artifact_size) is not int
        or expected_artifact_size < 1
        or type(offset) is not int
        or offset < 0
        or not isinstance(chunk, bytes)
        or not chunk
        or len(chunk) > ARTIFACT_STAGE_CHUNK_BYTES
        or type(final) is not bool
        or offset + len(chunk) > expected_artifact_size
        or final is not (offset + len(chunk) == expected_artifact_size)
    ):
        raise DeployError("Artifact staging chunk input is invalid")
    fields = {
        "expected_artifact_sha256": expected_artifact_sha256,
        "expected_artifact_size": expected_artifact_size,
        "offset": offset,
        "chunk_sha256": hashlib.sha256(chunk).hexdigest(),
        "chunk_base64": base64.b64encode(chunk).decode("ascii"),
        "final": final,
    }
    first_ambiguous_error: DeployError | None = None
    for attempt in range(ARTIFACT_STAGE_TRANSPORT_ATTEMPTS):
        try:
            response = bridge_call(
                client,
                "stage",
                token,
                deployment_id,
                **fields,
            )
            return verify_stage_receipt(
                response,
                deployment_id,
                expected_artifact_sha256,
                expected_artifact_size,
                offset,
                len(chunk),
                final,
            )
        except (NetworkDeployError, HTTPDeployError) as error:
            if not is_ambiguous_stage_transport_error(error):
                raise
            if first_ambiguous_error is None:
                first_ambiguous_error = error
            if attempt + 1 >= ARTIFACT_STAGE_TRANSPORT_ATTEMPTS:
                raise first_ambiguous_error
            time.sleep(PUBLIC_READ_RETRY_DELAYS_SECONDS[attempt])
    raise DeployError("Artifact staging transport retry budget was exhausted")


def stage_artifact(
    client: Client,
    token: str,
    deployment_id: str,
    raw: bytes,
    expected_artifact_sha256: str,
    expected_artifact_size: int,
) -> dict[str, Any]:
    """Upload and prove the immutable artifact before the privileged run call."""

    if (
        not isinstance(raw, bytes)
        or not raw
        or type(expected_artifact_size) is not int
        or len(raw) != expected_artifact_size
        or not re.fullmatch(r"[a-f0-9]{64}", expected_artifact_sha256)
        or not secrets.compare_digest(
            hashlib.sha256(raw).hexdigest(),
            expected_artifact_sha256,
        )
    ):
        raise DeployError("Artifact staging source integrity check failed")
    offset = 0
    chunk_count = 0
    final_receipt: dict[str, Any] | None = None
    while offset < expected_artifact_size:
        chunk = raw[offset : offset + ARTIFACT_STAGE_CHUNK_BYTES]
        final = offset + len(chunk) == expected_artifact_size
        final_receipt = stage_artifact_chunk(
            client,
            token,
            deployment_id,
            expected_artifact_sha256,
            expected_artifact_size,
            offset,
            chunk,
            final,
        )
        offset = int(final_receipt["next_offset"])
        chunk_count += 1
    if (
        final_receipt is None
        or final_receipt.get("complete") is not True
        or final_receipt.get("total_bytes") != expected_artifact_size
        or final_receipt.get("artifact_sha256") != expected_artifact_sha256
    ):
        raise DeployError("Artifact staging did not produce an exact completed receipt")
    return {
        "artifact_sha256": expected_artifact_sha256,
        "artifact_size": expected_artifact_size,
        "chunk_bytes": ARTIFACT_STAGE_CHUNK_BYTES,
        "chunk_count": chunk_count,
        "complete": True,
        "final_next_offset": int(final_receipt["next_offset"]),
    }


def verify_bridge_site_identity(response: dict[str, Any], target_host: str) -> dict[str, str]:
    identity = response.get("site_identity")
    if not isinstance(identity, dict):
        raise DeployError("Temporary bridge did not prove the WordPress site identity")
    expected = {
        "home_host": target_host,
        "siteurl_host": target_host,
        "rest_host": target_host,
    }
    for key, value in expected.items():
        if identity.get(key) != value:
            raise DeployError(f"Temporary bridge site identity failed for {key}")
    return expected


def poll_deployment_status(
    client: Client,
    token: str,
    deployment_id: str,
) -> dict[str, Any]:
    deadline = time.monotonic() + max(10, min(client.timeout + 180, 420))
    last: dict[str, Any] = {}
    transitional = {
        "reserved",
        "locked",
        "prepared",
        "installing",
        "rolling_back",
        "committing",
    }
    terminal = {
        "candidate_activation_pending",
        "candidate_activation_complete",
        "installed",
        "installed_pending_cleanup",
        "installed_pending_stabilization",
        "failed",
        "rolled_back",
        "rollback_failed",
        "commit_failed",
        "committed",
        "cleanup_failed",
        "finalized",
    }
    while time.monotonic() < deadline:
        try:
            last = bridge_call(client, "status", token, deployment_id)
        except DeployError:
            time.sleep(2)
            continue
        phase = str(last.get("phase", ""))
        if phase in terminal:
            return last
        if phase in transitional and last.get("recovery_ready"):
            return last
        if phase not in transitional:
            raise DeployError(f"Deployment status returned an invalid phase (phase={phase or 'missing'})")
        time.sleep(2)
    phase = str(last.get("phase", "unknown"))
    raise DeployError(f"Deployment status did not reach a terminal phase (phase={phase})")


def preflight_with_recovery(
    client: Client,
    token: str,
    deployment_id: str,
) -> dict[str, Any]:
    try:
        return bridge_call(client, "preflight", token, deployment_id)
    except DeployError as first_error:
        try:
            return bridge_call(client, "preflight", token, deployment_id)
        except DeployError:
            raise first_error


def install_with_recovery(
    client: Client,
    token: str,
    deployment_id: str,
    run_fields: dict[str, Any],
    expected_plugin_sha256: str,
) -> dict[str, Any]:
    if re.fullmatch(r"[a-f0-9]{64}", expected_plugin_sha256) is None:
        raise DeployError("Expected installed plugin digest is invalid")
    try:
        result = bridge_call(client, "run", token, deployment_id, **run_fields)
    except DeployError as original_error:
        status = poll_deployment_status(client, token, deployment_id)
        if candidate_activation_status_exact(
            status,
            deployment_id,
            run_fields,
            expected_plugin_sha256,
            completed=False,
        ) or candidate_activation_status_exact(
            status,
            deployment_id,
            run_fields,
            expected_plugin_sha256,
            completed=True,
        ):
            result = candidate_activation_install_result_from_status(
                status,
                deployment_id,
                run_fields,
                expected_plugin_sha256,
            )
            result["write_response_recovered"] = True
            return continue_candidate_activation(
                client,
                token,
                deployment_id,
                run_fields,
                expected_plugin_sha256,
                result,
            )
        if (
            status.get("phase")
            in {
                "installed",
                "installed_pending_cleanup",
                "installed_pending_stabilization",
            }
            and status.get("forward_stabilization_candidate")
            and status.get("expected_sha256") == run_fields["expected_sha256"]
            and status.get("installed_plugin_sha256") == expected_plugin_sha256
            and status.get("current_plugin_sha256")
            == expected_plugin_sha256
            and status.get("current_version") == run_fields["version"]
            and status.get("current_database_version") == run_fields["version"]
            and status.get("current_active")
        ):
            installed_plugin_sha256 = str(
                status.get("installed_plugin_sha256", "")
            )
            stabilization = stabilize_deployment(
                client,
                token,
                deployment_id,
                run_fields["version"],
                installed_plugin_sha256,
            )
            return {
                "active": True,
                "baseline_database_fingerprint": status.get(
                    "baseline_database_fingerprint", ""
                ),
                "cache_purge": {
                    "response_recovered": True,
                    "stabilization": stabilization,
                },
                "deployment_id": deployment_id,
                "had_plugin": bool(status.get("had_plugin")),
                "prior_active": bool(status.get("prior_active")),
                "prior_deployment": status.get("prior_deployment", ""),
                "prior_plugin_sha256": status.get("prior_plugin_sha256", ""),
                "prior_version": status.get("prior_version", ""),
                "robots_prior_exists": bool(status.get("robots_prior_exists")),
                "robots_prior_sha256": status.get("robots_prior_sha256", ""),
                "robots_sha256": status.get("robots_managed_sha256", ""),
                "installed_plugin_sha256": installed_plugin_sha256,
                "sha256": run_fields["expected_sha256"],
                "temp_removed": True,
                "version": run_fields["version"],
                "write_response_recovered": True,
            }
        raise original_error
    if result.get("continuation_required") is True:
        return continue_candidate_activation(
            client,
            token,
            deployment_id,
            run_fields,
            expected_plugin_sha256,
            result,
        )
    return result


def candidate_activation_status_exact(
    status: dict[str, Any],
    deployment_id: str,
    run_fields: dict[str, Any],
    expected_plugin_sha256: str,
    *,
    completed: bool,
) -> bool:
    """Validate the bounded, non-secret candidate handoff projection."""
    phase = str(status.get("phase", ""))
    common = (
        status.get("deployment_id") == deployment_id
        and status.get("state_exists") is True
        and status.get("lock_owned") is True
        and status.get("temp_removed") is True
        and status.get("expected_sha256") == run_fields.get("expected_sha256")
        and status.get("expected_version") == run_fields.get("version")
        and status.get("installed_plugin_sha256") == expected_plugin_sha256
        and status.get("current_plugin_sha256") == expected_plugin_sha256
        and status.get("current_version") == run_fields.get("version")
        and status.get("candidate_activation_required") is True
        and status.get("candidate_requested_active") is True
        and type(status.get("candidate_prior_active")) is bool
        and status.get("baseline_database_journal_valid") is True
        and re.fullmatch(
            r"[a-f0-9]{64}", str(status.get("baseline_database_fingerprint", ""))
        )
        is not None
        and status.get("no_rollback_artifacts") is True
    )
    if not common:
        return False
    if not completed:
        return (
            phase == "candidate_activation_pending"
            and status.get("candidate_activation_phase") == "pending"
            and status.get("forward_ready") is False
        )
    candidate_fingerprint = str(status.get("candidate_database_fingerprint", ""))
    return (
        phase in {"candidate_activation_complete", "installed_pending_stabilization"}
        and status.get("candidate_activation_phase") == "complete"
        and type(status.get("candidate_activation_completed_at")) is int
        and status.get("candidate_activation_completed_at", 0) > 0
        and re.fullmatch(r"[a-f0-9]{64}", candidate_fingerprint) is not None
        and status.get("database_fingerprint") == candidate_fingerprint
        and status.get("current_database_version") == run_fields.get("version")
        and status.get("current_deployment") == deployment_id
        and status.get("current_active") is True
        and status.get("migration_failed") is False
        and status.get("migration_invariants_valid") is True
        and status.get("forward_ready") is True
        and status.get("robots_applied") is True
        and re.fullmatch(
            r"[a-f0-9]{64}", str(status.get("robots_managed_sha256", ""))
        )
        is not None
        and status.get("robots_managed_sha256")
        == status.get("current_robots_sha256")
    )


def candidate_activation_install_result_from_status(
    status: dict[str, Any],
    deployment_id: str,
    run_fields: dict[str, Any],
    expected_plugin_sha256: str,
) -> dict[str, Any]:
    return {
        "active": bool(status.get("current_active")),
        "backup_ready": True,
        "baseline_database_fingerprint": status.get(
            "baseline_database_fingerprint", ""
        ),
        "continuation_required": True,
        "deployment_id": deployment_id,
        "had_plugin": bool(status.get("had_plugin")),
        "installed": True,
        "installed_plugin_sha256": expected_plugin_sha256,
        "prior_active": bool(status.get("prior_active")),
        "prior_deployment": status.get("prior_deployment", ""),
        "prior_plugin_sha256": status.get("prior_plugin_sha256", ""),
        "prior_version": status.get("prior_version", ""),
        "requested_active": True,
        "robots_prior_exists": bool(status.get("robots_prior_exists")),
        "robots_prior_sha256": status.get("robots_prior_sha256", ""),
        "sha256": run_fields["expected_sha256"],
        "slug": run_fields["slug"],
        "temp_removed": True,
        "version": run_fields["version"],
    }


def candidate_activation_status_matches_handoff(
    status: dict[str, Any], install_result: dict[str, Any]
) -> bool:
    """Bind every rollback-relevant handoff field to the same durable journal."""
    return (
        status.get("baseline_database_fingerprint")
        == install_result.get("baseline_database_fingerprint")
        and status.get("had_plugin") is install_result.get("had_plugin")
        and status.get("prior_active") is install_result.get("prior_active")
        and status.get("candidate_prior_active")
        is install_result.get("prior_active")
        and status.get("prior_deployment")
        == install_result.get("prior_deployment")
        and status.get("prior_plugin_sha256")
        == install_result.get("prior_plugin_sha256")
        and status.get("prior_version") == install_result.get("prior_version")
        and status.get("robots_prior_exists")
        is install_result.get("robots_prior_exists")
        and status.get("robots_prior_sha256")
        == install_result.get("robots_prior_sha256")
    )


def continue_candidate_activation(
    client: Client,
    token: str,
    deployment_id: str,
    run_fields: dict[str, Any],
    expected_plugin_sha256: str,
    install_result: dict[str, Any],
) -> dict[str, Any]:
    """Complete the fresh-request activation handoff with bounded ACK recovery."""
    handoff_keys = {
        "active",
        "backup_ready",
        "baseline_database_fingerprint",
        "continuation_required",
        "deployment_id",
        "had_plugin",
        "installed",
        "installed_plugin_sha256",
        "prior_active",
        "prior_deployment",
        "prior_plugin_sha256",
        "prior_version",
        "requested_active",
        "robots_prior_exists",
        "robots_prior_sha256",
        "sha256",
        "slug",
        "temp_removed",
        "version",
    }
    actual_handoff_keys = frozenset(install_result)
    if (
        actual_handoff_keys
        not in {frozenset(handoff_keys), frozenset(handoff_keys | {"write_response_recovered"})}
        or (
            "write_response_recovered" in install_result
            and install_result.get("write_response_recovered") is not True
        )
        or install_result.get("continuation_required") is not True
        or install_result.get("installed") is not True
        or install_result.get("requested_active") is not True
        or install_result.get("backup_ready") is not True
        or install_result.get("deployment_id") != deployment_id
        or install_result.get("slug") != run_fields.get("slug")
        or install_result.get("sha256") != run_fields.get("expected_sha256")
        or install_result.get("version") != run_fields.get("version")
        or install_result.get("installed_plugin_sha256") != expected_plugin_sha256
        or install_result.get("temp_removed") is not True
        or type(install_result.get("active")) is not bool
        or type(install_result.get("had_plugin")) is not bool
        or type(install_result.get("prior_active")) is not bool
        or type(install_result.get("robots_prior_exists")) is not bool
        or (
            install_result.get("prior_active") is True
            and install_result.get("had_plugin") is not True
        )
        or re.fullmatch(
            r"[a-f0-9]{64}", str(install_result.get("baseline_database_fingerprint", ""))
        )
        is None
        or (
            install_result.get("robots_prior_exists") is True
            and re.fullmatch(
                r"[a-f0-9]{64}", str(install_result.get("robots_prior_sha256", ""))
            )
            is None
        )
        or (
            install_result.get("robots_prior_exists") is False
            and install_result.get("robots_prior_sha256") != ""
        )
        or (
            install_result.get("had_plugin") is True
            and re.fullmatch(
                r"[a-f0-9]{64}", str(install_result.get("prior_plugin_sha256", ""))
            )
            is None
        )
        or (
            install_result.get("had_plugin") is False
            and install_result.get("prior_plugin_sha256") != ""
        )
    ):
        raise DeployError("Candidate activation handoff identity is invalid")

    response: dict[str, Any] | None = None
    response_recovered = False
    first_error: DeployError | None = None
    for attempt in range(2):
        try:
            response = bridge_call(
                client,
                "continue-activation",
                token,
                deployment_id,
            )
            break
        except DeployError as error:
            if first_error is None:
                first_error = error
            retryable = isinstance(error, NetworkDeployError) or (
                isinstance(error, HTTPDeployError)
                and type(error.status) is int
                and 500 <= error.status <= 599
            )
            if not retryable:
                raise
            status = poll_deployment_status(client, token, deployment_id)
            if candidate_activation_status_exact(
                status,
                deployment_id,
                run_fields,
                expected_plugin_sha256,
                completed=True,
            ) and candidate_activation_status_matches_handoff(status, install_result):
                if attempt == 0:
                    response_recovered = True
                    continue
                response = {
                    "active": True,
                    "continued": True,
                    "deployment_id": deployment_id,
                    "idempotent": True,
                    "phase": "installed_pending_stabilization",
                }
                response_recovered = True
                break
            if attempt == 0 and candidate_activation_status_exact(
                status,
                deployment_id,
                run_fields,
                expected_plugin_sha256,
                completed=False,
            ) and candidate_activation_status_matches_handoff(status, install_result):
                response_recovered = True
                continue
            raise first_error

    if response is None:
        if first_error is not None:
            raise first_error
        raise DeployError("Candidate activation continuation returned no result")
    response_keys = set(response)
    required_keys = {"active", "continued", "deployment_id", "phase"}
    if (
        frozenset(response_keys)
        not in {frozenset(required_keys), frozenset(required_keys | {"idempotent"})}
        or response.get("active") is not True
        or response.get("continued") is not True
        or response.get("deployment_id") != deployment_id
        or response.get("phase") != "installed_pending_stabilization"
        or ("idempotent" in response and response.get("idempotent") is not True)
    ):
        raise DeployError("Candidate activation continuation response is invalid")

    status = bridge_call(client, "status", token, deployment_id)
    if not (
        candidate_activation_status_exact(
            status,
            deployment_id,
            run_fields,
            expected_plugin_sha256,
            completed=True,
        )
        and candidate_activation_status_matches_handoff(status, install_result)
    ):
        raise DeployError("Candidate activation completion was not durably verified")

    normalized = dict(install_result)
    normalized.update(
        {
            "active": True,
            "cache_purge": {
                "candidate_activation_continuation": True,
                "response_recovered": response_recovered,
            },
            "candidate_activation": {
                "schema": "complete99-candidate-activation-client-receipt/v1",
                "deployment_id": deployment_id,
                "phase": "installed_pending_stabilization",
                "artifact_sha256": run_fields["expected_sha256"],
                "plugin_sha256": expected_plugin_sha256,
                "version": run_fields["version"],
                "completed_at": status["candidate_activation_completed_at"],
                "database_fingerprint": status["candidate_database_fingerprint"],
                "idempotent": bool(response.get("idempotent")),
                "response_recovered": response_recovered,
            },
            "continuation_required": False,
            "robots_sha256": status["robots_managed_sha256"],
        }
    )
    return normalized


def stabilize_deployment(
    client: Client,
    token: str,
    deployment_id: str,
    version: str,
    installed_plugin_sha256: str,
) -> dict[str, Any]:
    response: dict[str, Any] | None = None
    original_error: DeployError | None = None
    initial_failure_context: dict[str, Any] = {}
    stabilization_attempts = 0
    for attempt in range(2):
        stabilization_attempts += 1
        try:
            response = bridge_call(client, "stabilize", token, deployment_id)
            break
        except DeployError as error:
            original_error = error
            if (
                attempt == 0
                and isinstance(error, HTTPDeployError)
                and error.code == "c99_stabilize_forward_mismatch"
                and error.data.get("retryable_forward_mismatch") is True
            ):
                initial_failure_context = dict(error.data)
                time.sleep(2)
                continue
            break

    if response is None:
        if original_error is None:
            raise DeployError("Deployment stabilization returned no result")
        status = poll_deployment_status(client, token, deployment_id)
        if (
            status.get("phase") == "installed"
            and status.get("stabilized")
            and status.get("current_active")
            and status.get("current_version") == version
            and status.get("current_database_version") == version
            and status.get("current_deployment") == deployment_id
            and status.get("current_plugin_sha256") == installed_plugin_sha256
            and status.get("installed_plugin_sha256") == installed_plugin_sha256
            and status.get("database_fingerprint")
            == status.get("post_install_database_fingerprint")
        ):
            response = {
                "cache_purge": {"response_recovered": True},
                "database_version": version,
                "deployment_id": deployment_id,
                "installed_plugin_sha256": installed_plugin_sha256,
                "post_install_database_fingerprint": status.get(
                    "post_install_database_fingerprint", ""
                ),
                "stabilized": True,
                "stabilized_from_phase": "installed",
                "version": version,
            }
        else:
            raise original_error
    status = bridge_call(client, "status", token, deployment_id)
    persisted_fingerprint = str(
        status.get("post_install_database_fingerprint", "")
    )
    if (
        status.get("phase") != "installed"
        or not status.get("stabilized")
        or not status.get("current_active")
        or status.get("current_version") != version
        or status.get("current_database_version") != version
        or status.get("current_deployment") != deployment_id
        or status.get("current_plugin_sha256") != installed_plugin_sha256
        or status.get("installed_plugin_sha256") != installed_plugin_sha256
        or not re.fullmatch(r"[a-f0-9]{64}", persisted_fingerprint)
        or status.get("database_fingerprint") != persisted_fingerprint
        or response.get("post_install_database_fingerprint")
        != persisted_fingerprint
    ):
        raise DeployError(
            "Post-migration stabilization did not persist the exact checkpoint"
        )
    if (
        not response.get("stabilized")
        or response.get("version") != version
        or response.get("database_version") != version
        or response.get("deployment_id") != deployment_id
        or response.get("installed_plugin_sha256") != installed_plugin_sha256
        or not re.fullmatch(
            r"[a-f0-9]{64}",
            str(response.get("post_install_database_fingerprint", "")),
        )
    ):
        raise DeployError("Post-migration stabilization failed exact release verification")
    return {
        "cache_purge": response.get("cache_purge", {}),
        "database_version": version,
        "deployment_id": deployment_id,
        "installed_plugin_sha256": installed_plugin_sha256,
        "post_install_database_fingerprint": response.get(
            "post_install_database_fingerprint", ""
        ),
        "response_recovered": bool(
            response.get("cache_purge", {}).get("response_recovered")
        ),
        "initial_failure_context": initial_failure_context,
        "stabilization_attempts": stabilization_attempts,
        "stabilized": True,
        "stabilized_from_phase": response.get("stabilized_from_phase", ""),
        "version": version,
    }


def configure_sync(
    client: Client,
    token: str,
    deployment_id: str,
    sync_value: str,
) -> dict[str, Any]:
    """Initialize the WordPress sync credential without logging or persisting it locally."""
    if len(sync_value) < 32 or len(sync_value) > 4096:
        raise DeployError(
            "COMPLETE99_WORDPRESS_SYNC_SECRET must contain between 32 and 4096 characters"
        )

    recovered = False
    try:
        response = bridge_call(
            client,
            "configure-sync",
            token,
            deployment_id,
            sync_secret=sync_value,
        )
    except HTTPDeployError:
        raise
    except DeployError as original_error:
        recovered = True
        try:
            response = bridge_call(
                client,
                "configure-sync",
                token,
                deployment_id,
                sync_secret=sync_value,
            )
        except DeployError:
            status = bridge_call(client, "status", token, deployment_id)
            if (
                status.get("phase") == "installed"
                and status.get("stabilized")
                and status.get("current_sync_configured")
                and status.get("sync_configuration_checkpointed")
                and not status.get("sync_configuration_pending")
                and re.fullmatch(
                    r"[a-f0-9]{64}",
                    str(status.get("post_install_database_fingerprint", "")),
                )
                and status.get("database_fingerprint")
                == status.get("post_install_database_fingerprint")
            ):
                response = {
                    "configured": True,
                    "changed": False,
                    "idempotent": True,
                    "database_fingerprint": status.get(
                        "post_install_database_fingerprint", ""
                    ),
                }
            else:
                raise original_error

    database_fingerprint = str(response.get("database_fingerprint", ""))
    if (
        not response.get("configured")
        or not isinstance(response.get("changed"), bool)
        or not isinstance(response.get("idempotent"), bool)
        or response.get("changed") == response.get("idempotent")
        or not re.fullmatch(r"[a-f0-9]{64}", database_fingerprint)
    ):
        raise DeployError("Sync configuration response failed verification")

    status = bridge_call(client, "status", token, deployment_id)
    if (
        status.get("phase") != "installed"
        or not status.get("stabilized")
        or not status.get("current_sync_configured")
        or not status.get("sync_configuration_checkpointed")
        or status.get("sync_configuration_pending")
        or status.get("database_fingerprint") != database_fingerprint
        or status.get("post_install_database_fingerprint")
        != database_fingerprint
    ):
        raise DeployError("Sync configuration checkpoint was not durably verified")
    return {
        "configured": True,
        "changed": bool(response.get("changed")),
        "idempotent": bool(response.get("idempotent")),
        "database_fingerprint": database_fingerprint,
        "response_recovered": recovered,
    }


def rollback_with_recovery(
    client: Client,
    token: str,
    deployment_id: str,
) -> dict[str, Any]:
    try:
        return bridge_call(client, "rollback", token, deployment_id)
    except DeployError as original_error:
        retryable_ambiguity = isinstance(original_error, NetworkDeployError) or (
            isinstance(original_error, HTTPDeployError)
            and type(original_error.status) is int
            and 500 <= original_error.status <= 599
        )
        if not retryable_ambiguity:
            raise
        status = poll_deployment_status(client, token, deployment_id)
        if status.get("phase") in {
            "candidate_activation_pending",
            "candidate_activation_complete",
            "installed",
            "installed_pending_cleanup",
            "installed_pending_stabilization",
            "failed",
            "rollback_failed",
            "commit_failed",
            "committing",
            "prepared",
            "installing",
            "rolling_back",
        }:
            if status.get("phase") not in {
                "committing",
                "prepared",
                "installing",
                "rolling_back",
            } or status.get("recovery_ready"):
                try:
                    return bridge_call(client, "rollback", token, deployment_id)
                except DeployError:
                    status = poll_deployment_status(client, token, deployment_id)
        if status.get("phase") == "rolled_back" and status.get("database_restored"):
            return {
                "baseline_database_fingerprint": status.get(
                    "baseline_database_fingerprint", ""
                ),
                "database_restore": {"response_recovered": True},
                "had_plugin": bool(status.get("had_plugin")),
                "prior_active": bool(status.get("prior_active")),
                "prior_deployment": status.get("prior_deployment", ""),
                "prior_plugin_sha256": status.get("prior_plugin_sha256", ""),
                "prior_version": status.get("prior_version", ""),
                "robots_prior_exists": bool(status.get("robots_prior_exists")),
                "robots_prior_sha256": status.get("robots_prior_sha256", ""),
                "robots_restore": {
                    "not_managed": not bool(status.get("robots_applied")),
                    "response_recovered": True,
                    "restored": bool(status.get("robots_restored")),
                },
                "rolled_back": True,
                "write_response_recovered": True,
            }
        raise original_error


def verify_rollback_integrity(
    client: Client,
    token: str,
    deployment_id: str,
    rollback: dict[str, Any],
) -> dict[str, Any]:
    # Status is an idempotent read. Polling lets a transient runner-to-origin
    # timeout clear without issuing a second rollback mutation.
    status = poll_deployment_status(client, token, deployment_id)
    expected = str(rollback.get("baseline_database_fingerprint", ""))
    actual = str(status.get("database_fingerprint", ""))
    if not re.fullmatch(r"[a-f0-9]{64}", expected) or actual != expected:
        raise DeployError("Rollback did not restore the pre-deployment database fingerprint")
    had_plugin = bool(rollback.get("had_plugin"))
    prior_active = bool(rollback.get("prior_active"))
    if bool(status.get("current_active")) != prior_active:
        raise DeployError("Rollback did not restore the prior plugin activation state")
    if had_plugin:
        prior_digest = str(rollback.get("prior_plugin_sha256", ""))
        if (
            not status.get("current_target_dir_exists")
            or not status.get("current_plugin_main_exists")
            or not re.fullmatch(r"[a-f0-9]{64}", prior_digest)
            or status.get("current_plugin_sha256") != prior_digest
            or status.get("current_version") != rollback.get("prior_version")
        ):
            raise DeployError("Rollback did not restore the exact prior plugin files")
    elif status.get("current_target_dir_exists") or status.get("current_plugin_main_exists"):
        raise DeployError("First-install rollback left plugin files behind")
    prior_robots_exists = bool(rollback.get("robots_prior_exists"))
    prior_robots_sha256 = str(rollback.get("robots_prior_sha256", ""))
    expected_robots_sha256 = prior_robots_sha256 if prior_robots_exists else ""
    if prior_robots_exists and not re.fullmatch(r"[a-f0-9]{64}", prior_robots_sha256):
        raise DeployError("Rollback journal did not expose a valid prior robots.txt fingerprint")
    if not prior_robots_exists and prior_robots_sha256:
        raise DeployError("Rollback journal reported an impossible absent robots.txt fingerprint")
    if (
        status.get("current_robots_sha256") != expected_robots_sha256
        or (
            status.get("robots_applied")
            and not status.get("robots_restored")
        )
    ):
        raise DeployError("Rollback did not restore the exact prior robots.txt filesystem state")
    return {
        "database_fingerprint": actual,
        "database_restored": bool(status.get("database_restored")),
        "plugin_files_restored": had_plugin,
        "plugin_absent": not had_plugin,
        "robots_prior_exists": prior_robots_exists,
        "robots_sha256": expected_robots_sha256,
        "robots_restored": bool(status.get("robots_restored")),
    }


def validate_orphaned_rollback_live_state(
    deployment_id: str,
    status: dict[str, Any],
    proof: dict[str, Any],
) -> tuple[dict[str, Any], dict[str, Any]]:
    """Validate immutable release identity before observation or reconciliation."""
    failed = proof.get("failed_run", {})
    prior = proof.get("prior_run", {})
    reconciliation = proof.get("database_reconciliation")
    proof_is_v2 = isinstance(reconciliation, dict)
    reviewed_candidate_bindings = {
        "expected_sha256": failed.get("artifact_sha256"),
        "expected_version": failed.get("candidate_version"),
        "installed_plugin_sha256": failed.get("candidate_plugin_sha256"),
        "post_install_database_fingerprint": failed.get(
            "candidate_database_fingerprint"
        ),
    }
    for key, reviewed_value in reviewed_candidate_bindings.items():
        observed_value = status.get(key)
        identity_is_unavailable = observed_value is None or observed_value == ""
        orphaned_state_is_absent = (
            status.get("phase") == "rolling_back"
            and status.get("state_exists") is False
        )
        if (
            observed_value != reviewed_value
            and not (
                identity_is_unavailable
                and (
                    not proof_is_v2
                    or orphaned_state_is_absent
                )
            )
        ):
            raise DeployError(
                f"Orphaned rollback failed-run identity does not match {key}"
            )
    if (
        status.get("phase") != "rolling_back"
        or status.get("state_exists") is not False
        or status.get("lock_owned") is not True
        or status.get("recovery_ready") is not True
        or status.get("process_lock_available") is not True
        or deployment_id != failed.get("deployment_id")
        or status.get("current_version") != prior.get("version")
        or status.get("current_database_version") != prior.get("database_version")
        or status.get("current_active") is not prior.get("active")
        or status.get("current_plugin_sha256") != prior.get("plugin_sha256")
        or status.get("current_sync_configured") is not prior.get("sync_configured")
        or status.get("current_robots_sha256") != prior.get("robots_sha256")
        or status.get("current_deployment")
        not in {failed.get("deployment_id"), prior.get("deployment_id")}
    ):
        raise DeployError(
            "Orphaned rollback live state does not match the reviewed audit proof"
        )
    if proof_is_v2:
        current_deployment = status.get("current_deployment")
        expected_current_fingerprint = (
            reconciliation.get("observed_database_fingerprint")
            if current_deployment == failed.get("deployment_id")
            else reconciliation.get("expected_reconciled_database_fingerprint")
        )
        if (
            status.get("database_fingerprint") != expected_current_fingerprint
            or status.get("projected_deployment_id")
            != prior.get("deployment_id")
            or status.get("projected_database_fingerprint")
            != reconciliation.get("expected_reconciled_database_fingerprint")
            or status.get("database_manifest")
            != reconciliation.get("preserved_manifest")
            or status.get("database_manifest_sha256")
            != reconciliation.get("preserved_manifest_sha256")
            or status.get("database_storage")
            != reconciliation.get("transactional_storage")
        ):
            raise DeployError(
                "Orphaned rollback live database state does not match the reviewed v2 attestation"
            )
    return failed, prior


def observe_orphaned_rollback(
    deployment_id: str,
    status: dict[str, Any],
    proof: dict[str, Any],
    proof_sha256: str,
) -> dict[str, Any]:
    """Return a non-secret, mutation-free current-state attestation."""
    failed, prior = validate_orphaned_rollback_live_state(
        deployment_id,
        status,
        proof,
    )
    manifest = status.get("database_manifest")
    components = [
        "options_without_deployment_marker",
        "posts",
        "postmeta",
        "seed_ids",
        "evaluation_ids",
    ]
    schema = manifest.get("schema") if isinstance(manifest, dict) else None
    if schema == "complete99-database-snapshot-manifest/v3":
        components.extend(["ops_tables", "campaign_tables"])
    elif schema == "complete99-database-snapshot-manifest/v2":
        components.append("ops_tables")
    elif schema != "complete99-database-snapshot-manifest/v1":
        raise DeployError("Orphaned rollback database manifest schema is invalid")
    expected_manifest_keys = {
        "schema",
        "sync_secret_existed",
        "sync_secret_configured",
    }
    for component in components:
        expected_manifest_keys.add(f"{component}_count")
        expected_manifest_keys.add(f"{component}_sha256")
    if not isinstance(manifest, dict) or set(manifest) != expected_manifest_keys:
        raise DeployError("Orphaned rollback database manifest is invalid")
    if (
        manifest.get("sync_secret_existed") is not True
        or manifest.get("sync_secret_configured") is not True
    ):
        raise DeployError("Orphaned rollback database manifest lost sync identity")
    for component in components:
        count = manifest.get(f"{component}_count")
        digest = manifest.get(f"{component}_sha256")
        if (
            type(count) is not int
            or count < 0
            or count > 9223372036854775807
            or (component in {"ops_tables", "campaign_tables"} and count != 7)
        ):
            raise DeployError(
                f"Orphaned rollback database manifest count is invalid for {component}"
            )
        if not isinstance(digest, str) or re.fullmatch(r"[a-f0-9]{64}", digest) is None:
            raise DeployError(
                f"Orphaned rollback database manifest digest is invalid for {component}"
            )
    current_fingerprint = status.get("database_fingerprint")
    projected_fingerprint = status.get("projected_database_fingerprint")
    manifest_sha256 = status.get("database_manifest_sha256")
    database_storage = status.get("database_storage")
    if (
        status.get("projected_deployment_id") != prior.get("deployment_id")
        or not isinstance(current_fingerprint, str)
        or re.fullmatch(r"[a-f0-9]{64}", current_fingerprint) is None
        or not isinstance(projected_fingerprint, str)
        or re.fullmatch(r"[a-f0-9]{64}", projected_fingerprint) is None
        or not isinstance(manifest_sha256, str)
        or re.fullmatch(r"[a-f0-9]{64}", manifest_sha256) is None
    ):
        raise DeployError("Orphaned rollback database observation is incomplete")
    if (
        not isinstance(database_storage, dict)
        or set(database_storage) != {"engine", "tables"}
        or database_storage.get("engine")
        not in {"INNODB", "XTRADB", "INNODB,XTRADB"}
        or type(database_storage.get("tables")) is not int
        or database_storage.get("tables") != 3
    ):
        raise DeployError(
            "Orphaned rollback database observation storage is not transactional"
        )
    canonical_manifest = json.dumps(
        manifest,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    if not secrets.compare_digest(
        manifest_sha256,
        hashlib.sha256(canonical_manifest).hexdigest(),
    ):
        raise DeployError("Orphaned rollback database manifest digest does not match")
    return {
        "schema": "complete99-orphaned-rollback-observation/v1",
        "deployment_id": deployment_id,
        "proof_sha256": proof_sha256,
        "phase": "rolling_back",
        "state_exists": False,
        "lock_owned": True,
        "recovery_ready": True,
        "process_lock_available": True,
        "current_version": str(status.get("current_version", "")),
        "current_database_version": str(
            status.get("current_database_version", "")
        ),
        "current_active": bool(status.get("current_active")),
        "current_plugin_sha256": str(status.get("current_plugin_sha256", "")),
        "current_deployment": str(status.get("current_deployment", "")),
        "current_database_fingerprint": current_fingerprint,
        "projected_deployment_id": str(prior["deployment_id"]),
        "projected_database_fingerprint": projected_fingerprint,
        "historical_baseline_database_fingerprint": str(
            prior["database_fingerprint"]
        ),
        "historical_baseline_matches_projection": projected_fingerprint
        == prior["database_fingerprint"],
        "current_sync_configured": bool(status.get("current_sync_configured")),
        "current_robots_sha256": str(status.get("current_robots_sha256", "")),
        "database_manifest": manifest,
        "database_manifest_sha256": manifest_sha256,
        "database_storage": database_storage,
        "failed_candidate_database_fingerprint": str(
            failed["candidate_database_fingerprint"]
        ),
    }


def validate_v2_reconciliation_response(
    response: dict[str, Any],
    reconciliation: dict[str, Any],
) -> None:
    """Reject any mutation response that is not the exact reviewed v2 receipt."""
    receipt_sha256 = response.get("receipt_sha256")
    evidence_exists = response.get("evidence_directory_exists")
    evidence_sha256 = response.get("evidence_directory_sha256")
    marker_corrected = response.get("marker_corrected")
    marker_rows_affected = response.get("marker_rows_affected")
    marker_transition = response.get("marker_transition")
    if (
        response.get("reconciled") is not True
        or response.get("phase") != "committed"
        or response.get("lock_retained") is not True
        or response.get("receipt_schema")
        != "complete99-orphaned-rollback-receipt/v2"
        or type(receipt_sha256) is not str
        or re.fullmatch(r"[a-f0-9]{64}", receipt_sha256) is None
        or type(evidence_exists) is not bool
        or type(evidence_sha256) is not str
        or (
            evidence_exists
            and re.fullmatch(r"[a-f0-9]{64}", evidence_sha256) is None
        )
        or (not evidence_exists and evidence_sha256 != "")
        or type(marker_corrected) is not bool
        or type(marker_rows_affected) is not int
        or marker_rows_affected not in {0, 1}
        or marker_transition not in {"corrected", "already-correct"}
        or (marker_rows_affected == 1) != (marker_transition == "corrected")
        or marker_corrected is not (marker_rows_affected == 1)
        or response.get("historical_baseline_database_fingerprint")
        != reconciliation["baseline_database_fingerprint"]
        or response.get("observed_database_fingerprint")
        != reconciliation["observed_database_fingerprint"]
        or response.get("reconciled_database_fingerprint")
        != reconciliation["expected_reconciled_database_fingerprint"]
        or response.get("preserved_manifest_sha256")
        != reconciliation["preserved_manifest_sha256"]
    ):
        raise DeployError(
            "Orphaned rollback v2 mutation response did not confirm the reviewed receipt"
        )


def v2_reconciliation_response_from_status(
    status: dict[str, Any],
) -> dict[str, Any]:
    """Project the durable lock receipt into the v2 mutation response shape."""
    marker_rows_affected = status.get("orphaned_marker_rows_affected")
    return {
        "reconciled": status.get("phase") == "committed",
        "phase": status.get("phase"),
        "lock_retained": status.get("lock_owned"),
        "receipt_schema": status.get("orphaned_recovery_receipt_schema"),
        "receipt_sha256": status.get("orphaned_recovery_receipt_sha256"),
        "evidence_directory_exists": status.get(
            "orphaned_recovery_evidence_exists"
        ),
        "evidence_directory_sha256": status.get(
            "orphaned_recovery_evidence_sha256"
        ),
        "marker_corrected": marker_rows_affected == 1,
        "marker_rows_affected": marker_rows_affected,
        "marker_transition": status.get("orphaned_marker_transition"),
        "historical_baseline_database_fingerprint": status.get(
            "orphaned_historical_baseline_database_fingerprint"
        ),
        "observed_database_fingerprint": status.get(
            "orphaned_observed_database_fingerprint"
        ),
        "reconciled_database_fingerprint": status.get(
            "committed_expected_database_fingerprint"
        ),
        "preserved_manifest_sha256": status.get(
            "orphaned_preserved_manifest_sha256"
        ),
    }


def reconcile_orphaned_rollback(
    client: Client,
    token: str,
    deployment_id: str,
    status: dict[str, Any],
    proof: dict[str, Any],
    proof_sha256: str,
) -> dict[str, Any]:
    """Create a durable terminal rollback receipt from reviewed audit proof."""
    failed, prior = validate_orphaned_rollback_live_state(
        deployment_id,
        status,
        proof,
    )
    reconciliation = proof.get("database_reconciliation")
    proof_is_v2 = isinstance(reconciliation, dict)
    expected_receipt_schema = (
        "complete99-orphaned-rollback-receipt/v2"
        if proof_is_v2
        else "complete99-orphaned-rollback-receipt/v1"
    )
    request_fields: dict[str, Any] = {
        "proof_sha256": proof_sha256,
        "expected_observed_deployment": failed["deployment_id"],
        "expected_prior_deployment": prior["deployment_id"],
        "expected_prior_version": prior["version"],
        "expected_prior_database_version": prior["database_version"],
        "expected_prior_active": prior["active"],
        "expected_prior_plugin_sha256": prior["plugin_sha256"],
        "expected_baseline_database_fingerprint": prior["database_fingerprint"],
        "expected_prior_robots_exists": prior["robots_exists"],
        "expected_prior_robots_sha256": prior["robots_sha256"],
        "expected_sync_configured": prior["sync_configured"],
        "reviewed_proof": proof,
    }
    if proof_is_v2:
        request_fields.update(
            {
                "expected_observed_database_fingerprint": reconciliation[
                    "observed_database_fingerprint"
                ],
                "expected_reconciled_database_fingerprint": reconciliation[
                    "expected_reconciled_database_fingerprint"
                ],
                "expected_preserved_manifest_sha256": reconciliation[
                    "preserved_manifest_sha256"
                ],
                "expected_attestation_sha256": reconciliation[
                    "attestation_sha256"
                ],
                "expected_attestation_run_id": reconciliation[
                    "attestation_run_id"
                ],
            }
        )
    response_recovered = False
    committed: dict[str, Any] | None = None
    mutation_error: DeployError | None = None
    response: dict[str, Any] = {}
    try:
        response = bridge_call(
            client,
            "reconcile-orphaned-rollback",
            token,
            deployment_id,
            **request_fields,
        )
        if proof_is_v2:
            validate_v2_reconciliation_response(response, reconciliation)
    except DeployError as error:
        if not proof_is_v2:
            raise
        mutation_error = error

    if proof_is_v2:
        try:
            committed = bridge_call(
                client,
                "status",
                token,
                deployment_id,
                projected_deployment_id=prior["deployment_id"],
            )
        except DeployError:
            if mutation_error is not None:
                raise mutation_error
            raise
        try:
            if (
                committed.get("phase") != "committed"
                or committed.get("state_exists") is not False
                or committed.get("lock_owned") is not True
                or committed.get("orphaned_recovery_proof_sha256")
                != proof_sha256
            ):
                raise DeployError(
                    "Orphaned rollback durable status did not confirm the reviewed proof"
                )
            authoritative_response = v2_reconciliation_response_from_status(
                committed
            )
            validate_v2_reconciliation_response(
                authoritative_response,
                reconciliation,
            )
        except DeployError:
            if mutation_error is not None:
                raise mutation_error
            raise
        response_recovered = mutation_error is not None or any(
            response.get(key) != value
            for key, value in authoritative_response.items()
        )
        response = authoritative_response
    if (
        response.get("reconciled") is not True
        or response.get("phase") != "committed"
        or response.get("lock_retained") is not True
        or (
            proof_is_v2
            and response.get("receipt_schema") != expected_receipt_schema
        )
        or re.fullmatch(
            r"[a-f0-9]{64}", str(response.get("receipt_sha256", ""))
        )
        is None
    ):
        raise DeployError("Orphaned rollback terminal receipt was not confirmed")
    if committed is None:
        committed = (
            bridge_call(
                client,
                "status",
                token,
                deployment_id,
                projected_deployment_id=prior["deployment_id"],
            )
            if proof_is_v2
            else poll_deployment_status(client, token, deployment_id)
        )
    expected_database_fingerprint = (
        str(reconciliation["expected_reconciled_database_fingerprint"])
        if proof_is_v2
        else str(prior["database_fingerprint"])
    )
    expected_receipt = {
        "committed_outcome": "rolled_back",
        "committed_expected_active": bool(prior["active"]),
        "committed_expected_absent": False,
        "committed_expected_version": str(prior["version"]),
        "committed_expected_deployment": str(prior["deployment_id"]),
        "committed_expected_plugin_sha256": str(prior["plugin_sha256"]),
        "committed_expected_database_fingerprint": expected_database_fingerprint,
        "committed_expected_robots_exists": bool(prior["robots_exists"]),
        "committed_expected_robots_sha256": str(prior["robots_sha256"]),
        "committed_expected_sync_configured": bool(prior["sync_configured"]),
        "orphaned_recovery_proof_sha256": proof_sha256,
        "orphaned_recovery_receipt_sha256": str(response["receipt_sha256"]),
    }
    if (
        committed.get("phase") != "committed"
        or committed.get("state_exists") is not False
        or committed.get("lock_owned") is not True
    ):
        raise DeployError("Orphaned rollback terminal phase was not durable")
    for key, value in expected_receipt.items():
        if committed.get(key) != value:
            raise DeployError(f"Orphaned rollback receipt failed for {key}")
    evidence_directory_exists = response.get("evidence_directory_exists")
    evidence_directory_sha256 = str(
        response.get("evidence_directory_sha256", "")
    )
    marker_corrected = response.get("marker_corrected")
    if (
        type(evidence_directory_exists) is not bool
        or type(marker_corrected) is not bool
        or (
            evidence_directory_exists
            and re.fullmatch(r"[a-f0-9]{64}", evidence_directory_sha256) is None
        )
        or (not evidence_directory_exists and evidence_directory_sha256)
    ):
        raise DeployError("Orphaned rollback evidence receipt is invalid")
    result = {
        "evidence_directory_exists": evidence_directory_exists,
        "evidence_directory_sha256": evidence_directory_sha256,
        "lock_retained": True,
        "marker_corrected": marker_corrected,
        "phase": "committed",
        "proof_sha256": proof_sha256,
        "receipt_sha256": str(response["receipt_sha256"]),
    }
    if proof_is_v2:
        result.update(
            {
                "receipt_schema": expected_receipt_schema,
                "response_recovered": response_recovered,
            }
        )
        marker_rows_affected = response.get("marker_rows_affected")
        marker_transition = response.get("marker_transition")
        if (
            type(marker_rows_affected) is not int
            or marker_rows_affected not in {0, 1}
            or marker_transition
            not in {"corrected", "already-correct"}
            or (marker_rows_affected == 1) != (marker_transition == "corrected")
            or marker_corrected is not (marker_rows_affected == 1)
            or response.get("historical_baseline_database_fingerprint")
            != reconciliation["baseline_database_fingerprint"]
            or response.get("observed_database_fingerprint")
            != reconciliation["observed_database_fingerprint"]
            or response.get("reconciled_database_fingerprint")
            != reconciliation["expected_reconciled_database_fingerprint"]
            or response.get("preserved_manifest_sha256")
            != reconciliation["preserved_manifest_sha256"]
        ):
            raise DeployError(
                "Orphaned rollback v2 receipt did not preserve the reviewed state"
            )
        expected_v2_lock = {
            "expected_sha256": failed["artifact_sha256"],
            "expected_version": failed["candidate_version"],
            "installed_plugin_sha256": failed["candidate_plugin_sha256"],
            "post_install_database_fingerprint": failed[
                "candidate_database_fingerprint"
            ],
            "orphaned_reconciliation_mode": reconciliation["mode"],
            "orphaned_prior_proof_sha256": reconciliation[
                "prior_proof_sha256"
            ],
            "orphaned_attestation_run_id": reconciliation[
                "attestation_run_id"
            ],
            "orphaned_attestation_sha256": reconciliation[
                "attestation_sha256"
            ],
            "orphaned_attestation_audit_sha256": reconciliation[
                "attestation_audit_sha256"
            ],
            "orphaned_attestation_source_commit": reconciliation[
                "attestation_source_commit"
            ],
            "orphaned_recovery_receipt_schema": expected_receipt_schema,
            "orphaned_historical_baseline_database_fingerprint": reconciliation[
                "baseline_database_fingerprint"
            ],
            "orphaned_observed_database_fingerprint": reconciliation[
                "observed_database_fingerprint"
            ],
            "orphaned_preserved_manifest_sha256": reconciliation[
                "preserved_manifest_sha256"
            ],
            "orphaned_marker_rows_affected": marker_rows_affected,
            "orphaned_marker_transition": marker_transition,
        }
        for key, value in expected_v2_lock.items():
            if committed.get(key) != value:
                raise DeployError(f"Orphaned rollback v2 lock failed for {key}")
        if (
            committed.get("current_deployment") != prior["deployment_id"]
            or committed.get("database_fingerprint")
            != reconciliation["expected_reconciled_database_fingerprint"]
            or committed.get("database_manifest_sha256")
            != reconciliation["preserved_manifest_sha256"]
            or committed.get("orphaned_observed_deployment")
            != failed["deployment_id"]
            or committed.get("orphaned_reconciled_from") != "rolling_back"
        ):
            raise DeployError(
                "Orphaned rollback v2 committed state differs from the reviewed projection"
            )
        result.update(
            {
                "attestation_audit_sha256": reconciliation[
                    "attestation_audit_sha256"
                ],
                "attestation_run_id": reconciliation["attestation_run_id"],
                "attestation_sha256": reconciliation["attestation_sha256"],
                "attestation_source_commit": reconciliation[
                    "attestation_source_commit"
                ],
                "historical_baseline_database_fingerprint": reconciliation[
                    "baseline_database_fingerprint"
                ],
                "mode": reconciliation["mode"],
                "marker_rows_affected": marker_rows_affected,
                "marker_transition": marker_transition,
                "observed_database_fingerprint": reconciliation[
                    "observed_database_fingerprint"
                ],
                "preserved_manifest_sha256": reconciliation[
                    "preserved_manifest_sha256"
                ],
                "prior_proof_sha256": reconciliation["prior_proof_sha256"],
                "reconciled_database_fingerprint": reconciliation[
                    "expected_reconciled_database_fingerprint"
                ],
            }
        )
    return result


def expected_managed_robots(client: Client) -> bytes:
    return (
        "User-agent: *\n"
        "Disallow: /wp-admin/\n"
        "Allow: /wp-admin/admin-ajax.php\n"
        f"Sitemap: {client.base_url}/wp-sitemap.xml\n"
    ).encode("utf-8")


def verify_robots_journal_identity(
    record: dict[str, Any],
) -> tuple[bool, str]:
    prior_exists = bool(record.get("robots_prior_exists"))
    prior_sha256 = str(record.get("robots_prior_sha256", ""))
    if prior_exists and not re.fullmatch(r"[a-f0-9]{64}", prior_sha256):
        raise DeployError("Bridge did not expose a valid prior robots.txt fingerprint")
    if not prior_exists and prior_sha256:
        raise DeployError("Bridge exposed a fingerprint for an absent prior robots.txt")
    return prior_exists, prior_sha256


def verify_managed_robots(
    client: Client,
    expected_sha256: str,
) -> dict[str, Any]:
    if not re.fullmatch(r"[a-f0-9]{64}", expected_sha256):
        raise DeployError("Bridge did not expose a valid managed robots.txt fingerprint")
    status, raw = client.request_anonymous_bytes("/robots.txt", expected=(200,))
    expected = expected_managed_robots(client)
    actual_sha256 = hashlib.sha256(raw).hexdigest()
    expected_content_sha256 = hashlib.sha256(expected).hexdigest()
    if (
        raw != expected
        or actual_sha256 != expected_content_sha256
        or actual_sha256 != expected_sha256
    ):
        raise DeployError("Public robots.txt did not exactly match the managed crawler policy")
    return {
        "sha256": actual_sha256,
        "status": status,
    }


def verify_prior_robots(
    client: Client,
    prior_exists: bool,
    prior_sha256: str,
) -> dict[str, Any]:
    if prior_exists:
        if not re.fullmatch(r"[a-f0-9]{64}", prior_sha256):
            raise DeployError("Rollback did not expose a valid prior robots.txt fingerprint")
        status, raw = client.request_anonymous_bytes("/robots.txt", expected=(200,))
        actual_sha256 = hashlib.sha256(raw).hexdigest()
        if actual_sha256 != prior_sha256:
            raise DeployError("Public robots.txt did not match the exact pre-deployment content")
        return {
            "sha256": actual_sha256,
            "status": status,
        }
    if prior_sha256:
        raise DeployError("Rollback reported a fingerprint for an absent prior robots.txt")
    status, _ = client.request_anonymous_bytes("/robots.txt", expected=(404,))
    return {
        "sha256": "",
        "status": status,
    }


def robots_restore_audit(value: Any) -> dict[str, bool]:
    restore = value if isinstance(value, dict) else {}
    return {
        "already_restored": bool(restore.get("already_restored")),
        "not_managed": bool(restore.get("not_managed")),
        "response_recovered": bool(restore.get("response_recovered")),
        "restored": bool(restore.get("restored")),
    }


def verify_health(
    client: Client,
    version: str,
    deployment_id: str,
    *,
    require_sync_configured: bool = False,
) -> dict[str, Any]:
    query = urllib.parse.quote(deployment_id, safe="")
    _, health = client.request_public_json(
        f"/wp-json/complete99/v1/health?verify={query}"
    )
    expected = {
        "status": "ok",
        "component": SLUG,
        "version": version,
        "database_version": version,
        "deployment_id": deployment_id,
    }
    for key, value in expected.items():
        if health.get(key) != value:
            raise DeployError(f"Independent health verification failed for {key}")
    if require_sync_configured and health.get("sync_configured") is not True:
        raise DeployError("Independent health verification failed for sync configuration")
    result = {key: health.get(key) for key in expected}
    result["sync_configured"] = health.get("sync_configured") is True
    return result


def verify_rendered_home(
    client: Client,
    version: str,
    deployment_id: str,
    forbidden_deployment_id: str = "",
) -> dict[str, Any]:
    for value, label in ((deployment_id, "deployment"), (version, "version")):
        if not re.fullmatch(r"[A-Za-z0-9._+-]{3,96}", value):
            raise DeployError(f"Rendered-home {label} marker is invalid")
    _, html = client.request_anonymous_html("/")
    body_offset = html.lower().find("<body")
    if body_offset < 0:
        raise DeployError("Anonymous homepage did not contain a body element")
    body = html[body_offset:]
    version_marker = f'data-c99-version="{version}"'
    deployment_marker = f'data-c99-deployment="{deployment_id}"'
    if version_marker not in body or deployment_marker not in body:
        raise DeployError("Anonymous homepage body did not contain the new release markers")
    if forbidden_deployment_id and forbidden_deployment_id != deployment_id:
        old_marker = f'data-c99-deployment="{forbidden_deployment_id}"'
        if old_marker in body:
            raise DeployError("Anonymous homepage body still contains the prior deployment marker")
    return {
        "body_sha256": hashlib.sha256(body.encode("utf-8")).hexdigest(),
        "deployment_id": deployment_id,
        "exact_path": "/",
        "version": version,
    }


def verify_prior_health(client: Client, rollback: dict[str, Any]) -> dict[str, Any]:
    version = str(rollback.get("prior_version", ""))
    deployment_id = str(rollback.get("prior_deployment", ""))
    if not version or not deployment_id:
        raise DeployError("Rollback exercise requires an existing deployed Complete99 version")
    return verify_health(client, version, deployment_id)


def verify_inactive_plugin(client: Client, version: str) -> dict[str, Any]:
    _, plugin = client.request("GET", PLUGIN_REST_PATH)
    if plugin.get("status") != "inactive" or plugin.get("version") != version:
        raise DeployError("Rollback did not restore the expected inactive plugin")
    health_status, health = client.request_public_json(
        "/wp-json/complete99/v1/health",
        expected=(404,),
    )
    health_code = health.get("code", "") if isinstance(health, dict) else ""
    if health_status != 404 or health_code not in {"rest_no_route", ""}:
        raise DeployError("Inactive-plugin rollback did not prove the health route absent")
    return {"plugin_status": "inactive", "version": version, "health_route_404": True}


def verify_plugin_absent(client: Client) -> dict[str, Any]:
    status, plugin = client.request("GET", PLUGIN_REST_PATH, expected=(404,))
    plugin_code = plugin.get("code", "") if isinstance(plugin, dict) else ""
    if status != 404 or plugin_code not in {"rest_plugin_not_found", "rest_no_route", ""}:
        raise DeployError("First-install rollback did not prove the plugin absent")
    health_status, health = client.request_public_json(
        "/wp-json/complete99/v1/health",
        expected=(404,),
    )
    health_code = health.get("code", "") if isinstance(health, dict) else ""
    if health_status != 404 or health_code not in {"rest_no_route", ""}:
        raise DeployError("First-install rollback did not prove the health route absent")
    return {"plugin_absent": True, "health_route_404": True}


def validate_finalize_response(response: dict[str, Any]) -> None:
    """Require the exact boolean finalization receipt before trusting cleanup."""
    if (
        response.get("finalized") is not True
        or response.get("lock_released") is not True
        or response.get("state_removed") is not True
        or not isinstance(response.get("cache_purge", {}), dict)
    ):
        raise DeployError("Deployment backup finalization was not confirmed")


def finalize_deployment(client: Client, token: str, deployment_id: str) -> dict[str, Any]:
    recovered = False
    try:
        response = bridge_call(client, "finalize", token, deployment_id)
        validate_finalize_response(response)
    except DeployError as first_error:
        recovered = True
        try:
            response = bridge_call(client, "finalize", token, deployment_id)
            validate_finalize_response(response)
        except DeployError:
            status = poll_deployment_status(client, token, deployment_id)
            if (
                status.get("state_exists") is False
                and status.get("lock_owned") is False
            ):
                response = {
                    "cache_purge": {"response_recovered": True},
                    "finalized": True,
                    "lock_released": True,
                    "state_removed": True,
                }
            elif status.get("phase") in {
                "committed",
                "cleanup_failed",
            } or status.get("state_exists") is False:
                raise FinalizeCommittedError(
                    "Deployment committed but backup/lock cleanup remains unresolved"
                ) from first_error
            else:
                raise first_error
    validate_finalize_response(response)
    return {
        "cache_purge": response.get("cache_purge", {}),
        "finalized": True,
        "lock_released": True,
        "response_recovered": recovered,
        "state_removed": True,
    }


def can_finalize_unstarted_status(status: dict[str, Any]) -> bool:
    """Allow only exact state-free outcomes that finalization can close safely."""

    if not isinstance(status, dict) or status.get("state_exists") is not False:
        return False
    lock_owned = status.get("lock_owned")
    if lock_owned is False:
        return True
    if lock_owned is not True:
        return False
    phase = status.get("phase")
    if phase == "reserved":
        return True
    return phase == "locked" and status.get("recovery_ready") is True


def delete_snippet_and_prove_404(
    client: Client,
    snippet_id: int | None,
    token: str,
    deployment_id: str,
    creation_attempted: bool,
) -> dict[str, Any]:
    if not creation_attempted:
        return {"snippet_deleted": False, "snippet_active": False, "route_404": False}
    name = snippet_name(deployment_id)
    targets = set(find_active_snippet_ids(client, name))
    if snippet_id is not None:
        targets.add(snippet_id)
    retire_snippet_ids(client, token, deployment_id, targets)
    for target in sorted(targets):
        if get_snippet_by_id(client, target) is not None:
            raise DeployError("Temporary bridge row deletion could not be proven")
    remaining = find_active_snippet_ids(client, name)
    if remaining:
        remove_named_snippets(client, name, token, deployment_id)
    if find_active_snippet_ids(client, name):
        raise DeployError("Temporary bridge snippet deactivation could not be independently proven")
    status, response = client.request(
        "POST",
        f"/wp-json/complete99-deploy/v1/{urllib.parse.quote(deployment_id, safe='')}/preflight",
        {"token": token, "deployment_id": deployment_id},
        expected=(404,),
    )
    rest_code = response.get("code", "") if isinstance(response, dict) else ""
    if status != 404 or rest_code not in {"rest_no_route", ""}:
        raise DeployError("Temporary bridge cleanup could not be independently proven")
    return {
        "snippet_deleted": True,
        "snippet_active": False,
        "row_absence_verified": True,
        "removed_ids": sorted(targets),
        "route_404": True,
    }


def write_audit(directory: Path, audit: dict[str, Any]) -> Path:
    directory.mkdir(parents=True, exist_ok=True)
    path = directory / f"{audit['deployment_id']}.json"
    path.write_text(
        json.dumps(audit, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    return path


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", type=Path, default=ROOT / "plugin-dist")
    parser.add_argument("--base-url", default=os.environ.get("WP_BASE_URL", ""))
    parser.add_argument("--user", default=os.environ.get("WP_DEPLOY_USER", os.environ.get("WP_USER", "")))
    parser.add_argument(
        "--allowed-deploy-hosts",
        default=os.environ.get("WP_ALLOWED_DEPLOY_HOSTS", ""),
    )
    parser.add_argument("--deployment-id", default="")
    parser.add_argument("--bootstrap-code-snippets", action="store_true")
    parser.add_argument(
        "--local-test",
        action="store_true",
        help="Allow an isolated loopback WordPress target; never allows a remote or UPress staging host.",
    )
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--rollback-exercise", action="store_true")
    parser.add_argument(
        "--mutation-marker",
        type=Path,
        default=None,
        help=argparse.SUPPRESS,
    )
    parser.add_argument(
        "--fault-injection",
        choices=("", "db_capture", "after_prepare", "after_install", "during_rollback", "after_commit"),
        default="",
        help=argparse.SUPPRESS,
    )
    parser.add_argument("--audit-dir", type=Path, default=ROOT / "deploy-audit")
    args = parser.parse_args()
    app_password = os.environ.get("WP_APP_PASSWORD", "")
    sync_value = os.environ.get("COMPLETE99_WORDPRESS_SYNC_SECRET", "")

    if not args.base_url or not args.user or not app_password:
        raise DeployError("WP_BASE_URL, WP_DEPLOY_USER and WP_APP_PASSWORD are required")
    if sync_value and (len(sync_value) < 32 or len(sync_value) > 4096):
        raise DeployError(
            "COMPLETE99_WORDPRESS_SYNC_SECRET must contain between 32 and 4096 characters"
        )
    if args.fault_injection and not args.local_test:
        raise DeployError("Fault injection is restricted to isolated loopback tests")
    target = validate_target_url(
        args.base_url,
        args.local_test,
        args.allowed_deploy_hosts,
    )
    target_host = (target.hostname or "").lower()
    allowed_hosts = (
        {target_host}
        if args.local_test
        else ALLOWED_PRODUCTION_HOSTS
        | parse_allowed_deploy_hosts(args.allowed_deploy_hosts)
    )
    metadata, artifact, raw = load_artifact(args.dist.resolve())
    deployment_id = args.deployment_id or f"c99-{metadata['version']}-{int(time.time())}-{secrets.token_hex(4)}"
    token = secrets.token_urlsafe(36)
    max_bytes = package_upload_ceiling(len(raw))
    client = Client(
        args.base_url,
        args.user,
        app_password,
        allow_local_http=args.local_test,
        allowed_deploy_hosts=args.allowed_deploy_hosts,
    )
    snippet_id: int | None = None
    snippet_creation_attempted = False
    reservation_acquired = False
    deployed = False
    mutation_pending = False
    finalized = False
    completed_rollback: dict[str, Any] | None = None
    audit: dict[str, Any] = {
        "artifact": artifact.name,
        "commit": os.environ.get("GITHUB_SHA", "").strip(),
        "deployment_id": deployment_id,
        "dry_run": args.dry_run,
        "local_test": args.local_test,
        "result": "started",
        "rollback_exercise": args.rollback_exercise,
        "sha256": metadata["sha256"],
        "slug": SLUG,
        "source_sha256": metadata.get("source_sha256", ""),
        "started_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "version": metadata["version"],
    }

    primary_error: Exception | None = None
    gate = "auth"
    try:
        audit["identity"] = authenticate(client)
        gate = "bootstrap"
        ensure_code_snippets(client, args.bootstrap_code_snippets)
        gate = "create"
        code = render_bridge(
            token,
            deployment_id,
            max_bytes,
            args.local_test,
            args.fault_injection,
            target_host=target_host,
            allowed_hosts=allowed_hosts,
            expected_artifact_sha256=str(metadata["sha256"]),
            expected_artifact_size=len(raw),
            expected_plugin_sha256=str(metadata["installed_sha256"]),
            expected_version=str(metadata["version"]),
        )
        arm_live_mutation_recovery(args.mutation_marker, deployment_id)
        snippet_creation_attempted = True
        snippet_id = create_snippet(client, code, deployment_id)
        gate = "bootstrap-cleanup"
        audit["bootstrap_cleanup"] = remove_bootstrap_snippet(
            client,
            token,
            deployment_id,
        )
        gate = "preflight"
        preflight = preflight_with_recovery(client, token, deployment_id)
        reservation_acquired = bool(preflight.get("lock_reserved"))
        if not preflight.get("ready") or preflight.get("allowed_slug") != SLUG:
            raise DeployError("Temporary bridge preflight did not pass")
        audit["bridge_site_identity"] = verify_bridge_site_identity(
            preflight,
            target_host,
        )
        audit["stale_bridges_recovered"] = remove_prefixed_snippets(
            client,
            SNIPPET_PREFIX,
            token,
            deployment_id,
            exclude_ids={snippet_id},
        )
        audit["preflight"] = {
            "current_version": preflight.get("current_version", ""),
            "current_active": bool(preflight.get("current_active")),
            "current_deployment": preflight.get("current_deployment", ""),
            "had_plugin": bool(preflight.get("had_plugin")),
            "target_dir_exists": bool(preflight.get("target_dir_exists")),
            "plugin_main_exists": bool(preflight.get("plugin_main_exists")),
            "auto_update_enabled": bool(preflight.get("auto_update_enabled")),
            "direct_filesystem": bool(preflight.get("direct_filesystem")),
            "free_bytes": preflight.get("free_bytes"),
            "required_free_bytes": preflight.get("required_free_bytes"),
            "transactional_storage": preflight.get("transactional_storage", {}),
            "database_fingerprint": preflight.get("database_fingerprint", ""),
            "robots_prior_exists": bool(preflight.get("robots_prior_exists")),
            "robots_prior_sha256": preflight.get("robots_prior_sha256", ""),
            "lock_reserved": reservation_acquired,
        }
        prior_version = str(audit["preflight"]["current_version"])
        prior_deployment = str(audit["preflight"]["current_deployment"])
        prior_active = bool(audit["preflight"]["current_active"])
        if audit["preflight"]["auto_update_enabled"]:
            raise DeployError("Target plugin automatic updates must be disabled")
        if prior_active:
            if not prior_version or not prior_deployment:
                raise DeployError("Active prior plugin did not expose a complete rollback identity")
            audit["prior_health"] = verify_health(client, prior_version, prior_deployment)
            audit["prior_rendered_home"] = verify_rendered_home(
                client,
                prior_version,
                prior_deployment,
            )
        elif prior_version:
            audit["prior_inactive_plugin"] = verify_inactive_plugin(client, prior_version)
        if args.rollback_exercise and not prior_active:
            raise DeployError("Rollback exercise requires an existing active healthy Complete99 release")
        if args.dry_run:
            gate = "finalize"
            audit["dry_run_finalize"] = finalize_deployment(client, token, deployment_id)
            finalized = True
            reservation_acquired = False
            audit["result"] = "dry-run-passed"
        else:
            gate = "stage-artifact"
            audit["artifact_staging"] = stage_artifact(
                client,
                token,
                deployment_id,
                raw,
                str(metadata["sha256"]),
                len(raw),
            )
            gate = "install"
            run_fields = {
                "slug": SLUG,
                "type": "plugin",
                "version": metadata["version"],
                "expected_sha256": metadata["sha256"],
                "staged": True,
                "activate": True,
            }
            deployed = True
            mutation_pending = True
            result = install_with_recovery(
                client,
                token,
                deployment_id,
                run_fields,
                str(metadata["installed_sha256"]),
            )
            if (
                result.get("sha256") != metadata["sha256"]
                or result.get("version") != metadata["version"]
                or result.get("installed_plugin_sha256")
                != metadata["installed_sha256"]
                or not re.fullmatch(
                    r"[a-f0-9]{64}",
                    str(result.get("robots_sha256", "")),
                )
                or not result.get("temp_removed")
            ):
                raise DeployError("Bridge install response failed integrity verification")
            robots_prior_exists, robots_prior_sha256 = (
                verify_robots_journal_identity(result)
            )
            audit["install"] = {
                "baseline_database_fingerprint": result.get(
                    "baseline_database_fingerprint", ""
                ),
                "cache_purge": result.get("cache_purge", {}),
                "candidate_activation": result.get("candidate_activation", {}),
                "had_plugin": bool(result.get("had_plugin")),
                "prior_active": bool(result.get("prior_active")),
                "prior_deployment": result.get("prior_deployment", ""),
                "prior_plugin_sha256": result.get("prior_plugin_sha256", ""),
                "prior_version": result.get("prior_version", ""),
                "robots_prior_exists": robots_prior_exists,
                "robots_prior_sha256": robots_prior_sha256,
                "robots_sha256": result.get("robots_sha256", ""),
                "installed_plugin_sha256": result.get(
                    "installed_plugin_sha256", ""
                ),
                "temp_removed": True,
            }
            gate = "stabilize"
            audit["stabilize"] = stabilize_deployment(
                client,
                token,
                deployment_id,
                metadata["version"],
                str(result.get("installed_plugin_sha256", "")),
            )
            gate = "robots"
            audit["robots"] = verify_managed_robots(
                client,
                str(result.get("robots_sha256", "")),
            )
            if sync_value and not args.rollback_exercise:
                gate = "configure-sync"
                audit["sync_configuration"] = configure_sync(
                    client,
                    token,
                    deployment_id,
                    sync_value,
                )
            gate = "health"
            audit["health"] = verify_health(
                client,
                metadata["version"],
                deployment_id,
                require_sync_configured=bool(sync_value)
                and not args.rollback_exercise,
            )
            audit["rendered_home"] = verify_rendered_home(
                client,
                metadata["version"],
                deployment_id,
                prior_deployment if prior_active else "",
            )

            if args.rollback_exercise:
                gate = "rollback"
                rollback = rollback_with_recovery(client, token, deployment_id)
                if not rollback.get("rolled_back") or not rollback.get("database_restore"):
                    raise DeployError("Rollback exercise was not confirmed")
                completed_rollback = rollback
                (
                    rollback_robots_prior_exists,
                    rollback_robots_prior_sha256,
                ) = verify_robots_journal_identity(rollback)
                if (
                    rollback_robots_prior_exists != robots_prior_exists
                    or rollback_robots_prior_sha256 != robots_prior_sha256
                ):
                    raise DeployError(
                        "Rollback robots.txt journal identity changed after installation"
                    )
                audit["rollback"] = {
                    "rolled_back": bool(rollback.get("rolled_back")),
                    "prior_version": rollback.get("prior_version", ""),
                    "prior_deployment": rollback.get("prior_deployment", ""),
                    "database_restore": rollback.get("database_restore", {}),
                    "robots_prior_exists": rollback_robots_prior_exists,
                    "robots_prior_sha256": rollback_robots_prior_sha256,
                    "robots_restore": robots_restore_audit(
                        rollback.get("robots_restore")
                    ),
                }
                gate = "rollback-integrity"
                audit["rollback_integrity"] = verify_rollback_integrity(
                    client, token, deployment_id, rollback
                )
                gate = "rollback-robots"
                audit["rollback_robots"] = verify_prior_robots(
                    client,
                    rollback_robots_prior_exists,
                    rollback_robots_prior_sha256,
                )
                gate = "rollback-health"
                audit["rollback_health"] = verify_prior_health(client, rollback)
                gate = "rollback-rendered-home"
                audit["rollback_rendered_home"] = verify_rendered_home(
                    client,
                    str(rollback.get("prior_version", "")),
                    str(rollback.get("prior_deployment", "")),
                    deployment_id,
                )
                gate = "rollback-finalize"
                audit["rollback_finalize"] = finalize_deployment(client, token, deployment_id)
                finalized = True
                reservation_acquired = False
                mutation_pending = False
                completed_rollback = None
                gate = "install"
                finalized = False
                reservation = preflight_with_recovery(client, token, deployment_id)
                reservation_acquired = bool(reservation.get("lock_reserved"))
                if not reservation.get("ready") or not reservation_acquired:
                    raise DeployError("Post-rollback deployment reservation did not pass")
                audit["bridge_site_identity_after_exercise"] = verify_bridge_site_identity(
                    reservation,
                    target_host,
                )
                gate = "stage-artifact-after-exercise"
                audit["artifact_staging_after_exercise"] = stage_artifact(
                    client,
                    token,
                    deployment_id,
                    raw,
                    str(metadata["sha256"]),
                    len(raw),
                )
                gate = "install"
                mutation_pending = True
                result = install_with_recovery(
                    client,
                    token,
                    deployment_id,
                    run_fields,
                    str(metadata["installed_sha256"]),
                )
                if (
                    result.get("sha256") != metadata["sha256"]
                    or result.get("version") != metadata["version"]
                    or result.get("installed_plugin_sha256")
                    != metadata["installed_sha256"]
                    or not re.fullmatch(
                        r"[a-f0-9]{64}",
                        str(result.get("robots_sha256", "")),
                    )
                    or not result.get("temp_removed")
                ):
                    raise DeployError("Post-rollback redeploy digest verification failed")
                (
                    redeploy_robots_prior_exists,
                    redeploy_robots_prior_sha256,
                ) = verify_robots_journal_identity(result)
                if (
                    redeploy_robots_prior_exists != rollback_robots_prior_exists
                    or redeploy_robots_prior_sha256 != rollback_robots_prior_sha256
                ):
                    raise DeployError(
                        "Redeploy did not capture the exact rolled-back robots.txt state"
                    )
                audit["install_after_exercise"] = {
                    "baseline_database_fingerprint": result.get(
                        "baseline_database_fingerprint", ""
                    ),
                    "cache_purge": result.get("cache_purge", {}),
                    "had_plugin": bool(result.get("had_plugin")),
                    "prior_active": bool(result.get("prior_active")),
                    "prior_deployment": result.get("prior_deployment", ""),
                    "prior_plugin_sha256": result.get("prior_plugin_sha256", ""),
                    "prior_version": result.get("prior_version", ""),
                    "robots_prior_exists": redeploy_robots_prior_exists,
                    "robots_prior_sha256": redeploy_robots_prior_sha256,
                    "robots_sha256": result.get("robots_sha256", ""),
                    "installed_plugin_sha256": result.get(
                        "installed_plugin_sha256", ""
                    ),
                    "temp_removed": True,
                }
                gate = "stabilize"
                audit["stabilize_after_exercise"] = stabilize_deployment(
                    client,
                    token,
                    deployment_id,
                    metadata["version"],
                    str(result.get("installed_plugin_sha256", "")),
                )
                gate = "robots"
                audit["robots_after_exercise"] = verify_managed_robots(
                    client,
                    str(result.get("robots_sha256", "")),
                )
                if sync_value:
                    gate = "configure-sync"
                    audit["sync_configuration_after_exercise"] = configure_sync(
                        client,
                        token,
                        deployment_id,
                        sync_value,
                    )
                gate = "health"
                audit["health_after_exercise"] = verify_health(
                    client,
                    metadata["version"],
                    deployment_id,
                    require_sync_configured=bool(sync_value),
                )
                audit["rendered_home_after_exercise"] = verify_rendered_home(
                    client,
                    metadata["version"],
                    deployment_id,
                    prior_deployment,
                )

            gate = "finalize"
            audit["finalize"] = finalize_deployment(client, token, deployment_id)
            finalized = True
            reservation_acquired = False
            mutation_pending = False
            audit["result"] = "deployed"
    except Exception as error:  # cleanup/rollback handled below; message contains no credentials.
        primary_error = error
        audit["result"] = "failed"
        audit["error"] = type(error).__name__
        audit["failed_gate"] = gate
        if isinstance(error, HTTPDeployError) and error.data:
            audit["failure_context"] = error.data
        if reservation_acquired and not mutation_pending and not finalized:
            try:
                gate = "finalize"
                audit["reservation_finalize"] = finalize_deployment(client, token, deployment_id)
                finalized = True
                reservation_acquired = False
                mutation_pending = False
            except Exception as reservation_error:
                audit["reservation_finalize"] = {
                    "finalized": False,
                    "error": type(reservation_error).__name__,
                }
        if isinstance(primary_error, FinalizeCommittedError):
            audit["commit_cleanup_unresolved"] = {
                "rollback_refused": True,
                "reason": "committed_cleanup_requires_idempotent_finalize",
            }
        elif (
            mutation_pending
            and not finalized
            and completed_rollback is not None
        ):
            # The rollback mutation already returned a confirmed restore. If a
            # later readback times out, verify and finalize that same rollback;
            # never issue a second rollback against an already-restored state.
            try:
                audit["failure_rollback_integrity"] = verify_rollback_integrity(
                    client,
                    token,
                    deployment_id,
                    completed_rollback,
                )
                (
                    completed_robots_prior_exists,
                    completed_robots_prior_sha256,
                ) = verify_robots_journal_identity(completed_rollback)
                audit["failure_rollback_robots"] = verify_prior_robots(
                    client,
                    completed_robots_prior_exists,
                    completed_robots_prior_sha256,
                )
                if completed_rollback.get("prior_active"):
                    audit["failure_rollback_health"] = verify_health(
                        client,
                        str(completed_rollback.get("prior_version", "")),
                        str(completed_rollback.get("prior_deployment", "")),
                    )
                    audit["failure_rollback_rendered_home"] = verify_rendered_home(
                        client,
                        str(completed_rollback.get("prior_version", "")),
                        str(completed_rollback.get("prior_deployment", "")),
                        deployment_id,
                    )
                elif completed_rollback.get("had_plugin"):
                    audit["failure_rollback_inactive_plugin"] = verify_inactive_plugin(
                        client,
                        str(completed_rollback.get("prior_version", "")),
                    )
                else:
                    audit["failure_rollback_absence"] = verify_plugin_absent(client)
                audit["failure_finalize"] = finalize_deployment(
                    client,
                    token,
                    deployment_id,
                )
                finalized = True
                reservation_acquired = False
                mutation_pending = False
                audit["completed_rollback_recovery"] = {
                    "already_completed": True,
                    "finalized": True,
                    "second_rollback_refused": True,
                }
            except Exception as rollback_verification_error:
                audit["completed_rollback_recovery"] = {
                    "already_completed": True,
                    "error": type(rollback_verification_error).__name__,
                    "finalized": False,
                    "second_rollback_refused": True,
                }
        elif mutation_pending and not finalized:
            unstarted_recovered = False
            try:
                recovery_status = bridge_call(client, "status", token, deployment_id)
                if can_finalize_unstarted_status(recovery_status):
                    audit["unstarted_recovery_finalize"] = finalize_deployment(
                        client, token, deployment_id
                    )
                    finalized = True
                    reservation_acquired = False
                    mutation_pending = False
                    unstarted_recovered = True
            except Exception as recovery_probe_error:
                audit["unstarted_recovery_probe"] = {
                    "recovered": False,
                    "error": type(recovery_probe_error).__name__,
                }
            if unstarted_recovered:
                audit["unstarted_recovery"] = {
                    "mutation_detected": False,
                    "lock_released": True,
                }
            else:
                rollback_completed = False
                try:
                    gate = "rollback"
                    rollback = rollback_with_recovery(client, token, deployment_id)
                    if not rollback.get("rolled_back") or not rollback.get("database_restore"):
                        raise DeployError("Failure rollback was not confirmed")
                    (
                        failure_robots_prior_exists,
                        failure_robots_prior_sha256,
                    ) = verify_robots_journal_identity(rollback)
                    audit["failure_rollback"] = {
                        "rolled_back": bool(rollback.get("rolled_back")),
                        "had_plugin": bool(rollback.get("had_plugin")),
                        "prior_version": rollback.get("prior_version", ""),
                        "prior_active": bool(rollback.get("prior_active")),
                        "prior_deployment": rollback.get("prior_deployment", ""),
                        "prior_plugin_sha256": rollback.get("prior_plugin_sha256", ""),
                        "baseline_database_fingerprint": rollback.get(
                            "baseline_database_fingerprint", ""
                        ),
                        "database_restore": rollback.get("database_restore", {}),
                        "robots_prior_exists": failure_robots_prior_exists,
                        "robots_prior_sha256": failure_robots_prior_sha256,
                        "robots_restore": robots_restore_audit(
                            rollback.get("robots_restore")
                        ),
                    }
                    audit["failure_rollback_integrity"] = verify_rollback_integrity(
                        client, token, deployment_id, rollback
                    )
                    audit["failure_rollback_robots"] = verify_prior_robots(
                        client,
                        failure_robots_prior_exists,
                        failure_robots_prior_sha256,
                    )
                    if rollback.get("prior_active"):
                        audit["failure_rollback_health"] = verify_health(
                            client,
                            str(rollback.get("prior_version", "")),
                            str(rollback.get("prior_deployment", "")),
                        )
                        audit["failure_rollback_rendered_home"] = verify_rendered_home(
                            client,
                            str(rollback.get("prior_version", "")),
                            str(rollback.get("prior_deployment", "")),
                            deployment_id,
                        )
                    elif rollback.get("had_plugin"):
                        audit["failure_rollback_inactive_plugin"] = verify_inactive_plugin(
                            client,
                            str(rollback.get("prior_version", "")),
                        )
                    else:
                        audit["failure_rollback_absence"] = verify_plugin_absent(client)
                    rollback_completed = True
                except Exception as rollback_error:
                    audit["failure_rollback"] = {
                        "rolled_back": False,
                        "error": type(rollback_error).__name__,
                    }
                    if not audit.get("preflight", {}).get("current_version"):
                        try:
                            audit["failure_rollback_absence"] = verify_plugin_absent(client)
                        except Exception as absence_error:
                            audit["failure_rollback_absence"] = {
                                "plugin_absent": False,
                                "error": type(absence_error).__name__,
                            }
                if rollback_completed:
                    try:
                        gate = "finalize"
                        audit["failure_finalize"] = finalize_deployment(client, token, deployment_id)
                        finalized = True
                        reservation_acquired = False
                        mutation_pending = False
                        audit["failure_rollback"]["finalized"] = True
                    except Exception as finalize_error:
                        audit["failure_finalize"] = {
                            "finalized": False,
                            "error": type(finalize_error).__name__,
                        }
    finally:
        try:
            gate = "cleanup"
            audit["cleanup"] = delete_snippet_and_prove_404(
                client,
                snippet_id,
                token,
                deployment_id,
                snippet_creation_attempted,
            )
        except Exception as cleanup_error:
            audit["cleanup"] = {"snippet_deleted": False, "route_404": False, "error": type(cleanup_error).__name__}
            if primary_error is None:
                primary_error = cleanup_error
                audit["result"] = "failed"
                audit["failed_gate"] = "cleanup"
        audit["finished_at"] = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
        audit_path = write_audit(args.audit_dir.resolve(), audit)

    print(json.dumps({"audit": str(audit_path), "deployment_id": deployment_id, "result": audit["result"]}))
    if primary_error:
        raise primary_error
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except DeployError as error:
        print(f"DEPLOY FAILED: {error}", file=sys.stderr)
        raise SystemExit(1)
