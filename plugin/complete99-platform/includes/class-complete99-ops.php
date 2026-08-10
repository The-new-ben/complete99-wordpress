<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-native, private Complete99 operations foundation.
 *
 * P1 is deliberately read-only. It establishes durable schema, WordPress
 * authorization and an authenticated status surface without accepting an
 * operational command or claiming that the legacy application is migrated.
 */
final class Complete99_Ops {
	const PAGE_SLUG             = 'complete99-os';
	const CAPABILITY            = 'complete99_view_operations';
	const REST_NAMESPACE        = 'complete99/v1';
	const REST_ROUTE            = '/ops/status';
	const SCHEMA_VERSION        = 'complete99-ops-schema/v1';
	const OPTION_SCHEMA_VERSION = 'complete99_ops_schema_version';

	/**
	 * Register private admin and REST surfaces.
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Prepare idempotent table DDL while the platform migration lock is held.
	 *
	 * MySQL DDL commits implicitly, so this phase must run before START
	 * TRANSACTION. Empty prepared tables are inert and safe to retain on failure;
	 * the version marker and access capability remain transaction-bound.
	 */
	public static function prepare_schema() {
		self::install_schema_tables();
		self::assert_schema_tables();
	}

	/**
	 * Activate the prepared schema inside the existing platform transaction.
	 */
	public static function install() {
		self::assert_schema_tables();
		self::install_capability();
		update_option( self::OPTION_SCHEMA_VERSION, self::SCHEMA_VERSION, false );
		self::assert_invariants();
	}

	/**
	 * Assert all durable P1 foundations before the platform migration commits.
	 */
	public static function assert_invariants() {
		self::assert_schema();
		self::assert_capability();
		return true;
	}

	/**
	 * Add one top-level, private WordPress operations shell.
	 */
	public static function admin_menu() {
		add_menu_page(
			__( 'Complete99 OS', 'complete99-platform' ),
			__( 'Complete99 OS', 'complete99-platform' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-clipboard',
			3
		);
	}

	/**
	 * Return the WordPress-owned operations URL.
	 */
	public static function admin_page_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Load assets only on the private Complete99 OS page.
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== (string) $hook_suffix
			|| ! is_user_logged_in()
			|| ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		wp_enqueue_style(
			'complete99-ops',
			COMPLETE99_PLATFORM_URL . 'assets/css/ops.css',
			array(),
			COMPLETE99_PLATFORM_VERSION
		);
		wp_enqueue_script(
			'complete99-ops',
			COMPLETE99_PLATFORM_URL . 'assets/js/ops.js',
			array(),
			COMPLETE99_PLATFORM_VERSION,
			true
		);
		wp_localize_script(
			'complete99-ops',
			'Complete99OpsStatus',
			array(
				'endpoint'    => esc_url_raw( rest_url( ltrim( self::REST_NAMESPACE . self::REST_ROUTE, '/' ) ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'unavailable' => __( 'Private status could not be verified. The operations shell remains read-only.', 'complete99-platform' ),
				'ready'       => __( 'Foundation ready', 'complete99-platform' ),
				'checked'     => __( 'Verified just now', 'complete99-platform' ),
			)
		);
	}

	/**
	 * Register one nonce- and capability-protected read-only endpoint.
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( __CLASS__, 'authorize_status' ),
				'callback'            => array( __CLASS__, 'rest_status' ),
			)
		);
	}

	/**
	 * Require a logged-in WordPress user, the exact capability and a REST nonce.
	 */
	public static function authorize_status( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'complete99_ops_authentication_required',
				'WordPress authentication is required.',
				array( 'status' => 401 )
			);
		}
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return new WP_Error(
				'complete99_ops_forbidden',
				'This WordPress account cannot view Complete99 operations.',
				array( 'status' => 403 )
			);
		}

		$nonce = trim( (string) $request->get_header( 'X-WP-Nonce' ) );
		if ( '' === $nonce || false === wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'complete99_ops_invalid_nonce',
				'A valid WordPress REST nonce is required.',
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Return private foundation status, or fail closed while migration is stale.
	 */
	public static function rest_status( WP_REST_Request $request ) {
		unset( $request );
		$status = self::status_snapshot();
		if ( empty( $status['ready'] ) ) {
			return new WP_Error(
				'complete99_ops_migration_incomplete',
				'Complete99 operations remain unavailable until the private schema migration is complete.',
				array(
					'status' => 503,
					'state'  => 'read_only_unavailable',
				)
			);
		}
		return rest_ensure_response( $status );
	}

	/**
	 * Render a truthful, read-only Today foundation in wp-admin.
	 */
	public static function render_page() {
		if ( ! is_user_logged_in() || ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'This WordPress account cannot view Complete99 operations.', 'complete99-platform' ),
				esc_html__( 'Access denied', 'complete99-platform' ),
				array( 'response' => 403 )
			);
		}

		$status = self::status_snapshot();
		$ready  = ! empty( $status['ready'] );
		?>
		<div class="wrap c99-ops" data-c99-ops-shell="read-only">
			<header class="c99-ops__hero">
				<div>
					<p class="c99-ops__eyebrow"><?php echo esc_html__( 'Private WordPress workspace', 'complete99-platform' ); ?></p>
					<h1><?php echo esc_html__( 'Complete99 OS: Today', 'complete99-platform' ); ?></h1>
					<p class="c99-ops__lede"><?php echo esc_html__( 'This is the first WordPress-native operations foundation. It is read-only; daily operational modules and legacy data have not yet been migrated.', 'complete99-platform' ); ?></p>
				</div>
				<div class="c99-ops__status <?php echo $ready ? 'is-ready' : 'is-blocked'; ?>" data-c99-ops-status>
					<strong data-c99-ops-status-label><?php echo esc_html( $ready ? __( 'Foundation ready', 'complete99-platform' ) : __( 'Migration incomplete', 'complete99-platform' ) ); ?></strong>
					<span data-c99-ops-status-detail><?php echo esc_html( $ready ? __( 'Verified from durable WordPress storage', 'complete99-platform' ) : __( 'No operational actions are available', 'complete99-platform' ) ); ?></span>
				</div>
			</header>

			<div class="c99-ops__facts" aria-label="<?php echo esc_attr__( 'Foundation guarantees', 'complete99-platform' ); ?>">
				<span><?php echo esc_html__( 'Authentication: WordPress', 'complete99-platform' ); ?></span>
				<span><?php echo esc_html__( 'ChatGPT login: not required', 'complete99-platform' ); ?></span>
				<span><?php echo esc_html__( 'Operational writes: disabled', 'complete99-platform' ); ?></span>
			</div>

			<main class="c99-ops__grid">
				<section class="c99-ops__card" aria-labelledby="c99-ops-migration-title">
					<h2 id="c99-ops-migration-title"><?php echo esc_html__( 'Migration foundation', 'complete99-platform' ); ?></h2>
					<dl class="c99-ops__definition-list">
						<div><dt><?php echo esc_html__( 'Plugin database version', 'complete99-platform' ); ?></dt><dd><?php echo esc_html( $status['database_version'] ); ?></dd></div>
						<div><dt><?php echo esc_html__( 'Operations schema', 'complete99-platform' ); ?></dt><dd data-c99-ops-schema-version><?php echo esc_html( $status['ops_schema_version'] ); ?></dd></div>
						<div><dt><?php echo esc_html__( 'Schema tables', 'complete99-platform' ); ?></dt><dd data-c99-ops-table-count><?php echo esc_html( (string) $status['schema']['present_table_count'] . ' / ' . (string) $status['schema']['required_table_count'] ); ?></dd></div>
						<div><dt><?php echo esc_html__( 'Last status check', 'complete99-platform' ); ?></dt><dd data-c99-ops-checked><?php echo esc_html__( 'Server-rendered', 'complete99-platform' ); ?></dd></div>
					</dl>
				</section>

				<section class="c99-ops__card" aria-labelledby="c99-ops-today-title">
					<h2 id="c99-ops-today-title"><?php echo esc_html__( 'Today', 'complete99-platform' ); ?></h2>
					<div class="c99-ops__empty-state">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
						<p><?php echo esc_html__( 'Tasks and issues are not imported in P1. No legacy application data is shown as if it were current WordPress data.', 'complete99-platform' ); ?></p>
					</div>
				</section>

				<section class="c99-ops__card c99-ops__card--wide" aria-labelledby="c99-ops-modules-title">
					<h2 id="c99-ops-modules-title"><?php echo esc_html__( 'Migration map', 'complete99-platform' ); ?></h2>
					<ul class="c99-ops__modules">
						<?php foreach ( $status['modules'] as $module ) : ?>
							<li>
								<span><strong><?php echo esc_html( $module['label'] ); ?></strong><small><?php echo esc_html( $module['description'] ); ?></small></span>
								<em class="c99-ops__module-state c99-ops__module-state--<?php echo esc_attr( sanitize_html_class( $module['state'] ) ); ?>"><?php echo esc_html( $module['state_label'] ); ?></em>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</main>
		</div>
		<?php
	}

	/**
	 * Build a bounded private status snapshot with no secrets or row content.
	 */
	public static function status_snapshot() {
		$schema           = self::schema_status();
		$database_version = (string) get_option( 'complete99_platform_version', '' );
		$migration_failed = ! is_callable( array( 'Complete99_Platform', 'migration_failed' ) )
			|| Complete99_Platform::migration_failed();
		$ready = ! $migration_failed
			&& COMPLETE99_PLATFORM_VERSION === $database_version
			&& ! empty( $schema['ready'] );

		return array(
			'schema'                 => $schema,
			'status_schema'          => 'complete99-ops-status/v1',
			'ready'                  => $ready,
			'mode'                   => 'read_only_foundation',
			'auth_provider'          => 'wordpress',
			'chatgpt_login_required' => false,
			'write_commands_enabled' => false,
			'plugin_version'         => COMPLETE99_PLATFORM_VERSION,
			'database_version'       => $database_version,
			'ops_schema_version'     => $schema['stored_version'],
			'modules'                => self::module_statuses(),
		);
	}

	/**
	 * Truthful migration states shown in the read-only shell.
	 */
	private static function module_statuses() {
		return array(
			array(
				'key'         => 'foundation',
				'label'       => __( 'WordPress foundation', 'complete99-platform' ),
				'description' => __( 'Private authentication, schema and audit boundaries.', 'complete99-platform' ),
				'state'       => 'foundation',
				'state_label' => __( 'P1 foundation', 'complete99-platform' ),
			),
			array(
				'key'         => 'today',
				'label'       => __( 'Today, tasks and issues', 'complete99-platform' ),
				'description' => __( 'Tables exist; no legacy records or write commands are enabled.', 'complete99-platform' ),
				'state'       => 'schema-only',
				'state_label' => __( 'Schema only', 'complete99-platform' ),
			),
			array(
				'key'         => 'team',
				'label'       => __( 'Locations and memberships', 'complete99-platform' ),
				'description' => __( 'Foundation tables exist; staff and assignments are not imported.', 'complete99-platform' ),
				'state'       => 'schema-only',
				'state_label' => __( 'Schema only', 'complete99-platform' ),
			),
			array(
				'key'         => 'operations',
				'label'       => __( 'Kitchen, inventory, shifts and files', 'complete99-platform' ),
				'description' => __( 'These operational modules still require later migration slices.', 'complete99-platform' ),
				'state'       => 'not-migrated',
				'state_label' => __( 'Not migrated', 'complete99-platform' ),
			),
			array(
				'key'         => 'growth',
				'label'       => __( 'Campaigns, SEO, finance and projects', 'complete99-platform' ),
				'description' => __( 'These private workflows remain outside this P1 scope.', 'complete99-platform' ),
				'state'       => 'not-migrated',
				'state_label' => __( 'Not migrated', 'complete99-platform' ),
			),
		);
	}

	/**
	 * Return plugin-owned table names for the current site.
	 */
	public static function table_names() {
		global $wpdb;

		return array(
			'locations'         => $wpdb->prefix . 'c99_ops_locations',
			'memberships'       => $wpdb->prefix . 'c99_ops_memberships',
			'tasks'             => $wpdb->prefix . 'c99_ops_tasks',
			'issues'            => $wpdb->prefix . 'c99_ops_issues',
			'commands'          => $wpdb->prefix . 'c99_ops_commands',
			'mutation_receipts' => $wpdb->prefix . 'c99_ops_mutation_receipts',
			'audit_events'      => $wpdb->prefix . 'c99_ops_audit_events',
		);
	}

	/**
	 * Create all operational foundation tables.
	 */
	private static function install_schema_tables() {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		if ( ! function_exists( 'dbDelta' ) ) {
			throw new \RuntimeException( 'The WordPress schema migration utility is unavailable.' );
		}

		$tables          = self::table_names();
		$charset_collate = trim( (string) $wpdb->get_charset_collate() );
		$engine          = self::is_sqlite_database() ? '' : ' ENGINE=InnoDB';
		$suffix          = $engine . ( '' !== $charset_collate ? ' ' . $charset_collate : '' );
		$definitions     = array(
			"CREATE TABLE {$tables['locations']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_id varchar(64) NOT NULL,
				code varchar(64) NOT NULL,
				name_he varchar(191) NOT NULL DEFAULT '',
				name_en varchar(191) NOT NULL DEFAULT '',
				status varchar(24) NOT NULL DEFAULT 'draft',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_id (public_id),
				UNIQUE KEY code (code),
				KEY status (status)
			){$suffix};",
			"CREATE TABLE {$tables['memberships']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				location_id bigint(20) unsigned NOT NULL DEFAULT 0,
				role_key varchar(40) NOT NULL,
				status varchar(24) NOT NULL DEFAULT 'active',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_location_role (user_id,location_id,role_key),
				KEY location_status (location_id,status),
				KEY role_status (role_key,status)
			){$suffix};",
			"CREATE TABLE {$tables['tasks']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_id varchar(64) NOT NULL,
				location_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title text NOT NULL,
				status varchar(24) NOT NULL DEFAULT 'proposed',
				priority varchar(24) NOT NULL DEFAULT 'normal',
				assigned_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				due_at datetime NULL,
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_id (public_id),
				KEY location_status (location_id,status),
				KEY assignee_status (assigned_user_id,status),
				KEY due_at (due_at)
			){$suffix};",
			"CREATE TABLE {$tables['issues']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_id varchar(64) NOT NULL,
				location_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title text NOT NULL,
				details longtext NULL,
				status varchar(24) NOT NULL DEFAULT 'open',
				severity varchar(24) NOT NULL DEFAULT 'normal',
				reported_by bigint(20) unsigned NOT NULL DEFAULT 0,
				assigned_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_id (public_id),
				KEY location_status (location_id,status),
				KEY assignee_status (assigned_user_id,status),
				KEY severity (severity)
			){$suffix};",
			"CREATE TABLE {$tables['commands']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				command_id varchar(64) NOT NULL,
				command_type varchar(96) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL,
				aggregate_type varchar(64) NOT NULL,
				aggregate_id varchar(64) NOT NULL,
				expected_version bigint(20) unsigned NULL,
				payload_digest char(64) NOT NULL,
				status varchar(24) NOT NULL DEFAULT 'received',
				created_at datetime NOT NULL,
				completed_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY command_id (command_id),
				KEY aggregate (aggregate_type,aggregate_id),
				KEY actor_created (actor_user_id,created_at),
				KEY status (status)
			){$suffix};",
			"CREATE TABLE {$tables['mutation_receipts']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				receipt_id varchar(64) NOT NULL,
				command_id varchar(64) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL,
				aggregate_type varchar(64) NOT NULL,
				aggregate_id varchar(64) NOT NULL,
				before_version bigint(20) unsigned NULL,
				after_version bigint(20) unsigned NULL,
				result_digest char(64) NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY receipt_id (receipt_id),
				UNIQUE KEY command_id (command_id),
				KEY aggregate (aggregate_type,aggregate_id),
				KEY actor_created (actor_user_id,created_at)
			){$suffix};",
			"CREATE TABLE {$tables['audit_events']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_id varchar(64) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				action varchar(96) NOT NULL,
				subject_type varchar(64) NOT NULL,
				subject_id varchar(64) NOT NULL,
				command_id varchar(64) NULL,
				payload_digest char(64) NOT NULL,
				occurred_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_id (event_id),
				KEY subject (subject_type,subject_id),
				KEY actor_occurred (actor_user_id,occurred_at),
				KEY command_id (command_id),
				KEY occurred_at (occurred_at)
			){$suffix};",
		);

		foreach ( $definitions as $definition ) {
			dbDelta( $definition );
		}
	}

	/**
	 * Grant only administrators access in P1; worker roles are not activated.
	 */
	private static function install_capability() {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			throw new \RuntimeException( 'The WordPress administrator role is unavailable.' );
		}
		$administrator->add_cap( self::CAPABILITY );
		self::assert_capability();
	}

	/**
	 * Verify schema version, tables and required columns by durable readback.
	 */
	private static function assert_schema() {
		$status = self::schema_status();
		if ( empty( $status['ready'] ) ) {
			throw new \RuntimeException( 'The Complete99 operations schema is incomplete.' );
		}
	}

	/**
	 * Verify table DDL independently from the transaction-bound version marker.
	 */
	private static function assert_schema_tables() {
		$status = self::schema_status();
		if ( ! empty( $status['inspection_failed'] )
			|| ! empty( $status['missing_tables'] )
			|| ! empty( $status['invalid_tables'] )
			|| ! empty( $status['invalid_engines'] )
			|| ! empty( $status['invalid_indexes'] ) ) {
			throw new \RuntimeException( 'The Complete99 operations tables are incomplete.' );
		}
	}

	/**
	 * Verify the administrator capability directly from the roles option.
	 */
	private static function assert_capability() {
		global $wpdb;

		$role_key = $wpdb->get_blog_prefix( get_current_blog_id() ) . 'user_roles';
		$wpdb->last_error = '';
		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$role_key
			)
		);
		if ( '' !== (string) $wpdb->last_error || null === $raw ) {
			throw new \RuntimeException( 'The durable WordPress roles option is unavailable.' );
		}
		$roles = maybe_unserialize( $raw );
		if ( true !== ( $roles['administrator']['capabilities'][ self::CAPABILITY ] ?? null ) ) {
			throw new \RuntimeException( 'The Complete99 operations capability is not durable.' );
		}
	}

	/**
	 * Inspect only schema metadata and bounded record counts.
	 */
	private static function schema_status() {
		global $wpdb;

		$contract = self::schema_contract();
		$tables   = self::table_names();
		$status   = array(
			'ready'                => false,
			'stored_version'       => '',
			'required_table_count' => count( $contract ),
			'present_table_count'  => 0,
			'missing_tables'       => array(),
			'invalid_tables'       => array(),
			'invalid_engines'      => array(),
			'invalid_indexes'      => array(),
			'record_counts'        => array(),
			'inspection_failed'    => false,
		);

		try {
			$stored_version           = self::persisted_schema_version();
			$status['stored_version'] = $stored_version;
			$sqlite = self::is_sqlite_database();
			foreach ( $contract as $key => $requirements ) {
				$table    = $tables[ $key ];
				$metadata = self::table_metadata( $table, $sqlite );
				if ( empty( $metadata['exists'] ) ) {
					$status['missing_tables'][] = $key;
					continue;
				}
				++$status['present_table_count'];
				if ( array_diff( $requirements['columns'], $metadata['columns'] ) ) {
					$status['invalid_tables'][] = $key;
				}
				if ( ! $sqlite && ! in_array( strtolower( $metadata['engine'] ), array( 'innodb', 'xtradb' ), true ) ) {
					$status['invalid_engines'][ $key ] = $metadata['engine'];
				}
				$missing_indexes = self::missing_unique_indexes(
					$requirements['unique_indexes'],
					$metadata['unique_indexes'],
					$sqlite
				);
				if ( $missing_indexes ) {
					$status['invalid_indexes'][ $key ] = $missing_indexes;
				}

				$wpdb->last_error = '';
				$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::quote_identifier( $table ) );
				self::throw_on_database_error();
				if ( ! is_numeric( $count ) ) {
					throw new \RuntimeException( 'Complete99 operations record count could not be verified.' );
				}
				$status['record_counts'][ $key ] = max( 0, (int) $count );
			}
			$status['ready'] = hash_equals( self::SCHEMA_VERSION, $stored_version )
				&& empty( $status['missing_tables'] )
				&& empty( $status['invalid_tables'] )
				&& empty( $status['invalid_engines'] )
				&& empty( $status['invalid_indexes'] );
		} catch ( \Throwable $error ) {
			$status['ready']             = false;
			$status['inspection_failed'] = true;
		}
		return $status;
	}

	/**
	 * Minimal durable column and unique-index contract for each P1 table.
	 */
	private static function schema_contract() {
		return array(
			'locations'         => array(
				'columns'        => array( 'id', 'public_id', 'code', 'status', 'version', 'created_at', 'updated_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'public_id' => array( 'public_id' ), 'code' => array( 'code' ) ),
			),
			'memberships'       => array(
				'columns'        => array( 'id', 'user_id', 'location_id', 'role_key', 'status', 'version', 'created_at', 'updated_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'user_location_role' => array( 'user_id', 'location_id', 'role_key' ) ),
			),
			'tasks'             => array(
				'columns'        => array( 'id', 'public_id', 'location_id', 'title', 'status', 'assigned_user_id', 'version', 'created_at', 'updated_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'public_id' => array( 'public_id' ) ),
			),
			'issues'            => array(
				'columns'        => array( 'id', 'public_id', 'location_id', 'title', 'status', 'severity', 'version', 'created_at', 'updated_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'public_id' => array( 'public_id' ) ),
			),
			'commands'          => array(
				'columns'        => array( 'id', 'command_id', 'command_type', 'actor_user_id', 'aggregate_type', 'aggregate_id', 'expected_version', 'payload_digest', 'status', 'created_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'command_id' => array( 'command_id' ) ),
			),
			'mutation_receipts' => array(
				'columns'        => array( 'id', 'receipt_id', 'command_id', 'actor_user_id', 'aggregate_type', 'aggregate_id', 'before_version', 'after_version', 'result_digest', 'created_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'receipt_id' => array( 'receipt_id' ), 'command_id' => array( 'command_id' ) ),
			),
			'audit_events'      => array(
				'columns'        => array( 'id', 'event_id', 'actor_user_id', 'action', 'subject_type', 'subject_id', 'command_id', 'payload_digest', 'occurred_at' ),
				'unique_indexes' => array( 'primary' => array( 'id' ), 'event_id' => array( 'event_id' ) ),
			),
		);
	}

	/**
	 * Read table, engine, column and unique-index metadata from the active driver.
	 */
	private static function table_metadata( $table, $sqlite ) {
		global $wpdb;

		$metadata = array(
			'exists'         => false,
			'engine'         => $sqlite ? 'sqlite' : '',
			'columns'        => array(),
			'unique_indexes' => array(),
		);
		if ( $sqlite ) {
			$wpdb->last_error = '';
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT name FROM sqlite_master WHERE type = 'table' AND name = %s",
					$table
				)
			);
			self::throw_on_database_error();
			if ( ! is_string( $found ) || ! hash_equals( $table, $found ) ) {
				return $metadata;
			}
			$metadata['exists'] = true;

			$wpdb->last_error = '';
			$column_rows = $wpdb->get_results( 'PRAGMA table_info(' . self::quote_identifier( $table ) . ')', ARRAY_A );
			self::throw_on_database_error();
			$primary = array();
			foreach ( is_array( $column_rows ) ? $column_rows : array() as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['name'] ) ) {
					continue;
				}
				$name = (string) $row['name'];
				$metadata['columns'][] = $name;
				$primary_position = isset( $row['pk'] ) ? (int) $row['pk'] : 0;
				if ( 0 < $primary_position ) {
					$primary[ $primary_position ] = $name;
				}
			}
			if ( $primary ) {
				ksort( $primary, SORT_NUMERIC );
				$metadata['unique_indexes']['primary'] = array_values( $primary );
			}

			$wpdb->last_error = '';
			$index_rows = $wpdb->get_results( 'PRAGMA index_list(' . self::quote_identifier( $table ) . ')', ARRAY_A );
			self::throw_on_database_error();
			foreach ( is_array( $index_rows ) ? $index_rows : array() as $index_row ) {
				if ( ! is_array( $index_row ) || empty( $index_row['unique'] ) || empty( $index_row['name'] ) ) {
					continue;
				}
				$index_name = (string) $index_row['name'];
				$wpdb->last_error = '';
				$parts = $wpdb->get_results( 'PRAGMA index_info(' . self::quote_identifier( $index_name ) . ')', ARRAY_A );
				self::throw_on_database_error();
				$columns = array();
				foreach ( is_array( $parts ) ? $parts : array() as $part ) {
					if ( is_array( $part ) && isset( $part['name'] ) ) {
						$columns[ isset( $part['seqno'] ) ? (int) $part['seqno'] : count( $columns ) ] = (string) $part['name'];
					}
				}
				ksort( $columns, SORT_NUMERIC );
				$metadata['unique_indexes'][ strtolower( $index_name ) ] = array_values( $columns );
			}
			return $metadata;
		}

		$like = method_exists( $wpdb, 'esc_like' ) ? $wpdb->esc_like( $table ) : addcslashes( $table, '_%\\' );
		$wpdb->last_error = '';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
		self::throw_on_database_error();
		if ( ! is_string( $found ) || ! hash_equals( $table, $found ) ) {
			return $metadata;
		}
		$metadata['exists'] = true;

		$wpdb->last_error = '';
		$table_row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $like ), ARRAY_A );
		self::throw_on_database_error();
		$metadata['engine'] = is_array( $table_row ) && isset( $table_row['Engine'] ) ? (string) $table_row['Engine'] : '';

		$wpdb->last_error = '';
		$columns = $wpdb->get_col( 'SHOW COLUMNS FROM ' . self::quote_identifier( $table ), 0 );
		self::throw_on_database_error();
		$metadata['columns'] = is_array( $columns ) ? array_map( 'strval', $columns ) : array();

		$wpdb->last_error = '';
		$index_rows = $wpdb->get_results( 'SHOW INDEX FROM ' . self::quote_identifier( $table ), ARRAY_A );
		self::throw_on_database_error();
		$indexes = array();
		foreach ( is_array( $index_rows ) ? $index_rows : array() as $row ) {
			if ( ! is_array( $row )
				|| 0 !== (int) ( $row['Non_unique'] ?? 1 )
				|| empty( $row['Key_name'] )
				|| empty( $row['Column_name'] ) ) {
				continue;
			}
			$name = strtolower( (string) $row['Key_name'] );
			$sequence = isset( $row['Seq_in_index'] ) ? (int) $row['Seq_in_index'] : count( $indexes[ $name ] ?? array() ) + 1;
			$indexes[ $name ][ $sequence ] = (string) $row['Column_name'];
		}
		foreach ( $indexes as $name => $columns ) {
			ksort( $columns, SORT_NUMERIC );
			$metadata['unique_indexes'][ $name ] = array_values( $columns );
		}
		return $metadata;
	}

	/**
	 * Return required unique keys that are absent or structurally different.
	 */
	private static function missing_unique_indexes( $required, $actual, $sqlite ) {
		$missing = array();
		foreach ( $required as $name => $columns ) {
			$name = strtolower( (string) $name );
			if ( isset( $actual[ $name ] ) && $columns === array_values( $actual[ $name ] ) ) {
				continue;
			}
			if ( $sqlite ) {
				$found_by_columns = false;
				foreach ( $actual as $actual_columns ) {
					if ( $columns === array_values( $actual_columns ) ) {
						$found_by_columns = true;
						break;
					}
				}
				if ( $found_by_columns ) {
					continue;
				}
			}
			$missing[] = $name;
		}
		return $missing;
	}

	private static function throw_on_database_error() {
		global $wpdb;

		if ( '' !== (string) $wpdb->last_error ) {
			throw new \RuntimeException( 'Complete99 operations schema inspection failed.' );
		}
	}

	/**
	 * Read the schema marker directly, bypassing filters and object cache.
	 */
	private static function persisted_schema_version() {
		global $wpdb;

		$wpdb->last_error = '';
		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::OPTION_SCHEMA_VERSION
			)
		);
		if ( '' !== (string) $wpdb->last_error || null === $raw ) {
			return '';
		}
		$value = maybe_unserialize( $raw );
		return is_string( $value ) ? $value : '';
	}

	private static function quote_identifier( $identifier ) {
		return '`' . str_replace( '`', '``', (string) $identifier ) . '`';
	}

	private static function is_sqlite_database() {
		global $wpdb;

		$database_type = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
		if ( 'sqlite' === $database_type ) {
			return true;
		}
		$database_type = defined( 'DATABASE_TYPE' ) ? strtolower( (string) DATABASE_TYPE ) : '';
		return 'sqlite' === $database_type || false !== strpos( strtolower( get_class( $wpdb ) ), 'sqlite' );
	}
}
