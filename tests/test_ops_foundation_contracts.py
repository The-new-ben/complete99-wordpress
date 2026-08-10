from __future__ import annotations

import json
import shutil
import subprocess
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
BOOTSTRAP = PLUGIN / "complete99-platform.php"
OPS = PLUGIN / "includes" / "class-complete99-ops.php"
PLATFORM = PLUGIN / "includes" / "class-complete99-platform.php"
SETTINGS = PLUGIN / "includes" / "class-complete99-settings.php"
OPS_JS = PLUGIN / "assets" / "js" / "ops.js"
OPS_CSS = PLUGIN / "assets" / "css" / "ops.css"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
LAUNCH_CONTENT = PLUGIN / "data" / "launch-content.php"


class Complete99OpsFoundationContracts(unittest.TestCase):
    def test_private_ops_foundation_is_booted_inside_the_locked_migration(self) -> None:
        bootstrap = BOOTSTRAP.read_text(encoding="utf-8")
        platform = PLATFORM.read_text(encoding="utf-8")
        migration = platform[platform.index("private static function run_migration") :]

        self.assertIn("includes/class-complete99-ops.php", bootstrap)
        self.assertIn("Complete99_Ops::boot();", platform)
        transaction = migration.index("START TRANSACTION")
        prepare = migration.index("Complete99_Ops::prepare_schema();")
        install = migration.index("Complete99_Ops::install();")
        invariant = migration.index("Complete99_Ops::assert_invariants();")
        version = migration.index(
            "update_option( 'complete99_platform_version', COMPLETE99_PLATFORM_VERSION"
        )
        commit = migration.index("$wpdb->query( 'COMMIT' )")
        self.assertLess(prepare, transaction)
        self.assertLess(transaction, install)
        self.assertLess(install, invariant)
        self.assertLess(invariant, version)
        self.assertLess(version, commit)

    def test_schema_contract_covers_all_p1_entities_and_keeps_audit_append_only(self) -> None:
        ops = OPS.read_text(encoding="utf-8")

        self.assertIn(
            "const SCHEMA_VERSION        = 'complete99-ops-schema/v1';", ops
        )
        for suffix in (
            "c99_ops_locations",
            "c99_ops_memberships",
            "c99_ops_tasks",
            "c99_ops_issues",
            "c99_ops_commands",
            "c99_ops_mutation_receipts",
            "c99_ops_audit_events",
        ):
            self.assertIn(suffix, ops)
        self.assertIn("public static function prepare_schema()", ops)
        self.assertIn("dbDelta( $definition );", ops)
        self.assertIn("ENGINE=InnoDB", ops)
        self.assertIn("self::persisted_schema_version()", ops)
        self.assertIn("SHOW COLUMNS FROM", ops)
        self.assertIn("SHOW TABLE STATUS LIKE %s", ops)
        self.assertIn("SHOW INDEX FROM", ops)
        self.assertIn("SELECT name FROM sqlite_master", ops)
        self.assertIn("PRAGMA table_info(", ops)
        self.assertIn("PRAGMA index_list(", ops)

        audit_sql = ops.split(
            '"CREATE TABLE {$tables[\'audit_events\']}', 1
        )[1].split('){$suffix};",', 1)[0]
        self.assertIn("occurred_at datetime NOT NULL", audit_sql)
        self.assertNotIn("updated_at", audit_sql)
        self.assertNotIn("ON UPDATE", audit_sql.upper())

    def test_admin_and_rest_surfaces_are_private_nonce_bound_and_read_only(self) -> None:
        ops = OPS.read_text(encoding="utf-8")
        js = OPS_JS.read_text(encoding="utf-8")
        css = OPS_CSS.read_text(encoding="utf-8")

        self.assertIn("add_menu_page(", ops)
        self.assertIn("self::CAPABILITY", ops)
        self.assertIn("is_user_logged_in()", ops)
        self.assertIn("current_user_can( self::CAPABILITY )", ops)
        self.assertIn("wp_create_nonce( 'wp_rest' )", ops)
        self.assertIn("wp_verify_nonce( $nonce, 'wp_rest' )", ops)
        self.assertIn("WP_REST_Server::READABLE", ops)
        self.assertNotIn("WP_REST_Server::CREATABLE", ops)
        self.assertNotIn("WP_REST_Server::EDITABLE", ops)
        self.assertNotIn("WP_REST_Server::DELETABLE", ops)
        self.assertIn("'write_commands_enabled' => false", ops)
        self.assertIn("'chatgpt_login_required' => false", ops)
        self.assertNotIn("signin-with-chatgpt", ops.lower())
        self.assertNotIn("oai-authenticated", ops.lower())

        self.assertIn("credentials: 'same-origin'", js)
        self.assertIn("'X-WP-Nonce': config.nonce", js)
        self.assertIn(".catch(failClosed)", js)
        self.assertIn("payload.write_commands_enabled !== false", js)
        self.assertIn(".c99-ops__hero", css)

    def test_status_fails_closed_on_schema_or_platform_migration_drift(self) -> None:
        ops = OPS.read_text(encoding="utf-8")
        status = ops.split("public static function status_snapshot()", 1)[1].split(
            "private static function module_statuses", 1
        )[0]
        callback = ops.split("public static function rest_status", 1)[1].split(
            "public static function render_page", 1
        )[0]

        self.assertIn("Complete99_Platform::migration_failed()", status)
        self.assertIn("COMPLETE99_PLATFORM_VERSION === $database_version", status)
        self.assertIn("! empty( $schema['ready'] )", status)
        self.assertIn("'complete99_ops_migration_incomplete'", callback)
        self.assertIn("'status' => 503", callback)
        self.assertLess(
            callback.index("empty( $status['ready'] )"),
            callback.index("rest_ensure_response( $status )"),
        )

    def test_private_admin_url_is_not_owned_by_a_public_app_route(self) -> None:
        launch = LAUNCH_CONTENT.read_text(encoding="utf-8")
        frontend = FRONTEND.read_text(encoding="utf-8")

        consumer_keys = launch.split("$consumer_public_keys =", 1)[1].split(";", 1)[0]
        private_keys = launch.split("$private_public_keys =", 1)[1].split(";", 1)[0]
        self.assertNotIn("'app'", consumer_keys)
        self.assertIn("'app'", private_keys)
        self.assertIn("$record['status']         = 'private';", launch)
        self.assertIn("$record['index_eligible'] = false;", launch)

        schema_app = frontend.split("if ( 'app' === $key", 1)[1].split(
            "private static function verified_recipe_schema", 1
        )[0]
        app_tour = frontend.split("private static function render_app_tour", 1)[1].split(
            "private static function render_lead_section", 1
        )[0]
        self.assertIn("Complete99_Settings::app_url( $lang )", schema_app)
        self.assertIn("Complete99_Settings::app_url( $lang )", app_tour)
        self.assertEqual(3, frontend.count("Complete99_Settings::app_url( $lang )"))

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_schema_rejects_wrong_engine_missing_unique_key_and_failed_count(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
class WpdbOpsSchemaStub {{
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public $last_error = '';
    public $metadata = array();
    public $fail_count = false;
    public function prepare($query, ...$args) {{
        return array('query' => $query, 'args' => $args);
    }}
    public function esc_like($value) {{ return (string) $value; }}
    public function get_var($query) {{
        if (is_array($query)) {{
            $sql = (string) ($query['query'] ?? '');
            $value = (string) ($query['args'][0] ?? '');
            if (false !== strpos($sql, 'SELECT option_value')) {{
                return 'complete99_ops_schema_version' === $value
                    ? 'complete99-ops-schema/v1'
                    : null;
            }}
            if (false !== strpos($sql, 'SHOW TABLES LIKE')) {{
                return isset($this->metadata[$value]) ? $value : null;
            }}
        }}
        if (is_string($query) && false !== strpos($query, 'SELECT COUNT(*)')) {{
            if ($this->fail_count) {{
                $this->last_error = 'count failed';
                return false;
            }}
            return '0';
        }}
        return null;
    }}
    public function get_row($prepared, $format = null) {{
        $table = (string) ($prepared['args'][0] ?? '');
        return isset($this->metadata[$table])
            ? array('Engine' => $this->metadata[$table]['engine'])
            : null;
    }}
    public function get_col($query, $column = 0) {{
        preg_match('/`([^`]+)`/', (string) $query, $matches);
        $table = (string) ($matches[1] ?? '');
        return $this->metadata[$table]['columns'] ?? array();
    }}
    public function get_results($query, $format = null) {{
        preg_match('/`([^`]+)`/', (string) $query, $matches);
        $table = (string) ($matches[1] ?? '');
        $rows = array();
        foreach (($this->metadata[$table]['indexes'] ?? array()) as $name => $columns) {{
            foreach (array_values($columns) as $offset => $column) {{
                $rows[] = array(
                    'Key_name' => $name,
                    'Non_unique' => 0,
                    'Seq_in_index' => $offset + 1,
                    'Column_name' => $column,
                );
            }}
        }}
        return $rows;
    }}
}}
$wpdb = new WpdbOpsSchemaStub();
function maybe_unserialize($value) {{ return $value; }}
require {ops_path};
$contract_method = new ReflectionMethod('Complete99_Ops', 'schema_contract');
$contract_method->setAccessible(true);
$status_method = new ReflectionMethod('Complete99_Ops', 'schema_status');
$status_method->setAccessible(true);
$contract = $contract_method->invoke(null);
$tables = Complete99_Ops::table_names();
$valid = array();
foreach ($contract as $key => $requirements) {{
    $valid[$tables[$key]] = array(
        'engine' => 'InnoDB',
        'columns' => $requirements['columns'],
        'indexes' => $requirements['unique_indexes'],
    );
}}
function inspect_case($metadata, $fail_count = false) {{
    global $wpdb, $status_method;
    $wpdb->metadata = $metadata;
    $wpdb->fail_count = $fail_count;
    $wpdb->last_error = '';
    return $status_method->invoke(null);
}}
$wrong_engine = $valid;
$wrong_engine[$tables['tasks']]['engine'] = 'MyISAM';
$xtradb = $valid;
$xtradb[$tables['tasks']]['engine'] = 'XtraDB';
$missing_unique = $valid;
unset($missing_unique[$tables['commands']]['indexes']['command_id']);
$missing_primary = $valid;
unset($missing_primary[$tables['tasks']]['indexes']['primary']);
$results = array(
    'valid' => inspect_case($valid),
    'xtradb' => inspect_case($xtradb),
    'wrong_engine' => inspect_case($wrong_engine),
    'missing_unique' => inspect_case($missing_unique),
    'missing_primary' => inspect_case($missing_primary),
    'failed_count' => inspect_case($valid, true),
);
echo json_encode($results, JSON_THROW_ON_ERROR);
"""
        result = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        statuses = json.loads(result.stdout)
        self.assertTrue(statuses["valid"]["ready"])
        self.assertTrue(statuses["xtradb"]["ready"])
        self.assertFalse(statuses["wrong_engine"]["ready"])
        self.assertEqual("MyISAM", statuses["wrong_engine"]["invalid_engines"]["tasks"])
        self.assertFalse(statuses["missing_unique"]["ready"])
        self.assertEqual(
            ["command_id"],
            statuses["missing_unique"]["invalid_indexes"]["commands"],
        )
        self.assertFalse(statuses["missing_primary"]["ready"])
        self.assertEqual(
            ["primary"], statuses["missing_primary"]["invalid_indexes"]["tasks"]
        )
        self.assertFalse(statuses["failed_count"]["ready"])
        self.assertTrue(statuses["failed_count"]["inspection_failed"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_sqlite_schema_accepts_primary_key_and_autoindex_column_contracts(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
define('DB_ENGINE', 'sqlite');
class SQLiteWpdbOpsStub {{
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public $last_error = '';
    public $metadata = array();
    public $index_map = array();
    public function prepare($query, ...$args) {{ return array('query' => $query, 'args' => $args); }}
    public function get_var($query) {{
        if (is_array($query)) {{
            $sql = (string) ($query['query'] ?? '');
            $value = (string) ($query['args'][0] ?? '');
            if (false !== strpos($sql, 'SELECT option_value')) {{ return 'complete99-ops-schema/v1'; }}
            if (false !== strpos($sql, 'sqlite_master')) {{ return isset($this->metadata[$value]) ? $value : null; }}
        }}
        return is_string($query) && false !== strpos($query, 'SELECT COUNT(*)') ? '0' : null;
    }}
    public function get_results($query, $format = null) {{
        $query = (string) $query;
        if (preg_match('/PRAGMA table_info\\(`([^`]+)`\\)/', $query, $matches)) {{
            $rows = array();
            foreach (($this->metadata[$matches[1]]['columns'] ?? array()) as $column) {{
                $rows[] = array('name' => $column, 'pk' => 'id' === $column ? 1 : 0);
            }}
            return $rows;
        }}
        if (preg_match('/PRAGMA index_list\\(`([^`]+)`\\)/', $query, $matches)) {{
            $rows = array();
            foreach (($this->metadata[$matches[1]]['indexes'] ?? array()) as $name => $columns) {{
                if ('primary' !== $name) {{
                    $index_name = 'sqlite_auto_' . $matches[1] . '_' . $name;
                    $this->index_map[$index_name] = $columns;
                    $rows[] = array('name' => $index_name, 'unique' => 1);
                }}
            }}
            return $rows;
        }}
        if (preg_match('/PRAGMA index_info\\(`([^`]+)`\\)/', $query, $matches)) {{
            $rows = array();
            foreach (array_values($this->index_map[$matches[1]] ?? array()) as $offset => $column) {{
                $rows[] = array('seqno' => $offset, 'name' => $column);
            }}
            return $rows;
        }}
        return array();
    }}
}}
$wpdb = new SQLiteWpdbOpsStub();
function maybe_unserialize($value) {{ return $value; }}
require {ops_path};
$contract_method = new ReflectionMethod('Complete99_Ops', 'schema_contract');
$contract_method->setAccessible(true);
$status_method = new ReflectionMethod('Complete99_Ops', 'schema_status');
$status_method->setAccessible(true);
$contract = $contract_method->invoke(null);
$tables = Complete99_Ops::table_names();
foreach ($contract as $key => $requirements) {{
    $wpdb->metadata[$tables[$key]] = array(
        'columns' => $requirements['columns'],
        'indexes' => $requirements['unique_indexes'],
    );
}}
echo json_encode($status_method->invoke(null), JSON_THROW_ON_ERROR);
"""
        result = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        status = json.loads(result.stdout)
        self.assertTrue(status["ready"], status)
        self.assertEqual([], status["invalid_tables"])
        self.assertEqual([], status["invalid_engines"])
        self.assertEqual([], status["invalid_indexes"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_rest_permission_requires_login_capability_and_valid_nonce(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
class WP_Error {{
    public $code;
    public $data;
    public function __construct($code, $message = '', $data = array()) {{
        $this->code = $code;
        $this->data = $data;
    }}
}}
class WP_REST_Request {{
    private $nonce;
    public function __construct($nonce = '') {{ $this->nonce = $nonce; }}
    public function get_header($name) {{
        return 'X-WP-Nonce' === $name ? $this->nonce : '';
    }}
}}
function is_user_logged_in() {{ return !empty($GLOBALS['logged_in']); }}
function current_user_can($capability) {{
    return 'complete99_view_operations' === $capability && !empty($GLOBALS['has_cap']);
}}
function wp_verify_nonce($nonce, $action) {{
    return 'wp_rest' === $action && 'valid-rest-nonce' === $nonce ? 1 : false;
}}
require {ops_path};
function outcome($value) {{
    return $value instanceof WP_Error
        ? array('code' => $value->code, 'status' => $value->data['status'] ?? 0)
        : $value;
}}
$cases = array();
$GLOBALS['logged_in'] = false;
$GLOBALS['has_cap'] = false;
$cases['anonymous'] = outcome(Complete99_Ops::authorize_status(new WP_REST_Request('')));
$GLOBALS['logged_in'] = true;
$cases['no_capability'] = outcome(Complete99_Ops::authorize_status(new WP_REST_Request('valid-rest-nonce')));
$GLOBALS['has_cap'] = true;
$cases['missing_nonce'] = outcome(Complete99_Ops::authorize_status(new WP_REST_Request('')));
$cases['invalid_nonce'] = outcome(Complete99_Ops::authorize_status(new WP_REST_Request('invalid')));
$cases['authorized'] = outcome(Complete99_Ops::authorize_status(new WP_REST_Request('valid-rest-nonce')));
echo json_encode($cases, JSON_THROW_ON_ERROR);
"""
        result = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        cases = json.loads(result.stdout)
        self.assertEqual(
            {"code": "complete99_ops_authentication_required", "status": 401},
            cases["anonymous"],
        )
        self.assertEqual(
            {"code": "complete99_ops_forbidden", "status": 403},
            cases["no_capability"],
        )
        self.assertEqual(
            {"code": "complete99_ops_invalid_nonce", "status": 403},
            cases["missing_nonce"],
        )
        self.assertEqual(
            {"code": "complete99_ops_invalid_nonce", "status": 403},
            cases["invalid_nonce"],
        )
        self.assertTrue(cases["authorized"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_capability_is_granted_to_administrator_and_proven_from_storage(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
$GLOBALS['roles'] = array(
    'administrator' => array('capabilities' => array('manage_options' => true)),
);
class RoleStub {{
    public function add_cap($capability) {{
        $GLOBALS['roles']['administrator']['capabilities'][$capability] = true;
    }}
}}
class WpdbRoleStub {{
    public $options = 'wp_options';
    public $last_error = '';
    public function get_blog_prefix($blog_id) {{ return 'wp_'; }}
    public function prepare($query, ...$args) {{ return array($query, $args); }}
    public function get_var($prepared) {{ return serialize($GLOBALS['roles']); }}
}}
$wpdb = new WpdbRoleStub();
function get_role($role) {{ return 'administrator' === $role ? new RoleStub() : null; }}
function get_current_blog_id() {{ return 1; }}
function maybe_unserialize($value) {{ return unserialize($value); }}
require {ops_path};
$install = new ReflectionMethod('Complete99_Ops', 'install_capability');
$install->setAccessible(true);
$install->invoke(null);
echo json_encode($GLOBALS['roles'], JSON_THROW_ON_ERROR);
"""
        result = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        roles = json.loads(result.stdout)
        self.assertTrue(
            roles["administrator"]["capabilities"]["complete99_view_operations"]
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_legacy_defaults_upgrade_exactly_and_custom_https_values_survive(self) -> None:
        settings_path = json.dumps(SETTINGS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
define('COMPLETE99_PLATFORM_URL', 'https://complete99.co.il/wp-content/plugins/complete99-platform/');
class WpdbSettingsStub {{
    public $options = 'wp_options';
    public $last_error = '';
    public $values = array();
    public function prepare($query, ...$args) {{ return array('query' => $query, 'args' => $args); }}
    public function get_row($prepared, $format = null) {{
        $name = (string) ($prepared['args'][0] ?? '');
        return array_key_exists($name, $this->values)
            ? array('option_value' => $this->values[$name])
            : null;
    }}
}}
$wpdb = new WpdbSettingsStub();
function admin_url($path = '') {{ return 'https://complete99.co.il/wp-admin/' . ltrim($path, '/'); }}
function set_url_scheme($url, $scheme = null) {{ return preg_replace('/^https?:/i', ($scheme ?: 'https') . ':', $url); }}
function esc_url_raw($url, $protocols = array()) {{
    $url = filter_var((string) $url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {{ return ''; }}
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $protocols && !in_array($scheme, $protocols, true) ? '' : $url;
}}
function wp_parse_url($url, $component = -1) {{ return -1 === $component ? parse_url($url) : parse_url($url, $component); }}
function untrailingslashit($value) {{ return rtrim((string) $value, '/\\\\'); }}
function maybe_unserialize($value) {{ return $value; }}
function update_option($name, $value, $autoload = null) {{
    global $wpdb;
    $wpdb->values[$name] = $value;
    return true;
}}
function add_option($name, $value, $deprecated = '', $autoload = null) {{
    global $wpdb;
    if (array_key_exists($name, $wpdb->values)) {{ return false; }}
    $wpdb->values[$name] = $value;
    return true;
}}
function get_option($name, $fallback = false) {{
    global $wpdb;
    return $wpdb->values[$name] ?? $fallback;
}}
require {settings_path};
function run_case($app, $asset) {{
    global $wpdb;
    $wpdb->values = array(
        Complete99_Settings::OPTION_APP_URL => $app,
        Complete99_Settings::OPTION_ASSET_URL => $asset,
        Complete99_Settings::OPTION_SECRET => '',
    );
    Complete99_Settings::install_defaults();
    return array(
        'app' => $wpdb->values[Complete99_Settings::OPTION_APP_URL],
        'asset' => $wpdb->values[Complete99_Settings::OPTION_ASSET_URL],
    );
}}
$results = array(
    'defaults' => array(
        'app' => Complete99_Settings::default_app_url(),
        'asset' => Complete99_Settings::default_asset_url(),
    ),
    'legacy' => run_case(
        Complete99_Settings::LEGACY_DEFAULT_APP_URL,
        Complete99_Settings::LEGACY_DEFAULT_ASSET_URL
    ),
    'legacy_en' => run_case(
        Complete99_Settings::LEGACY_DEFAULT_APP_URL_EN,
        Complete99_Settings::LEGACY_DEFAULT_ASSET_URL
    ),
    'custom' => run_case(
        'https://owner.example.com/private-ops',
        'https://assets.owner.example.com'
    ),
    'near_legacy' => run_case(
        Complete99_Settings::LEGACY_DEFAULT_APP_URL . '?owner=1',
        Complete99_Settings::LEGACY_DEFAULT_ASSET_URL . '/owner-assets'
    ),
);
echo json_encode($results, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
        result = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        values = json.loads(result.stdout)
        expected_defaults = {
            "app": "https://complete99.co.il/wp-admin/admin.php?page=complete99-os",
            "asset": "https://complete99.co.il/wp-content/plugins/complete99-platform/assets/images/original",
        }
        self.assertEqual(expected_defaults, values["defaults"])
        self.assertEqual(expected_defaults, values["legacy"])
        self.assertEqual(expected_defaults, values["legacy_en"])
        self.assertEqual(
            {
                "app": "https://owner.example.com/private-ops",
                "asset": "https://assets.owner.example.com",
            },
            values["custom"],
        )
        self.assertEqual(
            {
                "app": "https://complete99-public.benben777.chatgpt.site/platform?owner=1",
                "asset": "https://complete99-public.benben777.chatgpt.site/owner-assets",
            },
            values["near_legacy"],
        )

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_plugin_asset_resolver_uses_bundled_webp_and_preserves_custom_base(self) -> None:
        settings_path = json.dumps(SETTINGS.as_posix())
        plugin_dir = json.dumps((PLUGIN.as_posix().rstrip("/") + "/"))
        script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', {plugin_dir});
define('COMPLETE99_PLATFORM_URL', 'https://complete99.co.il/wp-content/plugins/complete99-platform/');
$GLOBALS['asset_option'] = null;
function sanitize_file_name($value) {{
    $value = basename((string) $value);
    return preg_replace('/[^A-Za-z0-9._-]/', '', $value);
}}
function trailingslashit($value) {{ return rtrim((string) $value, '/\\\\') . '/'; }}
function untrailingslashit($value) {{ return rtrim((string) $value, '/\\\\'); }}
function set_url_scheme($url, $scheme = null) {{ return preg_replace('/^https?:/i', ($scheme ?: 'https') . ':', $url); }}
function admin_url($path = '') {{ return 'https://complete99.co.il/wp-admin/' . ltrim($path, '/'); }}
function esc_url_raw($url, $protocols = array()) {{
    $url = filter_var((string) $url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {{ return ''; }}
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $protocols && !in_array($scheme, $protocols, true) ? '' : $url;
}}
function wp_parse_url($url, $component = -1) {{ return -1 === $component ? parse_url($url) : parse_url($url, $component); }}
function get_option($name, $fallback = false) {{
    return Complete99_Settings::OPTION_ASSET_URL === $name && null !== $GLOBALS['asset_option']
        ? $GLOBALS['asset_option']
        : $fallback;
}}
require {settings_path};
$known = 'c99-food-sabich-pita-gallery-2021-wp-v01.jpg';
$unknown = 'c99-food-not-bundled-v01.jpg';
$default_known = Complete99_Settings::owned_asset_url($known);
$default_unknown = Complete99_Settings::owned_asset_url($unknown);
$GLOBALS['asset_option'] = 'https://assets.owner.example.com';
$custom_known = Complete99_Settings::owned_asset_url($known);
echo json_encode(array(
    'default_known' => $default_known,
    'default_unknown' => $default_unknown,
    'custom_known' => $custom_known,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
        result = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=15,
            check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        urls = json.loads(result.stdout)
        self.assertEqual(
            "https://complete99.co.il/wp-content/plugins/complete99-platform/"
            "assets/images/original/c99-food-sabich-pita-gallery-2021-wp-v01.webp",
            urls["default_known"],
        )
        self.assertEqual("", urls["default_unknown"])
        self.assertEqual(
            "https://assets.owner.example.com/assets/original/"
            "c99-food-sabich-pita-gallery-2021-wp-v01.jpg",
            urls["custom_known"],
        )


if __name__ == "__main__":
    unittest.main()
