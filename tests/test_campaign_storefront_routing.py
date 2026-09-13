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
function wc_get_page_id($key) { throw new Exception('Redirect alias must not be used'); }
function get_permalink($id) { return $GLOBALS['foreign'] ? 'https://other.test/store/' : 'https://example.test/food-store/'; }
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
    foreach (array_merge(['admin'=>false,'preview'=>false,'front'=>false,'page'=>true,'queried'=>79,'published_id'=>79,'foreign'=>false], $changes) as $key=>$value) { $GLOBALS[$key]=$value; }
    $target=$url->invoke(null,'store_banner');
    $result[$name]=['url'=>is_wp_error($target)?null:$target,'slot'=>$slot->invoke(null)];
}
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
    proof = source.split('private static function prove_public_quarantine_absence', 1)[1].split('private static function', 1)[0]
    assert "'redirection' => 0" in proof
    assert 'public_html_contains_campaign_marker' in proof
