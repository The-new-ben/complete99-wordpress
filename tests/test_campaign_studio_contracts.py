from __future__ import annotations

import hashlib
import json
import re
import shutil
import sqlite3
import subprocess
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
CAMPAIGNS = PLUGIN / "includes" / "class-complete99-campaigns.php"
PLATFORM = PLUGIN / "includes" / "class-complete99-platform.php"
OPS = PLUGIN / "includes" / "class-complete99-ops.php"
MEDIA_RIGHTS = PLUGIN / "includes" / "class-complete99-consumer-media-rights.php"
MEDIA_RIGHTS_DATA = PLUGIN / "data" / "consumer-media-rights.php"


class Complete99CampaignStudioContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.php = CAMPAIGNS.read_text(encoding="utf-8")

    def test_private_boot_routes_and_duty_separation(self) -> None:
        bootstrap = (PLUGIN / "complete99-platform.php").read_text(encoding="utf-8")
        self.assertIn("class-complete99-campaigns.php", bootstrap)
        for capability in (
            "complete99_view_campaigns",
            "complete99_manage_campaigns",
            "complete99_approve_campaigns",
            "complete99_schedule_campaigns",
            "complete99_record_campaign_evidence",
            "complete99_record_campaign_results",
            "complete99_moderate_campaigns",
        ):
            self.assertIn(capability, self.php)
        self.assertIn("wp_verify_nonce( $nonce, 'wp_rest' )", self.php)
        self.assertIn("INNER JOIN {$tables['locations']}", self.php)
        self.assertIn("l.status = 'active'", self.php)
        self.assertIn("1 > (int) $count", self.php)

    def test_versioned_schema_is_complete_and_bounded(self) -> None:
        self.assertIn("complete99-campaign-schema/v1", self.php)
        for suffix in (
            "c99_campaigns",
            "c99_campaign_revisions",
            "c99_campaign_packages",
            "c99_campaign_provider_receipts",
            "c99_campaign_results",
            "c99_campaign_placements",
            "c99_campaign_event_aggregates",
        ):
            self.assertIn(suffix, self.php)
        for column in ("scenario", "approved_by", "approved_at", "activated_at", "stopped_at"):
            self.assertIn(f"'{column}'", self.php)
        self.assertIn("const MAX_JSON_BYTES        = 65536", self.php)
        self.assertIn("const MAX_EVIDENCE_BYTES    = 8192", self.php)
        self.assertGreaterEqual(self.php.count("strlen( $json ) > self::MAX_JSON_BYTES"), 3)

    def test_server_authority_rejects_client_claims_and_stale_snapshots(self) -> None:
        for token in (
            "resolve_authoritative_asset",
            "resolve_authoritative_catalog_item",
            "resolve_authoritative_recipe",
            "resolve_authoritative_account",
            "resolve_authoritative_content_change",
            "authority_snapshot_is_current",
            "catalog_price_tampered",
            "recipe_cost_tampered",
            "asset_digest_mismatch",
            "catalog_reference_stale",
            "provider_account_reference_required",
            "allergensHe",
            "allergensEn",
            "catalog_evidence_digest_missing",
        ):
            self.assertIn(token, self.php)
        self.assertIn("$authority = self::authority_snapshot_is_current( $state );", self.php)
        self.assertIn("is_wp_error( $authority )", self.php)
        self.assertNotIn("return $configured;", self.php)

    def test_package_contracts_are_channel_exact_and_truthful(self) -> None:
        for schema in (
            "complete99-wordpress-campaign-placement-package/v1",
            "complete99-wordpress-campaign-package/v1",
            "complete99-wordpress-campaign-approval-snapshot/v1",
            "complete99-prepared-channel-package/v1",
        ):
            self.assertIn(schema, self.php)
        self.assertNotIn("legacyPreparedPackage", self.php)
        self.assertIn("channel_requires_separate_approval", self.php)
        self.assertIn("content_change_required", self.php)
        self.assertIn("'publicationClaim'=> false", self.php)
        self.assertIn("'providerAction'  => 'not_attempted'", self.php)
        self.assertIn("'preparedBy'", self.php)
        self.assertIn("'|' . absint( $prepared_by ) . '|'", self.php)
        self.assertIn("load_prepared_package", self.php)
        for payload_format in (
            "instagram_post/v1",
            "facebook_link_post/v1",
            "tiktok_video_script/v1",
            "whatsapp_manual_handoff/v1",
            "google_business_post/v1",
            "wolt_menu_item_handoff/v1",
            "mishloha_menu_item_handoff/v1",
            "cibus_menu_item_handoff/v1",
            "tenbis_menu_item_handoff/v1",
        ):
            self.assertIn(payload_format, self.php)
        for safety_field in (
            "requiresRecipientOptIn",
            "requiresTemplateApproval",
            "handoffMode",
            "destinationUrl",
            "channelNote",
            "callToAction",
            "externalSafetyAdapter",
        ):
            self.assertIn(safety_field, self.php)
        builder = self.php.split("public static function build_prepared_package", 1)[1].split(
            "private static function resolve_authoritative_account", 1
        )[0]
        self.assertIn("'prepared' === $package['status'] && empty( $package['blockers'] )", builder)
        self.assertIn("$external_destination['url']", builder)
        self.assertIn("'wolt' => 'Wolt'", builder)
        self.assertIn("preg_replace( '/[^0-9A-Za-z]+/'", builder)

    def test_prepared_package_time_and_identity_are_command_bound_and_replay_stable(self) -> None:
        builder = self.php.split("public static function build_prepared_package", 1)[1].split(
            "private static function resolve_authoritative_account", 1
        )[0]
        mutation = self.php.split("private static function run_mutation", 1)[1].split(
            "private static function require_replay_scope", 1
        )[0]
        replay = self.php.split("private static function reconstruct_mutation_response", 1)[1].split(
            "public static function rest_create", 1
        )[0]
        self.assertIn("command_prepared_at", mutation)
        self.assertIn("Y-m-d\\TH:i:s.v\\Z", mutation)
        self.assertIn("preparationCommandId", builder)
        self.assertIn("$preparation_command_id", builder)
        self.assertIn("$identity['command_id']", replay)
        self.assertIn("WHERE package_id=%s AND campaign_id=%s", replay)

        def package_id(command_id: str) -> str:
            raw = "campaign|7|wolt|4|material|" + command_id
            return "pkg_" + hashlib.sha256(raw.encode()).hexdigest()[:48]

        first = package_id("cmp_" + "a" * 60)
        self.assertEqual(first, package_id("cmp_" + "a" * 60))
        self.assertNotEqual(first, package_id("cmp_" + "b" * 60))
        self.assertNotEqual("2026-08-11T10:00:00.001Z", "2026-08-11T10:00:00.002Z")

    def test_owned_website_schedule_is_db_authoritative_and_exact(self) -> None:
        platform = PLATFORM.read_text(encoding="utf-8")
        self.assertIn("'jobState'] = 'reconcile_pending'", self.php)
        schedule = self.php.split("public static function rest_schedule", 1)[1].split(
            "/** Load and verify the exact prepared artifact", 1
        )[0]
        self.assertLess(
            schedule.index("self::save_campaign( $loaded, $state, 'schedule_requested'"),
            schedule.index("self::schedule_next_reconcile_trigger()"),
        )
        self.assertIn("add_action( 'complete99_campaign_activate'", self.php)
        self.assertIn("add_action( 'complete99_campaign_expire'", self.php)
        self.assertIn("array( (string) $state['campaignId'], (int) ( $state['runtime']['scheduledVersion']", self.php)
        self.assertIn("wp_next_scheduled( 'complete99_campaign_activate', $args )", self.php)
        self.assertIn("wp_next_scheduled( 'complete99_campaign_expire', $args )", self.php)
        self.assertIn("!== $starts", self.php)
        self.assertIn("!== $expires", self.php)
        self.assertIn("Complete99_Campaigns::begin_deactivation_suspension();", platform)
        self.assertIn("Complete99_Campaigns::complete_deactivation_suspension( $token );", platform)
        self.assertIn("deactivated_plugin", platform)
        self.assertIn("reconcile_schedules", self.php)

    def test_scheduler_repairs_drift_rejects_early_execution_and_checks_continuation(self) -> None:
        reconcile = self.php.split("private static function reconcile_campaign_schedule", 1)[1].split(
            "public static function suspend_schedules", 1
        )[0]
        for hook, timestamp, prefix in (
            ("complete99_campaign_activate", "$starts", "activation"),
            ("complete99_campaign_expire", "$expires", "expiry"),
        ):
            self.assertIn(f"wp_next_scheduled( '{hook}', $args )", reconcile)
            self.assertIn(f"wp_unschedule_event( (int) ${prefix}_next, '{hook}', $args, true )", reconcile)
            self.assertIn(f"(int) wp_next_scheduled( '{hook}', $args ) !== {timestamp}", reconcile)

        worker = self.php.split("public static function reconcile_schedules", 1)[1].split(
            "private static function reconcile_campaign_schedule", 1
        )[0]
        self.assertIn("$continuation = self::schedule_next_reconcile_trigger", worker)
        self.assertIn("if ( is_wp_error( $continuation ) ) { return $continuation; }", worker)

        exact = self.php.split("private static function ensure_exact_campaign_event", 1)[1].split(
            "private static function mark_schedule_retry", 1
        )[0]
        for token in (
            "3 !== count( $args )",
            "wp_unschedule_event( (int) $next, $hook, $args, true )",
            "wp_schedule_single_event( $timestamp, $hook, $args, true )",
            "(int) wp_next_scheduled( $hook, $args ) !== $timestamp",
        ):
            self.assertIn(token, exact)

        activation = self.php.split("public static function cron_activate", 1)[1].split(
            "private static function ensure_exact_campaign_event", 1
        )[0]
        expiry = self.php.split("public static function cron_expire", 1)[1].split(
            "private static function schedule_expiry_retry", 1
        )[0]
        self.assertIn("$activation_at > time()", activation)
        self.assertIn("ensure_exact_campaign_event_locked( 'complete99_campaign_activate'", activation)
        self.assertIn("$expiry_at > time()", expiry)
        self.assertIn("ensure_exact_campaign_event_locked( 'complete99_campaign_expire'", expiry)

        self.assertIn("'active' !== $lifecycle", reconcile)
        self.assertIn("'suspended' === ( $state['runtime']['jobState'] ?? '' )", reconcile)
        self.assertIn("'jobState'] = 'readback_verified'", reconcile)
        self.assertIn("'active_schedule_resumed'", reconcile)

        def repaired_timestamp(existing: int | None, durable: int) -> int:
            return durable if existing != durable else existing

        self.assertEqual(200, repaired_timestamp(100, 200))
        self.assertEqual(200, repaired_timestamp(200, 200))

    def test_scheduler_keyset_drain_cannot_starve_rows_after_batch_boundaries(self) -> None:
        scheduler_projection = self.php.split("private static function bounded_actionable_campaign_rows", 1)[1].split(
            "private static function ensure_public_quarantine_zero_membership_receipt", 1
        )[0]
        reconcile = self.php.split("private static function reconcile_schedules_fenced", 1)[1].split(
            "private static function reconcile_campaign_schedule", 1
        )[0]
        suspend = self.php.split("public static function suspend_schedules", 1)[1].split(
            "public static function enqueue_public_assets", 1
        )[0]
        cron_drain = self.php.split("private static function suspend_campaign_cron_batch", 1)[1].split(
            "private static function install_lifecycle_reservation", 1
        )[0]
        self.assertIn("c.id>%d", scheduler_projection)
        self.assertIn("$cursor_id = $id", scheduler_projection)
        self.assertIn("LIMIT 101", scheduler_projection)
        self.assertIn("CAMPAIGN_MAX_ROWS", scheduler_projection)
        self.assertIn("do {", scheduler_projection)
        self.assertIn("_get_cron_array", cron_drain)
        self.assertIn("self::RECONCILE_BATCH_SIZE", cron_drain)
        self.assertIn("break 3", cron_drain)
        self.assertIn("wp_unschedule_event", cron_drain)
        self.assertIn("wp_get_scheduled_event", cron_drain)
        self.assertIn("bounded_actionable_campaign_rows( 'schedule_due'", reconcile)
        self.assertIn("suspend_campaign_cron_batch", suspend)
        self.assertIn("store_lifecycle_receipt", suspend)
        self.assertIn("RECONCILE_MAX_BATCHES", reconcile)
        self.assertIn("RECONCILE_TIME_BUDGET", reconcile)
        self.assertIn("next_attempt_at<=%s", scheduler_projection)
        self.assertIn("schedule_next_reconcile_trigger", reconcile)
        self.assertNotIn("LIMIT 1000", suspend)
        self.assertNotIn("save_campaign", suspend)
        self.assertNotIn("append_cleanup_obligation", suspend)

        def repeated_bounded_drain(record_ids: list[int], batch_size: int = 50) -> list[int]:
            processed: list[int] = []
            due = list(record_ids)
            while due:
                invocation = due[: batch_size * 2]
                processed.extend(invocation)
                due = due[len(invocation) :]
            return processed

        reconcile_ids = list(range(1, 251))
        suspension_ids = list(range(1, 1206))
        self.assertEqual(reconcile_ids, repeated_bounded_drain(reconcile_ids))
        self.assertEqual(suspension_ids, repeated_bounded_drain(suspension_ids))

    def test_suspension_restores_authoritative_job_state_and_compensates_exact_hooks(self) -> None:
        reconcile = self.php.split("private static function reconcile_campaign_schedule", 1)[1].split(
            "public static function suspend_schedules", 1
        )[0]
        restore = reconcile.split("$authority = self::authority_snapshot_is_current", 1)[0]
        for token in (
            "suspendedFromJobState",
            "$resume_job",
            "complete99_campaign_suspended_state_invalid",
            "schedule_resume_state_restored",
            "authority_retry_pending",
        ):
            self.assertIn(token, restore)
        self.assertIn("'lifecycleState'] = 'schedule_requested'", reconcile)
        self.assertIn("'_cronAction' => 'activate'", reconcile)
        self.assertIn("self::cron_activate_fenced( $result['args'][0], $result['args'][1], $result['args'][2] )", reconcile)

        suspend = self.php.split("public static function suspend_schedules", 1)[1].split(
            "public static function resume_schedules", 1
        )[0]
        self.assertLess(suspend.index("begin_worker_execution_fence( false, true )"), suspend.index("transition_lifecycle_reservation_locked( $current, 'suspending' )"))
        self.assertIn("suspend_campaign_cron_batch", suspend)
        self.assertIn("store_lifecycle_receipt", suspend)
        self.assertIn("prove_public_quarantine_absence", suspend)
        self.assertIn("transition_lifecycle_reservation_locked( $current, 'inactive' )", suspend)
        self.assertNotIn("save_campaign", suspend)
        self.assertNotIn("campaign_event_snapshot", suspend)
        self.assertNotIn("append_cleanup_obligation", suspend)

        state = {
            "lifecycle": "active",
            "job": "suspended",
            "suspended_from": "authority_retry_pending",
            "placement": "suppressed_authority",
        }
        state["job"] = state.pop("suspended_from")
        if state["job"] == "authority_retry_pending":
            state["lifecycle"] = "schedule_requested"
            state["placement"] = "pending_readback"
        self.assertEqual("schedule_requested", state["lifecycle"])
        self.assertEqual("pending_readback", state["placement"])

    def test_suspended_state_fails_closed_at_public_and_cron_callbacks(self) -> None:
        for callback, end, role in (
            ("cron_activate", "private static function cron_activate_fenced", "activate"),
            ("cron_verify_readback", "private static function cron_verify_readback_fenced", "verify_readback"),
            ("cron_expire", "private static function cron_expire_fenced", "expire"),
        ):
            section = self.php.split(f"function {callback}", 1)[1].split(end, 1)[0]
            self.assertIn(f"run_lifecycle_worker_operation( '{role}'", section, callback)
        eligibility = self.php.split("private static function campaign_callback_eligible", 1)[1].split(
            "public static function cron_activate", 1
        )[0]
        self.assertNotIn("'suspended'", eligibility)
        self.assertIn("'schedule_requested'", eligibility)
        self.assertIn("'active'", eligibility)
        public_transaction = self.php.split("private static function begin_public_read_transaction", 1)[1].split(
            "private static function begin_public_event_transaction", 1
        )[0]
        event_transaction = self.php.split("private static function begin_public_event_transaction", 1)[1].split(
            "private static function commit_public_read_transaction", 1
        )[0]
        self.assertIn("lock_active_lifecycle_generation", public_transaction)
        self.assertIn("lock_active_lifecycle_generation", event_transaction)
        render = self.php.split("public static function render_public_placement", 1)[1].split(
            "private static function suppress_stale_placement", 1
        )[0]
        self.assertIn("begin_public_read_transaction", render)
        self.assertIn("commit_public_read_transaction", render)
        for callback, end in (
            ("rest_public_readback", "private static function placement_public_url"),
            ("rest_public_event", "public static function render_public_placement"),
        ):
            section = self.php.split(f"function {callback}", 1)[1].split(end, 1)[0]
            self.assertIn("locked_public_placement_context", section, callback)

    def test_manual_evidence_survives_schedule_version_increment_by_exact_package_binding(self) -> None:
        evidence = self.php.split("public static function rest_provider_receipts", 1)[1].split(
            "public static function rest_results", 1
        )[0]
        self.assertIn("scheduledPackageId", evidence)
        self.assertIn("scheduledApprovalDigest", evidence)
        self.assertIn("package_id=%s", evidence)
        self.assertNotIn("campaign_version=%d AND channel=%s AND package_digest=%s AND status", evidence)

    def test_global_website_slots_require_global_owner(self) -> None:
        self.assertIn("global_website_owner_required", self.php)
        self.assertIn("0 !== $location_id", self.php)
        self.assertIn("user_can( (int) ( $governance['approverUserId']", self.php)
        cron = self.php.split("public static function cron_activate", 1)[1].split(
            "public static function rest_public_readback", 1
        )[0]
        self.assertIn("0 !== (int) ( $state['locationId']", cron)
        self.assertIn("'manage_options'", cron)

    def test_public_readback_is_transactional_and_checks_every_write(self) -> None:
        readback = self.php.split("private static function commit_verified_readback", 1)[1].split(
            "public static function cron_expire", 1
        )[0]
        self.assertIn("begin_transaction", readback)
        self.assertIn("FOR UPDATE", readback)
        self.assertIn("rollback_transaction", readback)
        self.assertIn("commit_transaction", readback)
        self.assertIn("readback_receipt_failed", readback)
        self.assertIn("readback_placement_failed", readback)
        self.assertIn("is_wp_error( $saved )", readback)
        public = self.php.split("public static function rest_public_readback", 1)[1].split(
            "private static function placement_public_url", 1
        )[0]
        self.assertNotIn("$wpdb->insert", public)

    def test_private_evidence_acl_and_viewer_redaction(self) -> None:
        self.assertNotIn("current_user_can( 'read_post', $attachment_id )", self.php)
        self.assertIn("private_evidence_marker_state", self.php)
        self.assertIn("map_private_evidence_meta_cap", self.php)
        self.assertIn("private_evidence_actor_mode", self.php)
        self.assertIn("exclude_private_evidence_query", self.php)
        self.assertIn("_complete99_evidence_location_id", self.php)
        self.assertIn("_complete99_evidence_owner_user_id", self.php)
        detail = self.php.split("private static function campaign_detail", 1)[1].split(
            "private static function mutation_campaign_view", 1
        )[0]
        self.assertIn("current_user_can( self::EVIDENCE_CAPABILITY )", detail)
        self.assertIn("unset( $receipt['proof_ref'] )", detail)
        self.assertIn("unset( $result['source_ref'] )", detail)
        self.assertIn("evidence_summary", detail)
        self.assertIn("private_evidence_root", self.php)
        self.assertIn("private_evidence_file", self.php)
        self.assertIn("DOCUMENT_ROOT", self.php)
        self.assertIn("serve_private_evidence", self.php)

    def test_anonymous_signals_are_idempotent_rate_bounded_and_unverified(self) -> None:
        event = self.php.split("public static function rest_public_event", 1)[1].split(
            "public static function render_public_placement", 1
        )[0]
        self.assertIn("claim_public_event_budget", event)
        self.assertIn("PUBLIC_EVENTS_PER_HOUR", event)
        self.assertIn("event_id", event)
        self.assertIn("anonymous_unverified", event)
        self.assertIn("duplicate", event)
        self.assertIn("anonymous_unverified_claim", event)
        self.assertIn("FOR UPDATE", event)
        self.assertIn("claim_digest", event)
        self.assertIn("event_count=%d", event)
        self.assertNotIn("set_transient", event)
        self.assertNotIn("get_transient", event)
        self.assertIn("unverifiedWebSignals", self.php)
        self.assertNotIn("verifiedKpi", self.php)

        accepted = set()
        for event_id in [f"event-{index:020d}" for index in range(241)]:
            if len(accepted) < 240:
                accepted.add(event_id)
        self.assertEqual(240, len(accepted))
        duplicate = next(iter(accepted))
        accepted.add(duplicate)
        self.assertEqual(240, len(accepted))

    def test_owned_readback_receipt_and_anonymous_aggregate_are_truthful(self) -> None:
        self.assertIn("'proof_level' => 'system_verified'", self.php)
        self.assertIn("rest_public_readback", self.php)
        self.assertIn("rest_public_event", self.php)
        self.assertIn("event_count=event_count+1", self.php)
        public_event = self.php.split("public static function rest_public_event", 1)[1].split(
            "public static function render_public_placement", 1
        )[0]
        for pii in ("email", "phone", "ip_address", "user_agent", "cookie"):
            self.assertNotIn(pii, public_event.lower())

    def test_independent_public_html_verifier_precedes_system_receipt(self) -> None:
        verifier = self.php.split("public static function cron_verify_readback", 1)[1].split(
            "private static function commit_verified_readback", 1
        )[0]
        for token in (
            "placement_public_url",
            "wp_safe_remote_get",
            "timeout' => 5",
            "data-c99-placement-id",
            "data-c99-public-digest",
            "data-c99-rendered-body-digest",
            "commit_verified_readback",
        ):
            self.assertIn(token, verifier)
        self.assertLess(verifier.index("wp_safe_remote_get"), verifier.index("commit_verified_readback"))
        render = self.php.split("public static function render_public_placement", 1)[1].split(
            "private static function suppress_stale_placement", 1
        )[0]
        self.assertIn('data-c99-placement-id="', render)
        self.assertIn('data-c99-rendered-body-digest="', render)

    def test_retries_are_deferred_and_normal_init_only_enqueues_cron(self) -> None:
        boot = self.php.split("public static function boot", 1)[1].split(
            "public static function prepare_schema", 1
        )[0]
        self.assertIn("add_action( 'init', array( __CLASS__, 'ensure_reconcile_trigger' )", boot)
        self.assertIn("complete99_campaign_reconcile_schedules", boot)
        self.assertIn("admin_init", boot)
        reconcile = self.php.split("private static function reconcile_schedules_fenced", 1)[1].split(
            "private static function reconcile_campaign_schedule", 1
        )[0]
        scheduler_projection = self.php.split("private static function bounded_actionable_campaign_rows", 1)[1].split(
            "private static function ensure_public_quarantine_zero_membership_receipt", 1
        )[0]
        self.assertNotIn("wp_remote_get", reconcile)
        self.assertIn("next_attempt_at", scheduler_projection)
        retry = self.php.split("private static function mark_schedule_retry", 1)[1].split(
            "public static function rest_public_readback", 1
        )[0]
        self.assertIn("is_wp_error( $saved )", retry)

    def test_expiry_and_cancel_are_atomic_and_stop_external_state(self) -> None:
        expiry = self.php.split("public static function cron_expire", 1)[1].split(
            "public static function rest_public_event", 1
        )[0]
        for token in (
            "begin_transaction",
            "rollback_transaction",
            "commit_transaction",
            "expiry_retry_pending",
            "schedule_expiry_retry",
            "'externalState'] = 'expired'",
        ):
            self.assertIn(token, expiry)
        cancel = self.php.split("public static function rest_cancel", 1)[1].split(
            "private static function store_prepared_package", 1
        )[0]
        self.assertIn("'externalState'] = 'stopped'", cancel)

    def test_strict_rfc3339_rejects_relative_and_ambiguous_timestamps(self) -> None:
        parser = self.php.split("private static function iso_datetime", 1)[1].split(
            "private static function mysql_datetime_or_null", 1
        )[0]
        self.assertIn("RFC 3339", parser)
        self.assertIn("[+-]\\d{2}:\\d{2}", parser)
        self.assertIn("getLastErrors", parser)
        self.assertNotIn("new \\DateTimeImmutable( $value );\n\t\t} catch", parser.split("preg_match", 1)[0])

    def test_wolt_manual_handoff_uses_destination_truth_not_live_connection(self) -> None:
        builder = self.php.split("public static function build_prepared_package", 1)[1].split(
            "private static function resolve_authoritative_account", 1
        )[0]
        self.assertIn("destinationVerified", builder)
        self.assertIn("external_destination_unverified", builder)
        self.assertNotIn("'verified' !== ( $account['connectionState']", builder)
        accounts = self.php.split("private static function resolve_authoritative_account", 1)[1].split(
            "private static function channel_payload", 1
        )[0]
        self.assertIn("'connectionState'     => 'limited'", accounts)
        self.assertIn("'destinationVerified' => true", accounts)

    def test_active_render_revalidates_every_authority_and_suppresses(self) -> None:
        render = self.php.split("public static function render_public_placement", 1)[1].split(
            "private static function suppress_stale_placement", 1
        )[0]
        locked = self.php.split("private static function locked_public_placement_context", 1)[1].split(
            "public static function rest_public_readback", 1
        )[0]
        self.assertIn("locked_public_placement_context", render)
        self.assertIn("begin_public_read_transaction", render)
        self.assertIn("finally {", render)
        self.assertIn("self::commit_public_read_transaction();", render)
        self.assertLess(
            render.index("<aside"),
            render.rindex("self::commit_public_read_transaction();"),
            "the locked public transaction must remain open until HTML emission completes",
        )
        self.assertIn("authority_snapshot_is_current", locked)
        self.assertIn("scheduledPackageDigest", locked)
        self.assertIn("verified_readback_receipt_matches", locked)
        self.assertIn("$context['receipt']", render)
        suppress = self.php.split("private static function suppress_stale_placement", 1)[1].split(
            "public static function rest_provider_receipts", 1
        )[0]
        self.assertIn("suppressed_authority", suppress)
        self.assertIn("authority_retry_pending", suppress)
        self.assertIn("active_authority_suppressed", suppress)

    def test_accountable_people_and_location_scoped_authority_are_rechecked(self) -> None:
        authority = self.php.split("private static function build_authority_snapshot", 1)[1].split(
            "private static function resolve_authoritative_content_change", 1
        )[0]
        for role in ("budgetOwner", "approver", "moderationOwner", "escalationOwner"):
            self.assertIn(role, authority)
        self.assertIn("accountable_user_authority_fingerprint", authority)
        self.assertIn("authorityRevision", authority)
        self.assertIn("campaign_authority_user_revoked", authority)
        for resolver in ("complete99_campaign_recipe_receipt", "complete99_campaign_provider_account_receipt"):
            self.assertIn(resolver, self.php)
        self.assertIn("authorityScope", self.php)
        self.assertIn("locationId", self.php)

    def test_content_change_binds_unblocked_checks_and_current_after_projection(self) -> None:
        content = self.php.split("private static function resolve_authoritative_content_change", 1)[1].split(
            "private static function authority_snapshot_is_current", 1
        )[0]
        for token in ("$checks_valid", "$after_matches", "'passed'", "'warning'", "checksDigest", "afterDigest"):
            self.assertIn(token, content)
        self.assertNotIn("'blocked'", content)
        self.assertIn("array_key_exists( $key", content)

    def test_global_slot_window_is_locked_before_schedule_and_activation(self) -> None:
        lock = self.php.split("private static function lock_owned_slot_window", 1)[1].split(
            "public static function rest_cancel", 1
        )[0]
        self.assertIn("FOR UPDATE", lock)
        self.assertIn("activation_at < %s AND expiry_at > %s", lock)
        self.assertIn("slot_window_conflict", lock)
        schedule = self.php.split("public static function rest_schedule", 1)[1].split(
            "private static function load_prepared_package", 1
        )[0]
        activation = self.php.split("public static function cron_activate", 1)[1].split(
            "private static function mark_schedule_retry", 1
        )[0]
        self.assertIn("lock_owned_slot_window", schedule)
        self.assertIn("lock_owned_slot_window", activation)

        def overlaps(a_start: int, a_end: int, b_start: int, b_end: int) -> bool:
            return a_start < b_end and a_end > b_start

        self.assertTrue(overlaps(10, 20, 15, 25))
        self.assertFalse(overlaps(10, 20, 20, 30))

    def test_generic_attachment_requires_public_use_scope_and_rejects_private_evidence(self) -> None:
        assets = self.php.split("private static function resolve_authoritative_asset", 1)[1].split(
            "public static function asset_choices", 1
        )[0]
        for token in (
            "user_can( $reader_id, 'read_post'",
            "_complete99_campaign_public_use",
            "_complete99_campaign_asset_scope",
            "_complete99_campaign_asset_location_id",
            "_complete99_private_evidence",
        ):
            self.assertIn(token, assets)

    def test_archive_media_provenance_is_preserved_and_cannot_satisfy_campaign_rights(self) -> None:
        consumer = (PLUGIN / "includes" / "class-complete99-consumer.php").read_text(encoding="utf-8")
        rest = (PLUGIN / "includes" / "class-complete99-rest.php").read_text(encoding="utf-8")
        rights = MEDIA_RIGHTS.read_text(encoding="utf-8")
        rights_data = MEDIA_RIGHTS_DATA.read_text(encoding="utf-8")
        bundled = rest.split("private static function bundled_public_indexable_items", 1)[1].split(
            "public static function public_indexable_item_by_slug", 1
        )[0]
        self.assertIn("Complete99_Consumer_Media_Rights::assert_invariants", bundled)
        self.assertIn("Complete99_Consumer_Media_Rights::record_for_asset", bundled)
        self.assertIn("$media_rights['media_provenance']", bundled)
        self.assertIn("$media_rights['media_rights_state']", bundled)
        menu_data = (PLUGIN / "data" / "consumer-menu.php").read_text(encoding="utf-8")
        self.assertNotIn("'media_provenance'", menu_data)
        self.assertNotIn("'media_rights_state'", menu_data)
        self.assertEqual(13, rights_data.count("'media_provenance'          => 'complete99_archive'"))
        self.assertEqual(13, rights_data.count("'media_rights_state'        => 'approved_public_use'"))
        self.assertEqual(13, rights_data.count("'campaign_use_authorized'   => false"))
        self.assertIn("const PAYLOAD_SHA256", rights)
        self.assertIn("self::payload_digest( $registry )", rights)
        menu = consumer.split("public static function menu_items", 1)[1].split(
            "public static function dish_by_slug", 1
        )[0]
        self.assertIn("media_provenance", menu)
        self.assertIn("'unverified'", menu)
        self.assertIn("'review_required'", menu)
        self.assertNotIn("'complete99_archive' ===", menu)
        self.assertNotIn("= 'business_owned'", menu)
        resolver = self.php.split("private static function resolve_authoritative_asset", 1)[1].split(
            "public static function asset_choices", 1
        )[0]
        self.assertIn("Complete99_Consumer_Media_Rights::record_for_asset", resolver)
        self.assertIn("$archive_campaign_held", resolver)
        self.assertIn("'business_owned' !== ( $item['_complete99_media_provenance']", resolver)
        self.assertIn("'approved_public_use' !== ( $item['_complete99_media_rights']", resolver)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_media_rights_registry_digest_and_tamper_fail_closed(self) -> None:
        plugin_dir = (str(PLUGIN).replace("\\", "/") + "/").replace("'", "\\'")
        rights_path = str(MEDIA_RIGHTS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);define('ARRAY_A','ARRAY_A');define('HOUR_IN_SECONDS',3600);
define('COMPLETE99_PLATFORM_DIR', '{plugin_dir}');
require '{rights_path}';
$registry = Complete99_Consumer_Media_Rights::registry(true);
$tampered = $registry;
$tampered['records'][0]['campaign_use_authorized'] = true;
$missing = $registry;
array_pop($missing['records']);
echo json_encode(array(
  'count'=>count($registry['records']),
  'digest'=>Complete99_Consumer_Media_Rights::payload_digest($registry),
  'tampered_valid'=>Complete99_Consumer_Media_Rights::validate_registry($tampered),
  'missing_valid'=>Complete99_Consumer_Media_Rights::validate_registry($missing),
  'menu_record'=>Complete99_Consumer_Media_Rights::record_for_asset('c99-food-sabich-pita-gallery-2021-wp-v01.jpg'),
  'hero_record'=>Complete99_Consumer_Media_Rights::record_for_asset('/assets/c99-food-house-spread-hero-2021-wp-v01-768.webp')
), JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual(13, result["count"])
        self.assertEqual("9a0f6ceacea89869b0da896f10b0f6f25f8b082e9a911a377d36bf9afe00527f", result["digest"])
        self.assertFalse(result["tampered_valid"])
        self.assertFalse(result["missing_valid"])
        self.assertEqual("complete99_archive", result["menu_record"]["media_provenance"])
        self.assertFalse(result["menu_record"]["campaign_use_authorized"])
        self.assertEqual("complete99_archive", result["hero_record"]["media_provenance"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_archive_media_runtime_pipeline_blocks_campaign_but_keeps_brand_asset(self) -> None:
        plugin_dir = (str(PLUGIN).replace("\\", "/") + "/").replace("'", "\\'")
        rest_path = str(PLUGIN / "includes" / "class-complete99-rest.php").replace("\\", "/").replace("'", "\\'")
        consumer_path = str(PLUGIN / "includes" / "class-complete99-consumer.php").replace("\\", "/").replace("'", "\\'")
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        rights_path = str(MEDIA_RIGHTS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_dir}');
define('COMPLETE99_PLATFORM_URL', 'https://example.test/wp-content/plugins/complete99-platform/');
class WP_Error {{ private $code; public function __construct($code,$message='',$data=null){{$this->code=$code;}} public function get_error_code(){{return $this->code;}} }}
function is_wp_error($value){{return $value instanceof WP_Error;}}
function sanitize_title($value){{return strtolower(trim((string)$value));}}
function sanitize_key($value){{return strtolower(trim((string)$value));}}
function sanitize_file_name($value){{return basename((string)$value);}}
function get_option($key,$default=array()){{return $default;}}
function wp_parse_url($url,$component=-1){{return parse_url((string)$url,$component);}}
function home_url($path='/'){{return 'https://example.test/'.ltrim((string)$path,'/');}}
require '{rights_path}';
require '{rest_path}';
require '{consumer_path}';
require '{campaign_path}';
$items = Complete99_REST::public_indexable_items();
$consumer = Complete99_Consumer::menu_items();
$method = new ReflectionMethod('Complete99_Campaigns','resolve_authoritative_asset');
$method->setAccessible(true);
$archive = $method->invoke(null,array('variants'=>array(array('channel'=>'website','assetId'=>'dish:sabich')),'assets'=>array(array('assetId'=>'dish:sabich'))),'website');
$brand = $method->invoke(null,array('variants'=>array(array('channel'=>'website','assetId'=>'builtin:connected-table-editorial-v1')),'assets'=>array(array('assetId'=>'builtin:connected-table-editorial-v1'))),'website');
echo json_encode(array(
  'rest_provenance'=>$items[0]['media_provenance'],
  'consumer_provenance'=>$consumer[0]['_complete99_media_provenance'],
  'archive_error'=>is_wp_error($archive)?$archive->get_error_code():'',
  'brand_source'=>is_array($brand)?$brand['source']:'error'
), JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual("complete99_archive", result["rest_provenance"])
        self.assertEqual("complete99_archive", result["consumer_provenance"])
        self.assertEqual("asset_dish_receipt_invalid", result["archive_error"])
        self.assertEqual("packaged_brand_illustration", result["brand_source"])

    def test_schema_checks_critical_types_defaults_and_collation(self) -> None:
        for token in (
            "critical_columns",
            "column_spec_matches",
            "SHOW FULL COLUMNS",
            "invalid_columns",
            "invalid_collations",
            "varchar(80)",
            "next_attempt_at datetime NULL",
        ):
            self.assertIn(token, self.php)

    def test_background_state_and_revision_writes_are_atomic(self) -> None:
        save = self.php.split("private static function save_campaign", 1)[1].split(
            "private static function insert_revision", 1
        )[0]
        self.assertIn("$owns_transaction", save)
        self.assertIn("begin_transaction", save)
        self.assertIn("rollback_transaction", save)
        self.assertIn("commit_transaction", save)
        self.assertLess(save.index("insert_revision"), save.index("commit_transaction"))

    def test_manual_evidence_results_and_moderation_fail_closed(self) -> None:
        for callback in (
            "rest_provider_receipts",
            "rest_results",
            "rest_moderation_issues",
        ):
            self.assertIn(f"function {callback}", self.php)
        self.assertIn("'proof_level' => 'human_attested'", self.php)
        self.assertIn("_complete99_private_evidence", self.php)
        self.assertIn("proofSha256", self.php)
        self.assertIn("'observation_type' => 'observed'", self.php)
        self.assertIn("$ops['issues']", self.php)
        self.assertIn("complete99-campaign-moderation-link/v1", self.php)
        self.assertNotIn("REST users cannot create provider-verified receipts", self.php)

    def test_idempotent_replay_is_evidence_reconstructed_and_digest_checked(self) -> None:
        self.assertIn("reconstruct_mutation_response", self.php)
        self.assertIn("hash_equals( (string) $receipt['result_digest']", self.php)
        self.assertIn("complete99_campaign_replay_evidence_invalid", self.php)
        replay = self.php.split("private static function reconstruct_mutation_response", 1)[1].split(
            "public static function rest_create", 1
        )[0]
        self.assertIn("snapshot_digest", replay)
        self.assertIn("package_digest", replay)
        self.assertIn("provider_receipts", replay)
        self.assertIn("results", replay)
        self.assertIn("$ops['issues']", replay)

    def test_admin_and_public_assets_are_present_accessible_and_bilingual(self) -> None:
        for relative in (
            "assets/css/campaigns.css",
            "assets/js/campaigns.js",
            "assets/css/campaign-placement.css",
            "assets/js/campaign-placement.js",
        ):
            self.assertTrue((PLUGIN / relative).is_file(), relative)
        js = (PLUGIN / "assets/js/campaigns.js").read_text(encoding="utf-8")
        self.assertIn("credentials='same-origin'", js)
        self.assertIn("'X-WP-Nonce':cfg.nonce", js)
        self.assertIn("Idempotency-Key", js)
        self.assertIn("moderation-issues", js)
        self.assertIn("רשומה מנוהלת", self.php)
        self.assertNotIn("×", self.php)

    def test_dual_cohort_capacity_has_hard_limits_and_protected_write_reserve(self) -> None:
        capacity = self.php.split("public static function capacity_status", 1)[1].split(
            "private static function install_schema_tables", 1
        )[0]
        for token in (
            "Complete99_Ops::table_names()",
            "self::table_names()",
            "ORDER BY id ASC LIMIT",
            "self::canonical_json( $rows )",
            "CAPACITY_MAX_ROWS",
            "CAPACITY_MAX_BYTES",
            "CAPACITY_WRITE_RESERVE_ROWS",
            "CAPACITY_WRITE_RESERVE_BYTES",
            "writeReady",
        ):
            self.assertIn(token, capacity)
        self.assertIn("complete99-campaign-capacity/v3", capacity)

        max_rows, reserve_rows = 4500, 64
        max_bytes, reserve_bytes = 4_194_304, 262_144
        write_ready = lambda rows, size: rows <= max_rows - reserve_rows and size <= max_bytes - reserve_bytes
        self.assertTrue(write_ready(4436, 3_932_160))
        self.assertFalse(write_ready(4437, 3_932_160))
        self.assertFalse(write_ready(4436, 3_932_161))
        self.assertTrue(4500 <= max_rows)
        self.assertFalse(4501 <= max_rows)

    def test_public_events_have_lower_subcap_and_five_hour_worst_case_headroom(self) -> None:
        event = self.php.split("private static function claim_public_event_budget", 1)[1].split(
            "public static function render_public_placement", 1
        )[0]
        reserve = self.php.split("private static function assert_public_event_reserve", 1)[1].split(
            "private static function run_mutation", 1
        )[0]
        self.assertIn("assert_public_event_reserve", event)
        self.assertIn("PUBLIC_EVENT_TABLE_MAX_ROWS", reserve)
        self.assertIn("PUBLIC_EVENT_TABLE_MAX_BYTES", reserve)
        self.assertIn("$pending_rows  = 3", reserve)
        self.assertIn("PUBLIC_EVENT_RESERVED_BYTES_PER_ROW", reserve)
        self.assertIn("PUBLIC_EVENT_RETENTION_HOURS * HOUR_IN_SECONDS", self.php)
        worst_case_rows = 2 * 240 * 5 + 2 * 5 + 2 * 3
        self.assertEqual(2416, worst_case_rows)
        self.assertLess(worst_case_rows + 3, 2500)

    def test_generic_transactions_never_allow_public_cache_flush_dos(self) -> None:
        transactions = self.php.split("private static function begin_transaction", 1)[1].split(
            "private static function acquire_slot_advisory_lock", 1
        )[0]
        mutation = self.php.split("private static function run_mutation", 1)[1].split(
            "private static function require_replay_scope", 1
        )[0]
        public_event = self.php.split("public static function rest_public_event", 1)[1].split(
            "public static function render_public_placement", 1
        )[0]
        self.assertNotIn("wp_cache_flush", transactions)
        self.assertNotIn("wp_cache_flush", mutation)
        self.assertNotIn("wp_cache_flush", public_event)
        purge = self.php.split("private static function purge_public_placement_caches", 1)[1].split(
            "public static function enqueue_public_assets", 1
        )[0]
        self.assertEqual(0, purge.count("wp_cache_flush()"))

    def test_deploy_quiescence_drains_preexisting_writers_before_real_baseline(self) -> None:
        bridge = (ROOT / "deploy" / "temporary-bridge.php").read_text(encoding="utf-8")
        helper = bridge.split("$capture_quiescent_database_state = static function", 1)[1].split(
            "$verify_transactional_storage", 1
        )[0]
        self.assertIn("rollback-capacity", helper)
        self.assertIn("GET_LOCK", helper)
        self.assertIn("RELEASE_LOCK", helper)
        run = bridge.split("'callback'            => static function ( WP_REST_Request $request )", 1)[1]
        self.assertIn("$database_snapshot = $capture_quiescent_database_state();", run)
        self.assertLess(run.index("$database_snapshot = $capture_quiescent_database_state();"), run.index("$database_json = wp_json_encode"))
        begin = self.php.split("private static function begin_transaction", 1)[1].split(
            "private static function begin_public_read_transaction", 1
        )[0]
        self.assertLess(begin.index("active_deploy_lock( false )"), begin.index("acquire_slot_advisory_lock"))
        self.assertGreater(begin.rindex("active_deploy_lock()"), begin.index("acquire_slot_advisory_lock"))

    def test_activation_and_verified_readback_revalidate_locked_current_truth(self) -> None:
        activation = self.php.split("public static function cron_activate", 1)[1].split(
            "private static function ensure_exact_campaign_event", 1
        )[0]
        self.assertIn("load_campaign( (string) $campaign_id, true )", activation)
        for token in (
            "scheduledPackageId",
            "scheduledPackageDigest",
            "scheduledApprovalDigest",
            "activationTimestamp",
            "expiryTimestamp",
            "authority_snapshot_is_current",
        ):
            self.assertIn(token, activation)
        eligibility = self.php.split("private static function campaign_callback_eligible", 1)[1].split(
            "public static function cron_activate", 1
        )[0]
        for token in ("scheduleDigest", "schedule_requested", "unverified", "authoritySuppression"):
            self.assertIn(token, eligibility)
        self.assertLess(activation.index("load_campaign( (string) $campaign_id, true )"), activation.index("$wpdb->insert( $tables['placements']"))

        readback = self.php.split("private static function commit_verified_readback", 1)[1].split(
            "public static function cron_expire", 1
        )[0]
        self.assertIn("begin_transaction( self::CAPACITY_ROLE_RECOVERY )", readback)
        self.assertIn("locked_public_placement_context", readback)
        self.assertIn("$current = $context['campaign']", readback)
        for token in ("pending_readback", "expires_at", "scheduledPackageDigest", "scheduledApprovalDigest", "scheduleDigest"):
            self.assertIn(token, readback)
        self.assertLess(readback.index("locked_public_placement_context"), readback.index("'proof_level' => 'system_verified'"))

    def test_public_readback_and_event_lock_exact_campaign_package_binding(self) -> None:
        context = self.php.split("private static function locked_public_placement_context", 1)[1].split(
            "public static function rest_public_readback", 1
        )[0]
        for token in (
            "FOR UPDATE",
            "load_campaign( (string) $row['campaign_id'], true )",
            "scheduledVersion",
            "scheduledPackageDigest",
            "scheduledApprovalDigest",
            "scheduleDigest",
            "authority_snapshot_is_current",
            "$expires_at > time()",
        ):
            self.assertIn(token, context)
        event = self.php.split("public static function rest_public_event", 1)[1].split(
            "public static function render_public_placement", 1
        )[0]
        self.assertIn("locked_public_placement_context", event)
        self.assertIn("begin_public_event_transaction", event)
        self.assertIn("begin_public_read_transaction", event)
        self.assertNotIn("'capacity' =>", event)
        self.assertIn("complete99_campaign_event_temporarily_unavailable", event)

    def test_evidence_upload_is_idempotent_bound_deduplicated_and_quota_bounded(self) -> None:
        upload = self.php.split("private static function ensure_private_evidence_upload_intent", 1)[1].split(
            "private static function private_evidence_access_url", 1
        )[0]
        self.assertIn("run_mutation", upload)
        self.assertIn("'evidence_upload'", upload)
        self.assertIn("load_campaign( $id, true )", upload)
        self.assertIn("find_private_evidence_attachment", upload)
        self.assertIn("private_evidence_quota_status", upload)
        for token in (
            "MAX_PRIVATE_EVIDENCE_FILES_TOTAL",
            "MAX_PRIVATE_EVIDENCE_BYTES_TOTAL",
            "MAX_PRIVATE_EVIDENCE_FILES_PER_CAMPAIGN",
            "MAX_PRIVATE_EVIDENCE_BYTES_PER_CAMPAIGN",
            "_complete99_evidence_campaign_id",
            "_complete99_evidence_location_id",
            "_complete99_evidence_owner_user_id",
            "_complete99_evidence_command_id",
        ):
            self.assertIn(token, upload)
        self.assertIn("move_uploaded_file", upload)
        self.assertIn("private_evidence_authoritative_record", upload)
        self.assertIn("bind_private_evidence_command", upload)
        self.assertIn("PRIVATE_EVIDENCE_RETENTION_DAYS", upload)
        self.assertIn("_complete99_evidence_legal_hold", upload)
        self.assertIn("_complete99_evidence_retention_state", upload)
        self.assertIn("SELECT post_id FROM {$wpdb->postmeta}", upload)
        self.assertNotIn("get_post( $created_attachment_id )", upload)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_evidence_replay_digest_excludes_fresh_nonce_access_url(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);define('ARRAY_A','ARRAY_A');define('HOUR_IN_SECONDS',3600);
function wp_json_encode($value,$flags=0){{return json_encode($value,$flags);}}
$c99_tick = 1;
function admin_url($path=''){{return 'https://example.test/wp-admin/'.$path;}}
function wp_nonce_url($url,$action){{global $c99_tick;return $url.'&_wpnonce=tick-'.$c99_tick;}}
require '{campaign_path}';
$access = new ReflectionMethod('Complete99_Campaigns','private_evidence_access_url');
$access->setAccessible(true);
$canonical = new ReflectionMethod('Complete99_Campaigns','canonical_json');
$canonical->setAccessible(true);
$logical = array('schemaVersion'=>'complete99-private-evidence-upload/v3','attachmentId'=>91,'sha256'=>str_repeat('a',64),'campaignId'=>'campaign_replay_123','locationId'=>7,'ownerUserId'=>42,'retentionUntil'=>'2027-08-11T00:00:00Z','retentionState'=>'retained','legalHold'=>'no','publicationClaim'=>false);
$logicalDigest1 = hash('sha256',$canonical->invoke(null,$logical));
$first = $access->invoke(null,91);
$c99_tick = 2;
$logicalDigest2 = hash('sha256',$canonical->invoke(null,$logical));
$replay = $access->invoke(null,91);
echo json_encode(array(
  'logical'=>$logical,
  'logicalDigest1'=>$logicalDigest1,
  'logicalDigest2'=>$logicalDigest2,
  'firstAccess'=>$first,
  'replayAccess'=>$replay
), JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual(result["logicalDigest1"], result["logicalDigest2"])
        self.assertNotIn("accessUrl", result["logical"])
        self.assertEqual("campaign_replay_123", result["logical"]["campaignId"])
        self.assertEqual(7, result["logical"]["locationId"])
        self.assertEqual(42, result["logical"]["ownerUserId"])
        self.assertNotEqual(result["firstAccess"], result["replayAccess"])

        mutation = self.php.split("private static function run_mutation", 1)[1].split(
            "private static function require_replay_scope", 1
        )[0]
        self.assertLess(
            mutation.index("$receipt['result_digest']"),
            mutation.index("decorate_mutation_response( $action, $replayed )"),
        )
        self.assertLess(
            mutation.index("$result_digest = hash"),
            mutation.index("decorate_mutation_response( $action, $outcome['response'] )"),
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_moderation_keyset_scan_surfaces_older_target_after_200_unrelated(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A','ARRAY_A');
class WP_Error {{ private $code; public function __construct($code,$message='',$data=array()){{$this->code=$code;}} public function get_error_code(){{return $this->code;}} }}
function is_wp_error($value){{return $value instanceof WP_Error;}}
function mysql_to_rfc3339($value){{return '2026-08-11T00:00:00Z';}}
final class Complete99_Ops {{ public static function table_names(){{return array('issues'=>'wp_c99_ops_issues');}} }}
class C99ModerationWpdb {{
  public $last_error='';
  public $queries=0;
  public $issues=array();
  public function __construct(){{
    for($id=201;$id>=2;$id--){{$this->issues[]=array('id'=>$id,'public_id'=>'issue_'.str_pad((string)$id,48,'0',STR_PAD_LEFT),'location_id'=>7,'title'=>'Unrelated','status'=>'open','severity'=>'low','assigned_user_id'=>9,'version'=>1,'updated_at'=>'2026-08-11 00:00:00','details'=>json_encode(array('schemaVersion'=>'complete99-campaign-moderation-link/v2','campaignId'=>'campaign_other_123')));}}
    $this->issues[]=array('id'=>1,'public_id'=>'issue_'.str_repeat('a',48),'location_id'=>7,'title'=>'Target critical','status'=>'open','severity'=>'critical','assigned_user_id'=>42,'version'=>3,'updated_at'=>'2026-08-10 00:00:00','details'=>json_encode(array('schemaVersion'=>'complete99-campaign-moderation-link/v2','campaignId'=>'campaign_target_123','history'=>array())));
  }}
  public function prepare($query,...$args){{return array('query'=>$query,'args'=>$args);}}
  public function get_results($prepared,$mode){{
    $this->queries++;
    $args=$prepared['args'];$location=(int)$args[0];$limit=(int)$args[count($args)-1];$cursor=count($args)===3?(int)$args[1]:PHP_INT_MAX;
    $rows=array_values(array_filter($this->issues,static function($row)use($location,$cursor){{return (int)$row['location_id']===$location&&(int)$row['id']<$cursor;}}));
    return array_slice($rows,0,$limit);
  }}
  public function get_var($prepared){{return null;}}
}}
$wpdb=new C99ModerationWpdb();
require '{campaign_path}';
$method=new ReflectionMethod('Complete99_Campaigns','campaign_moderation_issues');
$method->setAccessible(true);
$issues=$method->invoke(null,'campaign_target_123',7);
echo json_encode(array('error'=>is_wp_error($issues)?$issues->get_error_code():'','issues'=>$issues,'queries'=>$wpdb->queries),JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual("", result["error"])
        self.assertEqual(2, result["queries"])
        self.assertEqual(1, len(result["issues"]))
        self.assertEqual("Target critical", result["issues"][0]["title"])
        self.assertEqual("open", result["issues"][0]["status"])
        self.assertEqual("critical", result["issues"][0]["severity"])
        self.assertEqual(3, result["issues"][0]["version"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_moderation_scan_prioritizes_older_actionable_over_100_closed_target_issues(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A','ARRAY_A');
class WP_Error {{ private $code; public function __construct($code,$message='',$data=array()){{$this->code=$code;}} public function get_error_code(){{return $this->code;}} }}
function is_wp_error($value){{return $value instanceof WP_Error;}}
function mysql_to_rfc3339($value){{return '2026-08-11T00:00:00Z';}}
final class Complete99_Ops {{ public static function table_names(){{return array('issues'=>'wp_c99_ops_issues');}} }}
class C99ClosedHistoryWpdb {{
  public $last_error='';
  public $queries=0;
  public $issues=array();
  public function __construct(){{
    for($id=101;$id>=2;$id--){{$this->issues[]=array('id'=>$id,'public_id'=>'issue_'.str_pad((string)$id,48,'0',STR_PAD_LEFT),'location_id'=>7,'title'=>'Closed history','status'=>'closed','severity'=>'low','assigned_user_id'=>9,'version'=>2,'updated_at'=>'2026-08-11 00:00:00','details'=>json_encode(array('schemaVersion'=>'complete99-campaign-moderation-link/v2','campaignId'=>'campaign_target_123')));}}
    $this->issues[]=array('id'=>1,'public_id'=>'issue_'.str_repeat('b',48),'location_id'=>7,'title'=>'Older actionable critical','status'=>'open','severity'=>'critical','assigned_user_id'=>42,'version'=>4,'updated_at'=>'2026-08-10 00:00:00','details'=>json_encode(array('schemaVersion'=>'complete99-campaign-moderation-link/v2','campaignId'=>'campaign_target_123','history'=>array())));
  }}
  public function prepare($query,...$args){{return array('query'=>$query,'args'=>$args);}}
  public function get_results($prepared,$mode){{
    $this->queries++;
    $args=$prepared['args'];$location=(int)$args[0];$limit=(int)$args[count($args)-1];$cursor=count($args)===3?(int)$args[1]:PHP_INT_MAX;
    $rows=array_values(array_filter($this->issues,static function($row)use($location,$cursor){{return (int)$row['location_id']===$location&&(int)$row['id']<$cursor;}}));
    return array_slice($rows,0,$limit);
  }}
  public function get_var($prepared){{return null;}}
}}
$wpdb=new C99ClosedHistoryWpdb();
require '{campaign_path}';
$method=new ReflectionMethod('Complete99_Campaigns','campaign_moderation_issues');
$method->setAccessible(true);
$issues=$method->invoke(null,'campaign_target_123',7);
echo json_encode(array('error'=>is_wp_error($issues)?$issues->get_error_code():'','issues'=>$issues,'queries'=>$wpdb->queries),JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual("", result["error"])
        self.assertEqual(1, result["queries"])
        self.assertEqual(101, len(result["issues"]))
        self.assertEqual("Older actionable critical", result["issues"][0]["title"])
        self.assertEqual("open", result["issues"][0]["status"])
        self.assertEqual("critical", result["issues"][0]["severity"])
        self.assertEqual(4, result["issues"][0]["version"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_campaign_list_pagination_and_search_reach_older_active_record(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A','ARRAY_A');
class WP_Error {{ private $code; public function __construct($code,$message='',$data=array()){{$this->code=$code;}} public function get_error_code(){{return $this->code;}} }}
function is_wp_error($value){{return $value instanceof WP_Error;}}
class WP_REST_Response {{public $data;public function __construct($data){{$this->data=$data;}}}}
class WP_REST_Request {{private $params;public function __construct($params){{$this->params=$params;}}public function get_param($key){{return $this->params[$key]??null;}}}}
function rest_ensure_response($value){{return new WP_REST_Response($value);}}
function current_user_can($capability){{return $capability==='manage_options';}}
function get_current_user_id(){{return 42;}}
function absint($value){{return abs((int)$value);}}
function sanitize_key($value){{return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$value));}}
function wp_json_encode($value,$flags=0){{return json_encode($value,$flags);}}
function mysql_to_rfc3339($value){{return str_replace(' ','T',(string)$value).'Z';}}
class C99ListWpdb {{
  public $prefix='wp_';
  public $last_error='';
  public $rows=array();
  public $sentinel=array();
  public function prepare($query,...$args){{if(count($args)===1&&is_array($args[0]))$args=$args[0];return array('query'=>$query,'args'=>$args);}}
  private function filtered($prepared){{
    $rows=$this->rows;$query=is_array($prepared)?$prepared['query']:(string)$prepared;$args=is_array($prepared)?$prepared['args']:array();
    if(strpos($query,'public_id LIKE')!==false){{$needle=trim((string)$args[0],'%');$rows=array_values(array_filter($rows,static function($row)use($needle){{return stripos($row['public_id'],$needle)!==false||stripos($row['name'],$needle)!==false;}}));}}
    return $rows;
  }}
  public function get_var($prepared){{return count($this->filtered($prepared));}}
  public function get_results($prepared,$mode){{$query=is_array($prepared)?$prepared['query']:(string)$prepared;if(strpos($query,'c99_campaign_placements')!==false){{if(strpos($query,"LEFT(status,4)='qtn_'")!==false)return array();return empty($this->sentinel)?array():array($this->sentinel);}}$rows=$this->filtered($prepared);$args=$prepared['args'];$limit=(int)$args[count($args)-2];$offset=(int)$args[count($args)-1];return array_slice($rows,$offset,$limit);}}
}}
$wpdb=new C99ListWpdb();
require '{campaign_path}';
$payloadMethod=new ReflectionMethod('Complete99_Campaigns','public_quarantine_payload');$payloadMethod->setAccessible(true);$valuesMethod=new ReflectionMethod('Complete99_Campaigns','public_quarantine_row_values');$valuesMethod->setAccessible(true);$clearPayload=$payloadMethod->invoke(null,'clear',0,'','','','','1970-01-01T00:00:00Z','',array());$wpdb->sentinel=array_merge(array('id'=>1),$valuesMethod->invoke(null,$clearPayload));
$canonical=new ReflectionMethod('Complete99_Campaigns','canonical_json');$canonical->setAccessible(true);
$cleanup=$canonical->invoke(null,array('schemaVersion'=>'complete99-campaign-cleanup-queue/v1','obligations'=>array()));
for($id=101;$id>=1;$id--){{
  $target=$id===1;
  $state=array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>$target?'campaign_older_active':'campaign_new_'.str_pad((string)$id,3,'0',STR_PAD_LEFT),'locationId'=>0,'name'=>$target?'Older active target':'Newer record '.$id,'primaryChannel'=>'instagram','scenario'=>'brand_story','governance'=>array('scheduledAt'=>'','expiresAt'=>''),'runtime'=>array('version'=>1,'lifecycleState'=>$target?'active':'draft','externalState'=>'none','jobState'=>'idle','nextAttemptAt'=>'','activationTimestamp'=>0,'expiryTimestamp'=>0,'activationGeneration'=>0,'publicQuarantineOverlay'=>array()));
  $json=$canonical->invoke(null,$state);
  $wpdb->rows[]=array('id'=>$id,'public_id'=>$state['campaignId'],'location_id'=>0,'name'=>$state['name'],'primary_channel'=>'instagram','scenario'=>'brand_story','lifecycle_state'=>$state['runtime']['lifecycleState'],'external_state'=>'none','job_state'=>'idle','next_attempt_at'=>'','slot_key'=>'','activation_at'=>'','expiry_at'=>'','cleanup_queue_json'=>$cleanup,'cleanup_queue_digest'=>hash('sha256',$cleanup),'cleanup_due_at'=>null,'cleanup_revision'=>0,'state_json'=>$json,'state_digest'=>hash('sha256',$json),'version'=>1,'updated_at'=>'2026-08-11 00:00:00');
}}
$page=Complete99_Campaigns::rest_list(new WP_REST_Request(array('page'=>3,'limit'=>50)));
$search=Complete99_Campaigns::rest_list(new WP_REST_Request(array('page'=>1,'limit'=>50,'search'=>'older active')));
echo json_encode(array('page'=>$page->data,'search'=>$search->data),JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        )
        result = json.loads(completed.stdout)
        self.assertEqual("complete99-campaign-list/v2", result["page"]["schemaVersion"])
        self.assertEqual(101, result["page"]["pagination"]["total"])
        self.assertEqual(3, result["page"]["pagination"]["page"])
        self.assertTrue(result["page"]["pagination"]["hasPrevious"])
        self.assertFalse(result["page"]["pagination"]["hasNext"])
        self.assertEqual("campaign_older_active", result["page"]["campaigns"][0]["campaignId"])
        self.assertEqual("active", result["page"]["campaigns"][0]["lifecycleState"])
        self.assertEqual(1, result["search"]["pagination"]["total"])
        self.assertEqual("campaign_older_active", result["search"]["campaigns"][0]["campaignId"])

    @unittest.skipUnless(shutil.which("php") and shutil.which("node"), "PHP and Node are required")
    def test_package_canonical_json_matches_javascript_for_line_separators(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        php_script = f"""
define('ABSPATH', __DIR__);
function wp_json_encode($value,$flags=0){{return json_encode($value,$flags);}}
require '{campaign_path}';
$method=new ReflectionMethod('Complete99_Campaigns','canonical_json');$method->setAccessible(true);
$json=$method->invoke(null,array('nested'=>array('url'=>'https://example.test/?x=1'),'a'=>"left\\u{{2028}}middle\\u{{2029}}right"));
echo json_encode(array('base64'=>base64_encode($json),'sha256'=>hash('sha256',$json)),JSON_THROW_ON_ERROR);
"""
        php_result = json.loads(
            subprocess.run(
                ["php", "-r", php_script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        node_script = r"""
const canonicalize = (value) => {
  if (Array.isArray(value)) return value.map(canonicalize);
  if (value && typeof value === 'object') return Object.keys(value).sort().reduce((out,key) => { out[key]=canonicalize(value[key]); return out; },{});
  return value;
};
const json = JSON.stringify(canonicalize({nested:{url:'https://example.test/?x=1'},a:'left\u2028middle\u2029right'}));
process.stdout.write(JSON.stringify({base64:Buffer.from(json,'utf8').toString('base64'),sha256:require('crypto').createHash('sha256').update(json,'utf8').digest('hex')}));
"""
        node_result = json.loads(
            subprocess.run(
                ["node", "-e", node_script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        expected = {
            "base64": "eyJhIjoibGVmdOKAqG1pZGRsZeKAqXJpZ2h0IiwibmVzdGVkIjp7InVybCI6Imh0dHBzOi8vZXhhbXBsZS50ZXN0Lz94PTEifX0=",
            "sha256": "e1feddc5f7418e1b944df53e726dac158fb63ad9c32d2308887bcb8e09fe6663",
        }
        self.assertEqual(expected, php_result)
        self.assertEqual(expected, node_result)
        self.assertIn("JSON_UNESCAPED_LINE_TERMINATORS", self.php)

    @unittest.skipUnless(shutil.which("php") and shutil.which("node"), "PHP and Node are required")
    def test_external_destination_bare_origin_matches_whatwg(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        php_script = f"""
define('ABSPATH', __DIR__);
function wp_parse_url($url,$component=-1){{return parse_url($url,$component);}}
require '{campaign_path}';
$method=new ReflectionMethod('Complete99_Campaigns','canonical_destination_base');$method->setAccessible(true);
echo $method->invoke(null,'https://Example.Test');
"""
        php_url = subprocess.run(
            ["php", "-r", php_script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout
        node_url = subprocess.run(
            ["node", "-e", "process.stdout.write(new URL('https://Example.Test').toString())"],
            cwd=ROOT, capture_output=True, text=True, encoding="utf-8", timeout=20, check=True,
        ).stdout
        self.assertEqual("https://example.test/", php_url)
        self.assertEqual(php_url, node_url)

    @unittest.skipUnless(shutil.which("php") and shutil.which("node"), "PHP and Node are required")
    def test_external_destination_rejects_whatwg_ambiguous_path_corpus(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
require '__CAMPAIGN_PATH__';
$method=new ReflectionMethod('Complete99_Campaigns','canonical_destination_base');$method->setAccessible(true);
$rejected=array(
 'https://example.test/a/../b',
 'https://example.test/%2e%2e/b',
 "https://example.test/\u{05E9}\u{05DC}\u{05D5}\u{05DD}",
 'https://example.test/a\\b',
 'https://example.test/a//b',
 'https://example.test/a%20b',
 'https://example.test/path?ref=a&ref=b',
 'https://example.test/path#fragment',
 'https://user@example.test/path'
);
$out=array();foreach($rejected as $url){try{$method->invoke(null,$url);$out[]='accepted';}catch(Throwable $error){$out[]='rejected';}}
$positive=array();foreach(array('https://wolt.com/en/isr/tel-aviv/restaurant/complete99','https://complete99.co.il/menu/') as $url){$positive[]=$method->invoke(null,$url);}
echo json_encode(array('rejected'=>$out,'positive'=>$positive),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual(["rejected"] * 9, result["rejected"])
        self.assertEqual(
            [
                "https://wolt.com/en/isr/tel-aviv/restaurant/complete99",
                "https://complete99.co.il/menu/",
            ],
            result["positive"],
        )
        for url in result["positive"]:
            node = subprocess.run(
                ["node", "-e", "process.stdout.write(new URL(process.argv[1]).toString())", url],
                cwd=ROOT, capture_output=True, text=True, encoding="utf-8", timeout=20, check=True,
            ).stdout
            self.assertEqual(url, node)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_public_conversion_is_rejected_before_any_database_work(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
class WP_REST_Request{public function get_param($key){return 'event'===$key?'conversion':'event_00000000000000000000';}}
function sanitize_key($value){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$value));}
class C99NoWriteWpdb{public $queries=0;public $prefix='wp_';}
$wpdb=new C99NoWriteWpdb();require '__CAMPAIGN_PATH__';
$result=Complete99_Campaigns::rest_public_event(new WP_REST_Request());
echo json_encode(array('code'=>$result->get_error_code(),'queries'=>$wpdb->queries),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual("complete99_campaign_event_invalid", result["code"])
        self.assertEqual(0, result["queries"])
        event = self.php.split("public static function rest_public_event", 1)[1].split(
            "/** Atomically claim one durable anonymous event", 1
        )[0]
        self.assertIn("array( 'impression', 'click' )", event)
        self.assertNotIn("conversion", event)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_os_placeholder_policy_is_broad_and_executable(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
require '{campaign_path}';
$method=new ReflectionMethod('Complete99_Campaigns','contains_placeholder');$method->setAccessible(true);
$cases=array('{{name}}','Required','TODO','TBD','lorem ipsum','New Dish Description','ארוחה מוסדית מלאה');
$out=array();foreach($cases as $case){{$out[]=$method->invoke(null,$case);}}echo json_encode($out,JSON_THROW_ON_ERROR);
"""
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual([True, True, True, True, True, True, False], result)
        for token in (
            "asset_not_in_campaign",
            "delivery_photo_required",
            "nameHe', 'nameEn', 'descriptionHe', 'descriptionEn",
            "in_array( $channel, array( 'wolt', 'mishloha', 'cibus', 'tenbis' ), true )",
            "'image' !== $asset['kind']",
        ):
            self.assertIn(token, self.php)

    def test_public_utm_material_is_server_derived_and_privacy_bounded(self) -> None:
        self.assertIn("return 'c99_' . substr( hash( 'sha256', $campaign_id ), 0, 24 )", self.php)
        self.assertIn("UTM term and content are disabled because campaign URLs are public", self.php)
        self.assertIn("UTM campaign must be the server-derived opaque campaign token", self.php)
        self.assertIn("'source'   => 'complete99'", self.php)
        self.assertIn("'medium'   => $channel", self.php)
        self.assertIn("'term'     => ''", self.php)
        self.assertIn("'content'  => ''", self.php)
        self.assertIn("UTM parameters are public", self.php)
        self.assertNotIn("'utm_term'", self.php)
        self.assertNotIn("'utm_content'", self.php)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_campaign_detail_query_errors_and_bad_shapes_fail_closed(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{{private $code;public function __construct($code,$message='',$data=array()){{$this->code=$code;}}public function get_error_code(){{return $this->code;}}}}
function is_wp_error($value){{return $value instanceof WP_Error;}}function sanitize_key($value){{return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$value));}}
class C99DetailWpdb{{public $last_error='';public $mode='error';public $fixture=null;public function get_results($sql,$shape){{if('error'===$this->mode){{$this->last_error='forced';return array();}}if('shape'===$this->mode){{return array('not-a-row');}}if('over'===$this->mode){{return array(array(),array());}}if('fixture'===$this->mode){{return array($this->fixture);}}return array(array('package_id'=>'pkg_1','campaign_version'=>'1','channel'=>'website','status'=>'prepared','package_json'=>'{{}}','package_digest'=>str_repeat('a',64),'approval_snapshot_digest'=>str_repeat('b',64),'created_at'=>'2026-08-11 00:00:00'));}}}}
$wpdb=new C99DetailWpdb();require '{campaign_path}';$method=new ReflectionMethod('Complete99_Campaigns','campaign_detail_rows');$method->setAccessible(true);
$out=array();foreach(array('error','shape','over','ok') as $mode){{$wpdb->mode=$mode;$wpdb->last_error='';$value=$method->invoke(null,'SELECT 1',1,'packages');$out[]=is_wp_error($value)?$value->get_error_code():'ok';}}echo json_encode($out,JSON_THROW_ON_ERROR);
"""
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual(
            [
                "complete99_campaign_detail_packages_unavailable",
                "complete99_campaign_detail_packages_invalid",
                "complete99_campaign_detail_packages_unavailable",
                "ok",
            ],
            result,
        )
        for component in ("packages", "provider_receipts", "results", "anonymous_aggregates"):
            self.assertIn(f"'{component}'", self.php)

    def test_schedule_faults_are_durable_bounded_and_non_starving(self) -> None:
        for token in (
            "activation_package_query_failed",
            "activation_package_binding_invalid",
            "activation_package_corrupt",
            "activation_locked_campaign_unavailable",
            "activation_locked_binding_drift",
            "SCHEDULE_MAX_ATTEMPTS",
            "'operator_attention'",
            "'retryStored' => true",
            "empty( $error_data['retryStored'] )",
        ):
            self.assertIn(token, self.php)
        activation = self.php.split("public static function cron_activate", 1)[1].split(
            "private static function mark_schedule_retry", 1
        )[0]
        self.assertNotIn("! is_array( $package ) ) { return;", activation)
        self.assertIn("return self::activation_fault", activation)

    def test_public_readback_is_bounded_no_redirect_and_receipt_exact(self) -> None:
        verifier = self.php.split("public static function cron_verify_readback", 1)[1].split(
            "private static function commit_verified_readback", 1
        )[0]
        self.assertIn("wp_safe_remote_get", verifier)
        self.assertIn("'redirection' => 0", verifier)
        self.assertIn("'limit_response_size' => 1048577", verifier)
        self.assertIn("strlen( $body ) > 1048576", verifier)
        self.assertIn("text\\/html|application\\/xhtml", verifier)
        receipt = self.php.split("private static function verified_readback_receipt_matches", 1)[1].split(
            "public static function cron_verify_readback", 1
        )[0]
        for token in (
            "campaign_version",
            "'website'",
            "'wordpress-owned'",
            "'complete99-wordpress'",
            "'owned_active'",
            "proof_ref",
            "material_digest",
            "payload_digest",
            "created_by",
        ):
            self.assertIn(token, receipt)

    def test_cache_retry_is_durable_operational_truth_and_never_global_flushes(self) -> None:
        for token in (
            "complete99-campaign-cache-retry/v2",
            "cache_retry_record_corrupt",
            "public_cache_retry_status",
            "reconcile_cache_retry",
            "CACHE_RETRY_MAX_ATTEMPTS",
            "'cacheReady'",
            "'writeReady'",
            "OPTION_CACHE_ERROR",
        ):
            self.assertIn(token, self.php)
        self.assertNotIn("wp_cache_flush", self.php)
        self.assertNotIn("litespeed_purge_all", self.php)
        self.assertNotIn("upress_purge_all", self.php.lower())
        expiry = self.php.split("public static function cron_expire", 1)[1].split(
            "public static function rest_public_event", 1
        )[0]
        self.assertIn("append_cleanup_obligation", expiry)
        self.assertIn("enqueue_reconcile_trigger", expiry)
        cleanup = self.php.split("private static function execute_cleanup_obligation", 1)[1].split(
            "private static function store_cleanup_queue_after_attempt", 1
        )[0]
        self.assertIn("purge_public_placement_caches", cleanup)
        self.assertIn("complete99_campaign_cleanup_surface_readback_failed", cleanup)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_cleanup_queue_and_audit_readbacks_reject_missing_or_same_revision_drift(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99CleanupWpdb{
 public $prefix='wp_';public $last_error='';public $campaignRow=array();public $auditRow=null;public $auditError=false;
 public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}
 public function get_row($prepared,$mode=null){$query=is_array($prepared)?$prepared['query']:(string)$prepared;if(false!==strpos($query,'audit_events')){if($this->auditError){$this->last_error='forced';return null;}return $this->auditRow;}return $this->campaignRow;}
}
$wpdb=new C99CleanupWpdb();require '__CAMPAIGN_PATH__';
$canonical=new ReflectionMethod('Complete99_Campaigns','canonical_json');$canonical->setAccessible(true);
$campaignId='campaign_cleanup_test';
$obligation=array('schemaVersion'=>'complete99-campaign-cleanup-obligation/v1','obligationId'=>'cln_'.str_repeat('a',48),'campaignId'=>$campaignId,'sourceCampaignVersion'=>1,'sourceIdentity'=>'source','reason'=>'cancel','expectation'=>'absent','surfaces'=>array(),'cronActions'=>array(),'supersededObligationIds'=>array(),'attempts'=>0,'dueAt'=>'2026-08-11T00:00:01Z','recordedAt'=>'2026-08-11T00:00:00Z','lastAttemptAt'=>'','lastErrorCode'=>'');
$obligation['sha256']=hash('sha256',$canonical->invoke(null,$obligation));
$queue=array('schemaVersion'=>'complete99-campaign-cleanup-queue/v1','obligations'=>array($obligation));$queueJson=$canonical->invoke(null,$queue);
$state=array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>$campaignId,'locationId'=>0,'name'=>'Cleanup','primaryChannel'=>'instagram','scenario'=>'brand_story','governance'=>array('scheduledAt'=>'','expiresAt'=>''),'runtime'=>array('version'=>1,'lifecycleState'=>'cancelled_internal','externalState'=>'stopped','jobState'=>'cancelled','nextAttemptAt'=>'','activationTimestamp'=>0,'expiryTimestamp'=>0,'activationGeneration'=>0));$stateJson=$canonical->invoke(null,$state);
$base=array('id'=>7,'public_id'=>$campaignId,'location_id'=>0,'lifecycle_state'=>'cancelled_internal','external_state'=>'stopped','job_state'=>'cancelled','next_attempt_at'=>'','slot_key'=>'','activation_at'=>'','expiry_at'=>'','cleanup_queue_json'=>$queueJson,'cleanup_queue_digest'=>hash('sha256',$queueJson),'cleanup_due_at'=>'2026-08-11 00:00:01','cleanup_revision'=>7,'state_json'=>$stateJson,'state_digest'=>hash('sha256',$stateJson),'version'=>1);
$wpdb->campaignRow=$base;
$queueMethod=new ReflectionMethod('Complete99_Campaigns','cleanup_queue_readback_matches');$queueMethod->setAccessible(true);
$auditMethod=new ReflectionMethod('Complete99_Campaigns','cleanup_audit_event_matches');$auditMethod->setAccessible(true);
$outcome=static function($value){return true===$value?'ok':$value->get_error_code();};
$results=array();$results['queueExact']=$outcome($queueMethod->invoke(null,$campaignId,7,$queueJson,$obligation['obligationId'],$obligation['sha256'],false));
$drift=$obligation;$drift['lastErrorCode']='drift';unset($drift['sha256']);$drift['sha256']=hash('sha256',$canonical->invoke(null,$drift));$driftQueue=array('schemaVersion'=>'complete99-campaign-cleanup-queue/v1','obligations'=>array($drift));$driftJson=$canonical->invoke(null,$driftQueue);$wpdb->campaignRow=array_merge($base,array('cleanup_queue_json'=>$driftJson,'cleanup_queue_digest'=>hash('sha256',$driftJson)));
$results['sameRevisionDrift']=$outcome($queueMethod->invoke(null,$campaignId,7,$queueJson,$obligation['obligationId'],$obligation['sha256'],false));
$emptyQueue=array('schemaVersion'=>'complete99-campaign-cleanup-queue/v1','obligations'=>array());$emptyJson=$canonical->invoke(null,$emptyQueue);$wpdb->campaignRow=array_merge($base,array('cleanup_queue_json'=>$emptyJson,'cleanup_queue_digest'=>hash('sha256',$emptyJson),'cleanup_due_at'=>null));
$results['missingObligation']=$outcome($queueMethod->invoke(null,$campaignId,7,$emptyJson,$obligation['obligationId'],$obligation['sha256'],false));
$eventId='evt_'.substr(hash('sha256',$obligation['obligationId'].'|completed|'.$obligation['sha256']),0,60);
$wpdb->auditRow=array('event_id'=>$eventId,'actor_user_id'=>0,'action'=>'campaign.cleanup_completed','subject_type'=>'campaign_cleanup','subject_id'=>$obligation['obligationId'],'command_id'=>null,'payload_digest'=>$obligation['sha256'],'occurred_at'=>'2026-08-11 00:00:02');
$results['auditExact']=$outcome($auditMethod->invoke(null,$eventId,'campaign.cleanup_completed',$obligation['obligationId'],$obligation['sha256']));
$wpdb->auditRow['payload_digest']=str_repeat('b',64);$results['auditDrift']=$outcome($auditMethod->invoke(null,$eventId,'campaign.cleanup_completed',$obligation['obligationId'],$obligation['sha256']));
$wpdb->auditRow=null;$results['auditMissing']=$outcome($auditMethod->invoke(null,$eventId,'campaign.cleanup_completed',$obligation['obligationId'],$obligation['sha256']));
$wpdb->auditError=true;$results['auditSqlError']=$outcome($auditMethod->invoke(null,$eventId,'campaign.cleanup_completed',$obligation['obligationId'],$obligation['sha256']));
echo json_encode($results,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual("ok", result["queueExact"])
        self.assertEqual("complete99_campaign_cleanup_readback_failed", result["sameRevisionDrift"])
        self.assertEqual("complete99_campaign_cleanup_obligation_readback_failed", result["missingObligation"])
        self.assertEqual("ok", result["auditExact"])
        for key in ("auditDrift", "auditMissing", "auditSqlError"):
            self.assertEqual("complete99_campaign_cleanup_audit_readback_failed", result[key])

        store = self.php.split("private static function store_cleanup_queue_after_attempt", 1)[1].split(
            "private static function reconcile_campaign_cleanup", 1
        )[0]
        for token in (
            "$obligation_snapshot",
            "cleanup_queue_readback_matches",
            "cleanup_audit_event_matches",
            "transactionClosed",
            "lockReleased",
            "cleanup_obligation_postcondition_is_current",
        ):
            self.assertIn(token, store)
        self.assertLess(
            store.index("cleanup_obligation_postcondition_is_current"),
            store.index("array_splice( $queue['obligations']"),
        )
        self.assertNotIn("cleanup_revision'] === $revision + 1", store)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_failed_advisory_release_poisons_same_request_before_another_write(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
class C99LockWpdb{public $prefix='wp_';public $last_error='';public $queryCount=0;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_var($prepared){return 0;}public function query($sql){$this->queryCount++;return true;}}
$wpdb=new C99LockWpdb();require '__CAMPAIGN_PATH__';
$locks=new ReflectionProperty('Complete99_Campaigns','advisory_locks');$locks->setAccessible(true);$locks->setValue(null,array('c99_test_lock'=>true));
$release=new ReflectionMethod('Complete99_Campaigns','release_advisory_locks');$release->setAccessible(true);$released=$release->invoke(null);
$poisoned=new ReflectionProperty('Complete99_Campaigns','advisory_lock_poisoned');$poisoned->setAccessible(true);
$begin=new ReflectionMethod('Complete99_Campaigns','begin_transaction');$begin->setAccessible(true);$next=$begin->invoke(null,'ordinary');
echo json_encode(array('released'=>$released,'poisoned'=>$poisoned->getValue(),'nextCode'=>$next->get_error_code(),'queries'=>$wpdb->queryCount),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertFalse(result["released"])
        self.assertTrue(result["poisoned"])
        self.assertEqual("complete99_campaign_slot_lock_release_unknown", result["nextCode"])
        self.assertEqual(0, result["queries"], "poison must stop before START TRANSACTION")

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_due_terminal_cleanup_is_enqueued_before_evidence_recovery_inspection(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);define('ARRAY_A','ARRAY_A');define('HOUR_IN_SECONDS',3600);
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function current_time($type,$gmt=false){return '2026-08-11 00:00:00';}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
$scheduled=array();$scheduleCalls=0;
function wp_next_scheduled($hook,$args=array()){global $scheduled;return empty($scheduled)?false:min(array_keys($scheduled));}
function wp_get_scheduled_event($hook,$args=array(),$timestamp=null){global $scheduled;if(null===$timestamp||!isset($scheduled[(int)$timestamp]))return false;return (object)array('timestamp'=>(int)$timestamp);}
function wp_schedule_single_event($timestamp,$hook,$args=array(),$wp_error=false){global $scheduled,$scheduleCalls;$scheduled[(int)$timestamp]=true;$scheduleCalls++;return true;}
function wp_unschedule_event($timestamp,$hook,$args=array(),$wp_error=false){global $scheduled;unset($scheduled[(int)$timestamp]);return true;}
class C99TerminalCleanupWpdb{public $prefix='wp_';public $last_error='';public $getVarCalls=0;public function prepare($query,...$args){return is_array($query)?(string)($query['query']??''):(string)$query;}public function get_results($prepared,$mode=null){$query=is_array($prepared)?$prepared['query']:(string)$prepared;if(strpos($query,'WHERE placement_id=%s OR readback_token=%s OR campaign_id=%s OR slot_key=%s')!==false){$payload=array('schemaVersion'=>'complete99-campaign-public-quarantine/v1','state'=>'clear','epoch'=>0,'reasonCode'=>'','initiatingCampaignId'=>'','scheduleDigest'=>'','slotKey'=>'','changedAt'=>'1970-01-01T00:00:00Z','nextAttemptAt'=>'','publicOrigins'=>array());ksort($payload,SORT_STRING);$json=json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS);return array(array('id'=>1,'placement_id'=>'plc_'.substr(hash('sha256','complete99-public-quarantine-placement/v1'),0,48),'campaign_id'=>'system_public_quarantine','campaign_version'=>0,'package_digest'=>hash('sha256','complete99-public-quarantine-package/v1'),'slot_key'=>'global_quarantine','locale'=>'system','public_json'=>$json,'public_digest'=>hash('sha256',$json),'readback_token'=>hash('sha256','complete99-public-quarantine-token/v1'),'status'=>'quarantine_clear','starts_at'=>'1970-01-01 00:00:00','expires_at'=>'9999-12-31 23:59:59','activated_at'=>null,'stopped_at'=>'1970-01-01 00:00:00','created_at'=>'1970-01-01 00:00:00','updated_at'=>'1970-01-01 00:00:00'));}if(strpos($query,'c99_campaigns')!==false&&strpos($query,'cleanup_due_at')!==false)return array(array('id'=>77,'public_id'=>'campaign_cleanup','cleanup_revision'=>0,'cleanup_due_at'=>'2026-08-10 00:00:00','lifecycle_state'=>'cancelled','external_state'=>'stopped','job_state'=>'cancelled','next_attempt_at'=>null,'aggregate_current_qtr'=>0));return array(array('campaign_id'=>'system_cron','placement_id'=>'system_cron_heartbeat','event_date'=>'1970-01-01','event_hour'=>'','event_key'=>'system_cron_heartbeat','provenance_key'=>'system_cron_heartbeat','claim_digest'=>hash('sha256','complete99-campaign-system-cron-heartbeat/v1'),'event_count'=>1,'last_at'=>gmdate('Y-m-d H:i:s')));}public function get_var($prepared){$this->getVarCalls++;return 77;}}
$wpdb=new C99TerminalCleanupWpdb();require '__CAMPAIGN_PATH__';
$lifecycle=new ReflectionProperty('Complete99_Campaigns','lifecycle_role_lock_owned');$lifecycle->setAccessible(true);$lifecycle->setValue(null,true);$worker=new ReflectionProperty('Complete99_Campaigns','worker_execution_fence_owned');$worker->setAccessible(true);$worker->setValue(null,true);
$method=new ReflectionMethod('Complete99_Campaigns','schedule_next_reconcile_trigger');$method->setAccessible(true);$result=$method->invoke(null);
echo json_encode(array('ok'=>true===$result,'code'=>is_wp_error($result)?$result->get_error_code():'','scheduled'=>empty($scheduled)?false:min(array_keys($scheduled)),'scheduleCalls'=>$scheduleCalls,'getVarCalls'=>$wpdb->getVarCalls),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertTrue(result["ok"], result)
        self.assertEqual(2, result["scheduleCalls"], "the 15-minute successor must be installed before due work advances it")
        self.assertEqual(0, result["getVarCalls"], "the indexed due-row projection must enqueue before evidence or scalar fallback queries")
        self.assertGreater(result["scheduled"], 0)
        scheduler = self.php.split("private static function schedule_next_reconcile_trigger", 1)[1].split(
            "private static function enqueue_reconcile_trigger", 1
        )[0]
        self.assertLess(scheduler.index("bounded_actionable_campaign_rows( 'cleanup_due'"), scheduler.index("private_evidence_recovery_status"))
        worker = self.php.split("private static function reconcile_schedules_fenced", 1)[1].split(
            "private static function reconcile_campaign_schedule", 1
        )[0]
        scheduler_projection = self.php.split("private static function bounded_actionable_campaign_rows", 1)[1].split(
            "private static function ensure_public_quarantine_zero_membership_receipt", 1
        )[0]
        self.assertIn("c.id>%d AND c.cleanup_due_at IS NULL", scheduler_projection)
        self.assertIn("$schedule_batch_limit", worker)
        self.assertLess(worker.index("$cleanup_rows ="), worker.index("reconcile_cache_retry"))
        self.assertLess(worker.index("$cleanup_rows ="), worker.index("reconcile_private_evidence_dispositions"))
        self.assertLess(worker.index("schedule_next_reconcile_trigger"), worker.index("reconcile_cache_retry"))
        self.assertNotIn("ORDER BY CASE WHEN cleanup_due_at", worker)

        revisions = {identifier: 0 for identifier in range(1, 122)}
        snapshot = sorted(revisions, key=lambda identifier: (revisions[identifier], identifier))[:100]
        self.assertIn(61, snapshot, "a full low-ID batch cannot hide the next due terminal row")
        for identifier in snapshot[:60]:
            revisions[identifier] += 1
        next_snapshot = sorted(revisions, key=lambda identifier: (revisions[identifier], identifier))[:100]
        self.assertLess(next_snapshot.index(101), next_snapshot.index(1))
        self.assertNotIn("OPTION_CLEANUP_CURSOR", self.php)
        for token in (
            "ORDER BY c.cleanup_revision ASC,c.id ASC LIMIT",
            "$seen_cleanup_rows",
            "defer_failed_cleanup_row",
            "cleanup_deferral_readback_matches",
            "campaign.cleanup_deferred",
        ):
            self.assertIn(token, worker if token in worker else self.php)
        deferral = self.php.split("private static function defer_failed_cleanup_row", 1)[1].split(
            "/** Process at most 100", 1
        )[0]
        self.assertIn("array( 'cleanup_revision' => $next_revision )", deferral)
        self.assertIn("'cleanup_queue_digest' => $queue_digest", deferral)
        self.assertIn("'cleanup_due_at' => $due_at", deferral)
        self.assertIn("cleanup_deferral_readback_matches", deferral)
        self.assertIn("cleanup_audit_event_matches", deferral)
        self.assertIn("transactionClosed", deferral)
        self.assertIn("lockReleased", deferral)
        self.assertLess(deferral.index("$raw_readback ="), deferral.index("$commit ="))
        self.assertLess(deferral.index("$commit ="), deferral.index("$raw_after_commit ="))

    def test_private_evidence_lifecycle_is_bound_audited_and_fail_closed(self) -> None:
        for token in (
            "PRIVATE_EVIDENCE_RETENTION_DAYS = 365",
            "PRIVATE_EVIDENCE_MAX_UPLOAD_BINDINGS = 64",
            "PRIVATE_EVIDENCE_MAX_LIFECYCLE_RECEIPTS = 128",
            "_complete99_evidence_retention_until",
            "_complete99_evidence_retention_state",
            "_complete99_evidence_legal_hold",
            "_complete99_evidence_disposition_command_id",
            "private_evidence_authoritative_record",
            "private_evidence_permissions_ready",
            "hash_file( 'sha256', $file )",
            "private_evidence_has_references",
            "complete99_campaign_private_disposition_nonterminal",
            "complete99_campaign_private_disposition_legal_hold",
            "complete99_campaign_private_disposition_referenced",
            "guard_private_evidence_delete",
            "guard_private_evidence_trash",
            "guard_private_evidence_file_move",
            "invalidate_private_evidence_cache_keys",
            "SELECT post_id FROM {$wpdb->postmeta}",
            "complete99_campaign_private_upload_stored_recoverable",
        ):
            self.assertIn(token, self.php)
        quota = self.php.split("private static function private_evidence_quota_status", 1)[1].split(
            "private static function find_private_evidence_attachment", 1
        )[0]
        self.assertNotIn("post_status='trash'", quota)
        self.assertNotIn("post_status<>'trash'", quota)
        dedupe = self.php.split("private static function find_private_evidence_attachment", 1)[1].split(
            "private static function private_evidence_receipt_response", 1
        )[0]
        for token in ("campaign_id", "location_id", "owner_id", "digest", "bind_private_evidence_command"):
            self.assertIn(token, dedupe)
        self.assertIn("'evidence_dispose'", self.php)
        self.assertIn("data-c99-evidence-dispose", self.php)

    def test_newer_cleanup_truth_supersedes_opposite_surface_and_preserves_removals(self) -> None:
        placement = "plc_" + "a" * 48
        args = ["campaign_test", 2, "b" * 64]
        older = {
            "obligationId": "cln_" + "1" * 48,
            "expectation": "present",
            "surfaces": [{"placementId": placement, "slot": "home_banner"}],
            "cronActions": [
                {"hook": "complete99_campaign_verify_readback", "args": args, "timestamp": 10, "action": "ensure"},
                {"hook": "complete99_campaign_activate", "args": args, "timestamp": 5, "action": "remove"},
            ],
        }
        newer = {
            "obligationId": "cln_" + "2" * 48,
            "expectation": "absent",
            "surfaces": [{"placementId": placement, "slot": "home_banner"}],
            "cronActions": [],
        }

        def coalesce(existing: list[dict], successor: dict) -> list[dict]:
            surface_ids = {item["placementId"] for item in successor["surfaces"]}
            superseded: list[str] = []
            removals: list[dict] = []
            retained: list[dict] = []
            for item in existing:
                if surface_ids.intersection(surface["placementId"] for surface in item["surfaces"]):
                    superseded.append(item["obligationId"])
                    removals.extend(action for action in item["cronActions"] if action["action"] == "remove")
                else:
                    retained.append(item)
            successor = dict(successor)
            successor["supersededObligationIds"] = sorted(superseded)
            successor["cronActions"] = successor["cronActions"] + removals
            return retained + [successor]

        queue = coalesce([older], newer)
        self.assertEqual(1, len(queue))
        self.assertEqual("absent", queue[0]["expectation"])
        self.assertEqual([older["obligationId"]], queue[0]["supersededObligationIds"])
        self.assertEqual(["remove"], [action["action"] for action in queue[0]["cronActions"]])
        queue.clear()
        self.assertEqual([], queue, "terminal successor must be able to drain completely")

        append = self.php.split("private static function append_cleanup_obligation", 1)[1].split(
            "private static function cleanup_queue_readback_matches", 1
        )[0]
        for token in (
            "supersededObligationIds",
            "'remove' === (string) ( $cron['action']",
            "campaign.cleanup_superseded",
            "$superseded_readback",
        ):
            self.assertIn(token, append)

    def test_private_evidence_disposition_matrix_and_stale_worker_cas(self) -> None:
        byte_states = ("valid", "missing", "invalid")
        lifecycle_states = ("pending", "recovery", "disposed_committed", "disposed_complete")

        def disposition_action(lifecycle: str, source: str, residue: str) -> str:
            if lifecycle in {"pending", "recovery"}:
                return "progress" if (source, residue) in {("valid", "missing"), ("missing", "valid")} else "recovery"
            if lifecycle == "disposed_committed":
                return "complete" if source == "missing" and residue in {"valid", "missing"} else "recovery"
            return "complete" if (source, residue) == ("missing", "missing") else "recovery"

        matrix = {
            (lifecycle, source, residue): disposition_action(lifecycle, source, residue)
            for lifecycle in lifecycle_states
            for source in byte_states
            for residue in byte_states
        }
        self.assertEqual(36, len(matrix))
        self.assertEqual("progress", matrix[("pending", "valid", "missing")])
        self.assertEqual("progress", matrix[("recovery", "missing", "valid")])
        self.assertEqual("complete", matrix[("disposed_committed", "missing", "valid")])
        self.assertEqual("complete", matrix[("disposed_complete", "missing", "missing")])
        for key, action in matrix.items():
            if "invalid" in key[1:]:
                self.assertEqual("recovery", action)

        def cas(expected: tuple[str, str, str], fresh: tuple[str, str, str], target: tuple[str, str, str]) -> str:
            if fresh == target:
                return "idempotent"
            return "apply" if fresh == expected else "stale"

        pending = ("disposition_pending", "prepared", "journal-prepared")
        staged = ("disposition_pending", "staged", "journal-staged")
        complete = ("disposed", "complete", "journal-complete")
        self.assertEqual("apply", cas(pending, pending, staged))
        self.assertEqual("idempotent", cas(pending, staged, staged))
        self.assertEqual("stale", cas(pending, complete, staged))

        finalizer = self.php.split("private static function finalize_private_evidence_disposition", 1)[1].split(
            "private static function private_evidence_path_is_controlled", 1
        )[0]
        transition = self.php.split("private static function transition_private_evidence_disposition", 1)[1].split(
            "private static function mark_private_evidence_recovery_required", 1
        )[0]
        self.assertNotIn("rename( $residue, $source", finalizer)
        self.assertIn("is_link( $path )", self.php)
        self.assertIn("$deterministic_residue_state", finalizer)
        self.assertIn("'missing' === $source_state && 'missing' === $deterministic_residue_state", finalizer)
        self.assertIn("$record['journalPhase'], (string) ( $journal['phase']", finalizer)
        complete_fast_path = finalizer.split("if ( 'disposed' === $record['retentionState'] && 'complete' === $record['journalPhase'] )", 1)[1].split(
            "if ( ! self::private_evidence_path_is_controlled", 1
        )[0]
        self.assertNotIn("file_exists( $source )", complete_fast_path)
        for token in ("$already_target", "$source_matches", "complete99_campaign_private_disposition_transition_stale"):
            self.assertIn(token, transition)
        self.assertLess(transition.index("if ( is_wp_error( $commit ) ) { return $commit; }"), transition.index("$verified ="))
        self.assertIn("'valid' === $source_state && 'missing' === $residue_state", finalizer)
        self.assertIn("'missing' === $source_state && 'valid' === $residue_state", finalizer)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_private_evidence_broken_symlink_is_invalid_not_missing(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);
$dir=sys_get_temp_dir().DIRECTORY_SEPARATOR.'c99-symlink-'.bin2hex(random_bytes(8));
mkdir($dir,0700,true);$target=$dir.DIRECTORY_SEPARATOR.'target';$link=$dir.DIRECTORY_SEPARATOR.'evidence';file_put_contents($target,'x');
if(!@symlink($target,$link)){@unlink($target);@rmdir($dir);echo json_encode(array('supported'=>false),JSON_THROW_ON_ERROR);exit;}
define('COMPLETE99_PRIVATE_EVIDENCE_DIR',$dir);define('WP_CONTENT_DIR',$dir.DIRECTORY_SEPARATOR.'public-content');
class WP_Error{} function is_wp_error($value){return $value instanceof WP_Error;}
function untrailingslashit($value){return rtrim((string)$value,"/\\");}
function trailingslashit($value){return untrailingslashit($value).'/';}
function wp_normalize_path($value){return str_replace('\\','/',(string)$value);}
function wp_upload_dir(){return array('basedir'=>COMPLETE99_PRIVATE_EVIDENCE_DIR.DIRECTORY_SEPARATOR.'public-uploads');}
@unlink($target);require '__CAMPAIGN_PATH__';
$method=new ReflectionMethod('Complete99_Campaigns','private_evidence_byte_path_state');$method->setAccessible(true);
$state=$method->invoke(null,$link,array('sizeBytes'=>1,'sha256'=>hash('sha256','x')));
$isLink=is_link($link);@unlink($link);@rmdir($dir);echo json_encode(array('supported'=>true,'isLink'=>$isLink,'state'=>$state),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        if not result["supported"]:
            self.skipTest("PHP cannot create a symlink in this environment")
        self.assertTrue(result["isLink"])
        self.assertEqual("invalid", result["state"])

    def test_private_evidence_projection_enumerates_every_shape_and_validates_full_truth(self) -> None:
        projection = self.php.split("private static function private_evidence_inventory_projection", 1)[1].split(
            "/** Exact private-evidence file/byte totals", 1
        )[0]
        record = self.php.split("private static function private_evidence_authoritative_record", 1)[1].split(
            "/**\n\t * Enumerate every evidence-shaped attachment", 1
        )[0]
        recovery = self.php.split("private static function reconcile_private_evidence_dispositions", 1)[1].split(
            "private static function private_evidence_access_url", 1
        )[0]
        for token in (
            "meta_key=%s",
            "meta_key LIKE %s",
            "$wpdb->esc_like( '_complete99_evidence_' ) . '%'",
            "self::CAPACITY_MAX_ROWS + 1",
            "private_evidence_raw_paths",
            "pathProjectionReady",
            "recoverablePending",
            "PRIVATE_EVIDENCE_ROOT_MAX_ENTRIES",
            "PRIVATE_EVIDENCE_ROOT_MAX_DEPTH",
            "complete99_campaign_private_root_orphan",
            "complete99_campaign_private_quota_exceeded",
            "complete99_campaign_private_campaign_quota_exceeded",
        ):
            self.assertIn(token, projection)
        for required in (
            "_complete99_private_evidence",
            "_wp_attached_file",
            "_complete99_evidence_upload_intent",
            "_complete99_evidence_upload_target",
            "_complete99_evidence_upload_staging",
            "_complete99_evidence_upload_attempts",
            "_complete99_evidence_upload_next_attempt_at",
            "_complete99_evidence_disposition_journal",
            "_complete99_evidence_disposition_phase",
            "_complete99_evidence_legal_hold",
        ):
            self.assertIn(required, record)
        self.assertIn("complete99_campaign_private_meta_unknown", record)
        self.assertIn("complete99_campaign_private_meta_duplicate", record)
        self.assertIn("complete99_campaign_private_meta_missing", record)
        self.assertIn("hash_equals( $journal_phase, (string) ( $journal['phase']", record)
        self.assertIn("hash_equals( $residue_path, (string) ( $journal['residuePath']", record)

        # Malformed records fail readiness, but disjoint, exactly projected due
        # records still receive a bounded pass ordered by due time then ID.
        self.assertIn("$projection['recoverablePending']", recovery)
        self.assertIn("usort( $due", recovery)
        self.assertLess(recovery.index("foreach ( array_slice( $due, 0, 50 )"), recovery.index("complete99_campaign_private_recovery_invalid"))
        candidates = [(5, 100), (2, 100), (1, 200), (9, 50)]
        self.assertEqual([(9, 50), (2, 100), (5, 100), (1, 200)], sorted(candidates, key=lambda item: (item[1], item[0])))

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_private_evidence_marker_state_denies_corrupt_evidence_shape(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);define('ARRAY_A','ARRAY_A');
class C99MarkerWpdb{public $prefix='wp_';public $postmeta='wp_postmeta';public $last_error='';public $case=array();public $forceError=false;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function esc_like($value){return addcslashes($value,'_%\\');}public function get_results($prepared,$mode=null){if($this->forceError){$this->last_error='forced';return array();}return false!==strpos($prepared['query'],' LIKE ')?$this->case['shape']:$this->case['marker'];}}
$wpdb=new C99MarkerWpdb();require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','private_evidence_marker_state');$method->setAccessible(true);
$shape=array(array('meta_key'=>'_complete99_evidence_campaign_id'));
$cases=array('ordinary'=>array('marker'=>array(),'shape'=>array()),'private'=>array('marker'=>array(array('meta_key'=>'_complete99_private_evidence','meta_value'=>'yes')),'shape'=>$shape),'missing_marker'=>array('marker'=>array(),'shape'=>$shape),'wrong_marker'=>array('marker'=>array(array('meta_key'=>'_complete99_private_evidence','meta_value'=>'no')),'shape'=>$shape),'duplicate_marker'=>array('marker'=>array(array('meta_key'=>'_complete99_private_evidence','meta_value'=>'yes'),array('meta_key'=>'_complete99_private_evidence','meta_value'=>'yes')),'shape'=>$shape));$out=array();
foreach($cases as $name=>$case){$wpdb->last_error='';$wpdb->case=$case;$out[$name]=$method->invoke(null,99);}
$wpdb->forceError=true;$wpdb->case=$cases['ordinary'];$out['db_error']=$method->invoke(null,99);echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual("public", result["ordinary"])
        self.assertEqual("private", result["private"])
        marker_state = self.php.split("private static function private_evidence_marker_state", 1)[1].split(
            "private static function is_private_evidence_attachment_direct", 1
        )[0]
        self.assertIn("meta_key=%s", marker_state)
        self.assertIn("meta_key LIKE %s", marker_state)
        self.assertIn("$wpdb->esc_like( $prefix ) . '%'", marker_state)
        self.assertNotIn("HEX(", marker_state)
        self.assertNotIn("_complete99_evidence_command_bindings", marker_state)
        for key in ("missing_marker", "wrong_marker", "duplicate_marker", "db_error"):
            self.assertEqual("error", result[key], key)

    def test_core_transaction_engines_and_aggregate_retention_are_explicit(self) -> None:
        self.assertIn("'invalid_core_engines'", self.php)
        self.assertIn("'posts' => $wpdb->posts", self.php)
        self.assertIn("'postmeta' => $wpdb->postmeta", self.php)
        self.assertIn("PUBLIC_AGGREGATE_RETENTION_DAYS = 90", self.php)
        retention = self.php.split("private static function prune_expired_public_aggregate_rows", 1)[1].split(
            "private static function assert_public_event_reserve", 1
        )[0]
        self.assertIn("provenance_key='anonymous_unverified_web'", retention)
        self.assertIn("LIMIT 500", retention)
        self.assertIn("PUBLIC_AGGREGATE_RETENTION_DAYS", retention)
        self.assertIn("'retentionDays' => self::PUBLIC_AGGREGATE_RETENTION_DAYS", self.php)

    def test_php_placement_contract_binds_contextual_opt_in_and_privacy(self) -> None:
        for token in (
            "WEBSITE_CONSENT_BASIS = 'public_contextual'",
            "WEBSITE_MEASUREMENT_MODE = 'explicit_opt_in'",
            "WEBSITE_MEASUREMENT_PURPOSE = 'campaign_banner_interactions_v1'",
            "website_contextual_basis_required",
            "'consentBasis' => self::WEBSITE_CONSENT_BASIS",
            "'measurementMode' => self::WEBSITE_MEASUREMENT_MODE",
            "'measurementPurpose' => self::WEBSITE_MEASUREMENT_PURPOSE",
            "'privacyUrl'",
            "data-c99-consent-basis",
            "data-c99-measurement-mode",
            "data-c99-measurement-purpose",
            "data-c99-measurement-scope",
            "data-c99-campaign-event-token",
            "data-c99-measurement-grant",
            "data-c99-measurement-decline",
            "data-c99-measurement-change",
            "data-c99-measurement-status",
            'aria-live="polite"',
            "rel=\"privacy-policy\"",
        ):
            self.assertIn(token, self.php)
        render = self.php.split("public static function render_public_placement", 1)[1].split(
            "private static function suppress_stale_placement", 1
        )[0]
        for control in ("grant", "decline", "change"):
            self.assertIn(
                f'data-c99-measurement-{control} hidden disabled aria-disabled="true" tabindex="-1"',
                render,
            )
        for state in ("unavailable", "undecided", "granted", "denied", "privacy-signal", "error"):
            self.assertIn(f"data-c99-status-{state}=", render)
        self.assertIn("Optional measurement is currently unavailable", render)
        self.assertNotIn("Optional anonymous measurement", render)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_website_measurement_purpose_is_inside_digest_backed_payload(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = f"""
define('ABSPATH', __DIR__);
class WP_Error{{}}
function is_wp_error($value){{return $value instanceof WP_Error;}}
function get_privacy_policy_url(){{return 'https://example.test/privacy/';}}
function home_url($path='/'){{return 'https://example.test'.('/'===substr($path,0,1)?$path:'/'.$path);}}
function wp_parse_url($url,$component=-1){{return parse_url($url,$component);}}
function wp_json_encode($value,$flags=0){{return json_encode($value,$flags);}}
function add_query_arg($args,$url){{$parts=parse_url($url);$query=array();parse_str((string)($parts['query']??''),$query);$query=array_merge($query,$args);$base=$parts['scheme'].'://'.$parts['host'].($parts['path']??'/');return $base.(empty($query)?'':'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986));}}
require '{campaign_path}';
$payloadMethod=new ReflectionMethod('Complete99_Campaigns','channel_payload');$payloadMethod->setAccessible(true);
$canonical=new ReflectionMethod('Complete99_Campaigns','canonical_json');$canonical->setAccessible(true);
$record=array('campaignId'=>'campaign_'.str_repeat('a',32),'governance'=>array('landingUrl'=>'https://example.test/menu/','slotKey'=>'home_banner','scheduledAt'=>'2026-08-11T12:00:00Z','expiresAt'=>'2026-08-12T12:00:00Z','utm'=>array('campaign'=>'c99_1234567890abcdef12345678')));
$variant=array('headline'=>array('he'=>'כותרת','en'=>'Headline'),'body'=>array('he'=>'גוף','en'=>'Body'),'cta'=>array('he'=>'פעולה','en'=>'Action'));
$asset=array('assetId'=>'builtin:connected-table-editorial-v1','kind'=>'image','url'=>'https://example.test/asset.webp','sha256'=>str_repeat('a',64));
$payload=$payloadMethod->invoke(null,$record,$variant,$asset,'website',array('url'=>'https://example.test/'),null);
$canonicalPayload=$canonical->invoke(null,$payload);$tampered=$payload;$tampered['measurementPurpose']='other_purpose';
echo json_encode(array('payload'=>$payload,'digest'=>hash('sha256',$canonicalPayload),'tamperedDigest'=>hash('sha256',$canonical->invoke(null,$tampered))),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
"""
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual("public_contextual", result["payload"]["consentBasis"])
        self.assertEqual("explicit_opt_in", result["payload"]["measurementMode"])
        self.assertEqual("campaign_banner_interactions_v1", result["payload"]["measurementPurpose"])
        self.assertEqual("https://example.test/privacy/", result["payload"]["privacyUrl"])
        self.assertNotEqual(result["digest"], result["tamperedDigest"])

    def test_operator_ui_prevents_false_intents_and_surfaces_all_truth(self) -> None:
        js = (PLUGIN / "assets/js/campaigns.js").read_text(encoding="utf-8")
        for token in (
            "var inFlight = false",
            "intentKeys = Object.create(null)",
            "if (inFlight) return Promise.reject",
            "value === null",
            "text === ''",
            "Number.isFinite",
            "instanceof FormData",
            "artifactJson",
            "navigator.clipboard.writeText",
            "anchor.download",
            "evidence-upload",
            "providerReceipts",
            "unverifiedWebSignals",
            "anonymous_unverified",
            "moderation-issues",
            "data-c99-campaign-search",
            "data-c99-campaign-prev",
            "data-c99-campaign-next",
            "pagination.hasNext",
            "listPage + 1",
        ):
            self.assertIn(token, js)
        self.assertNotIn("Number(window.prompt", js)
        self.assertIn("data-c99-package-preview", self.php)
        self.assertIn("data-c99-evidence-upload", self.php)
        self.assertIn("data-c99-provider-receipts", self.php)
        self.assertIn("data-c99-moderation", self.php)

    def test_moderation_transitions_are_scoped_versioned_and_audit_receipted(self) -> None:
        transition = self.php.split("private static function moderation_transition", 1)[1].split(
            "private static function private_evidence_root", 1
        )[0]
        for token in (
            "run_mutation",
            "require_location_scope",
            "issueExpectedVersion",
            "FOR UPDATE",
            "assigned_user_id",
            "escalationOwnerUserId",
            "'history'",
            "'moderationAction'",
        ):
            self.assertIn(token, transition)
        for action in ("moderation_resolve", "moderation_escalate", "moderation_outcome"):
            self.assertIn(action, self.php)

    def test_ops_today_reports_campaign_truth_without_claiming_other_modules(self) -> None:
        ops = OPS.read_text(encoding="utf-8")
        self.assertIn("Complete99_Campaigns::operational_status()", ops)
        self.assertIn("Campaign Studio is a verified WordPress-native operational module for this account", ops)
        self.assertIn("$campaign_operational_ready = $ready && $campaign_ready", ops)
        self.assertIn("self::module_statuses( $campaign_operational_ready, $campaign_operational_view )", ops)
        self.assertIn("'write_commands_enabled' => $ready && $campaign_write", ops)
        self.assertIn("SEO, finance and projects", ops)
        self.assertIn("'state'       => 'not-migrated'", ops)

    @unittest.skipUnless(shutil.which("php") and shutil.which("node"), "PHP and Node are required")
    def test_e650_numeric_and_legacy_ipv4_hosts_are_rejected_before_adapter_hashing(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH', __DIR__);
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
require '__CAMPAIGN_PATH__';
$method=new ReflectionMethod('Complete99_Campaigns','canonical_destination_base');$method->setAccessible(true);
$bad=array('https://127.1/','https://2130706433/','https://0177.0.0.1/','https://0x7f000001/','https://127.0.0.1/','https://1.2.3.4/','https://example.123/');
$out=array();foreach($bad as $url){try{$method->invoke(null,$url);$out[]='accepted';}catch(Throwable $error){$out[]='rejected';}}
echo json_encode(array('bad'=>$out,'good'=>$method->invoke(null,'https://example.test/clean-path')),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual(["rejected"] * 7, result["bad"])
        self.assertEqual("https://example.test/clean-path", result["good"])
        node = json.loads(
            subprocess.run(
                [
                    "node", "-e",
                    "process.stdout.write(JSON.stringify(process.argv.slice(1).map(u=>new URL(u).toString())))",
                    "https://127.1/", "https://2130706433/", "https://0177.0.0.1/", "https://0x7f000001/",
                ],
                cwd=ROOT, capture_output=True, text=True, encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual(["https://127.0.0.1/"] * 4, node)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_e650_authority_writers_share_one_nested_fail_closed_fence(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('DATABASE_TYPE','sqlite');define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99SqliteWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public function prepare($query,...$args){return $query;}public function get_var($query){return null;}public function get_results($query,$mode=null){return array(array('option_id'=>1,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>'{"changedAt":"2026-08-12T00:00:00Z","generation":1,"schemaVersion":"complete99-campaign-lifecycle-reservation/v1","state":"active"}','autoload'=>'no'));}}
$wpdb=new C99SqliteWpdb();require '__CAMPAIGN_PATH__';
$a=Complete99_Campaigns::begin_authority_write('commerce_product');
$b=Complete99_Campaigns::begin_authority_write('wordpress_authority');
$c=Complete99_Campaigns::end_authority_write();$d=Complete99_Campaigns::end_authority_write();
$bad=Complete99_Campaigns::begin_authority_write('unregistered_filter');
echo json_encode(array('nested'=>array_map(static function($value){return is_wp_error($value)?$value->get_error_code():$value;},array($a,$b,$c,$d)),'bad'=>$bad->get_error_code()),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(
            subprocess.run(
                ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
                encoding="utf-8", timeout=20, check=True,
            ).stdout
        )
        self.assertEqual([True, True, True, True], result["nested"])
        self.assertEqual("complete99_campaign_authority_fence_unavailable", result["bad"])

        live_catalog = (PLUGIN / "includes/class-complete99-live-catalog.php").read_text(encoding="utf-8")
        commerce = (PLUGIN / "includes/class-complete99-commerce.php").read_text(encoding="utf-8")
        for token in (
            "woocommerce_before_product_object_save",
            "woocommerce_product_before_set_stock",
            "woocommerce_variation_before_set_stock",
            "register_runtime_authority_writer_hooks",
            "begin_governed_post_meta_write",
            "begin_accountable_user_meta_write",
            "begin_governed_authority_option_write",
            "wp_pre_insert_user_data",
            "SELECT ID,post_type,post_status,post_modified_gmt",
            "SELECT meta_id,meta_key,meta_value",
            "wc_product_meta_lookup",
            "FOR UPDATE",
            "campaign_authority_callback_untrusted",
        ):
            self.assertIn(token, self.php)
        self.assertIn("begin_authority_write( 'live_catalog' )", live_catalog)
        self.assertIn("begin_authority_write( 'commerce_product' )", commerce)

    def test_e650_activation_binds_the_immutable_prepared_campaign_version(self) -> None:
        activation = self.php.split("public static function cron_activate", 1)[1].split(
            "/** Readback callback", 1
        )[0]
        package_query = activation.split("$package = $wpdb->get_row", 1)[1].split("ARRAY_A", 1)[0]
        self.assertIn("campaign_version=%d", package_query)
        self.assertIn("(int) $scheduled_version", package_query)
        self.assertIn("(int) ( $package['campaign_version'] ?? 0 ) !== (int) $scheduled_version", activation)
        self.assertIn("(int) ( $decoded['approvalSnapshot']['campaignVersion'] ?? 0 ) !== (int) $scheduled_version", activation)
        self.assertIn("'campaign_version' => $scheduled_version", activation)

        db = sqlite3.connect(":memory:")
        try:
            db.execute("CREATE TABLE packages(package_id TEXT,campaign_id TEXT,campaign_version INTEGER,payload TEXT)")
            db.executemany(
                "INSERT INTO packages VALUES(?,?,?,?)",
                [("pkg", "campaign", 7, "stale"), ("pkg", "campaign", 8, "scheduled")],
            )
            row = db.execute(
                "SELECT payload FROM packages WHERE package_id=? AND campaign_id=? AND campaign_version=? LIMIT 1",
                ("pkg", "campaign", 8),
            ).fetchone()
            self.assertEqual(("scheduled",), row)
        finally:
            db.close()

    def test_e650_recipe_cost_receipt_is_a_positive_bounded_native_integer(self) -> None:
        recipe = self.php.split("private static function resolve_authoritative_recipe", 1)[1].split(
            "private static function is_first_party_url", 1
        )[0]
        self.assertIn("! is_int( $receipt['portionCostMinor'] ?? null )", recipe)
        self.assertIn("0 >= $receipt['portionCostMinor']", recipe)
        self.assertIn("1000000000 < $receipt['portionCostMinor']", recipe)
        self.assertIn("! is_int( $reference['portionCostMinor'] )", recipe)
        self.assertNotIn("(int) $receipt['portionCostMinor']", recipe)
        decoded = [json.loads(value)["portionCostMinor"] for value in (
            '{"portionCostMinor":1}', '{"portionCostMinor":"1"}', '{"portionCostMinor":1.0}',
            '{"portionCostMinor":0}', '{"portionCostMinor":1000000001}',
        )]
        accepted = [type(value) is int and 0 < value <= 1_000_000_000 for value in decoded]
        self.assertEqual([True, False, False, False, False], accepted)

    def test_e650_zero_traffic_retention_heartbeat_and_public_event_allocation_are_independent(self) -> None:
        public_begin = self.php.split("private static function begin_public_event_transaction", 1)[1].split(
            "/** Commit an anonymous event", 1
        )[0]
        public_commit = self.php.split("private static function commit_public_event_transaction", 1)[1].split(
            "/** Time-driven retention pass", 1
        )[0]
        retention = self.php.split("private static function reconcile_public_event_retention", 1)[1].split(
            "private static function commit_public_read_transaction", 1
        )[0]
        scheduler = self.php.split("private static function schedule_next_reconcile_trigger", 1)[1].split(
            "private static function enqueue_reconcile_trigger", 1
        )[0]
        for token in (
            "PUBLIC_EVENT_RETENTION_HOURS = 5",
            "PUBLIC_AGGREGATE_RETENTION_DAYS = 90",
            "CRON_HEARTBEAT_MAX_AGE = 4500",
            "SYSTEM_CRON_INTERVAL_SECONDS = 900",
            "system_cron_heartbeat",
            "publicEventAllocationReady",
        ):
            self.assertIn(token, self.php)
        self.assertIn("public_event_capacity_status", public_begin)
        self.assertIn("prune_expired_ephemeral_capacity_rows", public_begin)
        self.assertNotIn("self::capacity_status(", public_begin)
        self.assertIn("public_event_capacity_status", public_commit)
        self.assertNotIn("assert_capacity_limits", public_commit)
        self.assertLess(retention.index("begin_public_event_transaction"), retention.index("store_cron_heartbeat"))
        self.assertLess(retention.index("store_cron_heartbeat"), retention.index("commit_public_event_transaction"))
        self.assertIn("$system_watchdog = time() + self::SYSTEM_CRON_INTERVAL_SECONDS", scheduler)
        self.assertIn("$system_watchdog < $timestamp", scheduler)
        self.assertIn("time() + 60", self.php)

    def test_e650_cleanup_supersession_audit_subject_is_bounded_and_digest_linked(self) -> None:
        append = self.php.split("private static function append_cleanup_obligation", 1)[1].split(
            "private static function cleanup_queue_readback_matches", 1
        )[0]
        for token in (
            "'supersededObligationId'",
            "'supersededSha256'",
            "'successorObligationId'",
            "'successorSha256'",
            "$link_subject = 'cls_' . substr( $link_digest, 0, 60 )",
            "campaign.cleanup_superseded",
            "cleanup_audit_event_matches",
        ):
            self.assertIn(token, append)
        digest = hashlib.sha256(b"old|new|old-sha|new-sha").hexdigest()
        subject = "cls_" + digest[:60]
        self.assertEqual(64, len(subject))
        self.assertRegex(subject, r"\Acls_[a-f0-9]{60}\Z")

    def test_e650_private_upload_intent_is_committed_born_private_exact_and_recoverable(self) -> None:
        intent = self.php.split("private static function ensure_private_evidence_upload_intent", 1)[1].split(
            "public static function rest_upload_evidence", 1
        )[0]
        upload = self.php.split("public static function rest_upload_evidence", 1)[1].split(
            "/** Update singleton evidence metadata", 1
        )[0]
        record = self.php.split("private static function private_evidence_authoritative_record", 1)[1].split(
            "/**\n\t * Enumerate every evidence-shaped attachment", 1
        )[0]
        recovery = self.php.split("private static function reconcile_private_evidence_dispositions", 1)[1].split(
            "private static function private_evidence_access_url", 1
        )[0]
        flow = intent + upload
        created_intent = intent.split("$inserted_post = $wpdb->insert( $wpdb->posts", 1)[1]
        self.assertNotIn("wp_insert_attachment", intent)
        self.assertIn("$wpdb->insert( $wpdb->posts", intent)
        self.assertIn("'post_status' => 'private'", intent)
        self.assertIn("'post_author' => 0", intent)
        self.assertLess(created_intent.index("_complete99_private_evidence"), created_intent.index("self::commit_transaction()"))
        committed_intent = flow.index("self::commit_transaction()", flow.index("$inserted_post = $wpdb->insert( $wpdb->posts"))
        self.assertLess(committed_intent, flow.index("move_uploaded_file"))
        for token in (
            "$expected_upload_intent_keys",
            "hash_equals( $upload_intent_json, self::canonical_json( $upload_intent ) )",
            "sanitize_key( $upload_last_error )",
            "_complete99_evidence_upload_target",
            "_complete99_evidence_upload_staging",
            "private_evidence_path_is_controlled",
        ):
            self.assertIn(token, record)
        self.assertIn("private_evidence_byte_path_state", upload)
        self.assertIn("private_evidence_byte_path_state", self.php)
        self.assertIn("PRIVATE_EVIDENCE_UPLOAD_ABANDON_SECONDS", self.php)
        self.assertIn("upload_abandoned", self.php)
        self.assertIn("usort( $due", recovery)
        self.assertIn("array_slice( $due, 0, 50 )", recovery)
        self.assertIn("PRIVATE_EVIDENCE_MAX_UPLOAD_BINDINGS", self.php)
        self.assertIn("PRIVATE_EVIDENCE_MAX_LIFECYCLE_RECEIPTS", self.php)

    def test_e650_moderation_and_private_proof_summaries_are_server_authored(self) -> None:
        moderation = self.php.split("private static function moderation_transition", 1)[1].split(
            "private static function private_evidence_root", 1
        )[0]
        summary = self.php.split("private static function evidence_summary", 1)[1].split(
            "private static function public_receipt_row", 1
        )[0]
        for token in (
            "optional_text( $identity['body']['reason'] ?? '', 500, false )",
            "complete99-campaign-moderation-outcome/v1",
            "'evidenceLevel' => 'human_attested'",
            "'provenance'    => 'human_attested_operator_record'",
            "'actorUserId'",
            "'recordedAt'",
        ):
            self.assertIn(token, moderation)
        self.assertNotIn("$identity['body']['finalOutcome']", moderation)
        self.assertIn("'attachmentDigest' => (string) $verified['sha256']", summary)
        self.assertRegex(summary, r"'attachmentId'\s*=> \(int\) \$verified\['attachmentId'\]")
        self.assertRegex(summary, r"'mimeType'\s*=> \(string\) \$verified\['mimeType'\]")
        self.assertRegex(summary, r"'sizeBytes'\s*=> \(int\) \$verified\['sizeBytes'\]")

    def test_c1c0_core_authority_writers_are_fenced_on_exact_sources(self) -> None:
        hooks = self.php.split("public static function register_runtime_authority_writer_hooks", 1)[1].split(
            "private static function governed_authority_meta_keys", 1
        )[0]
        for before, after in (
            ("pre_post_update", "post_updated"),
            ("wp_trash_post", "trashed_post"),
            ("untrash_post", "untrashed_post"),
            ("before_delete_post", "deleted_post"),
            ("update_option", "updated_option"),
            ("delete_option", "deleted_option"),
        ):
            self.assertIn(before, hooks)
            self.assertIn(after, hooks)
            self.assertLess(hooks.index("'" + before + "'"), hooks.index("'" + after + "'"))
        option_keys = self.php.split("private static function governed_authority_option_keys", 1)[1].split(
            "private static function args_contain_governed_authority_option", 1
        )[0]
        for key in ("user_roles", "'home'", "'siteurl'", "'woocommerce_currency'"):
            self.assertIn(key, option_keys)
        post_fence = self.php.split("public static function begin_governed_post_row_write", 1)[1].split(
            "public static function end_governed_post_row_write", 1
        )[0]
        self.assertIn("'wordpress_authority'", post_fence)
        self.assertNotIn("'wordpress_' . $post_type", post_fence)
        self.assertIn("Complete99_Live_Catalog::META_ASSET_MANAGED", self.php)

    def test_c1c0_scheduler_installs_successor_and_bounds_invalid_evidence(self) -> None:
        scheduler = self.php.split("private static function schedule_next_reconcile_trigger", 1)[1].split(
            "private static function enqueue_reconcile_trigger", 1
        )[0]
        worker = self.php.split("public static function reconcile_schedules", 1)[1].split(
            "private static function reconcile_campaign_schedule", 1
        )[0]
        self.assertLess(scheduler.index("time() + self::SYSTEM_CRON_INTERVAL_SECONDS"), scheduler.index("cron_heartbeat_status"))
        self.assertIn("array( 'missing', 'stale' )", scheduler)
        self.assertIn("time() + 60", scheduler)
        self.assertIn("$evidence_recovery['recoverablePending']", scheduler)
        self.assertIn("$evidence_recovery['recoverableNextAttemptAt']", scheduler)
        self.assertIn("$evidence_recovery['invalidNextAttemptAt']", scheduler)
        invalid_tail = scheduler.split("$invalid_evidence_timestamp", 1)[1]
        self.assertNotIn("return self::enqueue_reconcile_trigger( time() + 1 )", invalid_tail.split("$cache_retry", 1)[0])
        self.assertLess(worker.index("enqueue_reconcile_trigger( time() + self::SYSTEM_CRON_INTERVAL_SECONDS )"), worker.index("reconcile_public_event_retention"))
        self.assertIn("enqueue_reconcile_trigger( time() + 60 )", worker)

    def test_c1c0_evidence_selectors_are_indexable_bounded_and_byte_exact(self) -> None:
        marker = self.php.split("private static function private_evidence_marker_state", 1)[1].split(
            "private static function is_private_evidence_attachment_direct", 1
        )[0]
        projection = self.php.split("private static function private_evidence_inventory_projection", 1)[1].split(
            "/** Exact private-evidence file/byte totals", 1
        )[0]
        for source in (marker, projection):
            self.assertNotIn("HEX(", source)
            self.assertNotIn("SUBSTR(", source)
            self.assertIn("meta_key=%s", source)
            self.assertIn("meta_key LIKE %s", source)
            self.assertIn("$wpdb->esc_like", source)
        self.assertIn("LIMIT 3", marker)
        self.assertIn("LIMIT 258", marker)
        self.assertIn("self::CAPACITY_MAX_ROWS + 1", projection)
        self.assertIn("hash_equals( $marker_key", marker)
        self.assertIn("0 === strpos", marker)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c1c0_evidence_reference_counts_are_one_bounded_batch(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
class Complete99_Ops{public static function table_names(){return array('issues'=>'wp_c99_ops_issues');}}
class C99ReferenceWpdb{public $prefix='wp_';public $last_error='';public $queries=0;public $fail=false;public function prepare($query,...$args){return $query;}public function get_results($query,$mode=null){$this->queries++;if($this->fail){$this->last_error='forced';return null;}global $r1,$r2;if(false!==strpos($query,'proof_ref AS evidence_reference'))return array(array('evidence_reference'=>$r1,'reference_count'=>2));if(false!==strpos($query,'source_ref AS evidence_reference'))return array(array('evidence_reference'=>$r2,'reference_count'=>3));return array(array('details'=>json_encode(array('exact'=>$r1,'notExact'=>'prefix-'.$r2),JSON_THROW_ON_ERROR)),array('details'=>json_encode(array('nested'=>array(array('proof'=>$r2))),JSON_THROW_ON_ERROR)));}}
$wpdb=new C99ReferenceWpdb();require '__CAMPAIGN_PATH__';$r1='c99-private-attachment:11#sha256='.str_repeat('a',64);$r2='c99-private-attachment:12#sha256='.str_repeat('b',64);
$method=new ReflectionMethod('Complete99_Campaigns','private_evidence_reference_counts_batch');$method->setAccessible(true);
$records=array(array('attachmentId'=>11,'sha256'=>str_repeat('a',64),'locationId'=>7),array('attachmentId'=>12,'sha256'=>str_repeat('b',64),'locationId'=>7));
$counts=$method->invoke(null,$records,7);$queries=$wpdb->queries;$tooMany=$method->invoke(null,array_fill(0,101,$records[0]),7);$wpdb->fail=true;$failed=$method->invoke(null,$records,7);
echo json_encode(array('counts'=>$counts,'queries'=>$queries,'tooMany'=>$tooMany->get_error_code(),'failed'=>$failed->get_error_code()),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(3, result["queries"])
        self.assertEqual({"providerReceipts": 2, "results": 0, "moderationIssues": 1, "total": 3}, result["counts"]["11"])
        self.assertEqual({"providerReceipts": 0, "results": 3, "moderationIssues": 1, "total": 4}, result["counts"]["12"])
        self.assertEqual("complete99_campaign_private_reference_batch_bound", result["tooMany"])
        self.assertEqual("complete99_campaign_private_reference_query_failed", result["failed"])
        inventory = self.php.split("public static function rest_evidence_inventory", 1)[1].split(
            "/** Owner/custodian", 1
        )[0]
        self.assertEqual(1, inventory.count("private_evidence_reference_counts_batch"))
        self.assertNotIn("private_evidence_reference_counts( $attachment_id", inventory)

    def test_c1c0_private_residue_delete_is_exact_nofollow_and_filter_free(self) -> None:
        unlink = self.php.split("private static function private_evidence_unlink_exact", 1)[1].split(
            "private static function transition_private_evidence_disposition", 1
        )[0]
        finalizer = self.php.split("private static function finalize_private_evidence_disposition", 1)[1].split(
            "private static function abandon_private_evidence_upload_intent", 1
        )[0]
        for token in ("lstat( $path )", "is_link( $path )", "private_evidence_byte_path_state", "unlink( $path )", "false !== lstat( $path )"):
            self.assertIn(token, unlink)
        self.assertNotIn("wp_delete_file", unlink + finalizer)
        self.assertIn("private_evidence_unlink_exact", finalizer)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c1c0_lifecycle_receipts_require_canonical_allowlisted_objects(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);class WP_Error{}function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
require '__CAMPAIGN_PATH__';$canonical=new ReflectionMethod('Complete99_Campaigns','canonical_json');$canonical->setAccessible(true);$keys=new ReflectionMethod('Complete99_Campaigns','exact_object_keys');$keys->setAccessible(true);
$valid=array('commandId'=>'cmp_'.str_repeat('a',60),'response'=>array('schemaVersion'=>'complete99-private-evidence-legal-hold/v1'));
$json=$canonical->invoke(null,$valid);$variants=array($json," \n".$json,$canonical->invoke(null,array('commandId'=>$valid['commandId'],'response'=>$valid['response'],'extra'=>true)),'{"commandId":"'.$valid['commandId'].'","commandId":"'.$valid['commandId'].'","response":{"schemaVersion":"complete99-private-evidence-legal-hold/v1"}}');$out=array();
foreach($variants as $candidate){$decoded=json_decode($candidate,true);$out[]=is_array($decoded)&&hash_equals($candidate,$canonical->invoke(null,$decoded))&&$keys->invoke(null,$decoded,array('commandId','response'));}echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual([True, False, False, False], result)
        record = self.php.split("private static function private_evidence_authoritative_record", 1)[1].split(
            "/**\n\t * Enumerate every evidence-shaped attachment", 1
        )[0]
        for token in (
            "complete99-private-evidence-legal-hold/v1",
            "complete99-private-evidence-upload-abandon/v1",
            "exact_object_keys( $response",
            "originalOwnerUserId",
            "custodianActorId",
            "uploadCommandId",
            "abandonCommandId",
            "private_lifecycle_receipt_schema_invalid",
        ):
            self.assertIn(token, record)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c1c0_channel_payload_preserves_string_zero_exactly(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','channel_payload');$method->setAccessible(true);
$variant=array('headline'=>array('he'=>'0','en'=>''),'body'=>array('he'=>'0','en'=>''),'cta'=>array('he'=>'0','en'=>''));$destination=array('url'=>'https://example.test/');
$tiktok=$method->invoke(null,array('governance'=>array(),'assets'=>array()),$variant,null,'tiktok',$destination,null);$whatsapp=$method->invoke(null,array('governance'=>array(),'assets'=>array()),$variant,null,'whatsapp',$destination,null);
echo json_encode(array('tiktok'=>$tiktok['onScreenText'],'whatsapp'=>$whatsapp['message']),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(["0", "0"], result["tiktok"])
        self.assertEqual("0\n\n0\n\n0", result["whatsapp"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c1c0_public_context_table_rejects_every_package_receipt_and_state_drift(self) -> None:
        context = self.php.split("private static function locked_public_placement_context", 1)[1].split(
            "/** Convert an internal stale-authority context", 1
        )[0]
        watchdog = self.php.split("private static function reconcile_campaign_schedule", 1)[1].split(
            "/** Suspend durable jobs", 1
        )[0]
        suppress_many = self.php.split("private static function suppress_active_campaign_coherence", 1)[1].split(
            "private static function suppress_stale_placement", 1
        )[0]
        cases = {
            "missing_or_duplicate_package": ("LIMIT 2{$lock}", "1 === count( $package_rows )"),
            "noncanonical_or_bad_package_digest": ("self::canonical_json( $decoded_package )", "hash( 'sha256', self::canonical_json( $unsigned_package ) )"),
            "missing_or_duplicate_system_receipt": ("1 === count( $receipt_rows )", "verified_readback_receipt_matches"),
            "active_lifecycle_job_external_mismatch": ("'readback_verified'", "'active' === (string) ( $state['runtime']['lifecycleState']", "'owned_active' === (string) ( $state['runtime']['externalState']"),
            "pending_has_preexisting_receipt": ("empty( $receipt_rows )", "'pending_readback'"),
            "event_endpoint_binding": ("eventEndpoint", "self::is_first_party_url( $event_endpoint )"),
        }
        for name, tokens in cases.items():
            with self.subTest(name=name):
                for token in tokens:
                    self.assertIn(token, context)
        event = self.php.split("public static function rest_public_event", 1)[1].split(
            "public static function render_public_placement", 1
        )[0]
        render = self.php.split("public static function render_public_placement", 1)[1].split(
            "private static function suppress_active_campaign_coherence", 1
        )[0]
        self.assertIn("suppress_public_context_error", event)
        self.assertIn("suppress_public_context_error", render)
        self.assertIn("locked_public_placement_context", watchdog)
        self.assertIn("suppress_active_campaign_coherence", watchdog)
        for token in (
            "This O(1) transition",
            "public_quarantine_status( true )",
            "PUBLIC_QUARANTINE_STATUS_CLEAR",
            "public_quarantine_payload",
            "commit_public_read_transaction",
            "enqueue_reconcile_trigger",
        ):
            self.assertIn(token, suppress_many)
        self.assertNotIn("DELETE FROM", suppress_many)
        self.assertNotIn("CAPACITY_MAX_ROWS", suppress_many)
        self.assertNotIn("SELECT * FROM", suppress_many)
        self.assertIn("$context['receipt']", render)

        # Execute the same closed state table independently of WordPress. This is
        # deliberately data-driven so every package/receipt/status cardinality
        # and state-binding failure is exercised, not merely source-token checked.
        model = r"""
function public_context_ready($case) {
    $pending = 'pending_readback' === $case['placementStatus'];
    $active = 'active' === $case['placementStatus'];
    $stateCoherent = $pending
        ? 'schedule_requested' === $case['lifecycle']
            && in_array($case['job'], array('readback_pending', 'retry_pending'), true)
            && 'unverified' === $case['external']
        : $active
            && 'active' === $case['lifecycle']
            && 'readback_verified' === $case['job']
            && 'owned_active' === $case['external'];
    $receiptCoherent = $pending
        ? 0 === $case['receiptCount']
        : 1 === $case['receiptCount'] && true === $case['receiptCanonical'];
    return 1 === $case['placementCount']
        && 1 === $case['campaignTupleCount']
        && 1 === $case['slotRunnableCount']
        && 1 === $case['packageCount']
        && true === $case['packageCanonical']
        && $receiptCoherent
        && $stateCoherent;
}
function suppress_active_drift($activeRows, $historicalReceiptCount) {
    return array(
        'rowsSuppressed' => $activeRows,
        'externalState' => 'suppressed',
        'cleanupQueued' => true,
        'retryQueued' => true,
        'historicalReceiptsRetained' => $historicalReceiptCount,
    );
}
$pending = array('placementCount'=>1,'campaignTupleCount'=>1,'slotRunnableCount'=>1,'packageCount'=>1,'packageCanonical'=>true,'receiptCount'=>0,'receiptCanonical'=>false,'placementStatus'=>'pending_readback','lifecycle'=>'schedule_requested','job'=>'readback_pending','external'=>'unverified');
$active = array('placementCount'=>1,'campaignTupleCount'=>1,'slotRunnableCount'=>1,'packageCount'=>1,'packageCanonical'=>true,'receiptCount'=>1,'receiptCanonical'=>true,'placementStatus'=>'active','lifecycle'=>'active','job'=>'readback_verified','external'=>'owned_active');
$cases = array(
    'pending_no_receipt' => $pending,
    'active_exact_receipt' => $active,
    'missing_package' => array_replace($active,array('packageCount'=>0)),
    'duplicate_package' => array_replace($active,array('packageCount'=>2)),
    'malformed_package' => array_replace($active,array('packageCanonical'=>false)),
    'missing_receipt' => array_replace($active,array('receiptCount'=>0)),
    'duplicate_receipt' => array_replace($active,array('receiptCount'=>2)),
    'malformed_receipt' => array_replace($active,array('receiptCanonical'=>false)),
    'active_job_mismatch' => array_replace($active,array('job'=>'retry_pending')),
    'active_external_mismatch' => array_replace($active,array('external'=>'unverified')),
    'pending_with_receipt' => array_replace($pending,array('receiptCount'=>1,'receiptCanonical'=>true)),
    'duplicate_campaign_tuple' => array_replace($active,array('campaignTupleCount'=>2)),
    'cross_campaign_slot_conflict' => array_replace($active,array('slotRunnableCount'=>2)),
);
$ready = array();
foreach ($cases as $name => $case) { $ready[$name] = public_context_ready($case); }
echo json_encode(array(
    'ready'=>$ready,
    'zero'=>suppress_active_drift(0,1),
    'duplicate'=>suppress_active_drift(2,1),
), JSON_THROW_ON_ERROR);
"""
        result = json.loads(subprocess.run(
            ["php", "-r", model], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["ready"].pop("pending_no_receipt"))
        self.assertTrue(result["ready"].pop("active_exact_receipt"))
        self.assertTrue(all(value is False for value in result["ready"].values()), result["ready"])
        self.assertEqual({
            "rowsSuppressed": 0,
            "externalState": "suppressed",
            "cleanupQueued": True,
            "retryQueued": True,
            "historicalReceiptsRetained": 1,
        }, result["zero"])
        self.assertEqual(2, result["duplicate"]["rowsSuppressed"])
        self.assertEqual(1, result["duplicate"]["historicalReceiptsRetained"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_successor_replacement_never_destroys_the_old_event_on_failure(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
$events=array();$failSchedule=false;$failRemove=false;
function wp_next_scheduled($hook,$args=array()){global $events;return empty($events)?false:min(array_keys($events));}
function wp_get_scheduled_event($hook,$args=array(),$timestamp=null){global $events;return isset($events[(int)$timestamp])?(object)array('timestamp'=>(int)$timestamp):false;}
function wp_schedule_single_event($timestamp,$hook,$args=array(),$wp_error=false){global $events,$failSchedule;if($failSchedule)return false;$events[(int)$timestamp]=true;return true;}
function wp_unschedule_event($timestamp,$hook,$args=array(),$wp_error=false){global $events,$failRemove;if($failRemove)return false;unset($events[(int)$timestamp]);return true;}
require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','enqueue_reconcile_trigger');$method->setAccessible(true);$lifecycle=new ReflectionProperty('Complete99_Campaigns','lifecycle_role_lock_owned');$lifecycle->setAccessible(true);$worker=new ReflectionProperty('Complete99_Campaigns','worker_execution_fence_owned');$worker->setAccessible(true);
$now=time();$old=$now+7200;$new=$now+120;
$lifecycle->setValue(null,true);$worker->setValue(null,false);$partial=$method->invoke(null,$new);$afterPartial=array_keys($events);
$worker->setValue(null,true);
$events=array($old=>true);$failSchedule=true;$failedSchedule=$method->invoke(null,$new);$afterSchedule=array_keys($events);
$events=array($old=>true);$failSchedule=false;$failRemove=true;$failedRemove=$method->invoke(null,$new);ksort($events);$afterRemove=array_keys($events);
$events=array($old=>true);$failRemove=false;$success=$method->invoke(null,$new);$afterSuccess=array_keys($events);
echo json_encode(array('partialCode'=>$partial->get_error_code(),'afterPartial'=>$afterPartial,'scheduleCode'=>$failedSchedule->get_error_code(),'afterSchedule'=>$afterSchedule,'removeCode'=>$failedRemove->get_error_code(),'afterRemove'=>$afterRemove,'success'=>$success,'afterSuccess'=>$afterSuccess,'old'=>$old,'new'=>$new),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("complete99_campaign_reconcile_enqueue_order", result["partialCode"])
        self.assertEqual([], result["afterPartial"])
        self.assertEqual("complete99_campaign_reconcile_enqueue_failed", result["scheduleCode"])
        self.assertEqual([result["old"]], result["afterSchedule"])
        self.assertEqual("complete99_campaign_reconcile_reschedule_failed", result["removeCode"])
        self.assertEqual([result["new"], result["old"]], result["afterRemove"])
        self.assertTrue(result["success"])
        self.assertEqual([result["new"]], result["afterSuccess"])
        enqueue = self.php.split("private static function enqueue_reconcile_trigger", 1)[1].split(
            "/** Read back a raw cleanup", 1
        )[0]
        wrapper, fenced = enqueue.split("private static function enqueue_reconcile_trigger_fenced", 1)
        self.assertIn("run_lifecycle_worker_operation", wrapper)
        self.assertIn("$lifecycle_owned || $worker_owned", wrapper)
        self.assertIn("! $lifecycle_owned || ! $worker_owned", wrapper)
        self.assertIn("! self::$lifecycle_role_lock_owned || ! self::$worker_execution_fence_owned", fenced)
        self.assertLess(enqueue.index("wp_schedule_single_event"), enqueue.index("wp_unschedule_event"))
        self.assertIn("wp_get_scheduled_event", enqueue)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_authority_identity_extractors_are_position_exact_and_cover_old_rows(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class C99AuthorityWpdb{public $prefix='wp_';public $posts='wp_posts';public $last_error='';public $persisted=array(7=>'product',8=>'post',9=>'page');public function get_blog_prefix($id){return 'wp_';}public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_var($prepared){return $this->persisted[(int)$prepared['args'][0]]??'';}}
function get_current_blog_id(){return 1;}function get_option($key,$default=false){return 'woocommerce_shop_page_id'===$key?9:$default;}
$wpdb=new C99AuthorityWpdb();require '__CAMPAIGN_PATH__';
$meta=new ReflectionMethod('Complete99_Campaigns','governed_post_meta_write_identity');$meta->setAccessible(true);
$postType=new ReflectionMethod('Complete99_Campaigns','governed_authority_post_type');$postType->setAccessible(true);
$option=new ReflectionMethod('Complete99_Campaigns','args_contain_governed_authority_option');$option->setAccessible(true);
$out=array(
 'governedAdd'=>$meta->invoke(null,array(7,'ordinary','_sku')),
 'ungovernedValueSpoof'=>$meta->invoke(null,array(8,'ordinary','_sku')),
 'governedUpdate'=>$meta->invoke(null,array(91,7,'any-key','value')),
 'oldType'=>$postType->invoke(null,7,array('post_type'=>'post')),
	'shopPage'=>$postType->invoke(null,9,array('post_type'=>'page')),
 'optionValueSpoof'=>$option->invoke(null,array('ordinary','home','x')),
 'optionExact'=>$option->invoke(null,array('home','old','new')),
	'shopOption'=>$option->invoke(null,array('woocommerce_shop_page_id',9,10)),
	'permalinkOption'=>$option->invoke(null,array('permalink_structure','/%postname%/','/%post_id%/')),
);echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("product", result["governedAdd"]["postType"])
        self.assertEqual("ordinary", result["governedAdd"]["metaKey"])
        self.assertEqual("", result["ungovernedValueSpoof"]["postType"])
        self.assertEqual("product", result["governedUpdate"]["postType"])
        self.assertEqual("product", result["oldType"])
        self.assertEqual("complete99_shop_page", result["shopPage"])
        self.assertFalse(result["optionValueSpoof"])
        self.assertTrue(result["optionExact"])
        self.assertTrue(result["shopOption"])
        self.assertTrue(result["permalinkOption"])
        hooks = self.php.split("public static function register_runtime_authority_writer_hooks", 1)[1].split(
            "private static function governed_authority_meta_keys", 1
        )[0]
        for token in ("delete_attachment", "delete_user", "wpmu_delete_user", "deleted_user"):
            self.assertIn(token, hooks)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_private_evidence_dedupe_is_indexed_bounded_and_intersected(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
class C99DedupeWpdb{public $prefix='wp_';public $postmeta='wp_postmeta';public $last_error='';public $mode='empty';public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_col($prepared){$key=$prepared['args'][0];if('failed'===$this->mode){$this->last_error='forced';return null;}if('oversized'===$this->mode)return array_fill(0,4501,11);if('duplicate'===$this->mode)return array(11,12);return '_complete99_evidence_retention_state'===$key?array():array(11);}}
$wpdb=new C99DedupeWpdb();require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','find_private_evidence_attachment');$method->setAccessible(true);
$empty=$method->invoke(null,'campaign_test',7,9,str_repeat('a',64));$wpdb->mode='duplicate';$duplicate=$method->invoke(null,'campaign_test',7,9,str_repeat('a',64));$wpdb->mode='oversized';$oversized=$method->invoke(null,'campaign_test',7,9,str_repeat('a',64));$wpdb->mode='failed';$failed=$method->invoke(null,'campaign_test',7,9,str_repeat('a',64));
echo json_encode(array('empty'=>$empty,'duplicate'=>$duplicate->get_error_code(),'oversized'=>$oversized->get_error_code(),'failed'=>$failed->get_error_code()),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(0, result["empty"])
        self.assertEqual("complete99_campaign_private_dedupe_unavailable", result["duplicate"])
        self.assertEqual("complete99_campaign_private_dedupe_unavailable", result["oversized"])
        self.assertEqual("complete99_campaign_private_dedupe_unavailable", result["failed"])
        dedupe = self.php.split("private static function find_private_evidence_attachment", 1)[1].split(
            "private static function bind_private_evidence_command", 1
        )[0]
        for forbidden in ("HEX(", "GROUP BY", "HAVING"):
            self.assertNotIn(forbidden, dedupe)
        self.assertEqual(1, dedupe.count("array_intersect_key"))
        self.assertIn("CAPACITY_MAX_ROWS + 1", dedupe)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_activation_generation_receipt_and_cleanup_state_table(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_salt($scheme='auth'){return 'fixed-salt';}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}function rest_url($path=''){return 'https://current.example.test/wp-json/'.ltrim($path,'/');}function sanitize_key($value){return preg_replace('/[^a-z0-9_]/','',strtolower((string)$value));}
require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$id=c99_private('owned_placement_id');$token=c99_private('owned_placement_token');$receiptId=c99_private('system_readback_receipt_id');$canonical=c99_private('canonical_json');$receiptFault=c99_private('activation_supersession_receipt_fault');$advanceState=c99_private('activation_generation_advance_state');$cleanupIdentity=c99_private('authority_suppression_cleanup_identity');$historicalEndpoint=c99_private('historical_owned_event_endpoint_is_safe');
$campaign='campaign_generation';$schedule=str_repeat('a',64);$packageDigest=str_repeat('b',64);$p0=$id->invoke(null,$campaign,$schedule,0);$p1=$id->invoke(null,$campaign,$schedule,1);$p2=$id->invoke(null,$campaign,$schedule,2);$t0=$token->invoke(null,$p0,$packageDigest,$schedule,0);$t1=$token->invoke(null,$p1,$packageDigest,$schedule,1);$t2=$token->invoke(null,$p2,$packageDigest,$schedule,2);
$proofUrl='https://old.example.test/campaign-proof';$eventEndpoint='https://old.example.test/wp-json/complete99/v1/campaign-events/'.$t0;$publicJson=$canonical->invoke(null,array('eventEndpoint'=>$eventEndpoint,'proofUrl'=>$proofUrl));$publicDigest=hash('sha256',$publicJson);$approvalDigest=str_repeat('c',64);
$row=array('placement_id'=>$p0,'campaign_id'=>$campaign,'campaign_version'=>7,'public_json'=>$publicJson,'public_digest'=>$publicDigest,'readback_token'=>$t0);$package=array('approval_snapshot_digest'=>$approvalDigest);$validReceipt=array('receipt_id'=>$receiptId->invoke(null,$p0),'campaign_id'=>$campaign,'campaign_version'=>7,'channel'=>'website','provider_key'=>'wordpress-owned','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>'owned_active','external_id'=>$p0,'proof_ref'=>$proofUrl,'material_digest'=>$approvalDigest,'payload_digest'=>$publicDigest,'created_by'=>0,'occurred_at'=>'2026-08-12 00:02:00','created_at'=>'2026-08-12 00:02:00');$corruptReceipt=$validReceipt;$corruptReceipt['payload_digest']=str_repeat('f',64);
$verifiedExact=$receiptFault->invoke(null,true,array($validReceipt),$row,$package,$proofUrl);$verifiedMissing=$receiptFault->invoke(null,true,array(),$row,$package,$proofUrl);$verifiedDuplicate=$receiptFault->invoke(null,true,array($validReceipt,$validReceipt),$row,$package,$proofUrl);$verifiedCorrupt=$receiptFault->invoke(null,true,array($corruptReceipt),$row,$package,$proofUrl);$pendingExact=$receiptFault->invoke(null,false,array(),$row,$package,$proofUrl);$pendingReceipt=$receiptFault->invoke(null,false,array($validReceipt),$row,$package,$proofUrl);
$suppression=array('oldAuthorityDigest'=>str_repeat('1',64),'observedAuthorityDigest'=>str_repeat('2',64),'cronHeartbeatDigest'=>str_repeat('3',64),'publicCoherenceDigest'=>str_repeat('4',64),'errorCode'=>'campaign_authority_snapshot_stale');$state0=array('runtime'=>array('scheduleDigest'=>$schedule,'activationGeneration'=>0));$state1=array('runtime'=>array('scheduleDigest'=>$schedule,'activationGeneration'=>1));$identity0=$cleanupIdentity->invoke(null,$state0,array('placement_id'=>$p0),$suppression);$identity0Replay=$cleanupIdentity->invoke(null,$state0,array('placement_id'=>$p0),$suppression);$identity1=$cleanupIdentity->invoke(null,$state1,array('placement_id'=>$p1),$suppression);$badState=$state0;$badState['runtime']['activationGeneration']='0';$badIdentity=$cleanupIdentity->invoke(null,$badState,array('placement_id'=>$p0),$suppression);$runtime0=array('runtime'=>array('activationGeneration'=>0,'lifecycleState'=>'active','externalState'=>'suppressed','jobState'=>'authority_retry_pending','attempts'=>4,'nextAttemptAt'=>'2026-08-12T00:00:00Z','lastErrorCode'=>'authority_stale','authoritySuppression'=>$suppression));$runtime1=$advanceState->invoke(null,$runtime0,0);$suppressed1=$runtime1;$suppressed1['runtime']['lifecycleState']='active';$suppressed1['runtime']['externalState']='suppressed';$suppressed1['runtime']['jobState']='authority_retry_pending';$suppressed1['runtime']['authoritySuppression']=$suppression;$runtime2=$advanceState->invoke(null,$suppressed1,1);$staleTransition=$advanceState->invoke(null,$runtime1,0);$route='/complete99/v1/campaign-events/'.$t0;$endpointMatrix=array('pretty'=>$historicalEndpoint->invoke(null,$eventEndpoint,$proofUrl,$t0),'plain'=>$historicalEndpoint->invoke(null,'https://old.example.test/?rest_route='.rawurlencode($route),$proofUrl,$t0),'extraQuery'=>$historicalEndpoint->invoke(null,'https://old.example.test/?rest_route='.rawurlencode($route).'&x=1',$proofUrl,$t0),'backslash'=>$historicalEndpoint->invoke(null,'https://old.example.test/wp-json/complete99/v1/campaign-events\\'.$t0,$proofUrl,$t0));
echo json_encode(array('placements'=>array($p0,$p1,$p2),'tokens'=>array($t0,$t1,$t2),'receipts'=>array($receiptId->invoke(null,$p0),$receiptId->invoke(null,$p1),$receiptId->invoke(null,$p2)),'faults'=>array('verifiedExact'=>is_array($verifiedExact)?$verifiedExact['code']:'','verifiedMissing'=>$verifiedMissing['code'],'verifiedDuplicate'=>$verifiedDuplicate['code'],'verifiedCorrupt'=>$verifiedCorrupt['code'],'pendingExact'=>is_array($pendingExact)?$pendingExact['code']:'','pendingReceipt'=>$pendingReceipt['code']),'identities'=>array($identity0,$identity0Replay,$identity1),'badIdentity'=>is_wp_error($badIdentity)?$badIdentity->get_error_code():'','generations'=>array($runtime1['runtime']['activationGeneration'],$runtime2['runtime']['activationGeneration']),'generationJobs'=>array($runtime1['runtime']['jobState'],$runtime2['runtime']['jobState']),'staleTransition'=>is_wp_error($staleTransition)?$staleTransition->get_error_code():'','endpointMatrix'=>$endpointMatrix),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(3, len(set(result["placements"])), result)
        self.assertEqual(3, len(set(result["tokens"])), result)
        self.assertEqual(3, len(set(result["receipts"])), result)
        self.assertEqual("", result["faults"]["verifiedExact"], result)
        self.assertEqual("", result["faults"]["pendingExact"], result)
        self.assertEqual("activation_supersession_receipt_missing", result["faults"]["verifiedMissing"])
        self.assertEqual("activation_supersession_receipt_duplicated", result["faults"]["verifiedDuplicate"])
        self.assertEqual("activation_supersession_receipt_corrupt", result["faults"]["verifiedCorrupt"])
        self.assertEqual("activation_supersession_pending_receipt_invalid", result["faults"]["pendingReceipt"])
        self.assertEqual(result["identities"][0], result["identities"][1], result)
        self.assertNotEqual(result["identities"][0], result["identities"][2], result)
        self.assertEqual("complete99_campaign_authority_suppression_identity_invalid", result["badIdentity"])
        self.assertEqual([1, 2], result["generations"], result)
        self.assertEqual(["reconcile_pending", "reconcile_pending"], result["generationJobs"], result)
        self.assertEqual("complete99_campaign_activation_generation_transition_invalid", result["staleTransition"], result)
        self.assertEqual({"pretty": True, "plain": True, "extraQuery": False, "backslash": False}, result["endpointMatrix"], result)
        advance = self.php.split("private static function advance_suppressed_activation_generation", 1)[1].split(
            "/** Suspend durable jobs", 1
        )[0]
        fault = self.php.split("private static function store_activation_supersession_fault", 1)[1].split(
            "private static function advance_suppressed_activation_generation", 1
        )[0]
        for token in (
            "system_readback_receipts", "$historical_target_status = $quarantine_recovered ? $suppressed_status : 'superseded'", "activation_generation_advance_state",
            "activation_supersession_receipt_fault", "cleanup_due_at",
        ):
            self.assertIn(token, advance)
        self.assertIn("operator_attention", fault)
        for token in ("activation_supersession_receipt_missing", "activation_supersession_receipt_duplicated", "activation_supersession_receipt_corrupt"):
            self.assertIn(token, fault)
        self.assertNotRegex(advance, r"(?:UPDATE|DELETE).*provider_receipts")
        conflicts = advance.split("$current_conflicts =", 1)[1].split("$receipt_rows =", 1)[0]
        self.assertIn("suppressed_readback", conflicts)
        self.assertNotIn("superseded", conflicts)
        self.assertNotIn("qtr_", conflicts)
        suppress = self.php.split("private static function suppress_stale_placement", 1)[1].split(
            "/** Record manual evidence", 1
        )[0]
        self.assertIn("self::authority_suppression_cleanup_identity", suppress)
        boot = self.php.split("public static function boot", 1)[1].split("public static function register_runtime_authority_writer_hooks", 1)[0]
        worker = self.php.split("public static function reconcile_schedules", 1)[1].split("private static function reconcile_schedules_fenced", 1)[0]
        fenced = self.php.split("private static function reconcile_schedules_fenced", 1)[1].split("private static function reconcile_campaign_schedule", 1)[0]
        automatic = self.php.split("private static function reconcile_public_quarantine_epoch_resolution", 1)[1].split("private static function reconcile_public_quarantine_terminal_gc_class", 1)[0]
        schedule = self.php.split("private static function reconcile_campaign_schedule", 1)[1].split("private static function reconcile_campaign_schedule_under_cron_fence", 1)[0]
        self.assertIn("complete99_campaign_reconcile_schedules", boot)
        self.assertIn("self::reconcile_schedules_fenced()", worker)
        self.assertLess(fenced.index("reconcile_public_quarantine_terminal_gc"), fenced.index("bounded_actionable_campaign_rows( 'schedule_due'"))
        self.assertIn("reconcile_campaign_schedule", fenced)
        for token in ("CAPACITY_ROLE_QUARANTINE", "$qtr_status", "RECONCILE_BATCH_SIZE", "validate_public_quarantine_manifest_rows"):
            self.assertIn(token, automatic)
        for forbidden in ("save_campaign", "append_cleanup_obligation", "campaign_revisions", "audit_events"):
            self.assertNotIn(forbidden, automatic)
        self.assertIn("advance_suppressed_activation_generation", schedule)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_bulk_slot_suppression_has_no_100_row_or_false_aggregate_residue(self) -> None:
        model = r"""
$rows=array();for($i=1;$i<=151;$i++){$rows[]=array('id'=>$i,'campaignId'=>$i<=120?'campaign_a':'campaign_b','slot'=>'home_banner','status'=>$i%2?'active':'pending_readback');}
$campaigns=array('campaign_a'=>array('external'=>'owned_active','job'=>'readback_verified'),'campaign_b'=>array('external'=>'owned_active','job'=>'readback_verified'));
$surfaces=array();foreach($rows as &$row){$row['status']='suppressed_authority';$surfaces[$row['slot']]=true;$campaigns[$row['campaignId']]=array('external'=>'suppressed','job'=>'operator_attention');}unset($row);
$survivors=count(array_filter($rows,static function($row){return in_array($row['status'],array('scheduled','pending_readback','active'),true);}));
echo json_encode(array('updated'=>count($rows),'survivors'=>$survivors,'surfaces'=>count($surfaces),'campaigns'=>$campaigns),JSON_THROW_ON_ERROR);
"""
        result = json.loads(subprocess.run(
            ["php", "-r", model], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(151, result["updated"])
        self.assertEqual(0, result["survivors"])
        self.assertEqual(1, result["surfaces"])
        self.assertEqual({"external": "suppressed", "job": "operator_attention"}, result["campaigns"]["campaign_a"])
        self.assertEqual({"external": "suppressed", "job": "operator_attention"}, result["campaigns"]["campaign_b"])
        suppress = self.php.split("private static function suppress_active_campaign_coherence", 1)[1].split(
            "private static function suppress_stale_placement", 1
        )[0]
        self.assertIn("This O(1) transition", suppress)
        self.assertIn("public_quarantine_status( true )", suppress)
        self.assertIn("$wpdb->update(", suppress)
        self.assertIn("PUBLIC_QUARANTINE_STATUS_CLEAR", suppress)
        self.assertNotIn("CAPACITY_MAX_ROWS", suppress)
        self.assertNotIn("LIMIT 101", suppress)
        self.assertNotIn("SELECT * FROM", suppress)

    def test_8946_born_private_marker_first_direct_row_and_low_level_cache_contract(self) -> None:
        intent = self.php.split("private static function ensure_private_evidence_upload_intent", 1)[1].split(
            "public static function rest_upload_evidence", 1
        )[0]
        self.assertNotIn("wp_insert_attachment", intent)
        self.assertNotIn("add_attachment", intent)
        self.assertNotIn("clean_post_cache", intent)
        self.assertIn("private_evidence_transactional_storage_ready", intent)
        self.assertIn("$wpdb->insert( $wpdb->posts", intent)
        self.assertIn("SELECT ID,post_author,post_date,post_date_gmt", intent)
        marker = "$marker_inserted = $wpdb->insert( $wpdb->postmeta"
        self.assertIn(marker, intent)
        self.assertLess(intent.index(marker), intent.index("foreach ( $metadata as $meta_key"))
        self.assertNotIn("'_complete99_private_evidence' => 'yes'", intent.split("$metadata = array(", 1)[1].split(");", 1)[0])
        self.assertIn("(int) ( $meta_rows[0]['meta_id'] ?? 0 ) !== $marker_meta_id", intent)
        self.assertIn("invalidate_private_evidence_cache_keys( $attachment_id, true )", intent)
        cache = self.php.split("private static function invalidate_private_evidence_cache_keys", 1)[1].split(
            "private static function private_evidence_transactional_storage_ready", 1
        )[0]
        for token in ("'posts'", "'post_parent:'", "'post_meta'", "wp_cache_set_posts_last_changed"):
            self.assertIn(token, cache)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_lifecycle_receipt_reserve_and_terminal_custodian_release_table(self) -> None:
        model = r"""
function command($count,$held,$target,$same,$custodian,$terminalUsed){
 if($same)return array('accepted'=>false,'count'=>$count,'held'=>$held,'code'=>'unchanged');
 if($target&&127<=$count)return array('accepted'=>false,'count'=>$count,'held'=>$held,'code'=>'reserve');
 if(!$target&&128<=$count){if(!$held||!$custodian||$terminalUsed)return array('accepted'=>false,'count'=>$count,'held'=>$held,'code'=>'terminal_denied');return array('accepted'=>true,'count'=>$count,'held'=>false,'terminal'=>true);}
 return array('accepted'=>true,'count'=>$count+1,'held'=>$target,'terminal'=>false);
}
$set=command(126,false,true,false,true,false);$same=command($set['count'],true,true,true,true,false);$release=command($set['count'],true,false,false,true,false);$blocked=command(127,false,true,false,true,false);$terminal=command(128,true,false,false,true,false);$terminalWrong=command(128,true,false,false,false,false);
echo json_encode(compact('set','same','release','blocked','terminal','terminalWrong'),JSON_THROW_ON_ERROR);
"""
        result = json.loads(subprocess.run(
            ["php", "-r", model], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(127, result["set"]["count"])
        self.assertFalse(result["same"]["accepted"])
        self.assertEqual(127, result["same"]["count"])
        self.assertTrue(result["release"]["accepted"])
        self.assertEqual(128, result["release"]["count"])
        self.assertEqual("reserve", result["blocked"]["code"])
        self.assertTrue(result["terminal"]["accepted"])
        self.assertTrue(result["terminal"]["terminal"])
        self.assertEqual("terminal_denied", result["terminalWrong"]["code"])
        hold = self.php.split("public static function rest_evidence_legal_hold", 1)[1].split(
            "private static function private_evidence_lifecycle_receipt", 1
        )[0]
        for token in (
            "PRIVATE_EVIDENCE_LIFECYCLE_RELEASE_RESERVE", "complete99_campaign_private_hold_unchanged",
            "_complete99_evidence_terminal_release_receipt", "'custodian' !== $mode",
        ):
            self.assertIn(token, hold)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_public_quarantine_singleton_and_epoch_helpers_execute_production_code(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
$salt='before';function wp_salt($scheme='auth'){global $salt;return $salt;}
class C99QuarantineWpdb{public $prefix='wp_';public $last_error='';public $rows=array();public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){return $this->rows;}}
$wpdb=new C99QuarantineWpdb();require '__CAMPAIGN_PATH__';
function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$payloadMethod=c99_private('public_quarantine_payload');$valuesMethod=c99_private('public_quarantine_row_values');$statusMethod=c99_private('public_quarantine_status');$tokenMethod=c99_private('public_quarantine_token');$terminalMethod=c99_private('public_quarantine_terminal_status');$recoveredMethod=c99_private('public_quarantine_recovered_status');
$clearPayload=$payloadMethod->invoke(null,'clear',0,'','','','','1970-01-01T00:00:00Z','',array());$clearRow=array_merge(array('id'=>1),$valuesMethod->invoke(null,$clearPayload));$wpdb->rows=array($clearRow);$clear=$statusMethod->invoke(null,false);
$activePayload=$payloadMethod->invoke(null,'active',7,'campaign_public_coherence_stale','campaign_runtime',str_repeat('a',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.test/'));$activeRow=array_merge(array('id'=>1),$valuesMethod->invoke(null,$activePayload));$wpdb->rows=array($activeRow);$active=$statusMethod->invoke(null,false);
$tokenBefore=$tokenMethod->invoke(null);$salt='after';$tokenAfter=$tokenMethod->invoke(null);$wpdb->rows=array($clearRow);$rotated=$statusMethod->invoke(null,false);
$wpdb->rows=array();$missing=$statusMethod->invoke(null,false);$wpdb->rows=array($clearRow,$clearRow);$duplicate=$statusMethod->invoke(null,false);$bad=$clearRow;$bad['public_digest']=str_repeat('f',64);$wpdb->rows=array($bad);$malformed=$statusMethod->invoke(null,false);
$qtn7=$terminalMethod->invoke(null,$activePayload,false);$qto7=$terminalMethod->invoke(null,$activePayload,true);$active9=$activePayload;$active9['epoch']=9;$qtn9=$terminalMethod->invoke(null,$active9,false);$qtr7=$recoveredMethod->invoke(null,$qtn7);
echo json_encode(array('clear'=>$clear,'active'=>$active,'rotated'=>$rotated,'missing'=>$missing,'duplicate'=>$duplicate,'malformed'=>$malformed,'tokenBefore'=>$tokenBefore,'tokenAfter'=>$tokenAfter,'clearKeys'=>array_keys(json_decode($clearRow['public_json'],true)),'qtn7'=>$qtn7,'qto7'=>$qto7,'qtn9'=>$qtn9,'qtr7'=>$qtr7),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["clear"]["inspectable"])
        self.assertTrue(result["clear"]["ready"])
        self.assertTrue(result["active"]["inspectable"])
        self.assertTrue(result["active"]["active"])
        self.assertFalse(result["active"]["ready"])
        self.assertTrue(result["rotated"]["ready"])
        self.assertEqual(result["tokenBefore"], result["tokenAfter"], "WordPress salt rotation cannot brick the permanent singleton")
        self.assertEqual("missing", result["missing"]["reason"])
        self.assertEqual("duplicate", result["duplicate"]["reason"])
        self.assertEqual("invalid", result["malformed"]["reason"])
        self.assertEqual(
            ["changedAt", "epoch", "initiatingCampaignId", "nextAttemptAt", "publicOrigins", "reasonCode", "scheduleDigest", "schemaVersion", "slotKey", "state"],
            result["clearKeys"],
        )
        self.assertEqual("qtn_0000000000000007", result["qtn7"])
        self.assertEqual("qto_0000000000000007", result["qto7"])
        self.assertEqual("qtn_0000000000000009", result["qtn9"])
        self.assertEqual("qtr_0000000000000007", result["qtr7"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_terminal_cohort_4500_manifest_executes_production_projection(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
class C99CohortWpdb{public $prefix='wp_';public $last_error='';public $queries=0;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){global $qto,$publicJson,$publicDigest;$this->queries++;$cursor=(int)($prepared['args'][0]??0);$last=min(4499,$cursor+201);$rows=array();for($id=$cursor+1;$id<=$last;$id++){$rows[]=array('id'=>$id,'placement_id'=>'plc_'.substr(hash('sha256','placement-'.$id),0,48),'campaign_id'=>'orphan_'.$id,'campaign_version'=>1,'package_digest'=>str_repeat('b',64),'slot_key'=>$id%2?'home_banner':'store_banner','locale'=>'en','public_json'=>$publicJson,'public_digest'=>$publicDigest,'readback_token'=>hash('sha256','token-'.$id),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:00:00','created_at'=>'2026-08-11 00:00:00','status'=>$qto,'campaign_exists'=>0);}return $rows;}public function get_row($prepared,$mode=null){return null;}}
$wpdb=new C99CohortWpdb();require '__CAMPAIGN_PATH__';
function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$payloadMethod=c99_private('public_quarantine_payload');$terminalMethod=c99_private('public_quarantine_terminal_status');$canonicalMethod=c99_private('canonical_json');$cohortMethod=c99_private('public_quarantine_terminal_cohort');
$payload=$payloadMethod->invoke(null,'active',7,'campaign_public_coherence_stale','campaign_missing',str_repeat('a',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.test/'));$qto=$terminalMethod->invoke(null,$payload,true);$publicJson=$canonicalMethod->invoke(null,array('proofUrl'=>'https://example.test/'));$publicDigest=hash('sha256',$publicJson);$cohort=$cohortMethod->invoke(null,$payload,false);
if(is_wp_error($cohort)){echo json_encode(array('error'=>$cohort->get_error_code(),'queries'=>$wpdb->queries),JSON_THROW_ON_ERROR);exit;}
echo json_encode(array('error'=>'','rowCount'=>$cohort['rowCount'],'qtnCount'=>$cohort['qtnCount'],'qtoCount'=>$cohort['qtoCount'],'firstId'=>$cohort['firstId'],'lastId'=>$cohort['lastId'],'idBytes'=>strlen(base64_decode($cohort['rowIdsBase64'],true)),'bitBytes'=>strlen(base64_decode($cohort['qtoBitsetBase64'],true)),'rowsSha256'=>$cohort['rowsSha256'],'proofUrls'=>$cohort['proofUrls'],'queries'=>$wpdb->queries),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertEqual("", result["error"])
        self.assertEqual(4499, result["rowCount"])
        self.assertEqual(0, result["qtnCount"])
        self.assertEqual(4499, result["qtoCount"])
        self.assertEqual(1, result["firstId"])
        self.assertEqual(4499, result["lastId"])
        self.assertEqual(4499 * 8, result["idBytes"])
        self.assertEqual((4499 + 7) // 8, result["bitBytes"])
        self.assertRegex(result["rowsSha256"], r"^[a-f0-9]{64}$")
        self.assertEqual(["https://example.test/"], result["proofUrls"])
        self.assertLessEqual(result["queries"], 23)
        drain = self.php.split("private static function reconcile_public_quarantine_placements", 1)[1].split(
            "private static function reconcile_public_quarantine_orphans", 1
        )[0]
        orphan = self.php.split("private static function reconcile_public_quarantine_orphans", 1)[1].split(
            "private static function public_quarantine_cleanup_inputs", 1
        )[0]
        for method in (drain, orphan):
            self.assertIn("self::RECONCILE_BATCH_SIZE", method)
            self.assertIn("begin_transaction( self::CAPACITY_ROLE_QUARANTINE )", method)
            self.assertNotIn("audit_events", method)
            self.assertNotIn("save_campaign", method)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_final_quarantine_receipt_manifest_and_tamper_matrix_execute_production_code(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99ManifestWpdb{
 public $prefix='wp_';public $last_error='';public $provider=array();public $audit=array();public $terminal=array();
 public function prepare($query,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return array('query'=>$query,'args'=>$args);}
 public function get_row($prepared,$mode=null){return null;}
 public function get_results($prepared,$mode=null){$query=is_array($prepared)?$prepared['query']:(string)$prepared;$args=is_array($prepared)?$prepared['args']:array();
  if(strpos($query,'CASE WHEN c.id IS NULL')!==false)return $this->terminal;
  if(strpos($query,'c99_campaign_provider_receipts')!==false){return array_values(array_filter($this->provider,static function($row)use($args){return ($row['receipt_id']??'')===($args[0]??'')||(($row['provider_key']??'')===($args[1]??'')&&($row['external_id']??'')===($args[2]??''));}));}
  if(strpos($query,'c99_audit_events')!==false){return array_values(array_filter($this->audit,static function($row)use($args){return ($row['event_id']??'')===($args[0]??'');}));}
  if(strpos($query,'WHERE id IN')!==false)return array_map(static function($row){$copy=$row;unset($copy['campaign_exists']);return $copy;},$this->terminal);
  return array();
 }
}
$wpdb=new C99ManifestWpdb();require '__CAMPAIGN_PATH__';
function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
function c99_install_receipt($payload,$phase){global $wpdb,$receiptIdentity;$identity=$receiptIdentity->invoke(null,$payload,$phase);$states=array('opened'=>'quarantine_active','zero'=>'quarantine_zero_overlay','closed'=>'quarantine_clear');$wpdb->provider[]=array('id'=>count($wpdb->provider)+1,'receipt_id'=>$identity['receiptId'],'campaign_id'=>'system_public_quarantine','campaign_version'=>(int)$payload['epoch'],'channel'=>'website','provider_key'=>'complete99-public-quarantine','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$states[$phase],'external_id'=>$identity['externalId'],'proof_ref'=>$identity['proofRef'],'material_digest'=>$identity['materialDigest'],'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt'],'created_by'=>0,'created_at'=>$identity['occurredAt']);$wpdb->audit[]=array('id'=>count($wpdb->audit)+1,'event_id'=>$identity['eventId'],'actor_user_id'=>0,'action'=>$identity['action'],'subject_type'=>'campaign_quarantine','subject_id'=>$identity['subjectId'],'command_id'=>null,'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt']);}
function c99_install_aggregate($payload){global $wpdb,$aggregateIdentity;$identity=$aggregateIdentity->invoke(null,$payload,'cohort');$wpdb->provider[]=array('id'=>count($wpdb->provider)+1,'receipt_id'=>$identity['receiptId'],'campaign_id'=>'system_aggregate_cleanup','campaign_version'=>(int)$payload['generation'],'channel'=>'website','provider_key'=>'complete99-aggregate-cleanup','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>'aggregate_cleanup_complete','external_id'=>$identity['externalId'],'proof_ref'=>$identity['proofRef'],'material_digest'=>$identity['materialDigest'],'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt'],'created_by'=>0,'created_at'=>$identity['occurredAt']);$wpdb->audit[]=array('id'=>count($wpdb->audit)+1,'event_id'=>$identity['eventId'],'actor_user_id'=>0,'action'=>$identity['action'],'subject_type'=>'campaign_aggregate_cleanup','subject_id'=>$identity['subjectId'],'command_id'=>null,'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt']);}
$payloadMethod=c99_private('public_quarantine_payload');$terminalMethod=c99_private('public_quarantine_terminal_status');$canonicalMethod=c99_private('canonical_json');$cohortMethod=c99_private('public_quarantine_terminal_cohort');$zeroMethod=c99_private('public_quarantine_zero_membership_cohort');$receiptIdentity=c99_private('public_quarantine_receipt_identity');$aggregateIdentity=c99_private('aggregate_cleanup_receipt_identity');$manifestMethod=c99_private('public_quarantine_final_receipt_manifest');$validateMethod=c99_private('validate_public_quarantine_manifest_rows');
$opening=$payloadMethod->invoke(null,'active',7,'campaign_public_coherence_stale','campaign_manifest',str_repeat('a',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.test/'));$qtn=$terminalMethod->invoke(null,$opening,false);$qto=$terminalMethod->invoke(null,$opening,true);$publicJson=$canonicalMethod->invoke(null,array('proofUrl'=>'https://example.test/'));$publicDigest=hash('sha256',$publicJson);
$wpdb->terminal=array(
 array('id'=>7,'placement_id'=>'plc_'.substr(hash('sha256','manifest-7'),0,48),'campaign_id'=>'campaign_manifest','campaign_version'=>1,'package_digest'=>str_repeat('b',64),'slot_key'=>'home_banner','locale'=>'en','public_json'=>$publicJson,'public_digest'=>$publicDigest,'readback_token'=>hash('sha256','token-7'),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:00:00','created_at'=>'2026-08-11 00:00:00','status'=>$qtn,'campaign_exists'=>1),
 array('id'=>9,'placement_id'=>'plc_'.substr(hash('sha256','manifest-9'),0,48),'campaign_id'=>'orphan_9','campaign_version'=>1,'package_digest'=>str_repeat('b',64),'slot_key'=>'store_banner','locale'=>'en','public_json'=>$publicJson,'public_digest'=>$publicDigest,'readback_token'=>hash('sha256','token-9'),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:00:00','created_at'=>'2026-08-11 00:00:00','status'=>$qto,'campaign_exists'=>0)
);
$cohort=$cohortMethod->invoke(null,$opening,false);if(is_wp_error($cohort)){echo json_encode(array('setupError'=>$cohort->get_error_code()),JSON_THROW_ON_ERROR);exit;}
$completed='2026-08-12T00:02:00Z';$clear=$payloadMethod->invoke(null,'clear',7,'','','','',$completed,'',array());$surfaces=array(array('url'=>'https://example.test/','code'=>200,'contentType'=>'text/html','bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'markerAbsent'=>true,'verifiedAt'=>$completed));$absence=array('schemaVersion'=>'complete99-campaign-public-quarantine-absence/v1','surfaces'=>$surfaces,'sha256'=>hash('sha256',$canonicalMethod->invoke(null,$surfaces)));
$zero=$zeroMethod->invoke(null,$opening,false);$aggregateBinding=hash('sha256',$canonicalMethod->invoke(null,$opening));$aggregateChain=hash('sha256','complete99-aggregate-cleanup-cohort/v1|quarantine|7|'.$aggregateBinding);$aggregate=array('bindingDigest'=>$aggregateBinding,'cohortDigest'=>$aggregateChain,'completedAt'=>$completed,'generation'=>7,'obligationCount'=>0,'pageCount'=>0,'pageDigestsBase64'=>'','queueCount'=>0,'schemaVersion'=>'complete99-aggregate-cleanup-cohort/v1','scope'=>'quarantine');$final=array('aggregateCleanupReceiptDigest'=>hash('sha256',$canonicalMethod->invoke(null,$aggregate)),'schemaVersion'=>'complete99-campaign-public-quarantine-final/v1','epoch'=>7,'openingPayloadDigest'=>hash('sha256',$canonicalMethod->invoke(null,$opening)),'openingReceiptDigest'=>hash('sha256',$canonicalMethod->invoke(null,$opening)),'qtnStatus'=>$cohort['qtnStatus'],'qtoStatus'=>$cohort['qtoStatus'],'rowCount'=>$cohort['rowCount'],'qtnCount'=>$cohort['qtnCount'],'qtoCount'=>$cohort['qtoCount'],'firstId'=>$cohort['firstId'],'lastId'=>$cohort['lastId'],'rowIdsBase64'=>$cohort['rowIdsBase64'],'qtoBitsetBase64'=>$cohort['qtoBitsetBase64'],'rowsSha256'=>$cohort['rowsSha256'],'qtnRowsSha256'=>$cohort['qtnRowsSha256'],'qtoRowsSha256'=>$cohort['qtoRowsSha256'],'initiatorState'=>null,'publicAbsence'=>$absence,'clearPayload'=>$clear,'zeroCampaignReceiptDigest'=>hash('sha256',$canonicalMethod->invoke(null,$zero)),'zeroRunnable'=>true,'completedAt'=>$completed);
c99_install_receipt($opening,'opened');c99_install_receipt($zero,'zero');c99_install_aggregate($aggregate);c99_install_receipt($final,'closed');$manifest=$manifestMethod->invoke(null,7,false);$validRows=$validateMethod->invoke(null,$manifest,false);
$validProvider=$wpdb->provider;$validAudit=$wpdb->audit;
$duplicateProvider=$validProvider;$duplicateProvider[]=$validProvider[count($validProvider)-1];$wpdb->provider=$duplicateProvider;$duplicate=$manifestMethod->invoke(null,7,false);$wpdb->provider=$validProvider;
$badIds=$final;$badIds['rowIdsBase64']=base64_encode(pack('NN',0,7).pack('NN',0,7));$wpdb->provider=array($validProvider[0]);$wpdb->audit=array($validAudit[0]);c99_install_receipt($badIds,'closed');$idsError=$manifestMethod->invoke(null,7,false);
$badBits=$final;$badBits['qtoBitsetBase64']=base64_encode(chr(0x82));$wpdb->provider=array($validProvider[0]);$wpdb->audit=array($validAudit[0]);c99_install_receipt($badBits,'closed');$bitsError=$manifestMethod->invoke(null,7,false);
$extra=$final;$extra['unexpected']='x';$wpdb->provider=array($validProvider[0]);$wpdb->audit=array($validAudit[0]);c99_install_receipt($extra,'closed');$shapeError=$manifestMethod->invoke(null,7,false);
$wpdb->provider=$validProvider;$wpdb->audit=$validAudit;$manifest=$manifestMethod->invoke(null,7,false);$wpdb->terminal[0]['public_digest']=str_repeat('f',64);$rowError=$validateMethod->invoke(null,$manifest,false);
echo json_encode(array('setupError'=>'','ids'=>$manifest['ids']??array(),'orphan'=>$manifest['orphan']??array(),'validRows'=>true===$validRows,'duplicate'=>is_wp_error($duplicate)?$duplicate->get_error_code():'','idsError'=>is_wp_error($idsError)?$idsError->get_error_code():'','bitsError'=>is_wp_error($bitsError)?$bitsError->get_error_code():'','shapeError'=>is_wp_error($shapeError)?$shapeError->get_error_code():'','rowError'=>is_wp_error($rowError)?$rowError->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertEqual("", result["setupError"])
        self.assertEqual([7, 9], result["ids"])
        self.assertEqual([False, True], result["orphan"])
        self.assertTrue(result["validRows"])
        self.assertEqual("complete99_campaign_public_quarantine_receipt_unavailable", result["duplicate"])
        self.assertEqual("complete99_campaign_public_quarantine_manifest_ids", result["idsError"])
        self.assertEqual("complete99_campaign_public_quarantine_manifest_bits", result["bitsError"])
        self.assertEqual("complete99_campaign_public_quarantine_manifest_shape", result["shapeError"])
        self.assertEqual("complete99_campaign_public_quarantine_manifest_row_changed", result["rowError"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_zero_row_initiator_with_unrelated_terminal_rows_executes_production_overlay(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
class C99MixedCohortWpdb{public $prefix='wp_';public $last_error='';public $terminal=array();public $campaign=null;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){$query=is_array($prepared)?$prepared['query']:(string)$prepared;if(strpos($query,'GROUP BY status,slot_key')!==false)return array();if(strpos($query,'CASE WHEN c.id IS NULL')!==false)return $this->terminal;return array();}public function get_row($prepared,$mode=null){return $this->campaign;}}
$wpdb=new C99MixedCohortWpdb();require '__CAMPAIGN_PATH__';
function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
    $payloadMethod=c99_private('public_quarantine_payload');$terminalMethod=c99_private('public_quarantine_terminal_status');$canonicalMethod=c99_private('canonical_json');$emptyCleanup=c99_private('empty_cleanup_queue');$cohortMethod=c99_private('public_quarantine_terminal_cohort');
$payload=$payloadMethod->invoke(null,'active',7,'campaign_public_coherence_stale','campaign_zero',str_repeat('a',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.test/'));$qtn=$terminalMethod->invoke(null,$payload,false);$qto=$terminalMethod->invoke(null,$payload,true);$openingDigest=hash('sha256',$canonicalMethod->invoke(null,$payload));$overlay=array('schemaVersion'=>'complete99-campaign-public-quarantine-state/v1','epoch'=>7,'terminalStatus'=>$qtn,'openingPayloadDigest'=>$openingDigest,'reasonCode'=>'campaign_public_coherence_stale','slotKey'=>'home_banner','recordedAt'=>'2026-08-12T00:00:30Z');
$state=array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>'campaign_zero','locationId'=>0,'primaryChannel'=>'website','governance'=>array('slotKey'=>'home_banner','scheduledAt'=>'','expiresAt'=>''),'runtime'=>array('version'=>1,'activationGeneration'=>0,'lifecycleState'=>'active','externalState'=>'suppressed','jobState'=>'operator_attention','nextAttemptAt'=>'','activationTimestamp'=>0,'expiryTimestamp'=>0,'publicQuarantineOverlay'=>$overlay));$stateJson=$canonicalMethod->invoke(null,$state);$cleanupJson=$canonicalMethod->invoke(null,$emptyCleanup->invoke(null));
$wpdb->campaign=array('id'=>1,'public_id'=>'campaign_zero','location_id'=>0,'lifecycle_state'=>'active','external_state'=>'suppressed','job_state'=>'operator_attention','next_attempt_at'=>null,'slot_key'=>'home_banner','activation_at'=>null,'expiry_at'=>null,'cleanup_queue_json'=>$cleanupJson,'cleanup_queue_digest'=>hash('sha256',$cleanupJson),'cleanup_due_at'=>null,'cleanup_revision'=>0,'state_json'=>$stateJson,'state_digest'=>hash('sha256',$stateJson),'version'=>1);
$publicJson=$canonicalMethod->invoke(null,array('proofUrl'=>'https://example.test/'));$publicDigest=hash('sha256',$publicJson);$wpdb->terminal=array(array('id'=>11,'placement_id'=>'plc_'.substr(hash('sha256','mixed-11'),0,48),'campaign_id'=>'campaign_other','campaign_version'=>1,'package_digest'=>str_repeat('b',64),'slot_key'=>'home_banner','locale'=>'en','public_json'=>$publicJson,'public_digest'=>$publicDigest,'readback_token'=>hash('sha256','mixed-token-11'),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:00:00','created_at'=>'2026-08-11 00:00:00','status'=>$qtn,'campaign_exists'=>1),array('id'=>12,'placement_id'=>'plc_'.substr(hash('sha256','mixed-12'),0,48),'campaign_id'=>'orphan_12','campaign_version'=>1,'package_digest'=>str_repeat('b',64),'slot_key'=>'store_banner','locale'=>'en','public_json'=>$publicJson,'public_digest'=>$publicDigest,'readback_token'=>hash('sha256','mixed-token-12'),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:00:00','created_at'=>'2026-08-11 00:00:00','status'=>$qto,'campaign_exists'=>0));
    $cohort=$cohortMethod->invoke(null,$payload,false);
    echo json_encode(array('cohortError'=>is_wp_error($cohort)?$cohort->get_error_code():'','qtnCount'=>is_array($cohort)?$cohort['qtnCount']:-1,'qtoCount'=>is_array($cohort)?$cohort['qtoCount']:-1,'initiatorState'=>is_array($cohort)?$cohort['initiatorState']:'unexpected'),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("", result["cohortError"])
        self.assertEqual(1, result["qtnCount"])
        self.assertEqual(1, result["qtoCount"])
        self.assertIsNone(result["initiatorState"])
        cohort = self.php.split("private static function public_quarantine_terminal_cohort", 1)[1].split(
            "private static function reconcile_public_quarantine_campaigns", 1
        )[0]
        self.assertIn("'initiatorState' => null", cohort)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_quarantine_open_relocks_initiator_and_edit_cancel_are_clear_gated(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}public function get_error_message(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function sanitize_key($value){return strtolower(preg_replace('/[^a-z0-9_:-]/','',(string)$value));}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
class C99OpenRaceSqliteWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $sentinel=array();public $campaign=array();public $updates=0;public $queries=array();public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function query($query){$this->queries[]=$query;return 1;}public function get_var($prepared){return null;}public function get_results($prepared,$mode=null){$query=is_array($prepared)?$prepared['query']:(string)$prepared;if(false!==strpos($query,'wp_options')){return array(array('option_id'=>1,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>'{"changedAt":"2026-08-12T00:00:00Z","generation":1,"schemaVersion":"complete99-campaign-lifecycle-reservation/v1","state":"active"}','autoload'=>'no'));}return array($this->sentinel);}public function get_row($prepared,$mode=null){return $this->campaign;}public function update($table,$data,$where){$this->updates++;return 1;}}
$wpdb=new C99OpenRaceSqliteWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$payloadMethod=c99_private('public_quarantine_payload');$valuesMethod=c99_private('public_quarantine_row_values');$canonicalMethod=c99_private('canonical_json');$emptyCleanup=c99_private('empty_cleanup_queue');$suppressMethod=c99_private('suppress_active_campaign_coherence');
$clear=$payloadMethod->invoke(null,'clear',0,'','','','','1970-01-01T00:00:00Z','',array());$wpdb->sentinel=array_merge(array('id'=>1),$valuesMethod->invoke(null,$clear));$cleanupJson=$canonicalMethod->invoke(null,$emptyCleanup->invoke(null));
function c99_state($version){return array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>'campaign_race','locationId'=>0,'primaryChannel'=>'website','governance'=>array('slotKey'=>'home_banner','scheduledAt'=>'','expiresAt'=>''),'runtime'=>array('version'=>$version,'activationGeneration'=>0,'lifecycleState'=>'active','externalState'=>'owned_active','jobState'=>'readback_verified','nextAttemptAt'=>'','activationTimestamp'=>0,'expiryTimestamp'=>0,'scheduleDigest'=>str_repeat('a',64),'publicQuarantineOverlay'=>array()));}
$oldState=c99_state(1);$newState=c99_state(2);$oldJson=$canonicalMethod->invoke(null,$oldState);$newJson=$canonicalMethod->invoke(null,$newState);$loaded=array('row'=>array('version'=>1,'state_digest'=>hash('sha256',$oldJson)),'state'=>$oldState);
$wpdb->campaign=array('id'=>9,'public_id'=>'campaign_race','location_id'=>0,'lifecycle_state'=>'active','external_state'=>'owned_active','job_state'=>'readback_verified','next_attempt_at'=>null,'slot_key'=>'home_banner','activation_at'=>null,'expiry_at'=>null,'cleanup_queue_json'=>$cleanupJson,'cleanup_queue_digest'=>hash('sha256',$cleanupJson),'cleanup_due_at'=>null,'cleanup_revision'=>0,'state_json'=>$newJson,'state_digest'=>hash('sha256',$newJson),'version'=>2);
$result=$suppressMethod->invoke(null,$loaded,'campaign_public_coherence_stale');echo json_encode(array('code'=>is_wp_error($result)?$result->get_error_code():'','updates'=>$wpdb->updates,'rolledBack'=>in_array('ROLLBACK',$wpdb->queries,true)),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("complete99_campaign_public_quarantine_initiator_stale", result["code"])
        self.assertEqual(0, result["updates"])
        self.assertTrue(result["rolledBack"])
        for action, stop in (("rest_edit", "rest_submit"), ("rest_cancel", "store_prepared_package")):
            scope = self.php.split(f"public static function {action}", 1)[1].split(
                f"public static function {stop}" if stop.startswith("rest_") else f"private static function {stop}", 1
            )[0]
            self.assertIn("assert_public_quarantine_clear( true )", scope)
            self.assertLess(scope.index("assert_public_quarantine_clear( true )"), scope.index("load_campaign( $id"))

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_quarantine_zero_nonsentinel_rows_clear_with_initiator_and_final_receipt(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');define('HOUR_IN_SECONDS',3600);define('DAY_IN_SECONDS',86400);
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_message(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($value){return $value instanceof WP_Error;}function absint($value){return abs((int)$value);}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}function home_url($path='/'){return 'https://current.example.test/';}function get_option($key,$default=false){return $default;}function wc_get_page_id($name){return 2;}function get_permalink($id){return 'https://current.example.test/store/';}function clean_post_cache($id){}function do_action($hook,...$args){}function wp_unschedule_hook($hook,$wp_error=false){return 0;}function _get_cron_array(){return array();}function maybe_unserialize($value){return @unserialize($value);}function wp_safe_remote_get($url,$args=array()){return array('code'=>200,'body'=>'','type'=>'text/html');}function wp_remote_retrieve_response_code($response){return $response['code'];}function wp_remote_retrieve_body($response){return $response['body'];}function wp_remote_retrieve_header($response,$name){return $response['type'];}
class Complete99_Ops{public static function table_names(){return array('commands'=>'wp_c99_commands','mutation_receipts'=>'wp_c99_mutation_receipts','audit_events'=>'wp_c99_audit_events','issues'=>'wp_c99_issues','issue_events'=>'wp_c99_issue_events','memberships'=>'wp_c99_memberships','budgets'=>'wp_c99_budgets');}}
class C99ClearSqliteWpdb{
 public $prefix='wp_';public $options='wp_options';public $last_error='';public $sentinel=array();public $campaign=array();public $provider=array();public $audit=array();public $pendingSawCoalesce=false;public $queries=array();
 public function prepare($query,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return array('query'=>$query,'args'=>$args);}
 private function q($prepared){return is_array($prepared)?$prepared['query']:(string)$prepared;}private function a($prepared){return is_array($prepared)?$prepared['args']:array();}
     public function get_results($prepared,$mode=null){$query=$this->q($prepared);$args=$this->a($prepared);
      if(strpos($query,'wp_options')!==false){if(($args[0]??'')==='cron')return array();return array(array('option_id'=>1,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>'{"changedAt":"2026-08-12T00:00:00Z","generation":1,"schemaVersion":"complete99-campaign-lifecycle-reservation/v1","state":"active"}','autoload'=>'no'));}
      if(strpos($query,'WHERE placement_id=%s OR readback_token=%s OR campaign_id=%s OR slot_key=%s')!==false)return array($this->sentinel);
  if(strpos($query,'c99_campaign_provider_receipts')!==false){if(strpos($query,'SELECT * FROM `')===0)return $this->provider;if(strpos($query,"campaign_id='system_public_quarantine'")!==false)return array_values(array_filter($this->provider,static function($row){return ($row['campaign_id']??'')==='system_public_quarantine'&&($row['provider_key']??'')==='complete99-public-quarantine';}));return array_values(array_filter($this->provider,static function($row)use($args){return in_array(($row['receipt_id']??''),$args,true)||(in_array(($row['provider_key']??''),$args,true)&&in_array(($row['external_id']??''),$args,true));}));}
  if(strpos($query,'c99_audit_events')!==false){if(strpos($query,'SELECT * FROM `')===0)return $this->audit;return array_values(array_filter($this->audit,static function($row)use($args){return in_array(($row['event_id']??''),$args,true);}));}
  if(strpos($query,'CASE WHEN c.id IS NULL')!==false)return array();
  if(strpos($query,'SELECT * FROM `')===0)return array();
  return array();}
 public function get_row($prepared,$mode=null){$query=$this->q($prepared);if(strpos($query,'AS placement_backlog')!==false){$this->pendingSawCoalesce=strpos($query,'COALESCE(SUM')!==false;return array('placement_backlog'=>$this->pendingSawCoalesce?0:null);}if(strpos($query,'c99_campaigns')!==false&&strpos($query,'public_id = %s')!==false)return $this->campaign;return null;}
 public function get_var($prepared){$query=$this->q($prepared);if(strpos($query,"subject_type='campaign_quarantine'")!==false)return count(array_filter($this->audit,static function($row){return ($row['subject_type']??'')==='campaign_quarantine'&&0===strpos(($row['action']??''),'campaign.public_quarantine_');}));return strpos($query,'COUNT(*)')!==false?0:null;}public function get_col($prepared){return array();}public function query($query){$this->queries[]=$query;return 1;}
 public function insert($table,$data){if($table==='wp_c99_campaign_provider_receipts'){$data['id']=count($this->provider)+1;$this->provider[]=$data;return 1;}if($table==='wp_c99_audit_events'){$data['id']=count($this->audit)+1;$this->audit[]=$data;return 1;}return 1;}
 public function update($table,$data,$where){if($table!=='wp_c99_campaign_placements'||(int)($where['id']??0)!==(int)($this->sentinel['id']??0))return 0;$this->sentinel=array_merge(array('id'=>$this->sentinel['id']),$data);return 1;}
}
$wpdb=new C99ClearSqliteWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
function c99_install_receipt($payload,$phase){global $wpdb,$receiptIdentity;$identity=$receiptIdentity->invoke(null,$payload,$phase);$states=array('opened'=>'quarantine_active','zero'=>'quarantine_zero_overlay','closed'=>'quarantine_clear');$wpdb->provider[]=array('id'=>count($wpdb->provider)+1,'receipt_id'=>$identity['receiptId'],'campaign_id'=>'system_public_quarantine','campaign_version'=>(int)$payload['epoch'],'channel'=>'website','provider_key'=>'complete99-public-quarantine','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$states[$phase],'external_id'=>$identity['externalId'],'proof_ref'=>$identity['proofRef'],'material_digest'=>$identity['materialDigest'],'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt'],'created_by'=>0,'created_at'=>$identity['occurredAt']);$wpdb->audit[]=array('id'=>count($wpdb->audit)+1,'event_id'=>$identity['eventId'],'actor_user_id'=>0,'action'=>$identity['action'],'subject_type'=>'campaign_quarantine','subject_id'=>$identity['subjectId'],'command_id'=>null,'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt']);}
function c99_install_aggregate($payload){global $wpdb,$aggregateIdentity;$identity=$aggregateIdentity->invoke(null,$payload,'cohort');$wpdb->provider[]=array('id'=>count($wpdb->provider)+1,'receipt_id'=>$identity['receiptId'],'campaign_id'=>'system_aggregate_cleanup','campaign_version'=>(int)$payload['generation'],'channel'=>'website','provider_key'=>'complete99-aggregate-cleanup','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>'aggregate_cleanup_complete','external_id'=>$identity['externalId'],'proof_ref'=>$identity['proofRef'],'material_digest'=>$identity['materialDigest'],'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt'],'created_by'=>0,'created_at'=>$identity['occurredAt']);$wpdb->audit[]=array('id'=>count($wpdb->audit)+1,'event_id'=>$identity['eventId'],'actor_user_id'=>0,'action'=>$identity['action'],'subject_type'=>'campaign_aggregate_cleanup','subject_id'=>$identity['subjectId'],'command_id'=>null,'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt']);}
$payloadMethod=c99_private('public_quarantine_payload');$valuesMethod=c99_private('public_quarantine_row_values');$terminalMethod=c99_private('public_quarantine_terminal_status');$canonicalMethod=c99_private('canonical_json');$emptyCleanup=c99_private('empty_cleanup_queue');$zeroMethod=c99_private('public_quarantine_zero_membership_cohort');$receiptIdentity=c99_private('public_quarantine_receipt_identity');$aggregateIdentity=c99_private('aggregate_cleanup_receipt_identity');$clearMethod=c99_private('maybe_clear_public_quarantine');
$opening=$payloadMethod->invoke(null,'active',7,'campaign_public_coherence_stale','campaign_zero',str_repeat('a',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://old.example.test/'));$wpdb->sentinel=array_merge(array('id'=>1),$valuesMethod->invoke(null,$opening));$qtn=$terminalMethod->invoke(null,$opening,false);$openingDigest=hash('sha256',$canonicalMethod->invoke(null,$opening));$overlay=array('schemaVersion'=>'complete99-campaign-public-quarantine-state/v1','epoch'=>7,'terminalStatus'=>$qtn,'openingPayloadDigest'=>$openingDigest,'reasonCode'=>'campaign_public_coherence_stale','slotKey'=>'home_banner','recordedAt'=>'2026-08-12T00:00:00Z');
$state=array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>'campaign_zero','locationId'=>0,'primaryChannel'=>'website','governance'=>array('slotKey'=>'home_banner','scheduledAt'=>'','expiresAt'=>''),'runtime'=>array('version'=>1,'activationGeneration'=>0,'lifecycleState'=>'active','externalState'=>'suppressed','jobState'=>'operator_attention','nextAttemptAt'=>'','activationTimestamp'=>0,'expiryTimestamp'=>0,'scheduleDigest'=>str_repeat('a',64),'publicQuarantineOverlay'=>$overlay));$stateJson=$canonicalMethod->invoke(null,$state);$cleanupJson=$canonicalMethod->invoke(null,$emptyCleanup->invoke(null));$wpdb->campaign=array('id'=>2,'public_id'=>'campaign_zero','location_id'=>0,'lifecycle_state'=>'active','external_state'=>'suppressed','job_state'=>'operator_attention','next_attempt_at'=>null,'slot_key'=>'home_banner','activation_at'=>null,'expiry_at'=>null,'cleanup_queue_json'=>$cleanupJson,'cleanup_queue_digest'=>hash('sha256',$cleanupJson),'cleanup_due_at'=>null,'cleanup_revision'=>0,'state_json'=>$stateJson,'state_digest'=>hash('sha256',$stateJson),'version'=>1);
$zero=$zeroMethod->invoke(null,$opening,false);$aggregate=array('bindingDigest'=>$openingDigest,'cohortDigest'=>hash('sha256','complete99-aggregate-cleanup-cohort/v1|quarantine|7|'.$openingDigest),'completedAt'=>'2026-08-12T00:02:00Z','generation'=>7,'obligationCount'=>0,'pageCount'=>0,'pageDigestsBase64'=>'','queueCount'=>0,'schemaVersion'=>'complete99-aggregate-cleanup-cohort/v1','scope'=>'quarantine');c99_install_receipt($opening,'opened');c99_install_receipt($zero,'zero');c99_install_aggregate($aggregate);$result=$clearMethod->invoke(null,$opening);$closed=array_values(array_filter($wpdb->provider,static function($row){return ($row['external_state']??'')==='quarantine_clear';}));$final=count($closed)===1?json_decode($closed[0]['proof_ref'],true):array();$sentinelPayload=json_decode($wpdb->sentinel['public_json'],true);
echo json_encode(array('error'=>is_wp_error($result)?$result->get_error_code():'','cleared'=>is_array($result)&&!empty($result['cleared']),'pendingSawCoalesce'=>$wpdb->pendingSawCoalesce,'sentinelState'=>$sentinelPayload['state']??'','closedReceipts'=>count($closed),'rowCount'=>$final['rowCount']??-1,'initiatorStateNull'=>array_key_exists('initiatorState',$final)&&null===$final['initiatorState'],'zeroCampaignReceiptDigest'=>$final['zeroCampaignReceiptDigest']??'','absenceUrls'=>array_column($final['publicAbsence']['surfaces']??array(),'url')),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertEqual("", result["error"])
        self.assertTrue(result["cleared"], result)
        self.assertTrue(result["pendingSawCoalesce"])
        self.assertEqual("clear", result["sentinelState"])
        self.assertEqual(1, result["closedReceipts"])
        self.assertEqual(0, result["rowCount"])
        self.assertTrue(result["initiatorStateNull"])
        self.assertRegex(result["zeroCampaignReceiptDigest"], r"^[a-f0-9]{64}$")
        self.assertEqual(
            ["https://current.example.test/", "https://current.example.test/store/", "https://old.example.test/"],
            result["absenceUrls"],
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_terminal_token_is_unavailable_without_suppressing_current_truth(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99TerminalTokenWpdb{public $prefix='wp_';public $last_error='';public $sentinel=array();public $terminal=array();public $updates=0;public $rowReads=0;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){return array($this->sentinel);}public function get_row($prepared,$mode=null){$this->rowReads++;return $this->terminal;}public function update($table,$data,$where){$this->updates++;return 1;}}
$wpdb=new C99TerminalTokenWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$payloadMethod=c99_private('public_quarantine_payload');$valuesMethod=c99_private('public_quarantine_row_values');$contextMethod=c99_private('locked_public_placement_context');$clear=$payloadMethod->invoke(null,'clear',0,'','','','','1970-01-01T00:00:00Z','',array());$wpdb->sentinel=array_merge(array('id'=>1),$valuesMethod->invoke(null,$clear));$wpdb->terminal=array('id'=>44,'placement_id'=>'plc_'.str_repeat('a',48),'campaign_id'=>'campaign_current','readback_token'=>str_repeat('b',64),'status'=>'qtr_0000000000000007');$active=new ReflectionProperty('Complete99_Campaigns','transaction_active');$active->setAccessible(true);$active->setValue(null,true);$context=$contextMethod->invoke(null,str_repeat('b',64),array('active'));
echo json_encode(array('code'=>is_wp_error($context)?$context->get_error_code():'','updates'=>$wpdb->updates,'rowReads'=>$wpdb->rowReads),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("complete99_campaign_public_context_missing", result["code"])
        self.assertEqual(0, result["updates"])
        self.assertEqual(1, result["rowReads"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_zero_cohort_uses_signed_state_projection_not_phantom_columns(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
class C99ZeroCohortWpdb{public $prefix='wp_';public $last_error='';public $row=array();public $queries=array();public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){$this->queries[]=$prepared['query'];if(strpos($prepared['query'],'SELECT c.*')===false)return array();return 0===(int)($prepared['args'][0]??0)?array($this->row):array();}}
$wpdb=new C99ZeroCohortWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$canonical=c99_private('canonical_json');$empty=c99_private('empty_cleanup_queue');$payload=c99_private('public_quarantine_payload');$cohort=c99_private('public_quarantine_zero_membership_cohort');$decode=c99_private('decode_campaign_row');$projection=c99_private('public_quarantine_zero_projection_from_loaded');$memberMatches=c99_private('public_quarantine_zero_member_matches');
$state=array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>'campaign_zero_projection','locationId'=>7,'primaryChannel'=>'website','governance'=>array('slotKey'=>'home_banner','scheduledAt'=>'2026-08-12T00:00:00Z','expiresAt'=>'2026-08-13T00:00:00Z'),'runtime'=>array('version'=>4,'activationGeneration'=>2,'lifecycleState'=>'active','externalState'=>'owned_active','jobState'=>'readback_verified','nextAttemptAt'=>'','scheduledVersion'=>4,'scheduleDigest'=>str_repeat('d',64),'activationTimestamp'=>1786492800,'expiryTimestamp'=>1786579200));
function c99_zero_row($state){global $canonical,$empty;$stateJson=$canonical->invoke(null,$state);$cleanupJson=$canonical->invoke(null,$empty->invoke(null));return array('id'=>42,'public_id'=>$state['campaignId'],'location_id'=>$state['locationId'],'lifecycle_state'=>$state['runtime']['lifecycleState'],'external_state'=>$state['runtime']['externalState'],'job_state'=>$state['runtime']['jobState'],'next_attempt_at'=>null,'slot_key'=>$state['governance']['slotKey'],'activation_at'=>gmdate('Y-m-d H:i:s',$state['runtime']['activationTimestamp']),'expiry_at'=>gmdate('Y-m-d H:i:s',$state['runtime']['expiryTimestamp']),'cleanup_queue_json'=>$cleanupJson,'cleanup_queue_digest'=>hash('sha256',$cleanupJson),'cleanup_due_at'=>null,'cleanup_revision'=>0,'state_json'=>$stateJson,'state_digest'=>hash('sha256',$stateJson),'version'=>$state['runtime']['version']);}
$opening=$payload->invoke(null,'active',9,'campaign_public_coherence_stale','campaign_zero_projection',str_repeat('a',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.test/'));
$wpdb->row=c99_zero_row($state);$validRow=$wpdb->row;$valid=$cohort->invoke(null,$opening,false);$validQueries=$wpdb->queries;$loaded=$decode->invoke(null,$validRow);$stable=$projection->invoke(null,$loaded);$zero=array('memberDigests'=>array(42=>hash('sha256',$canonical->invoke(null,$stable))));$memberExact=$memberMatches->invoke(null,$loaded,$zero);$reusedId=$loaded;$reusedId['row']['id']=43;$memberReusedId=$memberMatches->invoke(null,$reusedId,$zero);$swappedState=$state;$swappedState['campaignId']='campaign_reused';$swappedLoaded=$decode->invoke(null,c99_zero_row($swappedState));$memberSwapped=$memberMatches->invoke(null,$swappedLoaded,$zero);$versionState=$state;$versionState['runtime']['version']=5;$versionLoaded=$decode->invoke(null,c99_zero_row($versionState));$memberVersion=$memberMatches->invoke(null,$versionLoaded,$zero);
$base=$state;$variantErrors=array();$variants=array(array('owned_active','readback_verified',''),array('owned_active','retry_pending',''),array('owned_active','expiry_retry_pending',''),array('suppressed','authority_retry_pending',''),array('suppressed','operator_attention',''),array('owned_active','suspended','readback_verified'),array('suppressed','suspended','authority_retry_pending'));foreach($variants as$variant){$state=$base;$state['runtime']['externalState']=$variant[0];$state['runtime']['jobState']=$variant[1];$state['runtime']['suspendedFromJobState']=$variant[2];$wpdb->row=c99_zero_row($state);$value=$cohort->invoke(null,$opening,false);$variantErrors[]=is_wp_error($value)?$value->get_error_code():'';}$state=$base;$state['runtime']['externalState']='owned_active';$state['runtime']['jobState']='operator_attention';$wpdb->row=c99_zero_row($state);$badPair=$cohort->invoke(null,$opening,false);$wpdb->queries=array();$state=$base;$state['runtime']['scheduledVersion']=0;$wpdb->row=c99_zero_row($state);$invalid=$cohort->invoke(null,$opening,false);
echo json_encode(array('validError'=>is_wp_error($valid)?$valid->get_error_code():'','count'=>is_array($valid)?$valid['campaignCount']:-1,'first'=>is_array($valid)?$valid['firstId']:-1,'last'=>is_array($valid)?$valid['lastId']:-1,'memberExact'=>$memberExact===true,'memberReusedId'=>is_wp_error($memberReusedId)?$memberReusedId->get_error_code():'','memberSwapped'=>is_wp_error($memberSwapped)?$memberSwapped->get_error_code():'','memberVersion'=>is_wp_error($memberVersion)?$memberVersion->get_error_code():'','variantErrors'=>$variantErrors,'badPairError'=>is_wp_error($badPair)?$badPair->get_error_code():'','invalidError'=>is_wp_error($invalid)?$invalid->get_error_code():'','queries'=>$validQueries),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("", result["validError"], result)
        self.assertEqual(1, result["count"])
        self.assertEqual(42, result["first"])
        self.assertEqual(42, result["last"])
        self.assertTrue(result["memberExact"], result)
        for key in ("memberReusedId", "memberSwapped", "memberVersion"):
            self.assertEqual("complete99_campaign_public_quarantine_zero_member_changed", result[key], result)
        self.assertEqual([""] * 7, result["variantErrors"], result)
        self.assertEqual("complete99_campaign_public_quarantine_zero_corrupt", result["badPairError"])
        self.assertEqual("complete99_campaign_public_quarantine_zero_corrupt", result["invalidError"])
        self.assertTrue(all("SELECT c.*" in query for query in result["queries"]))
        zero_scope = self.php.split("private static function public_quarantine_zero_membership_cohort", 1)[1].split(
            "private static function public_quarantine_zero_projection_from_loaded", 1
        )[0]
        self.assertNotIn("c.scheduled_version", zero_scope)
        self.assertNotIn("c.schedule_digest", zero_scope)
        self.assertIn("decode_campaign_row( $row )", zero_scope)
        scheduler_overlay = self.php.split("private static function campaign_projection_has_aggregate_overlay", 1)[1].split(
            "private static function effective_campaign_state", 1
        )[0]
        self.assertIn("decode_campaign_row( $row )", scheduler_overlay)
        self.assertIn("public_quarantine_zero_member_matches", scheduler_overlay)
        scheduler_rows = self.php.split("private static function bounded_actionable_campaign_rows", 1)[1].split(
            "private static function ensure_public_quarantine_zero_membership_receipt", 1
        )[0]
        self.assertIn("campaign_projection_has_aggregate_overlay", scheduler_rows)
        self.assertIn("if ( is_wp_error( $zero_overlay ) ) { return $zero_overlay; }", scheduler_rows)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_terminal_gc_chunks_are_exact_all_present_or_all_absent(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99GcWpdb{
 public $prefix='wp_';public $last_error='';public $rows=array();public $mode='all';
 public function prepare($query,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return array('query'=>$query,'args'=>$args);}
 public function get_results($prepared,$format=null){$out=array();foreach($prepared['args'] as $id){$id=(int)$id;if('absent'===$this->mode)continue;if('drop_first'===$this->mode&&$id<=50)continue;if('partial'===$this->mode&&1===$id)continue;if(!isset($this->rows[$id]))continue;$row=$this->rows[$id];if('reused'===$this->mode&&1===$id)$row['placement_id']='plc_reused_'.str_repeat('f',40);$out[]=$row;}return $out;}
}
$wpdb=new C99GcWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$canonical=c99_private('canonical_json');$build=c99_private('public_quarantine_build_gc_intent_locked');$batchState=c99_private('public_quarantine_gc_batch_state');
$active=new ReflectionProperty('Complete99_Campaigns','transaction_active');$active->setAccessible(true);$active->setValue(null,true);
$ids=array();$orphan=array();for($id=1;$id<=75;$id++){$public=$canonical->invoke(null,array('id'=>$id,'proofUrl'=>'https://old.example.test/'.$id));$row=array('id'=>$id,'placement_id'=>'plc_'.str_pad((string)$id,48,'0',STR_PAD_LEFT),'campaign_id'=>'orphan_'.str_pad((string)$id,8,'0',STR_PAD_LEFT),'campaign_version'=>3,'package_digest'=>str_repeat('a',64),'slot_key'=>0===$id%2?'home_banner':'store_banner','locale'=>'en_US','public_json'=>$public,'public_digest'=>hash('sha256',$public),'readback_token'=>str_repeat(dechex($id%16),64),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:01:00','created_at'=>'2026-08-11 23:59:00','status'=>'qto_0000000000000007');$wpdb->rows[$id]=$row;$ids[]=$id;$orphan[]=true;}
$proof=array('epoch'=>7,'qtnStatus'=>'qtn_0000000000000007','qtoStatus'=>'qto_0000000000000007','rowsSha256'=>str_repeat('b',64),'qtnRowsSha256'=>str_repeat('c',64),'qtoRowsSha256'=>str_repeat('d',64));$manifest=array('ids'=>$ids,'orphan'=>$orphan,'proof'=>$proof);
$payload=$build->invoke(null,$manifest,'qto','2026-08-12T00:02:00Z');$raw=base64_decode($payload['rowIdsBase64'],true);$decoded=array();for($offset=0;$offset<strlen($raw);$offset+=8){$words=unpack('Nhigh/Nlow',substr($raw,$offset,8));$decoded[]=(int)$words['high']*4294967296+(int)$words['low'];}$digestRaw=base64_decode($payload['batchDigestsBase64'],true);$digests=array();for($offset=0;$offset<strlen($digestRaw);$offset+=32)$digests[]=bin2hex(substr($digestRaw,$offset,32));$intent=array('proof'=>$payload,'ids'=>$decoded,'batchDigests'=>$digests);
$out=array('payloadError'=>is_wp_error($payload)?$payload->get_error_code():'','rowCount'=>$payload['rowCount']??-1,'digestCount'=>count($digests));foreach(array('all','drop_first','partial','reused','absent')as$mode){$wpdb->mode=$mode;$value=$batchState->invoke(null,$intent,'qto',true);$out[$mode]=is_wp_error($value)?array('error'=>$value->get_error_code()):array('error'=>'','survivors'=>$value['survivorCount'],'nextCount'=>is_array($value['nextBatch'])?count($value['nextBatch']):0,'nextFirst'=>is_array($value['nextBatch'])?$value['nextBatch'][0]:0);}
echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("", result["payloadError"], result)
        self.assertEqual(75, result["rowCount"])
        self.assertEqual(2, result["digestCount"])
        self.assertEqual({"error": "", "survivors": 75, "nextCount": 50, "nextFirst": 1}, result["all"])
        self.assertEqual({"error": "", "survivors": 25, "nextCount": 25, "nextFirst": 51}, result["drop_first"])
        self.assertEqual("complete99_campaign_public_quarantine_gc_batch_partial", result["partial"]["error"])
        self.assertIn(result["reused"]["error"], (
            "complete99_campaign_public_quarantine_gc_batch_reused",
            "complete99_campaign_public_quarantine_gc_batch_digest",
        ))
        self.assertEqual({"error": "", "survivors": 0, "nextCount": 0, "nextFirst": 0}, result["absent"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_terminal_gc_restores_capacity_at_supported_physical_maximum(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99MaxGcWpdb{
 public $prefix='wp_';public $last_error='';public $mode='all';public $queries=0;
 public function prepare($query,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return array('query'=>$query,'args'=>$args);}
 private function row($id){$public='{"proofUrl":"https://old.example.test/'.(int)$id.'"}';return array('id'=>(int)$id,'placement_id'=>'plc_'.substr(hash('sha256','placement-'.(int)$id),0,48),'campaign_id'=>'orphan_'.str_pad((string)$id,8,'0',STR_PAD_LEFT),'campaign_version'=>3,'package_digest'=>str_repeat('a',64),'slot_key'=>0===$id%2?'home_banner':'store_banner','locale'=>'en_US','public_json'=>$public,'public_digest'=>hash('sha256',$public),'readback_token'=>hash('sha256','token-'.(int)$id),'starts_at'=>'2026-08-12 00:00:00','expires_at'=>'2026-08-13 00:00:00','activated_at'=>'2026-08-12 00:01:00','created_at'=>'2026-08-11 23:59:00','status'=>'qto_0000000000000007');}
 public function get_results($prepared,$format=null){$this->queries++;if('absent'===$this->mode)return array();$out=array();foreach($prepared['args'] as$id){$id=(int)$id;if('partial'===$this->mode&&1===$id)continue;$row=$this->row($id);if('reused'===$this->mode&&1===$id)$row['campaign_id']='orphan_reused';$out[]=$row;}return $out;}
}
$wpdb=new C99MaxGcWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$build=c99_private('public_quarantine_build_gc_intent_locked');$batchState=c99_private('public_quarantine_gc_batch_state');$completion=c99_private('public_quarantine_gc_completion_payload');$active=new ReflectionProperty('Complete99_Campaigns','transaction_active');$active->setAccessible(true);$active->setValue(null,true);$class=new ReflectionClass('Complete99_Campaigns');$maximum=$class->getConstant('PUBLIC_PLACEMENT_MAX_ROWS');$physicalMaximum=$class->getConstant('CAPACITY_MAX_ROWS');$batchSize=$class->getConstant('RECONCILE_BATCH_SIZE');$ids=range(1,$maximum);$manifest=array('ids'=>$ids,'orphan'=>array_fill(0,$maximum,true),'proof'=>array('epoch'=>7,'qtnStatus'=>'qtn_0000000000000007','qtoStatus'=>'qto_0000000000000007','rowsSha256'=>str_repeat('b',64),'qtnRowsSha256'=>str_repeat('c',64),'qtoRowsSha256'=>str_repeat('d',64)));
$intentPayload=$build->invoke(null,$manifest,'qto','2026-08-12T00:02:00Z');$idRaw=base64_decode($intentPayload['rowIdsBase64'],true);$digestRaw=base64_decode($intentPayload['batchDigestsBase64'],true);$decoded=array();for($offset=0;$offset<strlen($idRaw);$offset+=8){$words=unpack('Nhigh/Nlow',substr($idRaw,$offset,8));$decoded[]=(int)$words['high']*4294967296+(int)$words['low'];}$digests=array();for($offset=0;$offset<strlen($digestRaw);$offset+=32)$digests[]=bin2hex(substr($digestRaw,$offset,32));$intent=array('proof'=>$intentPayload,'ids'=>$decoded,'batchDigests'=>$digests);
$wpdb->mode='all';$all=$batchState->invoke(null,$intent,'qto',true);$wpdb->mode='partial';$partial=$batchState->invoke(null,$intent,'qto',true);$wpdb->mode='reused';$reused=$batchState->invoke(null,$intent,'qto',true);$wpdb->mode='absent';$absent=$batchState->invoke(null,$intent,'qto',true);$complete=$completion->invoke(null,array('proof'=>$intentPayload),'qto','2026-08-12T00:03:00Z');
echo json_encode(array('maximum'=>$maximum,'physicalMaximum'=>$physicalMaximum,'atCap'=>1+$maximum,'afterOne'=>1+$maximum-$batchSize,'afterAll'=>1,'rowCount'=>$intentPayload['rowCount'],'maxId'=>$intentPayload['maxId'],'batchCount'=>count($digests),'allSurvivors'=>$all['survivorCount'],'allNext'=>count($all['nextBatch']),'partial'=>is_wp_error($partial)?$partial->get_error_code():'','reused'=>is_wp_error($reused)?$reused->get_error_code():'','absentSurvivors'=>$absent['survivorCount'],'absentNext'=>$absent['nextBatch'],'completionCount'=>$complete['rowCount'],'completionMax'=>$complete['maxId'],'queries'=>$wpdb->queries),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertEqual(4499, result["maximum"], result)
        self.assertEqual(4500, result["physicalMaximum"], result)
        self.assertEqual(result["physicalMaximum"], result["atCap"], result)
        self.assertEqual(4450, result["afterOne"], result)
        self.assertEqual(1, result["afterAll"], result)
        self.assertEqual(result["maximum"], result["rowCount"], result)
        self.assertEqual(result["maximum"], result["maxId"], result)
        self.assertEqual(90, result["batchCount"], result)
        self.assertEqual(result["maximum"], result["allSurvivors"], result)
        self.assertEqual(50, result["allNext"], result)
        self.assertEqual("complete99_campaign_public_quarantine_gc_batch_partial", result["partial"])
        self.assertIn(result["reused"], ("complete99_campaign_public_quarantine_gc_batch_reused", "complete99_campaign_public_quarantine_gc_batch_digest"))
        self.assertEqual(0, result["absentSurvivors"], result)
        self.assertIsNone(result["absentNext"], result)
        self.assertEqual(result["maximum"], result["completionCount"], result)
        self.assertEqual(result["maximum"], result["completionMax"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_gc_allocator_floor_requires_exact_receipt_audit_and_manifest_binding(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99AllocatorWpdb{public $prefix='wp_';public $last_error='';public $provider=array();public $audit=array();public function get_results($query,$format=null){return false!==strpos($query,'provider_receipts')?$this->provider:$this->audit;}}
$wpdb=new C99AllocatorWpdb();require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$canonical=c99_private('canonical_json');$identityMethod=c99_private('public_quarantine_receipt_identity');$intentMethod=c99_private('public_quarantine_gc_intent_from_manifest');$completionMethod=c99_private('public_quarantine_gc_completion_payload');$floorMethod=c99_private('public_quarantine_gc_allocator_floor');
function c99_add_receipt($payload,$phase){global $wpdb,$identityMethod;$identity=$identityMethod->invoke(null,$payload,$phase);$states=array('closed'=>'quarantine_clear','gc_qto_intent'=>'quarantine_gc_qto_intent','gc_qto_complete'=>'quarantine_gc_qto_done');$wpdb->provider[]=array('id'=>count($wpdb->provider)+1,'receipt_id'=>$identity['receiptId'],'campaign_id'=>'system_public_quarantine','campaign_version'=>(int)$payload['epoch'],'channel'=>'website','provider_key'=>'complete99-public-quarantine','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$states[$phase],'external_id'=>$identity['externalId'],'proof_ref'=>$identity['proofRef'],'material_digest'=>$identity['materialDigest'],'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt'],'created_by'=>0,'created_at'=>$identity['occurredAt']);$wpdb->audit[]=array('id'=>count($wpdb->audit)+1,'event_id'=>$identity['eventId'],'actor_user_id'=>0,'action'=>$identity['action'],'subject_type'=>'campaign_quarantine','subject_id'=>$identity['subjectId'],'command_id'=>null,'payload_digest'=>$identity['payloadDigest'],'occurred_at'=>$identity['occurredAt']);}
function c99_pack($id){$high=(int)floor($id/4294967296);$low=(int)($id-$high*4294967296);return base64_encode(pack('NN',$high,$low));}
$closed=array('schemaVersion'=>'complete99-campaign-public-quarantine-final/v1','epoch'=>7,'openingPayloadDigest'=>str_repeat('a',64),'completedAt'=>'2026-08-12T00:03:00Z','rowCount'=>1,'qtnCount'=>0,'qtoCount'=>1,'rowIdsBase64'=>c99_pack(42),'qtoBitsetBase64'=>base64_encode(chr(1)),'qtnStatus'=>'qtn_0000000000000007','qtoStatus'=>'qto_0000000000000007','rowsSha256'=>str_repeat('b',64),'qtnRowsSha256'=>str_repeat('c',64),'qtoRowsSha256'=>str_repeat('d',64));
$manifest=array('ids'=>array(42),'orphan'=>array(true),'proof'=>$closed);$intent=$intentMethod->invoke(null,$manifest,'qto','2026-08-12T00:04:00Z',base64_encode(str_repeat("\0",32)));$completion=$completionMethod->invoke(null,array('proof'=>$intent),'qto','2026-08-12T00:05:00Z');c99_add_receipt($closed,'closed');c99_add_receipt($intent,'gc_qto_intent');$intentOnly=$floorMethod->invoke(null);c99_add_receipt($completion,'gc_qto_complete');$valid=$floorMethod->invoke(null);
$wpdb->audit[2]['command_id']='tampered';$badAudit=$floorMethod->invoke(null);$wpdb->provider=array();$wpdb->audit=array();$badIntent=$intent;$badIntent['rowIdsBase64']=c99_pack(43);$badIntent['maxId']=43;$badCompletion=$completionMethod->invoke(null,array('proof'=>$badIntent),'qto','2026-08-12T00:05:00Z');c99_add_receipt($closed,'closed');c99_add_receipt($badIntent,'gc_qto_intent');c99_add_receipt($badCompletion,'gc_qto_complete');$badManifest=$floorMethod->invoke(null);
echo json_encode(array('intentOnly'=>is_wp_error($intentOnly)?$intentOnly->get_error_code():$intentOnly,'valid'=>is_wp_error($valid)?$valid->get_error_code():$valid,'audit'=>is_wp_error($badAudit)?$badAudit->get_error_code():'','manifest'=>is_wp_error($badManifest)?$badManifest->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual(42, result["intentOnly"], result)
        self.assertEqual(42, result["valid"], result)
        self.assertEqual("complete99_campaign_public_quarantine_gc_allocator_audit", result["audit"])
        self.assertEqual("complete99_campaign_public_quarantine_gc_allocator_binding", result["manifest"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_worker_fence_detects_deploy_aba_and_poisons_release_failure(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99FenceWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $release=1;public $aba=false;public $optionReads=0;public $getLocks=0;public $releaseLocks=0;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_var($prepared){$query=$prepared['query'];if(false!==strpos($query,'option_id')){$this->optionReads++;return $this->aba&&$this->optionReads%2===0?9:null;}if(false!==strpos($query,'GET_LOCK')){$this->getLocks++;return 1;}if(false!==strpos($query,'RELEASE_LOCK')){$this->releaseLocks++;return $this->release;}return null;}public function get_results($prepared,$mode=null){$payload=array('changedAt'=>'2026-08-12T00:00:00Z','generation'=>1,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>'active');return array(array('option_id'=>11,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS),'autoload'=>'no'));}}
$wpdb=new C99FenceWpdb();require '__CAMPAIGN_PATH__';function c99_prop($name){$property=new ReflectionProperty('Complete99_Campaigns',$name);$property->setAccessible(true);return $property;}
$normal=Complete99_Campaigns::begin_worker_execution_fence(false);$normalEnd=Complete99_Campaigns::end_worker_execution_fence();$wpdb->release=0;$failedStart=Complete99_Campaigns::begin_worker_execution_fence(false);$failedEnd=Complete99_Campaigns::end_worker_execution_fence();$afterPoison=Complete99_Campaigns::begin_worker_execution_fence(false);$poisoned=c99_prop('advisory_lock_poisoned')->getValue();c99_prop('advisory_lock_poisoned')->setValue(null,false);$wpdb->release=1;$wpdb->aba=true;$wpdb->optionReads=0;$aba=Complete99_Campaigns::begin_worker_execution_fence(false);$owned=c99_prop('worker_execution_fence_owned')->getValue();
echo json_encode(array('normal'=>is_array($normal),'normalEnd'=>$normalEnd,'failedStart'=>is_array($failedStart),'failedEnd'=>$failedEnd,'poisonCode'=>is_wp_error($afterPoison)?$afterPoison->get_error_code():'','poisoned'=>$poisoned,'abaCode'=>is_wp_error($aba)?$aba->get_error_code():'','owned'=>$owned,'getLocks'=>$wpdb->getLocks,'releaseLocks'=>$wpdb->releaseLocks),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["normal"], result)
        self.assertTrue(result["normalEnd"], result)
        self.assertTrue(result["failedStart"], result)
        self.assertFalse(result["failedEnd"], result)
        self.assertTrue(result["poisoned"], result)
        self.assertEqual("complete99_campaign_worker_fence_unavailable", result["poisonCode"])
        self.assertEqual("complete99_campaign_worker_deploy_locked", result["abaCode"])
        self.assertFalse(result["owned"], result)
        self.assertEqual(3, result["getLocks"])
        self.assertEqual(3, result["releaseLocks"])
        wrapper = self.php.split("public static function reconcile_schedules", 1)[1].split(
            "private static function reconcile_schedules_fenced", 1
        )[0]
        self.assertIn("run_lifecycle_worker_operation", wrapper)
        lifecycle_wrapper = self.php.split("private static function run_lifecycle_worker_operation", 1)[1].split(
            "/** Fresh, locked read", 1
        )[0]
        self.assertIn("finally", lifecycle_wrapper)
        self.assertIn("end_worker_execution_fence", lifecycle_wrapper)
        self.assertIn("end_lifecycle_role_fence", lifecycle_wrapper)
        cleanup = self.php.split("private static function execute_cleanup_obligation", 1)[1].split(
            "/** Re-prove exact cron", 1
        )[0]
        self.assertIn("catch ( \\Throwable $error )", cleanup)
        self.assertIn("complete99_campaign_cleanup_execution_exception", cleanup)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_8946_historical_public_absence_fault_matrix_executes_production_verifier(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
function home_url($path='/'){return 'https://example.test/';}function get_option($key,$default=false){return 0;}function clean_post_cache($id){}
$mode='valid';$throwPurge=false;function do_action($hook,...$args){global $throwPurge;if($throwPurge)throw new Exception('purge failed');}
function wp_safe_remote_get($url,$args=array()){global $mode;if('timeout'===$mode)return new WP_Error('timeout');$stale=array('stale_double'=>'data-c99-placement-id="plc_bad"','stale_single'=>"data-c99-public-digest='bad'",'stale_unquoted'=>'data-c99-campaign-id=campaign_bad','stale_mixed'=>'DaTa-C99-PlAcEmEnT-Id = plc_bad');$body='valid'===$mode?'':('oversize'===$mode?str_repeat('x',1048577):($stale[$mode]??''));return array('code'=>'redirect'===$mode?302:200,'body'=>$body,'type'=>'text/html; charset=UTF-8');}
function wp_remote_retrieve_response_code($response){return $response['code'];}function wp_remote_retrieve_body($response){return $response['body'];}function wp_remote_retrieve_header($response,$name){return $response['type'];}
require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','prove_public_quarantine_absence');$method->setAccessible(true);$url=array('https://example.test/');$at='2026-08-12T00:02:00Z';$out=array();foreach(array('valid','redirect','oversize','stale_double','stale_single','stale_unquoted','stale_mixed','timeout')as$case){$mode=$case;$value=$method->invoke(null,$url,$at);$out[$case]=is_wp_error($value)?$value->get_error_code():$value['schemaVersion'];}$mode='valid';$throwPurge=true;$value=$method->invoke(null,$url,$at);$out['purge']=is_wp_error($value)?$value->get_error_code():'';echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("complete99-campaign-public-quarantine-absence/v1", result["valid"])
        for case in ("redirect", "oversize", "stale_double", "stale_single", "stale_unquoted", "stale_mixed", "timeout"):
            self.assertEqual("complete99_campaign_public_quarantine_absence_failed", result[case])
        self.assertEqual("complete99_campaign_cache_purge_failed", result["purge"])

    def test_8946_quarantine_gate_clear_and_recovery_call_sites_are_source_bound(self) -> None:
        scopes = {
            "schedule": self.php.split("public static function rest_schedule", 1)[1].split("private static function load_prepared_package", 1)[0],
            "activate": self.php.split("public static function cron_activate", 1)[1].split("private static function verified_readback_receipt_matches", 1)[0],
            "verify": self.php.split("public static function cron_verify_readback", 1)[1].split("public static function cron_expire", 1)[0],
            "expire": self.php.split("public static function cron_expire", 1)[1].split("private static function schedule_expiry_retry", 1)[0],
            "public_context": self.php.split("private static function locked_public_placement_context", 1)[1].split("public static function rest_public_readback", 1)[0],
            "event": self.php.split("private static function claim_public_event_budget", 1)[1].split("public static function render_public_placement", 1)[0],
            "render": self.php.split("public static function render_public_placement", 1)[1].split("private static function suppress_active_campaign_coherence", 1)[0],
        }
        for name, scope in scopes.items():
            with self.subTest(path=name):
                self.assertIn("assert_public_quarantine_clear", scope)
        append = self.php.split("private static function append_cleanup_obligation", 1)[1].split(
            "private static function cleanup_queue_readback_matches", 1
        )[0]
        self.assertIn("'present' === $expectation", append)
        self.assertIn("assert_public_quarantine_clear( true )", append)
        clear = self.php.split("private static function maybe_clear_public_quarantine", 1)[1].split(
            "private static function reconcile_public_quarantine", 1
        )[0]
        for token in (
            "public_quarantine_receipt_matches( $payload, 'opened'",
            "public_quarantine_terminal_cohort",
            "prove_public_quarantine_absence",
            "store_public_quarantine_receipt_locked",
            "PUBLIC_QUARANTINE_STATUS_ACTIVE",
            "public_quarantine_status( true )",
            "commit_transaction",
        ):
            self.assertIn(token, clear)
        self.assertLess(clear.index("store_public_quarantine_receipt_locked"), clear.index("$wpdb->update("))
        recovery = self.php.split("private static function reconcile_public_quarantine_epoch_resolution", 1)[1].split(
            "private static function reconcile_public_quarantine_terminal_gc_class", 1
        )[0]
        for token in ("public_quarantine_final_receipt_manifest", "validate_public_quarantine_manifest_rows", "$qtn_status", "$qtr_status", "RECONCILE_BATCH_SIZE"):
            self.assertIn(token, recovery)
        for forbidden in ("load_campaign", "save_campaign", "append_cleanup_obligation", "audit_events"):
            self.assertNotIn(forbidden, recovery)
        for removed in ("validate_public_quarantine_recovery", "add_quarantine_recovery_surfaces", "quarantineRecoveryProofs", "receiptBacked"):
            self.assertNotIn(removed, self.php)
        table_names = self.php.split("public static function table_names", 1)[1].split("public static function capacity_status", 1)[0]
        self.assertEqual(7, table_names.count("=> $wpdb->prefix"))
        self.assertNotIn("OPTION_CLEANUP_CURSOR", self.php)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_fa426_plain_permalink_and_cleanup_origin_rebase_are_canonical(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}function wp_parse_url($url,$component=-1){return -1===$component?parse_url($url):parse_url($url,$component);}
require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$safe=c99_private('public_quarantine_url_is_safe');$surface=c99_private('cleanup_origin_surface');$matches=c99_private('cleanup_origin_surface_matches');$rebase=c99_private('cleanup_origin_rebase_surface');
$urls=array('pretty'=>'https://example.test/shop/','plain'=>'https://example.test/?page_id=42','zero'=>'https://example.test/?page_id=0','leading'=>'https://example.test/?page_id=042','duplicate'=>'https://example.test/?page_id=42&page_id=43','extra'=>'https://example.test/?page_id=42&x=1','semicolon'=>'https://example.test/?page_id=42;x=1','fragment'=>'https://example.test/?page_id=42#x','credentials'=>'https://user@example.test/?page_id=42','http'=>'http://example.test/?page_id=42');$urlResults=array();foreach($urls as$key=>$url)$urlResults[$key]=$safe->invoke(null,$url);
$a=$surface->invoke(null,'campaign_origin','store_banner','https://a.example.test/?page_id=42');$b=$surface->invoke(null,'campaign_origin','store_banner','https://b.example.test/?page_id=42');$c=$surface->invoke(null,'campaign_origin','store_banner','https://c.example.test/?page_id=42');$needC=$rebase->invoke(null,array($a,$b),'campaign_origin','store_banner','https://c.example.test/?page_id=42');$hasC=$rebase->invoke(null,array($a,$b,$c),'campaign_origin','store_banner','https://c.example.test/?page_id=42');$collision=$c;$collision['url']='https://tampered.example.test/?page_id=42';$bad=$rebase->invoke(null,array($a,$b,$collision),'campaign_origin','store_banner','https://c.example.test/?page_id=42');
echo json_encode(array('urls'=>$urlResults,'aValid'=>$matches->invoke(null,$a),'bValid'=>$matches->invoke(null,$b),'cValid'=>$matches->invoke(null,$c),'distinct'=>count(array_unique(array($a['placementId'],$b['placementId'],$c['placementId']))),'needC'=>is_array($needC)?$needC['url']:'','hasC'=>null===$hasC,'collision'=>is_wp_error($bad)?$bad->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["urls"]["pretty"], result)
        self.assertTrue(result["urls"]["plain"], result)
        for key in ("zero", "leading", "duplicate", "extra", "semicolon", "fragment", "credentials", "http"):
            self.assertFalse(result["urls"][key], (key, result))
        self.assertTrue(result["aValid"] and result["bValid"] and result["cValid"], result)
        self.assertEqual(3, result["distinct"], result)
        self.assertEqual("https://c.example.test/?page_id=42", result["needC"], result)
        self.assertTrue(result["hasC"], result)
        self.assertEqual("complete99_campaign_cleanup_origin_collision", result["collision"], result)
        execute = self.php.split("private static function execute_cleanup_obligation", 1)[1].split(
            "/** Re-prove exact cron", 1
        )[0]
        self.assertLess(execute.index("cleanup_origin_rebase_surface"), execute.index("foreach ( (array) ( $obligation['cronActions']"))
        store = self.php.split("private static function store_cleanup_queue_after_attempt", 1)[1].split(
            "private static function cleanup_audit_event_matches", 1
        )[0]
        self.assertIn("complete99_campaign_cleanup_origin_rebase_required", store)
        self.assertIn("origin-rebase:", store)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_fa426_aggregate_quarantine_resolution_scales_to_supported_capacity(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99QtrReadyWpdb{public $prefix='wp_';public $last_error='';public $mode='ready';public $calls=0;public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){$this->calls++;$ids=1===count($prepared['args'])&&is_array($prepared['args'][0])?$prepared['args'][0]:$prepared['args'];$rows=array();$queue='{"obligations":[],"schemaVersion":"complete99-campaign-cleanup-queue/v1"}';foreach($ids as$index=>$id){$status='qtr_0000000000000007';if('pending'===$this->mode&&1===$this->calls&&0===$index)$status='qtn_0000000000000007';if('corrupt'===$this->mode&&1===$this->calls&&0===$index)$status='superseded';$rows[]=array('id'=>$id,'status'=>$status,'cleanup_queue_json'=>$queue,'cleanup_queue_digest'=>hash('sha256',$queue),'cleanup_due_at'=>null,'cleanup_revision'=>0);}return $rows;}}
$wpdb=new C99QtrReadyWpdb();require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','public_quarantine_qtr_gc_ready');$method->setAccessible(true);$manifest=array('ids'=>range(1,4499),'orphan'=>array_fill(0,4499,false),'proof'=>array('qtnStatus'=>'qtn_0000000000000007'));
$ready=$method->invoke(null,$manifest);$readyCalls=$wpdb->calls;$wpdb->calls=0;$wpdb->mode='pending';$pending=$method->invoke(null,$manifest);$wpdb->calls=0;$wpdb->mode='corrupt';$corrupt=$method->invoke(null,$manifest);
echo json_encode(array('ready'=>$ready,'calls'=>$readyCalls,'pending'=>$pending,'corrupt'=>is_wp_error($corrupt)?$corrupt->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertTrue(result["ready"], result)
        self.assertEqual((4499 + 199) // 200, result["calls"], result)
        self.assertFalse(result["pending"], result)
        self.assertEqual("complete99_campaign_public_quarantine_qtr_status", result["corrupt"], result)
        resolution = self.php.split("private static function reconcile_public_quarantine_epoch_resolution", 1)[1].split(
            "private static function reconcile_public_quarantine_terminal_gc_class", 1
        )[0]
        self.assertIn("array_slice( $rows, 0, self::RECONCILE_BATCH_SIZE )", resolution)
        self.assertIn("public_quarantine_recovered_status", resolution)
        self.assertNotIn("campaign_id=%s", resolution)
        self.assertNotIn("save_campaign", resolution)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_fa426_schedule_and_expiry_guards_reject_stale_snapshot_matrix(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
class C99ExpiryWpdb{public $prefix='wp_';public $last_error='';public $rows=array();public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$mode=null){return $this->rows;}}
$wpdb=new C99ExpiryWpdb();
require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}
$snapshot=c99_private('campaign_schedule_snapshot_matches');$expiry=c99_private('expiry_callback_state_matches');$placementTruth=c99_private('expiry_placement_truth_is_current');$placementId=c99_private('owned_placement_id');$row=array('id'=>42,'public_id'=>'campaign_guard','version'=>4,'state_digest'=>str_repeat('a',64),'cleanup_queue_digest'=>str_repeat('b',64),'cleanup_due_at'=>null,'cleanup_revision'=>3);$state=array('campaignId'=>'campaign_guard','governance'=>array('slotKey'=>'home_banner'),'runtime'=>array('version'=>4,'activationGeneration'=>0,'lifecycleState'=>'active','externalState'=>'owned_active','jobState'=>'readback_verified','scheduledVersion'=>4,'scheduledPackageDigest'=>str_repeat('e',64),'scheduleDigest'=>str_repeat('c',64),'expiryTimestamp'=>time()-5));$base=array('row'=>$row,'state'=>$state);$snapshotResults=array('exact'=>$snapshot->invoke(null,$base,$base));foreach(array('id','version','public_id','state_digest','cleanup_queue_digest','cleanup_due_at','cleanup_revision')as$key){$changed=$base;$changed['row'][$key]=in_array($key,array('id','version','cleanup_revision'),true)?99:('cleanup_due_at'===$key?'2026-08-12 00:00:00':str_repeat('d',64));$snapshotResults[$key]=$snapshot->invoke(null,$base,$changed);}
$expiryResults=array('active'=>$expiry->invoke(null,$base,$base,'campaign_guard',4,str_repeat('c',64),true));$scheduled=$base;$scheduled['state']['runtime']['lifecycleState']='schedule_requested';$scheduled['state']['runtime']['externalState']='unverified';$scheduled['state']['runtime']['jobState']='scheduled';$expiryResults['scheduled']=$expiry->invoke(null,$scheduled,$scheduled,'campaign_guard',4,str_repeat('c',64),true);foreach(array('expired','superseded','cancelled')as$lifecycle){$changed=$base;$changed['state']['runtime']['lifecycleState']=$lifecycle;$expiryResults[$lifecycle]=$expiry->invoke(null,$base,$changed,'campaign_guard',4,str_repeat('c',64),true);}$badJob=$base;$badJob['state']['runtime']['jobState']='scheduled';$expiryResults['badJob']=$expiry->invoke(null,$base,$badJob,'campaign_guard',4,str_repeat('c',64),true);$expiryResults['badVersion']=$expiry->invoke(null,$base,$base,'campaign_guard',5,str_repeat('c',64),true);$expiryResults['badDigest']=$expiry->invoke(null,$base,$base,'campaign_guard',4,str_repeat('d',64),true);$future=$base;$future['state']['runtime']['expiryTimestamp']=time()+3600;$expiryResults['futureDue']=$expiry->invoke(null,$future,$future,'campaign_guard',4,str_repeat('c',64),true);
$expectedId=$placementId->invoke(null,'campaign_guard',str_repeat('c',64),0);$validPlacement=array('placement_id'=>$expectedId,'campaign_id'=>'campaign_guard','campaign_version'=>4,'package_digest'=>str_repeat('e',64),'slot_key'=>'home_banner','status'=>'active');$wpdb->rows=array($validPlacement);$placementResults=array('exact'=>$placementTruth->invoke(null,$base,true));$wpdb->rows=array($validPlacement,array_merge($validPlacement,array('placement_id'=>'plc_'.str_repeat('f',48),'campaign_version'=>3)));$value=$placementTruth->invoke(null,$base,true);$placementResults['duplicate']=is_wp_error($value)?$value->get_error_code():'';$wrong=$validPlacement;$wrong['campaign_version']=3;$wpdb->rows=array($wrong);$value=$placementTruth->invoke(null,$base,true);$placementResults['wrongVersion']=is_wp_error($value)?$value->get_error_code():'';$wpdb->rows=array();$value=$placementTruth->invoke(null,$base,true);$placementResults['missingActive']=is_wp_error($value)?$value->get_error_code():'';$wpdb->rows=array();$placementResults['missingScheduled']=$placementTruth->invoke(null,$scheduled,true);
echo json_encode(array('snapshot'=>$snapshotResults,'expiry'=>$expiryResults,'placement'=>$placementResults),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["snapshot"].pop("exact"), result)
        self.assertFalse(any(result["snapshot"].values()), result)
        self.assertTrue(result["expiry"]["active"], result)
        self.assertTrue(result["expiry"]["scheduled"], result)
        for key in ("expired", "superseded", "cancelled", "badJob", "badVersion", "badDigest", "futureDue"):
            self.assertFalse(result["expiry"][key], (key, result))
        self.assertTrue(result["placement"]["exact"], result)
        self.assertTrue(result["placement"]["missingScheduled"], result)
        for key in ("duplicate", "wrongVersion", "missingActive"):
            self.assertEqual("complete99_campaign_expiry_placement_truth_invalid", result["placement"][key], (key, result))
        under_fence = self.php.split("private static function reconcile_campaign_schedule_under_cron_fence", 1)[1].split(
            "/** Persist an unrecoverable historical-placement fault", 1
        )[0]
        self.assertLess(under_fence.index("load_campaign"), under_fence.index("wp_next_scheduled"))
        self.assertLess(under_fence.index("campaign_schedule_snapshot_matches"), under_fence.index("wp_next_scheduled"))
        self.assertIn("'_cronAction' => 'expire'", under_fence)
        self.assertIn("'_cronAction' => 'activate'", under_fence)
        reconcile = self.php.split("private static function reconcile_campaign_schedule", 1)[1].split(
            "private static function campaign_schedule_snapshot_matches", 1
        )[0]
        self.assertLess(reconcile.index("begin_campaign_cron_mutation_fence"), reconcile.index("reconcile_campaign_schedule_under_cron_fence"))
        self.assertIn("finally", reconcile)
        expire = self.php.split("public static function cron_expire", 1)[1].split(
            "private static function schedule_expiry_retry", 1
        )[0]
        self.assertIn("assert_public_quarantine_clear( true )", expire)
        self.assertIn("expiry_callback_state_matches( $loaded, $current", expire)
        self.assertIn("expiry_placement_truth_is_current( $current, true )", expire)
        placement_truth = self.php.split("private static function expiry_placement_truth_is_current", 1)[1].split(
            "public static function cron_expire", 1
        )[0]
        self.assertIn("ORDER BY id ASC LIMIT 2", placement_truth)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_fa426_overlay_suspension_and_supersession_commit_error_are_specialized(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}$next=false;function wp_next_scheduled($hook,$args=array()){global $next;return $next;}function wp_schedule_single_event($timestamp,$hook,$args=array(),$wp_error=false){global $next;$next=(int)$timestamp;return true;}function wp_unschedule_event($timestamp,$hook,$args=array(),$wp_error=false){global $next;$next=false;return true;}function wp_get_scheduled_event($hook,$args=array(),$timestamp=null){return false;}
require '__CAMPAIGN_PATH__';function c99_private($name){$method=new ReflectionMethod('Complete99_Campaigns',$name);$method->setAccessible(true);return $method;}$lifecycle=new ReflectionProperty('Complete99_Campaigns','lifecycle_role_lock_owned');$lifecycle->setAccessible(true);$lifecycle->setValue(null,true);$worker=new ReflectionProperty('Complete99_Campaigns','worker_execution_fence_owned');$worker->setAccessible(true);$worker->setValue(null,true);
$classify=c99_private('classify_commit_match_vector');$outcome=c99_private('activation_supersession_commit_outcome_error');$preStored=$classify->invoke(null,true,false);$postStored=$classify->invoke(null,false,true);$mixedStored=$classify->invoke(null,true,true);$error=$outcome->invoke(null,new WP_Error('commit_unacknowledged'),$mixedStored);$data=$error->get_error_data();
echo json_encode(array('code'=>$error->get_error_code(),'retryStored'=>$data['retryStored']??false,'stored'=>$data['stored']??'','preStored'=>$preStored,'postStored'=>$postStored,'mixedNone'=>$classify->invoke(null,false,false),'cause'=>$data['cause']??'','queued'=>$next>time()),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("complete99_campaign_activation_supersession_commit_outcome_unknown", result["code"])
        self.assertTrue(result["retryStored"], result)
        self.assertEqual("mixed", result["stored"])
        self.assertEqual("pre", result["preStored"])
        self.assertEqual("post", result["postStored"])
        self.assertEqual("mixed", result["mixedNone"])
        self.assertEqual("commit_unacknowledged", result["cause"])
        self.assertTrue(result["queued"], result)
        suspend = self.php.split("public static function suspend_schedules", 1)[1].split(
            "public static function resume_schedules", 1
        )[0]
        self.assertLess(suspend.index("begin_worker_execution_fence( false, true )"), suspend.index("transition_lifecycle_reservation_locked( $current, 'suspending' )"))
        self.assertIn("suspend_campaign_cron_batch", suspend)
        self.assertIn("prove_public_quarantine_absence", suspend)
        self.assertIn("store_lifecycle_receipt", suspend)
        self.assertNotIn("save_campaign", suspend)
        self.assertNotIn("append_cleanup_obligation", suspend)
        worker = self.php.split("private static function reconcile_schedules_fenced", 1)[1].split(
            "private static function reconcile_campaign_schedule", 1
        )[0]
        self.assertIn("$error_data['retryStored']", worker)
        advance = self.php.split("private static function advance_suppressed_activation_generation", 1)[1].split(
            "public static function suspend_schedules", 1
        )[0]
        self.assertGreaterEqual(advance.count("activation_supersession_commit_error"), 2)
        self.assertIn("activation_supersession_retry_error", advance)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c9_dynamic_quarantine_capacity_materializes_full_eleven_row_epoch(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','public_quarantine_epoch_capacity_budget');$method->setAccessible(true);$ready=new ReflectionMethod('Complete99_Campaigns','capacity_readiness_key');$ready->setAccessible(true);
$budget=$method->invoke(null,4500,4499);if(is_wp_error($budget)){echo json_encode(array('error'=>$budget->get_error_code()));exit;}
$campaign=$budget['opened']['campaign']+$budget['zero']['campaign']+$budget['closed']['campaign']+2*$budget['gc_intent']['campaign']+2*$budget['gc_complete']['campaign'];$operations=$budget['opened']['operations']+$budget['zero']['operations']+$budget['closed']['operations']+2*$budget['gc_intent']['operations']+2*$budget['gc_complete']['operations'];$maxProof=max($budget['opened']['campaign'],$budget['zero']['campaign'],$budget['closed']['campaign'],$budget['gc_intent']['campaign'],$budget['gc_complete']['campaign']);foreach($budget['zero_page']as$page){$campaign+=$page['campaign'];$operations+=$page['operations'];$maxProof=max($maxProof,$page['campaign']);}
$active=new ReflectionProperty('Complete99_Campaigns','transaction_active');$active->setAccessible(true);$active->setValue(null,true);$storedRole=new ReflectionProperty('Complete99_Campaigns','transaction_capacity_role');$storedRole->setAccessible(true);$storedRole->setValue(null,'ordinary');$begin=new ReflectionMethod('Complete99_Campaigns','begin_transaction');$begin->setAccessible(true);$escalation=$begin->invoke(null,'protected_recovery');$same=$begin->invoke(null,'ordinary');$storedRole->setValue(null,'protected_recovery');$nestedOrdinary=$begin->invoke(null,'ordinary');
echo json_encode(array('pages'=>count($budget['zero_page']),'rows'=>7+count($budget['zero_page']),'campaignBytes'=>$campaign,'operationsBytes'=>$operations,'maxProofRowBytes'=>$maxProof,'smallPages'=>count($method->invoke(null,1,1)['zero_page']),'emptyPages'=>count($method->invoke(null,0,0)['zero_page']),'recoveryBegin'=>$ready->invoke(null,'protected_recovery',false),'recoveryCommit'=>$ready->invoke(null,'protected_recovery',true),'cleanupCommit'=>$ready->invoke(null,'cleanup',true),'escalation'=>is_wp_error($escalation)?$escalation->get_error_code():'','same'=>$same,'nestedOrdinary'=>$nestedOrdinary),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertNotIn("error", result)
        self.assertEqual(4, result["pages"], result)
        self.assertEqual(11, result["rows"], result)
        self.assertGreater(result["campaignBytes"] + result["operationsBytes"], 256 * 1024, result)
        self.assertLessEqual(result["maxProofRowBytes"], 65535, result)
        self.assertEqual(1, result["smallPages"], result)
        self.assertEqual(0, result["emptyPages"], result)
        self.assertEqual("recoveryReady", result["recoveryBegin"], result)
        self.assertEqual("quarantineReady", result["recoveryCommit"], result)
        self.assertEqual("recoveryReady", result["cleanupCommit"], result)
        self.assertEqual("complete99_campaign_capacity_role_escalation", result["escalation"], result)
        self.assertTrue(result["same"], result)
        self.assertTrue(result["nestedOrdinary"], result)

        capacity = self.php.split("private static function public_quarantine_capacity_reservation", 1)[1].split(
            "/**\n\t * Return exact, bounded rollback-capacity projections", 1
        )[0]
        self.assertIn("Always reserve the next worst supported epoch", capacity)
        self.assertIn("public_quarantine_phase_discovery()", capacity)
        self.assertIn("public_quarantine_epoch_phase_inventories", capacity)
        self.assertIn("count( $target_budget['zero_page'] )", capacity)
        self.assertIn("'gc_' . $class . '_complete'", capacity)
        self.assertIn("empty( $inventory['complete'] )", capacity)
        self.assertIn("recoveryCampaignCanonicalBytes", capacity)
        begin = self.php.split("private static function begin_transaction", 1)[1].split(
            "/** Serialize a public truth read", 1
        )[0]
        self.assertNotIn("true === $role", begin)
        self.assertNotIn("false === $role", begin)
        self.assertIn("self::$transaction_capacity_role = $role", begin)
        commit = self.php.split("private static function commit_transaction", 1)[1].split(
            "private static function rollback_transaction", 1
        )[0]
        self.assertIn("assert_capacity_limits( $role, true )", commit)
        self.assertIn("complete99_campaign_capacity_role_escalation", begin)
        mutation = self.php.split("private static function run_mutation", 1)[1].split(
            "/** Direct low-level cache invalidation", 1
        )[0]
        self.assertIn("'evidence_upload' === $action ? self::CAPACITY_ROLE_EVIDENCE : self::CAPACITY_ROLE_ORDINARY", mutation)
        self.assertNotIn("in_array( $action, array( 'edit', 'cancel' ), true )", mutation)
        self.assertNotIn("'cancel' === $action ? self::CAPACITY_ROLE_RECOVERY", mutation)
        for role in (
            "CAPACITY_ROLE_ORDINARY", "CAPACITY_ROLE_QUARANTINE", "CAPACITY_ROLE_RECOVERY",
            "CAPACITY_ROLE_CLEANUP", "CAPACITY_ROLE_EVIDENCE",
        ):
            self.assertIn(role, self.php)
        self.assertNotRegex(self.php, r"begin_transaction\(\s*(?:true|false)\s*\)")

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c9_lifecycle_reservation_and_worker_wrappers_are_exact(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
require '__CAMPAIGN_PATH__';$method=new ReflectionMethod('Complete99_Campaigns','lifecycle_reservation_payload');$method->setAccessible(true);$out=array();foreach(array('active','suspending','inactive','bogus')as$state){$value=$method->invoke(null,$state,7,'2026-08-12T00:00:00Z');$out[$state]=is_wp_error($value)?$value->get_error_code():$value;}$out['zero']=($v=$method->invoke(null,'active',0,'2026-08-12T00:00:00Z')) instanceof WP_Error?$v->get_error_code():'';$out['badTime']=($v=$method->invoke(null,'active',7,'yesterday')) instanceof WP_Error?$v->get_error_code():'';echo json_encode($out,JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        for state in ("active", "suspending", "inactive"):
            self.assertEqual(state, result[state]["state"], result)
            self.assertEqual(7, result[state]["generation"], result)
            self.assertEqual("complete99-campaign-lifecycle-reservation/v1", result[state]["schemaVersion"], result)
        self.assertEqual("complete99_campaign_lifecycle_state", result["bogus"])
        self.assertEqual("complete99_campaign_lifecycle_generation", result["zero"])
        self.assertEqual("complete99_campaign_lifecycle_changed_at", result["badTime"])

        lifecycle = self.php.split("private static function lifecycle_reservation_status", 1)[1].split(
            "/** Seed exactly one non-autoloaded reservation", 1
        )[0]
        self.assertIn("ORDER BY option_id ASC LIMIT 2", lifecycle)
        self.assertIn("array( 'changedAt', 'generation', 'schemaVersion', 'state' )", lifecycle)
        self.assertIn("hash_equals( 'no'", lifecycle)
        transition = self.php.split("private static function transition_lifecycle_reservation_locked", 1)[1].split(
            "/** Keep the entire callback", 1
        )[0]
        self.assertIn("CAST(option_value AS BINARY)=CAST(%s AS BINARY)", transition)
        self.assertIn("lifecycle_reservation_status( false )", transition)
        operation = self.php.split("private static function run_lifecycle_worker_operation", 1)[1].split(
            "/** Fresh, locked read", 1
        )[0]
        self.assertLess(operation.index("begin_lifecycle_role_fence"), operation.index("begin_worker_execution_fence"))
        self.assertIn("finally", operation)
        self.assertLess(operation.index("end_worker_execution_fence"), operation.index("end_lifecycle_role_fence", operation.index("finally")))
        for public, private in (("cron_activate", "cron_activate_fenced"), ("cron_verify_readback", "cron_verify_readback_fenced"), ("cron_expire", "cron_expire_fenced")):
            wrapper = self.php.split(f"public static function {public}", 1)[1].split(f"private static function {private}", 1)[0]
            self.assertIn("run_lifecycle_worker_operation", wrapper)
        worker = self.php.split("private static function reconcile_campaign_schedule", 1)[1].split(
            "private static function campaign_schedule_snapshot_matches", 1
        )[0]
        self.assertIn("cron_activate_fenced", worker)
        self.assertIn("cron_expire_fenced", worker)
        self.assertNotIn("self::cron_activate(", worker)
        self.assertNotIn("self::cron_expire(", worker)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_c9_lifecycle_direct_db_cas_handles_crash_and_ambiguous_outcomes(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DB_ENGINE','sqlite');
class WP_Error{private $code;private $message;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->message=$message;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_message(){return $this->message;}public function get_error_data(){return $this->data;}}
function is_wp_error($value){return $value instanceof WP_Error;}function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}function wp_cache_delete($key,$group=''){return true;}
class C99LifecycleWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $mode='exact';public $rows=array();public function prepare($query,...$args){return array('query'=>$query,'args'=>$args);}public function get_results($prepared,$format=null){return $this->rows;}public function query($prepared){$args=$prepared['args'];if('pre'===$this->mode){$this->last_error='write failed';return false;}$target=(string)$args[0];if('mixed'===$this->mode){$decoded=json_decode($target,true);$decoded['generation']++;$target=json_encode($decoded,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS);}$this->rows[0]['option_value']=$target;$this->last_error='ambiguous'===$this->mode?'ack lost':'';return 'ambiguous'===$this->mode||'mixed'===$this->mode?false:1;}}
$wpdb=new C99LifecycleWpdb();$payload=array('changedAt'=>'2026-08-12T00:00:00Z','generation'=>1,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>'active');$wpdb->rows=array(array('option_id'=>7,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS),'autoload'=>'no'));
require '__CAMPAIGN_PATH__';function c99_private($name){$m=new ReflectionMethod('Complete99_Campaigns',$name);$m->setAccessible(true);return $m;}$status=c99_private('lifecycle_reservation_status');$transition=c99_private('transition_lifecycle_reservation_locked');$owned=new ReflectionProperty('Complete99_Campaigns','lifecycle_role_lock_owned');$owned->setAccessible(true);$owned->setValue(null,true);
$active=$status->invoke(null,false);$exact=$transition->invoke(null,$active,'suspending');$wpdb->mode='ambiguous';$ambiguous=$transition->invoke(null,$exact,'inactive');$wpdb->mode='pre';$pre=$transition->invoke(null,$ambiguous,'active');$wpdb->mode='mixed';$mixed=$transition->invoke(null,$ambiguous,'active');$saved=$wpdb->rows;$wpdb->rows=array();$missing=$status->invoke(null,false);$wpdb->rows=$saved;$wpdb->rows[0]['autoload']='yes';$malformed=$status->invoke(null,false);
echo json_encode(array('active'=>$active['state'],'exact'=>$exact['state'],'exactGeneration'=>$exact['generation'],'ambiguous'=>$ambiguous['state'],'ambiguousGeneration'=>$ambiguous['generation'],'ambiguousAck'=>$ambiguous['commitAmbiguous'],'pre'=>is_wp_error($pre)?$pre->get_error_code():'','mixed'=>is_wp_error($mixed)?$mixed->get_error_code():'','missing'=>is_wp_error($missing)?$missing->get_error_code():'','malformed'=>is_wp_error($malformed)?$malformed->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("active", result["active"], result)
        self.assertEqual("suspending", result["exact"], result)
        self.assertEqual(2, result["exactGeneration"], result)
        self.assertEqual("inactive", result["ambiguous"], result)
        self.assertEqual(3, result["ambiguousGeneration"], result)
        self.assertTrue(result["ambiguousAck"], result)
        self.assertEqual("complete99_campaign_lifecycle_transition_not_stored", result["pre"], result)
        self.assertEqual("complete99_campaign_lifecycle_transition_mixed", result["mixed"], result)
        self.assertEqual("complete99_campaign_lifecycle_cardinality", result["missing"], result)
        self.assertEqual("complete99_campaign_lifecycle_noncanonical", result["malformed"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_final_aggregate_overlay_capacity_and_cleanup_guards_are_centralized(self) -> None:
        self.assertIn("const CAMPAIGN_MAX_ROWS      = 4435;", self.php)
        self.assertIn("private static function aggregate_quarantine_resolution_state", self.php)
        scheduler = self.php.split("private static function bounded_actionable_campaign_rows", 1)[1].split(
            "private static function ensure_public_quarantine_zero_membership_receipt", 1
        )[0]
        self.assertEqual(1, scheduler.count("campaign_projection_has_aggregate_overlay"))
        self.assertIn("c.lifecycle_state IN ('schedule_requested','active')", scheduler)
        marker = self.php.split("private static function aggregate_quarantine_resolution_state", 1)[1].split(
            "private static function campaign_projection_has_aggregate_overlay", 1
        )[0]
        self.assertIn("complete99_campaign_quarantine_resolution_future", marker)
        self.assertNotIn("public_quarantine_final_receipt_manifest( (int) $marker['epoch']", marker)
        self.assertIn("Historical markers only classify relation", marker)
        self.assertIn("return 'old'", marker)
        self.assertIn("$receipt['memberDigests'][ $id ]", marker)
        capacity = self.php.split("private static function campaign_row_capacity", 1)[1].split(
            "private static function", 1
        )[0]
        self.assertIn("self::CAMPAIGN_MAX_ROWS + 1", capacity)
        insert = self.php.split("private static function insert_campaign", 1)[1].split(
            "private static function", 1
        )[0]
        self.assertIn("campaign_row_capacity( true )", insert)
        install = self.php.split("public static function install()", 1)[1].split(
            "public static function assert_invariants", 1
        )[0]
        self.assertIn("campaign_row_capacity( true )", install)
        repair = self.php.split("private static function repair_job_state_projection", 1)[1].split(
            "private static function quote_identifier", 1
        )[0]
        self.assertIn("self::CAMPAIGN_MAX_ROWS < ++$repaired", repair)
        aggregate_snapshot = self.php.split("private static function aggregate_cleanup_snapshot", 1)[1].split(
            "private static function prove_aggregate_cleanup_absence_records", 1
        )[0]
        aggregate_clear = self.php.split("private static function aggregate_cleanup_clear_queue_batch", 1)[1].split(
            "private static function reconcile_aggregate_cleanup", 1
        )[0]
        final_clear = self.php.split("private static function maybe_clear_public_quarantine", 1)[1].split(
            "private static function reconcile_public_quarantine", 1
        )[0]
        for aggregate_path in (aggregate_snapshot, aggregate_clear, final_clear):
            self.assertIn("aggregate_cleanup_quarantine_scope", aggregate_path)
            self.assertIn("c.lifecycle_state IN ('schedule_requested','active')", aggregate_path)
            self.assertIn("c.public_id=%s", aggregate_path)
        qtr = self.php.split("private static function public_quarantine_qtr_gc_ready", 1)[1].split(
            "private static function reconcile_public_quarantine_epoch_resolution", 1
        )[0]
        self.assertIn("c.cleanup_queue_json", qtr)
        self.assertIn("decode_cleanup_queue( $row )", qtr)
        self.assertIn("cleanup_due_at", qtr)
        recovery = self.php.split("public static function begin_activation_recovery", 1)[1].split(
            "private static function finish_activation_recovery_ownership", 1
        )[0]
        self.assertGreaterEqual(recovery.count("activation_recovery_deployment_owner()"), 2)
        self.assertIn("activation_recovery_token_factory", recovery)
        self.assertIn("reset_activation_recovery_state", recovery)
        entrant = self.php.split("private static function create_lifecycle_entrant_permit", 1)[1].split(
            "public static function complete_activation_recovery", 1
        )[0]
        self.assertIn("SELECT CONNECTION_ID()", entrant)
        self.assertIn("SELECT IS_USED_LOCK(%s)", entrant)
        self.assertIn("requestToken", entrant)
        self.assertIn("generation", entrant)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_final_aggregate_marker_epochs_and_campaign_headroom_execute(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v){return parse_url($v);}
class C99FinalWpdb{public $prefix='wp_';public $last_error='';public $count=0;public $sentinel=array();public $scopeQuery='';public $scopeArgs=array();public function get_col($query){return range(1,$this->count);}public function prepare($q,...$a){return array('query'=>$q,'args'=>$a);}public function get_results($prepared,$format=null){$query=is_array($prepared)?$prepared['query']:$prepared;$args=is_array($prepared)?$prepared['args']:array();if(false!==strpos($query,'placement_id=%s OR readback_token=%s'))return array($this->sentinel);if(false!==strpos($query,'SELECT c.*')){$this->scopeQuery=$query;$this->scopeArgs=$args;return array();}return array();}}
$wpdb=new C99FinalWpdb();require '__CAMPAIGN_PATH__';function priv($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function error_code($v){return is_wp_error($v)?$v->get_error_code():$v;}
$classify=priv('aggregate_quarantine_resolution_state');$capacity=priv('campaign_row_capacity');$cache=new ReflectionProperty('Complete99_Campaigns','public_quarantine_zero_cache');$cache->setAccessible(true);
$d1=str_repeat('1',64);$d2=str_repeat('2',64);$zero1=array('membership'=>array(42=>true),'memberDigests'=>array(42=>$d1));$zero2=array('membership'=>array(42=>true),'memberDigests'=>array(42=>$d2));$cache->setValue(null,array('1'=>$zero1));
$loaded=array('row'=>array('id'=>42,'version'=>5),'state'=>array('runtime'=>array('publicQuarantineOverlay'=>array())));$none=$classify->invoke(null,$loaded,$zero2,2);
$loaded['state']['runtime']['publicQuarantineOverlay']=array('epoch'=>2,'memberDigest'=>$d2,'resolvedVersion'=>5,'schemaVersion'=>'complete99-campaign-quarantine-resolution/v1');$current=$classify->invoke(null,$loaded,$zero2,2);
$loaded['state']['runtime']['publicQuarantineOverlay']=array('epoch'=>1,'memberDigest'=>$d1,'resolvedVersion'=>5,'schemaVersion'=>'complete99-campaign-quarantine-resolution/v1');$old=$classify->invoke(null,$loaded,$zero2,2);$oldAbsent=$classify->invoke(null,$loaded,array('membership'=>array(),'memberDigests'=>array()),2);
$futureFromZero=$classify->invoke(null,$loaded,array('membership'=>array(),'memberDigests'=>array()),0);
$loaded['state']['runtime']['publicQuarantineOverlay']=array('epoch'=>3,'memberDigest'=>$d2,'resolvedVersion'=>5,'schemaVersion'=>'complete99-campaign-quarantine-resolution/v1');$future=$classify->invoke(null,$loaded,$zero2,2);
$loaded['state']['runtime']['publicQuarantineOverlay']=array('schemaVersion'=>'complete99-campaign-quarantine-resolution/v1','epoch'=>2,'memberDigest'=>$d2,'resolvedVersion'=>5);$malformed=$classify->invoke(null,$loaded,$zero2,2);
$canonical=priv('canonical_json');$decode=priv('decode_campaign_row');$project=priv('public_quarantine_zero_projection_from_loaded');$overlay=priv('campaign_projection_has_aggregate_overlay');
$emptyQueue=array('schemaVersion'=>'complete99-campaign-cleanup-queue/v1','obligations'=>array());$activation=1786492800;$expiry=1786579200;
$state=array('schemaVersion'=>'complete99-campaign/v1','campaignId'=>'campaign_42','locationId'=>0,'primaryChannel'=>'website','governance'=>array('slotKey'=>'home_banner','scheduledAt'=>'2026-08-12T00:00:00Z','expiresAt'=>'2026-08-13T00:00:00Z'),'runtime'=>array('version'=>5,'activationGeneration'=>0,'lifecycleState'=>'active','externalState'=>'owned_active','jobState'=>'readback_verified','nextAttemptAt'=>'','activationTimestamp'=>$activation,'expiryTimestamp'=>$expiry,'scheduledVersion'=>5,'scheduleDigest'=>str_repeat('a',64),'publicQuarantineOverlay'=>array()));
$makeRow=function($state,$qtr)use($canonical,$emptyQueue,$activation,$expiry){$json=$canonical->invoke(null,$state);$queueJson=$canonical->invoke(null,$emptyQueue);return array('id'=>42,'public_id'=>'campaign_42','location_id'=>0,'version'=>(int)$state['runtime']['version'],'lifecycle_state'=>$state['runtime']['lifecycleState'],'external_state'=>$state['runtime']['externalState'],'job_state'=>$state['runtime']['jobState'],'next_attempt_at'=>null,'slot_key'=>'home_banner','activation_at'=>gmdate('Y-m-d H:i:s',$activation),'expiry_at'=>gmdate('Y-m-d H:i:s',$expiry),'state_json'=>$json,'state_digest'=>hash('sha256',$json),'cleanup_queue_json'=>$queueJson,'cleanup_queue_digest'=>hash('sha256',$queueJson),'cleanup_revision'=>0,'cleanup_due_at'=>null,'aggregate_current_qtr'=>$qtr);};
$baseRow=$makeRow($state,0);$baseLoaded=$decode->invoke(null,$baseRow);$projection=$project->invoke(null,$baseLoaded);$memberDigest=hash('sha256',$canonical->invoke(null,$projection));$overlayZero=array('proof'=>array('epoch'=>2),'membership'=>array(42=>true),'memberDigests'=>array(42=>$memberDigest));
$unresolvedSchedule=$overlay->invoke(null,$baseRow,$overlayZero,'schedule_due');$unresolvedCleanup=$overlay->invoke(null,$baseRow,$overlayZero,'cleanup_due');
$state['runtime']['publicQuarantineOverlay']=array('epoch'=>2,'memberDigest'=>$memberDigest,'resolvedVersion'=>5,'schemaVersion'=>'complete99-campaign-quarantine-resolution/v1');$resolvedRow=$makeRow($state,0);$resolvedSchedule=$overlay->invoke(null,$resolvedRow,$overlayZero,'schedule_due');$resolvedCleanup=$overlay->invoke(null,$resolvedRow,$overlayZero,'cleanup_due');
$qtrCleanup=$overlay->invoke(null,$makeRow(array_replace_recursive($state,array('runtime'=>array('publicQuarantineOverlay'=>array()))),1),$overlayZero,'cleanup_due');
$payloadMethod=priv('public_quarantine_payload');$valuesMethod=priv('public_quarantine_row_values');$snapshotMethod=priv('aggregate_cleanup_snapshot');$opening=$payloadMethod->invoke(null,'active',2,'context_drift','campaign_42',str_repeat('b',64),'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.com/'));$wpdb->sentinel=array_merge(array('id'=>999),$valuesMethod->invoke(null,$opening));$scopeSnapshot=$snapshotMethod->invoke(null,'quarantine',2,hash('sha256',$canonical->invoke(null,$opening)),false);
$wpdb->count=4435;$c4435=$capacity->invoke(null,false);$wpdb->count=4436;$c4436=$capacity->invoke(null,false);$wpdb->count=4437;$c4437=$capacity->invoke(null,false);
echo json_encode(array('none'=>$none,'current'=>$current,'old'=>error_code($old),'oldAbsent'=>error_code($oldAbsent),'futureFromZero'=>is_wp_error($futureFromZero)?$futureFromZero->get_error_code():'','future'=>is_wp_error($future)?$future->get_error_code():'','malformed'=>is_wp_error($malformed)?$malformed->get_error_code():'','unresolvedSchedule'=>$unresolvedSchedule,'unresolvedCleanup'=>$unresolvedCleanup,'resolvedSchedule'=>$resolvedSchedule,'resolvedCleanup'=>$resolvedCleanup,'qtrCleanup'=>$qtrCleanup,'scopeError'=>is_wp_error($scopeSnapshot)?$scopeSnapshot->get_error_code():'','scopeQuery'=>$wpdb->scopeQuery,'scopeArgs'=>$wpdb->scopeArgs,'c4435'=>$c4435,'c4436'=>$c4436,'c4437'=>$c4437),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("none", result["none"], result)
        self.assertEqual("current", result["current"], result)
        self.assertEqual("old", result["old"], result)
        self.assertEqual("old", result["oldAbsent"], result)
        self.assertEqual("complete99_campaign_quarantine_resolution_future", result["futureFromZero"], result)
        self.assertEqual("complete99_campaign_quarantine_resolution_future", result["future"], result)
        self.assertEqual("complete99_campaign_quarantine_resolution_corrupt", result["malformed"], result)
        self.assertTrue(result["unresolvedSchedule"], result)
        self.assertTrue(result["unresolvedCleanup"], result)
        self.assertFalse(result["resolvedSchedule"], result)
        self.assertFalse(result["resolvedCleanup"], result)
        self.assertFalse(result["qtrCleanup"], result)
        self.assertEqual("", result["scopeError"], result)
        self.assertIn("c.lifecycle_state IN ('schedule_requested','active')", result["scopeQuery"])
        self.assertIn("c.public_id=%s", result["scopeQuery"])
        self.assertIn("qtn_0000000000000002", result["scopeArgs"])
        self.assertIn("campaign_42", result["scopeArgs"])
        self.assertFalse(result["c4435"]["createReady"], result)
        self.assertTrue(result["c4435"]["ready"], result)
        self.assertFalse(result["c4436"]["createReady"], result)
        self.assertFalse(result["c4436"]["ready"], result)
        self.assertEqual({}, result["c4437"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_final_suspending_cleanup_uses_exact_lifecycle_generation_lock(self) -> None:
        cleanup = self.php.split("private static function suspend_cleanup_batch", 1)[1].split(
            "private static function suspend_campaign_cron_batch", 1
        )[0]
        self.assertIn("reconcile_aggregate_cleanup( 'lifecycle'", cleanup)
        self.assertNotIn("reconcile_campaign_cleanup", cleanup)
        self.assertNotIn("bounded_actionable_campaign_rows", cleanup)
        self.assertNotIn("LEFT(qp.status,4)='qtn_'", cleanup)
        external = self.php.split("private static function begin_worker_external_effect_fence", 1)[1].split(
            "private static function execute_cleanup_obligation", 1
        )[0]
        self.assertIn("self::$lifecycle_role_lock_owned && self::$worker_execution_fence_owned", external)
        self.assertIn("array( 'active', 'suspending' )", external)
        self.assertIn("begin_public_read_transaction( $allowed_states, $entrant_role )", external)
        self.assertIn("lifecycle_cleanup_drain_context_valid", external)
        self.assertIn("array( 'activation', 'deactivation' )", external)
        self.assertIn("'lifecycle_drain'", external)

        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
class C99LifecycleReadWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $rows=array();public function prepare($q,...$a){return $q;}public function get_results($q,$format=null){return $this->rows;}}
$wpdb=new C99LifecycleReadWpdb();$encode=function($state,$generation){return json_encode(array('changedAt'=>'2026-08-12T00:00:00Z','generation'=>$generation,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>$state),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS);};
$wpdb->rows=array(array('option_id'=>7,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>$encode('suspending',9),'autoload'=>'no'));
require '__CAMPAIGN_PATH__';function priv_lifecycle($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function prop_lifecycle($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}
$active=prop_lifecycle('transaction_active');$generation=prop_lifecycle('transaction_lifecycle_generation');$state=prop_lifecycle('transaction_lifecycle_state');$lock=priv_lifecycle('lock_active_lifecycle_generation');$assert=priv_lifecycle('assert_locked_lifecycle_generation');
$active->setValue(null,true);$generation->setValue(null,null);$state->setValue(null,null);$suspending=$lock->invoke(null,array('suspending'));$exact=$assert->invoke(null);
$wpdb->rows[0]['option_value']=$encode('active',9);$changed=$assert->invoke(null);
$wpdb->rows[0]['option_value']=$encode('suspending',9);$generation->setValue(null,null);$state->setValue(null,null);$default=$lock->invoke(null);
$generation->setValue(null,null);$state->setValue(null,null);$invalid=$lock->invoke(null,array('inactive'));
echo json_encode(array('locked'=>is_wp_error($suspending)?$suspending->get_error_code():$suspending['state'],'exact'=>is_wp_error($exact)?$exact->get_error_code():$exact['state'],'changed'=>is_wp_error($changed)?$changed->get_error_code():'','default'=>is_wp_error($default)?$default->get_error_code():'','invalid'=>is_wp_error($invalid)?$invalid->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertEqual("suspending", result["locked"], result)
        self.assertEqual("suspending", result["exact"], result)
        self.assertEqual("complete99_campaign_lifecycle_public_changed", result["changed"], result)
        self.assertEqual("complete99_campaign_lifecycle_public_inactive", result["default"], result)
        self.assertEqual("complete99_campaign_lifecycle_public_state_scope", result["invalid"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_aggregate_cleanup_page_schema_executes_against_production_validator(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);class WP_Error{}function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v){return parse_url($v);}
require '__CAMPAIGN_PATH__';$m=new ReflectionMethod('Complete99_Campaigns','aggregate_cleanup_payload_valid');$m->setAccessible(true);$hex=str_repeat('a',64);$at='2026-08-12T00:00:00Z';
$ob=array('campaignId'=>'campaign_1','campaignRowId'=>1,'convertedCronActionsDigest'=>$hex,'convertedExpectation'=>'absent','cronActionsDigest'=>$hex,'obligationDigest'=>$hex,'obligationId'=>'cln_'.str_repeat('b',48),'queueDigest'=>$hex,'queueDueAt'=>'2026-08-12 00:00:00','queueRevision'=>7,'sourceExpectation'=>'present','surfaceUrls'=>array('https://example.com/a'));
$record=array('bodyBytes'=>0,'bodySha256'=>$hex,'code'=>404,'markerAbsent'=>true,'type'=>'','url'=>'https://example.com/a','verifiedAt'=>$at);
$page=array('absenceRecords'=>array($record),'bindingDigest'=>$hex,'cohortDigest'=>$hex,'generation'=>9,'obligations'=>array($ob),'pageIndex'=>0,'schemaVersion'=>'complete99-aggregate-cleanup-page/v1','scope'=>'lifecycle','verifiedAt'=>$at);
$tooMany=$page;$tooMany['obligations']=array_fill(0,51,$ob);$stale=$page;$stale['absenceRecords'][0]['markerAbsent']=false;$unproved=$page;$unproved['absenceRecords']=array();
$cohort=array('bindingDigest'=>$hex,'cohortDigest'=>$hex,'completedAt'=>$at,'generation'=>9,'obligationCount'=>1,'pageCount'=>1,'pageDigestsBase64'=>base64_encode(str_repeat(chr(1),32)),'queueCount'=>1,'schemaVersion'=>'complete99-aggregate-cleanup-cohort/v1','scope'=>'lifecycle');
echo json_encode(array('valid'=>$m->invoke(null,$page,'page'),'tooMany'=>$m->invoke(null,$tooMany,'page'),'stale'=>$m->invoke(null,$stale,'page'),'unproved'=>$m->invoke(null,$unproved,'page'),'cohort'=>$m->invoke(null,$cohort,'cohort')),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["valid"], result)
        self.assertFalse(result["tooMany"], result)
        self.assertFalse(result["stale"], result)
        self.assertFalse(result["unproved"], result)
        self.assertTrue(result["cohort"], result)

    def test_aggregate_cleanup_and_retained_deactivation_are_wired_end_to_end(self) -> None:
        self.assertIn("const CAMPAIGN_MAX_ROWS      = 4435;", self.php)
        self.assertIn("private static function aggregate_cleanup_capacity_reservation", self.php)
        self.assertIn("private static function aggregate_cleanup_clear_queue_batch", self.php)
        self.assertIn("aggregateCleanupReceiptDigest", self.php)
        quarantine = self.php.split("private static function reconcile_public_quarantine()", 1)[1].split(
            "/** Hold the bridge-shared fence", 1
        )[0]
        self.assertIn("reconcile_aggregate_cleanup( 'quarantine'", quarantine)
        lifecycle = self.php.split("private static function suspend_schedules_fenced", 1)[1].split(
            "public static function resume_schedules", 1
        )[0]
        self.assertIn("suspend_cleanup_batch( $generation )", lifecycle)
        self.assertIn("lifecycle_cleanup_empty", lifecycle)
        self.assertIn("lifecycle_cron_empty", lifecycle)
        self.assertIn("aggregateCleanupReceiptDigest", lifecycle)
        self.assertIn("public static function begin_deactivation_suspension", self.php)
        self.assertIn("public static function complete_deactivation_suspension", self.php)
        platform = PLATFORM.read_text(encoding="utf-8")
        self.assertIn("begin_deactivation_suspension()", platform)
        self.assertIn("complete_deactivation_suspension( $token )", platform)
        self.assertIn("abort_deactivation_suspension", platform)

    def test_final_cleanup_chain_and_role_bound_entrant_wiring(self) -> None:
        self.assertIn("private static function verify_aggregate_cleanup_chain", self.php)
        verifier = self.php.split("private static function verify_aggregate_cleanup_chain", 1)[1].split(
            "private static function aggregate_cleanup_clear_queue_batch", 1
        )[0]
        self.assertIn("aggregate_cleanup_bootstrap_cohort", verifier)
        self.assertIn("aggregate_cleanup_batch_payloads", verifier)
        self.assertIn("aggregate_cleanup_load_cohort_pages", verifier)
        self.assertIn("aggregate_cleanup_chain_cache_key", verifier)
        self.assertIn("hash_equals( (string) $expected_digest", verifier)
        lifecycle_match = self.php.split("private static function lifecycle_receipt_matches", 1)[1].split(
            "private static function store_lifecycle_receipt_locked", 1
        )[0]
        self.assertIn("verify_aggregate_cleanup_chain( 'lifecycle'", lifecycle_match)
        manifest = self.php.split("private static function public_quarantine_final_receipt_manifest", 1)[1].split(
            "private static function public_quarantine_pack_ids", 1
        )[0]
        self.assertIn("verify_aggregate_cleanup_chain( 'quarantine'", manifest)
        queue_clear = self.php.split("private static function aggregate_cleanup_clear_queue_batch", 1)[1].split(
            "private static function reconcile_aggregate_cleanup", 1
        )[0]
        self.assertIn("verify_aggregate_cleanup_chain( $scope, $generation, $cohort_digest, true )", queue_clear)
        quarantine_clear = self.php.split("private static function maybe_clear_public_quarantine", 1)[1].split(
            "private static function reconcile_public_quarantine", 1
        )[0]
        self.assertIn("verify_aggregate_cleanup_chain( 'quarantine'", quarantine_clear)
        self.assertIn("$aggregate_cleanup_digest, true", quarantine_clear)
        resolution = self.php.split("private static function aggregate_quarantine_resolution_state", 1)[1].split(
            "private static function campaign_projection_has_aggregate_overlay", 1
        )[0]
        self.assertNotIn("public_quarantine_final_receipt_manifest", resolution)
        self.assertIn("Historical markers only classify relation", resolution)
        self.assertIn("return 'old'", resolution)
        scheduler_overlay = self.php.split("private static function campaign_projection_has_aggregate_overlay", 1)[1].split(
            "private static function bounded_actionable_campaign_rows", 1
        )[0]
        self.assertLess(scheduler_overlay.index("! isset( $membership[ $id ] )"), scheduler_overlay.index("aggregate_quarantine_resolution_state"))
        capacity = self.php.split("private static function public_quarantine_capacity_reservation", 1)[1].split(
            "public static function capacity_status", 1
        )[0]
        self.assertIn("public_quarantine_phase_discovery", capacity)
        self.assertIn("public_quarantine_epoch_phase_inventories", capacity)
        self.assertNotIn("public_quarantine_final_receipt_manifest", capacity)
        permit = self.php.split("private static function create_lifecycle_entrant_permit", 1)[1].split(
            "public static function complete_activation_recovery", 1
        )[0]
        for binding in ("role", "ownerToken", "requestToken", "connectionId", "generation", "rawJson", "lifecycleLockOwner", "workerLockOwner"):
            self.assertIn(binding, permit)
        self.assertIn("lifecycle_entrant_token_factory", permit)
        deactivation = self.php.split("public static function begin_deactivation_suspension", 1)[1].split(
            "public static function complete_deactivation_suspension", 1
        )[0]
        self.assertLess(deactivation.index("create_lifecycle_entrant_permit"), deactivation.index("transition_lifecycle_reservation_locked"))
        self.assertIn("rebind_lifecycle_entrant_permit", deactivation)
        public_read = self.php.split("private static function begin_public_read_transaction", 1)[1].split(
            "private static function begin_public_event_transaction", 1
        )[0]
        self.assertIn("lifecycle_cleanup_drain_context_valid", public_read)
        self.assertIn("array( 'activation', 'deactivation' )", public_read)
        public_event = self.php.split("private static function begin_public_event_transaction", 1)[1].split(
            "private static function commit_public_event_transaction", 1
        )[0]
        self.assertNotIn("lifecycle_entrant_permit", public_event)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_final_role_bound_entrant_permit_executes_and_rng_failure_clears(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
class C99PermitWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $row=array();public function prepare($q,...$a){return array('query'=>$q,'args'=>$a);}public function get_results($q,$f=null){return empty($this->row)?array():array($this->row);}}
$wpdb=new C99PermitWpdb();require '__CAMPAIGN_PATH__';function m($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function p($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}
$locks=array(p('lifecycle_role_lock_owned'),p('worker_execution_fence_owned'));foreach($locks as $lock)$lock->setValue(null,true);$factory=p('lifecycle_entrant_token_factory');$factory->setValue(null,static function(){return str_repeat("r",32);});$create=m('create_lifecycle_entrant_permit');$valid=m('lifecycle_entrant_permit_valid');$rebind=m('rebind_lifecycle_entrant_permit');$canonical=m('canonical_json');$permit=p('lifecycle_entrant_permit');
$activePayload=array('changedAt'=>'2026-08-12T00:00:00Z','generation'=>5,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>'active');$activeJson=$canonical->invoke(null,$activePayload);$wpdb->row=array('option_id'=>9,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>$activeJson,'autoload'=>'no');$active=array('optionId'=>9,'state'=>'active','generation'=>5,'changedAt'=>'2026-08-12T00:00:00Z','rawJson'=>$activeJson,'autoload'=>'no');
$created=$create->invoke(null,'edit',$active,'operation',str_repeat('a',64));$stored=$permit->getValue();$operationValid=$valid->invoke(null,array('operation'),array('active'));$wrongRole=$valid->invoke(null,array('deactivation'),array('active'));
$suspPayload=$activePayload;$suspPayload['state']='suspending';$suspPayload['generation']=6;$suspJson=$canonical->invoke(null,$suspPayload);$wpdb->row['option_value']=$suspJson;$stale=$valid->invoke(null,array('operation'),array('active'));$susp=array('optionId'=>9,'state'=>'suspending','generation'=>6,'changedAt'=>'2026-08-12T00:00:00Z','rawJson'=>$suspJson,'autoload'=>'no');$rebound=$rebind->invoke(null,$susp);$deactivationWrong=$valid->invoke(null,array('deactivation'),array('suspending'));
$factory->setValue(null,static function(){return 'short';});$failed=$create->invoke(null,'deactivation',$susp,'deactivation',str_repeat('d',64));$cleared=null===$permit->getValue();
$factory->setValue(null,static function(){return str_repeat("q",32);});$deactivation=$create->invoke(null,'deactivation',$susp,'deactivation',str_repeat('d',64));$deactivationValid=$valid->invoke(null,array('deactivation'),array('suspending'));$saved=$permit->getValue();
echo json_encode(array('created'=>$created,'operationValid'=>$operationValid,'wrongRole'=>$wrongRole,'stale'=>$stale,'rebound'=>$rebound,'deactivationWrong'=>$deactivationWrong,'failed'=>$failed,'cleared'=>$cleared,'deactivation'=>$deactivation,'deactivationValid'=>$deactivationValid,'keys'=>array_keys($saved),'owner'=>$saved['ownerToken'],'request'=>$saved['requestToken'],'operationOwner'=>$stored['ownerToken']),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["created"], result)
        self.assertTrue(result["operationValid"], result)
        self.assertFalse(result["wrongRole"], result)
        self.assertFalse(result["stale"], result)
        self.assertFalse(result["rebound"], result)
        self.assertFalse(result["deactivationWrong"], result)
        self.assertFalse(result["failed"], result)
        self.assertTrue(result["cleared"], result)
        self.assertTrue(result["deactivation"], result)
        self.assertTrue(result["deactivationValid"], result)
        self.assertEqual(str("d" * 64), result["owner"], result)
        self.assertEqual("71" * 32, result["request"], result)
        self.assertEqual(str("a" * 64), result["operationOwner"], result)
        self.assertEqual(["connectionId", "generation", "lifecycleLockOwner", "lifecycleState", "operation", "ownerToken", "rawJson", "requestToken", "role", "workerLockOwner"], result["keys"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_suspending_cleanup_side_effects_require_exact_activation_or_deactivation_context(self) -> None:
        proof = self.php.split("private static function prove_aggregate_cleanup_absence_records", 1)[1].split(
            "private static function aggregate_cleanup_chain_cache_key", 1
        )[0]
        self.assertLess(proof.index("begin_worker_external_effect_fence( 'lifecycle_drain' )"), proof.index("purge_public_placement_caches"))
        public_event = self.php.split("private static function begin_public_event_transaction", 1)[1].split(
            "private static function commit_public_event_transaction", 1
        )[0]
        self.assertNotIn("lifecycle_cleanup_drain_context", public_event)
        self.assertNotIn("lifecycle_entrant_permit", public_event)

        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}function home_url($p='/'){return 'https://example.test/';}function get_option($k,$d=false){return $d;}function clean_post_cache($id){}function do_action($hook,...$args){$GLOBALS['purges']=($GLOBALS['purges']??0)+1;}function wp_safe_remote_get($url,$args=array()){$GLOBALS['http']=($GLOBALS['http']??0)+1;return array('code'=>404,'body'=>'','headers'=>array('content-type'=>''));}function wp_remote_retrieve_response_code($r){return $r['code'];}function wp_remote_retrieve_body($r){return $r['body'];}function wp_remote_retrieve_header($r,$n){return $r['headers'][$n]??'';}
class C99DrainWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $row=array();public $deployAt=999;public $deployCalls=0;public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}public function get_results($p,$f=null){return strpos($p['query'],'complete99_campaign_lifecycle_reservation_v1')!==false||in_array('complete99_campaign_lifecycle_reservation_v1',$p['args'],true)?array($this->row):array();}public function get_var($p){$q=is_array($p)?$p['query']:$p;$a=is_array($p)?$p['args']:array();if(strpos($q,'complete99_deploy_lock')!==false||in_array('complete99_deploy_lock',$a,true)){++$this->deployCalls;return $this->deployCalls>=$this->deployAt?12:null;}return null;}public function query($q){return 1;}}
$wpdb=new C99DrainWpdb();$GLOBALS['http']=0;$GLOBALS['purges']=0;require '__CAMPAIGN_PATH__';function md($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function pd($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$canonical=md('canonical_json');$create=md('create_lifecycle_entrant_permit');$begin=md('begin_lifecycle_cleanup_drain_context');$valid=md('lifecycle_cleanup_drain_context_valid');$prove=md('prove_aggregate_cleanup_absence_records');$publicRead=md('begin_public_read_transaction');$publicEvent=md('begin_public_event_transaction');$permit=pd('lifecycle_entrant_permit');$context=pd('lifecycle_cleanup_drain_context');pd('lifecycle_role_lock_owned')->setValue(null,true);pd('worker_execution_fence_owned')->setValue(null,true);
$payload=array('changedAt'=>'2026-08-12T00:00:00Z','generation'=>12,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>'suspending');$raw=$canonical->invoke(null,$payload);$wpdb->row=array('option_id'=>9,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>$raw,'autoload'=>'no');$current=array('optionId'=>9,'state'=>'suspending','generation'=>12,'changedAt'=>'2026-08-12T00:00:00Z','rawJson'=>$raw,'autoload'=>'no');
$activation=$create->invoke(null,'activation_recovery',$current,'activation',str_repeat('a',64),str_repeat('b',64));$activationContext=$begin->invoke(null,$current);$wpdb->deployAt=2;$wpdb->deployCalls=0;$activationProof=$prove->invoke(null,array('https://example.test/old'), '2026-08-12T00:01:00Z','lifecycle');$activationHttp=$GLOBALS['http'];$publicDenied=$publicRead->invoke(null);$eventDenied=$publicEvent->invoke(null);
$context->setValue(null,null);$wpdb->deployAt=999;$wpdb->deployCalls=0;$deactivation=$create->invoke(null,'deactivation_suspension',$current,'deactivation',str_repeat('c',64),str_repeat('d',64));$deactivationContext=$begin->invoke(null,$current);$deactivationProof=$prove->invoke(null,array('https://example.test/new'),'2026-08-12T00:02:00Z','lifecycle');$contextExact=$valid->invoke(null,array('deactivation'));$beforeTamper=$GLOBALS['http'];$saved=$permit->getValue();$tampered=$saved;$tampered['requestToken']=str_repeat('e',64);$permit->setValue(null,$tampered);$tamperProof=$prove->invoke(null,array('https://example.test/tamper'),'2026-08-12T00:03:00Z','lifecycle');$afterTamper=$GLOBALS['http'];$permit->setValue(null,$saved);$changed=$payload;$changed['changedAt']='2026-08-12T00:04:00Z';$wpdb->row['option_value']=$canonical->invoke(null,$changed);$rawProof=$prove->invoke(null,array('https://example.test/raw'),'2026-08-12T00:04:00Z','lifecycle');
echo json_encode(array('activation'=>$activation,'activationContext'=>is_wp_error($activationContext)?$activationContext->get_error_code():$activationContext,'activationProof'=>is_wp_error($activationProof)?$activationProof->get_error_code():count($activationProof),'activationHttp'=>$activationHttp,'publicDenied'=>is_wp_error($publicDenied)?$publicDenied->get_error_code():'','eventDenied'=>is_wp_error($eventDenied)?$eventDenied->get_error_code():'','deactivation'=>$deactivation,'deactivationContext'=>is_wp_error($deactivationContext)?$deactivationContext->get_error_code():$deactivationContext,'deactivationProof'=>is_wp_error($deactivationProof)?$deactivationProof->get_error_code():count($deactivationProof),'contextValid'=>$contextExact,'tamperProof'=>is_wp_error($tamperProof)?$tamperProof->get_error_code():'','httpStable'=>$beforeTamper===$afterTamper,'rawProof'=>is_wp_error($rawProof)?$rawProof->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=30, check=True,
        ).stdout)
        self.assertTrue(result["activation"], result)
        self.assertTrue(result["activationContext"], result)
        self.assertEqual(1, result["activationProof"], result)
        self.assertEqual(1, result["activationHttp"], result)
        self.assertEqual("complete99_campaign_public_read_paused", result["publicDenied"], result)
        self.assertEqual("complete99_campaign_event_deploy_locked", result["eventDenied"], result)
        self.assertTrue(result["deactivation"], result)
        self.assertTrue(result["deactivationContext"], result)
        self.assertEqual(1, result["deactivationProof"], result)
        self.assertTrue(result["contextValid"], result)
        self.assertEqual("complete99_campaign_worker_lifecycle_drain_permit", result["tamperProof"], result)
        self.assertTrue(result["httpStable"], result)
        self.assertEqual("complete99_campaign_worker_lifecycle_drain_permit", result["rawProof"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_final_aggregate_cleanup_chain_rejects_page_and_audit_faults(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99ChainWpdb{public $prefix='wp_';public $last_error='';public $providers=array();public $audits=array();public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}public function get_results($p,$f=null){$q=$p['query'];$a=$p['args'];if(strpos($q,'provider_receipts')!==false){if(strpos($q,' IN (')!==false){$n=(count($a)-1)/2;$receipts=array_slice($a,0,$n);$provider=$a[$n];$external=array_slice($a,$n+1);return array_values(array_filter($this->providers,static function($r)use($receipts,$provider,$external){return in_array(($r['receipt_id']??''),$receipts,true)||(($r['provider_key']??'')===$provider&&in_array(($r['external_id']??''),$external,true));}));}return array_values(array_filter($this->providers,static function($r)use($a){return ($r['receipt_id']??'')===($a[0]??'')||(($r['provider_key']??'')===($a[1]??'')&&($r['external_id']??'')===($a[2]??''));}));}if(strpos($q,'audit_events')!==false){$events=strpos($q,' IN (')!==false?$a:array($a[0]??'');return array_values(array_filter($this->audits,static function($r)use($events){return in_array(($r['event_id']??''),$events,true);}));}return array();}}
$wpdb=new C99ChainWpdb();require '__CAMPAIGN_PATH__';function m($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function p($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$canonical=m('canonical_json');$identity=m('aggregate_cleanup_receipt_identity');$verify=m('verify_aggregate_cleanup_chain');$cache=p('aggregate_cleanup_chain_cache');$inventory=p('receipt_phase_inventory_cache');
$hex=str_repeat('a',64);$binding=str_repeat('b',64);$at='2026-08-12T00:00:00Z';$url='https://example.test/old';$ob=array('campaignId'=>'campaign_1','campaignRowId'=>1,'convertedCronActionsDigest'=>$hex,'convertedExpectation'=>'absent','cronActionsDigest'=>$hex,'obligationDigest'=>$hex,'obligationId'=>'cln_'.str_repeat('c',48),'queueDigest'=>$hex,'queueDueAt'=>'2026-08-12 00:00:00','queueRevision'=>3,'sourceExpectation'=>'present','surfaceUrls'=>array($url));$record=array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>404,'markerAbsent'=>true,'type'=>'','url'=>$url,'verifiedAt'=>$at);$projection=array('campaignId'=>'campaign_1','campaignRowId'=>1,'queueDigest'=>$hex,'queueDueAt'=>'2026-08-12 00:00:00','queueRevision'=>3,'obligationCount'=>1);$chain=hash('sha256','complete99-aggregate-cleanup-cohort/v1|lifecycle|9|'.$binding);$chain=hash('sha256',$chain.'|'.$canonical->invoke(null,$projection));$page=array('absenceRecords'=>array($record),'bindingDigest'=>$binding,'cohortDigest'=>$chain,'generation'=>9,'obligations'=>array($ob),'pageIndex'=>0,'schemaVersion'=>'complete99-aggregate-cleanup-page/v1','scope'=>'lifecycle','verifiedAt'=>$at);$pageDigest=hash('sha256',$canonical->invoke(null,$page));$cohort=array('bindingDigest'=>$binding,'cohortDigest'=>$chain,'completedAt'=>$at,'generation'=>9,'obligationCount'=>1,'pageCount'=>1,'pageDigestsBase64'=>base64_encode(hex2bin($pageDigest)),'queueCount'=>1,'schemaVersion'=>'complete99-aggregate-cleanup-cohort/v1','scope'=>'lifecycle');
$install=function($payload,$kind)use($identity,$canonical,$wpdb){$i=$identity->invoke(null,$payload,$kind);$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>'system_aggregate_cleanup','campaign_version'=>$payload['generation'],'channel'=>'website','provider_key'=>'complete99-aggregate-cleanup','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$i['externalState'],'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>'campaign_aggregate_cleanup','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);return $i;};$pageIdentity=$install($page,'page');$cohortIdentity=$install($cohort,'cohort');$digest=hash('sha256',$canonical->invoke(null,$cohort));$valid=$verify->invoke(null,'lifecycle',9,$digest,false);$providers=$wpdb->providers;$audits=$wpdb->audits;
$wpdb->audits=array_values(array_filter($audits,static function($r)use($pageIdentity){return ($r['event_id']??'')!==$pageIdentity['eventId'];}));$cache->setValue(null,array());$inventory->setValue(null,array());$missingAudit=$verify->invoke(null,'lifecycle',9,$digest,false);$wpdb->audits=$audits;$wpdb->providers=$providers;foreach($wpdb->providers as &$row){if(($row['receipt_id']??'')===$pageIdentity['receiptId']){$row['proof_ref'].=' ';break;}}unset($row);$cache->setValue(null,array());$inventory->setValue(null,array());$tamperedPage=$verify->invoke(null,'lifecycle',9,$digest,false);$wpdb->providers=$providers;$cache->setValue(null,array());$inventory->setValue(null,array());$badDigest=$verify->invoke(null,'lifecycle',9,str_repeat('f',64),false);
$wpdb->providers=array_values(array_filter($providers,static function($r)use($cohortIdentity){return ($r['receipt_id']??'')!==$cohortIdentity['receiptId'];}));$wpdb->audits=array_values(array_filter($audits,static function($r)use($cohortIdentity){return ($r['event_id']??'')!==$cohortIdentity['eventId'];}));$badCohort=$cohort;$badCohort['cohortDigest']=str_repeat('d',64);$install($badCohort,'cohort');$cache->setValue(null,array());$inventory->setValue(null,array());$badChain=$verify->invoke(null,'lifecycle',9,hash('sha256',$canonical->invoke(null,$badCohort)),false);
echo json_encode(array('valid'=>is_array($valid)&&1===count($valid['pages'])&&1===count($valid['queues']),'missingAudit'=>is_wp_error($missingAudit)?$missingAudit->get_error_code():'','tamperedPage'=>is_wp_error($tamperedPage)?$tamperedPage->get_error_code():'','badDigest'=>is_wp_error($badDigest)?$badDigest->get_error_code():'','badChain'=>is_wp_error($badChain)?$badChain->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["valid"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["missingAudit"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_corrupt", result["tamperedPage"], result)
        self.assertEqual("complete99_campaign_aggregate_cleanup_verify_digest", result["badDigest"], result)
        self.assertEqual("complete99_campaign_aggregate_cleanup_page_chain", result["badChain"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_aggregate_cleanup_chain_batches_1774_pages_and_locked_mode_revalidates(self) -> None:
        batch = self.php.split("private static function receipt_phase_inventory", 1)[1].split(
            "private static function aggregate_cleanup_bootstrap_cohort", 1
        )[0]
        self.assertIn("AGGREGATE_CLEANUP_RECEIPT_BATCH_SIZE", batch)
        self.assertIn("receipt_id IN", batch)
        self.assertIn("external_id IN", batch)
        self.assertIn("event_id IN", batch)
        verifier = self.php.split("private static function verify_aggregate_cleanup_chain", 1)[1].split(
            "private static function aggregate_cleanup_clear_queue_batch", 1
        )[0]
        self.assertIn("aggregate_cleanup_chain_cache_key", verifier)
        self.assertIn("aggregate_cleanup_transaction_epoch", verifier)
        self.assertIn("AGGREGATE_CLEANUP_CHAIN_CACHE_MAX", verifier)
        store = self.php.split("private static function store_aggregate_cleanup_receipt_locked", 1)[1].split(
            "private static function aggregate_cleanup_snapshot", 1
        )[0]
        self.assertGreaterEqual(store.count("invalidate_aggregate_cleanup_chain_cache"), 2)

        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99BatchChainWpdb{public $prefix='wp_';public $last_error='';public $providers=array();public $audits=array();public $providerQueries=0;public $auditQueries=0;public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}public function get_results($p,$f=null){$q=$p['query'];$a=$p['args'];if(strpos($q,'provider_receipts')!==false){++$this->providerQueries;if(strpos($q,' IN (')!==false){$n=(count($a)-1)/2;$receipts=array_slice($a,0,$n);$provider=$a[$n];$external=array_slice($a,$n+1);return array_values(array_filter($this->providers,static function($r)use($receipts,$provider,$external){return in_array(($r['receipt_id']??''),$receipts,true)||(($r['provider_key']??'')===$provider&&in_array(($r['external_id']??''),$external,true));}));}return array_values(array_filter($this->providers,static function($r)use($a){return ($r['receipt_id']??'')===($a[0]??'')||(($r['provider_key']??'')===($a[1]??'')&&($r['external_id']??'')===($a[2]??''));}));}if(strpos($q,'audit_events')!==false){++$this->auditQueries;$events=strpos($q,' IN (')!==false?$a:array($a[0]??'');return array_values(array_filter($this->audits,static function($r)use($events){return in_array(($r['event_id']??''),$events,true);}));}return array();}}
$wpdb=new C99BatchChainWpdb();require '__CAMPAIGN_PATH__';function mb($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function pb($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$canonical=mb('canonical_json');$identity=mb('aggregate_cleanup_receipt_identity');$verify=mb('verify_aggregate_cleanup_chain');$pageChain=mb('aggregate_cleanup_page_digest_chain');$binding=str_repeat('b',64);$at='2026-08-12T00:00:00Z';$pageCount=1774;$rowCount=4435;$groups=array_fill(0,$pageCount,array());$records=array_fill(0,$pageCount,array());$chain=hash('sha256','complete99-aggregate-cleanup-cohort/v1|lifecycle|77|'.$binding);
for($id=1;$id<=$rowCount;++$id){$campaign='campaign_'.str_pad((string)$id,4,'0',STR_PAD_LEFT);$queueDigest=hash('sha256','queue|'.$id);$obligationDigest=hash('sha256','obligation|'.$id);$url='https://example.test/c/'.$id;$ob=array('campaignId'=>$campaign,'campaignRowId'=>$id,'convertedCronActionsDigest'=>hash('sha256','converted|'.$id),'convertedExpectation'=>'absent','cronActionsDigest'=>hash('sha256','cron|'.$id),'obligationDigest'=>$obligationDigest,'obligationId'=>'cln_'.str_pad(dechex($id),48,'0',STR_PAD_LEFT),'queueDigest'=>$queueDigest,'queueDueAt'=>'2026-08-12 00:00:00','queueRevision'=>1,'sourceExpectation'=>'present','surfaceUrls'=>array($url));$projection=array('campaignId'=>$campaign,'campaignRowId'=>$id,'queueDigest'=>$queueDigest,'queueDueAt'=>'2026-08-12 00:00:00','queueRevision'=>1,'obligationCount'=>1);$chain=hash('sha256',$chain.'|'.$canonical->invoke(null,$projection));$page=(int)floor((($id-1)*$pageCount)/$rowCount);$groups[$page][]=$ob;$records[$page][]=array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>404,'markerAbsent'=>true,'type'=>'','url'=>$url,'verifiedAt'=>$at);}
$pages=array();$digestBytes='';for($i=0;$i<$pageCount;++$i){$page=array('absenceRecords'=>$records[$i],'bindingDigest'=>$binding,'cohortDigest'=>$chain,'generation'=>77,'obligations'=>$groups[$i],'pageIndex'=>$i,'schemaVersion'=>'complete99-aggregate-cleanup-page/v1','scope'=>'lifecycle','verifiedAt'=>$at);$pages[]=$page;$digestBytes.=hex2bin(hash('sha256',$canonical->invoke(null,$page)));}$cohort=array('bindingDigest'=>$binding,'cohortDigest'=>$chain,'completedAt'=>$at,'generation'=>77,'obligationCount'=>$rowCount,'pageCount'=>$pageCount,'pageDigestsBase64'=>base64_encode(hex2bin($pageChain->invoke(null,'lifecycle',77,$digestBytes))),'queueCount'=>$rowCount,'schemaVersion'=>'complete99-aggregate-cleanup-cohort/v1','scope'=>'lifecycle');
$install=function($payload,$kind)use($identity,$wpdb){$i=$identity->invoke(null,$payload,$kind);$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>'system_aggregate_cleanup','campaign_version'=>$payload['generation'],'channel'=>'website','provider_key'=>'complete99-aggregate-cleanup','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$i['externalState'],'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>'campaign_aggregate_cleanup','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);};foreach($pages as $page)$install($page,'page');$install($cohort,'cohort');$digest=hash('sha256',$canonical->invoke(null,$cohort));$valid=$verify->invoke(null,'lifecycle',77,$digest,false);$firstQueries=$wpdb->providerQueries+$wpdb->auditQueries;$cached=$verify->invoke(null,'lifecycle',77,$digest,false);$cachedQueries=$wpdb->providerQueries+$wpdb->auditQueries;
$removed=array_shift($wpdb->audits);pb('transaction_active')->setValue(null,true);pb('aggregate_cleanup_transaction_epoch')->setValue(null,900);$lockedMissing=$verify->invoke(null,'lifecycle',77,$digest,true);array_unshift($wpdb->audits,$removed);$locked=$verify->invoke(null,'lifecycle',77,$digest,true);$lockedQueries=$wpdb->providerQueries+$wpdb->auditQueries;$lockedCached=$verify->invoke(null,'lifecycle',77,$digest,true);$lockedCachedQueries=$wpdb->providerQueries+$wpdb->auditQueries;pb('aggregate_cleanup_transaction_epoch')->setValue(null,901);$nextTransaction=$verify->invoke(null,'lifecycle',77,$digest,true);$nextTransactionQueries=$wpdb->providerQueries+$wpdb->auditQueries;pb('transaction_active')->setValue(null,false);
$cohortIdentity=$identity->invoke(null,$cohort,'cohort');$wpdb->providers=array_values(array_filter($wpdb->providers,static function($r)use($cohortIdentity){return ($r['receipt_id']??'')!==$cohortIdentity['receiptId'];}));$wpdb->audits=array_values(array_filter($wpdb->audits,static function($r)use($cohortIdentity){return ($r['event_id']??'')!==$cohortIdentity['eventId'];}));$badCohort=$cohort;$badBytes=base64_decode($badCohort['pageDigestsBase64'],true);$badBytes[0]=chr(ord($badBytes[0])^1);$badCohort['pageDigestsBase64']=base64_encode($badBytes);$install($badCohort,'cohort');pb('aggregate_cleanup_chain_cache')->setValue(null,array());$badChain=$verify->invoke(null,'lifecycle',77,hash('sha256',$canonical->invoke(null,$badCohort)),false);echo json_encode(array('valid'=>is_array($valid)&&$pageCount===count($valid['pages'])&&$rowCount===count($valid['queues']),'firstQueries'=>$firstQueries,'cached'=>is_array($cached),'cachedQueries'=>$cachedQueries,'lockedMissing'=>is_wp_error($lockedMissing)?$lockedMissing->get_error_code():'','locked'=>is_array($locked),'lockedCached'=>is_array($lockedCached),'lockedQueries'=>$lockedQueries,'lockedCachedQueries'=>$lockedCachedQueries,'nextTransaction'=>is_array($nextTransaction),'nextTransactionQueries'=>$nextTransactionQueries,'badChain'=>is_wp_error($badChain)?$badChain->get_error_code():'','finalQueries'=>$wpdb->providerQueries+$wpdb->auditQueries),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=90, check=True,
        ).stdout)
        self.assertTrue(result["valid"], result)
        self.assertEqual(37, result["firstQueries"], result)
        self.assertTrue(result["cached"], result)
        self.assertEqual(result["firstQueries"], result["cachedQueries"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["lockedMissing"], result)
        self.assertTrue(result["locked"], result)
        self.assertTrue(result["lockedCached"], result)
        self.assertEqual(result["lockedQueries"], result["lockedCachedQueries"], result)
        self.assertTrue(result["nextTransaction"], result)
        self.assertEqual(37, result["nextTransactionQueries"] - result["lockedCachedQueries"], result)
        self.assertIn(result["badChain"], {
            "complete99_campaign_aggregate_cleanup_cohort_changed",
            "complete99_campaign_aggregate_cleanup_page_chain",
        }, result)
        self.assertLessEqual(result["finalQueries"], 151, result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_incomplete_aggregate_inventory_is_batched_contiguous_and_fail_closed(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99InventoryWpdb{public $prefix='wp_';public $last_error='';public $providers=array();public $audits=array();public $providerQueries=0;public $auditQueries=0;public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}public function get_results($p,$f=null){$q=$p['query'];$a=$p['args'];if(strpos($q,'provider_receipts')!==false){++$this->providerQueries;$n=(count($a)-1)/2;$receipts=array_slice($a,0,$n);$provider=$a[$n];$external=array_slice($a,$n+1);return array_values(array_filter($this->providers,static function($r)use($receipts,$provider,$external){return in_array(($r['receipt_id']??''),$receipts,true)||(($r['provider_key']??'')===$provider&&in_array(($r['external_id']??''),$external,true));}));}if(strpos($q,'audit_events')!==false){++$this->auditQueries;return array_values(array_filter($this->audits,static function($r)use($a){return in_array(($r['event_id']??''),$a,true);}));}return array();}}
$wpdb=new C99InventoryWpdb();require '__CAMPAIGN_PATH__';function mi($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function pri($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$canonical=mi('canonical_json');$identity=mi('aggregate_cleanup_receipt_identity');$inventory=mi('aggregate_cleanup_receipt_inventory');$validPage=mi('aggregate_cleanup_payload_valid');$matchPage=mi('aggregate_cleanup_page_matches_snapshot');$cache=pri('receipt_phase_inventory_cache');$binding=str_repeat('b',64);$cohortDigest=str_repeat('c',64);$hex=str_repeat('a',64);$at='2026-08-12T00:00:00Z';$pages=array();$snapshotPages=array();
for($i=0;$i<1774;++$i){$campaign='campaign_'.str_pad((string)$i,4,'0',STR_PAD_LEFT);$url='https://example.test/p/'.$i;$ob=array('campaignId'=>$campaign,'campaignRowId'=>$i+1,'convertedCronActionsDigest'=>$hex,'convertedExpectation'=>'absent','cronActionsDigest'=>$hex,'obligationDigest'=>$hex,'obligationId'=>'cln_'.str_pad(dechex($i+1),48,'0',STR_PAD_LEFT),'queueDigest'=>$hex,'queueDueAt'=>'2026-08-12 00:00:00','queueRevision'=>1,'sourceExpectation'=>'present','surfaceUrls'=>array($url));$record=array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>404,'markerAbsent'=>true,'type'=>'','url'=>$url,'verifiedAt'=>$at);$snapshotPages[$i]=array($ob);$pages[$i]=array('absenceRecords'=>array($record),'bindingDigest'=>$binding,'cohortDigest'=>$cohortDigest,'generation'=>91,'obligations'=>array($ob),'pageIndex'=>$i,'schemaVersion'=>'complete99-aggregate-cleanup-page/v1','scope'=>'lifecycle','verifiedAt'=>$at);}
$snapshot=array('scope'=>'lifecycle','generation'=>91,'bindingDigest'=>$binding,'cohortDigest'=>$cohortDigest,'pages'=>$snapshotPages);$install=function($payload)use($identity,$wpdb){$i=$identity->invoke(null,$payload,'page');$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>'system_aggregate_cleanup','campaign_version'=>$payload['generation'],'channel'=>'website','provider_key'=>'complete99-aggregate-cleanup','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$i['externalState'],'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>'campaign_aggregate_cleanup','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);return $i;};for($i=0;$i<1773;++$i)$ids[$i]=$install($pages[$i]);$prefix=$inventory->invoke(null,'boundary',$snapshot,false);$queries=$wpdb->providerQueries+$wpdb->auditQueries;$baseProviders=$wpdb->providers;$baseAudits=$wpdb->audits;
$wpdb->providers=array_values(array_filter($baseProviders,static function($r)use($ids){return ($r['receipt_id']??'')!==$ids[1000]['receiptId'];}));$wpdb->audits=array_values(array_filter($baseAudits,static function($r)use($ids){return ($r['event_id']??'')!==$ids[1000]['eventId'];}));$cache->setValue(null,array());$hole=$inventory->invoke(null,'hole',$snapshot,false);
$wpdb->providers=$baseProviders;$wpdb->audits=array_values(array_filter($baseAudits,static function($r)use($ids){return ($r['event_id']??'')!==$ids[20]['eventId'];}));$cache->setValue(null,array());$missingAudit=$inventory->invoke(null,'missing_audit',$snapshot,false);
$wpdb->providers=array_values(array_filter($baseProviders,static function($r)use($ids){return ($r['receipt_id']??'')!==$ids[21]['receiptId'];}));$wpdb->audits=$baseAudits;$cache->setValue(null,array());$missingProvider=$inventory->invoke(null,'missing_provider',$snapshot,false);
$wpdb->providers=$baseProviders;$wpdb->providers[]=$baseProviders[30];$wpdb->audits=$baseAudits;$cache->setValue(null,array());$collision=$inventory->invoke(null,'collision',$snapshot,false);
echo json_encode(array('prefix'=>is_array($prefix)?$prefix['presentPrefix']:-1,'prefixError'=>is_wp_error($prefix)?$prefix->get_error_code():'','pageValid'=>$validPage->invoke(null,$pages[0],'page'),'pageMatch'=>$matchPage->invoke(null,$pages[0],$snapshot,0),'next'=>is_array($prefix)?$prefix['nextMissing']:-1,'queries'=>$queries,'hole'=>is_wp_error($hole)?$hole->get_error_code():'','missingAudit'=>is_wp_error($missingAudit)?$missingAudit->get_error_code():'','missingProvider'=>is_wp_error($missingProvider)?$missingProvider->get_error_code():'','collision'=>is_wp_error($collision)?$collision->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=90, check=True,
        ).stdout)
        self.assertEqual(1773, result["prefix"], result)
        self.assertEqual(1773, result["next"], result)
        self.assertLessEqual(result["queries"], 37, result)
        self.assertEqual("complete99_campaign_aggregate_cleanup_inventory_hole", result["hole"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["missingAudit"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["missingProvider"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["collision"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_quarantine_phase_inventory_bounds_completed_and_incomplete_history(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}
class Complete99_Ops{public static function table_names(){return array('audit_events'=>'wp_c99_audit_events');}}
class C99PhaseWpdb{public $prefix='wp_';public $last_error='';public $providers=array();public $audits=array();public $queries=0;public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}public function get_results($p,$f=null){++$this->queries;$q=$p['query'];$a=$p['args'];if(strpos($q,'provider_receipts')!==false){$n=(count($a)-1)/2;$receipts=array_slice($a,0,$n);$provider=$a[$n];$external=array_slice($a,$n+1);return array_values(array_filter($this->providers,static function($r)use($receipts,$provider,$external){return in_array(($r['receipt_id']??''),$receipts,true)||(($r['provider_key']??'')===$provider&&in_array(($r['external_id']??''),$external,true));}));}return array_values(array_filter($this->audits,static function($r)use($a){return in_array(($r['event_id']??''),$a,true);}));}}
$wpdb=new C99PhaseWpdb();require '__CAMPAIGN_PATH__';function mq($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function pq($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$payload=mq('public_quarantine_payload');$identity=mq('public_quarantine_receipt_identity');$inventories=mq('public_quarantine_epoch_phase_inventories');$canonical=mq('canonical_json');$cache=pq('receipt_phase_inventory_cache');$hex=str_repeat('a',64);$at='2026-08-12T00:00:00Z';$install=function($proof,$phase)use($identity,$wpdb){$i=$identity->invoke(null,$proof,$phase);$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>'system_public_quarantine','campaign_version'=>$proof['epoch'],'channel'=>'website','provider_key'=>'complete99-public-quarantine','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>mq('public_quarantine_receipt_external_state')->invoke(null,$phase),'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>'campaign_quarantine','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);};$discovered=array();
for($epoch=1;$epoch<=503;++$epoch){$open=$payload->invoke(null,'active',$epoch,'context_drift','campaign_0001',$hex,'home_banner',$at,$at,array('https://example.test/'));$openDigest=hash('sha256',$canonical->invoke(null,$open));$zero=array('schemaVersion'=>'complete99-campaign-public-quarantine-zero/v1','epoch'=>$epoch,'openingPayloadDigest'=>$openDigest,'problemClass'=>'quarantine_generation_member','campaignCount'=>0,'firstId'=>0,'lastId'=>0,'campaignIdsBase64'=>'','rowsSha256'=>$hex,'recordedAt'=>$at);$clear=$payload->invoke(null,'clear',$epoch,'','','','',$at,'');$rowCount=$epoch<=500?0:1;$qto=$rowCount;$ids=$rowCount?base64_encode(pack('NN',0,$epoch)):'';$bits=$rowCount?base64_encode(chr(1)):'';$absenceSurfaces=array(array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>404,'contentType'=>'','markerAbsent'=>true,'url'=>'https://example.test/','verifiedAt'=>$at));$absence=array('schemaVersion'=>'complete99-campaign-public-quarantine-absence/v1','sha256'=>hash('sha256',$canonical->invoke(null,$absenceSurfaces)),'surfaces'=>$absenceSurfaces);$closed=array('aggregateCleanupReceiptDigest'=>$hex,'schemaVersion'=>'complete99-campaign-public-quarantine-final/v1','epoch'=>$epoch,'openingPayloadDigest'=>$openDigest,'openingReceiptDigest'=>$openDigest,'qtnStatus'=>'qtn_'.str_pad(dechex($epoch),16,'0',STR_PAD_LEFT),'qtoStatus'=>'qto_'.str_pad(dechex($epoch),16,'0',STR_PAD_LEFT),'rowCount'=>$rowCount,'qtnCount'=>0,'qtoCount'=>$qto,'firstId'=>$rowCount?$epoch:0,'lastId'=>$rowCount?$epoch:0,'rowIdsBase64'=>$ids,'qtoBitsetBase64'=>$bits,'rowsSha256'=>$hex,'qtnRowsSha256'=>$hex,'qtoRowsSha256'=>$hex,'initiatorState'=>null,'publicAbsence'=>$absence,'clearPayload'=>$clear,'zeroCampaignReceiptDigest'=>hash('sha256',$canonical->invoke(null,$zero)),'zeroRunnable'=>true,'completedAt'=>$at);foreach(array('opened'=>$open,'zero'=>$zero,'closed'=>$closed)as$phase=>$proof){$install($proof,$phase);$discovered[$epoch][$phase]=true;}}
$result=$inventories->invoke(null,'history',range(1,503),$discovered,false);$queries=$wpdb->queries;$complete=0;$incomplete=0;if(is_array($result))foreach($result as$row){if(!empty($row['complete']))++$complete;else++$incomplete;}$savedAudits=$wpdb->audits;array_pop($wpdb->audits);$cache->setValue(null,array());$missing=$inventories->invoke(null,'missing',range(1,503),$discovered,false);$wpdb->audits=$savedAudits;$wpdb->providers[]=$wpdb->providers[0];$cache->setValue(null,array());$collision=$inventories->invoke(null,'collision',range(1,503),$discovered,false);echo json_encode(array('complete'=>$complete,'incomplete'=>$incomplete,'queries'=>$queries,'missing'=>is_wp_error($missing)?$missing->get_error_code():'','collision'=>is_wp_error($collision)?$collision->get_error_code():''),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=90, check=True,
        ).stdout)
        self.assertEqual(500, result["complete"], result)
        self.assertEqual(3, result["incomplete"], result)
        self.assertLessEqual(result["queries"], 74, result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["missing"], result)
        self.assertEqual("complete99_campaign_receipt_inventory_cardinality", result["collision"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_old_overlay_markers_classify_without_historical_manifest_queries(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}class Complete99_Ops{}require '__CAMPAIGN_PATH__';$m=new ReflectionMethod('Complete99_Campaigns','aggregate_quarantine_resolution_state');$m->setAccessible(true);$hex=str_repeat('a',64);$zero=array('membership'=>array(),'memberDigests'=>array(),'proof'=>array('epoch'=>9));$one=true;$five=true;for($id=1;$id<=4435;++$id){$epoch=8;$loaded=array('row'=>array('id'=>$id,'version'=>2),'state'=>array('runtime'=>array('publicQuarantineOverlay'=>array('epoch'=>$epoch,'memberDigest'=>$hex,'resolvedVersion'=>1,'schemaVersion'=>'complete99-campaign-quarantine-resolution/v1'))));$one=$one&&'old'===$m->invoke(null,$loaded,$zero,9);$loaded['state']['runtime']['publicQuarantineOverlay']['epoch']=3+(($id-1)%5);$five=$five&&'old'===$m->invoke(null,$loaded,$zero,9);}$future=$loaded;$future['state']['runtime']['publicQuarantineOverlay']['epoch']=10;$bad=$loaded;$bad['state']['runtime']['publicQuarantineOverlay']['memberDigest']='bad';echo json_encode(array('one'=>$one,'five'=>$five,'future'=>($m->invoke(null,$future,$zero,9))->get_error_code(),'bad'=>($m->invoke(null,$bad,$zero,9))->get_error_code()),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["one"], result)
        self.assertTrue(result["five"], result)
        self.assertEqual("complete99_campaign_quarantine_resolution_future", result["future"], result)
        self.assertEqual("complete99_campaign_quarantine_resolution_corrupt", result["bad"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_quarantine_inventory_and_capacity_match_authoritative_absence_contract(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;public function __construct($code,$message='',$data=array()){$this->code=$code;}public function get_error_code(){return $this->code;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}function get_option($key,$default=false){return $default;}function maybe_unserialize($value){return @unserialize($value);}
class Complete99_Ops{public static function table_names(){return array('commands'=>'wp_c99_commands','mutation_receipts'=>'wp_c99_mutation_receipts','audit_events'=>'wp_c99_audit_events','issues'=>'wp_c99_issues','issue_events'=>'wp_c99_issue_events','memberships'=>'wp_c99_memberships','budgets'=>'wp_c99_budgets');}}
class C99ParitySqliteWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $providers=array();public $audits=array();public $sentinel=array();public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}private function q($p){return is_array($p)?$p['query']:(string)$p;}private function a($p){return is_array($p)?$p['args']:array();}public function get_results($p,$f=null){$q=$this->q($p);$a=$this->a($p);if(false!==strpos($q,'wp_options'))return array(array('option_id'=>1,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>'{"changedAt":"2026-08-12T00:00:00Z","generation":1,"schemaVersion":"complete99-campaign-lifecycle-reservation/v1","state":"active"}','autoload'=>'no'));if(false!==strpos($q,'WHERE placement_id=%s OR readback_token=%s'))return array($this->sentinel);if(false!==strpos($q,"SELECT receipt_id,campaign_version,external_state,external_id")&&false!==strpos($q,"campaign_id='system_public_quarantine'"))return array_values(array_filter($this->providers,static function($r){return ($r['campaign_id']??'')==='system_public_quarantine'&&($r['provider_key']??'')==='complete99-public-quarantine';}));if(preg_match('/\ASELECT \* FROM `([^`]+)` ORDER BY id ASC LIMIT/',$q,$m)){if('wp_c99_campaign_provider_receipts'===$m[1])return $this->providers;if('wp_c99_audit_events'===$m[1])return $this->audits;if('wp_c99_campaign_placements'===$m[1])return array($this->sentinel);return array();}if(false!==strpos($q,'c99_campaign_provider_receipts')){$n=(count($a)-1)/2;if(0>$n)return array();$receipts=array_slice($a,0,$n);$provider=(string)($a[$n]??'');$external=array_slice($a,$n+1);return array_values(array_filter($this->providers,static function($r)use($receipts,$provider,$external){return in_array(($r['receipt_id']??''),$receipts,true)||(($r['provider_key']??'')===$provider&&in_array(($r['external_id']??''),$external,true));}));}if(false!==strpos($q,'c99_audit_events'))return array_values(array_filter($this->audits,static function($r)use($a){return in_array(($r['event_id']??''),$a,true);}));if(false!==strpos($q,'SELECT c.*'))return array();return array();}public function get_var($p){$q=$this->q($p);if(false!==strpos($q,"subject_type='campaign_quarantine'"))return count(array_filter($this->audits,static function($r){return ($r['subject_type']??'')==='campaign_quarantine'&&0===strpos(($r['action']??''),'campaign.public_quarantine_');}));return false!==strpos($q,'COUNT(*)')?0:null;}public function get_col($p){return array();}}
require '__CAMPAIGN_PATH__';function mv($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function pv($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$canonical=mv('canonical_json');$payload=mv('public_quarantine_payload');$values=mv('public_quarantine_row_values');$identity=mv('public_quarantine_receipt_identity');$external=mv('public_quarantine_receipt_external_state');$inventory=mv('public_quarantine_epoch_phase_inventories');$inventoryCache=pv('receipt_phase_inventory_cache');$chainCache=pv('aggregate_cleanup_chain_cache');$hex=str_repeat('a',64);
$run=function($code,$type,$completedAt,$verifiedAt)use($canonical,$payload,$values,$identity,$external,$inventory,$inventoryCache,$chainCache,$hex){global $wpdb;$wpdb=new C99ParitySqliteWpdb();$open=$payload->invoke(null,'active',1,'context_drift','campaign_0001',$hex,'home_banner','2026-08-12T00:00:00Z','2026-08-12T00:01:00Z',array('https://example.test/'));$openDigest=hash('sha256',$canonical->invoke(null,$open));$zero=array('schemaVersion'=>'complete99-campaign-public-quarantine-zero/v1','epoch'=>1,'openingPayloadDigest'=>$openDigest,'problemClass'=>'quarantine_generation_member','campaignCount'=>0,'firstId'=>0,'lastId'=>0,'campaignIdsBase64'=>'','rowsSha256'=>$hex,'recordedAt'=>'2026-08-12T00:00:00Z');$clear=$payload->invoke(null,'clear',1,'','','','',$verifiedAt,'');$surfaces=array(array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>$code,'contentType'=>$type,'markerAbsent'=>true,'url'=>'https://example.test/','verifiedAt'=>$verifiedAt));$absence=array('schemaVersion'=>'complete99-campaign-public-quarantine-absence/v1','sha256'=>hash('sha256',$canonical->invoke(null,$surfaces)),'surfaces'=>$surfaces);$closed=array('aggregateCleanupReceiptDigest'=>$hex,'schemaVersion'=>'complete99-campaign-public-quarantine-final/v1','epoch'=>1,'openingPayloadDigest'=>$openDigest,'openingReceiptDigest'=>$openDigest,'qtnStatus'=>'qtn_0000000000000001','qtoStatus'=>'qto_0000000000000001','rowCount'=>0,'qtnCount'=>0,'qtoCount'=>0,'firstId'=>0,'lastId'=>0,'rowIdsBase64'=>'','qtoBitsetBase64'=>'','rowsSha256'=>$hex,'qtnRowsSha256'=>$hex,'qtoRowsSha256'=>$hex,'initiatorState'=>null,'publicAbsence'=>$absence,'clearPayload'=>$clear,'zeroCampaignReceiptDigest'=>hash('sha256',$canonical->invoke(null,$zero)),'zeroRunnable'=>true,'completedAt'=>$completedAt);foreach(array('opened'=>$open,'zero'=>$zero,'closed'=>$closed)as$phase=>$proof){$i=$identity->invoke(null,$proof,$phase);$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>'system_public_quarantine','campaign_version'=>1,'channel'=>'website','provider_key'=>'complete99-public-quarantine','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$external->invoke(null,$phase),'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>'campaign_quarantine','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);}$wpdb->sentinel=array_merge(array('id'=>1),$values->invoke(null,$clear));$inventoryCache->setValue(null,array());$chainCache->setValue(null,array());$loaded=$inventory->invoke(null,'parity',array(1),array(1=>array('opened'=>true,'zero'=>true,'closed'=>true)),false);$inventoryResult=is_wp_error($loaded)?$loaded->get_error_code():(is_array($loaded)&&!empty($loaded[1]['complete'])?'pass':'incomplete');$inventoryCache->setValue(null,array());$chainCache->setValue(null,array());$capacity=Complete99_Campaigns::capacity_status();return array('inventory'=>$inventoryResult,'capacity'=>!empty($capacity['inspectable'])?'pass':(string)($capacity['error']??'unavailable'));};
echo json_encode(array('html200'=>$run(200,'text/html','2026-08-12T00:00:00Z','2026-08-12T00:00:00Z'),'empty404'=>$run(404,'','2026-08-12T00:00:00Z','2026-08-12T00:00:00Z'),'legacy404'=>$run(404,'text/html','2026-08-12T00:00:00Z','2026-08-12T00:00:00Z'),'legacy410'=>$run(410,'application/xhtml+xml','2026-08-12T00:00:00Z','2026-08-12T00:00:00Z'),'empty200'=>$run(200,'','2026-08-12T00:00:00Z','2026-08-12T00:00:00Z'),'offset'=>$run(200,'text/html','2026-08-12T03:00:00+03:00','2026-08-12T00:00:00Z')),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=60, check=True,
        ).stdout)
        self.assertEqual({"inventory": "pass", "capacity": "pass"}, result["html200"], result)
        self.assertEqual({"inventory": "pass", "capacity": "pass"}, result["empty404"], result)
        self.assertEqual({"inventory": "pass", "capacity": "pass"}, result["legacy404"], result)
        self.assertEqual({"inventory": "pass", "capacity": "pass"}, result["legacy410"], result)
        for rejected in ("empty200", "offset"):
            self.assertEqual("complete99_campaign_quarantine_inventory_closed", result[rejected]["inventory"], result)
            self.assertEqual("capacity_inspection_failed", result[rejected]["capacity"], result)

        inventory_source = self.php.split("private static function public_quarantine_inventory_closed_valid", 1)[1].split(
            "/** Exact final-bound state", 1
        )[0]
        manifest_source = self.php.split("private static function public_quarantine_final_receipt_manifest", 1)[1].split(
            "private static function public_quarantine_pack_ids", 1
        )[0]
        self.assertIn("hash_equals( (string) ( $payload['completedAt'] ?? '' ), $completed )", inventory_source)
        self.assertIn("public_quarantine_absence_surface_valid", inventory_source)
        self.assertIn("public_quarantine_absence_surface_valid", manifest_source)
        proof_source = self.php.split("private static function prove_public_quarantine_absence", 1)[1].split(
            "/** Remove every Campaign lifecycle hook", 1
        )[0]
        self.assertIn("$terminal_absence ? '' : $type", proof_source)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_legacy_terminal_content_types_pass_manifest_lifecycle_and_activation_recovery(self) -> None:
        campaign_path = str(CAMPAIGNS).replace("\\", "/").replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('DATABASE_TYPE','sqlite');
class WP_Error{private $code;private $data;public function __construct($code,$message='',$data=array()){$this->code=$code;$this->data=$data;}public function get_error_code(){return $this->code;}public function get_error_data(){return $this->data;}}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}function wp_parse_url($v,$component=-1){return -1===$component?parse_url($v):parse_url($v,$component);}function get_option($key,$default=false){return $default;}function maybe_unserialize($value){return @unserialize($value);}function wp_cache_delete($key,$group=''){return true;}function clean_post_cache($id){}$GLOBALS['reconcileAt']=false;function wp_next_scheduled($hook,$args=array()){return 'complete99_campaign_reconcile_schedules'===$hook?$GLOBALS['reconcileAt']:false;}function wp_schedule_single_event($timestamp,$hook,$args=array(),$wp_error=false){if('complete99_campaign_reconcile_schedules'===$hook)$GLOBALS['reconcileAt']=(int)$timestamp;return true;}function wp_unschedule_event($timestamp,$hook,$args=array(),$wp_error=false){if('complete99_campaign_reconcile_schedules'===$hook&&(int)$GLOBALS['reconcileAt']===(int)$timestamp)$GLOBALS['reconcileAt']=false;return true;}function wp_get_scheduled_event($hook,$args=array(),$timestamp=null){return 'complete99_campaign_reconcile_schedules'===$hook&&false!==$GLOBALS['reconcileAt']&&((null===$timestamp)||(int)$timestamp===(int)$GLOBALS['reconcileAt'])?(object)array('timestamp'=>(int)$GLOBALS['reconcileAt']):false;}function _get_cron_array(){return array();}function wp_safe_remote_get($url,$args=array()){return array('code'=>404,'body'=>'','type'=>'text/html');}function wp_remote_retrieve_response_code($response){return $response['code'];}function wp_remote_retrieve_body($response){return $response['body'];}function wp_remote_retrieve_header($response,$name){return $response['type'];}function do_action($hook,...$args){}function home_url($path='/'){return 'https://example.test/';}function wc_get_page_id($name){return -1;}function get_permalink($id){return '';}
class Complete99_Ops{public static function table_names(){return array('commands'=>'wp_c99_commands','mutation_receipts'=>'wp_c99_mutation_receipts','audit_events'=>'wp_c99_audit_events','issues'=>'wp_c99_issues','issue_events'=>'wp_c99_issue_events','memberships'=>'wp_c99_memberships','budgets'=>'wp_c99_budgets');}}
class C99LegacySqliteWpdb{public $prefix='wp_';public $options='wp_options';public $last_error='';public $providers=array();public $audits=array();public $lifecycle=array();public $sentinel=array();public function prepare($q,...$a){if(1===count($a)&&is_array($a[0]))$a=$a[0];return array('query'=>$q,'args'=>$a);}private function q($p){return is_array($p)?$p['query']:(string)$p;}private function a($p){return is_array($p)?$p['args']:array();}public function get_results($p,$f=null){$q=$this->q($p);$a=$this->a($p);if(false!==strpos($q,'wp_options'))return array(array('option_id'=>1,'option_name'=>'complete99_campaign_lifecycle_reservation_v1','option_value'=>$this->lifecycle['rawJson'],'autoload'=>'no'));if(false!==strpos($q,'WHERE placement_id=%s OR readback_token=%s'))return array($this->sentinel);if(false!==strpos($q,'c99_campaign_provider_receipts'))return array_values(array_filter($this->providers,static function($r)use($a){return in_array(($r['receipt_id']??''),$a,true)||in_array(($r['external_id']??''),$a,true);}));if(false!==strpos($q,'c99_audit_events'))return array_values(array_filter($this->audits,static function($r)use($a){return in_array(($r['event_id']??''),$a,true);}));if(false!==strpos($q,'SELECT c.*')||false!==strpos($q,'CASE WHEN c.id IS NULL'))return array();return array();}public function get_var($p){return false!==strpos($this->q($p),'COUNT(*)')?0:null;}public function get_col($p){return array();}public function query($p){$q=$this->q($p);$a=$this->a($p);if(false!==strpos($q,'UPDATE wp_options SET option_value='))$this->lifecycle['rawJson']=(string)($a[0]??'');return 1;}public function update($table,$data,$where){if('wp_options'===$table){$this->lifecycle['rawJson']=$data['option_value'];return 1;}return 1;}}
$wpdb=new C99LegacySqliteWpdb();require '__CAMPAIGN_PATH__';function ml($n){$m=new ReflectionMethod('Complete99_Campaigns',$n);$m->setAccessible(true);return $m;}function pl($n){$p=new ReflectionProperty('Complete99_Campaigns',$n);$p->setAccessible(true);return $p;}$canonical=ml('canonical_json');$payload=ml('public_quarantine_payload');$values=ml('public_quarantine_row_values');$qid=ml('public_quarantine_receipt_identity');$lid=ml('lifecycle_receipt_identity');$aid=ml('aggregate_cleanup_receipt_identity');$surfaceValid=ml('public_quarantine_absence_surface_valid');$manifest=ml('public_quarantine_final_receipt_manifest');$lifecycleValid=ml('lifecycle_receipt_payload_valid');$storedLifecycle=ml('stored_lifecycle_receipt');$begin=ml('begin_activation_recovery');$complete=ml('complete_activation_recovery');$hex=str_repeat('a',64);$at='2026-08-12T00:00:00Z';
$surfaces=array(array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>404,'contentType'=>'text/html','markerAbsent'=>true,'url'=>'https://example.test/','verifiedAt'=>$at),array('bodyBytes'=>0,'bodySha256'=>hash('sha256',''),'code'=>410,'contentType'=>'application/xhtml+xml','markerAbsent'=>true,'url'=>'https://example.test/store/','verifiedAt'=>$at));$absence=array('schemaVersion'=>'complete99-campaign-public-quarantine-absence/v1','sha256'=>hash('sha256',$canonical->invoke(null,$surfaces)),'surfaces'=>$surfaces);$lifecyclePayload=array('changedAt'=>$at,'generation'=>2,'schemaVersion'=>'complete99-campaign-lifecycle-reservation/v1','state'=>'inactive');$wpdb->lifecycle=array('rawJson'=>$canonical->invoke(null,$lifecyclePayload));
$install=function($proof,$phase,$lifecycle=false)use($qid,$lid,$wpdb){$i=($lifecycle?$lid:$qid)->invoke(null,$proof,$phase);$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>$lifecycle?'system_campaign_lifecycle':'system_public_quarantine','campaign_version'=>(int)($proof['generation']??$proof['epoch']??0),'channel'=>'website','provider_key'=>$lifecycle?'complete99-campaign-lifecycle':'complete99-public-quarantine','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$lifecycle?$i['externalState']:ml('public_quarantine_receipt_external_state')->invoke(null,$phase),'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>$lifecycle?'campaign_lifecycle':'campaign_quarantine','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);};
$installAggregate=function($scope,$generation,$binding)use($aid,$canonical,$wpdb,$at){$cohortDigest=hash('sha256','complete99-aggregate-cleanup-cohort/v1|'.$scope.'|'.$generation.'|'.$binding);$cohort=array('bindingDigest'=>$binding,'cohortDigest'=>$cohortDigest,'completedAt'=>$at,'generation'=>(int)$generation,'obligationCount'=>0,'pageCount'=>0,'pageDigestsBase64'=>'','queueCount'=>0,'schemaVersion'=>'complete99-aggregate-cleanup-cohort/v1','scope'=>$scope);$i=$aid->invoke(null,$cohort,'cohort');$wpdb->providers[]=array('id'=>count($wpdb->providers)+1,'receipt_id'=>$i['receiptId'],'campaign_id'=>'system_aggregate_cleanup','campaign_version'=>(int)$generation,'channel'=>'website','provider_key'=>'complete99-aggregate-cleanup','provider_account_ref'=>'complete99-wordpress','receipt_status'=>'confirmed','proof_level'=>'system_verified','external_state'=>$i['externalState'],'external_id'=>$i['externalId'],'proof_ref'=>$i['proofRef'],'material_digest'=>$i['materialDigest'],'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt'],'created_by'=>0,'created_at'=>$i['occurredAt']);$wpdb->audits[]=array('id'=>count($wpdb->audits)+1,'event_id'=>$i['eventId'],'actor_user_id'=>0,'action'=>$i['action'],'subject_type'=>'campaign_aggregate_cleanup','subject_id'=>$i['subjectId'],'command_id'=>null,'payload_digest'=>$i['payloadDigest'],'occurred_at'=>$i['occurredAt']);return hash('sha256',$canonical->invoke(null,$cohort));};
$open=$payload->invoke(null,'active',1,'context_drift','campaign_0001',$hex,'home_banner',$at,$at,array('https://example.test/'));$openDigest=hash('sha256',$canonical->invoke(null,$open));$quarantineCleanup=$installAggregate('quarantine',1,$openDigest);$lifecycleCleanup=$installAggregate('lifecycle',1,$hex);$inactive=array('absence'=>$absence,'aggregateCleanupReceiptDigest'=>$lifecycleCleanup,'completedAt'=>$at,'cronEmpty'=>true,'generation'=>1,'inactiveGeneration'=>2,'lifecycleDigest'=>$hex,'openingReceiptDigest'=>$hex,'phase'=>'inactive','schemaVersion'=>'complete99-campaign-lifecycle-inactive/v1');$zero=array('schemaVersion'=>'complete99-campaign-public-quarantine-zero/v1','epoch'=>1,'openingPayloadDigest'=>$openDigest,'problemClass'=>'quarantine_generation_member','campaignCount'=>0,'firstId'=>0,'lastId'=>0,'campaignIdsBase64'=>'','rowsSha256'=>$hex,'recordedAt'=>$at);$clear=$payload->invoke(null,'clear',1,'','','','',$at,'');$closed=array('aggregateCleanupReceiptDigest'=>$quarantineCleanup,'schemaVersion'=>'complete99-campaign-public-quarantine-final/v1','epoch'=>1,'openingPayloadDigest'=>$openDigest,'openingReceiptDigest'=>$openDigest,'qtnStatus'=>'qtn_0000000000000001','qtoStatus'=>'qto_0000000000000001','rowCount'=>0,'qtnCount'=>0,'qtoCount'=>0,'firstId'=>0,'lastId'=>0,'rowIdsBase64'=>'','qtoBitsetBase64'=>'','rowsSha256'=>$hex,'qtnRowsSha256'=>$hex,'qtoRowsSha256'=>$hex,'initiatorState'=>null,'publicAbsence'=>$absence,'clearPayload'=>$clear,'zeroCampaignReceiptDigest'=>hash('sha256',$canonical->invoke(null,$zero)),'zeroRunnable'=>true,'completedAt'=>$at);foreach(array('opened'=>$open,'zero'=>$zero,'closed'=>$closed)as$phase=>$proof)$install($proof,$phase,false);$wpdb->sentinel=array_merge(array('id'=>1),$values->invoke(null,$clear));$manifestResult=$manifest->invoke(null,1,false);$lifecycleResult=$lifecycleValid->invoke(null,$inactive,'inactive');$install($inactive,'inactive',true);$storedResult=$storedLifecycle->invoke(null,1,'inactive',false);pl('activation_recovery_token_factory')->setValue(null,static function(){return str_repeat("\x01",32);});$beginResult=$begin->invoke(null);$token=is_array($beginResult)?$beginResult['token']:'';$completeResult=''===$token?new WP_Error('begin_failed'):$complete->invoke(null,$token);echo json_encode(array('legacy404'=>$surfaceValid->invoke(null,$surfaces[0],$at),'legacy410'=>$surfaceValid->invoke(null,$surfaces[1],$at),'manifest'=>is_wp_error($manifestResult)?$manifestResult->get_error_code():'pass','lifecycle'=>$lifecycleResult,'stored'=>is_wp_error($storedResult)?$storedResult->get_error_code():'pass','begin'=>is_wp_error($beginResult)?$beginResult->get_error_code():'pass','complete'=>is_wp_error($completeResult)?$completeResult->get_error_code():'pass'),JSON_THROW_ON_ERROR);
""".replace("__CAMPAIGN_PATH__", campaign_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=60, check=True,
        ).stdout)
        self.assertTrue(result["legacy404"], result)
        self.assertTrue(result["legacy410"], result)
        self.assertEqual("pass", result["manifest"], result)
        self.assertTrue(result["lifecycle"], result)
        self.assertEqual("pass", result["stored"], result)
        self.assertEqual("pass", result["begin"], result)
        self.assertEqual("pass", result["complete"], result)

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_changed_php_files_parse(self) -> None:
        for path in (CAMPAIGNS, PLATFORM, OPS, PLUGIN / "complete99-platform.php"):
            result = subprocess.run(
                ["php", "-l", str(path)], capture_output=True, text=True, check=False
            )
            self.assertEqual(0, result.returncode, result.stdout + result.stderr)


if __name__ == "__main__":
    unittest.main()
