#!/usr/bin/env python3
"""Transactional Complete99 plugin deployer using a temporary authenticated bridge."""

from __future__ import annotations

import argparse
import base64
import hashlib
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
from dataclasses import dataclass
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parents[1]
SLUG = "complete99-platform"
BRIDGE_TEMPLATE = ROOT / "deploy" / "temporary-bridge.php"
USER_AGENT = "Complete99WordPressDeploy/1.0"
ALLOWED_PRODUCTION_HOSTS = {"complete99.co.il", "www.complete99.co.il"}
ALLOWED_LOCAL_TEST_HOSTS = {"127.0.0.1", "localhost", "::1"}
PLUGIN_REST_PATH = "/wp-json/wp/v2/plugins/complete99-platform/complete99-platform?context=edit"
SNIPPET_PREFIX = "tmp-complete99-deploy-"


class DeployError(RuntimeError):
    pass


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


def validate_target_url(base_url: str, local_test: bool) -> urllib.parse.ParseResult:
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
        or hostname not in ALLOWED_PRODUCTION_HOSTS
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
    timeout: int = 180

    def __post_init__(self) -> None:
        validate_target_url(self.base_url, self.allow_local_http)
        self.base_url = self.base_url.rstrip("/")
        credential = f"{self.username}:{self.app_password}".encode()
        self.authorization = "Basic " + base64.b64encode(credential).decode("ascii")
        self.ssl_context = ssl.create_default_context()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPSHandler(context=self.ssl_context),
            RejectRedirects(),
        )

    def request(
        self,
        method: str,
        path: str,
        payload: dict[str, Any] | None = None,
        expected: tuple[int, ...] = (200, 201),
    ) -> tuple[int, Any]:
        if not path.startswith("/") or path.startswith("//"):
            raise DeployError("Deployment request path must be site-relative")
        url = self.base_url + path
        body = None
        headers = {
            "Accept": "application/json",
            "Authorization": self.authorization,
            "User-Agent": USER_AGENT,
        }
        if payload is not None:
            body = json.dumps(payload, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
            headers["Content-Type"] = "application/json"
        request = urllib.request.Request(url, data=body, headers=headers, method=method)
        try:
            with self.opener.open(request, timeout=self.timeout) as response:
                if response.geturl() != url:
                    raise DeployError("Deployment requests may not follow redirects")
                status = response.status
                raw = response.read()
        except urllib.error.HTTPError as error:
            status = error.code
            raw = error.read()
        except (urllib.error.URLError, TimeoutError, OSError) as error:
            reason = getattr(error, "reason", type(error).__name__)
            raise DeployError(f"Network request failed: {reason}") from error
        parsed: Any
        try:
            parsed = json.loads(raw.decode("utf-8")) if raw else {}
        except (UnicodeDecodeError, json.JSONDecodeError):
            parsed = {"non_json_response": True, "length": len(raw)}
        if status not in expected:
            code = parsed.get("code", "http_error") if isinstance(parsed, dict) else "http_error"
            message = parsed.get("message", "") if isinstance(parsed, dict) else ""
            raise DeployError(f"{method} {path} failed with HTTP {status} ({code}): {message}")
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
        try:
            with self.opener.open(request, timeout=self.timeout) as response:
                if response.geturl() != url:
                    raise DeployError("Anonymous render verification may not follow redirects")
                status = response.status
                raw = response.read(5 * 1024 * 1024 + 1)
        except urllib.error.HTTPError as error:
            status = error.code
            raw = error.read(5 * 1024 * 1024 + 1)
        except (urllib.error.URLError, TimeoutError, OSError) as error:
            reason = getattr(error, "reason", type(error).__name__)
            raise DeployError(f"Anonymous render verification failed: {reason}") from error
        if len(raw) > 5 * 1024 * 1024:
            raise DeployError("Anonymous homepage exceeded the verification size ceiling")
        if status not in expected:
            raise DeployError(f"Anonymous GET {path} failed with HTTP {status}")
        return status, raw.decode("utf-8", errors="replace")

    def request_public_json(
        self,
        path: str,
        expected: tuple[int, ...] = (200,),
    ) -> tuple[int, Any]:
        if not path.startswith("/") or path.startswith("//") or "#" in path:
            raise DeployError("Public verification path must be site-relative")
        url = self.base_url + path
        request = urllib.request.Request(
            url,
            headers={
                "Accept": "application/json",
                "User-Agent": USER_AGENT,
            },
            method="GET",
        )
        try:
            with self.opener.open(request, timeout=self.timeout) as response:
                if response.geturl() != url:
                    raise DeployError("Public verification may not follow redirects")
                status = response.status
                raw = response.read(1024 * 1024 + 1)
        except urllib.error.HTTPError as error:
            status = error.code
            raw = error.read(1024 * 1024 + 1)
        except (urllib.error.URLError, TimeoutError, OSError) as error:
            reason = getattr(error, "reason", type(error).__name__)
            raise DeployError(f"Public verification failed: {reason}") from error
        if len(raw) > 1024 * 1024:
            raise DeployError("Public JSON verification exceeded the size ceiling")
        try:
            parsed: Any = json.loads(raw.decode("utf-8")) if raw else {}
        except (UnicodeDecodeError, json.JSONDecodeError):
            parsed = {"non_json_response": True, "length": len(raw)}
        if status not in expected:
            code = parsed.get("code", "http_error") if isinstance(parsed, dict) else "http_error"
            raise DeployError(f"Public GET {path} failed with HTTP {status} ({code})")
        return status, parsed


def load_artifact(dist: Path) -> tuple[dict[str, Any], Path, bytes]:
    metadata = json.loads((dist / f"{SLUG}-integrity.json").read_text(encoding="utf-8"))
    if metadata.get("slug") != SLUG or metadata.get("type") != "plugin":
        raise DeployError("Package metadata is not allowlisted")
    artifact = dist / str(metadata["artifact"])
    raw = artifact.read_bytes()
    digest = hashlib.sha256(raw).hexdigest()
    if digest != metadata.get("sha256") or len(raw) != metadata.get("size"):
        raise DeployError("Local artifact integrity check failed")
    return metadata, artifact, raw


def authenticate(client: Client) -> dict[str, Any]:
    _, user = client.request("GET", "/wp-json/wp/v2/users/me?context=edit&_fields=id,roles,capabilities")
    roles = user.get("roles", []) if isinstance(user, dict) else []
    capabilities = user.get("capabilities", {}) if isinstance(user, dict) else {}
    if "administrator" not in roles and not capabilities.get("update_plugins"):
        raise DeployError("The deployment identity lacks the update_plugins capability")
    return {"id": user.get("id"), "roles": roles}


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
    cache_buster = secrets.token_hex(8)
    _, response = client.request(
        "GET",
        f"/wp-json/code-snippets/v1/snippets?c99cb={cache_buster}",
    )
    items: Any = response
    if isinstance(response, dict) and isinstance(response.get("data"), list):
        items = response["data"]
    if not isinstance(items, list):
        raise DeployError("Code Snippets list returned an invalid response")
    active: list[dict[str, Any]] = []
    for item in items:
        if not isinstance(item, dict) or not bool(item.get("active")):
            continue
        value = item.get("id")
        if isinstance(value, int) or str(value).isdigit():
            active.append({"id": int(value), "name": str(item.get("name", ""))})
    return active


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


def deactivate_and_delete_snippet(client: Client, snippet_id: int) -> None:
    try:
        client.request(
            "POST",
            f"/wp-json/code-snippets/v1/snippets/{snippet_id}/deactivate",
            expected=(200, 404),
        )
    except DeployError:
        # DELETE plus the independent collection/route checks below are authoritative.
        pass
    try:
        client.request(
            "DELETE",
            f"/wp-json/code-snippets/v1/snippets/{snippet_id}",
            expected=(200, 204, 404),
        )
    except DeployError:
        # Some managed proxies return a misleading response after committing a write.
        pass


def remove_named_snippets(client: Client, name: str) -> list[int]:
    removed: list[int] = []
    for _ in range(2):
        matches = find_active_snippet_ids(client, name)
        if not matches:
            return removed
        for snippet_id in matches:
            deactivate_and_delete_snippet(client, snippet_id)
            removed.append(snippet_id)
    remaining = find_active_snippet_ids(client, name)
    if remaining:
        raise DeployError("Temporary Code Snippets bridge remains active after cleanup")
    return removed


def remove_prefixed_snippets(
    client: Client,
    prefix: str,
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
        for snippet_id in matches:
            deactivate_and_delete_snippet(client, snippet_id)
            removed.append(snippet_id)
    remaining = [
        snippet_id
        for snippet_id in find_active_snippet_ids_by_prefix(client, prefix)
        if snippet_id not in excluded
    ]
    if remaining:
        raise DeployError("A stale Complete99 deployment bridge remains active")
    return sorted(set(removed))


def create_snippet(client: Client, code: str, deployment_id: str) -> int:
    name = snippet_name(deployment_id)
    remove_named_snippets(client, name)
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
    if isinstance(snippet_id, int) or str(snippet_id).isdigit():
        recovered = int(snippet_id)
        if recovered in matches and len(matches) == 1:
            return recovered
    if len(matches) == 1:
        return matches[0]
    if matches:
        remove_named_snippets(client, name)
        raise DeployError("Code Snippets create produced ambiguous duplicate bridges")
    if create_error is not None:
        raise create_error
    raise DeployError("Code Snippets did not persist a recoverable snippet ID")


def bridge_call(client: Client, action: str, token: str, deployment_id: str, **fields: Any) -> dict[str, Any]:
    payload = {"token": token, "deployment_id": deployment_id}
    payload.update(fields)
    route_id = urllib.parse.quote(deployment_id, safe="")
    _, response = client.request(
        "POST",
        f"/wp-json/complete99-deploy/v1/{route_id}/{action}",
        payload,
    )
    if not isinstance(response, dict):
        raise DeployError(f"Bridge action {action} returned an invalid response")
    return response


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
        "installed",
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
) -> dict[str, Any]:
    try:
        return bridge_call(client, "run", token, deployment_id, **run_fields)
    except DeployError as original_error:
        status = poll_deployment_status(client, token, deployment_id)
        if (
            status.get("phase") == "installed"
            and status.get("expected_sha256") == run_fields["expected_sha256"]
            and status.get("current_version") == run_fields["version"]
            and status.get("current_deployment") == deployment_id
            and status.get("current_active")
            and status.get("temp_removed")
        ):
            return {
                "active": True,
                "baseline_database_fingerprint": status.get(
                    "baseline_database_fingerprint", ""
                ),
                "cache_purge": {"response_recovered": True},
                "deployment_id": deployment_id,
                "had_plugin": bool(status.get("had_plugin")),
                "prior_active": bool(status.get("prior_active")),
                "prior_deployment": status.get("prior_deployment", ""),
                "prior_plugin_sha256": status.get("prior_plugin_sha256", ""),
                "prior_version": status.get("prior_version", ""),
                "sha256": run_fields["expected_sha256"],
                "temp_removed": True,
                "version": run_fields["version"],
                "write_response_recovered": True,
            }
        raise original_error


def rollback_with_recovery(
    client: Client,
    token: str,
    deployment_id: str,
) -> dict[str, Any]:
    try:
        return bridge_call(client, "rollback", token, deployment_id)
    except DeployError as original_error:
        status = poll_deployment_status(client, token, deployment_id)
        if status.get("phase") in {
            "installed",
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
    status = bridge_call(client, "status", token, deployment_id)
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
    return {
        "database_fingerprint": actual,
        "database_restored": bool(status.get("database_restored")),
        "plugin_files_restored": had_plugin,
        "plugin_absent": not had_plugin,
    }


def verify_health(client: Client, version: str, deployment_id: str) -> dict[str, Any]:
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
    return {key: health.get(key) for key in expected}


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


def finalize_deployment(client: Client, token: str, deployment_id: str) -> dict[str, Any]:
    recovered = False
    try:
        response = bridge_call(client, "finalize", token, deployment_id)
    except DeployError as first_error:
        recovered = True
        try:
            response = bridge_call(client, "finalize", token, deployment_id)
        except DeployError:
            status = poll_deployment_status(client, token, deployment_id)
            if not status.get("state_exists") and not status.get("lock_owned"):
                response = {
                    "cache_purge": {"response_recovered": True},
                    "finalized": True,
                    "lock_released": True,
                    "state_removed": True,
                }
            elif status.get("phase") in {"committed", "cleanup_failed"} or not status.get(
                "state_exists"
            ):
                raise FinalizeCommittedError(
                    "Deployment committed but backup/lock cleanup remains unresolved"
                ) from first_error
            else:
                raise first_error
    if (
        not response.get("finalized")
        or not response.get("lock_released")
        or not response.get("state_removed")
    ):
        raise DeployError("Deployment backup finalization was not confirmed")
    return {
        "cache_purge": response.get("cache_purge", {}),
        "finalized": True,
        "lock_released": True,
        "response_recovered": recovered,
        "state_removed": True,
    }


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
    for target in sorted(targets):
        deactivate_and_delete_snippet(client, target)
    remaining = find_active_snippet_ids(client, name)
    if remaining:
        remove_named_snippets(client, name)
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
        "--fault-injection",
        choices=("", "db_capture", "after_prepare", "after_install", "during_rollback", "after_commit"),
        default="",
        help=argparse.SUPPRESS,
    )
    parser.add_argument("--audit-dir", type=Path, default=ROOT / "deploy-audit")
    args = parser.parse_args()
    app_password = os.environ.get("WP_APP_PASSWORD", "")

    if not args.base_url or not args.user or not app_password:
        raise DeployError("WP_BASE_URL, WP_DEPLOY_USER and WP_APP_PASSWORD are required")
    if args.fault_injection and not args.local_test:
        raise DeployError("Fault injection is restricted to isolated loopback tests")
    validate_target_url(args.base_url, args.local_test)
    metadata, artifact, raw = load_artifact(args.dist.resolve())
    deployment_id = args.deployment_id or f"c99-{metadata['version']}-{int(time.time())}-{secrets.token_hex(4)}"
    token = secrets.token_urlsafe(36)
    max_bytes = min(max(len(raw) + 65536, 2 * 1024 * 1024), 8 * 1024 * 1024)
    client = Client(
        args.base_url,
        args.user,
        app_password,
        allow_local_http=args.local_test,
    )
    snippet_id: int | None = None
    snippet_creation_attempted = False
    reservation_acquired = False
    deployed = False
    mutation_pending = False
    finalized = False
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
        )
        snippet_creation_attempted = True
        snippet_id = create_snippet(client, code, deployment_id)
        gate = "preflight"
        preflight = preflight_with_recovery(client, token, deployment_id)
        reservation_acquired = bool(preflight.get("lock_reserved"))
        if not preflight.get("ready") or preflight.get("allowed_slug") != SLUG:
            raise DeployError("Temporary bridge preflight did not pass")
        audit["stale_bridges_recovered"] = remove_prefixed_snippets(
            client,
            SNIPPET_PREFIX,
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
            gate = "install"
            run_fields = {
                "slug": SLUG,
                "type": "plugin",
                "version": metadata["version"],
                "expected_sha256": metadata["sha256"],
                "package_base64": base64.b64encode(raw).decode("ascii"),
                "activate": True,
            }
            deployed = True
            mutation_pending = True
            result = install_with_recovery(client, token, deployment_id, run_fields)
            if (
                result.get("sha256") != metadata["sha256"]
                or result.get("version") != metadata["version"]
                or not result.get("temp_removed")
            ):
                raise DeployError("Bridge install response failed integrity verification")
            audit["install"] = {
                "baseline_database_fingerprint": result.get(
                    "baseline_database_fingerprint", ""
                ),
                "cache_purge": result.get("cache_purge", {}),
                "had_plugin": bool(result.get("had_plugin")),
                "prior_active": bool(result.get("prior_active")),
                "prior_deployment": result.get("prior_deployment", ""),
                "prior_plugin_sha256": result.get("prior_plugin_sha256", ""),
                "prior_version": result.get("prior_version", ""),
                "temp_removed": True,
            }
            gate = "health"
            audit["health"] = verify_health(client, metadata["version"], deployment_id)
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
                audit["rollback"] = {
                    "rolled_back": bool(rollback.get("rolled_back")),
                    "prior_version": rollback.get("prior_version", ""),
                    "prior_deployment": rollback.get("prior_deployment", ""),
                    "database_restore": rollback.get("database_restore", {}),
                }
                audit["rollback_integrity"] = verify_rollback_integrity(
                    client, token, deployment_id, rollback
                )
                audit["rollback_health"] = verify_prior_health(client, rollback)
                audit["rollback_rendered_home"] = verify_rendered_home(
                    client,
                    str(rollback.get("prior_version", "")),
                    str(rollback.get("prior_deployment", "")),
                    deployment_id,
                )
                gate = "finalize"
                audit["rollback_finalize"] = finalize_deployment(client, token, deployment_id)
                finalized = True
                reservation_acquired = False
                mutation_pending = False
                gate = "install"
                finalized = False
                reservation = preflight_with_recovery(client, token, deployment_id)
                reservation_acquired = bool(reservation.get("lock_reserved"))
                if not reservation.get("ready") or not reservation_acquired:
                    raise DeployError("Post-rollback deployment reservation did not pass")
                mutation_pending = True
                result = install_with_recovery(client, token, deployment_id, run_fields)
                if result.get("sha256") != metadata["sha256"] or not result.get("temp_removed"):
                    raise DeployError("Post-rollback redeploy digest verification failed")
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
                    "temp_removed": True,
                }
                gate = "health"
                audit["health_after_exercise"] = verify_health(client, metadata["version"], deployment_id)
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
        elif mutation_pending and not finalized:
            unstarted_recovered = False
            try:
                recovery_status = bridge_call(client, "status", token, deployment_id)
                if not recovery_status.get("state_exists") and (
                    not recovery_status.get("lock_owned")
                    or (
                        recovery_status.get("phase") == "locked"
                        and recovery_status.get("recovery_ready")
                    )
                ):
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
                    }
                    audit["failure_rollback_integrity"] = verify_rollback_integrity(
                        client, token, deployment_id, rollback
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
