from __future__ import annotations

import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
REST = PLUGIN / "includes" / "class-complete99-rest.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
CONTENT = PLUGIN / "includes" / "class-complete99-content.php"
TEMPLATE = PLUGIN / "templates" / "live-dish.php"
CSS = PLUGIN / "assets" / "css" / "public.css"

PHP_RUNTIME_BOOTSTRAP = r"""
define('ABSPATH', __DIR__);

class WP_Error {
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message = '', $data = array()) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }

class WP_REST_Request {
    private $body;
    public function __construct($body = '') { $this->body = $body; }
    public function get_body() { return $this->body; }
    public function get_header($name) { return ''; }
}
class WP_REST_Response {
    public $data;
    public function __construct($data) { $this->data = $data; }
}
function rest_ensure_response($value) { return new WP_REST_Response($value); }

$c99_persisted_options = array();
$c99_option_cache = array();
$c99_update_count = 0;
$c99_force_readback_mismatch = false;
$c99_object_cache_result = true;
$c99_object_cache_calls = 0;
$c99_litespeed_listener = true;
$c99_litespeed_calls = 0;
$c99_upress_calls = 0;
$c99_upress_result = null;

function maybe_unserialize($value) {
    if (!is_string($value)) return $value;
    $decoded = @unserialize($value, array('allowed_classes' => false));
    return false === $decoded && 'b:0;' !== $value ? $value : $decoded;
}
function get_option($name, $default = false) {
    global $c99_persisted_options, $c99_option_cache;
    if (array_key_exists($name, $c99_option_cache)) return $c99_option_cache[$name];
    if (!array_key_exists($name, $c99_persisted_options)) return $default;
    $value = maybe_unserialize($c99_persisted_options[$name]);
    $c99_option_cache[$name] = $value;
    return $value;
}
function update_option($name, $value, $autoload = null) {
    global $c99_persisted_options, $c99_option_cache, $c99_update_count;
    $raw = serialize($value);
    $changed = !array_key_exists($name, $c99_persisted_options)
        || $c99_persisted_options[$name] !== $raw;
    $c99_persisted_options[$name] = $raw;
    $c99_option_cache[$name] = $value;
    $c99_update_count++;
    return $changed;
}
function sanitize_key($value) {
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
}
function sanitize_title($value) {
    $value = strtolower(trim((string) $value));
    return trim(preg_replace('/[^a-z0-9]+/', '-', $value), '-');
}
function sanitize_text_field($value) { return trim((string) $value); }
function sanitize_file_name($value) { return basename((string) $value); }
function esc_url_raw($value, $protocols = null) {
    return 0 === strpos((string) $value, 'https://') ? (string) $value : '';
}
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function has_action($name) {
    global $c99_litespeed_listener;
    return 'litespeed_purge_all' === $name && $c99_litespeed_listener ? 10 : false;
}
function do_action($name) {
    global $c99_litespeed_calls;
    if ('litespeed_purge_all' === $name) $c99_litespeed_calls++;
}
function wp_cache_flush() {
    global $c99_object_cache_calls, $c99_object_cache_result, $c99_option_cache;
    $c99_object_cache_calls++;
    $c99_option_cache = array();
    return $c99_object_cache_result;
}

class Complete99RuntimeWpdb {
    public $options = 'wp_options';
    public $last_error = '';
    public function suppress_errors($suppress = true) { return false; }
    public function prepare($query, ...$args) {
        return array('query' => $query, 'args' => $args);
    }
    public function get_var($prepared) {
        global $c99_persisted_options, $c99_force_readback_mismatch, $c99_update_count;
        if ($c99_force_readback_mismatch && 0 < $c99_update_count) {
            return serialize(array('corrupted' => true));
        }
        $name = (string) ($prepared['args'][0] ?? '');
        return $c99_persisted_options[$name] ?? null;
    }
}
$wpdb = new Complete99RuntimeWpdb();

eval('namespace Upress\\EzCache; class Cache {
    public static function instance() { return new self(); }
    public function clear_cache() {
        $GLOBALS["c99_upress_calls"]++;
        return $GLOBALS["c99_upress_result"];
    }
}');

function c99_reset_runtime($stored = null) {
    global $c99_persisted_options, $c99_option_cache, $c99_update_count;
    global $c99_force_readback_mismatch, $c99_object_cache_result;
    global $c99_object_cache_calls, $c99_litespeed_calls, $c99_upress_calls;
    global $c99_litespeed_listener, $c99_upress_result;
    $c99_persisted_options = array();
    if (null !== $stored) {
        $c99_persisted_options['complete99_public_read_model'] = serialize($stored);
    }
    $c99_option_cache = array();
    $c99_update_count = 0;
    $c99_force_readback_mismatch = false;
    $c99_object_cache_result = true;
    $c99_object_cache_calls = 0;
    $c99_litespeed_listener = true;
    $c99_litespeed_calls = 0;
    $c99_upress_calls = 0;
    $c99_upress_result = null;
}
function c99_item($id, $slug) {
    return array(
        'id' => $id,
        'slug' => $slug,
        'name_he' => 'Hebrew name',
        'name_en' => 'English name',
        'description_he' => 'Hebrew description',
        'description_en' => 'English description',
        'availability' => 'available',
        'verification_state' => 'launch_ready',
        'published' => true,
        'vegetarian' => false,
        'sort' => 1,
        'updated_at' => gmdate('c'),
    );
}
function c99_payload($items) {
    return array(
        'schema' => 'complete99-public-read-model/v1',
        'version' => 'complete99-os-v42',
        'generated_at' => gmdate('c'),
        'branches' => array(),
        'menu_sections' => array(),
        'menu_items' => $items,
        'campaigns' => array(),
    );
}
function c99_request($payload) {
    return new WP_REST_Request(json_encode($payload, JSON_UNESCAPED_SLASHES));
}
function c99_error($value) {
    if (!is_wp_error($value)) return array('code' => '', 'status' => 0, 'data' => array());
    $data = $value->get_error_data();
    return array(
        'code' => $value->get_error_code(),
        'status' => is_array($data) ? (int) ($data['status'] ?? 0) : 0,
        'data' => is_array($data) ? $data : array(),
    );
}

require '__REST_PATH__';
"""


class LiveReadModelContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.rest = REST.read_text(encoding="utf-8")
        cls.frontend = FRONTEND.read_text(encoding="utf-8")
        cls.content = CONTENT.read_text(encoding="utf-8")
        cls.template = TEMPLATE.read_text(encoding="utf-8")
        cls.css = CSS.read_text(encoding="utf-8")

    def run_php_runtime(self, body: str) -> dict:
        rest_path = REST.as_posix().replace("'", "\\'")
        script = PHP_RUNTIME_BOOTSTRAP.replace("__REST_PATH__", rest_path) + "\n" + body
        result = subprocess.run(
            ["php", "-r", script],
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=20,
        )
        return json.loads(result.stdout)

    def test_receiver_keeps_the_bilingual_public_dish_contract(self) -> None:
        for field in (
            "'slug'",
            "'name_he'",
            "'name_en'",
            "'category_he'",
            "'category_en'",
            "'description_he'",
            "'description_en'",
            "'tag_he'",
            "'tag_en'",
            "'image_asset'",
            "'public_price'",
            "'currency'",
            "'availability'",
            "'verification_state'",
            "'updated_at'",
        ):
            self.assertIn(field, self.rest)

        gate = self.rest.split("public static function is_public_item", 1)[1].split(
            "public static function public_catalog", 1
        )[0]
        self.assertIn("'verified', 'launch_ready'", gate)
        self.assertIn("'available', 'low', 'sold_out'", gate)
        self.assertIn("'description_he', 'description_en'", gate)
        for marker in (
            "const PUBLIC_MODEL_TTL = 604800",
            "public static function public_indexable_items",
            "private static function read_persisted_read_model",
            "private static function validate_item_identities",
            "private static function purge_public_read_model_caches",
            "complete99_sync_boolean",
            "complete99_sync_slug_collision",
            "complete99_sync_stale_model",
            "serialize( $clean )",
            "litespeed_purge_all",
            "\\Upress\\EzCache\\Cache::instance()",
        ):
            self.assertIn(marker, self.rest)

    def test_public_item_gate_runs_in_php_and_fails_closed(self) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $base = array(
            'id' => 'dish-1',
            'slug' => 'sabich',
            'name_he' => 'סביח',
            'name_en' => 'Sabich',
            'description_he' => 'תיאור',
            'description_en' => 'Description',
            'availability' => 'available',
            'verification_state' => 'launch_ready',
            'published' => true,
        );
        $fresh = array(
            'updated_at' => gmdate('c'),
            'items' => array($base),
        );
        $stale = array(
            'updated_at' => gmdate('c', time() - Complete99_REST::PUBLIC_MODEL_TTL - 1),
            'items' => array($base),
        );
        $draft = $base;
        $draft['published'] = false;
        $string_false = $base;
        $string_false['published'] = 'false';
        $unverified = $base;
        $unverified['verification_state'] = 'draft';
        $missing = $base;
        $missing['description_en'] = '';
        echo json_encode(array(
            'valid' => Complete99_REST::is_public_item($base, $fresh),
            'draft' => Complete99_REST::is_public_item($draft, $fresh),
            'string_false' => Complete99_REST::is_public_item($string_false, $fresh),
            'unverified' => Complete99_REST::is_public_item($unverified, $fresh),
            'missing' => Complete99_REST::is_public_item($missing, $fresh),
            'stale' => Complete99_REST::is_public_item($base, $stale),
            'indexable_count' => count(Complete99_REST::public_indexable_items($fresh)),
            'stale_indexable_count' => count(Complete99_REST::public_indexable_items($stale)),
        ));
        """
        )
        self.assertEqual(
            {
                "valid": True,
                "draft": False,
                "string_false": False,
                "unverified": False,
                "missing": False,
                "stale": False,
                "indexable_count": 1,
                "stale_indexable_count": 0,
            },
            result,
        )

    def test_sync_normalizes_boolean_strings_and_rejects_malformed_values_and_slug_collisions(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $normalised_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $normalised_payload['menu_items'][0]['published'] = 'false';
        $normalised_payload['menu_items'][0]['vegetarian'] = 'true';
        $normalised = Complete99_REST::sync_read_model(c99_request($normalised_payload));
        $normalised_model = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );

        $malformed = array();
        foreach (array('False', ' false ', '0', 1, null) as $index => $invalid_boolean) {
            c99_reset_runtime();
            $malformed_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
            $malformed_payload['menu_items'][0]['published'] = $invalid_boolean;
            $response = Complete99_REST::sync_read_model(c99_request($malformed_payload));
            $malformed[] = array(
                'error' => c99_error($response),
                'updates' => $c99_update_count,
            );
        }

        c99_reset_runtime();
        $duplicate_payload = c99_payload(array(
            c99_item('dish-a', 'Same Dish'),
            c99_item('dish-b', 'same-dish'),
        ));
        $duplicate = Complete99_REST::sync_read_model(c99_request($duplicate_payload));

        $stored = array(
            'updated_at' => gmdate('c'),
            'items' => array(c99_item('dish-a', 'owned-slug')),
        );
        c99_reset_runtime($stored);
        $reassigned = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-b', 'owned-slug'))))
        );

        echo json_encode(array(
            'normalised_response' => $normalised instanceof WP_REST_Response
                ? $normalised->data
                : array(),
            'normalised_published' => $normalised_model['items'][0]['published'] ?? null,
            'normalised_vegetarian' => $normalised_model['items'][0]['vegetarian'] ?? null,
            'malformed' => $malformed,
            'duplicate' => c99_error($duplicate),
            'reassigned' => c99_error($reassigned),
        ));
        """
        )
        self.assertTrue(result["normalised_response"]["stored"])
        self.assertIs(result["normalised_published"], False)
        self.assertIs(result["normalised_vegetarian"], True)
        for malformed in result["malformed"]:
            self.assertEqual("complete99_sync_boolean", malformed["error"]["code"])
            self.assertEqual(400, malformed["error"]["status"])
            self.assertEqual(0, malformed["updates"])
        self.assertEqual("complete99_sync_slug_collision", result["duplicate"]["code"])
        self.assertEqual(409, result["duplicate"]["status"])
        self.assertEqual("complete99_sync_slug_collision", result["reassigned"]["code"])
        self.assertEqual(409, result["reassigned"]["status"])

    def test_sync_deliberately_preserves_canonical_slug_identity(self) -> None:
        result = self.run_php_runtime(
            r"""
        $legacy_item = c99_item('dish-legacy', 'unused');
        unset($legacy_item['slug']);
        $legacy = array(
            'updated_at' => gmdate('c'),
            'items' => array($legacy_item),
        );
        c99_reset_runtime($legacy);
        $assigned = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-legacy', 'initial-slug'))))
        );
        $assigned_data = $assigned instanceof WP_REST_Response
            ? $assigned->data
            : c99_error($assigned);
        $assigned_updates = $c99_update_count;

        $stored = array(
            'updated_at' => gmdate('c'),
            'items' => array(c99_item('dish-a', 'canonical-slug')),
        );
        c99_reset_runtime($stored);
        $renamed = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'replacement-slug'))))
        );

        echo json_encode(array(
            'legacy_assignment' => $assigned_data,
            'legacy_updates' => $assigned_updates,
            'renamed' => c99_error($renamed),
            'updates' => $c99_update_count,
        ));
        """
        )
        self.assertTrue(result["legacy_assignment"]["stored"])
        self.assertEqual(1, result["legacy_updates"])
        self.assertEqual("complete99_sync_slug_changed", result["renamed"]["code"])
        self.assertEqual(409, result["renamed"]["status"])
        self.assertEqual(0, result["updates"])

    def test_sync_requires_exact_readback_and_truthfully_reports_cache_work(self) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $success = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );
        $success_data = $success instanceof WP_REST_Response ? $success->data : array();
        $stored = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );
        $success_counts = array(
            'upress' => $c99_upress_calls,
            'litespeed' => $c99_litespeed_calls,
            'object' => $c99_object_cache_calls,
        );

        c99_reset_runtime();
        $c99_force_readback_mismatch = true;
        $mismatch = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );
        $mismatch_cache_calls = $c99_upress_calls + $c99_litespeed_calls + $c99_object_cache_calls;

        c99_reset_runtime();
        $c99_object_cache_result = false;
        $cache_failure = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );

        echo json_encode(array(
            'success' => $success_data,
            'stored_digest_matches' => is_array($stored)
                && ($stored['digest'] ?? '') === ($success_data['digest'] ?? ''),
            'success_counts' => $success_counts,
            'mismatch' => c99_error($mismatch),
            'mismatch_cache_calls' => $mismatch_cache_calls,
            'cache_failure' => c99_error($cache_failure),
        ));
        """
        )
        self.assertTrue(result["success"]["stored"])
        self.assertTrue(result["success"]["write_changed"])
        self.assertTrue(result["stored_digest_matches"])
        self.assertEqual(
            {"upress": 1, "litespeed": 1, "object": 1},
            result["success_counts"],
        )
        self.assertTrue(result["success"]["cache"]["object_cache"]["flushed"])
        self.assertTrue(
            result["success"]["cache"]["page_cache"]["upress"]["request_completed"]
        )
        self.assertTrue(
            result["success"]["cache"]["page_cache"]["litespeed"]["signal_sent"]
        )
        self.assertEqual("complete99_sync_readback", result["mismatch"]["code"])
        self.assertEqual(500, result["mismatch"]["status"])
        self.assertEqual(0, result["mismatch_cache_calls"])
        self.assertEqual(
            "complete99_sync_object_cache", result["cache_failure"]["code"]
        )
        self.assertEqual(503, result["cache_failure"]["status"])
        self.assertTrue(result["cache_failure"]["data"]["stored"])
        self.assertFalse(
            result["cache_failure"]["data"]["cache"]["object_cache"]["flushed"]
        )

    def test_generation_freshness_and_public_catalog_fail_closed(self) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $invalid_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $invalid_payload['generated_at'] = '2026-02-30T00:00:00Z';
        $invalid = Complete99_REST::sync_read_model(c99_request($invalid_payload));

        c99_reset_runtime();
        $stale_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $stale_payload['generated_at'] = gmdate(
            'c',
            time() - Complete99_REST::MAX_CLOCK_SKEW - 1
        );
        $stale = Complete99_REST::sync_read_model(c99_request($stale_payload));

        $stale_model = array(
            'updated_at' => gmdate(
                'c',
                time() - Complete99_REST::PUBLIC_MODEL_TTL - 1
            ),
            'items' => array(c99_item('dish-a', 'dish-a')),
        );
        c99_reset_runtime($stale_model);
        $catalog = Complete99_REST::public_catalog();

        echo json_encode(array(
            'invalid' => c99_error($invalid),
            'stale' => c99_error($stale),
            'catalog' => c99_error($catalog),
            'indexable' => Complete99_REST::public_indexable_items($stale_model),
        ));
        """
        )
        self.assertEqual("complete99_sync_generated_at", result["invalid"]["code"])
        self.assertEqual(400, result["invalid"]["status"])
        self.assertEqual("complete99_sync_stale_model", result["stale"]["code"])
        self.assertEqual(409, result["stale"]["status"])
        self.assertEqual("complete99_public_model_stale", result["catalog"]["code"])
        self.assertEqual(503, result["catalog"]["status"])
        self.assertEqual([], result["indexable"])

    def test_live_dish_routes_are_bilingual_and_do_not_replace_editorial_routes(self) -> None:
        self.assertIn("'^menu/([^/]+)/?$'", self.content)
        self.assertIn("'^en/menu/([^/]+)/?$'", self.content)
        self.assertIn("complete99_live_dish", self.content)
        self.assertIn("complete99_live_lang=en", self.content)
        self.assertIn("'^' . preg_quote( $base, '/' ) . '/([^/]+)/?$'", self.content)

    def test_live_menu_is_server_rendered_and_never_resurrects_static_items(self) -> None:
        for marker in (
            "private static function public_model_items",
            "Complete99_REST::public_indexable_items",
            "private static function render_live_menu",
            "No dishes have yet been published from the authoritative menu source.",
            "We therefore do not show an estimated menu or resurrect stale content.",
            "public static function render_live_dish_page",
            "private static function live_dish_head_metadata",
            "'@type'       => 'MenuItem'",
        ):
            self.assertIn(marker, self.frontend)
        self.assertNotIn("publicDishes", self.frontend)
        self.assertIn("wp_head()", self.template)
        self.assertIn("render_live_dish_page", self.template)

    def test_live_menu_has_responsive_and_accessible_presentation_contracts(self) -> None:
        for marker in (
            ".c99-live-menu-grid",
            ".c99-live-menu-card",
            ".c99-menu-availability",
            ".c99-live-dish-grid",
            "@media (max-width: 782px)",
            "@media (max-width: 560px)",
        ):
            self.assertIn(marker, self.css)
        self.assertIn('aria-labelledby="c99-live-menu-title"', self.frontend)
        self.assertIn('aria-current="page"', self.frontend)
        self.assertIn('loading="lazy"', self.frontend)


if __name__ == "__main__":
    unittest.main()
