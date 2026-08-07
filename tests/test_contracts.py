from __future__ import annotations

import csv
import importlib.util
import json
import os
import re
import subprocess
import sys
import tempfile
import threading
import unittest
import zipfile
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
DEPLOY_SPEC = importlib.util.spec_from_file_location(
    "complete99_deploy_wordpress",
    ROOT / "scripts" / "deploy-wordpress.py",
)
assert DEPLOY_SPEC and DEPLOY_SPEC.loader
DEPLOY = importlib.util.module_from_spec(DEPLOY_SPEC)
sys.modules[DEPLOY_SPEC.name] = DEPLOY
DEPLOY_SPEC.loader.exec_module(DEPLOY)
BUILD_SPEC = importlib.util.spec_from_file_location(
    "complete99_build_plugin_zip",
    ROOT / "scripts" / "build-plugin-zip.py",
)
assert BUILD_SPEC and BUILD_SPEC.loader
BUILD = importlib.util.module_from_spec(BUILD_SPEC)
BUILD_SPEC.loader.exec_module(BUILD)
VALIDATE_SPEC = importlib.util.spec_from_file_location(
    "complete99_validate_package",
    ROOT / "scripts" / "validate-package.py",
)
assert VALIDATE_SPEC and VALIDATE_SPEC.loader
VALIDATE = importlib.util.module_from_spec(VALIDATE_SPEC)
VALIDATE_SPEC.loader.exec_module(VALIDATE)


class Complete99ContractTests(unittest.TestCase):
    def test_version_header_constant_and_deployment_id(self) -> None:
        text = (PLUGIN / "complete99-platform.php").read_text(encoding="utf-8")
        header = re.search(r"Version:\s*([0-9]+\.[0-9]+\.[0-9]+)", text)
        constant = re.search(r"COMPLETE99_PLATFORM_VERSION',\s*'([^']+)'", text)
        deployment = re.search(r"COMPLETE99_PLATFORM_DEPLOYMENT_ID',\s*'([^']+)'", text)
        self.assertIsNotNone(header)
        self.assertIsNotNone(constant)
        self.assertEqual(header.group(1), constant.group(1))
        self.assertEqual(f"c99-wp-{header.group(1)}", deployment.group(1))

    def test_update_checker_is_pinned_guarded_and_deferred(self) -> None:
        main = (PLUGIN / "complete99-platform.php").read_text(encoding="utf-8")
        platform = (PLUGIN / "includes" / "class-complete99-platform.php").read_text(encoding="utf-8")
        vendor = PLUGIN / "lib" / "plugin-update-checker"
        puc_main = (vendor / "plugin-update-checker.php").read_text(encoding="utf-8")
        loader = (vendor / "load-v5p6.php").read_text(encoding="utf-8")
        provenance = (vendor / "UPSTREAM.md").read_text(encoding="utf-8")
        self.assertIn("Plugin Update Checker Library 5.6", puc_main)
        self.assertIn("Puc/v5p6", loader)
        self.assertIn("a2db6871deec989a74e1f90fafc6d58ae526a879", provenance)
        self.assertTrue((vendor / "license.txt").is_file())
        self.assertIn("The-new-ben/complete99-wordpress/main/plugin-dist/complete99-platform.json", main)
        self.assertIn("array( __CLASS__, 'boot_update_checker' ), 5", platform)
        self.assertIn("file_exists( $loader )", platform)
        self.assertIn("class_exists( $factory )", platform)
        self.assertIn("catch ( \\Throwable $error )", platform)
        self.assertIn("PucFactory", platform)

    def test_checked_in_update_manifest_matches_plugin(self) -> None:
        main = (PLUGIN / "complete99-platform.php").read_text(encoding="utf-8")
        version = re.search(r"COMPLETE99_PLATFORM_VERSION',\s*'([^']+)'", main).group(1)
        manifest = json.loads((ROOT / "plugin-dist" / "complete99-platform.json").read_text(encoding="utf-8"))
        required = {
            "name",
            "slug",
            "version",
            "author",
            "homepage",
            "requires",
            "tested",
            "requires_php",
            "download_url",
            "last_updated",
            "sections",
        }
        self.assertTrue(required.issubset(manifest))
        self.assertEqual(version, manifest["version"])
        self.assertEqual("complete99-platform", manifest["slug"])
        self.assertEqual("7.0", manifest["tested"])
        self.assertEqual(
            f"https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/main/"
            f"plugin-dist/complete99-platform-{version}.zip",
            manifest["download_url"],
        )
        self.assertTrue(manifest["sections"]["changelog"])

    def test_release_1_13_0_manifest_describes_syrian_depth_and_cumulative_boundary(self) -> None:
        manifest = json.loads(
            (ROOT / "plugin-dist" / "complete99-platform.json").read_text(
                encoding="utf-8"
            )
        )
        changelog = manifest["sections"]["changelog"]
        self.assertIn("<h4>1.13.0</h4>", changelog)
        self.assertIn("culinary-science registry to 287 entities", changelog)
        self.assertIn("Entity Studio to 343 subjects", changelog)
        self.assertIn("86 private, source-bound Syrian regional-depth identities", changelog)
        self.assertIn("Syrian graph to 196 entities", changelog)
        self.assertIn("56 dishes, 55 ingredients", changelog)
        self.assertIn("All 87 new identities remain private, noindex and reference-only", changelog)
        self.assertIn("exact retail-listing identity", changelog)
        self.assertIn("active-offer readiness", changelog)
        self.assertIn("<h4>1.12.1</h4>", changelog)
        self.assertIn("direct consumer cooking language for kome koji", changelog)
        self.assertIn("<h4>1.12.0</h4>", changelog)
        self.assertIn("culinary-science registry to 200 entities", changelog)
        self.assertIn("Entity Studio to 256 subjects", changelog)
        self.assertIn("200 science identities plus 56 product identities", changelog)
        self.assertIn("36 live WooCommerce prices and 20 private planning prices", changelog)
        self.assertIn("109-entity Syrian regional foundation", changelog)
        self.assertIn("106 culinary entities, including 46 ingredient entities", changelog)
        self.assertIn("six preparation entities", changelog)
        self.assertIn("plus three private held market observations", changelog)
        self.assertIn("one safe noindex consumer gateway and 108 private entities", changelog)
        self.assertIn("23 entities across 18 canonical page owners per language", changelog)
        self.assertIn("added no WooCommerce offers, stock, supplier claims", changelog)
        self.assertIn("payment activation or role assignments", changelog)

    def test_all_required_content_types_exist(self) -> None:
        text = (PLUGIN / "includes" / "class-complete99-content.php").read_text(encoding="utf-8")
        required = {
            "c99_service",
            "c99_industry",
            "c99_platform_feature",
            "c99_dish",
            "c99_ingredient",
            "c99_location",
            "c99_guide",
            "c99_case_study",
            "c99_team_member",
        }
        for post_type in required:
            self.assertIn(f"'{post_type}'", text)
        self.assertIn("map_meta_cap", text)
        self.assertIn("complete99_food_editor", text)
        self.assertIn("complete99_location_manager", text)

    def test_launch_content_contract(self) -> None:
        text = (PLUGIN / "data" / "launch-content.php").read_text(encoding="utf-8")
        fixed_records = len(re.findall(r"\$records\[\]\s*=\s*array\(", text))
        service_rows = len(re.findall(r"^\s*array\(\s*'[^']+',\s*'[^']+',\s*'[^']+'", text, re.MULTILINE))
        # Fixed records + six service + four industry + six platform loops exceed 20 launch concepts.
        self.assertGreaterEqual(fixed_records, 9)
        self.assertGreaterEqual(service_rows, 16)
        self.assertIn("'key'     => 'app'", text)
        self.assertIn("'key'     => 'proposal'", text)

    def test_six_dish_drafts_are_proof_gated(self) -> None:
        text = (PLUGIN / "data" / "dish-seeds.php").read_text(encoding="utf-8")
        dishes = re.findall(r"^\s*array\(\s*'([a-z0-9-]+)',\s*'[^']+',\s*'[^']+',\s*'c99-[^']+'\s*\)", text, re.MULTILINE)
        self.assertEqual(6, len(dishes))
        self.assertIn("'status'       => 'draft'", text)
        self.assertIn("'verification' => 'verification_required'", text)
        self.assertIn("'sources'            => array()", text)
        self.assertIn("'authoritative_sources' => array()", text)
        self.assertIn("'nutrition_reviewed' => false", text)

    def test_dish_publication_gate_matches_editorial_standard(self) -> None:
        text = (PLUGIN / "includes" / "class-complete99-content.php").read_text(encoding="utf-8")
        for marker in (
            "DISH_MIN_WORDS_PER_LANGUAGE = 5000",
            "DISH_MIN_SOURCES            = 8",
            "DISH_MIN_AUTHORITATIVE      = 2",
            "_complete99_kitchen_reviewed",
            "_complete99_allergen_reviewed",
            "_complete99_image_approved",
            "_complete99_originality_reviewed",
            "_complete99_he_editor",
            "_complete99_en_editor",
            "enforce_dish_publication_gate",
        ):
            self.assertIn(marker, text)
        frontend = (PLUGIN / "includes" / "class-complete99-frontend.php").read_text(encoding="utf-8")
        self.assertIn("protect_unready_dishes", frontend)
        self.assertIn("dish_gate_status( $post->ID )['passed']", frontend)

    def test_no_reference_or_third_party_ordering_assets(self) -> None:
        approved_order_urls = (
            b"https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete",
            b"https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete",
        )
        for path in PLUGIN.rglob("*"):
            if not path.is_file():
                continue
            raw = path.read_bytes().lower()
            self.assertNotIn(b"assets/reference", raw, path)
            self.assertNotIn("wolt", path.name.casefold(), path)
            for approved_order_url in approved_order_urls:
                raw = raw.replace(approved_order_url, b"")
            self.assertNotIn(b"wolt.com", raw, path)

    def test_schema_is_truthfully_gated(self) -> None:
        text = (PLUGIN / "includes" / "class-complete99-frontend.php").read_text(encoding="utf-8")
        self.assertIn("'verified' !== (string) get_post_meta", text)
        self.assertIn("empty( $recipe['sources'] )", text)
        self.assertNotIn("aggregateRating", text)
        self.assertNotIn("'nutrition' =>", text)

    def test_sync_requires_hmac_timestamp_nonce_and_replay_cache(self) -> None:
        text = (PLUGIN / "includes" / "class-complete99-rest.php").read_text(encoding="utf-8")
        for marker in (
            "x-complete99-timestamp",
            "x-complete99-nonce",
            "x-complete99-signature",
            "hash_hmac",
            "hash_equals",
            "get_transient",
            "set_transient",
            "MAX_CLOCK_SKEW",
            "MAX_BODY_BYTES",
        ):
            self.assertIn(marker, text)
        health_block = text.split("function health", 1)[1].split("function verify_sync_signature", 1)[0]
        self.assertIn("'sync_configured'", health_block)
        self.assertNotIn("'sync_secret'", health_block)
        self.assertNotIn("'secret' =>", health_block)

    def test_leads_are_local_private_and_rate_limited(self) -> None:
        text = (PLUGIN / "includes" / "class-complete99-leads.php").read_text(encoding="utf-8")
        self.assertIn("'public'              => false", text)
        self.assertIn("'post_status' => 'private'", text)
        self.assertIn("wp_verify_nonce", text)
        self.assertIn("get_transient", text)
        self.assertIn("hash_hmac", text)
        self.assertNotIn("wp_mail(", text)

    def test_keyword_ownership_is_unique_per_language(self) -> None:
        path = PLUGIN / "data" / "keyword-ownership.csv"
        with path.open(encoding="utf-8", newline="") as handle:
            rows = list(csv.DictReader(handle))
        public_groups = {
            "home",
            "about",
            "contact",
            "dishes",
            "ingredients",
            "traditions",
            "knowledge",
            "store",
            "proposal",
            "privacy",
            "terms",
            "accessibility",
        }
        self.assertEqual(2 * len(public_groups), len(rows))
        self.assertEqual(public_groups, {row["translation_key"] for row in rows})
        ownership = {(row["language"], row["primary_intent"]) for row in rows}
        self.assertEqual(len(rows), len(ownership))
        for row in rows:
            self.assertTrue(row["canonical_path"].startswith("/"))
            self.assertTrue(row["prohibited_competing_pages"])
            self.assertTrue(row["evidence_gate"])
            self.assertIn(
                row["publication_status"],
                {
                    "launch",
                    "consumer",
                    "proof-gated",
                    "qualified-review",
                    "product-demo",
                },
            )

    def test_rendered_public_language_has_no_internal_delivery_terms(self) -> None:
        launch_path = (PLUGIN / "data" / "launch-content.php").as_posix().replace("'", "\\'")
        php = (
            "define('ABSPATH', __DIR__);"
            f"$records=require '{launch_path}';"
            "$public=[];"
            "foreach($records as $r){"
            "if(isset($r['status'])&&in_array($r['status'],['draft','private'],true)){continue;}"
            "foreach(['he','en'] as $lang){$public[]=$r['title'][$lang].' '.$r['excerpt'][$lang].' '.$r['content'][$lang];}"
            "}"
            "echo json_encode($public, JSON_UNESCAPED_UNICODE);"
        )
        result = subprocess.run(["php", "-r", php], check=True, capture_output=True, text=True, encoding="utf-8")
        rendered = " ".join(json.loads(result.stdout))
        consumer = (PLUGIN / "includes" / "class-complete99-consumer.php").read_text(encoding="utf-8")
        pairs = re.findall(r"\$is_he\s*\?\s*'([^']*)'\s*:\s*'([^']*)'", consumer)
        visible_frontend = " ".join(value for pair in pairs for value in pair)
        public_language = (rendered + " " + visible_frontend).lower()
        forbidden = (
            "demo",
            "draft",
            "project",
            "mvp",
            "placeholder",
            "prototype",
            "connector status",
            "institutional",
            "operations platform",
            "supplier",
            "worker assignment",
            "inventory management",
            "procurement",
            "campaign studio",
            "human resources",
            "bom",
            "הסעדה מוסדית",
            "פלטפורמת תפעול",
            "ניהול ספקים",
            "שיבוץ עובדים",
            "ניהול מלאי",
            "רכש",
            "סטודיו קמפיינים",
            "משאבי אנוש",
            "הדגמה",
            "טיוטה",
            "גרסת ייצור פנימית",
            "פרויקט",
            "אב טיפוס",
            "מציין מקום",
            "שלב פיתוח",
        )
        for phrase in forbidden:
            self.assertNotIn(phrase, public_language, phrase)
        self.assertIn("home cooking", public_language)
        self.assertIn("sabich", public_language)
        self.assertIn("אוכל ביתי", public_language)

    def test_bridge_obeys_transaction_and_cleanup_contract(self) -> None:
        text = (ROOT / "deploy" / "temporary-bridge.php").read_text(encoding="utf-8")
        self.assertIn("current_user_can( 'update_plugins' )", text)
        self.assertIn("hash_equals( $config['token']", text)
        self.assertIn("'direct' !== get_filesystem_method()", text)
        self.assertIn("hash( 'sha256', $bytes )", text)
        self.assertIn("$wp_filesystem->mkdir( $state_root, FS_CHMOD_DIR )", text)
        self.assertIn("$wp_filesystem->mkdir( $state_dir, FS_CHMOD_DIR )", text)
        self.assertNotIn("$wp_filesystem->mkdir( $state_dir, FS_CHMOD_DIR, true )", text)
        self.assertIn("'min_free_bytes'=> __C99_MIN_FREE_BYTES__", text)
        self.assertIn("'local_test'    => __C99_LOCAL_TEST__", text)
        self.assertIn("'recovery_lease_seconds'=> 240", text)
        self.assertIn("'c99_deploy_disk_space'", text)
        self.assertIn("'required_free_bytes'", text)
        self.assertIn("get_site_option( 'auto_update_plugins'", text)
        self.assertIn("'c99_deploy_auto_update_enabled'", text)
        self.assertIn("\\Upress\\EzCache\\Cache::instance()", text)
        self.assertIn("$cache->clear_cache()", text)
        self.assertIn("'temp_removed'", text)
        self.assertIn("'complete99_deploy_lock'", text)
        self.assertIn("$route_prefix . '/status'", text)
        self.assertIn("'database_journal' =>", text)
        self.assertIn("'aes-256-gcm'", text)
        self.assertIn("hash_hmac( 'sha256', $config['deployment_id'], wp_salt( 'auth' )", text)
        self.assertNotIn("hash( 'sha256', $config['token'], true )", text)
        self.assertIn("$verify_transactional_storage", text)
        self.assertIn("'c99_db_engine_nontransactional'", text)
        self.assertIn("'c99_deploy_journal_readback'", text)
        self.assertIn("'recovery_ready'", text)
        self.assertIn("@rename( $target_dir, $displaced_dir )", text)
        self.assertIn("@rename( $restore_stage, $target_dir )", text)
        self.assertIn("'sync_secret_existed'", text)
        self.assertIn("'sync_secret_configured'", text)
        self.assertIn("'.htaccess'", text)
        self.assertIn("'web.config'", text)
        capture_block = text.split("$capture_database_state", 1)[1].split(
            "$encrypt_database_state",
            1,
        )[0]
        self.assertNotIn("'complete99_sync_secret',", capture_block)
        self.assertIn("copy_dir( $target_dir, $backup_dir )", text)
        self.assertIn("$capture_robots_snapshot", text)
        self.assertIn("$apply_managed_robots", text)
        self.assertIn("$restore_managed_robots", text)
        self.assertIn("$reapply_managed_robots", text)
        self.assertIn("'robots_managed_sha256'", text)
        self.assertIn("'/stabilize'", text)
        self.assertIn("'/configure-sync'", text)
        self.assertIn("'/rollback'", text)
        self.assertIn("'/finalize'", text)
        configure = text.split("$route_prefix . '/configure-sync'", 1)[1].split(
            "$route_prefix . '/retire'",
            1,
        )[0]
        self.assertIn("'c99_sync_rotation_refused'", configure)
        self.assertIn("strlen( $provided_secret ) < 32", configure)
        self.assertIn("$request->get_json_params()", configure)
        self.assertIn("'c99_sync_configure_transport'", configure)
        self.assertIn("'sync_configuration_pending'", configure)
        self.assertIn("'sync_configuration_checkpointed'", configure)
        self.assertIn("$provided_secret", configure)
        self.assertNotIn("'provided_secret' =>", configure)
        self.assertNotIn("'sync_secret' => $provided_secret", configure)
        rollback = text.split("$route_prefix . '/rollback'", 1)[1].split(
            "$route_prefix . '/finalize'",
            1,
        )[0]
        self.assertIn("$pending_sync_fingerprint", rollback)
        self.assertIn("'sync_secret_configured'", rollback)
        finalize = text.split("$route_prefix . '/finalize'", 1)[1]
        self.assertIn("'c99_finalize_sync_pending'", finalize)
        self.assertIn("'c99_finalize_database_checkpoint'", finalize)
        privileged = text.split("add_action(", 1)[0]
        self.assertNotIn("Plugin_Upgrader", privileged)

    def test_deployer_proves_health_rollback_and_route_404(self) -> None:
        text = (ROOT / "scripts" / "deploy-wordpress.py").read_text(encoding="utf-8")
        for marker in (
            "verify_health",
            "verify_prior_health",
            "verify_plugin_absent",
            "stabilize_deployment",
            "finalize_deployment",
            "RejectRedirects",
            "find_active_snippet_ids",
            "remove_named_snippets",
            "rollback-exercise",
            "delete_snippet_and_prove_404",
            "verify_rollback_integrity",
            "verify_managed_robots",
            "verify_prior_robots",
            "request_anonymous_bytes",
            'expected=(404,)',
            "finally:",
            "ALLOWED_PRODUCTION_HOSTS",
            "ALLOWED_LOCAL_TEST_HOSTS",
            "--local-test",
            "clean HTTP loopback WordPress origin",
            "complete99.co.il",
        ):
            self.assertIn(marker, text)
        self.assertNotIn("raw.githubusercontent.com", text)
        self.assertNotIn("X-Complete99-Local-Authorization", text)
        self.assertIn('"Authorization": self.authorization', text)
        self.assertIn(r're.search(r"__C99_[A-Z0-9_]+__"', text)
        self.assertIn("request_public_json", text)
        self.assertIn("request_anonymous_html", text)
        recovery = (ROOT / "scripts" / "recover-wordpress.py").read_text(encoding="utf-8")
        self.assertIn("rollback_interrupted_mutation", recovery)
        self.assertIn("finish_committed_cleanup", recovery)
        self.assertIn("delete_snippet_and_prove_404", recovery)
        public_shell = (
            PLUGIN / "templates" / "public-shell.php"
        ).read_text(encoding="utf-8")
        self.assertIn('data-c99-version="', public_shell)
        self.assertIn('data-c99-deployment="', public_shell)

    def test_plugin_migrations_and_health_are_transactionally_gated(self) -> None:
        platform = (
            PLUGIN / "includes" / "class-complete99-platform.php"
        ).read_text(encoding="utf-8")
        health = (
            PLUGIN / "includes" / "class-complete99-rest.php"
        ).read_text(encoding="utf-8")
        self.assertIn("$wpdb->query( 'START TRANSACTION' )", platform)
        self.assertIn("$wpdb->query( 'COMMIT' )", platform)
        self.assertIn("$wpdb->query( 'ROLLBACK' )", platform)
        self.assertIn("'complete99_platform_version'", platform)
        self.assertIn("'complete99_migration_incomplete'", health)
        self.assertIn("'database_version'=> $database_version", health)

    def test_health_separates_migration_and_culinary_graph_failures(self) -> None:
        health = (
            PLUGIN / "includes" / "class-complete99-rest.php"
        ).read_text(encoding="utf-8")
        health_method = health.split("public static function health()", 1)[1].split(
            "public static function verify_sync_signature", 1
        )[0]
        graph_gate_start = health_method.index("if ( ( $science_loaded")
        response_start = health_method.index("return rest_ensure_response")
        migration_gate = health_method[:graph_gate_start]
        graph_gate = health_method[graph_gate_start:response_start]

        self.assertIn("Complete99_Platform::migration_failed()", migration_gate)
        self.assertIn("COMPLETE99_PLATFORM_VERSION !== $database_version", migration_gate)
        self.assertIn("'complete99_migration_incomplete'", migration_gate)
        self.assertNotIn("empty( $science['ready'] )", migration_gate)
        self.assertNotIn("empty( $commerce_graph['ready'] )", migration_gate)

        self.assertIn("empty( $science['ready'] )", graph_gate)
        self.assertIn("! $commerce_registry_valid", graph_gate)
        self.assertNotIn("empty( $commerce_graph['ready'] )", graph_gate)
        self.assertIn("'complete99_culinary_graph_unavailable'", graph_gate)
        self.assertIn(
            "'Complete99 culinary data is temporarily unavailable.'", graph_gate
        )
        self.assertNotIn("'complete99_migration_incomplete'", graph_gate)
        self.assertNotIn("database migration", graph_gate)

    def test_client_rejects_redirects_without_forwarding_authorization(self) -> None:
        class Collector(BaseHTTPRequestHandler):
            authorization_seen = False
            request_count = 0

            def do_GET(self) -> None:  # noqa: N802
                type(self).authorization_seen = bool(self.headers.get("Authorization"))
                type(self).request_count += 1
                self.send_response(200)
                self.send_header("Content-Type", "application/json")
                self.end_headers()
                self.wfile.write(b"{}")

            do_POST = do_GET

            def log_message(self, *_args: object) -> None:
                return

        collector = ThreadingHTTPServer(("127.0.0.1", 0), Collector)

        class Redirector(BaseHTTPRequestHandler):
            def redirect(self) -> None:
                self.send_response(int(self.path.strip("/")))
                self.send_header(
                    "Location",
                    f"http://127.0.0.1:{collector.server_port}/collector",
                )
                self.end_headers()

            do_GET = redirect
            do_POST = redirect

            def log_message(self, *_args: object) -> None:
                return

        redirector = ThreadingHTTPServer(("127.0.0.1", 0), Redirector)
        collector_thread = threading.Thread(target=collector.serve_forever, daemon=True)
        redirect_thread = threading.Thread(target=redirector.serve_forever, daemon=True)
        collector_thread.start()
        redirect_thread.start()
        try:
            client = DEPLOY.Client(
                f"http://127.0.0.1:{redirector.server_port}",
                "deploy-user",
                "application-password",
                allow_local_http=True,
                timeout=3,
            )
            for status in (301, 302, 303, 307, 308):
                with self.assertRaises(DEPLOY.DeployError):
                    client.request("POST", f"/{status}", {"probe": True})
            self.assertFalse(Collector.authorization_seen)
            self.assertEqual(0, Collector.request_count)

            public_client = DEPLOY.Client(
                f"http://127.0.0.1:{collector.server_port}",
                "deploy-user",
                "application-password",
                allow_local_http=True,
                timeout=3,
            )
            public_client.request_public_json("/public")
            self.assertEqual(1, Collector.request_count)
            self.assertFalse(Collector.authorization_seen)
        finally:
            redirector.shutdown()
            collector.shutdown()
            redirector.server_close()
            collector.server_close()
            redirect_thread.join(timeout=3)
            collector_thread.join(timeout=3)

    def test_target_origin_lock_rejects_ports_userinfo_paths_queries_and_fragments(self) -> None:
        for origin in (
            "https://complete99.co.il",
            "https://complete99.co.il/",
            "https://complete99.co.il:443",
            "https://www.complete99.co.il/",
        ):
            DEPLOY.validate_target_url(origin, False)
        for origin in (
            "https://complete99.co.il:8443",
            "https://user@complete99.co.il",
            "https://complete99.co.il/wp-admin",
            "https://complete99.co.il/?target=other",
            "https://complete99.co.il/#fragment",
            "https://complete99.co.il.evil.example",
            "http://complete99.co.il",
        ):
            with self.assertRaises(DEPLOY.DeployError, msg=origin):
                DEPLOY.validate_target_url(origin, False)
        for origin in (
            "http://127.0.0.1:9420",
            "http://localhost:8080/",
            "http://[::1]:9000",
        ):
            DEPLOY.validate_target_url(origin, True)
        for origin in (
            "http://user@127.0.0.1:9420",
            "http://127.0.0.1:9420/wp",
            "http://127.0.0.1:9420/?query=1",
            "https://127.0.0.1:9420",
        ):
            with self.assertRaises(DEPLOY.DeployError, msg=origin):
                DEPLOY.validate_target_url(origin, True)

    def test_ambiguous_snippet_create_is_recovered_by_exact_name(self) -> None:
        deployment_id = "c99-test-ambiguous-create"
        expected_name = DEPLOY.snippet_name(deployment_id)

        class FakeClient:
            snippets: list[dict[str, object]] = []

            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                if method == "GET":
                    return 200, list(self.snippets)
                if method == "POST" and path == "/wp-json/code-snippets/v1/snippets":
                    self.snippets.append({"id": 71, "name": payload["name"], "active": True})
                    raise DEPLOY.DeployError("proxy returned a misleading response")
                if method == "POST" and path.endswith("/deactivate"):
                    return 200, {}
                if method == "DELETE":
                    self.snippets = []
                    return 204, {}
                raise AssertionError((method, path, expected_name))

        client = FakeClient()
        self.assertEqual(71, DEPLOY.create_snippet(client, "code", deployment_id))

    def test_inactive_snippet_row_prevents_false_delete_success(self) -> None:
        deployment_id = "c99-test-ambiguous-delete"
        name = DEPLOY.snippet_name(deployment_id)

        class FakeClient:
            snippets: list[dict[str, object]] = [{"id": 88, "name": name, "active": True}]

            def request(
                self,
                method: str,
                path: str,
                payload: dict[str, object] | None = None,
                expected: tuple[int, ...] = (200, 201),
            ) -> tuple[int, object]:
                if method == "GET" and f"/snippets/88?" in path:
                    return 200, dict(self.snippets[0])
                if method == "GET":
                    return 200, list(self.snippets)
                if method == "POST" and path.endswith("/deactivate"):
                    self.snippets[0]["active"] = False
                    return 200, {}
                if method == "POST" and path.endswith("/retire"):
                    raise DEPLOY.DeployError("proxy returned a misleading response")
                raise AssertionError((method, path, payload, expected))

        client = FakeClient()
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.delete_snippet_and_prove_404(
                client,
                None,
                "temporary-token",
                deployment_id,
                True,
            )
        self.assertEqual(1, len(client.snippets))
        self.assertFalse(client.snippets[0]["active"])

    def test_deployer_local_test_mode_cannot_target_remote_or_staging_hosts(self) -> None:
        script = ROOT / "scripts" / "deploy-wordpress.py"
        cases = (
            ("http://complete99.ussl.app", "--local-test accepts only"),
            ("https://127.0.0.1:9409", "--local-test accepts only"),
            ("http://complete99.co.il", "--local-test accepts only"),
        )
        for base_url, expected in cases:
            result = subprocess.run(
                [
                    "python",
                    str(script),
                    "--base-url",
                    base_url,
                    "--user",
                    "local-test",
                    "--local-test",
                ],
                capture_output=True,
                text=True,
                encoding="utf-8",
                env={**os.environ, "WP_APP_PASSWORD": "local-test-only"},
            )
            self.assertNotEqual(0, result.returncode)
            self.assertIn(expected, result.stderr)

    def test_github_actions_are_read_only_pinned_and_serialized(self) -> None:
        workflows = sorted((ROOT / ".github" / "workflows").glob("*.yml"))
        self.assertEqual(2, len(workflows))
        for path in workflows:
            text = path.read_text(encoding="utf-8")
            self.assertRegex(text, r"(?m)^permissions:\n(?:  [a-z-]+: read\n)+")
            self.assertIn("  contents: read", text)
            uses = re.findall(r"uses:\s*[^@\s]+@([^\s]+)", text)
            self.assertTrue(uses, path.name)
            for revision in uses:
                self.assertRegex(revision, r"^[0-9a-f]{40}$", f"{path.name}: {revision}")
        deploy = (ROOT / ".github" / "workflows" / "wordpress-deploy.yml").read_text(encoding="utf-8")
        self.assertIn("environment: production", deploy)
        self.assertIn("group: complete99-wordpress-production", deploy)
        self.assertIn("cancel-in-progress: false", deploy)
        self.assertIn('GITHUB_REF" != "refs/heads/main"', deploy)
        self.assertIn("WP_PRODUCTION_READY: ${{ vars.WP_PRODUCTION_READY }}", deploy)
        self.assertIn('if [[ "$WP_PRODUCTION_READY" != "true" ]]', deploy)
        self.assertIn("Require successful WordPress CI for this exact commit", deploy)
        self.assertIn("head_sha=${GITHUB_SHA}&branch=main&status=success", deploy)
        self.assertIn("ci_run_id: ${{ steps.exact_ci.outputs.run_id }}", deploy)
        self.assertIn("actions/download-artifact@", deploy)
        self.assertIn("run-id: ${{ needs.require-green-ci.outputs.ci_run_id }}", deploy)
        self.assertIn("recover-wordpress.py", deploy)
        self.assertIn(
            "if: failure() && steps.mutation_state.outputs.started == 'true'",
            deploy,
        )
        self.assertNotIn("build-plugin-zip.py", deploy)
        ci = (ROOT / ".github" / "workflows" / "wordpress-ci.yml").read_text(encoding="utf-8")
        self.assertIn(
            "python -m pip install --disable-pip-version-check --no-input pytest==9.0.2",
            ci,
        )
        self.assertIn("python -m pytest -q", ci)
        self.assertIn("verify-release-discipline.py", ci)
        self.assertIn("git diff --exit-code -- plugin-dist", ci)
        self.assertIn("git ls-files --others --exclude-standard -- plugin-dist", ci)
        self.assertIn("Prepare the exact validated release bundle", ci)
        release = (ROOT / "scripts" / "verify-release-discipline.py").read_text(
            encoding="utf-8"
        )
        self.assertIn("declared base commit is unavailable", release)

    def test_reproducible_zip_builder(self) -> None:
        with tempfile.TemporaryDirectory() as temp:
            dist = Path(temp)
            subprocess.run(
                ["python", str(ROOT / "scripts" / "build-plugin-zip.py"), "--dist", str(dist), "--verify-reproducible"],
                check=True,
                capture_output=True,
                text=True,
            )
            metadata = json.loads((dist / "complete99-platform-integrity.json").read_text(encoding="utf-8"))
            update_manifest = json.loads((dist / "complete99-platform.json").read_text(encoding="utf-8"))
            self.assertEqual(metadata["version"], update_manifest["version"])
            artifact = dist / metadata["artifact"]
            checksum = (dist / f"{artifact.name}.sha256").read_text(
                encoding="ascii"
            )
            self.assertEqual(
                f"{metadata['sha256']}  {artifact.name}\n",
                checksum,
            )
            subprocess.run(
                [
                    "python",
                    str(ROOT / "scripts" / "validate-package.py"),
                    "--dist",
                    str(dist),
                ],
                check=True,
                capture_output=True,
                text=True,
            )
            with zipfile.ZipFile(artifact) as archive:
                for name in archive.namelist():
                    path = PurePosixPath(name)
                    self.assertEqual("complete99-platform", path.parts[0])
                    self.assertNotIn("\\", name)
                main = archive.read(
                    "complete99-platform/complete99-platform.php"
                )
                self.assertNotIn(b"\r\n", main)

    def test_ci_lints_the_exact_release_zip(self) -> None:
        workflow = (ROOT / ".github" / "workflows" / "wordpress-ci.yml").read_text(
            encoding="utf-8"
        )
        validation = workflow.index("python scripts/validate-package.py")
        shipped_zip_lint = workflow.index("python scripts/lint-plugin-zip.py")
        provenance = workflow.index(
            "Prove checked-in release artifacts are current"
        )
        self.assertLess(validation, shipped_zip_lint)
        self.assertLess(shipped_zip_lint, provenance)

        result = subprocess.run(
            ["python", str(ROOT / "scripts" / "lint-plugin-zip.py")],
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
        )
        self.assertIn("linted ", result.stdout)
        self.assertIn("complete99-platform-", result.stdout)

    def test_public_package_secret_filename_policy_is_fail_closed(self) -> None:
        forbidden = (
            ".env",
            ".env.production",
            "tls/server.pem",
            "tls/server.KEY",
            "signing/private.p12",
            "signing/private.pfx",
            "ssh/id_rsa",
            "ssh/id_ed25519",
            "config/credentials.json",
            "config/service-account.json",
            "config/service_account_key.JSON",
            "config/client_secret_google.json",
            ".env.production/config.php",
            "config/credentials.json.backup",
        )
        allowed = (
            "includes/environment.php",
            "docs/credential-policy.md",
            "config/service-account-schema.php",
            "assets/public-key.txt",
            "data/menu.json",
        )
        for module in (BUILD, VALIDATE):
            for name in forbidden:
                path = PurePosixPath(name)
                with self.subTest(module=module.__name__, forbidden=name):
                    self.assertIsNotNone(module.forbidden_secret_path_reason(path))
            for name in allowed:
                path = PurePosixPath(name)
                with self.subTest(module=module.__name__, allowed=name):
                    self.assertIsNone(module.forbidden_secret_path_reason(path))

    def test_common_credential_signatures_are_detected_without_echoing_values(self) -> None:
        samples = {
            "private-key material": b"-----BEGIN " + b"OPENSSH PRIVATE KEY-----",
            "GitHub access token": b"ghp_" + (b"A" * 30),
            "GitHub fine-grained token": b"github_pat_" + (b"A" * 30),
            "OpenAI API key": b"sk-proj-" + (b"A" * 30),
            "AWS access key ID": b"AKIA" + (b"A" * 16),
            "Google API key": b"AIza" + (b"A" * 35),
            "Slack access token": b"xoxb-" + (b"A" * 30),
            "Stripe live secret key": b"sk_live_" + (b"A" * 24),
            "npm access token": b"npm_" + (b"A" * 24),
        }
        for module in (BUILD, VALIDATE):
            for expected_label, secret_value in samples.items():
                with self.subTest(module=module.__name__, label=expected_label):
                    self.assertEqual(
                        expected_label,
                        module.credential_signature_label(secret_value),
                    )

        secret_value = b"ghp_" + (b"Z" * 30)
        with tempfile.TemporaryDirectory() as temp:
            artifact = Path(temp) / "unsafe.zip"
            with zipfile.ZipFile(artifact, "w") as archive:
                archive.writestr("complete99-platform/config.php", secret_value)
            with zipfile.ZipFile(artifact) as archive:
                with self.assertRaises(SystemExit) as caught:
                    VALIDATE.validate_archive_safety(archive)
        message = str(caught.exception)
        self.assertIn("GitHub access token", message)
        self.assertNotIn(secret_value.decode("ascii"), message)

    def test_builder_refuses_secret_source_before_creating_zip(self) -> None:
        original_source = BUILD.SOURCE
        try:
            with tempfile.TemporaryDirectory() as temp:
                source = Path(temp) / "complete99-platform"
                source.mkdir()
                BUILD.SOURCE = source

                forbidden_path = source / ".env.local"
                forbidden_path.write_text("safe placeholder", encoding="utf-8")
                with self.assertRaises(SystemExit) as filename_error:
                    BUILD.source_files()
                self.assertIn("environment file", str(filename_error.exception))

                forbidden_path.unlink()
                secret_value = "sk-proj-" + ("X" * 30)
                (source / "config.php").write_text(secret_value, encoding="ascii")
                with self.assertRaises(SystemExit) as content_error:
                    BUILD.source_files()
                self.assertIn("OpenAI API key", str(content_error.exception))
                self.assertNotIn(secret_value, str(content_error.exception))
        finally:
            BUILD.SOURCE = original_source


if __name__ == "__main__":
    unittest.main()
