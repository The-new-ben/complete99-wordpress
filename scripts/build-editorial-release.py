#!/usr/bin/env python3
"""Deterministic presentation package, derived from the canonical reviewed platform.

No manually forked consumer renderer. The generated class has a separate name;
the live platform and its recovery journals are never replaced by this package.
"""
import hashlib
import importlib.util
import json
import subprocess
import tempfile
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'complete99-editorial-home'
VERSION = '1.2.0'
DIST = ROOT / 'editorial-dist'
spec = importlib.util.spec_from_file_location('canonical_builder', ROOT / 'scripts/build-plugin-zip.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)

def entries():
    platform = ROOT / 'plugin/complete99-platform'
    source = builder.canonical_contents(platform / 'includes/class-complete99-consumer.php')
    assert source.count(b'final class Complete99_Consumer {') == 1
    source = source.replace(b'final class Complete99_Consumer {', b'final class Complete99_Editorial_Consumer {')
    shell = builder.canonical_contents(platform / 'templates/public-shell.php')
    editorial_shell = shell.replace(b'Complete99_Consumer::render_current', b'c99_editorial_render_content')
    assert shell.count(b'Complete99_Consumer::render_current') == 1
    shell = shell.replace(b'Complete99_Consumer::render_current', b'Complete99_Editorial_Consumer::render_current')
    shell = shell.replace(b'<main id="c99-main"', f'<main data-c99-editorial-release="{VERSION}" id="c99-main"'.encode())
    view = builder.canonical_contents(platform / 'includes/views/food-home.php')
    assert view.count(b"COMPLETE99_PLATFORM_URL . 'assets/images/editorial/") == 2
    view = view.replace(b"COMPLETE99_PLATFORM_URL . 'assets/images/editorial/", b"C99_EDITORIAL_URL . 'assets/")
    result = {
        SLUG + '.php': builder.canonical_contents(ROOT / 'editorial-release/plugin' / (SLUG + '.php')),
        'includes/class-complete99-editorial-consumer.php': source,
        'includes/views/food-home.php': view,
        'public-shell.php': shell,
        'editorial-shell.php': editorial_shell,
        'editorial-content.php': builder.canonical_contents(ROOT / 'editorial-release/plugin/editorial-content.php'),
        'assets/discovery-local.js': builder.canonical_contents(ROOT / 'editorial-release/plugin/assets/discovery-local.js'),
        'assets/consumer.css': builder.canonical_contents(platform / 'assets/css/consumer.css'),
        'assets/public.js': builder.canonical_contents(platform / 'assets/js/public.js'),
        'assets/site-editorial.css': builder.canonical_contents(ROOT / 'editorial-release/plugin/assets/site-editorial.css'),
    }
    for name in ('c99-shared-table-v01.webp', 'c99-shared-table-v01-768.webp'):
        result['assets/' + name] = (platform / 'assets/images/editorial' / name).read_bytes()
    for stem in ('ingredient-still-life-v01', 'aubergine-pan-v01'):
        for width in (640, 1200):
            name = f'{stem}-{width}.webp'
            result['assets/' + name] = (ROOT / 'editorial-release/plugin/assets' / name).read_bytes()
    for name, raw in result.items():
        if builder.forbidden_secret_path_reason(Path(name)) or builder.credential_signature_label(raw):
            raise ValueError('Unsafe package input: ' + name)
    return result

def package_bytes(files):
    import io
    stream = io.BytesIO()
    with zipfile.ZipFile(stream, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, raw in sorted(files.items()):
            info = zipfile.ZipInfo(SLUG + '/' + name, builder.FIXED_TIME)
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, raw, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    return stream.getvalue()

def main():
    files = entries()
    raw = package_bytes(files)
    assert raw == package_bytes(files)
    with tempfile.TemporaryDirectory(prefix='c99-editorial-lint-') as folder:
        for name, contents in files.items():
            if name.endswith('.php'):
                target = Path(folder) / Path(name).name
                target.write_bytes(contents)
                subprocess.run(['php', '-l', str(target)], check=True)
    DIST.mkdir(exist_ok=True)
    name = SLUG + '-' + VERSION + '.zip'
    (DIST / name).write_bytes(raw)
    manifest = {'schema': 'complete99-editorial-release/v1', 'slug': SLUG, 'version': VERSION,
                'artifact': name, 'sha256': hashlib.sha256(raw).hexdigest(),
                'files': {key: hashlib.sha256(value).hexdigest() for key, value in sorted(files.items())}}
    (DIST / 'manifest.json').write_text(json.dumps(manifest, indent=2, sort_keys=True) + '\n', encoding='utf-8', newline='\n')
    print(json.dumps({k:v for k,v in manifest.items() if k != 'files'}))

if __name__ == '__main__':
    main()
