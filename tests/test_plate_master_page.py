"""Publication boundary and iframe fidelity checks for the one-page game."""
import importlib.util
from pathlib import Path

import pytest

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("plate_master", ROOT / "scripts/publish-plate-master.py")
game = importlib.util.module_from_spec(spec)
spec.loader.exec_module(game)


def test_actual_source_survives_wordpress_attribute_escaping():
    source = game.SOURCE.read_text(encoding="utf-8")
    content = game.build_content(source)
    game.verify_frame(content, source)
    assert content.count('<iframe ') == 1
    assert '<script>' not in content  # Script belongs only to the isolated document.


def test_unrelated_page_and_duplicate_slug_are_never_replaced():
    with pytest.raises(RuntimeError, match="unrelated"):
        game.owned_record([{"id": 22, "content": {"raw": "Existing unrelated page"}}])
    with pytest.raises(RuntimeError, match="Multiple"):
        game.owned_record([{}, {}])


def test_stripped_or_modified_game_prevents_publication():
    source = game.SOURCE.read_text(encoding="utf-8")
    with pytest.raises(RuntimeError, match="differs"):
        game.verify_frame('<iframe id="c99-plate-master-v1"></iframe>', source)
    with pytest.raises(RuntimeError, match="differs"):
        game.verify_frame(game.build_content(source).replace('שחק שוב', 'changed'), source)


def test_unexpected_slug_is_rejected_before_publish():
    source = game.SOURCE.read_text(encoding="utf-8")
    with pytest.raises(RuntimeError, match="slug"):
        game.verify_record({"slug": "game-2", "parent": 0, "type": "page", "content": {"raw": game.build_content(source)}}, source)


class PageClient:
    def __init__(self, source, strip_script=False, capabilities=True):
        self.source, self.strip_script, self.capabilities = source, strip_script, capabilities
        self.calls = []
        self.record = None

    def request(self, method, path, payload=None):
        self.calls.append((method, path, payload))
        if "users/me" in path:
            return 200, {"capabilities": {key: self.capabilities for key in ("edit_pages", "publish_pages", "unfiltered_html")}}
        if "slug=game" in path:
            return 200, []
        if method == "POST" and path.endswith("/pages"):
            self.record = {**payload, "id": 55, "type": "page", "link": game.PUBLIC_URL,
                           "content": {"raw": "stripped" if self.strip_script else payload["content"]}}
        elif method == "POST":
            self.record.update(payload)
        return 200, self.record

    def request_anonymous_html(self, path, expected=(200,)):
        if self.record is None:
            return 404, "missing"
        return 200, self.record["content"]["raw"]


@pytest.fixture
def client_source(monkeypatch):
    monkeypatch.setattr(game.transport, "verify_rest_identity", lambda client: {"home": game.ORIGIN, "url": game.ORIGIN})
    monkeypatch.setattr(game, "save_audit", lambda audit: None)
    return game.SOURCE.read_text(encoding="utf-8")


def test_read_only_mode_never_posts(client_source):
    client, audit = PageClient(client_source), {}
    game.publish(client, client_source, False, audit)
    assert all(method == "GET" for method, _, _ in client.calls)
    assert audit["result"] == "ready_no_mutation"


def test_draft_is_read_back_before_publish_and_public_frame_verified(client_source):
    client, audit = PageClient(client_source), {}
    game.publish(client, client_source, True, audit)
    writes = [(i, payload) for i, (method, _, payload) in enumerate(client.calls) if method == "POST"]
    assert [p["status"] for _, p in writes] == ["draft", "publish"]
    assert client.calls[writes[0][0] + 1][0] == "GET"
    assert audit["result"] == "published_verified"


def test_wordpress_stripping_stops_at_draft(client_source):
    client = PageClient(client_source, strip_script=True)
    with pytest.raises(RuntimeError, match="differs"):
        game.publish(client, client_source, True, {})
    assert client.record["status"] == "draft"
    assert sum(method == "POST" for method, _, _ in client.calls) == 1


def test_insufficient_existing_access_never_writes(client_source):
    client = PageClient(client_source, capabilities=False)
    with pytest.raises(RuntimeError, match="capability"):
        game.publish(client, client_source, True, {})
    assert all(method == "GET" for method, _, _ in client.calls)
