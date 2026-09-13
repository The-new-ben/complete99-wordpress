"""Mechanical format/size conversion of owned generated editorial images."""
from pathlib import Path
from PIL import Image

root = Path(__file__).resolve().parents[1]
out = root / 'editorial-release/plugin/assets'
for stem in ('ingredient-still-life-v01', 'aubergine-pan-v01'):
    with Image.open(root / 'assets/editorial-concepts' / (stem + '.png')) as original:
        for width in (640, 1200):
            image = original.convert('RGB')
            image.thumbnail((width, 2000), Image.Resampling.LANCZOS)
            path = out / f'{stem}-{width}.webp'
            image.save(path, 'WEBP', quality=84, method=6)
            print(path.name, image.size, path.stat().st_size)
