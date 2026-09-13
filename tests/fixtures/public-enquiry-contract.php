<?php
/** CLI-only adapter: native handler with in-memory WP storage, never production. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cfg = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
$fixture_native_leads = $argv[1] ?? null;
$argv = ['', 'library'];
require __DIR__ . '/lead-form-contract.php';
define('COMPLETE99_PLATFORM_VERSION', $cfg['version'] ?? '1.22.1');
function is_admin(){return $GLOBALS['cfg']['admin'] ?? false;}
function is_404(){return $GLOBALS['cfg']['notfound'] ?? false;}
function is_singular(){return $GLOBALS['cfg']['singular'] ?? true;}
function get_queried_object_id(){return 42;}
function get_post_status($id){return $GLOBALS['cfg']['status'] ?? 'publish';}
function get_permalink($id){return $GLOBALS['cfg']['url'] ?? home_url(($GLOBALS['cfg']['lang'] ?? 'he') === 'en' ? '/en/request-proposal/' : '/request-proposal/');}
class Complete99_Content {
    static function language_for_post($id){return $GLOBALS['cfg']['lang'] ?? 'he';}
    static function translation_group_for_post($id){return $GLOBALS['cfg']['group'] ?? 'proposal';}
}
class Complete99_Consumer {}
require ABSPATH . 'editorial-release/plugin/group-enquiry.php';
$mode = 'persist';
$fixture_options = $cfg['options'] ?? [];
$fixture_posts = []; $fixture_meta = []; $fixture_cache_reads = 0; $fixture_redirect = null;
$_POST = $cfg['post'] ?? [];
$_SERVER['REQUEST_METHOD'] = $cfg['method'] ?? 'GET';
ob_start(); Complete99_Leads::render_form($cfg['lang'] ?? 'he', $cfg['interest'] ?? 'group-order'); $original = ob_get_clean();
$html = $cfg['html'] ?? $original;
$url = c99_editorial_enquiry_url();
$changed = c99_editorial_enquiry_form($html, $url);
$result = 'not_dispatched';
try { c99_editorial_submit_enquiry(); } catch (RuntimeException $error) { $result = $error->getMessage(); }
echo json_encode(['url'=>$url, 'original'=>$original, 'html'=>$changed,
    'twice'=>c99_editorial_enquiry_form($changed, $url), 'result'=>$result,
    'posts'=>$fixture_posts, 'meta'=>$fixture_meta, 'redirect'=>$fixture_redirect]);
