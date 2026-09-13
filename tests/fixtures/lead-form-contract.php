<?php
/** CLI-only adapter. No network, database writes, email or real leads. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ABSPATH', dirname(__DIR__, 2) . '/');
define('HOUR_IN_SECONDS', 3600);
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function esc_html__($value, $domain = '') { return esc_html($value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function sanitize_textarea_field($value) { return sanitize_text_field($value); }
function sanitize_email($value) { return trim((string) $value); }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }
function wp_unslash($value) { return $value; }
function wp_timezone() { return new DateTimeZone('Asia/Jerusalem'); }
function current_datetime() { return new DateTimeImmutable('2026-09-13 12:00:00', wp_timezone()); }
function admin_url($path) { return '/wp-admin/' . $path; }
function wp_nonce_field($action, $name) { echo '<input type="hidden" name="' . esc_attr($name) . '" value="local-test-only">'; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'local-test-only'; }
function wp_salt($scheme) { return 'local-test-only-not-a-production-secret'; }
function get_transient($key) { return 0; }
function set_transient($key, $value, $ttl) { return true; }
function wp_generate_password($length, $special, $extra) { return 'LOCAL'; }
function wp_die($message, $title = '', $args = []) { throw new RuntimeException('rejected:' . ($args['response'] ?? 500)); }
function wp_insert_post($post, $return_error) { throw new RuntimeException('validation_passed_no_storage'); }
require ABSPATH . 'plugin/complete99-platform/includes/class-complete99-leads.php';
$mode = $argv[1] ?? 'render';
if ($mode === 'render') {
    Complete99_Leads::render_form($argv[2] ?? 'he', $argv[3] ?? 'group-order');
} elseif ($mode === 'validate') {
    $_POST = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
    try { Complete99_Leads::handle(); }
    catch (RuntimeException $error) { echo json_encode(['result' => $error->getMessage()]); }
} else { throw new RuntimeException('Unsupported fixture mode'); }
