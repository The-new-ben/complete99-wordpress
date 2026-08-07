from __future__ import annotations

import json
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
STUDIO = PLUGIN / "includes" / "class-complete99-entity-studio.php"
BOOTSTRAP = PLUGIN / "complete99-platform.php"
PLATFORM = PLUGIN / "includes" / "class-complete99-platform.php"


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


def _run_php(script: str) -> str:
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=30,
    )
    return completed.stdout


def _entity_studio_persistence_prelude() -> str:
    """Return a deterministic in-memory WordPress runtime for write-contract tests."""
    source = r'''
    define('ABSPATH', __DIR__);
    define('OBJECT', 'OBJECT');
    define('COMPLETE99_PLATFORM_DIR', sys_get_temp_dir() . '/complete99-entity-studio-empty/');
    define('WP_CONTENT_DIR', sys_get_temp_dir());
    @mkdir(COMPLETE99_PLATFORM_DIR, 0777, true);
    @mkdir(COMPLETE99_PLATFORM_DIR . 'data/', 0777, true);
    @unlink(COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php');
    @unlink(COMPLETE99_PLATFORM_DIR . 'data/live-catalog-prices.php');

    class WP_Error {
        private $code;
        private $message;
        private $data;
        public function __construct($code, $message, $data = array()) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data() { return $this->data; }
    }
    class WP_Post {
        public $ID = 0;
        public $post_type = '';
        public $post_status = '';
        public $post_title = '';
        public $post_name = '';
        public $post_content = '';
        public function __construct($values = array()) {
            foreach ($values as $key => $value) { $this->$key = $value; }
        }
    }
    class Complete99_Culinary_Science {
        public static function registry($fresh = false) {
            return array(
                'schema' => 'complete99-science-test/v1',
                'version' => 'test-science-1',
                'entities' => array(),
            );
        }
    }
    class Complete99_Culinary_Commerce {
        public static function registry($fresh = false) {
            return array(
                'schema' => 'complete99-commerce-test/v1',
                'version' => $GLOBALS['c99_commerce_version'] ?? 'test-commerce-1',
                'markets' => array(
                    array('id' => 'market-il-launch', 'label' => array('he' => 'ישראל', 'en' => 'Israel')),
                ),
                'channels' => array(
                    array('id' => 'channel-woo-web-il', 'label' => array('he' => 'אתר', 'en' => 'Web')),
                ),
                'currencies' => array(
                    array('id' => 'currency-ils', 'code' => 'ILS', 'minor_unit_digits' => 2),
                    array('id' => 'currency-jpy', 'code' => 'JPY', 'minor_unit_digits' => 0),
                ),
                'products' => !empty($GLOBALS['c99_products_disabled']) ? array() : array(
                    array(
                        'id' => 'product-one',
                        'name' => array('he' => 'מוצר אחד', 'en' => 'Product one'),
                        'knowledge_entity_id' => 'ingredient-one',
                        'taxonomy_ids' => array('premium-ingredient'),
                        'state' => 'candidate',
                    ),
                    array(
                        'id' => 'product-two',
                        'name' => array('he' => 'מוצר שני', 'en' => 'Product two'),
                        'knowledge_entity_id' => 'equipment-two',
                        'taxonomy_ids' => array('professional-equipment'),
                        'state' => 'candidate',
                    ),
                ),
                'variants' => array(
                    array('id' => 'variant-one', 'product_id' => 'product-one'),
                    array('id' => 'variant-two', 'product_id' => 'product-two'),
                ),
                'skus' => array(
                    array('id' => 'sku-one', 'variant_id' => 'variant-one'),
                    array('id' => 'sku-two', 'variant_id' => 'variant-two'),
                ),
                'market_observations' => array(
                    array(
                        'id' => $GLOBALS['c99_observation_one_id'] ?? 'observation-one',
                        'sku_id' => 'sku-one',
                        'market_id' => 'market-il-launch',
                        'currency_id' => 'currency-ils',
                        'state' => 'recorded',
                    ),
                    array(
                        'id' => 'observation-two',
                        'sku_id' => 'sku-two',
                        'market_id' => 'market-il-launch',
                        'currency_id' => 'currency-ils',
                        'state' => 'recorded',
                    ),
                ),
            );
        }
    }

    $GLOBALS['c99_posts'] = array();
    $GLOBALS['c99_meta'] = array();
    $GLOBALS['c99_next_post_id'] = 1;
    $GLOBALS['c99_uuid_counter'] = 1;
    $GLOBALS['c99_fail_meta_key'] = '';
    $GLOBALS['c99_corrupt_get_post'] = false;
    $GLOBALS['c99_get_posts_calls'] = array();
    $GLOBALS['c99_get_var_queries'] = array();
    $GLOBALS['c99_lock_result'] = 1;
    $GLOBALS['c99_manage_allowed'] = true;
    $GLOBALS['c99_revisions'] = array();
    $GLOBALS['c99_cleaned_post_ids'] = array();
    $GLOBALS['c99_deleted_cache_keys'] = array();
    $GLOBALS['c99_commerce_version'] = 'test-commerce-1';
    $GLOBALS['c99_products_disabled'] = false;
    $GLOBALS['c99_observation_one_id'] = 'observation-one';

    class C99_Test_WPDB {
        public $queries = array();
        private $snapshot = null;
        public function prepare($query, ...$args) { return $query; }
        public function get_var($query) {
            $GLOBALS['c99_get_var_queries'][] = $query;
            return false !== strpos($query, 'GET_LOCK') ? $GLOBALS['c99_lock_result'] : 1;
        }
        public function query($query) {
            $this->queries[] = $query;
            if ('START TRANSACTION' === $query) {
                $this->snapshot = array(
                    'posts' => unserialize(serialize($GLOBALS['c99_posts'])),
                    'meta' => unserialize(serialize($GLOBALS['c99_meta'])),
                    'next_id' => $GLOBALS['c99_next_post_id'],
                );
            } elseif ('ROLLBACK' === $query && is_array($this->snapshot)) {
                $GLOBALS['c99_posts'] = $this->snapshot['posts'];
                $GLOBALS['c99_meta'] = $this->snapshot['meta'];
                $GLOBALS['c99_next_post_id'] = $this->snapshot['next_id'];
                $this->snapshot = null;
            } elseif ('COMMIT' === $query) {
                $this->snapshot = null;
            }
            return 1;
        }
    }
    $GLOBALS['wpdb'] = new C99_Test_WPDB();

    function sanitize_key($value) {
        return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value));
    }
    function sanitize_title($value) {
        return trim(strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $value)), '-');
    }
    function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
    function sanitize_file_name($value) { return preg_replace('/[^A-Za-z0-9._-]/', '', (string) $value); }
    function wp_strip_all_tags($value, $remove_breaks = false) { return strip_tags((string) $value); }
    function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
    function wp_slash($value) { return $value; }
    function is_wp_error($value) { return $value instanceof WP_Error; }
    function absint($value) { return abs((int) $value); }
    function get_user_locale() { return 'en_US'; }
    function current_user_can($capability) { return 'manage_options' === $capability && $GLOBALS['c99_manage_allowed']; }
    function get_current_user_id() { return 17; }
    function get_current_blog_id() { return 1; }
    function home_url($path = '') { return 'https://complete99.test' . $path; }
    function trailingslashit($value) { return rtrim((string) $value, '/\\') . '/'; }
    function wp_generate_uuid4() {
        $counter = $GLOBALS['c99_uuid_counter']++;
        return sprintf('00000000-0000-4000-8000-%012d', $counter);
    }
    function get_page_by_path($path, $output = OBJECT, $post_type = '') {
        foreach ($GLOBALS['c99_posts'] as $post) {
            if ($post->post_name === $path && $post->post_type === $post_type) { return $post; }
        }
        return null;
    }
    function get_post($post_id) {
        $post = $GLOBALS['c99_posts'][(int) $post_id] ?? null;
        if ($post instanceof WP_Post && $GLOBALS['c99_corrupt_get_post']) {
            $post = clone $post;
            $post->post_content = '{}';
        }
        return $post;
    }
    function wp_insert_post($postarr, $wp_error = false) {
        $post_id = $GLOBALS['c99_next_post_id']++;
        $postarr['ID'] = $post_id;
        $GLOBALS['c99_posts'][$post_id] = new WP_Post($postarr);
        return $post_id;
    }
    function wp_update_post($postarr, $wp_error = false) {
        $post_id = (int) ($postarr['ID'] ?? 0);
        if (!$post_id || !isset($GLOBALS['c99_posts'][$post_id])) {
            return new WP_Error('missing_post', 'Missing post');
        }
        foreach ($postarr as $key => $value) {
            if ('ID' !== $key) { $GLOBALS['c99_posts'][$post_id]->$key = $value; }
        }
        return $post_id;
    }
    function update_post_meta($post_id, $key, $value) {
        if ($GLOBALS['c99_fail_meta_key'] === $key) { return false; }
        $GLOBALS['c99_meta'][(int) $post_id][$key] = $value;
        return true;
    }
    function get_post_meta($post_id, $key, $single = false) {
        return $GLOBALS['c99_meta'][(int) $post_id][$key] ?? '';
    }
    function clean_post_cache($post_id) { $GLOBALS['c99_cleaned_post_ids'][] = (int) $post_id; }
    function wp_cache_delete($key, $group = '') {
        $GLOBALS['c99_deleted_cache_keys'][] = array((int) $key, (string) $group);
        return true;
    }
    function wp_get_post_revisions($post_id, $args = array()) {
        return $GLOBALS['c99_revisions'][(int) $post_id] ?? array();
    }
    function get_posts($args = array()) {
        $GLOBALS['c99_get_posts_calls'][] = $args;
        $posts = array_values(array_filter(
            $GLOBALS['c99_posts'],
            static function($post) use ($args) {
                return $post->post_type === ($args['post_type'] ?? '')
                    && $post->post_status === ($args['post_status'] ?? '');
            }
        ));
        usort($posts, static function($left, $right) { return $left->ID <=> $right->ID; });
        $size = (int) ($args['posts_per_page'] ?? 0);
        $page = (int) ($args['paged'] ?? 1);
        return array_slice($posts, ($page - 1) * $size, $size);
    }

    require '__STUDIO_PATH__';

    function c99_payload($workflow_state = 'draft') {
        return array(
            'subject_id' => 'product-one',
            'workflow_state' => $workflow_state,
            'pricing_applicability' => 'priceable',
            'commercial_role' => 'conversion',
            'offer_type' => 'ingredient',
            'market_id' => 'market-il-launch',
            'channel_id' => 'channel-woo-web-il',
            'currency_code' => 'ILS',
            'planned_price_minor' => 11900,
            'pricing_state' => 'owner_authorized_planned',
            'quality_tier' => 'premium',
            'source_observation_ids' => array('observation-one'),
            'cross_sell_subject_ids' => array('product-two'),
            'upsell_subject_ids' => array(),
            'value_proposition' => array('he' => 'ערך קולינרי', 'en' => 'Culinary value'),
            'price_rationale' => array('he' => 'מחיר שוק מתועד', 'en' => 'Documented market price'),
            'private_note' => 'Private planning note',
        );
    }
    function c99_error_code($value) {
        return is_wp_error($value) ? $value->get_error_code() : '';
    }
    function c99_base_digest($subject_id = 'product-one') {
        $subjects = Complete99_Entity_Studio::subject_index(true);
        return (string) ($subjects[$subject_id]['base_registry']['digest'] ?? '');
    }
    function c99_reset_store() {
        $GLOBALS['c99_posts'] = array();
        $GLOBALS['c99_meta'] = array();
        $GLOBALS['c99_next_post_id'] = 1;
        $GLOBALS['c99_uuid_counter'] = 1;
        $GLOBALS['c99_fail_meta_key'] = '';
        $GLOBALS['c99_corrupt_get_post'] = false;
        $GLOBALS['c99_get_posts_calls'] = array();
        $GLOBALS['c99_get_var_queries'] = array();
        $GLOBALS['c99_lock_result'] = 1;
        $GLOBALS['c99_manage_allowed'] = true;
        $GLOBALS['c99_revisions'] = array();
        $GLOBALS['c99_cleaned_post_ids'] = array();
        $GLOBALS['c99_deleted_cache_keys'] = array();
        $GLOBALS['c99_commerce_version'] = 'test-commerce-1';
        $GLOBALS['c99_products_disabled'] = false;
        $GLOBALS['c99_observation_one_id'] = 'observation-one';
        $GLOBALS['wpdb'] = new C99_Test_WPDB();
    }
    '''
    return source.replace("__STUDIO_PATH__", _php_path(STUDIO))


def test_entity_studio_is_private_revisioned_wordpress_infrastructure():
    source = STUDIO.read_text(encoding="utf-8")

    assert "register_post_type(" in source
    assert "'public'              => false" in source
    assert "'publicly_queryable'  => false" in source
    assert "'exclude_from_search' => true" in source
    assert "'show_ui'             => false" in source
    assert "'show_in_rest'        => false" in source
    assert "'rewrite'             => false" in source
    assert "'supports'            => array( 'title', 'editor', 'revisions' )" in source
    assert source.count("'manage_options'") >= 10
    assert "add_management_page(" in source
    assert "current_user_can( 'manage_options' )" in source

    assert "register_rest_route(" in source
    assert "'/editorial/entity-studio'" in source
    assert source.count("'permission_callback' => array( __CLASS__, 'can_manage' )") == 2

    assert "wp_insert_post(" in source
    assert "wp_update_post(" in source
    assert "wp_get_post_revisions(" in source
    assert "expected_revision" in source
    assert "SELECT GET_LOCK" in source
    assert "prior_event_digest" in source
    assert "event_digest" in source
    assert "payload_digest" in source
    assert "base_registry" in source

    assert "add_role(" not in source
    assert "remove_role(" not in source
    assert "ensure_product(" not in source
    assert "set_price(" not in source
    assert "set_regular_price(" not in source
    assert "set_stock_quantity(" not in source
    assert "wp_delete_post(" not in source
    assert "delete_post_meta(" not in source
    assert "\u2014" not in source


def test_bootstrap_requires_and_boots_entity_studio_after_graph_modules():
    bootstrap = BOOTSTRAP.read_text(encoding="utf-8")
    platform = PLATFORM.read_text(encoding="utf-8")

    assert "includes/class-complete99-entity-studio.php" in bootstrap
    assert "Complete99_Entity_Studio::boot();" in platform
    assert platform.index("Complete99_Culinary_Science::boot();") < platform.index(
        "Complete99_Entity_Studio::boot();"
    )
    assert platform.index("Complete99_Culinary_Commerce::boot();") < platform.index(
        "Complete99_Entity_Studio::boot();"
    )
    assert platform.index("Complete99_Entity_Studio::boot();") < platform.index(
        "Complete99_Review_Lab::boot();"
    )
    assert platform.index("Complete99_Entity_Studio::boot();") < platform.index(
        "Complete99_Frontend::boot();"
    )


def test_editable_dossier_validation_is_bounded_and_fail_closed():
    studio_path = _php_path(STUDIO)
    payload = _run_php(
        f"""
        define('ABSPATH', __DIR__);
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
        function sanitize_key($value) {{
            return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value));
        }}
        function sanitize_text_field($value) {{ return trim(strip_tags((string) $value)); }}
        function wp_strip_all_tags($value, $remove_breaks = false) {{ return strip_tags((string) $value); }}
        function is_wp_error($value) {{ return $value instanceof WP_Error; }}
        require '{studio_path}';

        $subjects = array(
            'product-one' => array('subject_type' => 'product'),
            'product-two' => array('subject_type' => 'product'),
            'entity-one' => array('subject_type' => 'entity'),
        );
        $references = array(
            'markets' => array('market-il-launch' => true),
            'channels' => array('channel-woo-web-il' => true),
            'currencies' => array('ILS' => array('code' => 'ILS', 'minor_unit_digits' => 2)),
            'observations' => array(
                'observation-one' => array('subject_ids' => array('product-one'), 'state' => 'recorded'),
                'observation-foreign' => array('subject_ids' => array('product-two'), 'state' => 'recorded'),
                'observation-invalidated' => array('subject_ids' => array('product-one'), 'state' => 'invalidated'),
            ),
        );
        $record = array(
            'subject_id' => 'product-one',
            'workflow_state' => 'approved',
            'pricing_applicability' => 'priceable',
            'commercial_role' => 'conversion',
            'offer_type' => 'ingredient',
            'market_id' => 'market-il-launch',
            'channel_id' => 'channel-woo-web-il',
            'currency_code' => 'ILS',
            'planned_price_minor' => 14900,
            'pricing_state' => 'owner_authorized_planned',
            'quality_tier' => 'premium',
            'source_observation_ids' => array('observation-one'),
            'cross_sell_subject_ids' => array('product-two'),
            'upsell_subject_ids' => array(),
            'value_proposition' => array('he' => 'ערך קולינרי ברור', 'en' => 'Clear culinary value'),
            'price_rationale' => array('he' => 'מחיר שוק מתועד', 'en' => 'Documented market rationale'),
            'private_note' => 'Private commercial note',
        );
        function result($candidate, $subjects, $references) {{
            $value = Complete99_Entity_Studio::normalize_editable_record($candidate, $subjects, $references);
            return is_wp_error($value)
                ? array('valid' => false, 'code' => $value->get_error_code())
                : array('valid' => true, 'record' => $value);
        }}
        $cases = array();
        $cases['valid_reordered'] = result(array_reverse($record, true), $subjects, $references);

        $candidate = $record;
        $candidate['subject_id'] = 'entity-one';
        $cases['entity_price'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['source_observation_ids'] = array();
        $cases['missing_evidence'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['source_observation_ids'] = array('observation-foreign');
        $cases['foreign_evidence'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['source_observation_ids'] = array('observation-invalidated');
        $cases['inactive_evidence'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['cross_sell_subject_ids'] = array('product-one');
        $cases['self_relation'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['value_proposition']['en'] = 'Forbidden ' . hex2bin('e28094') . ' punctuation';
        $cases['em_dash'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['value_proposition'] = array('en' => 'English first', 'he' => 'עברית שנייה');
        $cases['localized_key_order'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['unexpected'] = true;
        $cases['unknown_field'] = result($candidate, $subjects, $references);

        $candidate = $record;
        $candidate['subject_id'] = 'entity-one';
        $candidate['workflow_state'] = 'draft';
        $candidate['pricing_applicability'] = 'not_priceable';
        $candidate['offer_type'] = 'none';
        $candidate['market_id'] = '';
        $candidate['channel_id'] = '';
        $candidate['currency_code'] = '';
        $candidate['planned_price_minor'] = 0;
        $candidate['pricing_state'] = 'not_applicable';
        $candidate['quality_tier'] = 'not_applicable';
        $candidate['source_observation_ids'] = array();
        $candidate['cross_sell_subject_ids'] = array('product-one');
        $candidate['value_proposition'] = array('he' => '', 'en' => '');
        $candidate['price_rationale'] = array('he' => '', 'en' => '');
        $cases['valid_nonpriceable'] = result($candidate, $subjects, $references);

        echo json_encode($cases, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        """
    )
    cases = json.loads(payload)

    assert cases["valid_reordered"]["valid"] is True
    assert cases["valid_reordered"]["record"]["planned_price_minor"] == 14900
    assert cases["valid_nonpriceable"]["valid"] is True
    assert cases["entity_price"] == {
        "valid": False,
        "code": "complete99_entity_studio_product_required",
    }
    assert cases["missing_evidence"] == {
        "valid": False,
        "code": "complete99_entity_studio_price_evidence_required",
    }
    assert cases["foreign_evidence"] == {
        "valid": False,
        "code": "complete99_entity_studio_observation_subject_mismatch",
    }
    assert cases["inactive_evidence"] == {
        "valid": False,
        "code": "complete99_entity_studio_inactive_observation",
    }
    assert cases["self_relation"] == {
        "valid": False,
        "code": "complete99_entity_studio_invalid_target",
    }
    assert cases["em_dash"] == {
        "valid": False,
        "code": "complete99_entity_studio_invalid_text",
    }
    assert cases["unknown_field"] == {
        "valid": False,
        "code": "complete99_entity_studio_unknown_fields",
    }
    assert cases["localized_key_order"]["valid"] is True


def test_subject_index_joins_science_catalog_commerce_and_price_evidence():
    studio_path = _php_path(STUDIO)
    science_path = _php_path(
        PLUGIN / "includes" / "class-complete99-culinary-science.php"
    )
    commerce_path = _php_path(
        PLUGIN / "includes" / "class-complete99-culinary-commerce.php"
    )
    plugin_path = _php_path(PLUGIN) + "/"
    payload = _run_php(
        f"""
        define('ABSPATH', __DIR__);
        define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
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
        function sanitize_key($value) {{
            return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value));
        }}
        function sanitize_text_field($value) {{ return trim(strip_tags((string) $value)); }}
        function sanitize_file_name($value) {{ return preg_replace('/[^A-Za-z0-9._-]/', '', (string) $value); }}
        function wp_json_encode($value, $flags = 0) {{ return json_encode($value, $flags); }}
        function absint($value) {{ return abs((int) $value); }}
        function get_user_locale() {{ return 'he_IL'; }}
        require '{science_path}';
        require '{commerce_path}';
        require '{studio_path}';
        $subjects = Complete99_Entity_Studio::subject_index(true);
        $references = Complete99_Entity_Studio::reference_index(true);
        $defaults = new ReflectionMethod('Complete99_Entity_Studio', 'default_editable_record');
        $defaults->setAccessible(true);
        $product_count = 0;
        foreach ($subjects as $subject) {{
            if ($subject['subject_type'] === 'product') {{ $product_count++; }}
        }}
        echo json_encode(array(
            'subject_count' => count($subjects),
            'product_count' => $product_count,
            'observation_count' => count($references['observations']),
            'rice' => $subjects['product-koshihikari-uozu-2kg'],
            'hangiri' => $subjects['product-umezawa-hangiri-36cm'],
            'kombu_entity' => $subjects['ingredient-kombu'],
            'hangiri_default' => $defaults->invoke(null, $subjects['product-umezawa-hangiri-36cm']),
            'kombu_default' => $defaults->invoke(null, $subjects['ingredient-kombu']),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        """
    )
    result = json.loads(payload)

    assert result["subject_count"] == 607
    assert result["product_count"] == 56
    assert result["observation_count"] >= 46
    assert result["rice"]["domain"] == "catalog"
    assert result["rice"]["current_price_minor"] == 14900
    assert result["rice"]["market_observation_ids"]
    assert result["rice"]["base_registry"]["digest"]
    assert result["hangiri"]["domain"] == "commerce"
    assert result["hangiri"]["current_price_minor"] is None
    assert result["hangiri"]["planning_price_minor"] == 64900
    assert result["hangiri"]["planning_currency_code"] == "ILS"
    assert result["hangiri"]["market_observation_ids"] == [
        "observation-umezawa-hangiri-36cm-20260806"
    ]
    assert result["kombu_entity"]["domain"] == "science"
    assert result["kombu_entity"]["subject_type"] == "entity"
    assert result["kombu_entity"]["current_price_minor"] == 8900
    assert result["kombu_default"]["pricing_applicability"] == "not_priceable"
    assert result["kombu_default"]["planned_price_minor"] == 0
    assert result["hangiri_default"]["offer_type"] == "equipment"
    assert result["hangiri_default"]["planned_price_minor"] == 64900
    assert result["hangiri_default"]["pricing_state"] == "owner_authorized_planned"


def test_persistence_initial_draft_review_and_approval_form_a_verified_chain():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial commercial draft',
            'wordpress-rest', 'correlation-draft', false, c99_base_digest()
        );
        $review = Complete99_Entity_Studio::save_record(
            c99_payload('in_review'), 1, 'Submit for review',
            'wordpress-rest', 'correlation-review', false, c99_base_digest()
        );
        $approved = Complete99_Entity_Studio::save_record(
            c99_payload('approved'), 2, 'Approve reviewed dossier',
            'wordpress-rest', 'correlation-approved', false, c99_base_digest()
        );
        echo json_encode(array(
            'errors' => array(
                c99_error_code($draft), c99_error_code($review), c99_error_code($approved),
            ),
            'states' => array(
                $draft['workflow']['state'] ?? '',
                $review['workflow']['state'] ?? '',
                $approved['workflow']['state'] ?? '',
            ),
            'revisions' => array(
                $draft['workflow']['revision'] ?? 0,
                $review['workflow']['revision'] ?? 0,
                $approved['workflow']['revision'] ?? 0,
            ),
            'transitions' => array(
                $draft['event']['workflow_transition'] ?? array(),
                $review['event']['workflow_transition'] ?? array(),
                $approved['event']['workflow_transition'] ?? array(),
            ),
            'sources' => array(
                $draft['event']['source'] ?? '',
                $review['event']['source'] ?? '',
                $approved['event']['source'] ?? '',
            ),
            'prior_revisions' => array(
                $draft['event']['prior_revision'] ?? -1,
                $review['event']['prior_revision'] ?? -1,
                $approved['event']['prior_revision'] ?? -1,
            ),
            'event_chain' => array(
                $draft['event']['prior_event_digest'] ?? 'missing',
                ($review['event']['prior_event_digest'] ?? '') === ($draft['event']['event_digest'] ?? 'x'),
                ($approved['event']['prior_event_digest'] ?? '') === ($review['event']['event_digest'] ?? 'x'),
            ),
            'record_chain' => array(
                $draft['event']['prior_record_digest'] ?? 'missing',
                strlen($review['event']['prior_record_digest'] ?? ''),
                strlen($approved['event']['prior_record_digest'] ?? ''),
            ),
            'changed_paths' => array(
                $draft['event']['changed_field_paths'] ?? array(),
                $review['event']['changed_field_paths'] ?? array(),
                $approved['event']['changed_field_paths'] ?? array(),
            ),
            'queries' => $GLOBALS['wpdb']->queries,
            'stored_post_count' => count($GLOBALS['c99_posts']),
            'stored_meta' => $GLOBALS['c99_meta'][1] ?? array(),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        '''
    )
    result = json.loads(payload)

    assert result["errors"] == ["", "", ""]
    assert result["states"] == ["draft", "in_review", "approved"]
    assert result["revisions"] == [1, 2, 3]
    assert result["transitions"] == [
        {"from": "", "to": "draft"},
        {"from": "draft", "to": "in_review"},
        {"from": "in_review", "to": "approved"},
    ]
    assert result["sources"] == ["wordpress-rest"] * 3
    assert result["prior_revisions"] == [0, 1, 2]
    assert result["event_chain"] == ["", True, True]
    assert result["record_chain"] == ["", 64, 64]
    assert "/workflow/state" in result["changed_paths"][0]
    assert "/workflow/state" in result["changed_paths"][1]
    assert "/workflow/state" in result["changed_paths"][2]
    assert result["queries"] == [
        "START TRANSACTION",
        "COMMIT",
        "START TRANSACTION",
        "COMMIT",
        "START TRANSACTION",
        "COMMIT",
    ]
    assert result["stored_post_count"] == 1
    assert result["stored_meta"]["_complete99_entity_subject_id"] == "product-one"
    assert result["stored_meta"]["_complete99_entity_revision"] == 3
    assert len(result["stored_meta"]["_complete99_entity_digest"]) == 64


def test_persistence_rejects_direct_draft_to_approved_and_invalid_sources():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $empty_source = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Has a reason', '', 'correlation-empty-source', false, c99_base_digest()
        );
        $long_source = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Has a reason', str_repeat('a', 51), 'correlation-long-source', false, c99_base_digest()
        );
        $wrong_base = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Has a reason', 'wordpress-admin',
            'correlation-wrong-base', false, str_repeat('0', 64)
        );
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial draft', 'wordpress-admin', 'correlation-draft', false, c99_base_digest()
        );
        $approval = Complete99_Entity_Studio::save_record(
            c99_payload('approved'), 1, 'Attempt direct approval',
            'wordpress-admin', 'correlation-approval', false, c99_base_digest()
        );
        $stored = Complete99_Entity_Studio::record('product-one');
        echo json_encode(array(
            'empty_source' => c99_error_code($empty_source),
            'long_source' => c99_error_code($long_source),
            'wrong_base' => c99_error_code($wrong_base),
            'draft_error' => c99_error_code($draft),
            'approval_error' => c99_error_code($approval),
            'stored_state' => $stored['workflow']['state'] ?? '',
            'stored_revision' => $stored['workflow']['revision'] ?? 0,
            'queries' => $GLOBALS['wpdb']->queries,
        ), JSON_UNESCAPED_SLASHES);
        '''
    )
    result = json.loads(payload)

    assert result == {
        "empty_source": "complete99_entity_studio_invalid_source",
        "long_source": "complete99_entity_studio_invalid_source",
        "wrong_base": "complete99_entity_studio_base_conflict",
        "draft_error": "",
        "approval_error": "complete99_entity_studio_invalid_transition",
        "stored_state": "draft",
        "stored_revision": 1,
        "queries": ["START TRANSACTION", "COMMIT"],
    }


def test_persistence_refuses_to_overwrite_a_corrupt_existing_post():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial draft', 'wordpress-admin', 'correlation-draft', false, c99_base_digest()
        );
        $post_id = (int) ($draft['post_id'] ?? 0);
        $GLOBALS['c99_posts'][$post_id]->post_content = '{"broken":';
        $attempt = Complete99_Entity_Studio::save_record(
            c99_payload('in_review'), 1, 'Submit corrupt record',
            'wordpress-admin', 'correlation-review', false, c99_base_digest()
        );
        echo json_encode(array(
            'draft_error' => c99_error_code($draft),
            'attempt_error' => c99_error_code($attempt),
            'content' => $GLOBALS['c99_posts'][$post_id]->post_content,
            'revision_meta' => $GLOBALS['c99_meta'][$post_id]['_complete99_entity_revision'] ?? 0,
            'queries' => $GLOBALS['wpdb']->queries,
        ), JSON_UNESCAPED_SLASHES);
        '''
    )
    result = json.loads(payload)

    assert result["draft_error"] == ""
    assert result["attempt_error"] == "complete99_entity_studio_corrupt_prior"
    assert result["content"] == '{"broken":'
    assert result["revision_meta"] == 1
    assert result["queries"] == ["START TRANSACTION", "COMMIT"]


def test_persistence_rolls_back_meta_and_record_readback_failures():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $GLOBALS['c99_fail_meta_key'] = '_complete99_entity_digest';
        $meta_failure = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Meta readback failure',
            'wordpress-admin', 'correlation-meta', false, c99_base_digest()
        );
        $meta_case = array(
            'error' => c99_error_code($meta_failure),
            'post_count' => count($GLOBALS['c99_posts']),
            'meta_count' => count($GLOBALS['c99_meta']),
            'queries' => $GLOBALS['wpdb']->queries,
            'cleaned_post_ids' => $GLOBALS['c99_cleaned_post_ids'],
            'deleted_cache_keys' => $GLOBALS['c99_deleted_cache_keys'],
        );

        c99_reset_store();
        $GLOBALS['c99_corrupt_get_post'] = true;
        $record_failure = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Record readback failure',
            'wordpress-admin', 'correlation-record', false, c99_base_digest()
        );
        $record_case = array(
            'error' => c99_error_code($record_failure),
            'post_count' => count($GLOBALS['c99_posts']),
            'meta_count' => count($GLOBALS['c99_meta']),
            'queries' => $GLOBALS['wpdb']->queries,
            'cleaned_post_ids' => $GLOBALS['c99_cleaned_post_ids'],
            'deleted_cache_keys' => $GLOBALS['c99_deleted_cache_keys'],
        );
        echo json_encode(array('meta' => $meta_case, 'record' => $record_case));
        '''
    )
    result = json.loads(payload)

    expected_base = {
        "error": "complete99_entity_studio_write_rolled_back",
        "post_count": 0,
        "meta_count": 0,
        "queries": ["START TRANSACTION", "ROLLBACK"],
    }
    for case in (result["meta"], result["record"]):
        assert {key: case[key] for key in expected_base} == expected_base
        assert case["cleaned_post_ids"][-1] == 1
        assert case["deleted_cache_keys"][-1] == [1, "post_meta"]


def test_record_readback_binds_content_to_post_slug_meta_and_expected_identity():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial draft', 'wordpress-admin', 'correlation-draft', false, c99_base_digest()
        );
        $post_id = (int) ($draft['post_id'] ?? 0);
        $post = $GLOBALS['c99_posts'][$post_id];
        $valid = Complete99_Entity_Studio::record('product-one');

        $GLOBALS['c99_meta'][$post_id]['_complete99_entity_subject_id'] = 'product-two';
        $wrong_meta = Complete99_Entity_Studio::record('product-one');
        $GLOBALS['c99_meta'][$post_id]['_complete99_entity_subject_id'] = 'product-one';

        $post->post_name = 'product-two';
        $wrong_slug = Complete99_Entity_Studio::record('product-one');
        $post->post_name = 'product-one';

        $reader = new ReflectionMethod('Complete99_Entity_Studio', 'record_from_post');
        $reader->setAccessible(true);
        $wrong_expected_identity = $reader->invoke(null, $post, 'product-two');

        echo json_encode(array(
            'valid_id' => $valid['stable_id'] ?? '',
            'valid_post_id' => $valid['post_id'] ?? 0,
            'wrong_meta_rejected' => array() === $wrong_meta,
            'wrong_slug_rejected' => array() === $wrong_slug,
            'wrong_expected_identity_rejected' => array() === $wrong_expected_identity,
        ));
        '''
    )
    result = json.loads(payload)

    assert result == {
        "valid_id": "product-one",
        "valid_post_id": 1,
        "wrong_meta_rejected": True,
        "wrong_slug_rejected": True,
        "wrong_expected_identity_rejected": True,
    }


def test_currency_amount_contract_handles_zero_digit_jpy_without_rounding():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $parse = new ReflectionMethod('Complete99_Entity_Studio', 'amount_to_minor');
        $parse->setAccessible(true);
        $render = new ReflectionMethod('Complete99_Entity_Studio', 'minor_to_amount');
        $render->setAccessible(true);
        $digits = new ReflectionMethod('Complete99_Entity_Studio', 'currency_digits');
        $digits->setAccessible(true);
        $references = Complete99_Entity_Studio::reference_index(true);
        echo json_encode(array(
            'digits' => $digits->invoke(null, 'JPY', $references),
            'parsed' => $parse->invoke(null, '1880', 0),
            'decimal_rejected' => null === $parse->invoke(null, '1880.0', 0),
            'leading_zero_rejected' => null === $parse->invoke(null, '01880', 0),
            'rendered' => $render->invoke(null, 1880, 0),
        ));
        '''
    )
    result = json.loads(payload)

    assert result == {
        "digits": 0,
        "parsed": 1880,
        "decimal_rejected": True,
        "leading_zero_rejected": True,
        "rendered": "1880",
    }


def test_all_records_paginates_at_contract_size_and_deduplicates_stable_identity():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial draft', 'wordpress-admin', 'correlation-draft', false, c99_base_digest()
        );
        $template_post = $GLOBALS['c99_posts'][1];
        $template_meta = $GLOBALS['c99_meta'][1];
        for ($post_id = 2; $post_id <= 251; $post_id++) {
            $copy = clone $template_post;
            $copy->ID = $post_id;
            $GLOBALS['c99_posts'][$post_id] = $copy;
            $GLOBALS['c99_meta'][$post_id] = $template_meta;
        }
        $GLOBALS['c99_next_post_id'] = 252;
        $GLOBALS['c99_get_posts_calls'] = array();
        $reader = new ReflectionMethod('Complete99_Entity_Studio', 'all_records');
        $reader->setAccessible(true);
        $records = $reader->invoke(null);
        $calls = array_map(
            static function($args) {
                return array(
                    'page' => $args['paged'] ?? 0,
                    'size' => $args['posts_per_page'] ?? 0,
                    'post_type' => $args['post_type'] ?? '',
                    'post_status' => $args['post_status'] ?? '',
                    'order' => $args['order'] ?? '',
                    'no_found_rows' => $args['no_found_rows'] ?? false,
                );
            },
            $GLOBALS['c99_get_posts_calls']
        );
        echo json_encode(array(
            'record_count' => count($records),
            'record_ids' => array_keys($records),
            'calls' => $calls,
        ));
        '''
    )
    result = json.loads(payload)

    assert result["record_count"] == 1
    assert result["record_ids"] == ["product-one"]
    assert result["calls"] == [
        {
            "page": 1,
            "size": 250,
            "post_type": "c99_entity_dossier",
            "post_status": "private",
            "order": "ASC",
            "no_found_rows": True,
        },
        {
            "page": 2,
            "size": 250,
            "post_type": "c99_entity_dossier",
            "post_status": "private",
            "order": "ASC",
            "no_found_rows": True,
        },
    ]


def test_capability_and_advisory_lock_fail_closed_and_release_cleanly():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $GLOBALS['c99_manage_allowed'] = false;
        $denied_snapshot = Complete99_Entity_Studio::snapshot();
        $denied_save = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Denied write', 'wordpress-rest',
            'correlation-denied', false, c99_base_digest()
        );

        $GLOBALS['c99_manage_allowed'] = true;
        $GLOBALS['c99_lock_result'] = 0;
        $locked = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Locked write', 'wordpress-rest',
            'correlation-locked', false, c99_base_digest()
        );

        $GLOBALS['c99_lock_result'] = 1;
        $saved = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Accepted write', 'wordpress-rest',
            'correlation-saved', false, c99_base_digest()
        );
        echo json_encode(array(
            'denied_snapshot' => c99_error_code($denied_snapshot),
            'denied_save' => c99_error_code($denied_save),
            'locked' => c99_error_code($locked),
            'saved' => c99_error_code($saved),
            'transaction_queries' => $GLOBALS['wpdb']->queries,
            'lock_queries' => $GLOBALS['c99_get_var_queries'],
        ));
        '''
    )
    result = json.loads(payload)

    assert result["denied_snapshot"] == "complete99_entity_studio_forbidden"
    assert result["denied_save"] == "complete99_entity_studio_forbidden"
    assert result["locked"] == "complete99_entity_studio_locked"
    assert result["saved"] == ""
    assert result["transaction_queries"] == ["START TRANSACTION", "COMMIT"]
    assert sum("GET_LOCK" in query for query in result["lock_queries"]) == 2
    assert sum("RELEASE_LOCK" in query for query in result["lock_queries"]) == 1


def test_commerce_product_can_rebase_as_draft_after_catalog_promotion():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Commerce-only draft', 'wordpress-admin',
            'correlation-commerce', false, c99_base_digest()
        );
        $catalog = array(
            'schema' => 'complete99-catalog-test/v1',
            'registry_reviewed_at' => 'test-catalog-2',
            'products' => array(
                array(
                    'product_code' => 'product-one',
                    'name' => array('he' => 'מוצר אחד', 'en' => 'Product one'),
                    'product_kind' => 'ingredient',
                    'ingredient_code' => 'ingredient-one',
                    'sale_state' => 'held_until_acceptance',
                ),
            ),
        );
        file_put_contents(
            COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php',
            '<?php return ' . var_export($catalog, true) . ';'
        );
        $promoted_subjects = Complete99_Entity_Studio::subject_index(true);
        $readable_before_rebase = Complete99_Entity_Studio::record('product-one');
        $new_digest = $promoted_subjects['product-one']['base_registry']['digest'];
        $without_rebase = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 1, 'Missing explicit rebase', 'wordpress-admin',
            'correlation-no-rebase', false, $new_digest
        );
        $with_rebase = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 1, 'Reviewed catalog promotion', 'wordpress-admin',
            'correlation-rebase', true, $new_digest
        );
        @unlink(COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php');
        echo json_encode(array(
            'draft_error' => c99_error_code($draft),
            'promoted_domain' => $promoted_subjects['product-one']['domain'] ?? '',
            'readable_domain' => $readable_before_rebase['domain'] ?? '',
            'without_rebase' => c99_error_code($without_rebase),
            'with_rebase' => c99_error_code($with_rebase),
            'rebased_domain' => is_wp_error($with_rebase) ? '' : ($with_rebase['domain'] ?? ''),
            'rebased_revision' => is_wp_error($with_rebase) ? 0 : ($with_rebase['workflow']['revision'] ?? 0),
            'changed_paths' => is_wp_error($with_rebase) ? array() : ($with_rebase['event']['changed_field_paths'] ?? array()),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        '''
    )
    result = json.loads(payload)

    assert result["draft_error"] == ""
    assert result["promoted_domain"] == "catalog"
    assert result["readable_domain"] == "commerce"
    assert result["without_rebase"] == "complete99_entity_studio_stale_base"
    assert result["with_rebase"] == ""
    assert result["rebased_domain"] == "catalog"
    assert result["rebased_revision"] == 2
    assert "/base_registry" in result["changed_paths"]


def test_history_bundle_validates_payload_identity_and_transition_origin():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $draft = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial draft', 'wordpress-rest',
            'correlation-draft', false, c99_base_digest()
        );
        $review = Complete99_Entity_Studio::save_record(
            c99_payload('in_review'), 1, 'Review', 'wordpress-rest',
            'correlation-review', false, c99_base_digest()
        );
        $approved = Complete99_Entity_Studio::save_record(
            c99_payload('approved'), 2, 'Approval', 'wordpress-rest',
            'correlation-approved', false, c99_base_digest()
        );
        $digest_method = new ReflectionMethod('Complete99_Entity_Studio', 'canonical_digest');
        $digest_method->setAccessible(true);
        $history_method = new ReflectionMethod('Complete99_Entity_Studio', 'history_bundle');
        $history_method->setAccessible(true);
        $make_revision = static function($record, $id) {
            unset($record['post_id']);
            return new WP_Post(array(
                'ID' => $id,
                'post_type' => 'revision',
                'post_content' => json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ));
        };

        $GLOBALS['c99_revisions'][1] = array($make_revision($draft, 101), $make_revision($review, 102));
        $verified = $history_method->invoke(null, 'product-one');

        $bad_transition = $review;
        unset($bad_transition['post_id']);
        $bad_transition['event']['workflow_transition']['from'] = 'in_review';
        $event_without_digest = $bad_transition['event'];
        unset($event_without_digest['event_digest']);
        $bad_transition['event']['event_digest'] = $digest_method->invoke(null, $event_without_digest);
        $bad_current = $approved;
        unset($bad_current['post_id']);
        $bad_current['event']['prior_record_digest'] = $digest_method->invoke(null, $bad_transition);
        $bad_current['event']['prior_event_digest'] = $bad_transition['event']['event_digest'];
        $event_without_digest = $bad_current['event'];
        unset($event_without_digest['event_digest']);
        $bad_current['event']['event_digest'] = $digest_method->invoke(null, $event_without_digest);
        $GLOBALS['c99_posts'][1]->post_content = json_encode($bad_current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $GLOBALS['c99_meta'][1]['_complete99_entity_digest'] = $digest_method->invoke(null, $bad_current);
        $GLOBALS['c99_revisions'][1] = array($make_revision($draft, 101), $make_revision($bad_transition, 102));
        $transition_result = $history_method->invoke(null, 'product-one');

        $original_current = $approved;
        unset($original_current['post_id']);
        $GLOBALS['c99_posts'][1]->post_content = json_encode($original_current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $GLOBALS['c99_meta'][1]['_complete99_entity_digest'] = $digest_method->invoke(null, $original_current);

        $bad_identity = $review;
        unset($bad_identity['post_id']);
        $bad_identity['stable_id'] = 'product-three';
        $GLOBALS['c99_revisions'][1] = array($make_revision($draft, 101), $make_revision($bad_identity, 102));
        $identity_result = $history_method->invoke(null, 'product-one');

        $bad_payload = $review;
        unset($bad_payload['post_id']);
        $bad_payload['payload']['quality_tier'] = 'unsupported-tier';
        $bad_payload['payload_digest'] = $digest_method->invoke(null, $bad_payload['payload']);
        $bad_payload['event']['resulting_payload_digest'] = $bad_payload['payload_digest'];
        $event_without_digest = $bad_payload['event'];
        unset($event_without_digest['event_digest']);
        $bad_payload['event']['event_digest'] = $digest_method->invoke(null, $event_without_digest);
        $GLOBALS['c99_revisions'][1] = array($make_revision($draft, 101), $make_revision($bad_payload, 102));
        $payload_result = $history_method->invoke(null, 'product-one');

        echo json_encode(array(
            'verified' => array($verified['state'], count($verified['records'])),
            'transition' => array($transition_result['state'], $transition_result['reason']),
            'identity' => array($identity_result['state'], $identity_result['reason']),
            'payload' => array($payload_result['state'], $payload_result['reason']),
        ));
        '''
    )
    result = json.loads(payload)

    assert result["verified"] == ["verified", 3]
    assert result["transition"] == ["corrupt", "chain_link"]
    assert result["identity"] == ["corrupt", "revision_record"]
    assert result["payload"] == ["corrupt", "revision_record"]


def test_snapshot_is_bounded_and_preserves_direct_orphan_audit_access():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $saved = Complete99_Entity_Studio::save_record(
            c99_payload('draft'), 0, 'Initial draft', 'wordpress-rest',
            'correlation-draft', false, c99_base_digest()
        );
        $page_one = Complete99_Entity_Studio::snapshot('', 1, 1);
        $page_two = Complete99_Entity_Studio::snapshot('', 2, 1);
        $invalid = Complete99_Entity_Studio::snapshot('', 1, 101);

        $GLOBALS['c99_products_disabled'] = true;
        Complete99_Entity_Studio::subject_index(true);
        $orphan = Complete99_Entity_Studio::snapshot('product-one', 1, 1);
        echo json_encode(array(
            'saved' => c99_error_code($saved),
            'page_one' => array(
                count($page_one['subjects']),
                $page_one['pagination'],
                count($page_one['references']['observations']),
            ),
            'page_two' => array(
                count($page_two['subjects']),
                $page_two['pagination'],
                count($page_two['references']['observations']),
            ),
            'invalid' => c99_error_code($invalid),
            'orphaned' => $orphan['orphaned'] ?? false,
            'orphan_subjects' => count($orphan['subjects'] ?? array()),
            'orphan_records' => count($orphan['records'] ?? array()),
            'orphan_state' => $orphan['records'][0]['base_registry_state'] ?? '',
        ));
        '''
    )
    result = json.loads(payload)

    assert result["saved"] == ""
    assert result["page_one"][0] == 1
    assert result["page_one"][1] == {
        "page": 1,
        "per_page": 1,
        "total_subjects": 2,
        "total_pages": 2,
        "next_page": 2,
    }
    assert result["page_two"][0] == 1
    assert result["page_two"][1]["next_page"] is None
    assert result["page_one"][2] <= 1
    assert result["page_two"][2] <= 1
    assert result["invalid"] == "complete99_entity_studio_invalid_page_size"
    assert result["orphaned"] is True
    assert result["orphan_subjects"] == 0
    assert result["orphan_records"] == 1
    assert result["orphan_state"] == "subject_missing"


def test_observation_identity_collision_fails_closed_across_registries():
    payload = _run_php(
        _entity_studio_persistence_prelude()
        + r'''
        $collision_id = 'catalog-observation-product-one-20260806';
        $GLOBALS['c99_observation_one_id'] = $collision_id;
        $catalog = array(
            'schema' => 'complete99-catalog-test/v1',
            'registry_reviewed_at' => 'test-catalog-1',
            'products' => array(
                array(
                    'product_code' => 'product-one',
                    'name' => array('he' => 'מוצר אחד', 'en' => 'Product one'),
                    'product_kind' => 'ingredient',
                    'ingredient_code' => 'ingredient-one',
                    'sale_state' => 'held_until_acceptance',
                    'market_observation' => array('checked_at' => '2026-08-06'),
                ),
            ),
        );
        file_put_contents(
            COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php',
            '<?php return ' . var_export($catalog, true) . ';'
        );
        $error = '';
        try {
            Complete99_Entity_Studio::reference_index(true);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
        @unlink(COMPLETE99_PLATFORM_DIR . 'data/catalog-product-seeds.php');
        $references = '' === $error ? Complete99_Entity_Studio::reference_index(false) : array('observations' => array());
        echo json_encode(array('error' => $error, 'observation_ids' => array_keys($references['observations'])));
        '''
    )
    result = json.loads(payload)

    assert result["error"] == (
        "entity-studio-observation-identity-collision."
        "catalog-observation-product-one-20260806"
    )


def test_entity_studio_php_lints():
    completed = subprocess.run(
        ["php", "-l", str(STUDIO)],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=30,
    )
    assert "No syntax errors detected" in completed.stdout
