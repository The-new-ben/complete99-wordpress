from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
PLATFORM = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-platform.php"
)
CONTENT = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-content.php"
)
BRIDGE = ROOT / "deploy" / "temporary-bridge.php"


class MigrationRuntimeContractTests(unittest.TestCase):
    def test_core_activation_and_deactivation_finalize_only_after_option_persistence(self) -> None:
        platform = PLATFORM.read_text(encoding="utf-8")
        self.assertIn("add_action( 'activated_plugin'", platform)
        self.assertIn("add_action( 'deactivated_plugin'", platform)
        self.assertIn("SELECT option_id,option_value FROM {$wpdb->options}", platform)
        self.assertIn("ORDER BY option_id ASC LIMIT 2", platform)
        activation = platform.split("public static function activate()", 1)[1].split(
            "private static function register_lifecycle_shutdown_guard", 1
        )[0]
        self.assertNotIn("complete_activation_recovery", activation)
        self.assertIn("self::$activation_pending_token", activation)
        persisted = platform.split("public static function activation_persisted", 1)[1].split(
            "public static function recover_active_upgrade", 1
        )[0]
        self.assertIn("plugin_activation_is_persisted( true )", persisted)
        self.assertIn("complete_activation_recovery", persisted)
        deactivation = platform.split("public static function deactivate()", 1)[1]
        self.assertIn("begin_deactivation_suspension()", deactivation)
        self.assertIn("is_wp_error( $prepared )", deactivation)
        self.assertIn("plugin_activation_is_persisted( false )", deactivation)
        self.assertIn("complete_deactivation_suspension", deactivation)
        self.assertIn("is_wp_error( $finalized )", deactivation)
        self.assertIn("lifecycle_shutdown_guard", platform)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for the runtime contract")
    def test_core_activation_persistence_gate_executes_against_exact_row(self) -> None:
        platform_path = PLATFORM.as_posix().replace("'", "\\'")
        script = r"""
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');define('COMPLETE99_PLATFORM_FILE','complete99-platform/complete99-platform.php');
class WP_Error{} function is_wp_error($v){return $v instanceof WP_Error;} function plugin_basename($v){return basename(dirname($v)).'/'.basename($v);} function maybe_unserialize($v){$x=@unserialize($v);return false===$x&&'b:0;'!==$v?$v:$x;} function flush_rewrite_rules($hard=true){$GLOBALS['flushed']=true;}
class C99CoreWpdb{public $options='wp_options';public $last_error='';public $rows=array();public function prepare($q,...$a){return array($q,$a);}public function get_results($q,$mode=null){return $this->rows;}}
class Complete99_Campaigns{public static $complete=array();public static $suspend=array();public static function complete_activation_recovery($t){self::$complete[]=$t;return true;}public static function abort_activation_recovery($t){return true;}public static function begin_deactivation_suspension(){self::$suspend[]='begin';return array('token'=>str_repeat('b',64));}public static function complete_deactivation_suspension($t){self::$suspend[]='complete:'.$t;return true;}public static function abort_deactivation_suspension($t){self::$suspend[]='abort:'.$t;return true;}}
$wpdb=new C99CoreWpdb();require '__PLATFORM_PATH__';$gate=new ReflectionMethod('Complete99_Platform','plugin_activation_is_persisted');$gate->setAccessible(true);$token=new ReflectionProperty('Complete99_Platform','activation_pending_token');$token->setAccessible(true);$deact=new ReflectionProperty('Complete99_Platform','deactivation_pending_token');$deact->setAccessible(true);
function row($plugins){return array(array('option_id'=>9,'option_value'=>serialize($plugins)));}
$wpdb->rows=row(array('complete99-platform/complete99-platform.php'));$active=$gate->invoke(null,true);$inactiveFalse=$gate->invoke(null,false);$token->setValue(null,str_repeat('a',64));Complete99_Platform::activation_persisted('complete99-platform/complete99-platform.php',false);$tokenAfter=$token->getValue();
$wpdb->rows=row(array('other/plugin.php'));$inactive=$gate->invoke(null,false);$deact->setValue(null,str_repeat('b',64));Complete99_Platform::deactivation_persisted('complete99-platform/complete99-platform.php',false);$deactAfter=$deact->getValue();
$wpdb->rows=array();$missing=$gate->invoke(null,true);$wpdb->rows=array(array('option_id'=>1,'option_value'=>serialize(array())),array('option_id'=>2,'option_value'=>serialize(array())));$duplicate=$gate->invoke(null,true);$wpdb->rows=array(array('option_id'=>1,'option_value'=>'not-serialized'));$malformed=$gate->invoke(null,true);
echo json_encode(array('active'=>$active,'inactiveFalse'=>$inactiveFalse,'inactive'=>$inactive,'missing'=>$missing,'duplicate'=>$duplicate,'malformed'=>$malformed,'complete'=>Complete99_Campaigns::$complete,'tokenAfter'=>$tokenAfter,'suspend'=>Complete99_Campaigns::$suspend,'deactAfter'=>$deactAfter,'flushed'=>!empty($GLOBALS['flushed'])),JSON_THROW_ON_ERROR);
""".replace("__PLATFORM_PATH__", platform_path)
        result = json.loads(subprocess.run(
            ["php", "-r", script], cwd=ROOT, capture_output=True, text=True,
            encoding="utf-8", timeout=20, check=True,
        ).stdout)
        self.assertTrue(result["active"], result)
        self.assertFalse(result["inactiveFalse"], result)
        self.assertTrue(result["inactive"], result)
        for key in ("missing", "duplicate", "malformed"):
            self.assertFalse(result[key], result)
        self.assertEqual(["a" * 64], result["complete"], result)
        self.assertEqual("", result["tokenAfter"], result)
        self.assertEqual(["complete:" + "b" * 64], result["suspend"], result)
        self.assertEqual("", result["deactAfter"], result)
        self.assertTrue(result["flushed"], result)

    def test_cross_request_lock_is_bounded_and_always_released(self) -> None:
        platform = PLATFORM.read_text(encoding="utf-8")
        migration = platform[platform.index("private static function run_migration") :]

        self.assertIn("const MIGRATION_LOCK_TIMEOUT = 10;", platform)
        self.assertIn("SELECT GET_LOCK(%s, %d)", platform)
        self.assertIn("SELECT RELEASE_LOCK(%s)", platform)
        self.assertIn("flock( $handle, LOCK_EX | LOCK_NB )", platform)
        self.assertIn("microtime( true ) + self::MIGRATION_LOCK_TIMEOUT", platform)
        self.assertIn("finally {", migration)
        self.assertIn("self::release_migration_lock( $lock );", migration)

        acquired = migration.index("$lock = self::acquire_migration_lock();")
        reread = migration.index(
            "$current = self::persisted_option( 'complete99_platform_version' )"
        )
        transaction = migration.index("START TRANSACTION")
        self.assertLess(acquired, reread)
        self.assertLess(reread, transaction)

    def test_deploy_preflight_probes_the_same_advisory_lock_contract(self) -> None:
        bridge = BRIDGE.read_text(encoding="utf-8")

        self.assertIn("$verify_migration_advisory_lock", bridge)
        self.assertIn("'complete99-migration-' . substr(", bridge)
        self.assertIn("SELECT GET_LOCK(%s, %d)", bridge)
        self.assertIn("SELECT RELEASE_LOCK(%s)", bridge)
        self.assertIn("'migration_lock'", bridge)
        self.assertIn("'driver'          => 'filesystem'", bridge)
        self.assertIn("'driver'          => 'mysql'", bridge)

    @unittest.skipUnless(shutil.which("php"), "PHP is required for the runtime contract")
    def test_migration_preserves_runtime_deployment_identity_and_repairs_absence(
        self,
    ) -> None:
        platform_path = json.dumps(PLATFORM.as_posix())
        with tempfile.TemporaryDirectory(prefix="complete99-deployment-id-") as tmp:
            content_dir = json.dumps(Path(tmp).as_posix())
            script = f"""
define('ABSPATH', __DIR__);
define('WP_CONTENT_DIR', {content_dir});
define('DB_ENGINE', 'sqlite');
define('COMPLETE99_PLATFORM_VERSION', '9.9.9');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-wp-9.9.9');

class WP_Error {{}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function trailingslashit($value) {{ return rtrim($value, '/\\\\') . '/'; }}
function get_current_blog_id() {{ return 1; }}
function home_url($path = '/') {{ return 'http://localhost' . $path; }}
function maybe_unserialize($value) {{ return $value; }}
function wp_cache_flush() {{ return true; }}
function flush_rewrite_rules($hard = true) {{ return true; }}
function update_option($name, $value, $autoload = null) {{
    global $wpdb;
    $wpdb->values[$name] = $value;
    return true;
}}

class Complete99_Content {{
    public static function register() {{}}
    public static function register_rewrites() {{}}
    public static function install_roles() {{}}
    public static function seed_launch_content() {{}}
    public static function assert_migration_invariants() {{}}
}}
class Complete99_Leads {{
    public static function register_post_type() {{}}
}}
class Complete99_Settings {{
    public static function install_defaults() {{}}
    public static function assert_defaults() {{}}
}}
class Complete99_Ops {{
    public static function prepare_schema() {{}}
    public static function install() {{}}
    public static function assert_invariants() {{}}
}}
class Complete99_Campaigns {{
    public static function begin_authority_write($source) {{ return true; }}
    public static function end_authority_write() {{ return true; }}
    public static function prepare_schema() {{}}
    public static function install() {{}}
    public static function assert_invariants() {{}}
}}
class Complete99_Commerce {{
    public static function register_product_planning_type() {{}}
}}
class Complete99_Catalog_Graph {{
    public static function register_meta() {{}}
}}
class Complete99_Evaluation_Catalog {{
    const OPTION_RECEIPT = 'complete99_evaluation_catalog_receipt';
    const MODE_PRIVATE_ONLY = 'private_only';
    public static function register_meta() {{}}
    public static function materialize($mode) {{
        update_option(self::OPTION_RECEIPT, array('stub' => 'ready'), false);
        return array('mode' => $mode);
    }}
    public static function persisted_status($receipt) {{
        return array('ready' => is_array($receipt) && 'ready' === ($receipt['stub'] ?? ''));
    }}
}}
class Complete99_Inventory_Bridge {{
    public static function register_meta() {{}}
}}
class Complete99_Culinary_Science {{
    public static function assert_invariants() {{ return true; }}
}}
class Complete99_Culinary_Commerce {{
    public static function assert_invariants() {{ return true; }}
}}

class MigrationWpdbIdentityStub {{
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public $last_error = '';
    public $is_mysql = false;
    public $values = array();
    public function prepare($query, ...$args) {{
        return array('query' => $query, 'args' => $args);
    }}
    public function get_var($prepared) {{
        $name = (string) ($prepared['args'][0] ?? '');
        return array_key_exists($name, $this->values)
            ? $this->values[$name]
            : null;
    }}
    public function query($query) {{ return 1; }}
}}
$wpdb = new MigrationWpdbIdentityStub();
require {platform_path};
$run = new ReflectionMethod('Complete99_Platform', 'run_migration');
$run->setAccessible(true);

$cases = array(
    'dynamic' => 'c99-prod-runtime-123',
    'missing' => null,
    'empty' => '',
);
$results = array();
foreach ($cases as $label => $deployment) {{
    $wpdb->values = array('complete99_platform_version' => '1.0.4');
    if (null !== $deployment) {{
        $wpdb->values['complete99_last_deployment_id'] = $deployment;
    }}
    $result = $run->invoke(null, false);
    $results[$label] = array(
        'ok' => true === $result,
        'version' => $wpdb->values['complete99_platform_version'] ?? '',
        'deployment' => $wpdb->values['complete99_last_deployment_id'] ?? '',
    );
}}
echo json_encode($results, JSON_THROW_ON_ERROR);
"""
            completed = subprocess.run(
                ["php", "-r", script],
                cwd=ROOT,
                capture_output=True,
                text=True,
                encoding="utf-8",
                errors="replace",
                timeout=15,
                check=False,
            )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertEqual(
            {
                "ok": True,
                "version": "9.9.9",
                "deployment": "c99-prod-runtime-123",
            },
            result["dynamic"],
        )
        for label in ("missing", "empty"):
            self.assertEqual(
                {
                    "ok": True,
                    "version": "9.9.9",
                    "deployment": "c99-wp-9.9.9",
                },
                result[label],
            )

    @unittest.skipUnless(shutil.which("php"), "PHP is required for the runtime contract")
    def test_evaluation_invariant_failure_rolls_back_without_version_write(
        self,
    ) -> None:
        platform_path = json.dumps(PLATFORM.as_posix())
        with tempfile.TemporaryDirectory(prefix="complete99-evaluation-rollback-") as tmp:
            content_dir = json.dumps(Path(tmp).as_posix())
            script = f"""
define('ABSPATH', __DIR__);
define('WP_CONTENT_DIR', {content_dir});
define('DB_ENGINE', 'sqlite');
define('COMPLETE99_PLATFORM_VERSION', '9.9.9');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'evaluation-rollback-test');

class WP_Error {{}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function trailingslashit($value) {{ return rtrim($value, '/\\\\') . '/'; }}
function get_current_blog_id() {{ return 1; }}
function home_url($path = '/') {{ return 'http://localhost' . $path; }}
function maybe_unserialize($value) {{ return $value; }}
function wp_cache_flush() {{ return true; }}
function flush_rewrite_rules($hard = true) {{ return true; }}
function update_option($name, $value, $autoload = null) {{
    global $wpdb;
    $wpdb->values[$name] = $value;
    return true;
}}

class Complete99_Content {{
    public static function register() {{}}
    public static function register_rewrites() {{}}
    public static function seed_launch_content() {{}}
    public static function assert_migration_invariants() {{}}
}}
class Complete99_Leads {{
    public static function register_post_type() {{}}
}}
class Complete99_Settings {{
    public static function install_defaults() {{}}
    public static function assert_defaults() {{}}
}}
class Complete99_Ops {{
    public static function prepare_schema() {{}}
    public static function install() {{}}
    public static function assert_invariants() {{}}
}}
class Complete99_Campaigns {{
    public static function begin_authority_write($source) {{ return true; }}
    public static function end_authority_write() {{ return true; }}
    public static function prepare_schema() {{}}
    public static function install() {{}}
    public static function assert_invariants() {{}}
}}
class Complete99_Commerce {{
    public static function register_product_planning_type() {{}}
}}
class Complete99_Catalog_Graph {{
    public static function register_meta() {{}}
}}
class Complete99_Evaluation_Catalog {{
    const OPTION_RECEIPT = 'complete99_evaluation_catalog_receipt';
    const MODE_PRIVATE_ONLY = 'private_only';
    public static function register_meta() {{}}
    public static function materialize($mode) {{
        update_option(self::OPTION_RECEIPT, array('stub' => 'corrupt'), false);
        return array('mode' => $mode);
    }}
    public static function persisted_status($receipt) {{
        return array('ready' => false);
    }}
}}
class Complete99_Inventory_Bridge {{
    public static function register_meta() {{}}
}}

class MigrationEvaluationRollbackWpdb {{
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public $last_error = '';
    public $is_mysql = false;
    public $values = array(
        'complete99_platform_version' => '1.0.4',
        'complete99_last_deployment_id' => 'prior-runtime',
    );
    public $snapshot = array();
    public $queries = array();
    public function prepare($query, ...$args) {{
        return array('query' => $query, 'args' => $args);
    }}
    public function get_var($prepared) {{
        $name = (string) ($prepared['args'][0] ?? '');
        return array_key_exists($name, $this->values)
            ? $this->values[$name]
            : null;
    }}
    public function query($query) {{
        $this->queries[] = $query;
        if ('START TRANSACTION' === $query) {{
            $this->snapshot = $this->values;
        }} elseif ('ROLLBACK' === $query) {{
            $this->values = $this->snapshot;
        }}
        return 1;
    }}
}}
$wpdb = new MigrationEvaluationRollbackWpdb();
require {platform_path};
$run = new ReflectionMethod('Complete99_Platform', 'run_migration');
$run->setAccessible(true);
$result = $run->invoke(null, false);
echo json_encode(array(
    'failed' => is_wp_error($result),
    'queries' => $wpdb->queries,
    'version' => $wpdb->values['complete99_platform_version'] ?? '',
    'receipt_exists' => array_key_exists(
        'complete99_evaluation_catalog_receipt',
        $wpdb->values
    ),
), JSON_THROW_ON_ERROR);
"""
            completed = subprocess.run(
                ["php", "-r", script],
                cwd=ROOT,
                capture_output=True,
                text=True,
                encoding="utf-8",
                errors="replace",
                timeout=15,
                check=False,
            )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertTrue(result["failed"])
        self.assertEqual(["START TRANSACTION", "ROLLBACK"], result["queries"])
        self.assertEqual("1.0.4", result["version"])
        self.assertFalse(result["receipt_exists"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for the runtime contract")
    def test_recipe_provenance_preserves_chef_edits(self) -> None:
        content_path = json.dumps(CONTENT.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
function sanitize_text_field($value) {{
    return trim((string) $value);
}}
function maybe_serialize($value) {{
    return is_array($value) || is_object($value) ? serialize($value) : $value;
}}
require {content_path};

$refresh = new ReflectionMethod('Complete99_Content', 'should_refresh_seed_recipe');
$refresh->setAccessible(true);
$hash = new ReflectionMethod('Complete99_Content', 'recipe_hash');
$hash->setAccessible(true);
$next_provenance = new ReflectionMethod('Complete99_Content', 'recipe_provenance_after_sync');
$next_provenance->setAccessible(true);

$prior_seed = array(
    'yield' => '3',
    'ingredients' => array('tomato', 'olive oil'),
    'instructions' => array('mix'),
);
$seed = array(
    'yield' => '4',
    'ingredients' => array('tomato', 'olive oil'),
    'instructions' => array('mix'),
);
$chef = array(
    'yield' => '6',
    'ingredients' => array('tomato', 'olive oil', 'lemon'),
    'instructions' => array('mix', 'rest'),
);
$seed_hash = $hash->invoke(null, $seed);
$prior_hash = $hash->invoke(null, $prior_seed);
$invalid_provenance_failed = false;
try {{
    $next_provenance->invoke(null, $seed_hash, 'invalid', true, false);
}} catch (RuntimeException $error) {{
    $invalid_provenance_failed = true;
}}
$result = array(
    'absent_recipe' => $refresh->invoke(null, $seed, null, '', false),
    'seed_owned' => $refresh->invoke(null, $seed, $seed, $seed_hash, true),
    'chef_owned' => $refresh->invoke(null, $seed, $chef, $prior_hash, true),
    'legacy_identical' => $refresh->invoke(null, $seed, $seed, '', true),
    'legacy_chef' => $refresh->invoke(null, $seed, $chef, '', true),
    'preserved_provenance' => $next_provenance->invoke(null, $seed_hash, $prior_hash, true, false),
    'refreshed_provenance' => $next_provenance->invoke(null, $seed_hash, $prior_hash, true, true),
    'legacy_baseline' => $next_provenance->invoke(null, $seed_hash, '', false, false),
    'invalid_provenance_failed' => $invalid_provenance_failed,
    'seed_hash' => $seed_hash,
    'prior_hash' => $prior_hash,
);
echo json_encode($result, JSON_THROW_ON_ERROR);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertTrue(result["absent_recipe"])
        self.assertTrue(result["seed_owned"])
        self.assertFalse(result["chef_owned"])
        self.assertTrue(result["legacy_identical"])
        self.assertFalse(result["legacy_chef"])
        self.assertEqual(result["prior_hash"], result["preserved_provenance"])
        self.assertNotEqual(result["seed_hash"], result["preserved_provenance"])
        self.assertEqual(result["seed_hash"], result["refreshed_provenance"])
        self.assertEqual(result["seed_hash"], result["legacy_baseline"])
        self.assertTrue(result["invalid_provenance_failed"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for the runtime contract")
    def test_published_draft_blueprint_seed_remains_published(self) -> None:
        content_path = json.dumps(CONTENT.as_posix())
        script = f"""
define('ABSPATH', __DIR__);
require {content_path};
$required = new ReflectionMethod('Complete99_Content', 'required_seed_status');
$required->setAccessible(true);
$allowed = new ReflectionMethod('Complete99_Content', 'allowed_seed_statuses');
$allowed->setAccessible(true);
$draft = array('status' => 'draft');
$publish = array('status' => 'publish');
echo json_encode(
    array(
        'published_draft_seed' => $required->invoke(null, $draft, 'publish'),
        'private_draft_seed' => $required->invoke(null, $draft, 'private'),
        'pending_draft_seed' => $required->invoke(null, $draft, 'pending'),
        'future_draft_seed' => $required->invoke(null, $draft, 'future'),
        'trashed_draft_seed' => $required->invoke(null, $draft, 'trash'),
        'draft_publish_seed' => $required->invoke(null, $publish, 'draft'),
        'draft_allowed' => $allowed->invoke(null, $draft),
        'publish_allowed' => $allowed->invoke(null, $publish),
    ),
    JSON_THROW_ON_ERROR
);
"""
        completed = subprocess.run(
            ["php", "-r", script],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=10,
            check=False,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertEqual("publish", result["published_draft_seed"])
        self.assertEqual("private", result["private_draft_seed"])
        self.assertEqual("draft", result["pending_draft_seed"])
        self.assertEqual("draft", result["future_draft_seed"])
        self.assertEqual("draft", result["trashed_draft_seed"])
        self.assertEqual("publish", result["draft_publish_seed"])
        self.assertEqual(["draft", "private", "publish"], result["draft_allowed"])
        self.assertEqual(["publish", "private"], result["publish_allowed"])

    @unittest.skipUnless(shutil.which("php"), "PHP is required for the runtime contract")
    def test_migration_failure_rolls_back_and_releases_file_lock(self) -> None:
        platform_path = json.dumps(PLATFORM.as_posix())
        with tempfile.TemporaryDirectory(prefix="complete99-migration-lock-") as tmp:
            content_dir = json.dumps(Path(tmp).as_posix())
            script = f"""
define('ABSPATH', __DIR__);
define('WP_CONTENT_DIR', {content_dir});
define('DB_ENGINE', 'sqlite');
define('COMPLETE99_PLATFORM_VERSION', '9.9.9');
define('COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'runtime-failure-test');

class WP_Error {{
    public $code;
    public function __construct($code, $message = '') {{
        $this->code = $code;
    }}
}}
function is_wp_error($value) {{ return $value instanceof WP_Error; }}
function trailingslashit($value) {{ return rtrim($value, '/\\\\') . '/'; }}
function get_current_blog_id() {{ return 1; }}
function home_url($path = '/') {{ return 'http://localhost' . $path; }}
function maybe_unserialize($value) {{ return $value; }}
function wp_cache_flush() {{ return true; }}

class Complete99_Content {{
    public static function register() {{
        throw new RuntimeException('injected-migration-failure');
    }}
}}
class Complete99_Leads {{}}
class Complete99_Settings {{}}
class Complete99_Ops {{
    public static function prepare_schema() {{}}
}}
class Complete99_Campaigns {{
    public static function begin_authority_write($source) {{ return true; }}
    public static function end_authority_write() {{ return true; }}
    public static function prepare_schema() {{}}
}}

class MigrationWpdbStub {{
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public $last_error = '';
    public $is_mysql = false;
    public $queries = array();
    public function prepare($query, ...$args) {{ return $query; }}
    public function get_var($query) {{ return '0.0.0'; }}
    public function query($query) {{
        $this->queries[] = $query;
        return 0;
    }}
}}
$wpdb = new MigrationWpdbStub();
require {platform_path};

$run = new ReflectionMethod('Complete99_Platform', 'run_migration');
$run->setAccessible(true);
$result = $run->invoke(null, false);
$lock_path = trailingslashit(WP_CONTENT_DIR) . '.complete99-platform-migration.lock';
$probe = fopen($lock_path, 'c+');
$lock_available = flock($probe, LOCK_EX | LOCK_NB);
if ($lock_available) {{
    flock($probe, LOCK_UN);
}}
fclose($probe);

echo json_encode(
    array(
        'failed_closed' => is_wp_error($result),
        'queries' => $wpdb->queries,
        'lock_available' => $lock_available,
    ),
    JSON_THROW_ON_ERROR
);
"""
            completed = subprocess.run(
                ["php", "-r", script],
                cwd=ROOT,
                capture_output=True,
                text=True,
                encoding="utf-8",
                errors="replace",
                timeout=10,
                check=False,
            )
        self.assertEqual(0, completed.returncode, completed.stderr)
        result = json.loads(completed.stdout)
        self.assertTrue(result["failed_closed"])
        self.assertEqual(["START TRANSACTION", "ROLLBACK"], result["queries"])
        self.assertNotIn("COMMIT", result["queries"])
        self.assertTrue(result["lock_available"])


if __name__ == "__main__":
    unittest.main()
