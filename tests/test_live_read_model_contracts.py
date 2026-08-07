from __future__ import annotations

import json
import hashlib
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
DIGEST_FIXTURE = ROOT / "tests" / "fixtures" / "complete99-public-read-model-digest-v1.json"

PHP_RUNTIME_BOOTSTRAP = r"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '__PLUGIN_PATH__/');
define('COMPLETE99_PLATFORM_VERSION', '1.12.0');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-wp-1.12.0');
define('C99_DIGEST_FIXTURE', '__FIXTURE_PATH__');

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
    private $headers;
    public function __construct($body = '', $headers = array()) {
        $this->body = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }
    public function get_body() { return $this->body; }
    public function get_header($name) {
        return $this->headers[strtolower((string) $name)] ?? '';
    }
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
$c99_transients = array();

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
function get_transient($name) {
    global $c99_transients;
    if (!isset($c99_transients[$name])) return false;
    if ($c99_transients[$name]['expires'] < time()) {
        unset($c99_transients[$name]);
        return false;
    }
    return $c99_transients[$name]['value'];
}
function set_transient($name, $value, $ttl) {
    global $c99_transients;
    $c99_transients[$name] = array(
        'value' => $value,
        'expires' => time() + (int) $ttl,
    );
    return true;
}
function wp_salt($scheme = 'auth') { return 'test-salt-' . $scheme; }
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
        $query = (string) ($prepared['query'] ?? '');
        if (false !== strpos($query, 'GET_LOCK(')
            || false !== strpos($query, 'RELEASE_LOCK(')) {
            return '1';
        }
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
    global $c99_transients;
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
    $c99_transients = array();
}
function c99_generation_at($seconds_offset = 0, $milliseconds = '000') {
    return gmdate('Y-m-d\TH:i:s', time() + (int) $seconds_offset)
        . '.' . $milliseconds . 'Z';
}
function c99_item($id, $slug) {
    return array(
        'id' => $id,
        'slug' => $slug,
        'section_id' => 'section-default',
        'name_he' => 'Hebrew name',
        'name_en' => 'English name',
        'category_he' => 'Hebrew category',
        'category_en' => 'English category',
        'description_he' => 'Hebrew description',
        'description_en' => 'English description',
        'tag_he' => 'Hebrew tag',
        'tag_en' => 'English tag',
        'image_asset' => '',
        'media_provenance' => 'business_owned',
        'media_rights_state' => 'approved_public_use',
        'verification_state' => 'launch_ready',
        'published' => true,
        'vegetarian' => false,
        'sort' => 1,
        'updated_at' => c99_generation_at(),
    );
}
function c99_payload_at($items, $generated_at) {
    foreach ($items as &$item) {
        $item['updated_at'] = $generated_at;
    }
    unset($item);
    return array(
        'schema' => 'complete99-public-read-model/v1',
        'version' => 'complete99-os-v42',
        'generated_at' => $generated_at,
        'branches' => array(),
        'menu_sections' => array(),
        'menu_items' => $items,
        'campaigns' => array(),
    );
}
function c99_payload($items) {
    return c99_payload_at($items, c99_generation_at());
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

function c99_canonicalize_read_model_value($value) {
    if (!is_array($value)) return $value;
    $is_list = true;
    $offset = 0;
    foreach (array_keys($value) as $key) {
        if ($key !== $offset) {
            $is_list = false;
            break;
        }
        $offset++;
    }
    $canonical = array();
    foreach ($value as $key => $item) {
        $canonical[$key] = c99_canonicalize_read_model_value($item);
    }
    if (!$is_list) ksort($canonical, SORT_STRING);
    return $canonical;
}
function c99_sign_read_model($model) {
    unset($model['digest']);
    $model['digest'] = hash(
        'sha256',
        json_encode(
            c99_canonicalize_read_model_value($model),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    );
    return $model;
}
function c99_signed_transport_model($items = array(), $generated_at = null, $sections = array()) {
    $generated_at = null === $generated_at ? c99_generation_at() : $generated_at;
    $model = c99_payload_at($items, $generated_at);
    $model['menu_sections'] = $sections;
    return c99_sign_read_model($model);
}
function c99_legacy_model($items = array(), $generated = null, $with_digest = false) {
    $generated = null === $generated ? gmdate('c', time() - 1) : $generated;
    foreach ($items as &$item) {
        $item['updated_at'] = $generated;
    }
    unset($item);
    $model = array(
        'schema' => 'complete99-public-read-model/v1',
        'version' => 'complete99-os-v11',
        'generated' => $generated,
        'updated_at' => $generated,
        'sections' => array(),
        'items' => $items,
        'campaigns' => array(),
    );
    if ($with_digest) {
        $model['digest'] = hash(
            'sha256',
            json_encode(
                $model,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }
    return $model;
}
function c99_bundled_transport_items($generated_at) {
    $items = require COMPLETE99_PLATFORM_DIR . 'data/consumer-menu.php';
    foreach ($items as &$item) {
        $item['section_id'] = 'section-default';
        $item['verification_state'] = 'launch_ready';
        $item['updated_at'] = $generated_at;
        $item['media_provenance'] = 'business_owned';
        $item['media_rights_state'] = 'approved_public_use';
        $item['vegetarian'] = in_array(
            'vegetarian',
            $item['facets'] ?? array(),
            true
        );
        unset(
            $item['facets'],
            $item['badge_codes'],
            $item['menu_evidence'],
            $item['availability']
        );
    }
    unset($item);
    return $items;
}

class Complete99_Settings {
    const OPTION_SECRET = 'complete99_sync_secret';
}
class Complete99_Platform {
    public static function migration_failed() { return false; }
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
        plugin_path = PLUGIN.as_posix().replace("'", "\\'")
        script = (
            PHP_RUNTIME_BOOTSTRAP.replace("__REST_PATH__", rest_path)
            .replace("__PLUGIN_PATH__", plugin_path)
            .replace("__FIXTURE_PATH__", DIGEST_FIXTURE.as_posix().replace("'", "\\'"))
            + "\n"
            + body
        )
        result = subprocess.run(
            ["php", "-r", script],
            check=True,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=20,
        )
        return json.loads(result.stdout)

    def test_health_exposes_only_the_stored_read_model_digest_and_fresh_requires_it(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        $valid = c99_signed_transport_model(
            array(c99_item('dish-a', 'dish-a'))
        );
        $health = static function($model = null) {
            c99_reset_runtime($model);
            $GLOBALS['c99_persisted_options']['complete99_platform_version'] = serialize('1.12.0');
            $GLOBALS['c99_persisted_options']['complete99_last_deployment_id'] = serialize('c99-wp-1.12.0');
            return Complete99_REST::health()->data['read_model'];
        };
        $valid_health = $health($valid);

        $reordered = array(
            'digest' => $valid['digest'],
            'campaigns' => $valid['campaigns'],
            'menu_items' => array(array_reverse($valid['menu_items'][0], true)),
            'menu_sections' => $valid['menu_sections'],
            'branches' => $valid['branches'],
            'generated_at' => $valid['generated_at'],
            'version' => $valid['version'],
            'schema' => $valid['schema'],
        );
        $reordered_health = $health($reordered);

        $arbitrary = $valid;
        $arbitrary['digest'] = str_repeat('a', 64);
        $arbitrary_health = $health($arbitrary);

        $missing = $valid;
        unset($missing['digest']);
        $missing_health = $health($missing);

        $malformed = $valid;
        $malformed['digest'] = 'not-a-digest';
        $malformed_health = $health($malformed);

        $tampered = $valid;
        $tampered['menu_items'][0]['description_en'] = 'Tampered after signing.';
        $tampered_health = $health($tampered);

        $empty_health = $health();

        echo json_encode(array(
            'valid' => $valid_health,
            'reordered' => $reordered_health,
            'arbitrary' => $arbitrary_health,
            'missing' => $missing_health,
            'malformed' => $malformed_health,
            'tampered' => $tampered_health,
            'empty' => $empty_health,
        ), JSON_THROW_ON_ERROR);
        """
        )
        self.assertRegex(result["valid"]["digest"], r"^[a-f0-9]{64}$")
        self.assertTrue(result["valid"]["fresh"])
        self.assertEqual(result["valid"], result["reordered"])
        for state in ("arbitrary", "missing", "malformed", "tampered", "empty"):
            self.assertEqual("", result[state]["digest"])
            self.assertEqual("", result[state]["version"])
            self.assertEqual("", result[state]["updated_at"])
            self.assertEqual("", result[state]["expires_at"])
            self.assertFalse(result[state]["fresh"])

    def test_cross_language_digest_fixture_matches_the_wordpress_contract(self) -> None:
        fixture = json.loads(DIGEST_FIXTURE.read_text(encoding="utf-8"))
        canonical = json.dumps(
            fixture["model"],
            ensure_ascii=False,
            sort_keys=True,
            separators=(",", ":"),
        ).encode("utf-8")
        self.assertEqual(fixture["expected_canonical_utf8_bytes"], len(canonical))
        self.assertEqual(
            fixture["expected_sha256"], hashlib.sha256(canonical).hexdigest()
        )

        result = self.run_php_runtime(
            r"""
        $fixture = json_decode(
            file_get_contents(C99_DIGEST_FIXTURE),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $model = $fixture['model'];
        $digest_method = new ReflectionMethod('Complete99_REST', 'read_model_digest');
        $digest_method->setAccessible(true);
        $shape_method = new ReflectionMethod(
            'Complete99_REST',
            'is_valid_transport_read_model_shape'
        );
        $shape_method->setAccessible(true);
        $model['digest'] = $fixture['expected_sha256'];
        $canonical = json_encode(
            c99_canonicalize_read_model_value($fixture['model']),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        c99_reset_runtime($model);
        $sync = Complete99_REST::sync_read_model(
            c99_request($fixture['model'])
        );
        $receipt = $sync instanceof WP_REST_Response ? $sync->data : array();
        $stored = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );
        echo json_encode(array(
            'bytes' => strlen($canonical),
            'digest' => $digest_method->invoke(null, $model),
            'shape_valid' => $shape_method->invoke(null, $model),
            'expected' => $fixture['expected_sha256'],
            'receipt' => $receipt,
            'receipt_keys' => array_keys($receipt),
            'stored_matches_fixture' => $stored === $model,
        ), JSON_THROW_ON_ERROR);
        """
        )
        self.assertEqual(810, result["bytes"])
        self.assertEqual(
            "b183d09588cb21c1374b5ec75d6d90fac836a49f5e1dbe030f01aa9d85d35410",
            result["digest"],
        )
        self.assertEqual(result["expected"], result["digest"])
        self.assertTrue(result["shape_valid"])
        self.assertEqual(result["expected"], result["receipt"]["digest"])
        self.assertFalse(result["receipt"]["write_changed"])
        self.assertTrue(result["stored_matches_fixture"])
        self.assertEqual(
            {
                "stored",
                "write_changed",
                "version",
                "digest",
                "item_count",
                "expires_at",
                "cache",
            },
            set(result["receipt_keys"]),
        )

    def test_sync_persists_the_exact_transport_envelope_and_rejects_partial_input(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $expected = c99_sign_read_model($payload);
        $response = Complete99_REST::sync_read_model(c99_request($payload));
        $receipt = $response instanceof WP_REST_Response ? $response->data : array();
        $stored = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );
        $stored_unsigned = $stored;
        unset($stored_unsigned['digest']);

        c99_reset_runtime();
        $missing_version = $payload;
        unset($missing_version['version']);
        $missing_response = Complete99_REST::sync_read_model(
            c99_request($missing_version)
        );
        $missing_updates = $c99_update_count;

        c99_reset_runtime();
        $missing_item_field = $payload;
        unset($missing_item_field['menu_items'][0]['tag_en']);
        $missing_item_response = Complete99_REST::sync_read_model(
            c99_request($missing_item_field)
        );
        $missing_item_updates = $c99_update_count;

        c99_reset_runtime();
        $timestamp_mismatch = $payload;
        $timestamp_mismatch['menu_items'][0]['updated_at'] =
            substr($payload['generated_at'], 0, 19) . '.001Z';
        $timestamp_response = Complete99_REST::sync_read_model(
            c99_request($timestamp_mismatch)
        );
        $timestamp_updates = $c99_update_count;

        echo json_encode(array(
            'receipt' => $receipt,
            'receipt_keys' => array_keys($receipt),
            'stored_keys' => array_keys($stored),
            'stored_unsigned_matches' => $stored_unsigned === $payload,
            'expected_digest' => $expected['digest'],
            'missing_version' => c99_error($missing_response),
            'missing_updates' => $missing_updates,
            'missing_item' => c99_error($missing_item_response),
            'missing_item_updates' => $missing_item_updates,
            'timestamp' => c99_error($timestamp_response),
            'timestamp_updates' => $timestamp_updates,
        ), JSON_THROW_ON_ERROR);
        """
        )
        self.assertEqual(
            {
                "stored",
                "write_changed",
                "version",
                "digest",
                "item_count",
                "expires_at",
                "cache",
            },
            set(result["receipt_keys"]),
        )
        self.assertEqual(
            {
                "schema",
                "version",
                "generated_at",
                "branches",
                "menu_sections",
                "menu_items",
                "campaigns",
                "digest",
            },
            set(result["stored_keys"]),
        )
        self.assertTrue(result["stored_unsigned_matches"])
        self.assertEqual(result["expected_digest"], result["receipt"]["digest"])
        for state in ("missing_version", "missing_item"):
            self.assertEqual("complete99_sync_normalization", result[state]["code"])
            self.assertEqual(400, result[state]["status"])
        self.assertEqual("complete99_sync_item_timestamp", result["timestamp"]["code"])
        self.assertEqual(400, result["timestamp"]["status"])
        self.assertEqual(0, result["missing_updates"])
        self.assertEqual(0, result["missing_item_updates"])
        self.assertEqual(0, result["timestamp_updates"])

    def test_legacy_upgrade_is_narrow_integrity_checked_and_identity_preserving(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        $live_generated = '2026-08-01T02:39:21+00:00';
        $live_items = c99_bundled_transport_items($live_generated);
        $live_legacy = c99_legacy_model(
            $live_items,
            $live_generated,
            false
        );
        c99_reset_runtime($live_legacy);
        $new_generated = c99_generation_at();
        $new_items = c99_bundled_transport_items($new_generated);
        $live_upgrade = Complete99_REST::sync_read_model(
            c99_request(c99_payload_at($new_items, $new_generated))
        );
        $live_stored = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );

        $later_legacy = c99_legacy_model(
            array(c99_item('dish-a', 'dish-a')),
            gmdate('c', time() - 2),
            true
        );
        c99_reset_runtime($later_legacy);
        $later_upgrade = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );

        $same_generated = c99_generation_at(-1);
        $same_item = c99_item('dish-a', 'dish-a');
        $same_legacy = c99_legacy_model(
            array($same_item),
            $same_generated,
            true
        );
        c99_reset_runtime($same_legacy);
        $same_payload = c99_payload_at(array($same_item), $same_generated);
        $same_payload['version'] = 'complete99-os-v11';
        $same_upgrade = Complete99_REST::sync_read_model(
            c99_request($same_payload)
        );
        $same_stored = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );

        $tampered_legacy = c99_legacy_model(
            array(c99_item('dish-a', 'dish-a')),
            gmdate('c', time() - 2),
            true
        );
        $tampered_legacy['items'][0]['description_en'] = 'Tampered legacy';
        c99_reset_runtime($tampered_legacy);
        $tampered_response = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );
        $tampered_updates = $c99_update_count;

        $narrow_missing = c99_legacy_model(
            array(c99_item('dish-a', 'dish-a')),
            gmdate('c', time() - 2),
            false
        );
        c99_reset_runtime($narrow_missing);
        $narrow_response = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );

        $malformed_new = c99_signed_transport_model(
            array(c99_item('dish-a', 'dish-a'))
        );
        unset($malformed_new['digest']);
        c99_reset_runtime($malformed_new);
        $malformed_response = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );

        $fraction_second = substr(c99_generation_at(-1), 0, 19);
        $fraction_legacy_time = $fraction_second . '.900Z';
        $fraction_new_time = $fraction_second . '.500Z';
        $fraction_item = c99_item('dish-a', 'dish-a');
        $fraction_legacy = c99_legacy_model(
            array($fraction_item),
            $fraction_legacy_time,
            true
        );
        c99_reset_runtime($fraction_legacy);
        $fraction_payload = c99_payload_at(
            array($fraction_item),
            $fraction_new_time
        );
        $fraction_payload['version'] = 'complete99-os-v11';
        $fraction_response = Complete99_REST::sync_read_model(
            c99_request($fraction_payload)
        );

        echo json_encode(array(
            'live_upgrade' => $live_upgrade instanceof WP_REST_Response
                ? $live_upgrade->data
                : c99_error($live_upgrade),
            'live_stored_keys' => array_keys($live_stored),
            'live_item_count' => count($live_stored['menu_items'] ?? array()),
            'later_upgrade' => $later_upgrade instanceof WP_REST_Response
                ? $later_upgrade->data
                : c99_error($later_upgrade),
            'same_upgrade' => $same_upgrade instanceof WP_REST_Response
                ? $same_upgrade->data
                : c99_error($same_upgrade),
            'same_stored_keys' => array_keys($same_stored),
            'tampered' => c99_error($tampered_response),
            'tampered_updates' => $tampered_updates,
            'narrow_missing' => c99_error($narrow_response),
            'malformed_new' => c99_error($malformed_response),
            'fraction' => c99_error($fraction_response),
        ), JSON_THROW_ON_ERROR);
        """
        )
        for state in ("live_upgrade", "later_upgrade", "same_upgrade"):
            self.assertTrue(result[state]["stored"])
        self.assertEqual(12, result["live_item_count"])
        expected_keys = {
            "schema",
            "version",
            "generated_at",
            "branches",
            "menu_sections",
            "menu_items",
            "campaigns",
            "digest",
        }
        self.assertEqual(expected_keys, set(result["live_stored_keys"]))
        self.assertEqual(expected_keys, set(result["same_stored_keys"]))
        for state in ("tampered", "narrow_missing", "malformed_new"):
            self.assertEqual("complete99_sync_stored_integrity", result[state]["code"])
            self.assertEqual(500, result[state]["status"])
        self.assertEqual(0, result["tampered_updates"])
        self.assertEqual("complete99_sync_non_monotonic", result["fraction"]["code"])
        self.assertEqual(409, result["fraction"]["status"])

    def test_transport_time_and_shape_tampering_fail_closed_before_storage(self) -> None:
        result = self.run_php_runtime(
            r"""
        $timestamp_results = array();
        foreach (array(
            '2026-08-06T12:34:56Z',
            '2026-08-06T12:34:56.000+00:00',
            '2026-08-06T12:34:56.000000Z',
            '2026-02-30T12:34:56.000Z'
        ) as $timestamp) {
            c99_reset_runtime();
            $payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
            $payload['generated_at'] = $timestamp;
            $payload['menu_items'][0]['updated_at'] = $timestamp;
            $response = Complete99_REST::sync_read_model(c99_request($payload));
            $timestamp_results[] = array(
                'error' => c99_error($response),
                'updates' => $c99_update_count,
            );
        }

        $tampered_time = c99_signed_transport_model(
            array(c99_item('dish-a', 'dish-a'))
        );
        $tampered_time['menu_items'][0]['updated_at'] =
            substr($tampered_time['generated_at'], 0, 19) . '.001Z';
        $tampered_time = c99_sign_read_model($tampered_time);
        c99_reset_runtime($tampered_time);
        $tampered_response = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-a', 'dish-a'))))
        );

        $malicious_schema = c99_signed_transport_model(
            array(c99_item('dish-a', 'dish-a'))
        );
        $malicious_schema['schema'] = 'private-schema';
        $malicious_schema = c99_sign_read_model($malicious_schema);
        c99_reset_runtime($malicious_schema);
        $c99_persisted_options['complete99_platform_version'] = serialize('1.12.0');
        $health = Complete99_REST::health()->data['read_model'];
        $catalog = Complete99_REST::public_catalog()->data;

        echo json_encode(array(
            'timestamps' => $timestamp_results,
            'tampered' => c99_error($tampered_response),
            'health' => $health,
            'catalog' => $catalog,
        ), JSON_THROW_ON_ERROR);
        """
        )
        for timestamp in result["timestamps"]:
            self.assertEqual(
                "complete99_sync_generated_at", timestamp["error"]["code"]
            )
            self.assertEqual(400, timestamp["error"]["status"])
            self.assertEqual(0, timestamp["updates"])
        self.assertEqual("complete99_sync_stored_integrity", result["tampered"]["code"])
        self.assertEqual(500, result["tampered"]["status"])
        self.assertEqual("", result["health"]["digest"])
        self.assertEqual("", result["health"]["version"])
        self.assertFalse(result["health"]["fresh"])
        self.assertEqual("complete99-public-read-model/v1", result["catalog"]["schema"])
        self.assertEqual("wordpress_bundle", result["catalog"]["source"])

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
            "'media_provenance'",
            "'media_rights_state'",
            "'verification_state'",
            "'updated_at'",
        ):
            self.assertIn(field, self.rest)

        item_contract = self.rest.split(
            "$items = self::clean_records(", 1
        )[1].split("$stored_model = self::read_persisted_read_model()", 1)[0]
        for operational_field in (
            "'public_price'",
            "'currency'",
            "'availability'",
        ):
            self.assertNotIn(operational_field, item_contract)

        gate = self.rest.split("public static function is_public_item", 1)[1].split(
            "public static function public_catalog", 1
        )[0]
        self.assertIn("'verified', 'launch_ready'", gate)
        self.assertIn("'description_he', 'description_en'", gate)
        self.assertIn("'complete99_archive', 'business_owned', 'licensed'", gate)
        self.assertIn("'approved_public_use'", gate)
        for marker in (
            "const PUBLIC_MODEL_TTL = 86400",
            "public static function public_indexable_items",
            "private static function read_persisted_read_model",
            "private static function validate_item_identities",
            "private static function purge_public_read_model_caches",
            "complete99_sync_boolean",
            "complete99_sync_unknown_field",
            "complete99_sync_private_field",
            "complete99_sync_slug_collision",
            "complete99_sync_stale_model",
            "complete99_sync_non_monotonic",
            "complete99_sync_stored_integrity",
            "complete99_sync_item_timestamp",
            "reserve_sync_nonce",
            "SELECT GET_LOCK",
            "canonicalize_read_model_value",
            "canonical_read_model_value_digest",
            "read_model_digest",
            "read_model_integrity_is_valid",
            "is_valid_transport_read_model_shape",
            "is_recognized_legacy_read_model",
            "hash_equals( $expected, $stored )",
            "litespeed_purge_all",
            "\\Upress\\EzCache\\Cache::instance()",
        ):
            self.assertIn(marker, self.rest)

    def test_campaigns_are_rejected_and_public_catalog_uses_a_safe_projection(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $private_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $private_payload['campaigns'] = array(
            array(
                'id' => 'campaign-1',
                'title_en' => 'Private campaign',
                'published' => true,
            )
        );
        $private_campaign = Complete99_REST::sync_read_model(
            c99_request($private_payload)
        );
        $private_campaign_updates = $c99_update_count;

        c99_reset_runtime();
        $private_branch_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $private_branch_payload['branches'] = array(
            array(
                'id' => 'branch-from-private-os',
                'name_en' => 'Internal branch name',
                'employee_count' => 12,
                'published' => true,
            )
        );
        $private_branch = Complete99_REST::sync_read_model(
            c99_request($private_branch_payload)
        );

        c99_reset_runtime();
        $accepted_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $accepted = Complete99_REST::sync_read_model(
            c99_request($accepted_payload)
        );
        $accepted_model = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );

        $approved_items = require COMPLETE99_PLATFORM_DIR . 'data/consumer-menu.php';
        foreach ($approved_items as &$approved_item) {
            $approved_item['section_id'] = 'section-default';
            $approved_item['verification_state'] = 'launch_ready';
            $approved_item['updated_at'] = gmdate('c');
            $approved_item['media_provenance'] = 'business_owned';
            $approved_item['media_rights_state'] = 'approved_public_use';
            $approved_item['vegetarian'] = in_array(
                'vegetarian',
                $approved_item['facets'] ?? array(),
                true
            );
            unset(
                $approved_item['facets'],
                $approved_item['badge_codes'],
                $approved_item['menu_evidence'],
                $approved_item['availability']
            );
        }
        unset($approved_item);
        $model = c99_signed_transport_model($approved_items);
        c99_reset_runtime($model);
        $catalog_response = Complete99_REST::public_catalog();
        $catalog = $catalog_response instanceof WP_REST_Response
            ? $catalog_response->data
            : array();

        echo json_encode(array(
            'private_campaign' => c99_error($private_campaign),
            'private_campaign_updates' => $private_campaign_updates,
            'private_branch' => c99_error($private_branch),
            'accepted' => $accepted instanceof WP_REST_Response
                ? $accepted->data
                : array(),
            'stored_campaigns' => $accepted_model['campaigns'] ?? null,
            'stored_branches' => $accepted_model['branches'] ?? null,
            'catalog' => $catalog,
        ));
        """
        )
        self.assertEqual(
            "complete99_sync_private_field", result["private_campaign"]["code"]
        )
        self.assertEqual(422, result["private_campaign"]["status"])
        self.assertEqual(0, result["private_campaign_updates"])
        self.assertEqual(
            "complete99_sync_private_field", result["private_branch"]["code"]
        )
        self.assertEqual(422, result["private_branch"]["status"])
        self.assertTrue(result["accepted"]["stored"])
        self.assertEqual(1, result["accepted"]["item_count"])
        self.assertEqual([], result["stored_campaigns"])
        self.assertEqual([], result["stored_branches"])

        catalog = result["catalog"]
        self.assertEqual(
            {
                "schema",
                "version",
                "updated_at",
                "sections",
                "items",
                "freshness",
                "source",
                "sync",
            },
            set(catalog),
        )
        self.assertEqual(
            "wordpress_bundle_attested_by_synced_model", catalog["source"]
        )
        self.assertTrue(catalog["sync"]["attested"])
        self.assertTrue(catalog["sync"]["controls_applied"])
        self.assertNotIn("campaigns", catalog)
        self.assertNotIn("branches", catalog)
        self.assertNotIn("digest", catalog)
        self.assertNotIn("generated", catalog)
        self.assertEqual({"name_he", "name_en"}, set(catalog["sections"][0]))
        self.assertEqual(
            {
                "slug",
                "name_he",
                "name_en",
                "category_he",
                "category_en",
                "description_he",
                "description_en",
                "tag_he",
                "tag_en",
                "image_asset",
                "vegetarian",
                "updated_at",
            },
            set(catalog["items"][0]),
        )
        serialized_catalog = json.dumps(catalog, sort_keys=True)
        for private_value in (
            "campaign-private",
            "Never public",
            "branch-1",
            "branch-from-private-os",
            "Internal branch name",
            "section-1",
            "operating",
            "business_owned",
            "approved_public_use",
            "launch_ready",
        ):
            self.assertNotIn(private_value, serialized_catalog)

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
            'updated_at' => gmdate('c'),
        );
        $fresh = c99_signed_transport_model(
            array(c99_item('dish-1', 'sabich'))
        );
        $stale = c99_signed_transport_model(
            array(c99_item('dish-1', 'sabich')),
            c99_generation_at(-Complete99_REST::PUBLIC_MODEL_TTL - 1)
        );
        $approved_items = require COMPLETE99_PLATFORM_DIR . 'data/consumer-menu.php';
        foreach ($approved_items as &$approved_item) {
            $approved_item['section_id'] = 'section-default';
            $approved_item['verification_state'] = 'launch_ready';
            $approved_item['updated_at'] = gmdate('c');
            $approved_item['media_provenance'] = 'business_owned';
            $approved_item['media_rights_state'] = 'approved_public_use';
            $approved_item['vegetarian'] = in_array(
                'vegetarian',
                $approved_item['facets'] ?? array(),
                true
            );
            unset(
                $approved_item['facets'],
                $approved_item['badge_codes'],
                $approved_item['menu_evidence'],
                $approved_item['availability']
            );
        }
        unset($approved_item);
        $approved = c99_signed_transport_model($approved_items);
        $changed_copy = $approved;
        $changed_copy['menu_items'][0]['description_en'] = 'Unapproved replacement copy.';
        $changed_copy = c99_sign_read_model($changed_copy);
        $held = $approved;
        $held['menu_items'][0]['published'] = false;
        $held = c99_sign_read_model($held);
        $draft = $base;
        $draft['published'] = false;
        $string_false = $base;
        $string_false['published'] = 'false';
        $unverified = $base;
        $unverified['verification_state'] = 'draft';
        $missing = $base;
        $missing['description_en'] = '';
        $stale_item = $base;
        $stale_item['updated_at'] = gmdate(
            'c',
            time() - Complete99_REST::PUBLIC_MODEL_TTL - 1
        );
        $unapproved_media = $base;
        $unapproved_media['image_asset'] = 'dish.jpg';
        $unapproved_media['media_provenance'] = 'unknown';
        $unapproved_media['media_rights_state'] = 'pending';
        $approved_media = $base;
        $approved_media['image_asset'] = 'dish.jpg';
        $approved_media['media_provenance'] = 'business_owned';
        $approved_media['media_rights_state'] = 'approved_public_use';
        echo json_encode(array(
            'valid' => Complete99_REST::is_public_item($base, $fresh),
            'draft' => Complete99_REST::is_public_item($draft, $fresh),
            'string_false' => Complete99_REST::is_public_item($string_false, $fresh),
            'unverified' => Complete99_REST::is_public_item($unverified, $fresh),
            'missing' => Complete99_REST::is_public_item($missing, $fresh),
            'stale' => Complete99_REST::is_public_item($base, $stale),
            'stale_item' => Complete99_REST::is_public_item($stale_item, $fresh),
            'unapproved_media' => Complete99_REST::is_public_item($unapproved_media, $fresh),
            'approved_media' => Complete99_REST::is_public_item($approved_media, $fresh),
            'indexable_count' => count(Complete99_REST::public_indexable_items($fresh)),
            'indexable_source' => Complete99_REST::public_indexable_items($fresh)[0]['_complete99_source'] ?? '',
            'stale_indexable_count' => count(Complete99_REST::public_indexable_items($stale)),
            'approved_indexable_count' => count(Complete99_REST::public_indexable_items($approved)),
            'approved_indexable_source' => Complete99_REST::public_indexable_items($approved)[0]['_complete99_source'],
            'approved_facets' => Complete99_REST::public_indexable_items($approved)[0]['facets'],
            'approved_badges' => Complete99_REST::public_indexable_items($approved)[0]['badge_codes'],
            'approved_sort' => Complete99_REST::public_indexable_items($approved)[0]['sort'],
            'changed_copy_source' => Complete99_REST::public_indexable_items($changed_copy)[0]['_complete99_source'],
            'held_count' => count(Complete99_REST::public_indexable_items($held)),
            'held_contains_sabich' => !empty(array_filter(
                Complete99_REST::public_indexable_items($held),
                static function ($item) {
                    return ($item['slug'] ?? '') === 'sabich';
                }
            )),
            'held_source' => Complete99_REST::public_indexable_items($held)[0]['_complete99_source'],
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
                "stale_item": False,
                "unapproved_media": False,
                "approved_media": True,
                "indexable_count": 1,
                "indexable_source": "wordpress_bundle_with_synced_controls",
                "stale_indexable_count": 12,
                "approved_indexable_count": 12,
                "approved_indexable_source": "wordpress_bundle_attested_by_synced_model",
                "approved_facets": ["pita", "plate", "vegetarian"],
                "approved_badges": ["pita", "vegetarian"],
                "approved_sort": 10,
                "changed_copy_source": "wordpress_bundle_with_synced_controls",
                "held_count": 11,
                "held_contains_sabich": False,
                "held_source": "wordpress_bundle_with_synced_controls",
            },
            result,
        )

    def test_branch_input_is_rejected_because_public_transport_requires_empty_lists(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $payload = c99_payload(array());
        $payload['branches'] = array(
            array(
                'id' => 'private-location-1',
                'name_en' => 'Private location',
                'employee_count' => 42,
                'supplier_notes' => 'Never public',
                'published' => true,
            )
        );
        $nonempty = Complete99_REST::sync_read_model(c99_request($payload));

        c99_reset_runtime();
        $scalar_payload = c99_payload(array());
        $scalar_payload['branches'] = 'private';
        $scalar = Complete99_REST::sync_read_model(c99_request($scalar_payload));
        echo json_encode(array(
            'nonempty' => c99_error($nonempty),
            'scalar' => c99_error($scalar),
            'updates' => $c99_update_count,
        ));
        """
        )
        for state in ("nonempty", "scalar"):
            self.assertEqual("complete99_sync_private_field", result[state]["code"])
            self.assertEqual(422, result[state]["status"])
        self.assertEqual(0, result["updates"])

    def test_sync_rejects_unknown_public_payload_and_record_fields(self) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $top_level = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $top_level['internal_notes'] = 'private';
        $top_level_response = Complete99_REST::sync_read_model(
            c99_request($top_level)
        );

        c99_reset_runtime();
        $record = c99_item('dish-a', 'dish-a');
        $record['availability'] = 'available';
        $record_response = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array($record)))
        );

        echo json_encode(array(
            'top_level' => c99_error($top_level_response),
            'record' => c99_error($record_response),
            'updates' => $c99_update_count,
        ));
        """
        )
        self.assertEqual("complete99_sync_unknown_field", result["top_level"]["code"])
        self.assertEqual(400, result["top_level"]["status"])
        self.assertEqual("complete99_sync_unknown_field", result["record"]["code"])
        self.assertEqual(400, result["record"]["status"])
        self.assertEqual(0, result["updates"])

    def test_sync_rejects_non_boolean_values_and_slug_collisions(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $malformed = array();
        foreach (array('true', 'false', 'False', ' false ', '0', 1, null) as $index => $invalid_boolean) {
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
            c99_item('dish-a', 'same-dish'),
            c99_item('dish-b', 'same-dish'),
        ));
        $duplicate = Complete99_REST::sync_read_model(c99_request($duplicate_payload));

        $stored = c99_signed_transport_model(
            array(c99_item('dish-a', 'owned-slug'))
        );
        c99_reset_runtime($stored);
        $reassigned = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-b', 'owned-slug'))))
        );

        echo json_encode(array(
            'malformed' => $malformed,
            'duplicate' => c99_error($duplicate),
            'reassigned' => c99_error($reassigned),
        ));
        """
        )
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
        $legacy = c99_legacy_model(array($legacy_item), null, true);
        c99_reset_runtime($legacy);
        $assigned = Complete99_REST::sync_read_model(
            c99_request(c99_payload(array(c99_item('dish-legacy', 'initial-slug'))))
        );
        $assigned_data = $assigned instanceof WP_REST_Response
            ? $assigned->data
            : c99_error($assigned);
        $assigned_updates = $c99_update_count;

        $stored = c99_legacy_model(
            array(c99_item('dish-a', 'canonical-slug')),
            null,
            true
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

    def test_sync_generation_is_monotonic_and_exact_retries_are_idempotent(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $generated = c99_generation_at(0, '500');
        $generation_second = substr($generated, 0, 19);
        $first_payload = c99_payload_at(
            array(c99_item('dish-a', 'dish-a')),
            $generated
        );
        $first = Complete99_REST::sync_read_model(c99_request($first_payload));
        $updates_after_first = $c99_update_count;
		$cache_calls_after_first = array(
			'upress' => $c99_upress_calls,
			'litespeed' => $c99_litespeed_calls,
			'object' => $c99_object_cache_calls,
		);

        $retry = Complete99_REST::sync_read_model(c99_request($first_payload));
        $updates_after_retry = $c99_update_count;
		$cache_calls_after_retry = array(
			'upress' => $c99_upress_calls,
			'litespeed' => $c99_litespeed_calls,
			'object' => $c99_object_cache_calls,
		);

        $older_payload = c99_payload_at(
            array(c99_item('dish-a', 'dish-a')),
            $generation_second . '.400Z'
        );
        $older_payload['menu_items'][0]['description_en'] = 'Changed older copy';
        $older = Complete99_REST::sync_read_model(c99_request($older_payload));

        $same_time_changed = $first_payload;
        $same_time_changed['menu_items'][0]['description_en'] = 'Changed same-time copy';
        $same_time = Complete99_REST::sync_read_model(
            c99_request($same_time_changed)
        );

        $later_payload = c99_payload_at(
            array(c99_item('dish-a', 'dish-a')),
            $generation_second . '.600Z'
        );
        $later_payload['menu_items'][0]['description_en'] = 'Changed later copy';
        $later = Complete99_REST::sync_read_model(c99_request($later_payload));

        echo json_encode(array(
            'first' => $first instanceof WP_REST_Response ? $first->data : array(),
            'retry' => $retry instanceof WP_REST_Response ? $retry->data : array(),
            'updates_after_first' => $updates_after_first,
            'updates_after_retry' => $updates_after_retry,
			'cache_calls_after_first' => $cache_calls_after_first,
			'cache_calls_after_retry' => $cache_calls_after_retry,
            'older' => c99_error($older),
            'same_time' => c99_error($same_time),
            'later' => $later instanceof WP_REST_Response ? $later->data : c99_error($later),
        ));
        """
        )
        self.assertTrue(result["first"]["write_changed"])
        self.assertFalse(result["retry"]["write_changed"])
        self.assertEqual(
            {"upress": 1, "litespeed": 1, "object": 1},
            result["cache_calls_after_first"],
        )
        self.assertEqual(
            {"upress": 2, "litespeed": 2, "object": 2},
            result["cache_calls_after_retry"],
        )
        self.assertTrue(result["retry"]["cache"]["object_cache"]["flushed"])
        self.assertTrue(
            result["retry"]["cache"]["page_cache"]["upress"][
                "request_completed"
            ]
        )
        self.assertTrue(
            result["retry"]["cache"]["page_cache"]["litespeed"]["signal_sent"]
        )
        self.assertEqual(
            result["updates_after_first"], result["updates_after_retry"]
        )
        self.assertEqual("complete99_sync_non_monotonic", result["older"]["code"])
        self.assertEqual(409, result["older"]["status"])
        self.assertEqual(
            "complete99_sync_non_monotonic", result["same_time"]["code"]
        )
        self.assertEqual(409, result["same_time"]["status"])
        self.assertTrue(result["later"]["stored"])

    def test_cache_failure_is_fail_closed_and_recovers_on_equivalent_fresh_nonce_retry(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $secret = str_repeat('s', 32);
        update_option(Complete99_Settings::OPTION_SECRET, $secret, false);

        $payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) time();
        $attempt = static function($nonce) use ($body, $timestamp, $secret) {
            $canonical = $timestamp . "\n" . $nonce . "\n"
                . hash('sha256', $body);
            $request = new WP_REST_Request(
                $body,
                array(
                    'x-complete99-timestamp' => $timestamp,
                    'x-complete99-nonce' => $nonce,
                    'x-complete99-signature' => hash_hmac(
                        'sha256',
                        $canonical,
                        $secret
                    ),
                )
            );
            $permission = Complete99_REST::verify_sync_signature($request);
            $response = true === $permission
                ? Complete99_REST::sync_read_model($request)
                : $permission;
            return array(
                'permission' => true === $permission,
                'response' => $response,
            );
        };

        $c99_object_cache_result = false;
        $first = $attempt('cache_failure_nonce_0001');
        $updates_after_first = $c99_update_count;
        $stored_after_first = maybe_unserialize(
            $c99_persisted_options['complete99_public_read_model'] ?? ''
        );

        $retry_failure = $attempt('cache_failure_nonce_0002');
        $updates_after_retry_failure = $c99_update_count;
        $calls_after_retry_failure = array(
            'upress' => $c99_upress_calls,
            'litespeed' => $c99_litespeed_calls,
            'object' => $c99_object_cache_calls,
        );

        $c99_object_cache_result = true;
        $recovered = $attempt('cache_failure_nonce_0003');
        $updates_after_recovery = $c99_update_count;
        $calls_after_recovery = array(
            'upress' => $c99_upress_calls,
            'litespeed' => $c99_litespeed_calls,
            'object' => $c99_object_cache_calls,
        );

        echo json_encode(array(
            'first_permission' => $first['permission'],
            'first' => c99_error($first['response']),
            'stored_digest' => is_array($stored_after_first)
                ? ($stored_after_first['digest'] ?? '')
                : '',
            'retry_permission' => $retry_failure['permission'],
            'retry_failure' => c99_error($retry_failure['response']),
            'recovery_permission' => $recovered['permission'],
            'recovered' => $recovered['response'] instanceof WP_REST_Response
                ? $recovered['response']->data
                : array(),
            'updates_after_first' => $updates_after_first,
            'updates_after_retry_failure' => $updates_after_retry_failure,
            'updates_after_recovery' => $updates_after_recovery,
            'calls_after_retry_failure' => $calls_after_retry_failure,
            'calls_after_recovery' => $calls_after_recovery,
        ));
        """
        )
        self.assertTrue(result["first_permission"])
        self.assertEqual("complete99_sync_object_cache", result["first"]["code"])
        self.assertEqual(503, result["first"]["status"])
        self.assertTrue(result["first"]["data"]["stored"])
        self.assertTrue(result["retry_permission"])
        self.assertEqual(
            "complete99_sync_object_cache", result["retry_failure"]["code"]
        )
        self.assertEqual(503, result["retry_failure"]["status"])
        self.assertTrue(result["retry_failure"]["data"]["stored"])
        self.assertEqual(
            {"upress": 2, "litespeed": 2, "object": 2},
            result["calls_after_retry_failure"],
        )
        self.assertTrue(result["recovery_permission"])
        self.assertTrue(result["recovered"]["stored"])
        self.assertFalse(result["recovered"]["write_changed"])
        self.assertEqual(result["stored_digest"], result["recovered"]["digest"])
        self.assertTrue(result["recovered"]["cache"]["object_cache"]["flushed"])
        self.assertEqual(
            {"upress": 3, "litespeed": 3, "object": 3},
            result["calls_after_recovery"],
        )
        self.assertEqual(
            result["updates_after_first"], result["updates_after_retry_failure"]
        )
        self.assertEqual(
            result["updates_after_first"], result["updates_after_recovery"]
        )

    def test_stale_equivalent_retry_uses_fresh_signature_and_only_repurges_caches(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        $generated = c99_generation_at(
            -Complete99_REST::MAX_CLOCK_SKEW - 5
        );
        $item = c99_item('dish-a', 'dish-a');
        $stored = c99_signed_transport_model(array($item), $generated);
        c99_reset_runtime($stored);

        $secret = str_repeat('s', 32);
        update_option(Complete99_Settings::OPTION_SECRET, $secret, false);
        $updates_before_requests = $c99_update_count;
        $timestamp = (string) time();
        $send = static function($body, $nonce) use ($timestamp, $secret) {
            $canonical = $timestamp . "\n" . $nonce . "\n"
                . hash('sha256', $body);
            $request = new WP_REST_Request(
                $body,
                array(
                    'x-complete99-timestamp' => $timestamp,
                    'x-complete99-nonce' => $nonce,
                    'x-complete99-signature' => hash_hmac(
                        'sha256',
                        $canonical,
                        $secret
                    ),
                )
            );
            $permission = Complete99_REST::verify_sync_signature($request);
            return array(
                'permission' => true === $permission,
                'response' => true === $permission
                    ? Complete99_REST::sync_read_model($request)
                    : $permission,
            );
        };

        $payload = c99_payload_at(array($item), $generated);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $retry = $send($body, 'stale_equivalent_nonce_0001');
        $calls_after_retry = array(
            'upress' => $c99_upress_calls,
            'litespeed' => $c99_litespeed_calls,
            'object' => $c99_object_cache_calls,
        );

        $different_payload = $payload;
        $different_payload['menu_items'][0]['description_en'] = 'Different copy';
        $different = $send(
            json_encode($different_payload, JSON_UNESCAPED_SLASHES),
            'stale_equivalent_nonce_0002'
        );
        $calls_after_different = array(
            'upress' => $c99_upress_calls,
            'litespeed' => $c99_litespeed_calls,
            'object' => $c99_object_cache_calls,
        );

        echo json_encode(array(
            'retry_permission' => $retry['permission'],
            'retry' => $retry['response'] instanceof WP_REST_Response
                ? $retry['response']->data
                : array(),
            'different_permission' => $different['permission'],
            'different' => c99_error($different['response']),
            'calls_after_retry' => $calls_after_retry,
            'calls_after_different' => $calls_after_different,
            'updates_before_requests' => $updates_before_requests,
            'updates_after_requests' => $c99_update_count,
        ));
        """
        )
        self.assertTrue(result["retry_permission"])
        self.assertTrue(result["retry"]["stored"])
        self.assertFalse(result["retry"]["write_changed"])
        self.assertTrue(result["retry"]["cache"]["object_cache"]["flushed"])
        self.assertEqual(
            {"upress": 1, "litespeed": 1, "object": 1},
            result["calls_after_retry"],
        )
        self.assertTrue(result["different_permission"])
        self.assertEqual("complete99_sync_stale_model", result["different"]["code"])
        self.assertEqual(409, result["different"]["status"])
        self.assertEqual(
            result["calls_after_retry"], result["calls_after_different"]
        )
        self.assertEqual(
            result["updates_before_requests"], result["updates_after_requests"]
        )

    def test_signed_sync_reserves_nonce_only_after_signature_verification(
        self,
    ) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $secret = str_repeat('s', 32);
        update_option(Complete99_Settings::OPTION_SECRET, $secret, false);
        $body = json_encode(c99_payload(array()), JSON_UNESCAPED_SLASHES);
        $timestamp = (string) time();

        $make_request = static function($nonce, $signature) use ($body, $timestamp) {
            return new WP_REST_Request(
                $body,
                array(
                    'x-complete99-timestamp' => $timestamp,
                    'x-complete99-nonce' => $nonce,
                    'x-complete99-signature' => $signature,
                )
            );
        };
        $sign = static function($nonce) use ($body, $timestamp, $secret) {
            $canonical = $timestamp . "\n" . $nonce . "\n"
                . hash('sha256', $body);
            return hash_hmac('sha256', $canonical, $secret);
        };

        $nonce_one = 'nonce_reservation_0001';
        $first = Complete99_REST::verify_sync_signature(
            $make_request($nonce_one, $sign($nonce_one))
        );
        $replay = Complete99_REST::verify_sync_signature(
            $make_request($nonce_one, $sign($nonce_one))
        );

        $nonce_two = 'nonce_reservation_0002';
        $bad = Complete99_REST::verify_sync_signature(
            $make_request($nonce_two, str_repeat('0', 64))
        );
        $valid_after_bad = Complete99_REST::verify_sync_signature(
            $make_request($nonce_two, $sign($nonce_two))
        );

        echo json_encode(array(
            'first' => true === $first,
            'replay' => c99_error($replay),
            'bad' => c99_error($bad),
            'valid_after_bad' => true === $valid_after_bad,
        ));
        """
        )
        self.assertTrue(result["first"])
        self.assertEqual("complete99_sync_replay", result["replay"]["code"])
        self.assertEqual(409, result["replay"]["status"])
        self.assertEqual("complete99_sync_signature", result["bad"]["code"])
        self.assertEqual(401, result["bad"]["status"])
        self.assertTrue(result["valid_after_bad"])

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

    def test_generation_freshness_rejects_bad_sync_and_uses_bundled_catalog(self) -> None:
        result = self.run_php_runtime(
            r"""
        c99_reset_runtime();
        $invalid_payload = c99_payload(array(c99_item('dish-a', 'dish-a')));
        $invalid_payload['generated_at'] = '2026-02-30T00:00:00Z';
        $invalid = Complete99_REST::sync_read_model(c99_request($invalid_payload));

        c99_reset_runtime();
        $stale_payload = c99_payload_at(
            array(c99_item('dish-a', 'dish-a')),
            c99_generation_at(-Complete99_REST::MAX_CLOCK_SKEW - 1)
        );
        $stale = Complete99_REST::sync_read_model(c99_request($stale_payload));

        $stale_model = c99_signed_transport_model(
            array(c99_item('dish-a', 'dish-a')),
            c99_generation_at(-Complete99_REST::PUBLIC_MODEL_TTL - 1)
        );
        c99_reset_runtime($stale_model);
        $catalog_response = Complete99_REST::public_catalog();
        $catalog = $catalog_response instanceof WP_REST_Response
            ? $catalog_response->data
            : array();

        echo json_encode(array(
            'invalid' => c99_error($invalid),
            'stale' => c99_error($stale),
            'catalog' => $catalog,
            'indexable' => Complete99_REST::public_indexable_items($stale_model),
        ));
        """
        )
        self.assertEqual("complete99_sync_generated_at", result["invalid"]["code"])
        self.assertEqual(400, result["invalid"]["status"])
        self.assertEqual("complete99_sync_stale_model", result["stale"]["code"])
        self.assertEqual(409, result["stale"]["status"])
        self.assertEqual("wordpress_bundle", result["catalog"]["source"])
        self.assertEqual("wordpress-bundle-2026-08-01-v1", result["catalog"]["version"])
        self.assertTrue(result["catalog"]["freshness"]["fallback_active"])
        self.assertFalse(result["catalog"]["freshness"]["fresh"])
        self.assertFalse(result["catalog"]["sync"]["attested"])
        self.assertFalse(result["catalog"]["sync"]["controls_applied"])
        self.assertGreater(len(result["catalog"]["sections"]), 0)
        self.assertEqual(12, len(result["catalog"]["items"]))
        self.assertEqual(12, len(result["indexable"]))
        self.assertTrue(
            all(
                item["_complete99_source"] == "wordpress_bundle"
                for item in result["indexable"]
            )
        )

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
        self.assertIn(
            "Complete99_REST::is_public_model_fresh( $model )", self.frontend
        )
        self.assertNotIn("publicDishes", self.frontend)
        self.assertIn("Complete99_Frontend::render_document_head()", self.template)
        self.assertNotIn("wp_head()", self.template)
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
