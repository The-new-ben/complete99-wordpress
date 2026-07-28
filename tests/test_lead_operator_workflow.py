from __future__ import annotations

import json
import re
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
LEADS = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-leads.php"
)


class LeadOperatorWorkflowTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.source = LEADS.read_text(encoding="utf-8")

    def method(self, name: str, next_name: str) -> str:
        return self.source.split(f"function {name}", 1)[1].split(
            f"function {next_name}", 1
        )[0]

    def test_detail_record_is_read_only_and_capability_gated(self) -> None:
        self.assertIn(
            "add_action( 'add_meta_boxes_c99_lead'",
            self.source,
        )
        self.assertIn("'supports'            => array()", self.source)
        register = self.method("register_detail_meta_box", "render_detail_meta_box")
        detail = self.method("render_detail_meta_box", "format_consent_time")
        self.assertIn("current_user_can( 'read_post', $post->ID )", register)
        self.assertIn("remove_meta_box( 'submitdiv'", register)
        self.assertIn("remove_meta_box( 'slugdiv'", register)
        self.assertIn("current_user_can( 'read_post', $post->ID )", detail)
        self.assertIn("array( 'response' => 403 )", detail)
        self.assertNotIn("<input", detail)
        self.assertNotIn("<textarea", detail)
        for key in (
            "_c99_contact_name",
            "_c99_organisation",
            "_c99_email",
            "_c99_phone",
            "_c99_message",
            "_c99_interest",
            "_c99_language",
            "_c99_consent_at",
            "_c99_source_url",
        ):
            self.assertIn(key, detail)
        self.assertIn("c99-lead-full-request", detail)
        self.assertIn("normalise_source_url", detail)
        self.assertGreaterEqual(detail.count("esc_html"), 10)

    def test_list_view_adds_phone_and_a_bounded_request_preview(self) -> None:
        columns = self.method("columns", "column_value")
        values = self.source.split("function column_value", 1)[1]
        self.assertIn("'phone'", columns)
        self.assertIn("'request'", columns)
        self.assertIn("current_user_can( 'read_post', $post_id )", values)
        self.assertIn("'_c99_phone'", values)
        self.assertIn("'_c99_message'", values)
        self.assertIn("bounded_preview", values)
        self.assertIn(", 160 )", values)
        self.assertIn("echo esc_html(", values)

    def test_dashboard_widget_is_private_bounded_and_links_to_records(self) -> None:
        self.assertIn("add_action( 'wp_dashboard_setup'", self.source)
        register = self.method("register_dashboard_widget", "render_dashboard_widget")
        dashboard = self.method("render_dashboard_widget", "columns")
        self.assertIn("self::can_view_leads()", register)
        self.assertIn("wp_add_dashboard_widget(", register)
        self.assertIn("self::can_view_leads()", dashboard)
        self.assertIn("'post_type'              => 'c99_lead'", dashboard)
        self.assertIn("'post_status'            => 'private'", dashboard)
        self.assertIn("'posts_per_page'         => 5", dashboard)
        self.assertIn("'perm'                   => 'readable'", dashboard)
        self.assertIn("current_user_can( 'read_post', $lead->ID )", dashboard)
        self.assertIn("bounded_preview", dashboard)
        self.assertIn(", 120 )", dashboard)
        self.assertIn("get_edit_post_link", dashboard)
        self.assertIn("edit.php?post_type=c99_lead", dashboard)

    def test_submission_requires_durable_metadata_readback(self) -> None:
        handle = self.method("handle", "limit_text")
        store = self.method("store_and_verify_fields", "discard_failed_lead")
        cleanup = self.method("discard_failed_lead", "rate_key")
        self.assertIn("store_and_verify_fields( $lead_id, $fields )", handle)
        self.assertIn("discard_failed_lead( $lead_id, array_keys( $fields ) )", handle)
        self.assertLess(
            handle.index("store_and_verify_fields"),
            handle.index("redirect_back( true )"),
        )
        self.assertIn("update_post_meta( $lead_id, $key, wp_slash( $value ) )", store)
        self.assertIn("wp_cache_delete( $lead_id, 'post_meta' )", store)
        self.assertGreaterEqual(store.count("metadata_exists( 'post'"), 2)
        self.assertGreaterEqual(store.count("get_post_meta("), 2)
        self.assertIn("!== (string) $value", store)
        self.assertIn("wp_delete_post( $lead_id, true )", cleanup)
        self.assertIn("delete_post_meta( $lead_id, $key )", cleanup)
        self.assertIn("'post_status' => 'private'", cleanup)
        self.assertIn("C99-STORAGE-FAILED-", cleanup)

    def test_source_is_same_origin_and_drops_query_and_fragment_data(self) -> None:
        normalise = self.method("normalise_source_url", "redirect_back")
        self.assertIn("$source_scheme !== $home_scheme", normalise)
        self.assertIn("$source_host !== $home_host", normalise)
        self.assertIn("$source_port !== $home_port", normalise)
        self.assertIn("isset( $source['user'] )", normalise)
        self.assertIn("isset( $source['pass'] )", normalise)
        self.assertIn("return esc_url_raw( $origin . $path )", normalise)
        self.assertNotRegex(normalise, r"\$source\[['\"]query['\"]\]")
        self.assertNotRegex(normalise, r"\$source\[['\"]fragment['\"]\]")

    def test_source_normalization_runtime_rejects_external_origins(self) -> None:
        php_path = LEADS.as_posix().replace("'", "\\'")
        php = (
            "define('ABSPATH', __DIR__);"
            "function home_url($path='/'){return 'https://complete99.co.il/';}"
            "function wp_parse_url($url){return parse_url($url);}"
            "function esc_url_raw($url){return $url;}"
            f"require '{php_path}';"
            "$method=new ReflectionMethod('Complete99_Leads','normalise_source_url');"
            "$method->setAccessible(true);"
            "$values=[];"
            "foreach(["
            "'https://complete99.co.il/proposal?email=private@example.test#form',"
            "'https://evil.example/proposal',"
            "'https://' . 'user:pass@complete99.co.il/proposal',"
            "'http://complete99.co.il/proposal'"
            "] as $url){$values[]=$method->invoke(null,$url);}"
            "echo json_encode($values);"
        )
        result = subprocess.run(
            ["php", "-r", php],
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
        )
        self.assertEqual(
            [
                "https://complete99.co.il/proposal",
                "",
                "",
                "",
            ],
            json.loads(result.stdout),
        )

    def test_privacy_consent_and_rate_limit_contract_remains_intact(self) -> None:
        self.assertIn("'public'              => false", self.source)
        self.assertIn("'show_in_rest'        => false", self.source)
        self.assertIn("'exclude_from_search' => true", self.source)
        self.assertIn("wp_verify_nonce", self.source)
        self.assertIn("isset( $_POST['consent'] )", self.source)
        self.assertIn("$count >= 5", self.source)
        self.assertIn("HOUR_IN_SECONDS", self.source)
        self.assertIn("hash_hmac( 'sha256'", self.source)
        for external_write in (
            "wp_mail(",
            "wp_remote_post(",
            "wp_remote_request(",
            "curl_exec(",
        ):
            self.assertNotIn(external_write, self.source)
        for public_hook in (
            "rest_api_init",
            "the_content",
            "wp_ajax_nopriv",
        ):
            self.assertNotIn(public_hook, self.source)

    def test_server_enforces_the_public_form_size_limits(self) -> None:
        handle = self.method("handle", "limit_text")
        for maximum in (120, 160, 190, 40, 3000, 80):
            self.assertRegex(
                handle,
                rf"self::limit_text\([\s\S]*?, {maximum} \)",
            )


if __name__ == "__main__":
    unittest.main()
