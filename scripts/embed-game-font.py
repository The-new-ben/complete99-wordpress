"""Embed the licensed Heebo subset so social browsers need no font request."""
import base64
import re
from pathlib import Path

root = Path(__file__).resolve().parents[1]
font = base64.b64encode((root / "games/fonts/heebo-game.woff").read_bytes()).decode()
css = ('/* GAME_FONT_START: Heebo, SIL Open Font License 1.1, games/fonts/OFL.txt */\n'
       '@font-face{font-family:Heebo;src:url(data:font/woff;base64,' + font +
       ') format("woff");font-weight:100 900;font-display:swap}\n/* GAME_FONT_END */')
for name in ("kitchen", "courier"):
    path = root / f"games/{name}/index.html"
    if not path.exists():
        continue
    source = path.read_text(encoding="utf-8")
    source = re.sub(r'/\* GAME_FONT_START.*?/\* GAME_FONT_END \*/\n?', '', source, flags=re.S)
    source = source.replace('<style>', '<style>\n' + css, 1)
    path.write_text(source, encoding="utf-8", newline="\n")
    print(name, path.stat().st_size, "bytes")
