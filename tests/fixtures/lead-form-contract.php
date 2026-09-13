<?php
/** Local-only adapter. No network, database writes, email or real leads. */
if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) { http_response_code(404); exit; }
$preview_language = null;
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(405); exit; }
    if (in_array($path, ['/plugin/complete99-platform/assets/css/public.css', '/plugin/complete99-platform/assets/css/consumer.css'], true)) { return false; }
    if (!in_array($path, ['/preview/he/', '/preview/en/'], true)) { http_response_code(404); exit; }
    $preview_language = $path === '/preview/en/' ? 'en' : 'he';
}
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
function get_transient($key) { return $GLOBALS['fixture_options']['rate_count'] ?? 0; }
function set_transient($key, $value, $ttl) { return true; }
function wp_generate_password($length, $special, $extra) { return 'LOCAL'; }
function wp_die($message, $title = '', $args = []) { throw new RuntimeException('rejected:' . ($args['response'] ?? 500)); }
function wp_insert_post($post, $return_error) {
    if (($GLOBALS['mode'] ?? '') !== 'persist') { throw new RuntimeException('validation_passed_no_storage'); }
    if (!empty($GLOBALS['fixture_options']['insert_failure'])) { return 0; }
    $GLOBALS['fixture_posts'][101] = $post;
    return 101;
}
function is_wp_error($value) { return false; }
function wp_slash($value) { return addslashes($value); }
function update_post_meta($id, $key, $value) {
    if (($GLOBALS['fixture_options']['write_failure'] ?? '') === $key) { return false; }
    $GLOBALS['fixture_meta'][$id][$key] = stripslashes($value);
    return empty($GLOBALS['fixture_options']['unchanged_write_result']);
}
function metadata_exists($type, $id, $key) { return array_key_exists($key, $GLOBALS['fixture_meta'][$id] ?? []); }
function get_post_meta($id, $key, $single) { return $GLOBALS['fixture_meta'][$id][$key] ?? ''; }
function wp_cache_delete($id, $group) {
    $GLOBALS['fixture_cache_reads']++;
    $key = $GLOBALS['fixture_options']['readback_corruption'] ?? '';
    if ($key !== '' && isset($GLOBALS['fixture_meta'][$id][$key])) { $GLOBALS['fixture_meta'][$id][$key] = 'corrupted'; }
}
function wp_delete_post($id, $force) {
    if (!empty($GLOBALS['fixture_options']['delete_failure'])) { return false; }
    unset($GLOBALS['fixture_posts'][$id], $GLOBALS['fixture_meta'][$id]);
    return true;
}
function delete_post_meta($id, $key) {
    if (($GLOBALS['fixture_options']['meta_delete_failure'] ?? '') === $key) { return false; }
    unset($GLOBALS['fixture_meta'][$id][$key]);
    return true;
}
function wp_update_post($post) { $GLOBALS['fixture_posts'][$post['ID']] = array_merge($GLOBALS['fixture_posts'][$post['ID']] ?? [], $post); }
function home_url($path = '/') { return 'https://complete99.co.il' . $path; }
function wp_get_referer() { return $GLOBALS['fixture_options']['referer'] ?? 'https://complete99.co.il/request-proposal/'; }
function wp_parse_url($url) { return parse_url($url); }
function esc_url_raw($url) { return $url; }
function add_query_arg($name, $value, $url) { return $url . '?' . rawurlencode($name) . '=' . rawurlencode($value); }
function wp_safe_redirect($url) {
    $GLOBALS['fixture_redirect'] = $url;
    throw new RuntimeException('redirected');
}
require ABSPATH . 'plugin/complete99-platform/includes/class-complete99-leads.php';
$mode = $preview_language ? 'preview' : ($argv[1] ?? 'render');
if ($mode === 'render') {
    Complete99_Leads::render_form($argv[2] ?? 'he', $argv[3] ?? 'group-order');
} elseif ($mode === 'preview') {
    ?><!doctype html><html lang="<?= $preview_language ?>" dir="<?= $preview_language === 'he' ? 'rtl' : 'ltr' ?>">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete99 local group enquiry preview</title>
    <link rel="stylesheet" href="/plugin/complete99-platform/assets/css/public.css">
    <link rel="stylesheet" href="/plugin/complete99-platform/assets/css/consumer.css"></head>
    <body class="complete99-public c99-consumer-site">
    <aside style="padding:12px;background:#17231f;color:white">Local form preview only. No submissions are stored.</aside>
    <main class="c99-container" style="padding-block:24px;max-width:860px">
    <h1><?= $preview_language === 'he' ? 'ספרו לנו מה תרצו לארגן' : 'Tell us what you would like to arrange' ?></h1>
    <div class="c99-group-order-form-card"><?php Complete99_Leads::render_form($preview_language, 'group-order'); ?></div>
    </main></body></html><?php
} elseif (in_array($mode, ['validate', 'persist'], true)) {
    $_POST = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
    $fixture_options = $_POST['_fixture'] ?? [];
    unset($_POST['_fixture']);
    $fixture_posts = [];
    $fixture_meta = [];
    $fixture_cache_reads = 0;
    $fixture_redirect = null;
    try { Complete99_Leads::handle(); }
    catch (RuntimeException $error) {
        $result = ['result' => $error->getMessage()];
        if ($mode === 'persist') {
            $result += ['posts' => $fixture_posts, 'meta' => $fixture_meta,
                'fresh_reads' => $fixture_cache_reads, 'redirect' => $fixture_redirect];
        }
        echo json_encode($result);
    }
} else { throw new RuntimeException('Unsupported fixture mode'); }
