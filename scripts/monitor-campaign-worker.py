#!/usr/bin/env python3
"""Run the fixed Complete99 Campaign worker and verify its durable heartbeat."""

from __future__ import annotations

import base64
from dataclasses import dataclass
from datetime import datetime, timezone
import json
import os
import re
import sys
from typing import Any
import urllib.error
import urllib.parse
import urllib.request


ROUTE = "complete99/v1/ops/campaign-worker"
RESPONSE_SCHEMA = "complete99-campaign-worker-monitor/v1"
HEARTBEAT_MAX_AGE = 4500
MAX_RESPONSE_BYTES = 64 * 1024
REQUEST_TIMEOUT_SECONDS = 30
ALLOWED_PRODUCTION_HOSTS = frozenset({"complete99.co.il", "www.complete99.co.il"})
SUPPORTED_TRANSITIONAL_HOSTS = frozenset({"a235232-tmp.s1242.upress.link"})
HOST_PATTERN = re.compile(
    r"(?=.{1,253}\Z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+"
    r"[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\Z"
)
UTC_TIMESTAMP_PATTERN = re.compile(r"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\Z")


class MonitorError(RuntimeError):
    """A bounded monitor refusal safe to print without response or secret data."""


class RejectRedirects(urllib.request.HTTPRedirectHandler):
    """Never forward Application Password credentials across a redirect."""

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


@dataclass(frozen=True)
class MonitorConfig:
    base_url: str
    username: str
    app_password: str
    allowed_monitor_hosts: str = ""


@dataclass(frozen=True)
class HttpResult:
    status: int
    content_type: str
    body: bytes


def parse_allowed_monitor_hosts(value: str) -> set[str]:
    """Accept only explicitly supported exact transitional DNS hostnames."""
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
            or HOST_PATTERN.fullmatch(host) is None
        ):
            raise MonitorError(
                "WP_ALLOWED_MONITOR_HOSTS must contain exact DNS hostnames only"
            )
        configured.add(host)
    if configured - SUPPORTED_TRANSITIONAL_HOSTS:
        raise MonitorError(
            "WP_ALLOWED_MONITOR_HOSTS contains an unsupported monitor hostname"
        )
    return configured


def validate_target_url(base_url: str, allowed_monitor_hosts: str = "") -> str:
    """Return a canonical credential-safe origin after exact production validation."""
    if not base_url or base_url != base_url.strip():
        raise MonitorError("WP_BASE_URL must be a clean HTTPS production origin")
    try:
        parsed = urllib.parse.urlparse(base_url)
        port = parsed.port
    except ValueError as error:
        raise MonitorError("WP_BASE_URL must be a clean HTTPS production origin") from error
    host = (parsed.hostname or "").lower()
    clean_root = (
        parsed.scheme == "https"
        and parsed.username is None
        and parsed.password is None
        and parsed.path in {"", "/"}
        and not parsed.params
        and not parsed.query
        and not parsed.fragment
        and port is None
    )
    allowed = ALLOWED_PRODUCTION_HOSTS | parse_allowed_monitor_hosts(
        allowed_monitor_hosts
    )
    if not clean_root or host not in allowed:
        raise MonitorError("WP_BASE_URL must be a clean approved HTTPS production origin")
    return f"https://{host}"


def _route_urls(base_url: str) -> tuple[str, str]:
    pretty = f"{base_url}/wp-json/{ROUTE}"
    query = f"{base_url}/?rest_route={urllib.parse.quote('/' + ROUTE, safe='/')}"
    return pretty, query


def _build_request(url: str, username: str, app_password: str) -> urllib.request.Request:
    if (
        not username
        or username != username.strip()
        or ":" in username
        or not app_password
        or app_password != app_password.strip()
    ):
        raise MonitorError("Dedicated Campaign worker credentials are unavailable")
    token = base64.b64encode(f"{username}:{app_password}".encode("utf-8")).decode(
        "ascii"
    )
    return urllib.request.Request(
        url,
        data=b"{}",
        method="POST",
        headers={
            "Accept": "application/json",
            "Authorization": f"Basic {token}",
            "Content-Type": "application/json",
            "User-Agent": "Complete99-Campaign-Monitor/1",
        },
    )


def _read_bounded(response: Any) -> HttpResult:
    body = response.read(MAX_RESPONSE_BYTES + 1)
    if len(body) > MAX_RESPONSE_BYTES:
        raise MonitorError("The Campaign worker response exceeded its size limit")
    status_value = getattr(response, "status", None)
    if status_value is None:
        status_value = response.getcode()
    status = int(status_value)
    content_type = str(response.headers.get("Content-Type", "")).split(";", 1)[0]
    return HttpResult(status=status, content_type=content_type.strip().lower(), body=body)


def _request_once(
    opener: urllib.request.OpenerDirector,
    request: urllib.request.Request,
) -> HttpResult:
    try:
        with opener.open(request, timeout=REQUEST_TIMEOUT_SECONDS) as response:
            return _read_bounded(response)
    except urllib.error.HTTPError as error:
        try:
            return _read_bounded(error)
        finally:
            error.close()
    except (urllib.error.URLError, TimeoutError, OSError) as error:
        raise MonitorError("The Campaign worker request could not reach WordPress") from error


def _is_demonstrable_host_html_denial(result: HttpResult) -> bool:
    if result.status != 403 or result.content_type != "text/html":
        return False
    prefix = result.body.lstrip()[:32].lower()
    return prefix.startswith(b"<!doctype html") or prefix.startswith(b"<html")


def _unique_object(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    value: dict[str, Any] = {}
    for key, item in pairs:
        if key in value:
            raise MonitorError("The Campaign worker returned duplicate JSON fields")
        value[key] = item
    return value


def decode_response(result: HttpResult) -> dict[str, Any]:
    if result.status != 200:
        raise MonitorError("The Campaign worker returned a non-success status")
    if result.content_type not in {"application/json", "application/problem+json"}:
        raise MonitorError("The Campaign worker did not return JSON")
    try:
        payload = json.loads(
            result.body.decode("utf-8", errors="strict"),
            object_pairs_hook=_unique_object,
        )
    except (UnicodeDecodeError, json.JSONDecodeError) as error:
        raise MonitorError("The Campaign worker returned invalid JSON") from error
    if not isinstance(payload, dict):
        raise MonitorError("The Campaign worker returned an invalid response shape")
    return payload


def validate_monitor_payload(
    payload: dict[str, Any], now: datetime | None = None
) -> dict[str, Any]:
    """Require the exact success schema and a coherent 75-minute heartbeat."""
    if set(payload) != {"schemaVersion", "workerCompleted", "cronRunner"}:
        raise MonitorError("The Campaign worker returned an invalid response shape")
    if payload.get("schemaVersion") != RESPONSE_SCHEMA or payload.get(
        "workerCompleted"
    ) is not True:
        raise MonitorError("The Campaign worker did not prove completion")
    runner = payload.get("cronRunner")
    expected_runner_fields = {
        "ready",
        "inspectable",
        "lastAt",
        "ageSeconds",
        "maxAgeSeconds",
    }
    if not isinstance(runner, dict) or set(runner) != expected_runner_fields:
        raise MonitorError("The Campaign worker returned an invalid heartbeat shape")
    age = runner.get("ageSeconds")
    max_age = runner.get("maxAgeSeconds")
    if (
        runner.get("ready") is not True
        or runner.get("inspectable") is not True
        or not isinstance(age, int)
        or isinstance(age, bool)
        or not 0 <= age <= HEARTBEAT_MAX_AGE
        or not isinstance(max_age, int)
        or isinstance(max_age, bool)
        or max_age != HEARTBEAT_MAX_AGE
    ):
        raise MonitorError("The Campaign worker heartbeat is not ready")
    last_at = runner.get("lastAt")
    if not isinstance(last_at, str) or UTC_TIMESTAMP_PATTERN.fullmatch(last_at) is None:
        raise MonitorError("The Campaign worker heartbeat timestamp is invalid")
    try:
        recorded = datetime.strptime(last_at, "%Y-%m-%dT%H:%M:%SZ").replace(
            tzinfo=timezone.utc
        )
    except ValueError as error:
        raise MonitorError("The Campaign worker heartbeat timestamp is invalid") from error
    observed_at = now or datetime.now(timezone.utc)
    observed_age = int((observed_at - recorded).total_seconds())
    if observed_age < -300 or abs(max(0, observed_age) - age) > 300:
        raise MonitorError("The Campaign worker heartbeat timestamp is inconsistent")
    return payload


def run_monitor(
    config: MonitorConfig,
    opener: urllib.request.OpenerDirector | None = None,
    now: datetime | None = None,
) -> dict[str, Any]:
    base_url = validate_target_url(config.base_url, config.allowed_monitor_hosts)
    pretty_url, query_url = _route_urls(base_url)
    client = opener or urllib.request.build_opener(RejectRedirects())
    result = _request_once(
        client, _build_request(pretty_url, config.username, config.app_password)
    )
    if _is_demonstrable_host_html_denial(result):
        result = _request_once(
            client, _build_request(query_url, config.username, config.app_password)
        )
    return validate_monitor_payload(decode_response(result), now=now)


def main() -> int:
    try:
        payload = run_monitor(
            MonitorConfig(
                base_url=os.environ.get("WP_BASE_URL", ""),
                username=os.environ.get("WP_CAMPAIGN_WORKER_USER", ""),
                app_password=os.environ.get("WP_CAMPAIGN_WORKER_APP_PASSWORD", ""),
                allowed_monitor_hosts=os.environ.get("WP_ALLOWED_MONITOR_HOSTS", ""),
            )
        )
        age = payload["cronRunner"]["ageSeconds"]
        print(f"Campaign worker completed; durable heartbeat age is {age} seconds.")
        return 0
    except MonitorError as error:
        print(f"Campaign monitor failed: {error}", file=sys.stderr)
        return 1
    except Exception:
        print("Campaign monitor failed: an unexpected monitor error occurred", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
