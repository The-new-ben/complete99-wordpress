from __future__ import annotations

import json
import os
import re
import subprocess
import unittest
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from urllib.parse import quote


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SETTINGS = PLUGIN / "includes" / "class-complete99-settings.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
NOT_FOUND_TEMPLATE = PLUGIN / "templates" / "not-found.php"
DISH_SEEDS = PLUGIN / "data" / "dish-seeds.php"
LAUNCH_CONTENT = PLUGIN / "data" / "launch-content.php"
PRIVATE_OS_HOST = "complete99-os.benben777.chatgpt.site"
PUBLIC_SITE = "https://complete99-public.benben777.chatgpt.site"


def constant(text: str, name: str) -> str:
    match = re.search(rf"const\s+{re.escape(name)}\s*=\s*'([^']+)'", text)
    if not match:
        raise AssertionError(f"Missing {name} constant")
    return match.group(1)


class Complete99PublicLaunchContracts(unittest.TestCase):
    def test_public_demo_and_asset_defaults_are_separate(self) -> None:
        settings = SETTINGS.read_text(encoding="utf-8")
        frontend = FRONTEND.read_text(encoding="utf-8")

        self.assertEqual(PUBLIC_SITE, constant(settings, "DEFAULT_PUBLIC_SITE_URL"))
        self.assertEqual(f"{PUBLIC_SITE}/platform", constant(settings, "DEFAULT_APP_URL"))
        self.assertEqual(f"{PUBLIC_SITE}/en/platform", constant(settings, "DEFAULT_APP_URL_EN"))
        self.assertEqual(PUBLIC_SITE, constant(settings, "DEFAULT_ASSET_URL"))
        self.assertIn(
            "self::install_default( self::OPTION_ASSET_URL, self::DEFAULT_ASSET_URL )",
            settings,
        )
        self.assertIn("public static function app_url( $language = 'he' )", settings)
        self.assertIn("Complete99_Settings::app_url( $lang )", frontend)
        self.assertNotIn(PRIVATE_OS_HOST, settings)
        self.assertNotIn(PRIVATE_OS_HOST, frontend)

    def test_https_urls_are_canonical_and_public_hosted(self) -> None:
        settings_path = SETTINGS.as_posix().replace("'", "\\'")
        php = f"""
define('ABSPATH', __DIR__);
function esc_url_raw($url, $protocols = array()) {{
    $url = filter_var((string) $url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {{
        return '';
    }}
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $protocols && !in_array($scheme, $protocols, true) ? '' : $url;
}}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function untrailingslashit($value) {{
    return rtrim((string) $value, "/\\\\");
}}
require '{settings_path}';
$method = new ReflectionMethod('Complete99_Settings', 'canonical_https_url');
$method->setAccessible(true);
$credential_url = 'https://' . 'user:pass@example.com/path';
$cases = array(
    'HTTPS://Example.COM/path/' => 'https://example.com/path',
    'https://complete99.co.il/' => 'https://complete99.co.il',
    'http://example.com/path' => '',
    'https://localhost/path' => '',
    $credential_url => '',
    'https://example.com:8443/path' => '',
    'javascript:alert(1)' => '',
);
$results = array();
foreach ($cases as $candidate => $expected) {{
    $results[$candidate] = array(
        'expected' => $expected,
        'actual' => $method->invoke(null, $candidate),
    );
}}
echo json_encode($results);
"""
        result = subprocess.run(
            ["php", "-r", php],
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
        )
        for candidate, outcome in json.loads(result.stdout).items():
            self.assertEqual(
                outcome["expected"],
                outcome["actual"],
                f"Unexpected canonical form for {candidate}",
            )

    def test_managed_pages_own_one_canonical_and_truthful_social_metadata(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")

        self.assertEqual(1, frontend.count('<link rel="canonical"'))
        self.assertIn(
            "add_action( 'template_redirect', array( __CLASS__, 'remove_core_canonical' ), 0 )",
            frontend,
        )
        self.assertIn("remove_action( 'wp_head', 'rel_canonical' )", frontend)
        for marker in (
            'property="og:locale:alternate"',
            'name="twitter:card"',
            'name="twitter:title"',
            'name="twitter:description"',
            'name="twitter:image"',
        ):
            self.assertIn(marker, frontend)
        self.assertNotIn("twitter:site", frontend)
        self.assertNotIn("twitter:creator", frontend)

    def test_unknown_live_dishes_use_bilingual_plugin_owned_404s(self) -> None:
        frontend_path = FRONTEND.as_posix().replace("'", "\\'")
        plugin_path = (PLUGIN.as_posix() + "/").replace("'", "\\'")
        php = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
$GLOBALS['complete99_query'] = array();
function get_query_var($key, $default = '') {{
    return array_key_exists($key, $GLOBALS['complete99_query'])
        ? $GLOBALS['complete99_query'][$key]
        : $default;
}}
function sanitize_title($value) {{
    return strtolower(trim((string) $value));
}}
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_\\-]/', '', strtolower((string) $value));
}}
function is_404() {{ return true; }}
function is_singular($post_type = '') {{ return false; }}
function get_queried_object_id() {{ return 0; }}
class Complete99_REST {{
    public static function public_indexable_items() {{ return array(); }}
}}
class Complete99_Content {{
    public static function is_complete99_post($post_id) {{ return false; }}
}}
require '{frontend_path}';
$results = array();
foreach (array('he', 'en') as $language) {{
    $GLOBALS['complete99_query'] = array(
        'complete99_live_dish' => 'missing-dish',
        'complete99_live_lang' => $language,
    );
    $results[$language] = array(
        'body' => Complete99_Frontend::body_classes(array('rtl')),
        'title' => Complete99_Frontend::document_title('Theme fallback'),
        'robots' => Complete99_Frontend::robots(array('index' => true)),
        'template' => basename(Complete99_Frontend::template_include('theme-404.php')),
    );
}}
echo json_encode($results, JSON_UNESCAPED_UNICODE);
"""
        result = subprocess.run(
            ["php", "-r", php],
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
        )
        outcomes = json.loads(result.stdout)

        self.assertEqual("not-found.php", outcomes["he"]["template"])
        self.assertEqual("not-found.php", outcomes["en"]["template"])
        self.assertEqual("המנה לא נמצאה | קומפלט 99", outcomes["he"]["title"])
        self.assertEqual("Dish not found | Complete99", outcomes["en"]["title"])
        self.assertIn("complete99-rtl", outcomes["he"]["body"])
        self.assertIn("complete99-ltr", outcomes["en"]["body"])
        self.assertIn("complete99-not-found", outcomes["he"]["body"])
        self.assertIn("complete99-not-found", outcomes["en"]["body"])
        self.assertIn("rtl", outcomes["he"]["body"])
        self.assertNotIn("rtl", outcomes["en"]["body"])
        for language in ("he", "en"):
            self.assertTrue(outcomes[language]["robots"]["noindex"])
            self.assertFalse(outcomes[language]["robots"]["nofollow"])
            self.assertNotIn("index", outcomes[language]["robots"])

        template = NOT_FOUND_TEMPLATE.read_text(encoding="utf-8")
        self.assertIn(
            '<html lang="<?php echo esc_attr( $complete99_not_found_lang ); ?>" '
            'dir="<?php echo esc_attr( $complete99_not_found_dir ); ?>">',
            template,
        )
        self.assertIn("render_live_dish_not_found_page", template)
        self.assertIn("wp_head();", template)
        self.assertIn("wp_footer();", template)
        self.assertNotIn('rel="canonical"', template)

    def test_homepage_has_no_visible_or_schema_breadcrumb_duplication(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        render_current = frontend.split(
            "public static function render_current", 1
        )[1].split("private static function render_home", 1)[0]
        self.assertLess(
            render_current.index("if ( 'home' === $key )"),
            render_current.index("self::render_breadcrumb( $post, $lang )"),
        )

        schema = frontend.split("private static function schema_graph", 1)[1].split(
            "private static function verified_recipe_schema", 1
        )[0]
        self.assertIn("if ( 'home' !== $key )", schema)
        self.assertIn("'@type'           => 'BreadcrumbList'", schema)
        self.assertNotIn(
            "'breadcrumb'   => array( '@id' => $url . '#breadcrumb' )",
            schema.split("if ( 'home' !== $key )", 1)[0],
        )

    def test_referenced_asset_names_match_public_inventory(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        dishes = DISH_SEEDS.read_text(encoding="utf-8")
        settings = SETTINGS.read_text(encoding="utf-8")
        launch = LAUNCH_CONTENT.read_text(encoding="utf-8")
        combined = "\n".join((frontend, dishes, launch))
        assets = set(
            re.findall(r"c99-[a-z0-9-]+\.(?:jpg|jpeg|png|webp|avif)", combined)
        )

        self.assertIn("assets/images/complete99-mark.svg", combined)
        self.assertNotIn(
            "c99-identity-legacy-logo-square-2021-wp-v01.png",
            combined,
        )
        self.assertIn(
            "c99-food-sabich-pita-gallery-2021-wp-v01.jpg",
            assets,
        )
        self.assertNotIn(
            "c99-identity-legacy-logo-square-small-2021-wp-v01.png",
            combined,
        )
        self.assertNotIn(
            "c99-food-sabich-plate-gallery-2021-wp-v01.jpg",
            combined,
        )
        self.assertEqual(PUBLIC_SITE, constant(settings, "DEFAULT_ASSET_URL"))
        self.assertGreaterEqual(len(assets), 8)

    def test_public_cta_is_an_overview_not_a_private_application_claim(self) -> None:
        frontend = FRONTEND.read_text(encoding="utf-8")
        self.assertIn("'סקירת יכולות המערכת' : 'Explore the platform'", frontend)
        self.assertNotIn("Launch the application", frontend)
        self.assertNotIn("פתיחת האפליקציה", frontend)

    @unittest.skipUnless(
        os.environ.get("COMPLETE99_VERIFY_PUBLIC_URLS") == "1",
        "Set COMPLETE99_VERIFY_PUBLIC_URLS=1 for anonymous public-host verification.",
    )
    def test_every_public_launch_url_returns_anonymous_200(self) -> None:
        settings = SETTINGS.read_text(encoding="utf-8")
        combined = "\n".join(
            path.read_text(encoding="utf-8")
            for path in (FRONTEND, DISH_SEEDS, LAUNCH_CONTENT)
        )
        asset_base = constant(settings, "DEFAULT_ASSET_URL")
        urls = {
            constant(settings, "DEFAULT_APP_URL"): "text/html",
            constant(settings, "DEFAULT_APP_URL_EN"): "text/html",
        }
        for filename in set(
            re.findall(r"c99-[a-z0-9-]+\.(?:jpg|jpeg|png|webp|avif)", combined)
        ):
            urls[f"{asset_base}/assets/original/{quote(filename)}"] = "image/"

        def verify(item: tuple[str, str]) -> tuple[str, int, str]:
            url, expected_type = item
            request = urllib.request.Request(
                url,
                method="HEAD",
                headers={"User-Agent": "Complete99-Public-Launch-Contract/1.0"},
            )
            with urllib.request.urlopen(request, timeout=30) as response:
                return url, response.status, response.headers.get_content_type()

        with ThreadPoolExecutor(max_workers=6) as pool:
            results = list(pool.map(verify, urls.items()))

        for url, status, content_type in results:
            self.assertEqual(200, status, url)
            expected_type = urls[url]
            if expected_type.endswith("/"):
                self.assertTrue(content_type.startswith(expected_type), url)
            else:
                self.assertEqual(expected_type, content_type, url)


if __name__ == "__main__":
    unittest.main()
