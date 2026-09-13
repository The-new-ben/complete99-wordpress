import importlib.util
import json
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('editorial_build_test', ROOT / 'scripts/build-editorial-release.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)

def test_package_is_exact_reproducible_and_derived():
    files = builder.entries()
    assert len(files) == 8
    assert builder.package_bytes(files) == builder.package_bytes(files)
    archive = ROOT / 'editorial-dist/complete99-editorial-home-1.0.0.zip'
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
    assert "'overwrite_package' => false" in bridge
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
        function add_action($n,$f,$p){$GLOBALS['actions'][$n]=$f;}
        function wp_dequeue_style($h){}
        function wp_deregister_style($h){}
        function wp_enqueue_style(...$args){$GLOBALS['assets'][]=$args;}
        function wp_dequeue_script($h){}
        function wp_deregister_script($h){}
        function wp_enqueue_script(...$args){$GLOBALS['assets'][]=$args;}
        require '__ENTRY__';
        $actions['wp_enqueue_scripts']();
        echo json_encode(['gate'=>c99_editorial_home_request(),'assets'=>$assets]);
        """.replace('__CONFIG__',cfg).replace('__ENTRY__',entry)
        fixture=tmp_path/'gate.php'
        fixture.write_text(code,encoding='utf-8')
        result=json.loads(subprocess.check_output(['php',str(fixture)],text=True))
        assert result['gate'] is expected
        assert len(result['assets']) == (2 if expected else 0)
        if expected:
            assert all('complete99-editorial-home/' in asset[1] for asset in result['assets'])
