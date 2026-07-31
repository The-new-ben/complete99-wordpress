from __future__ import annotations

import json
import shutil
import subprocess
import unittest
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


class Complete99DocumentTitleContracts(unittest.TestCase):
    def test_every_plugin_owned_shell_renders_the_shared_title_before_wp_head(
        self,
    ) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        renderer = frontend.split(
            "public static function render_document_title_tag", 1
        )[1].split("public static function robots", 1)[0]

        self.assertIn(
            "remove_action( 'wp_head', '_wp_render_title_tag', 1 )", renderer
        )
        self.assertIn("wp_get_document_title()", renderer)
        self.assertIn("esc_html( $title )", renderer)
        self.assertIn("$title = 'Complete99';", renderer)

        call = "Complete99_Frontend::render_document_title_tag();"
        for template in TEMPLATES:
            source = template.read_text(encoding="utf-8")
            self.assertEqual(1, source.count(call), template)
            self.assertLess(source.index(call), source.index("wp_head();"), template)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for template rendering")
    def test_live_dish_and_not_found_templates_emit_one_escaped_title(self) -> None:
        php = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', __PLUGIN_DIR__);
define('COMPLETE99_PLATFORM_VERSION', 'test');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-test');
define('C99_LIVE_DISH_TEMPLATE', __LIVE_DISH_TEMPLATE__);
define('C99_NOT_FOUND_TEMPLATE', __NOT_FOUND_TEMPLATE__);

$GLOBALS['c99_query'] = array();
$GLOBALS['c99_is_404'] = false;
$GLOBALS['c99_core_title_active'] = true;
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
function get_option($key, $default = false) {
    return $default;
}
function is_404() {
    return $GLOBALS['c99_is_404'];
}
function is_singular($post_type = '') {
    return false;
}
function get_queried_object_id() {
    return 0;
}
function bloginfo($key) {
    echo 'UTF-8';
}
function body_class() {
    echo 'class="complete99-test"';
}
function wp_body_open() {}
function wp_footer() {}
function wp_head() {
    if ($GLOBALS['c99_core_title_active']) {
        echo '<title>Core duplicate</title>';
    }
}

class Complete99_Consumer {
    public static function menu_items() {
        return $GLOBALS['c99_items'];
    }
    public static function render_live_dish_page($dish, $lang) {}
    public static function render_not_found_page($lang) {}
}

require __FRONTEND__;

function c99_render_document($language, $slug, $not_found) {
    $GLOBALS['c99_query'] = array(
        'complete99_live_dish' => $slug,
        'complete99_live_lang' => $language,
    );
    $GLOBALS['c99_is_404'] = $not_found;
    $GLOBALS['c99_core_title_active'] = true;

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

$documents = array(
    'he_live' => c99_render_document('he', 'sabich', false),
    'en_live' => c99_render_document('en', 'sabich', false),
    'he_404' => c99_render_document('he', 'missing-dish', true),
    'en_404' => c99_render_document('en', 'missing-dish', true),
);
$result = array();
foreach ($documents as $key => $html) {
    preg_match_all('#<title>(.*?)</title>#s', $html, $matches);
    $result[$key] = $matches[1];
}
echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
"""
        replacements = {
            "__PLUGIN_DIR__": json.dumps(f"{PLUGIN.as_posix()}/"),
            "__LIVE_DISH_TEMPLATE__": json.dumps(TEMPLATES[0].as_posix()),
            "__NOT_FOUND_TEMPLATE__": json.dumps(TEMPLATES[2].as_posix()),
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
        titles = json.loads(completed.stdout)

        self.assertEqual(
            ["הסביח של 99 &amp; מיוחד | Complete99"], titles["he_live"]
        )
        self.assertEqual(
            ["The 99 Sabich &amp; Special | Complete99"], titles["en_live"]
        )
        self.assertEqual(["המנה לא נמצאה | קומפלט 99"], titles["he_404"])
        self.assertEqual(["Dish not found | Complete99"], titles["en_404"])
        for case_titles in titles.values():
            self.assertEqual(1, len(case_titles))
            self.assertTrue(case_titles[0].strip())


if __name__ == "__main__":
    unittest.main()
