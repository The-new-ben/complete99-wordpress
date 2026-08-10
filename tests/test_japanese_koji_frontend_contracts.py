from __future__ import annotations

import json
import shutil
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = (
    PLUGIN / "includes" / "class-complete99-culinary-museum-frontend.php"
)
SCIENCE = PLUGIN / "includes" / "class-complete99-culinary-science.php"

ROUTES = {
    "/ingredients/shoyu-koji/": (
        "ingredient-shoyu-koji",
        "DefinedTerm",
        "c99-science-shoyu-koji-substrate-v01",
    ),
    "/knowledge/koji-enzymatic-hydrolysis/": (
        "guide-koji-hydrolysis",
        "Article",
        "c99-science-koji-enzymes-hydrolysis-guide-v01",
    ),
    "/knowledge/kioke-barrel-guide/": (
        "equipment-kioke",
        "DefinedTerm",
        "c99-science-kioke-wooden-barrel-v01",
    ),
    "/knowledge/jas-1703-shoyu-standard/": (
        "standard-jas-shoyu-1703",
        "Legislation",
        "c99-science-jas-1703-shoyu-standard-v01",
    ),
}


def test_v6_measurement_renderer_is_explicitly_verified_only() -> None:
    frontend = FRONTEND.read_text(encoding="utf-8")
    renderer = frontend.split(
        "private static function render_scientific_measurements", 1
    )[1].split("private static function render_sections", 1)[0]

    for field in (
        "property",
        "kind",
        "low",
        "high",
        "value",
        "unit",
        "method",
        "specimen_scope",
        "conditions",
        "confidence",
        "source_ids",
        "measured_at",
    ):
        assert f"$measurement['{field}']" in renderer
    assert "verified_scientific_measurements" in renderer
    assert "'verified' !== $measurement['confidence']" in renderer
    assert "data-c99-scientific-measurement=\"verified\"" in renderer
    assert "render_source_markers" in renderer
    assert "No separate measurement timestamp" in renderer
    assert "PRIVATE" not in renderer
    assert "\N{EM DASH}" not in frontend


@pytest.mark.skipif(shutil.which("php") is None, reason="PHP is required")
def test_real_v20_candidate_routes_are_absent_without_owner_receipts() -> None:
    frontend_path = json.dumps(FRONTEND.as_posix())
    science_path = json.dumps(SCIENCE.as_posix())
    plugin_dir = json.dumps(f"{PLUGIN.as_posix()}/")
    requested_paths = [
        path
        for he_path in ROUTES
        for path in (he_path, f"/en{he_path}")
    ]
    php_paths = json.dumps(requested_paths)
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {plugin_dir});
define('COMPLETE99_PLATFORM_URL', 'https://complete99.example/plugin/');
define('COMPLETE99_PLATFORM_VERSION', 'test');

class WP_Error {{
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message, $data = array()) {{
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }}
    public function get_error_code() {{ return $this->code; }}
    public function get_error_message() {{ return $this->message; }}
    public function get_error_data() {{ return $this->data; }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
function home_url($path = '') {{
    return 'https://complete99.example/' . ltrim((string) $path, '/');
}}
function wp_parse_url($url, $component = -1) {{
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}}
function wp_unslash($value) {{ return $value; }}
function wp_strip_all_tags($value) {{ return strip_tags((string) $value); }}
function sanitize_html_class($value) {{
    return preg_replace('/[^a-z0-9_-]/', '-', strtolower((string) $value));
}}
function sanitize_key($value) {{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}}
function absint($value) {{ return abs((int) $value); }}
function esc_attr($value) {{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}}
function esc_url($value) {{ return esc_attr($value); }}
function esc_html($value) {{ return esc_attr($value); }}
function esc_url_raw($value, $protocols = null) {{ return (string) $value; }}
function number_format_i18n($value, $decimals = 0) {{
    return number_format((float) $value, (int) $decimals, '.', ',');
}}

require {science_path};
require {frontend_path};

$schema_method = new ReflectionMethod(
    'Complete99_Culinary_Museum_Frontend',
    'schema_graph'
);
$schema_method->setAccessible(true);

function c99_add_measurement_canaries(&$bundle) {{
    if ('guide-koji-hydrolysis' !== $bundle['entity']['id']) {{
        return;
    }}
    foreach ($bundle['sections'] as &$section) {{
        if ('reaction-koji-enzymatic-hydrolysis' !== $section['id']) {{
            continue;
        }}
        foreach ($section['facts'] as &$fact) {{
            if ('fact-koji-industrial-protease-activity-ranges' !== $fact['id']) {{
                continue;
            }}
            $reviewed = $fact['scientific_measurements'][0];
            $reviewed['id'] = 'measurement-private-reviewed-canary';
            $reviewed['confidence'] = 'reviewed';
            $reviewed['method'] = 'PRIVATE-REVIEWED-MEASUREMENT-CANARY';
            $fact['scientific_measurements'][] = $reviewed;

            $point = array(
                'id' => 'measurement-public-point-renderer-canary',
                'property' => 'process-temperature',
                'kind' => 'point',
                'low' => null,
                'high' => null,
                'value' => 40,
                'unit' => 'C',
                'method' => 'PUBLIC-VERIFIED-POINT-RENDERER-CANARY',
                'specimen_scope' => 'lot_measurement',
                'conditions' => array(
                    'instrument' => 'Calibrated public probe',
                ),
                'confidence' => 'verified',
                'source_ids' => array('zhang-industrial-koji-proteases-2023'),
                'measured_at' => '2026-08-08T12:00:00+03:00',
            );
            $fact['scientific_measurements'][] = $point;
        }}
        unset($fact);
    }}
    unset($section);
}}

function c99_probe_route($path, $schema_method) {{
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    $wp = (object) array('query_vars' => array());
    Complete99_Culinary_Museum_Frontend::capture_request($wp);
    if (!Complete99_Culinary_Museum_Frontend::is_museum_request()) {{
        return array('active' => false);
    }}

    $bundle = Complete99_Culinary_Museum_Frontend::current_bundle();
    $schema = $schema_method->invoke(null, $bundle);
    $robots = Complete99_Culinary_Museum_Frontend::robots(
        array('index' => true, 'nofollow' => true, 'max-snippet' => 0)
    );
    ob_start();
    Complete99_Culinary_Museum_Frontend::head_metadata();
    $head = ob_get_clean();

    c99_add_measurement_canaries($bundle);
    ob_start();
    Complete99_Culinary_Museum_Frontend::render_page($bundle);
    $html = ob_get_clean();

    $link_urls = array();
    $link_target_ids = array();
    foreach (array_merge(array($bundle['entity']), $bundle['sections']) as $record) {{
        foreach ($record['internal_links'] as $link) {{
            $link_urls[] = $link['url'];
            $link_target_ids[] = $link['target_id'];
        }}
    }}

    return array(
        'active' => true,
        'language' => $bundle['language'],
        'entity_id' => $bundle['entity']['id'],
        'route_mode' => $bundle['entity']['seo']['route_mode'],
        'entity_schema_type' => $bundle['entity']['seo']['schema_type'],
        'canonical_path' => $bundle['canonical_path'],
        'canonical_url' => $bundle['canonical_url'],
        'alternates' => $bundle['alternates'],
        'indexable' => $bundle['indexable'],
        'robots' => $robots,
        'head' => $head,
        'html' => $html,
        'schema' => $schema,
        'visual' => $bundle['entity']['visual'],
        'sections' => array_map(
            static function($section) {{
                return array(
                    'id' => $section['id'],
                    'route_mode' => $section['seo']['route_mode'],
                    'section_id' => $section['seo']['section_id'],
                    'schema_type' => $section['seo']['schema_type'],
                    'visual' => $section['visual'],
                );
            }},
            $bundle['sections']
        ),
        'link_urls' => array_values(array_unique($link_urls)),
        'link_target_ids' => array_values(array_unique($link_target_ids)),
        'offer' => $bundle['entity']['offer'],
        'market_context' => $bundle['entity']['market_context'],
    );
}}

$results = array();
foreach ({php_paths} as $path) {{
    $results[$path] = c99_probe_route($path, $schema_method);
}}
echo json_encode(
    $results,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);
"""
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
        timeout=60,
        check=False,
    )
    assert completed.returncode == 0, completed.stderr
    results = json.loads(completed.stdout)

    assert set(results) == set(requested_paths)
    for path in requested_paths:
        assert results[path] == {"active": False}
