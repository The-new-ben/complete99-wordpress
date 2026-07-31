from __future__ import annotations

import json
import re
import shutil
import subprocess
import unittest
from html.parser import HTMLParser
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
TEMPLATES = (
    PLUGIN / "templates" / "live-dish.php",
    PLUGIN / "templates" / "public-shell.php",
    PLUGIN / "templates" / "not-found.php",
    PLUGIN / "templates" / "commerce-shell.php",
)


class DocumentHeadParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=False)
        self.in_head = False
        self.protected_depth = 0
        self.current_title: list[str] | None = None
        self.titles: list[str] = []
        self.viewport_count = 0

    def handle_starttag(
        self, tag: str, attrs: list[tuple[str, str | None]]
    ) -> None:
        tag = tag.casefold()
        if tag == "head":
            self.in_head = True
            return
        if not self.in_head:
            return
        if tag in {"math", "svg", "template"}:
            self.protected_depth += 1
            return
        if self.protected_depth:
            return
        if tag == "title":
            self.current_title = []
        elif tag == "meta":
            values = {
                name.casefold(): (value or "")
                for name, value in attrs
            }
            if values.get("name", "").strip().casefold() == "viewport":
                self.viewport_count += 1

    def handle_startendtag(
        self, tag: str, attrs: list[tuple[str, str | None]]
    ) -> None:
        if tag.casefold() == "template":
            self.handle_starttag(tag, attrs)
            return
        self.handle_starttag(tag, attrs)
        self.handle_endtag(tag)

    def handle_endtag(self, tag: str) -> None:
        tag = tag.casefold()
        if tag == "head":
            self.in_head = False
            return
        if not self.in_head:
            return
        if tag in {"math", "svg", "template"}:
            self.protected_depth = max(0, self.protected_depth - 1)
            return
        if tag == "title" and self.current_title is not None:
            self.titles.append("".join(self.current_title))
            self.current_title = None

    def handle_data(self, data: str) -> None:
        if self.current_title is not None:
            self.current_title.append(data)

    def handle_entityref(self, name: str) -> None:
        if self.current_title is not None:
            self.current_title.append(f"&{name};")

    def handle_charref(self, name: str) -> None:
        if self.current_title is not None:
            self.current_title.append(f"&#{name};")


class Complete99DocumentTitleContracts(unittest.TestCase):
    def test_every_plugin_owned_shell_uses_the_shared_document_head_renderer(
        self,
    ) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        renderer = frontend.split(
            "public static function render_document_head", 1
        )[1].split("public static function robots", 1)[0]

        self.assertIn(
            "remove_action( 'wp_head', '_wp_render_title_tag', 1 )", renderer
        )
        self.assertIn("wp_get_document_title()", renderer)
        self.assertIn("esc_html( $title )", renderer)
        self.assertIn("$title = 'Complete99';", renderer)
        self.assertIn("ob_start();", renderer)
        self.assertIn("wp_head();", renderer)
        self.assertIn("strip_document_head_duplicates( $head )", renderer)
        self.assertIn('name="viewport"', renderer)
        self.assertIn("private static function find_html_tag_end", renderer)
        self.assertIn("private static function html_tag_attribute", renderer)

        call = "Complete99_Frontend::render_document_head();"
        for template in TEMPLATES:
            source = template.read_text(encoding="utf-8")
            self.assertEqual(1, source.count(call), template)
            self.assertNotIn("wp_head();", source, template)
            self.assertNotIn('name="viewport"', source, template)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for template rendering")
    def test_all_shells_emit_one_canonical_head_and_preserve_unrelated_markup(
        self,
    ) -> None:
        php = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', __PLUGIN_DIR__);
define('COMPLETE99_PLATFORM_VERSION', 'test');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-test');
define('C99_LIVE_DISH_TEMPLATE', __LIVE_DISH_TEMPLATE__);
define('C99_PUBLIC_TEMPLATE', __PUBLIC_TEMPLATE__);
define('C99_NOT_FOUND_TEMPLATE', __NOT_FOUND_TEMPLATE__);
define('C99_COMMERCE_TEMPLATE', __COMMERCE_TEMPLATE__);

$GLOBALS['c99_query'] = array();
$GLOBALS['c99_is_404'] = false;
$GLOBALS['c99_core_title_active'] = true;
$GLOBALS['c99_head_calls'] = 0;
$GLOBALS['c99_document_title_override'] = '';
$GLOBALS['c99_is_singular'] = false;
$GLOBALS['c99_transaction'] = false;
$GLOBALS['c99_language'] = 'he';
$GLOBALS['c99_post'] = null;
$GLOBALS['c99_items'] = array(
    array(
        'id' => 'menu-reference-sabich',
        'slug' => 'sabich',
        'name_he' => 'הסביח של 99 & מיוחד',
        'name_en' => 'The 99 Sabich & Special',
        'description_he' => 'תיאור',
        'description_en' => 'Description',
        'sort' => 10,
    ),
);

function remove_action($hook, $callback, $priority = 10) {
    if ('wp_head' === $hook && '_wp_render_title_tag' === $callback && 1 === $priority) {
        $GLOBALS['c99_core_title_active'] = false;
    }
    return true;
}
function wp_get_document_title() {
    if ('' !== $GLOBALS['c99_document_title_override']) {
        return $GLOBALS['c99_document_title_override'];
    }
    return Complete99_Frontend::document_title('');
}
function wp_strip_all_tags($value) {
    return strip_tags((string) $value);
}
function esc_html($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function esc_attr($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function sanitize_title($value) {
    return strtolower(trim((string) $value));
}
function sanitize_key($value) {
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}
function get_query_var($key, $default = '') {
    return array_key_exists($key, $GLOBALS['c99_query'])
        ? $GLOBALS['c99_query'][$key]
        : $default;
}
function wp_unslash($value) {
    return $value;
}
function wp_parse_url($url, $component = -1) {
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}
function home_url($path = '') {
    return 'https://example.test/' . ltrim((string) $path, '/');
}
function get_option($key, $default = false) {
    return $default;
}
function is_404() {
    return $GLOBALS['c99_is_404'];
}
function is_singular($post_type = '') {
    return $GLOBALS['c99_is_singular'];
}
function get_queried_object_id() {
    return $GLOBALS['c99_is_singular'] ? $GLOBALS['c99_post']->ID : 0;
}
function get_queried_object() {
    return $GLOBALS['c99_post'];
}
function bloginfo($key) {
    echo 'UTF-8';
}
function body_class() {
    echo 'class="complete99-test"';
}
function wp_body_open() {}
function wp_footer() {}
function switch_to_locale($locale) {
    return true;
}
function restore_previous_locale() {
    return true;
}
function wp_head() {
    ++$GLOBALS['c99_head_calls'];
    if ($GLOBALS['c99_core_title_active']) {
        echo '<title>Core duplicate</title>';
    }
    echo '<TITLE data-note="1 > 0">SEO duplicate</TITLE data-end="1">';
    echo '<meta content="a > b" NAME="VIEWPORT">';
    echo "<meta data-name=\"viewport\" name=\"description\" content=\"keep\">";
    echo '<meta name="robots" content="index, follow">';
    echo '<link rel="canonical" href="https://example.test/kept/">';
    echo '<script id="c99-script-sentinel">const invalid="</ script>";window.c99Literal="<title>script literal</title><meta name=\"viewport\">";</script data-end="1">';
    echo '<title data-after-script="1">After-script duplicate</title data-end="1">';
    echo '<meta data-after-script="1" name="viewport">';
    echo '<style id="c99-style-sentinel">.c99::before{content:"<title>style literal</title>"}</style>';
    echo '<!-- <title>comment literal</title><meta name="viewport"> -->';
    echo '<svg id="c99-svg-sentinel"><title>Accessible icon title</title></svg>';
    echo '<template/><title>Template title</title></template>';
    echo '< title id="invalid-title">Invalid title-shaped text</ title>';
    echo '< meta name="viewport" id="invalid-meta">';
    echo '<title.foo>Custom title element</title.foo>';
    echo '<meta.foo name="viewport">';
    echo '<title data-unclosed="1">Unclosed duplicate';
}

class WP_Post {
    public $ID;
    public $post_title;
    public function __construct($id, $title) {
        $this->ID = $id;
        $this->post_title = $title;
    }
}

class Complete99_Content {
    public static function language_for_post($post_id) {
        return $GLOBALS['c99_language'];
    }
    public static function find_translation_post_id($key, $lang, $strict = false) {
        return 99;
    }
    public static function is_complete99_post($post_id) {
        return true;
    }
}

class Complete99_Commerce {
    public static function transaction_language() {
        return $GLOBALS['c99_language'];
    }
    public static function transaction_page_type() {
        return 'cart';
    }
    public static function is_transaction_page() {
        return $GLOBALS['c99_transaction'];
    }
    public static function is_ready() {
        return true;
    }
    public static function can_preview_commerce() {
        return false;
    }
    public static function can_access_customer_continuity() {
        return false;
    }
}

class Complete99_REST {
    public static function public_indexable_items() {
        return $GLOBALS['c99_items'];
    }
    public static function public_indexable_item_by_slug($slug) {
        foreach ($GLOBALS['c99_items'] as $item) {
            if (($item['slug'] ?? '') === $slug) {
                return $item;
            }
        }
        return array();
    }
}

class Complete99_Consumer {
    public static function menu_items() {
        return $GLOBALS['c99_items'];
    }
    public static function render_live_dish_page($dish, $lang) {}
    public static function render_not_found_page($lang) {}
    public static function render_site_not_found_page($lang) {}
    public static function render_header($post_id, $lang) {}
    public static function render_current($post) {}
    public static function render_transaction_page($type, $lang) {}
    public static function render_footer($lang) {}
}

require __FRONTEND__;

function c99_render_document(
    $language,
    $slug,
    $not_found,
    $request_uri = '/'
) {
    $GLOBALS['c99_query'] = $slug
        ? array(
            'complete99_live_dish' => $slug,
            'complete99_live_lang' => $language,
        )
        : array();
    $_SERVER['REQUEST_URI'] = $request_uri;
    $GLOBALS['c99_is_404'] = $not_found;
    $GLOBALS['c99_core_title_active'] = true;
    $GLOBALS['c99_document_title_override'] = '';
    $GLOBALS['c99_is_singular'] = false;
    $GLOBALS['c99_transaction'] = false;
    $GLOBALS['c99_language'] = $language;
    $GLOBALS['c99_post'] = null;

    ob_start();
    if ($not_found) {
        include C99_NOT_FOUND_TEMPLATE;
    } else {
        $complete99_live_dish = $GLOBALS['c99_items'][0];
        $complete99_live_lang = $language;
        include C99_LIVE_DISH_TEMPLATE;
    }
    return ob_get_clean();
}

function c99_render_shell($shell, $language) {
    $GLOBALS['c99_query'] = array();
    $GLOBALS['c99_is_404'] = false;
    $GLOBALS['c99_core_title_active'] = true;
    $GLOBALS['c99_document_title_override'] = (
        'he' === $language ? 'כותרת ציבורית & מיוחדת' : 'Public & Special'
    ) . ' | Complete99';
    $GLOBALS['c99_is_singular'] = 'public' === $shell;
    $GLOBALS['c99_transaction'] = 'commerce' === $shell;
    $GLOBALS['c99_language'] = $language;
    $GLOBALS['c99_post'] = new WP_Post(77, 'Public page');

    $template = 'public' === $shell ? C99_PUBLIC_TEMPLATE : C99_COMMERCE_TEMPLATE;
    ob_start();
    include $template;
    return ob_get_clean();
}

$documents = array(
    'he_live' => c99_render_document('he', 'sabich', false),
    'en_live' => c99_render_document('en', 'sabich', false),
    'he_404' => c99_render_document('he', 'missing-dish', true),
    'en_404' => c99_render_document('en', 'missing-dish', true),
    'he_generic_404' => c99_render_document('he', '', true, '/missing-page/'),
    'en_generic_404' => c99_render_document('en', '', true, '/en/missing-page/'),
    'he_public' => c99_render_shell('public', 'he'),
    'en_public' => c99_render_shell('public', 'en'),
    'he_commerce' => c99_render_shell('commerce', 'he'),
    'en_commerce' => c99_render_shell('commerce', 'en'),
);
$result = array();
foreach ($documents as $key => $html) {
    preg_match('#<head>(.*?)</head>#s', $html, $head_match);
    $head = $head_match[1];
    preg_match('#<title>(.*?)</title>#s', $head, $title_match);
    $canonical_title = $title_match[1];
    $result[$key] = array(
        'head' => $head,
        'title' => $canonical_title,
        'title_count' => substr_count(
            $head,
            '<title>' . $canonical_title . '</title>'
        ),
        'viewport_count' => substr_count(
            $head,
            '<meta name="viewport" content="width=device-width, initial-scale=1" />'
        ),
        'core_duplicate_removed' => false === strpos($head, 'Core duplicate'),
        'seo_duplicate_removed' => false === strpos($head, 'SEO duplicate'),
        'competing_viewport_removed' => false === strpos($head, 'content="a > b"'),
        'after_script_title_removed' => false === strpos(
            $head,
            'After-script duplicate'
        ),
        'after_script_viewport_removed' => false === strpos(
            $head,
            'data-after-script'
        ),
        'description_preserved' => false !== strpos(
            $head,
            '<meta data-name="viewport" name="description" content="keep">'
        ),
        'robots_preserved' => false !== strpos(
            $head,
            '<meta name="robots" content="index, follow">'
        ),
        'canonical_preserved' => false !== strpos(
            $head,
            '<link rel="canonical" href="https://example.test/kept/">'
        ),
        'script_preserved' => false !== strpos(
            $head,
            '<script id="c99-script-sentinel">const invalid="</ script>";window.c99Literal="<title>script literal</title><meta name=\"viewport\">";</script data-end="1">'
        ),
        'style_preserved' => false !== strpos(
            $head,
            '<style id="c99-style-sentinel">.c99::before{content:"<title>style literal</title>"}</style>'
        ),
        'comment_preserved' => false !== strpos(
            $head,
            '<!-- <title>comment literal</title><meta name="viewport"> -->'
        ),
        'svg_title_preserved' => false !== strpos(
            $head,
            '<svg id="c99-svg-sentinel"><title>Accessible icon title</title></svg>'
        ),
        'template_title_preserved' => false !== strpos(
            $head,
            '<template/><title>Template title</title></template>'
        ),
        'invalid_tag_text_preserved' => false !== strpos(
            $head,
            '< title id="invalid-title">Invalid title-shaped text</ title>'
        ) && false !== strpos(
            $head,
            '< meta name="viewport" id="invalid-meta">'
        ),
        'custom_elements_preserved' => false !== strpos(
            $head,
            '<title.foo>Custom title element</title.foo><meta.foo name="viewport">'
        ),
        'unclosed_title_removed' => false === strpos(
            $head,
            'Unclosed duplicate'
        ),
    );
}
$result['_head_calls'] = $GLOBALS['c99_head_calls'];
echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
"""
        replacements = {
            "__PLUGIN_DIR__": json.dumps(f"{PLUGIN.as_posix()}/"),
            "__LIVE_DISH_TEMPLATE__": json.dumps(TEMPLATES[0].as_posix()),
            "__PUBLIC_TEMPLATE__": json.dumps(TEMPLATES[1].as_posix()),
            "__NOT_FOUND_TEMPLATE__": json.dumps(TEMPLATES[2].as_posix()),
            "__COMMERCE_TEMPLATE__": json.dumps(TEMPLATES[3].as_posix()),
            "__FRONTEND__": json.dumps(FRONTEND.as_posix()),
        }
        for marker, replacement in replacements.items():
            php = php.replace(marker, replacement)

        completed = subprocess.run(
            ["php", "-r", php],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        outcomes = json.loads(completed.stdout)

        self.assertEqual(
            "הסביח של 99 &amp; מיוחד | Complete99",
            outcomes["he_live"]["title"],
        )
        self.assertEqual(
            "The 99 Sabich &amp; Special | Complete99",
            outcomes["en_live"]["title"],
        )
        self.assertEqual(
            "המנה לא נמצאה | קומפלט 99", outcomes["he_404"]["title"]
        )
        self.assertEqual(
            "Dish not found | Complete99", outcomes["en_404"]["title"]
        )
        self.assertEqual(
            "העמוד לא נמצא | קומפלט 99",
            outcomes["he_generic_404"]["title"],
        )
        self.assertEqual(
            "Page not found | Complete99",
            outcomes["en_generic_404"]["title"],
        )
        for key in ("he_public", "he_commerce"):
            self.assertEqual(
                "כותרת ציבורית &amp; מיוחדת | Complete99",
                outcomes[key]["title"],
            )
        for key in ("en_public", "en_commerce"):
            self.assertEqual(
                "Public &amp; Special | Complete99",
                outcomes[key]["title"],
            )
        self.assertEqual(len(outcomes) - 1, outcomes["_head_calls"])
        for key, outcome in outcomes.items():
            if key.startswith("_"):
                continue
            parser = DocumentHeadParser()
            semantic_head = re.sub(
                r"<script id=\"c99-script-sentinel\">.*?</script data-end=\"1\">",
                "",
                outcome["head"],
                flags=re.IGNORECASE | re.DOTALL,
            )
            semantic_head = re.sub(
                r"<style id=\"c99-style-sentinel\">.*?</style>",
                "",
                semantic_head,
                flags=re.IGNORECASE | re.DOTALL,
            )
            parser.feed(f"<head>{semantic_head}</head>")
            self.assertEqual([outcome["title"]], parser.titles, key)
            self.assertEqual(1, parser.viewport_count, key)
            self.assertTrue(outcome["title"].strip())
            self.assertEqual(1, outcome["title_count"])
            self.assertEqual(1, outcome["viewport_count"])
            for contract, passed in outcome.items():
                if contract in {"head", "title", "title_count", "viewport_count"}:
                    continue
                self.assertTrue(passed, f"{key}: {contract}")


if __name__ == "__main__":
    unittest.main()
