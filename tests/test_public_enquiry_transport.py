"""Actual native handler, including archived live 1.22.1; no real leads or mail."""
import io
import json
import subprocess
import zipfile
from pathlib import Path

import pytest
from test_group_request_friction import group_payload

ROOT = Path(__file__).resolve().parents[1]

@pytest.fixture(params=['live_1_22_1', 'current'])
def native_source(request, tmp_path):
    if request.param == 'current':
        return ROOT / 'plugin/complete99-platform/includes/class-complete99-leads.php'
    raw = subprocess.check_output(['git', 'show', 'HEAD:plugin-dist/complete99-platform-1.22.1.zip'], cwd=ROOT)
    with zipfile.ZipFile(io.BytesIO(raw)) as archive:
        source = archive.read('complete99-platform/includes/class-complete99-leads.php')
    file = tmp_path / 'native-leads.php'
    file.write_bytes(source)
    return file

def invoke_public(native_source, **cfg):
    return json.loads(subprocess.check_output(['php', str(ROOT/'tests/fixtures/public-enquiry-contract.php'), str(native_source)], input=json.dumps(cfg), text=True, encoding='utf-8'))

@pytest.mark.parametrize('lang', ['he', 'en'])
def test_native_form_has_public_action_without_javascript(native_source, lang):
    r = invoke_public(native_source, lang=lang)
    expected = 'https://complete99.co.il/' + ('en/' if lang == 'en' else '') + 'request-proposal/'
    assert r['url'] == expected
    assert f'action="{expected}" data-c99-public-enquiry="1"' in r['html']
    assert r['html'] == r['twice']
    assert r['html'].split('>', 1)[1] == r['original'].split('>', 1)[1]
    assert r['result'] == 'not_dispatched'

@pytest.mark.parametrize('cfg', [
    {'admin':True}, {'notfound':True}, {'singular':False}, {'status':'draft'},
    {'group':'dishes'}, {'lang':'fr'}, {'version':'1.21.0'},
    {'url':'https://outside.example/request-proposal/'},
    {'url':'https://complete99.co.il/request-proposal/?page=1'},
    {'url':'https://complete99.co.il/other/'},
])
def test_other_surfaces_are_untouched(native_source, cfg):
    r = invoke_public(native_source, method='POST', post={'action':'complete99_submit_lead','interest':'group-order'}, **cfg)
    assert r['url'] == '' and r['html'] == r['original']
    assert r['result'] == 'not_dispatched'

@pytest.mark.parametrize('lang', ['he','en'])
def test_canonical_post_reaches_native_validation_and_storage(native_source, lang):
    payload = dict(group_payload(), action='complete99_submit_lead', language=lang,
                   organisation='Local test only', message='Local test only')
    page = 'https://complete99.co.il/' + ('en/' if lang=='en' else '') + 'request-proposal/'
    for change, expected in [({'complete99_lead_nonce':'invalid'}, 'rejected:403'), ({'consent':'0'}, 'rejected:400'), ({}, 'redirected')]:
        r = invoke_public(native_source, lang=lang, method='POST', post={**payload, **change}, options={'referer':page})
        assert r['result'] == expected
        if change:
            assert not r['posts'] and r['redirect'] is None
        else:
            assert r['posts']['101']['post_status'] == 'private'
            assert r['meta']['101']['_c99_language'] == lang
            assert r['redirect'] == page + '?c99_sent=1'

def test_upgrade_does_not_remove_transport_and_unrelated_posts_are_ignored(native_source):
    r = invoke_public(native_source, version='1.24.0')
    assert r['url'] and 'data-c99-public-enquiry' in r['html']
    for post in [{'action':'other','interest':'group-order'}, {'action':[],'interest':'group-order'}, {'action':'complete99_submit_lead','interest':'institutional-service'}]:
        r = invoke_public(native_source, method='POST', post=post)
        assert r['result'] == 'not_dispatched'
    r = invoke_public(native_source, interest='institutional-service')
    assert r['html'] == r['original']

def test_unknown_or_duplicated_forms_are_not_rewritten(native_source):
    original = invoke_public(native_source)['original']
    for html in [original + original, original.replace('complete99_submit_lead', 'unrelated'), original.replace('method="post"', 'method="get"')]:
        r = invoke_public(native_source, html=html)
        assert r['html'] == html
