import json
import subprocess
from pathlib import Path

import pytest

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / 'editorial-release/plugin/ingredient-nutrition.json'


def run_notes(tmp_path, html, lang):
    source = (ROOT / 'editorial-release/plugin/ingredient-nutrition.php').as_posix()
    code = ('<?php define("ABSPATH","/");'
            'function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,"UTF-8");}'
            'function esc_html($s){return htmlspecialchars($s,ENT_QUOTES,"UTF-8");}'
            'function esc_url($s){return $s;}'
            f'require "{source}"; $html=json_decode(file_get_contents("php://stdin"),true);'
            f'echo c99_editorial_ingredient_nutrition($html,"{lang}");')
    path = tmp_path / 'notes.php'
    path.write_text(code, encoding='utf-8')
    return subprocess.check_output(['php', str(path)], input=json.dumps(html), text=True, encoding='utf-8')


@pytest.mark.parametrize('lang', ['he', 'en'])
def test_exact_cards_keep_original_content_and_links(tmp_path, lang):
    data = json.loads(DATA.read_text(encoding='utf-8'))
    ids = list(data['ingredients']) + ['equipment-wasabi-grater', 'ingredient-rice']
    card = '<article id="{}" class="c99-ingredient-index-card"><img src="/image.webp"><div><h3>Original</h3><p>Original ingredients</p><div class="c99-ingredient-index-links"><a href="/existing/">Order</a></div></div></article>'
    html = ''.join(card.format(key) for key in ids)
    output = run_notes(tmp_path, html, lang)
    assert output.count('data-c99-ingredient-nutrition=') == 3
    assert output.count('<p>Original ingredients</p>') == len(ids)
    assert output.count('href="/existing/"') == len(ids)
    assert output.count('src="/image.webp"') == len(ids)
    for key, entry in data['ingredients'].items():
        assert entry[lang] in output
        assert data['sources'][entry['source']]['url'] in output
    for key in ids[-2:]:
        assert card.format(key) in output
    assert run_notes(tmp_path, output, lang) == output
    assert run_notes(tmp_path, html, 'fr') == html


def test_no_guessing_from_names_or_changed_markup(tmp_path):
    html = ('<article id="ingredient-chickpea" class="product"><h3>Chickpeas</h3><div class="c99-ingredient-index-links">Order</div></article>'
            '<article id="ingredient-tahini" class="c99-ingredient-index-card"><h3>Tahini</h3><div class="changed-links">Order</div></article>')
    assert run_notes(tmp_path, html, 'en') == html


def test_sources_and_small_non_medical_public_payload():
    data = json.loads(DATA.read_text(encoding='utf-8'))
    assert len(data['ingredients']) == 3
    for entry in data['ingredients'].values():
        assert entry['source'] in data['sources']
        for lang in ['he', 'en']:
            assert len(entry[lang].split()) < 55
            assert not any(c in entry[lang] for c in ['—', '–'])
            assert not any(c.isdigit() for c in entry[lang])
    assert set(data) == {'schema', 'sources', 'ingredients'}


def test_new_predecessor_is_exact_live_package():
    import importlib.util
    spec = importlib.util.spec_from_file_location('nutrition_driver', ROOT / 'scripts/deploy-editorial-release.py')
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    raw = (ROOT / 'editorial-dist/complete99-editorial-home-1.2.1.zip').read_bytes()
    prior = module.predecessor('a' * 40, '1.2.2', lambda url: raw)
    assert prior['version'] == '1.2.1'
    assert len(prior['files']) == 17
