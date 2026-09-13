import json
import subprocess
import pytest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

@pytest.mark.parametrize('key,language,old_stem,new_stem', [
    ('ingredients','he','shakshuka-plate','ingredient-still-life'),
    ('ingredients','en','shakshuka-plate','ingredient-still-life'),
    ('knowledge','he','kubeh-beet-soup','aubergine-pan'),
    ('knowledge','en','kubeh-beet-soup','aubergine-pan'),
])
def test_both_hubs_and_languages_retain_original_article(tmp_path,key,language,old_stem,new_stem):
    source=(ROOT/'editorial-release/plugin/editorial-content.php').as_posix()
    html=('<picture>\n<source srcset="/c99-food-'+old_stem+'-gallery-2021-wp-v01.avif" type="image/avif" />\n'
          '<img src="/c99-food-'+old_stem+'-gallery-2021-wp-v01.webp" /></picture>'
          '<section class="c99-ingredient-index" aria-labelledby="c99-ingredient-index-title">Index</section>'
          '<article class="c99-article"><p>Original.</p><a href="/dishes/">Dishes</a></article>')
    code=('<?php define("ABSPATH","/"); define("C99_EDITORIAL_URL","/plugin/");'
          'function esc_attr($s){return htmlspecialchars($s);} function esc_html($s){return htmlspecialchars($s);} function esc_url($s){return $s;}'
          f'require "{source}"; $html=json_decode(file_get_contents("php://stdin"),true);'
          f'echo c99_editorial_enrich_html($html,"{key}","{language}");')
    file=tmp_path/'hub.php'; file.write_text(code,encoding='utf-8')
    output=subprocess.check_output(['php',str(file)],input=json.dumps(html),text=True,encoding='utf-8')
    assert new_stem+'-v01-1200.webp' in output
    assert '<p>Original.</p><a href="/dishes/">Dishes</a>' in output
    assert output.count('id="c99-nutrition-title"') == 1
    if key == 'ingredients':
        assert output.index('id="c99-nutrition-title"') < output.index('>Index</section>')

def test_editorial_enrichment_preserves_content_and_targets_only_known_hero(tmp_path):
    source = ROOT / 'editorial-release/plugin/editorial-content.php'
    code = '''<?php
    define('ABSPATH','/wp/'); define('C99_EDITORIAL_URL','https://complete99.co.il/wp-content/plugins/complete99-editorial-home/');
    function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
    function esc_html($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
    function esc_url($s){return $s;}
    require '__SOURCE__';
    $old='<picture><source srcset="https://complete99.co.il/assets/c99-food-shakshuka-plate-gallery-2021-wp-v01.avif" type="image/avif" /><img src="https://complete99.co.il/assets/c99-food-shakshuka-plate-gallery-2021-wp-v01.webp" alt="old" /></picture>';
    $body=$old.'<article class="c99-article"><h2>Existing title</h2><p>CMS content survives.</p><a href="/old/">Existing link</a></article>';
    $out=c99_editorial_enrich_html($body,'ingredients','he');
    echo json_encode(['old'=>$body,'new'=>$out,'twice'=>c99_editorial_enrich_html($out,'ingredients','he'),'unrelated'=>c99_editorial_enrich_html($body,'store','he'),'unsupported'=>c99_editorial_enrich_html($body,'ingredients','fr')]);
    '''.replace('__SOURCE__',source.as_posix())
    fixture=tmp_path/'enrich.php'; fixture.write_text(code,encoding='utf-8')
    result=json.loads(subprocess.check_output(['php',str(fixture)],text=True,encoding='utf-8'))
    html=result['new']
    assert 'ingredient-still-life-v01-1200.webp' in html
    assert 'width="1200" height="800"' in html and 'srcset=' in html
    assert 'old.avif' not in html and 'shakshuka' not in html
    assert 'CMS content survives.' in html and 'href="/old/"' in html
    assert 'gov.il/he/pages/dietary-guidelines' in html and 'fdc.nal.usda.gov' in html
    assert html.count('id="c99-nutrition-title"') == 1
    assert result['twice'] == html
    assert result['unrelated'] == result['old'] == result['unsupported']
