from __future__ import annotations

import importlib.util
import json
import re
import sys
import threading
import types
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    assert spec and spec.loader
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


DEPLOY = load_module(
    "complete99_pipeline_deployer",
    ROOT / "scripts" / "deploy-wordpress.py",
)
RECOVER = load_module(
    "complete99_pipeline_recovery",
    ROOT / "scripts" / "recover-wordpress.py",
)


class PipelineHardeningTests(unittest.TestCase):
    def test_one_time_bootstrap_cleanup_uses_exact_name_and_proves_each_row_absent(
        self,
    ) -> None:
        class FakeClient:
            snippets: dict[int, dict[str, object]] = {
                5: {
                    "id": 5,
                    "name": DEPLOY.BOOTSTRAP_SNIPPET_NAME,
                    "active": True,
                },
                9: {
                    "id": 9,
                    "name": DEPLOY.BOOTSTRAP_SNIPPET_NAME,
                    "active": False,
                },
                12: {"id": 12, "name": "keep-this-snippet", "active": True},
            }

            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                exact = re.search(r"/snippets/(\d+)\?", path)
                if method == "GET" and exact:
                    snippet_id = int(exact.group(1))
                    row = self.snippets.get(snippet_id)
                    return (
                        (200, dict(row))
                        if row
                        else (500, {"code": "rest_cannot_get"})
                    )
                if method == "GET":
                    return 200, list(self.snippets.values())
                if method == "POST" and path.endswith("/retire"):
                    assert payload is not None
                    for snippet_id in payload["snippet_ids"]:
                        self.snippets.pop(int(snippet_id), None)
                    return 200, {"permanently_deleted": payload["snippet_ids"]}
                raise AssertionError((method, path, payload, expected))

        client = FakeClient()
        result = DEPLOY.remove_bootstrap_snippet(
            client,
            "temporary-token",
            "c99-bootstrap-cleanup",
        )
        self.assertEqual([5, 9], result["removed_ids"])
        self.assertTrue(result["known_id_matched"])
        self.assertTrue(result["row_absence_verified"])
        self.assertEqual(
            {12: {"id": 12, "name": "keep-this-snippet", "active": True}},
            client.snippets,
        )

    def test_proxy_ambiguous_delete_requires_exact_id_absence(self) -> None:
        deployment_id = "c99-delete-proof-1234"
        name = DEPLOY.snippet_name(deployment_id)

        class FakeClient:
            snippets: dict[int, dict[str, object]] = {
                42: {"id": 42, "name": name, "active": True}
            }

            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                if method == "GET" and "/snippets/42?" in path:
                    row = self.snippets.get(42)
                    return (
                        (200, dict(row))
                        if row
                        else (500, {"code": "rest_cannot_get"})
                    )
                if method == "GET" and path.startswith(
                    "/wp-json/code-snippets/v1/snippets?"
                ):
                    return 200, list(self.snippets.values())
                if method == "POST" and path.endswith("/retire"):
                    self.snippets.pop(42, None)
                    raise DEPLOY.DeployError("proxy returned a misleading response")
                if method == "POST" and path.endswith(
                    f"/{deployment_id}/preflight"
                ):
                    return 404, {"code": "rest_no_route"}
                raise AssertionError((method, path, payload, expected))

        result = DEPLOY.delete_snippet_and_prove_404(
            FakeClient(),
            42,
            "temporary-token",
            deployment_id,
            True,
        )
        self.assertTrue(result["snippet_deleted"])
        self.assertTrue(result["row_absence_verified"])
        self.assertTrue(result["route_404"])

    def test_live_396_missing_exact_get_is_trusted_only_for_rest_cannot_get(
        self,
    ) -> None:
        class MissingClient:
            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                self.assert_request(method, path, expected)
                return 500, {"code": "rest_cannot_get"}

            @staticmethod
            def assert_request(
                method: str, path: str, expected: tuple[int, ...]
            ) -> None:
                if method != "GET" or "/snippets/77?" not in path:
                    raise AssertionError((method, path, expected))
                if 500 not in expected:
                    raise AssertionError(expected)

        self.assertIsNone(DEPLOY.get_snippet_by_id(MissingClient(), 77))

        class UntrustedFailureClient:
            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                return 500, {"code": "internal_server_error"}

        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.get_snippet_by_id(UntrustedFailureClient(), 77)

    def test_bridge_permanent_retirement_uses_code_snippets_delete_api(
        self,
    ) -> None:
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )
        self.assertIn("$route_prefix . '/retire'", bridge)
        self.assertIn("Code_Snippets\\\\delete_snippet", bridge)
        self.assertIn("'c99-deploy-bootstrap' !== $name", bridge)
        self.assertIn("str_starts_with( $name, 'tmp-complete99-deploy-' )", bridge)

    def test_upress_alias_requires_the_exact_explicit_allowlist(self) -> None:
        alias = "a235232-tmp.s1242.upress.link"
        DEPLOY.validate_target_url(f"https://{alias}", False, alias)
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.validate_target_url(f"https://{alias}", False)
        for unsafe in (
            "*.upress.link",
            "another.s1242.upress.link",
            f"https://{alias}",
            f"{alias}:443",
        ):
            with self.assertRaises(DEPLOY.DeployError, msg=unsafe):
                DEPLOY.parse_allowed_deploy_hosts(unsafe)

        bridge = DEPLOY.render_bridge(
            "safe-temporary-token",
            "c99-alias-contract",
            2 * 1024 * 1024,
            False,
            target_host=alias,
            allowed_hosts={
                "complete99.co.il",
                "www.complete99.co.il",
                alias,
            },
        )
        self.assertIn(f"'target_host'   => '{alias}'", bridge)
        self.assertNotIn("__C99_ALLOWED_HOSTS__", bridge)
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.render_bridge(
                "safe-temporary-token",
                "c99-unsupported-host",
                2 * 1024 * 1024,
                False,
                target_host="unapproved.example",
                allowed_hosts={"unapproved.example"},
            )

    def test_http_errors_keep_only_safe_lock_discovery_metadata(self) -> None:
        class ConflictHandler(BaseHTTPRequestHandler):
            def do_GET(self) -> None:  # noqa: N802
                body = json.dumps(
                    {
                        "code": "c99_deploy_locked",
                        "message": "Rejected token must-not-escape",
                        "data": {
                            "status": 409,
                            "deployment_id": "c99-prod-owner-1234",
                            "phase": "installing",
                            "secret": "must-not-escape",
                            "nested": {"token": "must-not-escape"},
                        },
                    }
                ).encode()
                self.send_response(409)
                self.send_header("Content-Type", "application/json")
                self.send_header("Content-Length", str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(("127.0.0.1", 0), ConflictHandler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            client = DEPLOY.Client(
                f"http://127.0.0.1:{server.server_port}",
                "deploy-user",
                "application-password",
                allow_local_http=True,
                timeout=3,
            )
            with self.assertRaises(DEPLOY.HTTPDeployError) as caught:
                client.request("GET", "/conflict")
            error = caught.exception
            self.assertEqual("c99_deploy_locked", error.code)
            self.assertEqual(409, error.status)
            self.assertEqual("c99-prod-owner-1234", error.data["deployment_id"])
            self.assertEqual("installing", error.data["phase"])
            self.assertNotIn("secret", error.data)
            self.assertNotIn("must-not-escape", str(error))
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

    def test_authenticated_probe_discovers_the_exact_owning_deployment(self) -> None:
        calls: list[tuple[str, object]] = []

        def locked_preflight(*_args):
            raise DEPLOY.HTTPDeployError(
                "locked",
                status=409,
                code="c99_deploy_locked",
                data={
                    "deployment_id": "c99-prod-owner-5678",
                    "phase": "installing",
                },
            )

        fake_deployer = types.SimpleNamespace(
            re=re,
            DeployError=DEPLOY.DeployError,
            HTTPDeployError=DEPLOY.HTTPDeployError,
            render_bridge=lambda *_args, **_kwargs: "bridge",
            create_snippet=lambda *_args: 71,
            remove_bootstrap_snippet=lambda *_args: {
                "removed_ids": [],
                "row_absence_verified": True,
            },
            preflight_with_recovery=locked_preflight,
            delete_snippet_and_prove_404=lambda *_args: {
                "snippet_deleted": True,
                "route_404": True,
            },
            verify_bridge_site_identity=lambda *_args: {},
            finalize_deployment=lambda *_args: calls.append(("finalize", _args)),
        )
        owner, discovery = RECOVER.discover_lock_owner(
            fake_deployer,
            object(),
            "c99-recovery-probe-1234",
            False,
            "complete99.co.il",
            {"complete99.co.il", "www.complete99.co.il"},
        )
        self.assertEqual("c99-prod-owner-5678", owner)
        self.assertEqual("owner-discovered", discovery["result"])
        self.assertTrue(
            discovery["bootstrap_cleanup"]["row_absence_verified"]
        )
        self.assertTrue(discovery["cleanup"]["route_404"])
        self.assertEqual([], calls)

    def test_bridge_fences_mutations_and_compensates_failed_database_restore(self) -> None:
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )
        self.assertIn("flock( $handle, LOCK_EX | LOCK_NB )", bridge)
        self.assertIn("BINARY option_value = BINARY %s", bridge)
        self.assertIn("option_value = %s COLLATE BINARY", bridge)
        self.assertIn("'fence'", bridge)
        self.assertIn("'heartbeat_seq'", bridge)
        self.assertIn("'process_lock_available'", bridge)
        self.assertNotIn(".maintenance", bridge)

        rollback = bridge.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/finalize'", 1
        )[0]
        self.assertIn("'forward_plugin_sha256'", rollback)
        self.assertIn("'rollback_files_already_restored'", rollback)
        self.assertIn("$compensate_forward", rollback)
        self.assertIn("@rename( $displaced_dir, $target_dir )", rollback)
        self.assertIn("'rollback_compensated'", rollback)
        self.assertIn("'c99_db_restore_compensated'", rollback)
        self.assertIn("wp_opcache_invalidate_directory( $target_dir )", rollback)
        self.assertIn(
            "(bool) is_plugin_active( $config['plugin_file'] ) !== $forward_was_active",
            rollback,
        )
        restore_call = rollback.index(
            "$database_restore = $restore_database_state( $database_snapshot )"
        )
        compensate_call = rollback.index(
            "'c99_db_restore_compensated'", restore_call
        )
        displaced_delete = rollback.index(
            "$wp_filesystem->delete( $displaced_dir, true )", compensate_call
        )
        baseline_readback = rollback.index(
            "'c99_rollback_database_readback'", compensate_call
        )
        self.assertLess(restore_call, compensate_call)
        self.assertLess(compensate_call, baseline_readback)
        self.assertLess(baseline_readback, displaced_delete)

    def test_committed_cleanup_uses_the_installed_directory_digest(self) -> None:
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )
        recovery = (ROOT / "scripts" / "recover-wordpress.py").read_text(
            encoding="utf-8"
        )
        self.assertIn("'installed_plugin_sha256'", bridge)
        self.assertIn("'committed_expected_plugin_sha256'", bridge)
        self.assertIn("'committed_expected_absent'", bridge)
        self.assertIn("committed_expected_plugin_sha256", recovery)
        self.assertIn("verify_plugin_absent", recovery)
        self.assertIn("verify_inactive_plugin", recovery)
        self.assertNotIn(
            'status.get("current_plugin_sha256") != expected_sha256',
            recovery,
        )

    def test_workflow_is_manual_and_safe_first_install_is_the_default(self) -> None:
        workflow = (
            ROOT / ".github" / "workflows" / "wordpress-deploy.yml"
        ).read_text(encoding="utf-8")
        self.assertRegex(workflow, r"(?m)^on:\n  workflow_dispatch:")
        self.assertNotRegex(workflow, r"(?m)^  push:")
        rollback_input = workflow.split("rollback_exercise:", 1)[1].split(
            "bootstrap_code_snippets:", 1
        )[0]
        bootstrap_input = workflow.split("bootstrap_code_snippets:", 1)[1].split(
            "permissions:", 1
        )[0]
        self.assertIn("default: false", rollback_input)
        self.assertIn("default: true", bootstrap_input)
        self.assertIn("recover-wordpress.py", workflow)
        self.assertIn("--discover", workflow)
        self.assertIn("WP_ALLOWED_DEPLOY_HOSTS", workflow)


if __name__ == "__main__":
    unittest.main()
