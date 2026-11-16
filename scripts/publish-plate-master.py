"""Publish only the reviewed Plate Master WordPress page. No plugin mutation."""
from __future__ import annotations

import argparse
import hashlib
import html
import importlib.util
import json
import os
import re
import sys
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "games/plate-master/index.html"
ORIGIN = "https://complete99.co.il"
PUBLIC_URL = ORIGIN + "/game/"
MARKER = "c99-plate-master-v1"
TITLE = "מאסטר הדלפק: המשחק של סביח קומפלט, אבן גבירול 99"
AUDIT = ROOT / "game-publish-audit.json"

spec = importlib.util.spec_from_file_location("game_wp_transport", ROOT / "scripts/deploy-wordpress.py")
transport = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = transport
spec.loader.exec_module(transport)


def digest(value: str) -> str:
    return hashlib.sha256(value.encode("utf-8")).hexdigest()


class GameFrame(HTMLParser):
    def __init__(self, text: str):
        super().__init__(convert_charrefs=True)
        self.frames = []
        self.feed(text)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "iframe" and attrs.get("id") == MARKER:
            self.frames.append(attrs)


def build_content(source: str) -> str:
    if len(source.encode("utf-8")) > 100_000:
        raise ValueError("Unexpectedly large game source")
    if 'https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete' not in source:
        raise ValueError("Canonical restaurant link is missing")
    return ('<!-- wp:html -->\n'
            '<div id="c99-game-shell" dir="rtl" style="max-width:640px;margin:0 auto;padding:0 8px">\n'
            f'<iframe id="{MARKER}" title="מאסטר הדלפק, שלושה אתגרי הרכבת מנות" '
            'style="display:block;width:100%;height:850px;max-height:100dvh;min-height:800px;border:0" '
            'allow="clipboard-write" '
            f'srcdoc="{html.escape(source, quote=True)}"></iframe>\n'
            '<p style="text-align:center">סביח, קובה סלק ואיג׳ה. שלושה אתגרים בדלפק של אבן גבירול 99.</p>\n'
            '</div>\n<!-- /wp:html -->')


def verify_frame(content: str, source: str) -> None:
    frames = GameFrame(content).frames
    if len(frames) != 1 or frames[0].get("srcdoc") != source:
        raise RuntimeError("Saved game frame differs from the reviewed source")


def verify_record(record: dict, source: str) -> None:
    if record.get("slug") != "game" or record.get("parent", 0) != 0:
        raise RuntimeError("WordPress did not retain the exact /game/ slug")
    if record.get("type") != "page":
        raise RuntimeError("Unexpected WordPress content type")
    verify_frame(record.get("content", {}).get("raw", ""), source)


def owned_record(records: list) -> dict | None:
    if len(records) > 1:
        raise RuntimeError("Multiple /game/ records require review; nothing overwritten")
    if not records:
        return None
    record = records[0]
    if len(GameFrame(record.get("content", {}).get("raw", "")).frames) != 1:
        raise RuntimeError("An unrelated /game/ page exists; nothing overwritten")
    return record


def save_audit(audit: dict) -> None:
    AUDIT.write_text(json.dumps(audit, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def publish(client, source: str, apply: bool, audit: dict) -> None:
    audit["site_identity"] = transport.verify_rest_identity(client)
    _, identity = client.request("GET", "/wp-json/wp/v2/users/me?context=edit&_fields=id,capabilities")
    caps = identity.get("capabilities", {})
    audit["required_capabilities"] = {key: bool(caps.get(key)) for key in ("edit_pages", "publish_pages", "unfiltered_html")}
    if not all(audit["required_capabilities"].values()):
        raise RuntimeError("Existing WordPress identity lacks a required page capability")
    query = "/wp-json/wp/v2/pages?context=edit&slug=game&status=publish,draft,pending,private,future&per_page=10"
    _, records = client.request("GET", query)
    if not isinstance(records, list):
        raise RuntimeError("Unexpected pages response")
    record = owned_record(records)
    audit["existing_page_id"] = record["id"] if record else None
    if not record:
        status, _ = client.request_anonymous_html("/game/", expected=(200, 404))
        if status != 404:
            raise RuntimeError("Public /game/ is already occupied outside the page record")
    if not apply:
        audit["result"] = "ready_no_mutation"
        return
    content = build_content(source)
    if record:
        # Back up only this owned page. The audit never contains credentials.
        (ROOT / "game-page-before.json").write_text(json.dumps({key: record.get(key) for key in
            ("id", "slug", "status", "link", "title", "content", "modified_gmt")}, ensure_ascii=False, indent=2), encoding="utf-8")
        if record["content"]["raw"] != content:
            if record.get("status") == "publish":
                raise RuntimeError("A different published game exists; review an in-place update first")
            _, record = client.request("POST", f'/wp-json/wp/v2/pages/{record["id"]}', {"content": content, "title": TITLE})
    else:
        _, record = client.request("POST", "/wp-json/wp/v2/pages", {
            "slug": "game", "title": TITLE, "content": content, "status": "draft", "parent": 0,
            "excerpt": "שלושה אתגרי הרכבת מנות: סביח, קובה סלק ואיג׳ה. סביח קומפלט, אבן גבירול 99."
        })
    audit["page_id"] = record["id"]
    audit["result"] = "saved_requires_readback"
    save_audit(audit)
    _, saved = client.request("GET", f'/wp-json/wp/v2/pages/{record["id"]}?context=edit')
    verify_record(saved, source)
    audit["source_readback_verified"] = True
    if saved.get("status") != "publish":
        client.request("POST", f'/wp-json/wp/v2/pages/{record["id"]}', {"status": "publish"})
    _, saved = client.request("GET", f'/wp-json/wp/v2/pages/{record["id"]}?context=edit')
    verify_record(saved, source)
    if saved.get("status") != "publish" or saved.get("link") != PUBLIC_URL:
        raise RuntimeError("Publication status or permalink differs")
    audit["result"] = "published_requires_public_readback"
    save_audit(audit)
    _, public_html = client.request_anonymous_html("/game/")
    verify_frame(public_html, source)
    audit.update(result="published_verified", public_url=PUBLIC_URL,
                 public_html_sha256=digest(public_html), public_source_verified=True)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--publish", action="store_true")
    parser.add_argument("--build-content", type=Path)
    args = parser.parse_args()
    source = SOURCE.read_text(encoding="utf-8")
    if args.build_content:
        args.build_content.write_text(build_content(source), encoding="utf-8")
        print("Prepared standalone WordPress game block")
        return 0
    audit = {"task": "HAD-762", "utc": datetime.now(timezone.utc).isoformat(),
             "source_sha256": digest(source), "public_url": PUBLIC_URL, "apply": args.publish,
             "result": "preflight", "scope": "one WordPress game page; no campaign or plugin changes"}
    try:
        if os.environ.get("WP_BASE_URL", "").rstrip("/") != ORIGIN:
            raise RuntimeError("Expected the existing canonical Complete99 production origin")
        if not os.environ.get("WP_DEPLOY_USER") or not os.environ.get("WP_APP_PASSWORD"):
            raise RuntimeError("Existing deployment credentials unavailable")
        client = transport.Client(ORIGIN, os.environ["WP_DEPLOY_USER"], os.environ["WP_APP_PASSWORD"], timeout=30)
        publish(client, source, args.publish, audit)
    except Exception as error:
        audit["error_type"] = type(error).__name__
        # Sanitized transport errors expose only route and status, never credentials.
        audit["error"] = str(error)[:350]
        save_audit(audit)
        print(json.dumps(audit, ensure_ascii=True))
        return 1
    save_audit(audit)
    print(json.dumps(audit, ensure_ascii=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
