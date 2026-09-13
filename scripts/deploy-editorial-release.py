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

def main():
    commit = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
    if os.environ.get('GITHUB_REF') != 'refs/heads/main' or os.environ.get('GITHUB_SHA') != commit or not re.fullmatch('[a-f0-9]{40}', commit):
        raise RuntimeError('Only the exact protected main workflow may deploy')
    manifest = json.loads((ROOT / 'editorial-dist/manifest.json').read_text())
    url = f'https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/{commit}/editorial-dist/{manifest["artifact"]}'
    raw = public_bytes(url)
    if hashlib.sha256(raw).hexdigest() != manifest['sha256']:
        raise RuntimeError('Independent download differs from reviewed artifact')
    client = transport.Client(os.environ['WP_BASE_URL'], os.environ['WP_DEPLOY_USER'], os.environ['WP_APP_PASSWORD'], allowed_deploy_hosts=os.environ.get('WP_ALLOWED_DEPLOY_HOSTS', 'complete99.co.il'))
    transport.authenticate(client)
    transport.ensure_code_snippets(client, False)
    prior = {path: assert_home(public_bytes(client.base_url + path), False) for path in ('/', '/en/')}
    token = secrets.token_hex(32)
    config = {'commit': commit, 'token': token, 'sha256': manifest['sha256'], 'files': manifest['files'], 'url': url + '?nlcb=' + str(int(time.time()))}
    encoded = json.dumps(config, separators=(',', ':'), ensure_ascii=True)
    if "'" in encoded:
        raise RuntimeError('Unsafe config encoding')
    code = (ROOT / 'deploy/editorial-bridge.php').read_text(encoding='utf-8').removeprefix('<?php').replace('__EDITORIAL_CONFIG_JSON__', encoded)
    name = 'tmp-c99-editorial-' + commit
    existing = transport.active_snippets(client)
    if any(row.get('name') == name for row in existing):
        raise RuntimeError('Existing release bridge must be inspected, not duplicated')
    audit = {'commit': commit, 'artifact_sha256': manifest['sha256'], 'live': False}
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
        if status['files'] != manifest['files'] or not status['active'] or not status['core_unchanged'] or status['version'] != manifest['version']:
            raise RuntimeError('Independent installed-state verification failed')
        for path in prior:
            assert_home(public_bytes(client.base_url + path), True)
        audit['rollback'] = call('rollback')
        if audit['rollback']['active']:
            raise RuntimeError('Rollback deactivation failed')
        for path in prior:
            assert_home(public_bytes(client.base_url + path), False)
        audit['redeploy'] = call('install')
        audit['final_status'] = call('status')
        if not audit['final_status']['active'] or not audit['final_status']['core_unchanged'] or audit['final_status']['files'] != manifest['files']:
            raise RuntimeError('Final state verification failed')
        for path in prior:
            assert_home(public_bytes(client.base_url + path), True)
        audit['live'] = True
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
    print(json.dumps({'live': audit['live'], 'commit': commit, 'route_404': audit.get('route_404', False)}))

if __name__ == '__main__':
    main()
