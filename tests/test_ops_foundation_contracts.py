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

    def test_admin_status_stays_private_while_campaign_slice_is_operational(self) -> None:
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
        self.assertEqual(1, ops.count("WP_REST_Server::CREATABLE"))
        self.assertNotIn("WP_REST_Server::EDITABLE", ops)
        self.assertNotIn("WP_REST_Server::DELETABLE", ops)
        self.assertIn("const WORKER_ROLE           = 'complete99_campaign_worker';", ops)
        self.assertIn("const WORKER_CAPABILITY     = 'complete99_run_campaign_worker';", ops)
        self.assertIn("const WORKER_ROUTE          = '/ops/campaign-worker';", ops)
        self.assertIn("const WORKER_INTERVAL       = 900;", ops)
        self.assertIn("const WORKER_MAX_AGE        = 4500;", ops)
        self.assertIn("'application_password_did_authenticate'", ops)
        self.assertIn("array( __CLASS__, 'authorize_campaign_worker' )", ops)
        self.assertIn("array( __CLASS__, 'rest_campaign_worker' )", ops)
        self.assertIn("Complete99_Campaigns::reconcile_schedules()", ops)
        self.assertIn("'begin_worker_execution_fence'", ops)
        self.assertIn("'end_worker_execution_fence'", ops)
        self.assertIn("'worker_execution_fence_lock_name'", ops)
        self.assertIn("'worker_quiescence_status'", ops)
        self.assertIn("wp_set_current_user( 0 )", ops)
        self.assertIn("wp_set_current_user( $authenticated_user_id )", ops)
        self.assertIn("'no-store, private, max-age=0'", ops)
        self.assertIn("'write_commands_enabled' => $ready && $campaign_write", ops)
        self.assertIn("current_user_can( $capability )", ops)
        self.assertIn("Complete99_Campaigns::operational_status()", ops)
        self.assertIn("'chatgpt_login_required' => false", ops)
        self.assertNotIn("signin-with-chatgpt", ops.lower())
        self.assertNotIn("oai-authenticated", ops.lower())

        self.assertIn("credentials: 'same-origin'", js)
        self.assertIn("'X-WP-Nonce': config.nonce", js)
        self.assertIn(".catch(failClosed)", js)
        self.assertIn("payload.write_commands_enabled !== true", js)
        self.assertIn("payload.campaigns.ready !== true", js)
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
        self.assertIn("! empty( $worker_role['ready'] )", status)
        self.assertIn("! empty( $campaign['ready'] )", status)
        self.assertIn("$campaign_operational_ready = $ready && $campaign_ready", status)
        self.assertIn("$campaign_operational_view  = $campaign_operational_ready && $campaign_view", status)
        self.assertIn("self::module_statuses( $campaign_operational_ready, $campaign_operational_view )", status)
        self.assertIn("'complete99_ops_migration_incomplete'", callback)
        self.assertIn("'status' => 503", callback)
        self.assertLess(
            callback.index("empty( $status['ready'] )"),
            callback.index("rest_ensure_response( $status )"),
        )

        def projected(migration_failed: bool, version_match: bool, schema_ready: bool) -> tuple[bool, bool, bool]:
            overall_ready = not migration_failed and version_match and schema_ready and True
            module_ready = overall_ready and True
            module_view = module_ready and True
            module_write = overall_ready and True
            return module_ready, module_view, module_write

        for prerequisites in (
            (True, True, True),
            (False, False, True),
            (False, True, False),
        ):
            with self.subTest(prerequisites=prerequisites):
                self.assertEqual((False, False, False), projected(*prerequisites))

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
        self.assertIs(True, cases["authorized"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_campaign_worker_requires_core_app_password_and_exact_current_site_role(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
class WP_Error {{
    public $code;
    public $data;
    public function __construct($code, $message = '', $data = array()) {{
        $this->code = $code;
        $this->data = $data;
    }}
}}
class WP_REST_Request {{}}
class WpdbWorkerAuthStub {{
    public $options = 'wp_options';
    public $usermeta = 'wp_usermeta';
    public $blogs = 'wp_blogs';
    public $base_prefix = 'wp_';
    public $last_error = '';
    public function get_blog_prefix($blog_id) {{ return 2 === (int) $blog_id ? 'wp_2_' : 'wp_'; }}
    public function prepare($query, ...$args) {{ return array('query' => $query, 'args' => $args); }}
    public function get_var($prepared) {{ return serialize($GLOBALS['roles']); }}
    public function get_results($prepared, $format = null) {{
        if (false !== strpos((string) ($prepared['query'] ?? ''), 'FROM wp_blogs b INNER JOIN')) {{
            $GLOBALS['membership_query'] = (string) $prepared['query'];
            return $GLOBALS['membership_rows'];
        }}
        return $GLOBALS['assignment_rows'];
    }}
}}
$wpdb = new WpdbWorkerAuthStub();
$GLOBALS['roles'] = array(
    'administrator' => array('capabilities' => array('complete99_view_operations' => true)),
    'complete99_campaign_worker' => array('capabilities' => array(
        'read' => true,
        'complete99_run_campaign_worker' => true,
    )),
);
$GLOBALS['current_user'] = (object) array(
    'ID' => 0,
    'user_status' => 0,
    'spam' => 0,
    'deleted' => 0,
);
$GLOBALS['runtime_cap'] = true;
$GLOBALS['super_admin'] = false;
$GLOBALS['assignment_rows'] = array();
$GLOBALS['membership_rows'] = array();
$GLOBALS['membership_query'] = '';
function absint($value) {{ return abs((int) $value); }}
function get_current_blog_id() {{ return 2; }}
function is_multisite() {{ return true; }}
function maybe_unserialize($value) {{ return unserialize($value); }}
function is_user_logged_in() {{ return 0 < (int) $GLOBALS['current_user']->ID; }}
function wp_get_current_user() {{ return $GLOBALS['current_user']; }}
function current_user_can($capability) {{
    return 'complete99_run_campaign_worker' === $capability && !empty($GLOBALS['runtime_cap']);
}}
function is_super_admin($user_id = null) {{ return !empty($GLOBALS['super_admin']); }}
require {ops_path};
function worker_outcome($value) {{
    return $value instanceof WP_Error
        ? array('code' => $value->code, 'status' => $value->data['status'] ?? 0)
        : $value;
}}
function set_worker($assignment, $status = 0, $spam = 0, $deleted = 0) {{
    $GLOBALS['current_user'] = (object) array(
        'ID' => 42,
        'user_status' => $status,
        'spam' => $spam,
        'deleted' => $deleted,
    );
    $GLOBALS['assignment_rows'] = array(array(
        'umeta_id' => 9,
        'meta_value' => serialize($assignment),
    ));
    $GLOBALS['membership_rows'] = array(array('blog_id' => 2, 'umeta_id' => 9));
}}
function mark_application_password($user_id) {{
    Complete99_Ops::note_application_password_authentication(
        (object) array('ID' => $user_id),
        array('uuid' => '123e4567-e89b-42d3-a456-426614174000')
    );
}}
$request = new WP_REST_Request();
$cases = array();
Complete99_Ops::note_application_password_authentication(null, array());
$cases['anonymous'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true));
Complete99_Ops::note_application_password_authentication(null, array());
$cases['cookie_only'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
mark_application_password(43);
$cases['marker_mismatch'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
mark_application_password(42);
set_worker(array('subscriber' => true));
$cases['wrong_role'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true, 'edit_posts' => true));
$cases['direct_grant'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true));
$GLOBALS['assignment_rows'][] = $GLOBALS['assignment_rows'][0];
$cases['duplicate_assignment'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true), 1);
$cases['user_status'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true), 0, 1, 0);
$cases['spam'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true), 0, 0, 1);
$cases['deleted'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true));
$GLOBALS['membership_rows'][] = array('blog_id' => 1, 'umeta_id' => 10);
$cases['other_site_membership'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true));
$GLOBALS['membership_rows'] = array(array('blog_id' => 1, 'umeta_id' => 10));
$cases['wrong_site_membership'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
set_worker(array('complete99_campaign_worker' => true));
$GLOBALS['super_admin'] = true;
$cases['super_admin'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
$GLOBALS['super_admin'] = false;
$GLOBALS['runtime_cap'] = false;
$cases['runtime_cap_missing'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
$GLOBALS['runtime_cap'] = true;
$cases['authorized'] = worker_outcome(Complete99_Ops::authorize_campaign_worker($request));
echo json_encode(array(
    'cases' => $cases,
    'membershipQuery' => $GLOBALS['membership_query'],
), JSON_THROW_ON_ERROR);
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
        payload = json.loads(result.stdout)
        cases = payload["cases"]
        self.assertEqual(
            {"code": "complete99_campaign_worker_authentication_required", "status": 401},
            cases["anonymous"],
        )
        for name in (
            "cookie_only",
            "marker_mismatch",
            "wrong_role",
            "direct_grant",
            "duplicate_assignment",
            "user_status",
            "spam",
            "deleted",
            "other_site_membership",
            "wrong_site_membership",
            "super_admin",
            "runtime_cap_missing",
        ):
            with self.subTest(name=name):
                self.assertEqual(
                    {"code": "complete99_campaign_worker_forbidden", "status": 403},
                    cases[name],
                )
        self.assertTrue(cases["authorized"])
        self.assertIn("FROM wp_blogs b INNER JOIN", payload["membershipQuery"])
        self.assertIn("ORDER BY b.blog_id ASC,um.umeta_id ASC LIMIT 2", payload["membershipQuery"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_campaign_worker_runs_as_system_restores_user_and_requires_fresh_heartbeat(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
define('ARRAY_A', 'ARRAY_A');
define('COMPLETE99_PLATFORM_VERSION', '1.22.0');
class WP_Error {{
    public function __construct($code = '', $message = '', $data = array()) {{}}
}}
class WP_REST_Request {{
    public function get_param($name) {{ ++$GLOBALS['parameters_read']; return 'untrusted'; }}
}}
class WP_REST_Response {{
    public $data;
    public $status;
    public $headers = array();
    public function __construct($data, $status = 200) {{
        $this->data = $data;
        $this->status = $status;
    }}
    public function header($name, $value) {{ $this->headers[$name] = $value; }}
}}
class WpdbWorkerRouteStub {{
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public $last_error = '';
    public $metadata = array();
    public function prepare($query, ...$args) {{ return array('query' => $query, 'args' => $args); }}
    public function esc_like($value) {{ return (string) $value; }}
    public function get_blog_prefix($blog_id) {{ return 'wp_'; }}
    public function get_var($query) {{
        if (is_array($query)) {{
            $sql = (string) ($query['query'] ?? '');
            $value = (string) ($query['args'][0] ?? '');
            if (false !== strpos($sql, 'SELECT option_value')) {{
                if ('complete99_ops_schema_version' === $value) {{
                    return 'ops_schema_missing' === Complete99_Campaigns::$mode
                        ? 'stale-ops-schema'
                        : 'complete99-ops-schema/v1';
                }}
                if ('wp_user_roles' === $value) {{
                    $roles = $GLOBALS['worker_roles'];
                    if ('worker_role_missing' === Complete99_Campaigns::$mode) {{
                        unset($roles['complete99_campaign_worker']);
                    }}
                    return serialize($roles);
                }}
                return null;
            }}
            if (false !== strpos($sql, 'SHOW TABLES LIKE')) {{
                return isset($this->metadata[$value]) ? $value : null;
            }}
        }}
        return is_string($query) && false !== strpos($query, 'SELECT COUNT(*)') ? '0' : null;
    }}
    public function get_row($prepared, $format = null) {{
        $table = (string) ($prepared['args'][0] ?? '');
        return isset($this->metadata[$table])
            ? array('Engine' => $this->metadata[$table]['engine'])
            : null;
    }}
    public function get_col($query, $column = 0) {{
        preg_match('/`([^`]+)`/', (string) $query, $matches);
        return $this->metadata[(string) ($matches[1] ?? '')]['columns'] ?? array();
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
$wpdb = new WpdbWorkerRouteStub();
$GLOBALS['worker_roles'] = array(
    'administrator' => array('capabilities' => array('complete99_view_operations' => true)),
    'complete99_campaign_worker' => array('capabilities' => array(
        'read' => true,
        'complete99_run_campaign_worker' => true,
    )),
);
class Complete99_Platform {{
    public static function migration_failed() {{ return false; }}
}}
class Complete99_Campaigns {{
    const SCHEMA_VERSION = 'complete99-campaign-schema/v1';
    const SYSTEM_CRON_INTERVAL_SECONDS = 900;
    const CRON_HEARTBEAT_MAX_AGE = 4500;
    public static $mode = 'success';
    public static function begin_worker_execution_fence($deployment_owner = false) {{
        ++$GLOBALS['unexpected_fence_api_calls'];
        return array('ready' => true);
    }}
    public static function end_worker_execution_fence() {{
        ++$GLOBALS['unexpected_fence_api_calls'];
        return true;
    }}
    public static function worker_execution_fence_lock_name() {{
        return 'c99_campaign_worker_contract_lock';
    }}
    public static function worker_quiescence_status() {{
        ++$GLOBALS['quiescence_checks'];
        return 'deploy_locked' === self::$mode
            ? new WP_Error('complete99_campaign_worker_deploy_locked')
            : array('ready' => true);
    }}
    public static function operational_status() {{
        $heartbeat = array(
            'ready' => true,
            'inspectable' => true,
            'lastAt' => gmdate('Y-m-d\\TH:i:s\\Z'),
            'ageSeconds' => 0,
            'maxAgeSeconds' => 4500,
        );
        if ('stale' === self::$mode) {{
            $heartbeat['ready'] = false;
            $heartbeat['ageSeconds'] = 4501;
        }}
        return array(
            'schema_version' => self::SCHEMA_VERSION,
            'capabilities_ready' => true,
            'capacity' => array(
                'ready' => true,
                'writeReady' => false,
                'inspectable' => true,
                'publicEventAllocationReady' => false,
                'cohorts' => array(
                    'operations' => array('writeReady' => true),
                    'campaign' => array('writeReady' => 'no_headroom' !== self::$mode),
                ),
            ),
            'cron_runner' => $heartbeat,
        );
    }}
    public static function reconcile_schedules() {{
        ++$GLOBALS['reconcile_calls'];
        if ('fence_refusal' === self::$mode) {{
            return new WP_Error('complete99_campaign_worker_fence_busy');
        }}
        $GLOBALS['worker_actor'] = get_current_user_id();
        ++$GLOBALS['worker_mutations'];
        if ('throw' === self::$mode) {{ throw new RuntimeException('private detail'); }}
        return in_array(self::$mode, array('error', 'release_failure'), true)
            ? new WP_Error('complete99_campaign_worker_unavailable')
            : true;
    }}
}}
$GLOBALS['current_user_id'] = 42;
$GLOBALS['worker_actor'] = null;
$GLOBALS['worker_mutations'] = 0;
$GLOBALS['reconcile_calls'] = 0;
$GLOBALS['quiescence_checks'] = 0;
$GLOBALS['unexpected_fence_api_calls'] = 0;
$GLOBALS['parameters_read'] = 0;
function get_option($name, $fallback = false) {{
    return 'complete99_platform_version' === $name ? COMPLETE99_PLATFORM_VERSION : $fallback;
}}
function get_current_user_id() {{ return (int) $GLOBALS['current_user_id']; }}
function get_current_blog_id() {{ return 1; }}
function maybe_unserialize($value) {{
    return is_string($value) && 1 === preg_match('/^[aObisCdN]:/', $value)
        ? unserialize($value)
        : $value;
}}
function wp_set_current_user($user_id) {{
    $GLOBALS['current_user_id'] = (int) $user_id;
    return (object) array('ID' => (int) $user_id);
}}
require {ops_path};
$contract_method = new ReflectionMethod('Complete99_Ops', 'schema_contract');
$contract_method->setAccessible(true);
$contract = $contract_method->invoke(null);
$tables = Complete99_Ops::table_names();
foreach ($contract as $key => $requirements) {{
    $wpdb->metadata[$tables[$key]] = array(
        'engine' => 'InnoDB',
        'columns' => $requirements['columns'],
        'indexes' => $requirements['unique_indexes'],
    );
}}
function invoke_worker($mode) {{
    Complete99_Campaigns::$mode = $mode;
    $GLOBALS['current_user_id'] = 42;
    $GLOBALS['worker_actor'] = null;
    $GLOBALS['worker_mutations'] = 0;
    $GLOBALS['reconcile_calls'] = 0;
    $GLOBALS['quiescence_checks'] = 0;
    $GLOBALS['unexpected_fence_api_calls'] = 0;
    $GLOBALS['parameters_read'] = 0;
    $response = Complete99_Ops::rest_campaign_worker(new WP_REST_Request());
    return array(
        'status' => $response->status,
        'data' => $response->data,
        'headers' => $response->headers,
        'actor' => $GLOBALS['worker_actor'],
        'mutations' => $GLOBALS['worker_mutations'],
        'reconcileCalls' => $GLOBALS['reconcile_calls'],
        'quiescenceChecks' => $GLOBALS['quiescence_checks'],
        'unexpectedFenceApiCalls' => $GLOBALS['unexpected_fence_api_calls'],
        'restored' => $GLOBALS['current_user_id'],
        'parametersRead' => $GLOBALS['parameters_read'],
    );
}}
echo json_encode(array(
    'success' => invoke_worker('success'),
    'error' => invoke_worker('error'),
    'stale' => invoke_worker('stale'),
    'throw' => invoke_worker('throw'),
    'no_headroom' => invoke_worker('no_headroom'),
    'deploy_locked' => invoke_worker('deploy_locked'),
    'fence_refusal' => invoke_worker('fence_refusal'),
    'release_failure' => invoke_worker('release_failure'),
    'ops_schema_missing' => invoke_worker('ops_schema_missing'),
    'worker_role_missing' => invoke_worker('worker_role_missing'),
), JSON_THROW_ON_ERROR);
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
        success = cases["success"]
        self.assertEqual(200, success["status"])
        self.assertEqual(
            {
                "schemaVersion": "complete99-campaign-worker-monitor/v1",
                "workerCompleted": True,
                "cronRunner": {
                    "ready": True,
                    "inspectable": True,
                    "lastAt": success["data"]["cronRunner"]["lastAt"],
                    "ageSeconds": 0,
                    "maxAgeSeconds": 4500,
                },
            },
            success["data"],
        )
        self.assertEqual(0, success["actor"])
        self.assertEqual(1, success["mutations"])
        self.assertEqual(1, success["reconcileCalls"])
        self.assertEqual(1, success["quiescenceChecks"])
        self.assertEqual(0, success["unexpectedFenceApiCalls"])
        self.assertEqual(42, success["restored"])
        self.assertEqual(0, success["parametersRead"])
        self.assertEqual(
            "no-store, private, max-age=0", success["headers"]["Cache-Control"]
        )
        for name in ("error", "stale", "throw", "release_failure"):
            with self.subTest(name=name):
                self.assertEqual(503, cases[name]["status"])
                self.assertEqual(
                    {
                        "schemaVersion": "complete99-campaign-worker-monitor/v1",
                        "workerCompleted": False,
                        "state": "unavailable",
                    },
                    cases[name]["data"],
                )
                self.assertEqual(0, cases[name]["actor"])
                self.assertEqual(1, cases[name]["mutations"])
                self.assertEqual(1, cases[name]["reconcileCalls"])
                self.assertEqual(42, cases[name]["restored"])
                self.assertEqual(0, cases[name]["parametersRead"])
        for name in (
            "no_headroom",
            "deploy_locked",
            "ops_schema_missing",
            "worker_role_missing",
        ):
            with self.subTest(name=name):
                self.assertEqual(503, cases[name]["status"])
                self.assertIsNone(cases[name]["actor"])
                self.assertEqual(0, cases[name]["mutations"])
                self.assertEqual(0, cases[name]["reconcileCalls"])
                self.assertEqual(42, cases[name]["restored"])
                self.assertEqual(0, cases[name]["parametersRead"])
        self.assertEqual(503, cases["fence_refusal"]["status"])
        self.assertIsNone(cases["fence_refusal"]["actor"])
        self.assertEqual(0, cases["fence_refusal"]["mutations"])
        self.assertEqual(1, cases["fence_refusal"]["reconcileCalls"])
        self.assertEqual(1, cases["deploy_locked"]["quiescenceChecks"])
        self.assertEqual(0, cases["ops_schema_missing"]["quiescenceChecks"])
        for case in cases.values():
            self.assertEqual(0, case["unexpectedFenceApiCalls"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required")
    def test_capability_is_granted_to_administrator_and_proven_from_storage(self) -> None:
        ops_path = json.dumps(OPS.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
$GLOBALS['roles'] = array(
    'administrator' => array('capabilities' => array('manage_options' => true)),
);
class RoleStub {{
    public $capabilities;
    private $role;
    public function __construct($role) {{
        $this->role = $role;
        $this->capabilities = $GLOBALS['roles'][$role]['capabilities'];
    }}
    public function add_cap($capability, $grant = true) {{
        $GLOBALS['roles'][$this->role]['capabilities'][$capability] = (bool) $grant;
        $this->capabilities = $GLOBALS['roles'][$this->role]['capabilities'];
    }}
    public function remove_cap($capability) {{
        unset($GLOBALS['roles'][$this->role]['capabilities'][$capability]);
        $this->capabilities = $GLOBALS['roles'][$this->role]['capabilities'];
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
function get_role($role) {{ return isset($GLOBALS['roles'][$role]) ? new RoleStub($role) : null; }}
function add_role($role, $label, $capabilities) {{
    $GLOBALS['roles'][$role] = array('name' => $label, 'capabilities' => $capabilities);
    return new RoleStub($role);
}}
function __($value, $domain = null) {{ return $value; }}
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
        self.assertNotIn(
            "complete99_run_campaign_worker",
            roles["administrator"]["capabilities"],
        )
        self.assertEqual(
            {"read": True, "complete99_run_campaign_worker": True},
            roles["complete99_campaign_worker"]["capabilities"],
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
