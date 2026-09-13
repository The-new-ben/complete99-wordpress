"""Homepage-only contracts; these are not substitutes for Chrome release tests."""
from pathlib import Path
import re
import subprocess
from html.parser import HTMLParser

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"


def home_source():
    source = (PLUGIN / "includes/class-complete99-consumer.php").read_text(encoding="utf-8")
    return source.split("private static function render_home", 1)[1].split("private static function render_menu_preview", 1)[0]


def test_existing_conversion_and_editorial_owners_are_retained():
    source = home_source()
    for owner in ("dishes", "proposal", "contact", "ingredients", "knowledge", "about", "traditions"):
        assert f"self::route( '{owner}', $lang )" in source
    assert "Complete99_Commerce::order_url( $lang )" in source
    assert "render_menu_preview( $lang, 6 )" in source
    assert "render_group_order_teaser( $lang )" in source
    assert "render_pantry_teaser( $lang )" in source


def test_server_rendered_choices_do_not_create_parameter_routes():
    source = home_source()
    choices = source.split('<nav class="c99-home-choices', 1)[1].split("</nav>", 1)[0]
    assert choices.count("<a href=") == 3
    assert "<button" not in choices
    assert "add_query_arg" not in source
    assert "?utm" not in source
    assert "wp_redirect" not in source
    assert source.count("<h1 ") == 1


def test_public_home_copy_has_no_internal_review_filler():
    source = home_source()
    for phrase in ("מדריכים שמתפרסמים רק אחרי", "בלי לנפח הבטחות", "—", "בלי להסתיר"):
        assert phrase not in source
    assert 'data-c99-home-experience="food-discovery-v1"' in source
    assert "c99-food-house-spread-hero-2021-wp-v01" in source


def test_home_styles_are_scoped_and_have_touch_and_motion_rules():
    css = (PLUGIN / "assets/css/consumer.css").read_text(encoding="utf-8")
    layer = css.split("/* Homepage discovery layer.", 1)[1]
    assert "min-height: 44px" in layer
    assert "prefers-reduced-motion: reduce" in layer
    assert "outline: 3px solid" in layer
    assert "grid-template-columns: minmax(0, 1fr)" in layer
    # Rules must use the homepage wrapper or its new navigation component.
    for selector in re.findall(r"([^{}]+)\{", layer):
        if "@media" in selector:
            continue
        assert "c99-food-portal" in selector or "c99-home-choices" in selector


def test_actual_php_home_renders_both_languages_with_clean_navigation():
    class Page(HTMLParser):
        def __init__(self):
            super().__init__()
            self.h1_count = 0
            self.links = []
            self.images = []
        def handle_starttag(self, tag, attrs):
            attrs = dict(attrs)
            if tag == 'h1':
                self.h1_count += 1
            if tag == 'a':
                self.links.append(attrs.get('href', ''))
            if tag == 'img':
                self.images.append(attrs)

    for lang in ('he', 'en'):
        result = subprocess.run(['php', str(ROOT / 'tests/fixtures/food-home-render.php'), lang],
                                capture_output=True, text=True, encoding='utf-8', check=True)
        assert result.stderr == ''
        page = Page()
        page.feed(result.stdout)
        assert page.h1_count == 1
        assert len(page.images) >= 10
        assert sum(image.get('fetchpriority') == 'high' for image in page.images) == 1
        assert all(image.get('alt') and image.get('width') and image.get('height') for image in page.images)
        assert all('?' not in url for url in page.links)
        prefix = 'https://complete99.co.il/' + ('en/' if lang == 'en' else '')
        assert prefix + 'request-proposal/' in page.links
        assert prefix + 'menu/sabich/' in page.links
        assert prefix + 'ingredients/' in page.links
