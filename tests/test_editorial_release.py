import importlib.util
import json
import subprocess
import zipfile
import hashlib
import re
import pytest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('editorial_build_test', ROOT / 'scripts/build-editorial-release.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)

def test_upgrade_uses_exact_prior_archive_and_checks_restored_runtime():
    spec = importlib.util.spec_from_file_location('editorial_upgrade_test', ROOT / 'scripts/deploy-editorial-release.py')
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    old = (ROOT / 'editorial-dist/complete99-editorial-home-1.0.0.zip').read_bytes()
    previous = driver.predecessor('a' * 40, '1.1.0', lambda url: old)
    assert previous['version'] == '1.0.0' and len(previous['files']) == 8
    assert '/'+ 'a' * 40 + '/' in previous['url']
    prior_live = (ROOT / 'editorial-dist/complete99-editorial-home-1.1.0.zip').read_bytes()
    next_previous = driver.predecessor('a' * 40, '1.2.0', lambda url: prior_live)
    assert next_previous['version'] == '1.1.0' and len(next_previous['files']) == 9
    with pytest.raises(RuntimeError, match='Predecessor'):
        driver.predecessor('a' * 40, '1.1.0', lambda url: b'changed')
    with pytest.raises(RuntimeError, match='Unsupported'):
        driver.predecessor('a' * 40, '2.0.0')
    state = {'active': True, 'core_unchanged': True, 'version': '1.0.0', 'files': previous['files']}
    driver.assert_status(state, previous)
    for field, value in [('active', False), ('core_unchanged', False), ('version', '1.1.0'), ('files', {})]:
        with pytest.raises(RuntimeError):
            driver.assert_status({**state, field: value}, previous)

def test_internal_release_requires_loaded_styles_and_preserved_canonical():
    spec = importlib.util.spec_from_file_location('editorial_internal_test', ROOT / 'scripts/deploy-editorial-release.py')
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    old = '<link rel="canonical" href="https://complete99.co.il/dishes/">'
    new = old + '<link rel="stylesheet" href="/wp-content/plugins/complete99-editorial-home/assets/site-editorial.css?ver=1.1.0">'
    assert driver.verify_internal(new, old, '1.1.0')['seo_preserved']
    for broken in (old, new.replace('1.1.0', '1.0.0'), new.replace('/dishes/', '/wrong/')):
        with pytest.raises(RuntimeError):
            driver.verify_internal(broken, old, '1.1.0')

def test_public_verification_checks_metadata_assets_and_destinations():
    spec = importlib.util.spec_from_file_location('editorial_public_test', ROOT / 'scripts/deploy-editorial-release.py')
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    head = ('<link rel="canonical" href="https://complete99.co.il/">'
            '<link rel="alternate" hreflang="he" href="https://complete99.co.il/">'
            '<link rel="alternate" hreflang="en" href="https://complete99.co.il/en/">')
    html = head + ('<body><input data-c99-menu-search><button data-c99-filter></button>'
                   '<p data-c99-filter-empty></p><a data-c99-dish-card href="/dishes/example/">Dish</a>'
                   '<img src="/photo.webp"></body>')
    manifest = {'files': {'assets/public.js': hashlib.sha256(b'script').hexdigest()}}
    responses = {'https://complete99.co.il/dishes/example/': b'<html><body>Dish</body></html>',
                 'https://complete99.co.il/photo.webp': b'RIFFimage',
                 'https://complete99.co.il/wp-content/plugins/complete99-editorial-home/assets/public.js': b'script'}
    result = driver.verify_public(html, head, 'https://complete99.co.il/', manifest, responses.__getitem__)
    assert result['links_checked'] == 1 and result['images_checked'] == 1
    assert result['browser_interactions'] == 'pending'
    for broken in (html.replace('canonical', 'missing'), html.replace('data-c99-menu-search', 'missing'),
                   html.replace('href="/dishes/example/"', 'href="#"')):
        with pytest.raises(RuntimeError):
            driver.verify_public(broken, head, 'https://complete99.co.il/', manifest, responses.__getitem__)
    responses['https://complete99.co.il/wp-content/plugins/complete99-editorial-home/assets/public.js'] = b'wrong'
    with pytest.raises(RuntimeError, match='Public asset'):
        driver.verify_public(html, head, 'https://complete99.co.il/', manifest, responses.__getitem__)

def test_package_is_exact_reproducible_and_derived():
    files = builder.entries()
    assert len(files) == 17
    assert builder.package_bytes(files) == builder.package_bytes(files)
    archive = ROOT / f'editorial-dist/complete99-editorial-home-{builder.VERSION}.zip'
    assert archive.read_bytes() == builder.package_bytes(files)
    with zipfile.ZipFile(archive) as z:
        assert all(name.startswith('complete99-editorial-home/') for name in z.namelist())
    source = files['includes/class-complete99-editorial-consumer.php']
    original = builder.builder.canonical_contents(ROOT / 'plugin/complete99-platform/includes/class-complete99-consumer.php')
    assert source == original.replace(b'final class Complete99_Consumer {', b'final class Complete99_Editorial_Consumer {')
    assert b'Complete99_Consumer::render_header' in files['public-shell.php']
    assert b'Complete99_Consumer::render_footer' in files['public-shell.php']
    assert b'Complete99_Frontend::render_document_head' in files['public-shell.php']
    assert b'C99_EDITORIAL_URL' in files['includes/views/food-home.php']

def test_presentation_has_no_activation_or_data_migration():
    entry = (ROOT / 'editorial-release/plugin/complete99-editorial-home.php').read_text()
    for forbidden in ('register_activation_hook', 'update_option', 'wp_update_post', 'dbDelta', 'wp_redirect', 'wp_robots', 'noindex'):
        assert forbidden not in entry
    bridge = (ROOT / 'deploy/editorial-bridge.php').read_text()
    assert "flock( $process, LOCK_EX | LOCK_NB )" in bridge
    assert "'overwrite_package' => $overwrite" in bridge
    assert "! $backup['prior_plugin_absent']" in bridge
    assert "$current_files !== $config['files'] && $current_files !== $backup['prior_files']" in bridge
    assert "copy( $archive, $working )" in bridge
    assert "/.complete99-deploy-backups/editorial-" in bridge
    assert "rename( $prior_archive, $durable )" in bridge
    assert "$backup['prior_archive'] = $durable" in bridge
    assert 'current_user_can( \'update_plugins\' )' in bridge
    assert "hash_equals( $config['sha256'], hash_file( 'sha256', $temp ) )" in bridge
    assert "deactivate_plugins( $plugin, true )" in bridge
    assert "get_option( $backup_key ) !== $backup" in bridge

def test_home_gate_and_assets_execute(tmp_path):
    entry = (ROOT / 'editorial-release/plugin/complete99-editorial-home.php').as_posix()
    for version, locale, group, admin, singular, notfound, expected in [
        ('1.22.1','he','home',False,True,False,True),
        ('1.22.1','en','home',False,True,False,True),
        ('1.24.0','he','home',False,True,False,False),
        ('1.21.0','he','home',False,True,False,False),
        ('1.22.1','he','dishes',False,True,False,False),
        ('1.22.1','he','home',True,True,False,False),
        ('1.22.1','he','home',False,False,False,False),
        ('1.22.1','he','home',False,True,True,False),
        ('1.22.1','fr','home',False,True,False,False),
    ]:
        cfg = json.dumps([version,locale,group,admin,singular,notfound])
        code = """<?php
        define('ABSPATH', '/wp/');
        $cfg=json_decode('__CONFIG__',true); define('COMPLETE99_PLATFORM_VERSION',$cfg[0]);
        $actions=[]; $assets=[];
        function plugin_dir_url($p){return '/wp-content/plugins/complete99-editorial-home/';}
        function is_admin(){return $GLOBALS['cfg'][3];}
        function is_singular(){return $GLOBALS['cfg'][4];}
        function is_404(){return $GLOBALS['cfg'][5];}
        function get_queried_object_id(){return 1;}
        class Complete99_Content {
          static function translation_group_for_post($id){return $GLOBALS['cfg'][2];}
          static function language_for_post($id){return $GLOBALS['cfg'][1];}
        }
        class Complete99_Consumer {}
        function add_filter($n,$f,$p){}
        function add_action($n,$f,$p){$GLOBALS['actions'][$n][]=$f;}
        function wp_dequeue_style($h){}
        function wp_deregister_style($h){}
        function wp_enqueue_style(...$args){$GLOBALS['assets'][]=$args;}
        function wp_dequeue_script($h){}
        function wp_deregister_script($h){}
        function wp_enqueue_script(...$args){$GLOBALS['assets'][]=$args;}
        function wp_add_inline_script(...$args){}
        require '__ENTRY__';
        foreach($actions['wp_enqueue_scripts'] as $callback){$callback();}
        echo json_encode(['gate'=>c99_editorial_home_request(),'assets'=>$assets]);
        """.replace('__CONFIG__',cfg).replace('__ENTRY__',entry)
        fixture=tmp_path/'gate.php'
        fixture.write_text(code,encoding='utf-8')
        result=json.loads(subprocess.check_output(['php',str(fixture)],text=True))
        assert result['gate'] is expected
        legacy_menu = version == '1.22.1' and group == 'dishes' and locale in ('he','en') and not admin and singular and not notfound
        assert len(result['assets']) == ((2 if expected else 0) + (0 if admin else 1) + int(legacy_menu))
        if expected:
            assert all('complete99-editorial-home/' in asset[1] for asset in result['assets'])

def test_shared_styles_do_not_hide_content_or_change_public_controls():
    css = (ROOT / 'editorial-release/plugin/assets/site-editorial.css').read_text()
    assert 'body.c99-consumer-site' in css
    assert 'prefers-reduced-motion' in css
    assert 'minmax(0, 1fr)' in css
    for component in ('c99-consumer-dish-grid', 'c99-consumer-menu-card',
                      'c99-consumer-editorial-grid', 'c99-group-order-form-card',
                      'c99-ingredient-index-card', 'c99-consumer-header'):
        assert component in css
    for forbidden in ('display: none', 'visibility: hidden', 'pointer-events: none',
                      'position: fixed', 'url('):
        assert forbidden not in css
    assert not re.search(r'(?<![\w-])content\s*:', css)
