from __future__ import annotations

import importlib.util
import hashlib
import json
import re
import sys
import tempfile
import threading
import types
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from unittest import mock


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
    def test_rest_identity_uses_a_bounded_field_projection(self) -> None:
        self.assertEqual(
            "/wp-json/?_fields=home,url",
            DEPLOY.REST_IDENTITY_PATH,
        )

        class IdentityClient:
            base_url = "http://127.0.0.1"
            allow_local_http = True
            allowed_deploy_hosts = ""
            paths: list[str] = []

            def request_public_json(self, path: str):
                self.paths.append(path)
                return 200, {
                    "home": "http://127.0.0.1",
                    "url": "http://127.0.0.1",
                }

        client = IdentityClient()
        self.assertEqual(
            {
                "home": "http://127.0.0.1",
                "url": "http://127.0.0.1",
            },
            DEPLOY.verify_rest_identity(client),
        )
        self.assertEqual([DEPLOY.REST_IDENTITY_PATH], client.paths)

    def test_upress_requests_use_a_normal_browser_signature(self) -> None:
        self.assertTrue(DEPLOY.USER_AGENT.startswith("Mozilla/5.0 "))
        self.assertIn("Chrome/", DEPLOY.USER_AGENT)
        self.assertIn("Safari/", DEPLOY.USER_AGENT)
        self.assertNotIn("Complete99WordPressDeploy", DEPLOY.USER_AGENT)

    def test_html_403_uses_standard_rest_route_transport_and_json_403_does_not(
        self,
    ) -> None:
        class WafHandler(BaseHTTPRequestHandler):
            paths: list[str] = []

            def do_GET(self) -> None:
                type(self).paths.append(self.path)
                if self.path.startswith("/wp-json/wp/v2/users/me"):
                    body = b"<html><body>forbidden by nginx</body></html>"
                    self.send_response(403)
                    self.send_header("Content-Type", "text/html")
                elif self.path.startswith("/?rest_route=/wp/v2/users/me"):
                    body = json.dumps(
                        {"id": 1, "roles": ["administrator"]}
                    ).encode()
                    self.send_response(200)
                    self.send_header("Content-Type", "application/json")
                elif self.path.startswith(
                    "/?rest_route=/code-snippets/v1/snippets"
                ):
                    body = b"[]"
                    self.send_response(200)
                    self.send_header("Content-Type", "application/json")
                elif self.path.startswith("/wp-json/json-forbidden"):
                    body = json.dumps(
                        {"code": "rest_forbidden", "data": {"status": 403}}
                    ).encode()
                    self.send_response(403)
                    self.send_header("Content-Type", "application/json")
                else:
                    body = b"not found"
                    self.send_response(404)
                    self.send_header("Content-Type", "text/plain")
                self.send_header("Content-Length", str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, _format: str, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(("127.0.0.1", 0), WafHandler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        base_url = f"http://127.0.0.1:{server.server_port}"
        try:
            client = DEPLOY.Client(
                base_url,
                "local-admin",
                "local-test-only",
                allow_local_http=True,
            )
            status, user = client.request(
                "GET",
                "/wp-json/wp/v2/users/me?context=edit",
            )
            self.assertEqual(200, status)
            self.assertEqual(["administrator"], user["roles"])
            self.assertTrue(client.use_query_rest_transport)
            _, snippets = client.request(
                "GET",
                "/wp-json/code-snippets/v1/snippets?per_page=1",
            )
            self.assertEqual([], snippets)
            self.assertEqual(
                [
                    "/wp-json/wp/v2/users/me?context=edit",
                    "/?rest_route=/wp/v2/users/me&context=edit",
                    "/?rest_route=/code-snippets/v1/snippets&per_page=1",
                ],
                WafHandler.paths,
            )

            strict_client = DEPLOY.Client(
                base_url,
                "local-admin",
                "local-test-only",
                allow_local_http=True,
            )
            with self.assertRaises(DEPLOY.HTTPDeployError):
                strict_client.request("GET", "/wp-json/json-forbidden")
            self.assertFalse(strict_client.use_query_rest_transport)
            self.assertEqual(
                "/wp-json/json-forbidden",
                WafHandler.paths[-1],
            )
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

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

    def test_package_upload_ceiling_accepts_current_release_and_stays_bounded(
        self,
    ) -> None:
        metadata, _, raw = DEPLOY.load_artifact((ROOT / "plugin-dist").resolve())
        self.assertEqual("1.8.0", metadata["version"])

        ceiling = DEPLOY.package_upload_ceiling(len(raw))
        self.assertEqual(
            len(raw) + DEPLOY.PACKAGE_UPLOAD_HEADROOM_BYTES,
            ceiling,
        )
        self.assertGreater(ceiling, len(raw))
        self.assertLessEqual(ceiling, DEPLOY.MAX_PACKAGE_UPLOAD_BYTES)

        bridge = DEPLOY.render_bridge(
            "safe-temporary-token",
            "c99-package-ceiling-contract",
            ceiling,
            True,
            target_host="localhost",
            allowed_hosts={"localhost"},
        )
        self.assertIn(f"'max_bytes'     => {ceiling}", bridge)

        for invalid in (0, -1, True, 1.5):
            with self.assertRaises(DEPLOY.DeployError, msg=repr(invalid)):
                DEPLOY.package_upload_ceiling(invalid)  # type: ignore[arg-type]
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.package_upload_ceiling(
                DEPLOY.MAX_PACKAGE_UPLOAD_BYTES + 1
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
                            "runtime_loaded": False,
                            "runtime_version": "1.2.1",
                            "migration_failed": None,
                            "plugin_digest_match": True,
                            "plugin_header_match": True,
                            "database_version_match": False,
                            "plugin_active": True,
                            "database_error": False,
                            "current_version": "1.3.0",
                            "current_database_version": "1.2.1",
                            "retryable_forward_mismatch": True,
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
            self.assertFalse(error.data["runtime_loaded"])
            self.assertEqual("1.2.1", error.data["runtime_version"])
            self.assertIsNone(error.data["migration_failed"])
            self.assertTrue(error.data["plugin_digest_match"])
            self.assertTrue(error.data["plugin_header_match"])
            self.assertFalse(error.data["database_version_match"])
            self.assertTrue(error.data["plugin_active"])
            self.assertFalse(error.data["database_error"])
            self.assertEqual("1.3.0", error.data["current_version"])
            self.assertEqual(
                "1.2.1",
                error.data["current_database_version"],
            )
            self.assertTrue(error.data["retryable_forward_mismatch"])
            self.assertNotIn("secret", error.data)
            self.assertNotIn("must-not-escape", str(error))
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

    def test_http_errors_expose_only_bounded_catalog_failure_messages(self) -> None:
        class CatalogFailureHandler(BaseHTTPRequestHandler):
            def do_GET(self) -> None:  # noqa: N802
                message = (
                    "complete99_live_catalog_asset_upload_failed: "
                    "The approved product image could not be imported: product-tahini-500g"
                )
                body = json.dumps(
                    {
                        "code": "complete99_live_catalog_apply_failed",
                        "message": message,
                        "data": {
                            "status": 500,
                            "catalog_stage": "secret_token",
                            "catalog_cause_code": "complete99_live_catalog_secretvalue",
                            "catalog_product_code": "product-secretvalue",
                            "secret": "must-not-escape",
                        },
                    }
                ).encode()
                self.send_response(500)
                self.send_header("Content-Type", "application/json")
                self.send_header("Content-Length", str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(("127.0.0.1", 0), CatalogFailureHandler)
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
                client.request("GET", "/catalog-failure")
            error = caught.exception
            self.assertEqual(500, error.status)
            self.assertEqual("complete99_live_catalog_apply_failed", error.code)
            self.assertEqual(
                "complete99_live_catalog_asset_upload_failed",
                error.data["catalog_cause_code"],
            )
            self.assertEqual(
                "product-tahini-500g",
                error.data["catalog_product_code"],
            )
            self.assertEqual("attachment", error.data["catalog_stage"])
            self.assertIn("product-tahini-500g", str(error))
            self.assertNotIn("approved product image", str(error).lower())
            self.assertNotIn("secret", error.data)
            self.assertNotIn("must-not-escape", str(error))
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

    def test_http_errors_reject_catalog_messages_with_server_paths(self) -> None:
        class UnsafeCatalogFailureHandler(BaseHTTPRequestHandler):
            def do_GET(self) -> None:  # noqa: N802
                body = json.dumps(
                    {
                        "code": "complete99_live_catalog_apply_failed",
                        "message": "Failure in /home/example/public_html/wp-config.php",
                        "data": {
                            "status": 500,
                            "catalog_stage": "secret_token",
                            "catalog_cause_code": "complete99_live_catalog_secretvalue",
                            "catalog_product_code": "product-secretvalue",
                        },
                    }
                ).encode()
                self.send_response(500)
                self.send_header("Content-Type", "application/json")
                self.send_header("Content-Length", str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(("127.0.0.1", 0), UnsafeCatalogFailureHandler)
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
                client.request("GET", "/catalog-failure")
            error = caught.exception
            self.assertNotIn("catalog_cause_code", error.data)
            self.assertNotIn("catalog_product_code", error.data)
            self.assertNotIn("catalog_stage", error.data)
            self.assertNotIn("secretvalue", str(error))
            self.assertNotIn("/home/example", str(error))
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

    def test_http_errors_map_only_exact_catalog_runtime_messages(self) -> None:
        cases: dict[str, tuple[str, str]] = {}
        for index, (message, cause) in enumerate(
            DEPLOY.CATALOG_RUNTIME_MESSAGE_CAUSE.items()
        ):
            cases[f"/runtime-{index}"] = (message, cause)
            cases[f"/recovery-{index}"] = (
                DEPLOY.CATALOG_RECOVERY_MESSAGE_PREFIX + message,
                cause,
            )
        readback_cause = (
            "complete99_live_catalog_strict_readback_store_configuration_mismatch"
        )
        cases["/recovery-readback"] = (
            DEPLOY.CATALOG_RECOVERY_MESSAGE_PREFIX
            + readback_cause
            + ": Strict public catalog readback failed.",
            readback_cause,
        )

        class RuntimeCatalogFailureHandler(BaseHTTPRequestHandler):
            def do_GET(self) -> None:  # noqa: N802
                message, _cause = cases[self.path]
                code = (
                    "complete99_live_catalog_recovery_required"
                    if self.path.startswith("/recovery-")
                    else "complete99_live_catalog_apply_failed"
                )
                body = json.dumps(
                    {
                        "code": code,
                        "message": message,
                        "data": {
                            "status": 500,
                            "catalog_stage": "secret_token",
                            "catalog_cause_code": "complete99_live_catalog_secretvalue",
                            "catalog_product_code": "product-secretvalue",
                        },
                    }
                ).encode()
                self.send_response(500)
                self.send_header("Content-Type", "application/json")
                self.send_header("Content-Length", str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(
            ("127.0.0.1", 0),
            RuntimeCatalogFailureHandler,
        )
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
            for path, (message, cause) in cases.items():
                with self.subTest(path=path):
                    with self.assertRaises(DEPLOY.HTTPDeployError) as caught:
                        client.request("GET", path)
                    error = caught.exception
                    self.assertEqual(cause, error.data["catalog_cause_code"])
                    self.assertEqual(
                        DEPLOY.CATALOG_CAUSE_STAGE[cause],
                        error.data["catalog_stage"],
                    )
                    self.assertNotIn("catalog_product_code", error.data)
                    self.assertNotIn("secretvalue", str(error))
                    self.assertNotIn(message, str(error))
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

    def test_http_errors_drop_conflicting_catalog_diagnostics(self) -> None:
        class ConflictingCatalogFailureHandler(BaseHTTPRequestHandler):
            def do_GET(self) -> None:  # noqa: N802
                body = json.dumps(
                    {
                        "code": "complete99_live_catalog_apply_failed",
                        "message": (
                            "complete99_live_catalog_asset_upload_failed: "
                            "product-tahini-500g"
                        ),
                        "data": {
                            "status": 500,
                            "catalog_cause_code": "complete99_live_catalog_tax_api_missing",
                            "catalog_product_code": "product-amba-500g",
                        },
                    }
                ).encode()
                self.send_response(500)
                self.send_header("Content-Type", "application/json")
                self.send_header("Content-Length", str(len(body)))
                self.end_headers()
                self.wfile.write(body)

            def log_message(self, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(
            ("127.0.0.1", 0),
            ConflictingCatalogFailureHandler,
        )
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
            self.assertNotIn("catalog_cause_code", error.data)
            self.assertNotIn("catalog_stage", error.data)
            self.assertNotIn("catalog_product_code", error.data)
            self.assertNotIn("product-tahini-500g", str(error))
            self.assertNotIn("product-amba-500g", str(error))
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

    def test_robots_bridge_is_journaled_atomic_and_rollback_safe(self) -> None:
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )
        capture = bridge.split("$capture_robots_snapshot", 1)[1].split(
            "$apply_managed_robots",
            1,
        )[0]
        apply = bridge.split("$apply_managed_robots", 1)[1].split(
            "$restore_managed_robots",
            1,
        )[0]
        restore = bridge.split("$restore_managed_robots", 1)[1].split(
            "$reapply_managed_robots",
            1,
        )[0]
        status = bridge.split("$route_prefix . '/status'", 1)[1].split(
            "$route_prefix . '/stabilize'",
            1,
        )[0]
        stabilize = bridge.split("$route_prefix . '/stabilize'", 1)[1].split(
            "$route_prefix . '/rollback'",
            1,
        )[0]
        rollback = bridge.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/finalize'",
            1,
        )[0]
        finalize = bridge.split("$route_prefix . '/finalize'", 1)[1]

        self.assertIn("realpath( ABSPATH )", bridge)
        self.assertIn("DIRECTORY_SEPARATOR . 'robots.txt'", bridge)
        self.assertIn("is_link( $path ) || is_dir( $path )", capture)
        self.assertIn("$size > 65536", capture)
        self.assertIn("hash( 'sha256', $contents )", capture)
        self.assertIn("base64_encode( $contents )", capture)
        self.assertIn("'c99_robots_journal_readback'", bridge)
        self.assertIn("base64_decode( $persisted_robots_base64, true )", bridge)
        self.assertIn("strlen( $persisted_robots_bytes ) <= 65536", bridge)
        self.assertLess(
            bridge.index("'c99_robots_journal_readback'"),
            bridge.index("$upgrader->install("),
        )
        self.assertNotIn("'robots_prior_base64'", status)

        self.assertIn("'c99_robots_conflict'", apply)
        self.assertIn("file_put_contents( $temp, $managed, LOCK_EX )", apply)
        self.assertIn("@rename( $path, $prior_live )", apply)
        self.assertIn("@rename( $temp, $path )", apply)
        self.assertIn("@hash_file( 'sha256', $path )", apply)
        self.assertLess(
            apply.index("'c99_robots_conflict'"),
            apply.index("'already_applied' => true"),
        )
        self.assertLess(
            apply.index("@rename( $temp, $path )"),
            apply.rindex("@hash_file( 'sha256', $path )"),
        )

        self.assertIn("base64_decode(", restore)
        self.assertIn("hash( 'sha256', $prior )", restore)
        self.assertIn("@rename( $path, $forward )", restore)
        self.assertIn("@rename( $temp, $path )", restore)
        self.assertIn("$restore_managed_robots( $state_dir, $state )", rollback)
        self.assertIn("$reapply_managed_robots( $state_dir, $state )", rollback)
        self.assertLess(
            rollback.index("$restore_managed_robots( $state_dir, $state )"),
            rollback.index("$restore_database_state( $database_snapshot )"),
        )
        self.assertGreaterEqual(status.count("$robots_forward_ready"), 1)
        self.assertGreaterEqual(stabilize.count("$robots_forward_ready"), 1)
        self.assertIn("hash_equals( $managed_robots_sha256, $current_robots_sha256 )", stabilize)
        self.assertIn("'committed_expected_robots_sha256'", finalize)
        self.assertIn("'c99_finalize_robots_forward'", finalize)
        self.assertIn("'c99_finalize_robots_rollback'", finalize)
        self.assertIn(
            "hash_equals( $commit_identity['committed_expected_robots_sha256'], $current_robots_sha256 )",
            finalize,
        )

    def test_public_robots_verification_is_exact_anonymous_and_bounded(self) -> None:
        class RobotsHandler(BaseHTTPRequestHandler):
            status = 200
            body = b""
            authorization_headers: list[str | None] = []

            def do_GET(self) -> None:
                type(self).authorization_headers.append(
                    self.headers.get("Authorization")
                )
                if self.path != "/robots.txt":
                    self.send_response(404)
                    self.end_headers()
                    return
                self.send_response(type(self).status)
                self.send_header("Content-Type", "text/plain")
                self.send_header("Content-Length", str(len(type(self).body)))
                self.end_headers()
                self.wfile.write(type(self).body)

            def log_message(self, _format: str, *_args: object) -> None:
                return

        server = ThreadingHTTPServer(("127.0.0.1", 0), RobotsHandler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            client = DEPLOY.Client(
                f"http://127.0.0.1:{server.server_port}",
                "local-admin",
                "local-test-only",
                allow_local_http=True,
            )
            managed = DEPLOY.expected_managed_robots(client)
            managed_sha256 = hashlib.sha256(managed).hexdigest()
            RobotsHandler.status = 200
            RobotsHandler.body = managed
            evidence = DEPLOY.verify_managed_robots(client, managed_sha256)
            self.assertEqual(
                {
                    "sha256": managed_sha256,
                    "status": 200,
                },
                evidence,
            )

            prior = b"User-agent: *\nDisallow: /private/\n"
            prior_sha256 = hashlib.sha256(prior).hexdigest()
            RobotsHandler.body = prior
            restored = DEPLOY.verify_prior_robots(client, True, prior_sha256)
            self.assertEqual(prior_sha256, restored["sha256"])

            RobotsHandler.status = 404
            RobotsHandler.body = b"not found"
            absent = DEPLOY.verify_prior_robots(client, False, "")
            self.assertEqual(
                {"sha256": "", "status": 404},
                absent,
            )

            RobotsHandler.status = 200
            RobotsHandler.body = managed + b" "
            with self.assertRaisesRegex(
                DEPLOY.DeployError,
                "exactly match",
            ):
                DEPLOY.verify_managed_robots(client, managed_sha256)
            self.assertEqual(
                {
                    "already_restored": False,
                    "not_managed": False,
                    "response_recovered": False,
                    "restored": True,
                },
                DEPLOY.robots_restore_audit(
                    {"restored": True, "robots_prior_base64": "must-not-leak"}
                ),
            )
            self.assertTrue(RobotsHandler.authorization_headers)
            self.assertEqual(
                {None},
                set(RobotsHandler.authorization_headers),
            )
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=5)

    def test_deployer_verifies_robots_across_install_rollback_and_redeploy(self) -> None:
        deployer = (ROOT / "scripts" / "deploy-wordpress.py").read_text(
            encoding="utf-8"
        )
        install_flow = deployer.split('audit["install"] = {', 1)[1].split(
            "if args.rollback_exercise",
            1,
        )[0]
        rollback_flow = deployer.split("if args.rollback_exercise:", 1)[1].split(
            'gate = "finalize"',
            1,
        )[0]
        redeploy_flow = deployer.split('audit["install_after_exercise"] = {', 1)[
            1
        ].split('audit["finalize"]', 1)[0]
        failure_flow = deployer.split('audit["failure_rollback"] = {', 1)[1]

        self.assertLess(
            install_flow.index('audit["robots"] = verify_managed_robots('),
            install_flow.index('audit["health"] = verify_health('),
        )
        self.assertIn('audit["rollback_robots"] = verify_prior_robots(', rollback_flow)
        self.assertIn(
            'audit["robots_after_exercise"] = verify_managed_robots(',
            redeploy_flow,
        )
        self.assertIn(
            'audit["failure_rollback_robots"] = verify_prior_robots(',
            failure_flow,
        )
        self.assertIn('request_anonymous_bytes("/robots.txt", expected=(200,))', deployer)
        self.assertIn('request_anonymous_bytes("/robots.txt", expected=(404,))', deployer)
        self.assertIn('"robots_restore": robots_restore_audit(', deployer)
        self.assertNotIn('"Authorization": self.authorization', deployer.split(
            "def request_anonymous_bytes",
            1,
        )[1].split("def request_public_json", 1)[0])

    def test_post_migration_stabilization_precedes_health_and_can_recover_forward(
        self,
    ) -> None:
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )
        deployer = (ROOT / "scripts" / "deploy-wordpress.py").read_text(
            encoding="utf-8"
        )
        recovery = (ROOT / "scripts" / "recover-wordpress.py").read_text(
            encoding="utf-8"
        )

        self.assertIn("$route_prefix . '/stabilize'", bridge)
        self.assertIn("'current_database_version'", bridge)
        stabilize = bridge.split("$route_prefix . '/stabilize'", 1)[1].split(
            "$route_prefix . '/rollback'", 1
        )[0]
        self.assertIn(
            "array( 'installed', 'installed_pending_stabilization', 'installed_pending_cleanup' )",
            stabilize,
        )
        self.assertNotIn(
            "array( 'installed', 'failed', 'rollback_failed' )",
            stabilize,
        )
        self.assertIn("'c99_stabilize_forward_mismatch'", stabilize)
        self.assertIn("Complete99_Platform::migration_failed()", stabilize)
        for diagnostic_key in (
            "runtime_loaded",
            "runtime_version",
            "migration_failed",
            "plugin_digest_match",
            "plugin_header_match",
            "database_version_match",
            "plugin_active",
            "database_error",
            "current_version",
            "current_database_version",
            "retryable_forward_mismatch",
        ):
            self.assertIn(f"'{diagnostic_key}'", stabilize)
        self.assertIn(
            "wp_opcache_invalidate_directory( $target_dir )",
            stabilize,
        )
        self.assertIn("'c99_stabilize_idempotency_conflict'", stabilize)
        self.assertIn("'c99_stabilize_swap_artifacts'", stabilize)
        self.assertIn(
            "update_option( 'complete99_last_deployment_id', $deployment_id, false )",
            stabilize,
        )
        self.assertIn("'post_install_database_fingerprint'", stabilize)
        self.assertLess(
            stabilize.index(
                "update_option( 'complete99_last_deployment_id', $deployment_id, false )"
            ),
            stabilize.index("$post_install_snapshot = $capture_database_state()"),
        )

        install_flow = deployer.split(
            'audit["install"] = {', 1
        )[1].split("if args.rollback_exercise", 1)[0]
        self.assertLess(
            install_flow.index('gate = "stabilize"'),
            install_flow.index('gate = "health"'),
        )
        self.assertIn(
            'audit["stabilize"] = stabilize_deployment(',
            install_flow,
        )
        self.assertIn(
            'status.get("forward_stabilization_candidate")',
            recovery,
        )
        self.assertIn(
            'audit["decision"] = "stabilize_completed_forward_migration"',
            recovery,
        )
        rollback = bridge.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/finalize'",
            1,
        )[0]
        self.assertIn(
            "array( 'installed', 'installed_pending_stabilization', "
            "'installed_pending_cleanup', 'failed', 'rollback_failed', "
            "'commit_failed' )",
            rollback,
        )
        self.assertIn('"installed_pending_cleanup"', deployer)
        self.assertIn('"installed_pending_stabilization"', deployer)
        self.assertIn("recover_forward_candidate(", recovery)
        self.assertIn(
            '"rollback_failed_forward_stabilization"',
            recovery,
        )
        install_upgrade = bridge.split(
            "$result   = $upgrader->install(",
            1,
        )[1].split("$post_install_snapshot = $capture_database_state()", 1)[0]
        self.assertLess(
            install_upgrade.index(
                "wp_opcache_invalidate_directory( $target_dir )"
            ),
            install_upgrade.index(
                "$data = get_plugin_data( $plugin_path, false, false )"
            ),
        )
        self.assertLess(
            install_upgrade.index("clearstatcache( true, $plugin_path )"),
            install_upgrade.index(
                "$data = get_plugin_data( $plugin_path, false, false )"
            ),
        )
        self.assertIn('audit["failure_context"] = error.data', deployer)

        class StabilizeClient:
            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                if method != "POST":
                    raise AssertionError((method, path, expected))
                if payload != {
                    "token": "temporary-token",
                    "deployment_id": "c99-prod-stabilize-1234",
                }:
                    raise AssertionError(payload)
                if path.endswith("/c99-prod-stabilize-1234/stabilize"):
                    return 200, {
                        "cache_purge": {"object_cache_flushed": True},
                        "database_version": "1.1.1",
                        "deployment_id": "c99-prod-stabilize-1234",
                        "installed_plugin_sha256": "a" * 64,
                        "post_install_database_fingerprint": "b" * 64,
                        "stabilized": True,
                        "stabilized_from_phase": "installed_pending_stabilization",
                        "version": "1.1.1",
                    }
                if path.endswith("/c99-prod-stabilize-1234/status"):
                    return 200, {
                        "current_active": True,
                        "current_database_version": "1.1.1",
                        "current_deployment": "c99-prod-stabilize-1234",
                        "current_plugin_sha256": "a" * 64,
                        "current_version": "1.1.1",
                        "database_fingerprint": "b" * 64,
                        "installed_plugin_sha256": "a" * 64,
                        "phase": "installed",
                        "post_install_database_fingerprint": "b" * 64,
                        "stabilized": True,
                    }
                raise AssertionError((method, path, expected))

        result = DEPLOY.stabilize_deployment(
            StabilizeClient(),
            "temporary-token",
            "c99-prod-stabilize-1234",
            "1.1.1",
            "a" * 64,
        )
        self.assertTrue(result["stabilized"])
        self.assertEqual(1, result["stabilization_attempts"])
        self.assertEqual({}, result["initial_failure_context"])
        self.assertEqual("b" * 64, result["post_install_database_fingerprint"])
        self.assertEqual(
            "installed_pending_stabilization",
            result["stabilized_from_phase"],
        )
        self.assertIn("'c99_finalize_unstabilized'", bridge)

    def test_stabilization_retries_one_safe_forward_mismatch_only(self) -> None:
        forward_response = {
            "cache_purge": {"object_cache_flushed": True},
            "database_version": "1.3.0",
            "deployment_id": "c99-prod-stabilize-retry",
            "installed_plugin_sha256": "a" * 64,
            "post_install_database_fingerprint": "b" * 64,
            "stabilized": True,
            "stabilized_from_phase": "installed_pending_stabilization",
            "version": "1.3.0",
        }
        status_response = {
            "current_active": True,
            "current_database_version": "1.3.0",
            "current_deployment": "c99-prod-stabilize-retry",
            "current_plugin_sha256": "a" * 64,
            "current_version": "1.3.0",
            "database_fingerprint": "b" * 64,
            "installed_plugin_sha256": "a" * 64,
            "phase": "installed",
            "post_install_database_fingerprint": "b" * 64,
            "stabilized": True,
        }
        retryable = DEPLOY.HTTPDeployError(
            "retryable forward mismatch",
            status=409,
            code="c99_stabilize_forward_mismatch",
            data={"retryable_forward_mismatch": True},
        )

        with mock.patch.object(
            DEPLOY,
            "bridge_call",
            side_effect=[retryable, forward_response, status_response],
        ) as bridge_call, mock.patch.object(DEPLOY.time, "sleep") as sleep:
            result = DEPLOY.stabilize_deployment(
                object(),
                "temporary-token",
                "c99-prod-stabilize-retry",
                "1.3.0",
                "a" * 64,
            )

        self.assertTrue(result["stabilized"])
        self.assertEqual(2, result["stabilization_attempts"])
        self.assertEqual(
            {"retryable_forward_mismatch": True},
            result["initial_failure_context"],
        )
        self.assertEqual(
            ["stabilize", "stabilize", "status"],
            [call.args[1] for call in bridge_call.call_args_list],
        )
        sleep.assert_called_once_with(2)

        non_retryable = DEPLOY.HTTPDeployError(
            "non-retryable forward mismatch",
            status=409,
            code="c99_stabilize_forward_mismatch",
            data={"retryable_forward_mismatch": False},
        )
        with mock.patch.object(
            DEPLOY,
            "bridge_call",
            side_effect=non_retryable,
        ) as no_retry_call, mock.patch.object(
            DEPLOY,
            "poll_deployment_status",
            return_value={"phase": "failed"},
        ), mock.patch.object(DEPLOY.time, "sleep") as no_sleep:
            with self.assertRaises(DEPLOY.HTTPDeployError):
                DEPLOY.stabilize_deployment(
                    object(),
                    "temporary-token",
                    "c99-prod-stabilize-no-retry",
                    "1.3.0",
                    "a" * 64,
                )

        self.assertEqual(1, no_retry_call.call_count)
        no_sleep.assert_not_called()

    def test_pending_forward_phases_retry_rollback_without_stale_lease(self) -> None:
        for phase in (
            "installed_pending_stabilization",
            "installed_pending_cleanup",
        ):
            with self.subTest(phase=phase):
                bridge_responses = [
                    DEPLOY.DeployError("injected lost rollback response"),
                    {
                        "rolled_back": True,
                        "database_restore": {"restored": True},
                    },
                ]
                with mock.patch.object(
                    DEPLOY,
                    "bridge_call",
                    side_effect=bridge_responses,
                ) as bridge_call, mock.patch.object(
                    DEPLOY,
                    "poll_deployment_status",
                    return_value={"phase": phase},
                ) as poll_status:
                    result = DEPLOY.rollback_with_recovery(
                        object(),
                        "temporary-token",
                        "c99-prod-pending-rollback",
                    )

                self.assertTrue(result["rolled_back"])
                self.assertTrue(result["database_restore"]["restored"])
                self.assertEqual(2, bridge_call.call_count)
                poll_status.assert_called_once()

    def test_failed_pending_stabilization_rolls_back_and_verifies_prior_state(
        self,
    ) -> None:
        for phase in (
            "installed_pending_stabilization",
            "installed_pending_cleanup",
        ):
            with self.subTest(phase=phase):
                calls: list[str] = []

                def stabilization_failure(*_args: object) -> dict[str, object]:
                    calls.append("stabilize")
                    raise DEPLOY.DeployError("injected stabilization failure")

                fake_deployer = types.SimpleNamespace(
                    DeployError=DEPLOY.DeployError,
                    stabilize_deployment=stabilization_failure,
                    rollback_with_recovery=lambda *_args: (
                        calls.append("rollback")
                        or {
                            "rolled_back": True,
                            "database_restore": {"restored": True},
                            "had_plugin": True,
                            "prior_active": True,
                            "prior_version": "1.2.1",
                            "prior_deployment": "c99-prod-prior-1234",
                        }
                    ),
                    verify_rollback_integrity=lambda *_args: (
                        calls.append("rollback_integrity")
                        or {"database_restored": True}
                    ),
                    verify_health=lambda *_args: (
                        calls.append("prior_health") or {"status": "ok"}
                    ),
                    verify_rendered_home=lambda *_args: (
                        calls.append("prior_rendered_home")
                        or {"deployment_id": "c99-prod-prior-1234"}
                    ),
                    verify_inactive_plugin=lambda *_args: (
                        calls.append("prior_inactive_plugin")
                        or {"plugin_status": "inactive"}
                    ),
                    verify_plugin_absent=lambda *_args: (
                        calls.append("prior_absence") or {"plugin_absent": True}
                    ),
                    finalize_deployment=lambda *_args: (
                        calls.append("finalize") or {"finalized": True}
                    ),
                )
                audit: dict[str, object] = {}
                RECOVER.recover_forward_candidate(
                    fake_deployer,
                    object(),
                    "temporary-token",
                    "c99-prod-pending-recovery",
                    {
                        "phase": phase,
                        "expected_version": "1.3.0",
                        "installed_plugin_sha256": "a" * 64,
                    },
                    audit,
                )

                self.assertEqual(
                    [
                        "stabilize",
                        "rollback",
                        "rollback_integrity",
                        "prior_health",
                        "prior_rendered_home",
                        "finalize",
                    ],
                    calls,
                )
                self.assertEqual(
                    {
                        "error": "DeployError",
                        "phase": phase,
                    },
                    audit["stabilization_failure"],
                )
                self.assertEqual(
                    "rollback_failed_forward_stabilization",
                    audit["decision"],
                )
                self.assertTrue(audit["rollback"]["rolled_back"])
                self.assertTrue(audit["finalize"]["finalized"])

    def test_failed_stabilization_refuses_unconfirmed_rollback(self) -> None:
        fake_deployer = types.SimpleNamespace(
            DeployError=DEPLOY.DeployError,
            stabilize_deployment=lambda *_args: (_ for _ in ()).throw(
                DEPLOY.DeployError("injected stabilization failure")
            ),
            rollback_with_recovery=lambda *_args: {
                "rolled_back": True,
                "database_restore": {},
            },
        )
        audit: dict[str, object] = {}

        with self.assertRaisesRegex(
            DEPLOY.DeployError,
            "Recovery rollback was not confirmed",
        ):
            RECOVER.recover_forward_candidate(
                fake_deployer,
                object(),
                "temporary-token",
                "c99-prod-pending-recovery",
                {
                    "phase": "installed_pending_stabilization",
                    "expected_version": "1.3.0",
                    "installed_plugin_sha256": "a" * 64,
                },
                audit,
            )
        self.assertNotIn("finalize", audit)
        self.assertNotIn("decision", audit)

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

    def test_sync_bootstrap_is_secret_store_only_checkpointed_and_rollback_safe(
        self,
    ) -> None:
        workflow = (
            ROOT / ".github" / "workflows" / "wordpress-deploy.yml"
        ).read_text(encoding="utf-8")
        deployer = (ROOT / "scripts" / "deploy-wordpress.py").read_text(
            encoding="utf-8"
        )
        recovery = (ROOT / "scripts" / "recover-wordpress.py").read_text(
            encoding="utf-8"
        )
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(
            encoding="utf-8"
        )

        self.assertIn(
            "COMPLETE99_WORDPRESS_SYNC_SECRET: "
            "${{ secrets.COMPLETE99_WORDPRESS_SYNC_SECRET }}",
            workflow,
        )
        self.assertIn(
            "[string]::IsNullOrEmpty($env:COMPLETE99_WORDPRESS_SYNC_SECRET)",
            workflow,
        )
        self.assertIn(
            'os.environ.get("COMPLETE99_WORDPRESS_SYNC_SECRET", "")',
            deployer,
        )
        self.assertNotIn('--sync-secret', deployer)
        self.assertNotIn('"sync_value":', deployer)
        self.assertIn('gate = "configure-sync"', deployer)
        self.assertIn("require_sync_configured=bool(sync_value)", deployer)
        self.assertIn(
            'and not status.get("sync_configuration_pending")',
            recovery,
        )

        configure_route = bridge.split(
            "$route_prefix . '/configure-sync'", 1
        )[1].split("$route_prefix . '/retire'", 1)[0]
        rollback_route = bridge.split("$route_prefix . '/rollback'", 1)[
            1
        ].split("$route_prefix . '/finalize'", 1)[0]
        capture = bridge.split("$capture_database_state", 1)[1].split(
            "$encrypt_database_state", 1
        )[0]
        restore = bridge.split("$restore_database_state", 1)[1].split(
            "$auto_update_enabled", 1
        )[0]

        self.assertIn("hash_equals( $current_value, $provided_secret )", configure_route)
        self.assertIn("'c99_sync_rotation_refused'", configure_route)
        self.assertIn("'sync_configuration_pending'", configure_route)
        self.assertLess(
            configure_route.index("'sync_configuration_pending'"),
            configure_route.index("$write_result ="),
        )
        self.assertIn("'sync_secret_existed'", capture)
        self.assertIn("'sync_secret_configured'", capture)
        self.assertNotIn(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            capture,
        )
        self.assertIn("$snapshot['sync_secret_configured']", restore)
        self.assertIn("'sync_secret_empty_restore'", restore)
        self.assertIn("'sync_secret_empty_insert'", restore)
        self.assertIn("'sync_secret_delete'", restore)
        self.assertIn("$pending_sync_fingerprint", rollback_route)

        initial_flow = deployer.split(
            'audit["stabilize"] = stabilize_deployment(', 1
        )[1].split("if args.rollback_exercise:", 1)[0]
        final_redeploy_flow = deployer.split(
            'audit["stabilize_after_exercise"] = stabilize_deployment(', 1
        )[1].split('audit["finalize"]', 1)[0]
        self.assertIn("if sync_value and not args.rollback_exercise:", initial_flow)
        self.assertIn(
            'audit["sync_configuration_after_exercise"] = configure_sync(',
            final_redeploy_flow,
        )

    def test_production_consumes_exact_ci_artifact_and_recovery_is_mutation_gated(
        self,
    ) -> None:
        workflow = (
            ROOT / ".github" / "workflows" / "wordpress-deploy.yml"
        ).read_text(encoding="utf-8")
        ci = (ROOT / ".github" / "workflows" / "wordpress-ci.yml").read_text(
            encoding="utf-8"
        )
        deployer = (ROOT / "scripts" / "deploy-wordpress.py").read_text(
            encoding="utf-8"
        )

        self.assertIn("actions/download-artifact@", workflow)
        self.assertIn(
            "run-id: ${{ needs.require-green-ci.outputs.ci_run_id }}",
            workflow,
        )
        self.assertIn(
            "head_sha=${GITHUB_SHA}&branch=main&status=success",
            workflow,
        )
        self.assertIn('run.get("event") == "push"', workflow)
        self.assertNotIn("build-plugin-zip.py", workflow)
        self.assertIn("Prepare the exact validated release bundle", ci)
        self.assertIn(
            "test -z \"$(git ls-files --others --exclude-standard -- plugin-dist)\"",
            ci,
        )

        immutable_validation = workflow.split(
            "- name: Validate the downloaded immutable release", 1
        )[1].split(
            "- name: Require the secure sync bootstrap before any live request",
            1,
        )[0]
        self.assertGreaterEqual(
            immutable_validation.count(
                "if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }"
            ),
            4,
        )
        self.assertIn("validate-package.py --dist $releaseDir", immutable_validation)

        preflight = workflow.split(
            "- name: Run live recovery probe and dry-run acceptance", 1
        )[1].split(
            "- name: Deploy the exact CI artifact with independent verification",
            1,
        )[0]
        self.assertNotIn("--mutation-marker", preflight)
        self.assertIn("--dry-run", preflight)
        self.assertIn("--dist $releaseDir", preflight)

        production = workflow.split(
            "- name: Deploy the exact CI artifact with independent verification",
            1,
        )[1].split(
            "- name: Detect whether the production mutation edge was crossed",
            1,
        )[0]
        self.assertIn("--mutation-marker $mutationMarker", production)
        self.assertIn("--rollback-exercise", production)
        self.assertIn(
            "COMPLETE99_WORDPRESS_SYNC_SECRET: "
            "${{ secrets.COMPLETE99_WORDPRESS_SYNC_SECRET }}",
            production,
        )

        recovery = workflow.split(
            "- name: Recover any interrupted mutation with a recreated temporary bridge",
            1,
        )[1].split(
            "- name: Remove the runner-local mutation marker",
            1,
        )[0]
        self.assertIn(
            "if: failure() && steps.mutation_state.outputs.started == 'true'",
            recovery,
        )
        self.assertNotIn("c99-dry-", recovery)
        self.assertIn("c99-prod-", recovery)

        main_flow = deployer.split("def main() -> int:", 1)[1]
        ensure = main_flow.index(
            "ensure_code_snippets(client, args.bootstrap_code_snippets)"
        )
        arm = main_flow.index(
            "arm_live_mutation_recovery(args.mutation_marker, deployment_id)"
        )
        create = main_flow.index("snippet_creation_attempted = True")
        self.assertLess(ensure, arm)
        self.assertLess(arm, create)

        with tempfile.TemporaryDirectory() as temp:
            marker = Path(temp) / "mutation.marker"
            DEPLOY.arm_live_mutation_recovery(marker, "c99-prod-test-1234")
            self.assertEqual("c99-prod-test-1234\n", marker.read_text(encoding="ascii"))
            with self.assertRaisesRegex(
                DEPLOY.DeployError,
                "could not be armed safely",
            ):
                DEPLOY.arm_live_mutation_recovery(marker, "c99-prod-test-1234")

    def test_sync_bootstrap_driver_never_returns_the_credential(self) -> None:
        sync_value = "S" * 48

        class SyncClient:
            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                if method != "POST" or payload is None:
                    raise AssertionError((method, path, expected))
                if payload.get("token") != "temporary-token":
                    raise AssertionError(payload)
                if payload.get("deployment_id") != "c99-prod-sync-1234":
                    raise AssertionError(payload)
                if path.endswith("/c99-prod-sync-1234/configure-sync"):
                    if payload.get("sync_secret") != sync_value:
                        raise AssertionError("credential was not delivered exactly")
                    return 200, {
                        "configured": True,
                        "changed": True,
                        "idempotent": False,
                        "database_fingerprint": "c" * 64,
                    }
                if path.endswith("/c99-prod-sync-1234/status"):
                    self.assert_no_credential(payload)
                    return 200, {
                        "phase": "installed",
                        "stabilized": True,
                        "current_sync_configured": True,
                        "sync_configuration_checkpointed": True,
                        "sync_configuration_pending": False,
                        "database_fingerprint": "c" * 64,
                        "post_install_database_fingerprint": "c" * 64,
                    }
                raise AssertionError((method, path, expected))

            @staticmethod
            def assert_no_credential(payload: dict[str, object]) -> None:
                if "sync_secret" in payload:
                    raise AssertionError("credential escaped into status request")

        result = DEPLOY.configure_sync(
            SyncClient(),
            "temporary-token",
            "c99-prod-sync-1234",
            sync_value,
        )
        self.assertTrue(result["configured"])
        self.assertTrue(result["changed"])
        self.assertNotIn(sync_value, json.dumps(result))
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.configure_sync(
                SyncClient(),
                "temporary-token",
                "c99-prod-sync-1234",
                "too-short",
            )

    def test_workflow_is_manual_and_1_3_1_requires_rollback_exercise(self) -> None:
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
        self.assertIn("default: true", rollback_input)
        self.assertIn("default: true", bootstrap_input)
        rollback_guard = workflow.split(
            "- name: Require the 1.3.1 rollback and identical-artifact redeploy exercise",
            1,
        )[1].split(
            "- name: Require the secure sync bootstrap before any live request",
            1,
        )[0]
        self.assertIn(
            '$releaseMetadata.version -eq "1.3.1"',
            rollback_guard,
        )
        self.assertIn(
            '"${{ inputs.rollback_exercise }}" -ne "true"',
            rollback_guard,
        )
        self.assertIn(
            "rollback_exercise=true before any production request",
            rollback_guard,
        )
        self.assertIn("recover-wordpress.py", workflow)
        self.assertIn("--discover", workflow)
        self.assertIn("WP_ALLOWED_DEPLOY_HOSTS", workflow)
        self.assertIn(
            "runs-on: [self-hosted, Windows, X64, complete99-deploy]",
            workflow,
        )
        deploy_job = workflow.split("  deploy:", 1)[1]
        checkout_block = deploy_job.split(
            "- name: Check out approved production source",
            1,
        )[1].split(
            "- name: Download the immutable exact-commit CI artifact",
            1,
        )[0]
        self.assertIn("fetch-depth: 1", checkout_block)
        self.assertNotIn("fetch-depth: 0", checkout_block)
        self.assertNotIn("actions/setup-python@", deploy_job)
        self.assertIn("python --version", deploy_job)
        self.assertIn("sys.version_info >= (3, 11)", deploy_job)
        self.assertGreaterEqual(workflow.count("shell: powershell"), 2)


if __name__ == "__main__":
    unittest.main()
