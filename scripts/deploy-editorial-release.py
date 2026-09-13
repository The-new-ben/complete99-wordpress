#!/usr/bin/env python3
"""Install only the independent homepage package from exact protected-main bytes."""
import hashlib
import importlib.util
import json
import os
import re
import secrets
import subprocess
import sys
import time
import urllib.request
import io
import zipfile
from urllib.parse import urljoin, urlsplit
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('editorial_deployer_transport', ROOT / 'scripts/deploy-wordpress.py')
transport = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = transport
spec.loader.exec_module(transport)

def public_bytes(url):
    opener = urllib.request.build_opener(transport.RejectRedirects())
    with opener.open(urllib.request.Request(url, headers={'User-Agent': transport.USER_AGENT}), timeout=30) as response:
        if response.status != 200:
            raise RuntimeError('Public read failed')
        return response.read(2_000_000)

def assert_home(raw, new):
    text = raw.decode('utf-8')
    body = text.split('<body', 1)[1]
    marker = 'data-c99-home-experience="food-editorial-v2"'
    if (marker in body) != new or ('c99-consumer-hero-grid' in body) == new:
        raise RuntimeError('Rendered homepage does not match expected release')
    return text

def predecessor(commit, version, read=public_bytes):
    if version == '1.0.0':
        return None
    versions = {
        '1.1.0': ('1.0.0', '10dd228e113b9cc1673930167ba8aa81a6e373d0c865be7017990de05d4907f8'),
        '1.2.0': ('1.1.0', '3464b7f620bd52b10c20350688e1ec4644cce76edc921d2e160e6fed9b0aa0c4'),
        '1.2.1': ('1.2.0', '7232ae2c14ce7d164b73d3ac9229dc10b4e257e447721b0589e8bb5491a4a17e'),
        '1.2.2': ('1.2.1', '99081667e34302186d53f783ac4f0b5b0bdcd87fc78b36094d51225f23b8fc60'),
        '1.2.3': ('1.2.2', '6df3a07130af2004d5485ad7e0f80bb64b0face0f66fadb636ebef81602d7824'),
    }
    if version not in versions:
        raise RuntimeError('Unsupported presentation upgrade')
    prior_version, digest = versions[version]
    filename = f'complete99-editorial-home-{prior_version}.zip'
    url = f'https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/{commit}/editorial-dist/{filename}'
    raw = read(url)
    if hashlib.sha256(raw).hexdigest() != digest:
        raise RuntimeError('Predecessor archive differs from verified live release')
    with zipfile.ZipFile(io.BytesIO(raw)) as archive:
        files = {name.removeprefix('complete99-editorial-home/'): hashlib.sha256(archive.read(name)).hexdigest()
                 for name in archive.namelist() if not name.endswith('/')}
    return {'version': prior_version, 'sha256': digest, 'files': dict(sorted(files.items())),
            'url': url + '?nlcb=' + str(int(time.time()))}

def assert_status(status, expected):
    if (not status['active'] or not status['core_unchanged'] or
            status['files'] != expected['files'] or status['version'] != expected['version']):
        raise RuntimeError('Independent installed-state verification failed')

def verify_internal(text, prior, version, path=''):
    page = PublicPage(text)
    if page.seo() != PublicPage(prior).seo() or not any(s[0] == 'canonical' for s in page.seo()):
        raise RuntimeError('Internal page SEO changed')
    if not any(tag == 'link' and '/complete99-editorial-home/assets/site-editorial.css' in a.get('href', '')
               and ('ver=' + version) in a.get('href', '') for tag, a in page.tags):
        raise RuntimeError('Shared presentation stylesheet not loaded')
    result = {'seo_preserved': True, 'shared_stylesheet': version}
    key = path.removeprefix('/en').strip('/')
    if version in ('1.2.0', '1.2.1', '1.2.2', '1.2.3') and key in ('ingredients', 'knowledge'):
        stem = 'ingredient-still-life-v01' if key == 'ingredients' else 'aubergine-pan-v01'
        if (text.count('id="c99-nutrition-title"') != 1 or
                not any(tag == 'picture' and a.get('data-c99-editorial-picture') == key for tag, a in page.tags) or
                not any(tag == 'img' and a.get('src', '').endswith(stem + '-1200.webp') for tag, a in page.tags)):
            raise RuntimeError('Editorial image or nutrition module missing')
        prior_links = {a.get('href') for tag, a in PublicPage(prior).tags if tag == 'a'}
        current_links = {a.get('href') for tag, a in page.tags if tag == 'a'}
        if not prior_links.issubset(current_links):
            raise RuntimeError('Existing editorial navigation removed')
        result.update(editorial_image=stem, nutrition_module=True, existing_links_preserved=True)
    if version in ('1.2.2', '1.2.3') and key == 'ingredients':
        identities = [a['data-c99-ingredient-nutrition'] for tag, a in page.tags if 'data-c99-ingredient-nutrition' in a]
        if sorted(identities) != ['ingredient-chickpea', 'ingredient-olive-oil', 'ingredient-tahini']:
            raise RuntimeError('Exact ingredient nutrition notes missing or duplicated')
        result['ingredient_nutrition_notes'] = identities
    if version in ('1.2.1', '1.2.2', '1.2.3') and key == 'dishes':
        if not any(tag == 'script' and '/complete99-editorial-home/assets/legacy-public.js?ver=' + version in a.get('src', '') for tag, a in page.tags):
            raise RuntimeError('Exact live-core menu compatibility script missing')
        result['live_core_filter_compatibility'] = True
    if version == '1.2.3' and key == 'request-proposal':
        if not any(tag == 'script' and '/complete99-editorial-home/assets/group-enquiry.js?ver=' + version in a.get('src', '') for tag, a in page.tags):
            raise RuntimeError('Group enquiry recovery script missing')
        result['group_enquiry_recovery_loaded'] = True
    return result

class PublicPage(HTMLParser):
    def __init__(self, text):
        super().__init__()
        self.tags = []
        self.feed(text)

    def handle_starttag(self, tag, attrs):
        self.tags.append((tag, dict(attrs)))

    def seo(self):
        return sorted((a.get('rel'), a.get('hreflang', ''), a.get('href', ''))
                      for tag, a in self.tags if tag == 'link'
                      and a.get('rel') in ('canonical', 'alternate'))

def verify_public(text, prior, page_url, manifest, read=public_bytes):
    page = PublicPage(text)
    previous = PublicPage(prior)
    seo = page.seo()
    if seo != previous.seo() or not any(x[0] == 'canonical' and x[2] == page_url for x in seo):
        raise RuntimeError('Canonical or language links changed or are missing')
    if not all(any(x[1] == language for x in seo) for language in ('he', 'en')):
        raise RuntimeError('Missing reciprocal language destinations')
    for attribute in ('data-c99-menu-search', 'data-c99-filter', 'data-c99-filter-empty'):
        if not any(attribute in a for _, a in page.tags):
            raise RuntimeError('Missing search control: ' + attribute)
    cards = [a for tag, a in page.tags if tag == 'a' and 'data-c99-dish-card' in a]
    if not cards or any(not a.get('href') or a['href'].startswith('#') for a in cards):
        raise RuntimeError('Missing real dish destinations')
    links = set()
    images = set()
    for tag, attrs in page.tags:
        if tag == 'a' and attrs.get('href') and not attrs['href'].startswith('#'):
            candidate = urljoin(page_url, attrs['href'])
            if urlsplit(candidate).netloc == urlsplit(page_url).netloc:
                links.add(candidate)
        if tag == 'img' and attrs.get('src'):
            images.add(urljoin(page_url, attrs['src']))
    for url in sorted(links):
        result = read(url).decode('utf-8')
        if '<body' not in result or 'error404' in result.split('<body', 1)[1].split('>', 1)[0]:
            raise RuntimeError('Internal destination is not a working page')
    for url in sorted(images):
        image = read(url)
        if not image or b'<html' in image[:256].lower():
            raise RuntimeError('Image failed to load')
    assets = {}
    for name, digest in manifest['files'].items():
        if not name.startswith('assets/'):
            continue
        url = urljoin(page_url, '/wp-content/plugins/complete99-editorial-home/' + name)
        if hashlib.sha256(read(url)).hexdigest() != digest:
            raise RuntimeError('Public asset differs from reviewed package: ' + name)
        assets[name] = digest
    return {'seo_preserved': True, 'dish_count': len(cards), 'links_checked': len(links),
            'images_checked': len(images), 'assets': assets,
            'browser_interactions': 'pending', 'desktop_mobile_screenshots': 'pending'}

def main():
    commit = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
    if os.environ.get('GITHUB_REF') != 'refs/heads/main' or os.environ.get('GITHUB_SHA') != commit or not re.fullmatch('[a-f0-9]{40}', commit):
        raise RuntimeError('Only the exact protected main workflow may deploy')
    manifest = json.loads((ROOT / 'editorial-dist/manifest.json').read_text())
    previous = predecessor(commit, manifest['version'])
    url = f'https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/{commit}/editorial-dist/{manifest["artifact"]}'
    raw = public_bytes(url)
    if hashlib.sha256(raw).hexdigest() != manifest['sha256']:
        raise RuntimeError('Independent download differs from reviewed artifact')
    client = transport.Client(os.environ['WP_BASE_URL'], os.environ['WP_DEPLOY_USER'], os.environ['WP_APP_PASSWORD'], allowed_deploy_hosts=os.environ.get('WP_ALLOWED_DEPLOY_HOSTS', 'complete99.co.il'))
    transport.authenticate(client)
    transport.ensure_code_snippets(client, False)
    prior = {path: assert_home(public_bytes(client.base_url + path), bool(previous)) for path in ('/', '/en/')}
    internal = {path: public_bytes(client.base_url + path).decode('utf-8') for path in
                ('/dishes/', '/en/dishes/', '/menu/beet-kubbeh/', '/request-proposal/', '/en/request-proposal/', '/ingredients/', '/knowledge/', '/en/ingredients/', '/en/knowledge/')} if previous else {}
    token = secrets.token_hex(32)
    config = {'commit': commit, 'token': token, 'sha256': manifest['sha256'], 'files': manifest['files'], 'url': url + '?nlcb=' + str(int(time.time()))}
    if previous:
        config['prior'] = previous
    encoded = json.dumps(config, separators=(',', ':'), ensure_ascii=True)
    if "'" in encoded:
        raise RuntimeError('Unsafe config encoding')
    code = (ROOT / 'deploy/editorial-bridge.php').read_text(encoding='utf-8').removeprefix('<?php').replace('__EDITORIAL_CONFIG_JSON__', encoded)
    name = 'tmp-c99-editorial-' + commit
    existing = transport.active_snippets(client)
    if any(row.get('name') == name for row in existing):
        raise RuntimeError('Existing release bridge must be inspected, not duplicated')
    audit = {'commit': commit, 'artifact_sha256': manifest['sha256'],
             'installed_and_http_verified': False, 'release_acceptance': 'pending_browser_verification'}
    audit_path = ROOT / 'editorial-audit.json'
    snippet_id = None
    def call(action):
        _, result = client.request('POST', '/wp-json/c99-editorial-deploy/v1/release', {'token': token, 'action': action})
        if not isinstance(result, dict) or 'code' in result:
            raise RuntimeError('Presentation release failed: ' + str(result.get('code', 'invalid response')))
        return result
    try:
        _, created = client.request('POST', '/wp-json/code-snippets/v1/snippets', {'name': name, 'code': code, 'scope': 'global', 'active': True})
        snippet_id = int(created.get('id', created.get('data', {}).get('id', 0)))
        if snippet_id <= 0:
            raise RuntimeError('Missing exact temporary snippet ID')
        audit['snippet_id'] = snippet_id
        audit['before'] = call('status')
        audit['install'] = call('install')
        # Fresh request loads the installed presentation plugin.
        audit['installed_status'] = call('status')
        status = audit['installed_status']
        assert_status(status, manifest)
        for path in prior:
            assert_home(public_bytes(client.base_url + path), True)
        audit['rollback'] = call('rollback')
        if previous:
            audit['rollback_status'] = call('status')
            assert_status(audit['rollback_status'], previous)
        elif audit['rollback']['active']:
            raise RuntimeError('Rollback deactivation failed')
        for path in prior:
            restored = assert_home(public_bytes(client.base_url + path), bool(previous))
            if previous and ('data-c99-editorial-release="' + previous['version'] + '"') not in restored:
                raise RuntimeError('Prior rendered release marker not restored')
        audit['redeploy'] = call('install')
        audit['final_status'] = call('status')
        assert_status(audit['final_status'], manifest)
        audit['public_verification'] = {}
        for path in prior:
            text = assert_home(public_bytes(client.base_url + path), True)
            audit['public_verification'][path] = verify_public(text, prior[path], client.base_url + path, manifest)
        audit['internal_pages'] = {path: verify_internal(public_bytes(client.base_url + path).decode('utf-8'), old, manifest['version'], path)
                                   for path, old in internal.items()}
        audit['installed_and_http_verified'] = True
    except Exception as error:
        audit['error_type'] = type(error).__name__
        if snippet_id:
            try:
                audit['failure_rollback'] = call('rollback')
            except Exception as rollback_error:
                audit['rollback_error_type'] = type(rollback_error).__name__
        raise
    finally:
        # Resolve a lost create response by exact unique name; never create again.
        if snippet_id is None:
            matches = [row for row in transport.active_snippets(client) if row.get('name') == name]
            if len(matches) == 1:
                snippet_id = int(matches[0]['id'])
        if snippet_id:
            try:
                _, retired = client.request('POST', '/wp-json/c99-editorial-deploy/v1/retire', {'token': token})
                audit['cleanup'] = retired
                if retired.get('deleted_id') != snippet_id or not retired.get('deleted'):
                    raise RuntimeError('Temporary row deletion not acknowledged')
                if transport.get_snippet_by_id(client, snippet_id) is not None:
                    raise RuntimeError('Temporary row still exists')
                code, _ = client.request('POST', '/wp-json/c99-editorial-deploy/v1/release', {'token': token, 'action': 'status'}, expected=(404,))
                audit['route_404'] = code == 404
            except Exception:
                client.request('POST', f'/wp-json/code-snippets/v1/snippets/{snippet_id}/deactivate', expected=(200,404))
                raise
            finally:
                audit_path.write_text(json.dumps(audit, indent=2) + '\n', encoding='utf-8')
        else:
            audit_path.write_text(json.dumps(audit, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'installed_and_http_verified': audit['installed_and_http_verified'],
                      'release_acceptance': audit['release_acceptance'], 'commit': commit,
                      'route_404': audit.get('route_404', False)}))

if __name__ == '__main__':
    main()
