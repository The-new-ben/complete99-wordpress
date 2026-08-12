from __future__ import annotations

from datetime import datetime, timezone
import importlib.util
import json
from pathlib import Path
import re
import sys
import unittest


ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "scripts" / "monitor-campaign-worker.py"
WORKFLOW = ROOT / ".github" / "workflows" / "wordpress-campaign-monitor.yml"


def load_monitor_module():
    spec = importlib.util.spec_from_file_location("complete99_campaign_monitor", SCRIPT)
    if spec is None or spec.loader is None:
        raise RuntimeError("Campaign monitor module could not be loaded")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


MONITOR = load_monitor_module()


class FakeResponse:
    def __init__(self, status: int, content_type: str, body: bytes) -> None:
        self.status = status
        self.headers = {"Content-Type": content_type}
        self.body = body

    def read(self, limit: int) -> bytes:
        return self.body[:limit]

    def getcode(self) -> int:
        return self.status

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc, traceback) -> None:
        return None


class FakeOpener:
    def __init__(self, responses: list[FakeResponse]) -> None:
        self.responses = list(responses)
        self.requests = []

    def open(self, request, timeout: int):
        self.requests.append((request, timeout))
        return self.responses.pop(0)


class Complete99CampaignMonitorContracts(unittest.TestCase):
    def valid_payload(self) -> dict:
        return {
            "schemaVersion": "complete99-campaign-worker-monitor/v1",
            "workerCompleted": True,
            "cronRunner": {
                "ready": True,
                "inspectable": True,
                "lastAt": "2026-08-12T09:00:00Z",
                "ageSeconds": 0,
                "maxAgeSeconds": 4500,
            },
        }

    def test_workflow_is_15_minute_serialized_and_uses_dedicated_secrets(self) -> None:
        workflow = WORKFLOW.read_text(encoding="utf-8")
        self.assertIn("cron: '7,22,37,52 * * * *'", workflow)
        self.assertNotIn("cron: '*/15 * * * *'", workflow)
        self.assertIn("workflow_dispatch:", workflow)
        self.assertIn("contents: read", workflow)
        self.assertIn("group: complete99-campaign-monitor", workflow)
        self.assertNotIn("group: complete99-wordpress-production", workflow)
        self.assertIn("cancel-in-progress: false", workflow)
        self.assertIn("runs-on: [self-hosted, Windows, X64, complete99-monitor]", workflow)
        self.assertNotIn("complete99-deploy", workflow)
        self.assertIn("environment: campaign-monitor", workflow)
        self.assertIn('$env:GITHUB_REF -ne "refs/heads/main"', workflow)
        self.assertIn("WP_CAMPAIGN_WORKER_USER: ${{ secrets.WP_CAMPAIGN_WORKER_USER }}", workflow)
        self.assertIn(
            "WP_CAMPAIGN_WORKER_APP_PASSWORD: "
            "${{ secrets.WP_CAMPAIGN_WORKER_APP_PASSWORD }}",
            workflow,
        )
        self.assertIn(
            "WP_ALLOWED_MONITOR_HOSTS: ${{ secrets.WP_ALLOWED_MONITOR_HOSTS }}",
            workflow,
        )
        self.assertNotIn("WP_DEPLOY_USER", workflow)
        self.assertNotRegex(workflow, r"(?m)^\s+WP_APP_PASSWORD:")
        revisions = re.findall(r"uses:\s*[^@\s]+@([^\s]+)", workflow)
        self.assertEqual(1, len(revisions))
        self.assertRegex(revisions[0], r"^[0-9a-f]{40}$")
        self.assertIn("persist-credentials: false", workflow)
        self.assertIn("fetch-depth: 1", workflow)
        self.assertIn("sparse-checkout: |\n            scripts/monitor-campaign-worker.py", workflow)
        self.assertIn("sparse-checkout-cone-mode: false", workflow)
        self.assertNotRegex(workflow, r"(?m)^\s+filter:")
        self.assertNotIn("actions/setup-python", workflow)

    def test_target_origin_is_exact_and_transitional_host_is_explicit(self) -> None:
        self.assertEqual(
            "https://complete99.co.il",
            MONITOR.validate_target_url("https://complete99.co.il/"),
        )
        transitional = "a235232-tmp.s1242.upress.link"
        self.assertEqual(
            f"https://{transitional}",
            MONITOR.validate_target_url(
                f"https://{transitional}", transitional
            ),
        )
        invalid_userinfo = ":".join(("invalid-user", "invalid-password"))
        credentialed_url = f"https://{invalid_userinfo}@complete99.co.il"
        for value in (
            "http://complete99.co.il",
            "https://complete99.co.il/path",
            "https://complete99.co.il:443",
            credentialed_url,
            "https://evil.example",
        ):
            with self.subTest(value=value):
                with self.assertRaises(MONITOR.MonitorError):
                    MONITOR.validate_target_url(value)
        for value in ("*.example.com", "https://example.com", "evil.example"):
            with self.subTest(allowlist=value):
                with self.assertRaises(MONITOR.MonitorError):
                    MONITOR.parse_allowed_monitor_hosts(value)

    def test_response_requires_exact_fresh_75_minute_contract(self) -> None:
        now = datetime(2026, 8, 12, 9, 0, 0, tzinfo=timezone.utc)
        self.assertEqual(
            self.valid_payload(),
            MONITOR.validate_monitor_payload(self.valid_payload(), now=now),
        )
        invalid_payloads = []
        extra = self.valid_payload()
        extra["extra"] = True
        invalid_payloads.append(extra)
        bool_age = self.valid_payload()
        bool_age["cronRunner"]["ageSeconds"] = True
        invalid_payloads.append(bool_age)
        wrong_max = self.valid_payload()
        wrong_max["cronRunner"]["maxAgeSeconds"] = 7200
        invalid_payloads.append(wrong_max)
        for invalid_max_age in (4500.0, True, "4500"):
            wrong_max_type = self.valid_payload()
            wrong_max_type["cronRunner"]["maxAgeSeconds"] = invalid_max_age
            invalid_payloads.append(wrong_max_type)
        stale = self.valid_payload()
        stale["cronRunner"]["ageSeconds"] = 4501
        invalid_payloads.append(stale)
        inconsistent = self.valid_payload()
        inconsistent["cronRunner"]["lastAt"] = "2026-08-12T07:00:00Z"
        invalid_payloads.append(inconsistent)
        for payload in invalid_payloads:
            with self.subTest(payload=payload):
                with self.assertRaises(MONITOR.MonitorError):
                    MONITOR.validate_monitor_payload(payload, now=now)

    def test_only_demonstrable_html_403_uses_query_transport_fallback(self) -> None:
        now = datetime(2026, 8, 12, 9, 0, 0, tzinfo=timezone.utc)
        body = json.dumps(self.valid_payload()).encode("utf-8")
        opener = FakeOpener(
            [
                FakeResponse(403, "text/html", b"<!doctype html><title>Denied</title>"),
                FakeResponse(200, "application/json; charset=UTF-8", body),
            ]
        )
        result = MONITOR.run_monitor(
            MONITOR.MonitorConfig(
                base_url="https://complete99.co.il",
                username="campaign-worker",
                app_password="abcd efgh ijkl mnop",
            ),
            opener=opener,
            now=now,
        )
        self.assertTrue(result["workerCompleted"])
        self.assertEqual(2, len(opener.requests))
        first, second = (entry[0] for entry in opener.requests)
        self.assertEqual(
            "https://complete99.co.il/wp-json/complete99/v1/ops/campaign-worker",
            first.full_url,
        )
        self.assertEqual(
            "https://complete99.co.il/?rest_route=/complete99/v1/ops/campaign-worker",
            second.full_url,
        )
        self.assertEqual("POST", first.get_method())
        self.assertTrue(first.get_header("Authorization", "").startswith("Basic "))

        denial = FakeOpener(
            [FakeResponse(403, "application/json", b'{"code":"rest_forbidden"}')]
        )
        with self.assertRaises(MONITOR.MonitorError):
            MONITOR.run_monitor(
                MONITOR.MonitorConfig(
                    base_url="https://complete99.co.il",
                    username="campaign-worker",
                    app_password="abcd efgh ijkl mnop",
                ),
                opener=denial,
                now=now,
            )
        self.assertEqual(1, len(denial.requests))


if __name__ == "__main__":
    unittest.main()
