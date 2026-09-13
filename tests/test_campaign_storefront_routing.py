"""Execute the real Campaign surface resolver against the CMS page contract."""
import json
from pathlib import Path
import shutil
import subprocess

import pytest

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'plugin/complete99-platform/includes/class-complete99-campaigns.php'


def test_published_store_is_both_render_and_readback_surface():
    if not shutil.which('php'):
        pytest.skip('PHP required')
    fixture = r'''<?php
define('ABSPATH', __DIR__);
class WP_Error { public function __construct(...$args) {} }
function is_wp_error($value) { return $value instanceof WP_Error; }
function home_url($path) { return 'https://example.test' . $path; }
function wp_parse_url($url) { return parse_url($url); }
function wc_get_page_id($key) { $GLOBALS['shop_reads']++; return 649; }
function get_permalink($id) { return $id===649 ? 'https://example.test/shop/' : ($GLOBALS['foreign'] ? 'https://other.test/store/' : 'https://example.test/food-store/'); }
function get_option($key,$default=false) { return $key==='page_on_front' ? 1 : $default; }
function clean_post_cache($id) { $GLOBALS['cleared_ids'][]=$id; }
function do_action($hook,...$args) { $GLOBALS['actions'][]=[$hook,$args]; }
function nocache_headers() { $GLOBALS['nocache_calls']++; }
function wp_enqueue_style(...$args) { $GLOBALS['assets'][]='style'; }
function wp_enqueue_script(...$args) { $GLOBALS['assets'][]='script'; }
define('COMPLETE99_PLATFORM_URL','https://example.test/wp-content/plugins/complete99/');
define('COMPLETE99_PLATFORM_VERSION','fixture');
function is_admin() { return $GLOBALS['admin']; }
function is_preview() { return $GLOBALS['preview']; }
function is_front_page() { return $GLOBALS['front']; }
function is_page() { return $GLOBALS['page']; }
function get_queried_object_id() { return $GLOBALS['queried']; }
class Complete99_Content {
    public static function find_translation_post_id($key, $language, $public) {
        if ($key !== 'store' || $language !== 'he' || $public !== true) { throw new Exception('Wrong CMS ownership'); }
        return $GLOBALS['published_id'];
    }
}
require __SOURCE__;
$url = new ReflectionMethod('Complete99_Campaigns', 'placement_public_url'); $url->setAccessible(true);
$slot = new ReflectionMethod('Complete99_Campaigns', 'public_placement_slot_for_request'); $slot->setAccessible(true);
$cases = ['store'=>[], 'home'=>['front'=>true], 'shop_alias'=>['queried'=>649], 'other_page'=>['queried'=>12], 'missing_store'=>['published_id'=>0], 'external_store'=>['foreign'=>true], 'admin'=>['admin'=>true], 'preview'=>['preview'=>true], 'archive'=>['page'=>false]];
$result=[];
foreach ($cases as $name=>$changes) {
    foreach (array_merge(['admin'=>false,'preview'=>false,'front'=>false,'page'=>true,'queried'=>79,'published_id'=>79,'foreign'=>false,'shop_reads'=>0,'assets'=>[],'actions'=>[],'nocache_calls'=>0], $changes) as $key=>$value) { $GLOBALS[$key]=$value; }
    $target=$url->invoke(null,'store_banner');
    Complete99_Campaigns::enqueue_public_assets();
    Complete99_Campaigns::enforce_public_slot_no_cache();
    $admitted=in_array($name,['store','home'],true);
    if ($GLOBALS['shop_reads']!==0 || count($GLOBALS['assets'])!==($admitted?2:0) || $GLOBALS['nocache_calls']!==($admitted?1:0)) { throw new Exception('Surface hooks disagree'); }
    $result[$name]=['url'=>is_wp_error($target)?null:$target,'slot'=>$slot->invoke(null)];
}
$GLOBALS['published_id']=79;$GLOBALS['foreign']=false;$GLOBALS['cleared_ids']=[];$GLOBALS['actions']=[];
$purge=new ReflectionMethod('Complete99_Campaigns','purge_public_placement_caches');$purge->setAccessible(true);
if ($purge->invoke(null,'fixture')!==true || $GLOBALS['cleared_ids']!==[1,79,649] || !in_array(['litespeed_purge_url',['https://example.test/food-store/']],$GLOBALS['actions'],true)) { throw new Exception('Owned store cache not invalidated'); }
echo json_encode($result,JSON_THROW_ON_ERROR);
'''.replace('__SOURCE__', json.dumps(SOURCE.as_posix()))
    output = subprocess.run(['php'], input=fixture, text=True, capture_output=True, check=True)
    result = json.loads(output.stdout)
    assert result['store'] == {'url': 'https://example.test/food-store/', 'slot': 'store_banner'}
    assert result['home']['slot'] == 'home_banner'
    for name in ('shop_alias', 'other_page', 'missing_store', 'external_store', 'admin', 'preview', 'archive'):
        assert result[name]['slot'] == '', name
    assert result['missing_store']['url'] is None
    assert result['external_store']['url'] is None


def test_renderer_uses_resolver_without_weakening_absence_proof():
    source = SOURCE.read_text(encoding='utf-8')
    renderer = source.split('public static function render_public_placement()', 1)[1].split('$probe_id', 1)[0]
    assert 'self::public_placement_slot_for_request()' in renderer
    assert 'is_shop' not in renderer
    for name in ('enforce_public_slot_no_cache', 'enqueue_public_assets'):
        hook = source.split(f'public static function {name}()', 1)[1].split('\n\t}', 1)[0]
        assert 'self::public_placement_slot_for_request()' in hook
    proof = source.split('private static function prove_public_quarantine_absence', 1)[1].split('private static function', 1)[0]
    assert "'redirection' => 0" in proof
    assert 'public_html_contains_campaign_marker' in proof


def test_previously_deployed_receipt_width_repair_is_not_regressed():
    source = SOURCE.read_text(encoding='utf-8')
    assert 'const PROVIDER_EXTERNAL_STATE_MAX_BYTES = 32;' in source
    assert 'external_state varchar(32) NOT NULL,' in source
    assert "'external_state'     => array( 'type' => 'varchar(32)', 'nullable' => false )" in source
    assert 'external_state varchar(24) NOT NULL,' not in source
    assert "self::PROVIDER_EXTERNAL_STATE_MAX_BYTES < strlen( (string) $identity['externalState'] )" in source
