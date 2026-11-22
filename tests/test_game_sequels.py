"""Game-page deployment boundaries; mocks simulate content loss and bad robots."""
import copy
import html
import importlib.util
from pathlib import Path

import pytest

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("sequels", ROOT / "scripts/publish-game-sequels.py")
g = importlib.util.module_from_spec(spec)
spec.loader.exec_module(g)


def test_robots_must_be_in_parent_head_not_in_iframe_or_body():
    assert "noindex" not in g.Document('<head></head><body><meta name="robots" content="noindex"></body>').robots
    nested = html.escape('<head><meta name="robots" content="noindex"></head>', quote=True)
    assert "noindex" not in g.Document(f'<head></head><iframe srcdoc="{nested}"></iframe>').robots
    assert "noindex" in g.Document('<head><meta name="robots" content="noindex, follow"></head>').robots


def test_sources_survive_attribute_escaping():
    for game in g.GAMES:
        source = (ROOT / game["source"]).read_text(encoding="utf-8")
        assert g.frame_source(g.build_content(game, source), game["marker"]) == source


class Client:
    def __init__(self, *, strip=False, wrong_meta=False, bad_head=False, occupied=False):
        self.strip, self.wrong_meta, self.bad_head, self.occupied = strip, wrong_meta, bad_head, occupied
        self.calls = []
        source = (ROOT / "games/plate-master/index.html").read_text(encoding="utf-8")
        content = f'<iframe id="c99-plate-master-v1" srcdoc="{html.escape(source, quote=True)}"></iframe>'
        self.records = {970: {"id": 970, "slug": "game", "type": "page", "parent": 0, "status": "publish",
                        "link": g.ORIGIN + "/game/", "content": {"raw": content}, "meta": {}}}

    def request(self, method, path, payload=None):
        self.calls.append((method, path, copy.deepcopy(payload)))
        if "users/me" in path:
            return 200, {"capabilities": {k: True for k in ("edit_pages", "publish_pages", "unfiltered_html")}}
        if "slug=" in path:
            slug = path.split("slug=")[1].split("&")[0]
            return 200, copy.deepcopy([r for r in self.records.values() if r["slug"] == slug])
        if method == "POST" and path.endswith("/pages"):
            ident = max(self.records) + 1
            self.records[ident] = {**copy.deepcopy(payload), "id": ident, "type": "page",
                                  "link": g.ORIGIN + "/" + payload["slug"] + "/",
                                  "content": {"raw": "stripped" if self.strip else payload["content"]}}
        else:
            ident = int(path.split("/pages/")[1].split("?")[0])
            if method == "POST":
                self.records[ident].update(copy.deepcopy(payload))
        if self.wrong_meta:
            self.records[ident]["meta"] = {}
        return 200, copy.deepcopy(self.records[ident])

    def request_anonymous_html(self, path, expected=(200,)):
        if path == "/":
            return 200, '<head><meta name="robots" content="max-image-preview:large"></head>'
        row = next((r for r in self.records.values() if path == "/" + r["slug"] + "/"), None)
        if row is None:
            return (200, "unrelated route") if self.occupied else (404, "missing")
        robots = "noindex,follow" if row.get("meta") == g.META and not self.bad_head else "index"
        return 200, f'<head><meta name="robots" content="{robots}"></head>' + row["content"]["raw"]


@pytest.fixture(autouse=True)
def isolated_audit(monkeypatch, tmp_path):
    monkeypatch.setattr(g, "AUDIT_DIR", tmp_path)
    monkeypatch.setattr(g.transport, "verify_rest_identity", lambda client: {"home": g.ORIGIN})


def test_preflight_has_no_mutations():
    client, audit = Client(), {"pages": []}
    g.run(client, False, audit)
    assert audit["result"] == "ready_no_mutation"
    assert all(c[0] == "GET" for c in client.calls)


def test_publish_drafts_readback_then_publish_and_preserves_old_content():
    client, audit = Client(), {"pages": []}
    old = client.records[970]["content"]["raw"]
    g.run(client, True, audit)
    assert audit["result"] == "all_three_verified"
    assert len(audit["pages"]) == 3
    assert client.records[970]["content"]["raw"] == old
    for i, (method, path, payload) in enumerate(client.calls):
        if method == "POST" and payload.get("status") == "publish":
            assert client.calls[i - 1][0] == "GET"
    old_writes = [p for m, path, p in client.calls if m == "POST" and path.endswith("/970")]
    assert old_writes == [{"meta": g.META}]


@pytest.mark.parametrize("failure", ["strip", "wrong_meta", "bad_head"])
def test_failed_readback_never_claims_all_verified(failure):
    client, audit = Client(**{failure: True}), {"pages": []}
    with pytest.raises(RuntimeError):
        g.run(client, True, audit)
    assert audit.get("result") != "all_three_verified"
    if failure == "strip":
        assert client.records[971]["status"] == "draft"


def test_occupied_slug_blocks_all_writes():
    client = Client(occupied=True)
    with pytest.raises(RuntimeError, match="occupied"):
        g.run(client, True, {"pages": []})
    assert all(c[0] == "GET" for c in client.calls)


def test_changed_legacy_source_blocks_all_writes():
    client = Client()
    client.records[970]["content"]["raw"] = client.records[970]["content"]["raw"].replace('שחק שוב', 'modified')
    with pytest.raises(RuntimeError, match="Legacy source"):
        g.run(client, True, {"pages": []})
    assert all(c[0] == "GET" for c in client.calls)


def test_second_run_is_idempotent():
    client = Client()
    g.run(client, True, {"pages": []})
    g.run(client, True, {"pages": []})
    assert len(client.records) == 3
    assert all(row["status"] == "publish" for row in client.records.values())
