"""HAD-807: publish two fixed game pages and noindex the three game parents.

Uses the site's existing registered metadata, not a plugin install or site-wide
reading setting. No credentials, arbitrary URL, slug or source input is accepted.
"""
from __future__ import annotations

import argparse
import hashlib
import html
import importlib.util
import json
import os
import sys
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ORIGIN = "https://complete99.co.il"
WOLT = "https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete"
AUDIT_DIR = ROOT / "game-sequels-audit"
META = {"_complete99_managed": True, "_complete99_index_eligible": False,
        "_complete99_language": "he"}
GAMES = (
    {"slug": "kitchen-game", "source": "games/kitchen/index.html", "marker": "c99-kitchen-v1",
     "title": "משמרת על האש: המשחק של סביח קומפלט", "description": "מבשלים לקבוצה, מנהלים סירים ועומדים בקצב. אבן גבירול 99."},
    {"slug": "delivery-game", "source": "games/courier/index.html", "marker": "c99-courier-v1",
     "title": "משלוח 99: הסיר יוצא לדרך", "description": "משחק שליחויות עם משימות, פניות וסיר שצריך לשמור עליו. סביח קומפלט, אבן גבירול 99."},
)
LEGACY = {"slug": "game", "id": 970, "marker": "c99-plate-master-v1"}

spec = importlib.util.spec_from_file_location("sequels_transport", ROOT / "scripts/deploy-wordpress.py")
transport = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = transport
spec.loader.exec_module(transport)


def sha(value):
    return hashlib.sha256(value.encode("utf-8")).hexdigest()


class Document(HTMLParser):
    def __init__(self, value):
        super().__init__(convert_charrefs=True)
        self.frames, self.robots, self.in_head = [], [], False
        self.feed(value)

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "head":
            self.in_head = True
        elif tag == "iframe":
            self.frames.append(a)
        elif self.in_head and tag == "meta" and a.get("name", "").lower() in ("robots", "googlebot"):
            self.robots.extend(a.get("content", "").lower().replace(",", " ").split())

    def handle_endtag(self, tag):
        if tag == "head":
            self.in_head = False


def frame_source(content, marker):
    frames = [f for f in Document(content).frames if f.get("id") == marker]
    if len(frames) != 1 or not frames[0].get("srcdoc"):
        raise RuntimeError("Missing or ambiguous owned game iframe")
    return frames[0]["srcdoc"]


def build_content(game, source):
    if not 10_000 < len(source.encode("utf-8")) < 500_000 or WOLT not in source:
        raise ValueError("Unexpected source size or restaurant link")
    # Local page styles only. No theme, measurement or global settings change.
    return ('<!-- wp:html -->\n<style>\n'
            'body:has(#c99-sequel-shell) .c99-consumer-page-hero,body:has(#c99-sequel-shell) .c99-consumer-note{display:none}\n'
            'body:has(#c99-sequel-shell) .c99-consumer-editorial-grid{display:block}\n'
            'body:has(#c99-sequel-shell) .c99-consumer-editorial{padding:12px 0}\n'
            '#c99-sequel-shell{width:100%;max-width:1100px;margin:auto}\n'
            '#c99-sequel-shell iframe{display:block;width:100%;height:min(860px,95svh);min-height:540px;border:0}\n'
            '@media(max-width:600px){body:has(#c99-sequel-shell) .c99-container{padding-inline:4px}}\n'
            '</style>\n<div id="c99-sequel-shell" dir="rtl">\n'
            f'<iframe id="{game["marker"]}" title="{html.escape(game["title"], quote=True)}" '
            'allow="clipboard-write; web-share; fullscreen" allowfullscreen '
            f'srcdoc="{html.escape(source, quote=True)}"></iframe>\n'
            f'<p style="text-align:center">{html.escape(game["description"])}</p>\n'
            '<nav aria-label="משחקים נוספים" style="text-align:center">'
            '<a href="/game/">מאסטר הדלפק</a> · <a href="/kitchen-game/">משמרת על האש</a> · '
            '<a href="/delivery-game/">משלוח 99</a></nav></div>\n<!-- /wp:html -->')


def save(audit):
    AUDIT_DIR.mkdir(exist_ok=True)
    (AUDIT_DIR / "receipt.json").write_text(json.dumps(audit, ensure_ascii=False, indent=2), encoding="utf-8")


def backup(record):
    AUDIT_DIR.mkdir(exist_ok=True)
    path = AUDIT_DIR / f'page-{record["id"]}-before.json'
    if not path.exists():
        path.write_text(json.dumps({k: record.get(k) for k in
            ("id", "slug", "status", "link", "title", "content", "excerpt", "meta", "modified_gmt")},
            ensure_ascii=False, indent=2), encoding="utf-8")


def lookup(client, game):
    _, rows = client.request("GET", f'/wp-json/wp/v2/pages?context=edit&slug={game["slug"]}&status=publish,draft,pending,private,future&per_page=10')
    if not isinstance(rows, list) or len(rows) > 1:
        raise RuntimeError("Ambiguous page lookup")
    if not rows:
        return None
    record = rows[0]
    if record.get("slug") != game["slug"] or record.get("parent", 0) or record.get("type") != "page":
        raise RuntimeError("Unexpected page identity")
    if game.get("id") and record.get("id") != game["id"]:
        raise RuntimeError("Legacy page ID differs")
    frame_source(record.get("content", {}).get("raw", ""), game["marker"])
    return record


def verify_record(record, game, source):
    if record.get("slug") != game["slug"] or record.get("parent", 0) or record.get("type") != "page":
        raise RuntimeError("Saved page identity differs")
    if frame_source(record.get("content", {}).get("raw", ""), game["marker"]) != source:
        raise RuntimeError("Saved source differs")
    if any(record.get("meta", {}).get(k) != v for k, v in META.items()):
        raise RuntimeError("Noindex metadata readback differs")


def verify_public(client, game, source):
    _, page = client.request_anonymous_html(f'/{game["slug"]}/')
    if frame_source(page, game["marker"]) != source:
        raise RuntimeError("Public game source differs")
    if "noindex" not in Document(page).robots:
        raise RuntimeError("Parent head is missing noindex")
    return {"public_url": f'{ORIGIN}/{game["slug"]}/', "source_sha256": sha(source),
            "parent_noindex": True, "public_source_verified": True}


def run(client, apply, audit):
    audit["identity"] = transport.verify_rest_identity(client)
    _, me = client.request("GET", "/wp-json/wp/v2/users/me?context=edit&_fields=id,capabilities")
    if not all(me.get("capabilities", {}).get(k) for k in ("edit_pages", "publish_pages", "unfiltered_html")):
        raise RuntimeError("Existing identity lacks required page capabilities")
    _, home = client.request_anonymous_html("/")
    home_noindex = "noindex" in Document(home).robots
    prepared = []
    # Preflight every destination before the first write.
    for game in (LEGACY, *GAMES):
        record = lookup(client, game)
        if game is LEGACY:
            if record is None or record.get("status") != "publish":
                raise RuntimeError("Expected existing published game 970")
            source = frame_source(record["content"]["raw"], game["marker"])
            if sha(source) != sha((ROOT / "games/plate-master/index.html").read_text(encoding="utf-8")):
                raise RuntimeError("Legacy source changed; review before metadata update")
        else:
            source = (ROOT / game["source"]).read_text(encoding="utf-8")
            build_content(game, source)
            if record and frame_source(record["content"]["raw"], game["marker"]) != source:
                raise RuntimeError("Different owned game source requires explicit version review")
            if record is None:
                status, _ = client.request_anonymous_html(f'/{game["slug"]}/', expected=(200, 404))
                if status != 404:
                    raise RuntimeError("Destination already occupied outside the page record")
        prepared.append((game, record, source))
    if not apply:
        audit["result"] = "ready_no_mutation"
        return
    for game, record, source in prepared:
        item = {"slug": game["slug"], "result": "before_write"}
        audit["pages"].append(item)
        save(audit)
        if record:
            backup(record)
            original_content = record["content"]["raw"]
            # Existing content is preserved byte-for-byte, including the original game.
            _, record = client.request("POST", f'/wp-json/wp/v2/pages/{record["id"]}', {"meta": META})
        else:
            original_content = build_content(game, source)
            _, record = client.request("POST", "/wp-json/wp/v2/pages", {
                "slug": game["slug"], "parent": 0, "title": game["title"],
                "excerpt": game["description"], "content": original_content, "status": "draft", "meta": META})
        item.update(id=record["id"], result="saved_requires_readback")
        save(audit)
        _, saved = client.request("GET", f'/wp-json/wp/v2/pages/{record["id"]}?context=edit')
        verify_record(saved, game, source)
        if saved["content"]["raw"] != original_content:
            raise RuntimeError("Page content changed during metadata save")
        if saved.get("status") != "publish":
            client.request("POST", f'/wp-json/wp/v2/pages/{record["id"]}', {"status": "publish"})
        _, saved = client.request("GET", f'/wp-json/wp/v2/pages/{record["id"]}?context=edit')
        verify_record(saved, game, source)
        if saved.get("status") != "publish" or saved.get("link") != f'{ORIGIN}/{game["slug"]}/':
            raise RuntimeError("Publication status or link differs")
        item.update(result="published_requires_public_readback")
        save(audit)
        item.update(verify_public(client, game, source), result="published_verified")
        save(audit)
    _, home_after = client.request_anonymous_html("/")
    if ("noindex" in Document(home_after).robots) != home_noindex:
        raise RuntimeError("Homepage indexing changed unexpectedly")
    audit.update(result="all_three_verified", homepage_indexing_preserved=True)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--publish", action="store_true")
    args = parser.parse_args()
    audit = {"task": "HAD-807", "utc": datetime.now(timezone.utc).isoformat(),
             "apply": args.publish, "pages": [], "result": "preflight"}
    try:
        if os.environ.get("WP_BASE_URL", "").rstrip("/") != ORIGIN:
            raise RuntimeError("Expected canonical production origin")
        if not os.environ.get("WP_DEPLOY_USER") or not os.environ.get("WP_APP_PASSWORD"):
            raise RuntimeError("Existing deployment credentials unavailable")
        client = transport.Client(ORIGIN, os.environ["WP_DEPLOY_USER"], os.environ["WP_APP_PASSWORD"], timeout=30)
        run(client, args.publish, audit)
    except Exception as error:
        audit.update(error_type=type(error).__name__, error=str(error)[:350])
        save(audit)
        print(json.dumps(audit, ensure_ascii=True))
        return 1
    save(audit)
    print(json.dumps(audit, ensure_ascii=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
