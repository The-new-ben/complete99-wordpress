<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress-native, private Complete99 operations foundation.
 *
 * Campaign Studio is the first capability-controlled operational vertical.
 * Other modules remain schema-only or pending, and no legacy record is ever
 * presented as migrated without exact evidence.
 */
final class Complete99_Ops {
	const PAGE_SLUG             = 'complete99-os';
	const CAPABILITY            = 'complete99_view_operations';
	const REST_NAMESPACE        = 'complete99/v1';
	const REST_ROUTE            = '/ops/status';
	const WORKER_ROLE           = 'complete99_campaign_worker';
	const WORKER_CAPABILITY     = 'complete99_run_campaign_worker';
	const WORKER_ROUTE          = '/ops/campaign-worker';
	const WORKER_SCHEMA         = 'complete99-campaign-worker-monitor/v1';
	const WORKER_INTERVAL       = 900;
	const WORKER_MAX_AGE        = 4500;
	const SCHEMA_VERSION        = 'complete99-ops-schema/v1';
	const OPTION_SCHEMA_VERSION = 'complete99_ops_schema_version';

	/** @var int User authenticated by WordPress core's Application Password provider. */
	private static $application_password_user_id = 0;

	/** @var string Validated UUID of the core Application Password used this request. */
	private static $application_password_uuid = '';

	/**
	 * Register private admin and REST surfaces.
	 */
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'application_password_did_authenticate', array( __CLASS__, 'note_application_password_authentication' ), 10, 2 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'prevent_worker_response_caching' ), 10, 3 );
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
	 * Register the private status endpoint and one fixed Campaign worker operation.
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
		register_rest_route(
			self::REST_NAMESPACE,
			self::WORKER_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'authorize_campaign_worker' ),
				'callback'            => array( __CLASS__, 'rest_campaign_worker' ),
			)
		);
	}

	/**
	 * Record only WordPress core's successful Application Password provenance.
	 *
	 * The secret and application metadata are deliberately never retained.
	 */
	public static function note_application_password_authentication( $user, $item ) {
		self::$application_password_user_id = 0;
		self::$application_password_uuid    = '';
		$user_id = is_object( $user ) && isset( $user->ID ) ? absint( $user->ID ) : 0;
		$uuid    = is_array( $item ) && is_string( $item['uuid'] ?? null ) ? strtolower( trim( $item['uuid'] ) ) : '';
		if ( 0 < $user_id && 1 === preg_match( '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $uuid ) ) {
			self::$application_password_user_id = $user_id;
			self::$application_password_uuid    = $uuid;
		}
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
	 * Require the exact current-site worker role and core Application Password auth.
	 */
	public static function authorize_campaign_worker( WP_REST_Request $request ) {
		unset( $request );
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'complete99_campaign_worker_authentication_required',
				'WordPress Application Password authentication is required.',
				array( 'status' => 401 )
			);
		}

		$user = wp_get_current_user();
		$user_id = is_object( $user ) && isset( $user->ID ) ? absint( $user->ID ) : 0;
		$official_application_password = 0 < $user_id
			&& $user_id === self::$application_password_user_id
			&& '' !== self::$application_password_uuid;
		$role = self::worker_role_status();
		if ( ! $official_application_password
			|| ! self::worker_user_is_enabled( $user )
			|| is_super_admin( $user_id )
			|| empty( $role['ready'] )
			|| ! self::worker_user_assignment_is_exact( $user_id )
			|| ! current_user_can( self::WORKER_CAPABILITY ) ) {
			return new WP_Error(
				'complete99_campaign_worker_forbidden',
				'This WordPress account cannot run the Campaign worker.',
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
	 * Run exactly one bounded Campaign reconciliation as the system actor.
	 */
	public static function rest_campaign_worker( WP_REST_Request $request ) {
		unset( $request );
		if ( ! self::campaign_worker_prerequisites_ready() ) {
			return self::campaign_worker_unavailable_response();
		}

		$authenticated_user_id = get_current_user_id();
		if ( 1 > $authenticated_user_id ) {
			return self::campaign_worker_unavailable_response();
		}
		$failed                = false;
		$restore_failed        = false;
		$cron_runner           = null;
		try {
			wp_set_current_user( 0 );
			if ( 0 !== get_current_user_id() ) {
				throw new \RuntimeException( 'The Campaign worker system identity could not be established.' );
			}
			$result = Complete99_Campaigns::reconcile_schedules();
			if ( true !== $result ) {
				$failed = true;
			} else {
				$status      = Complete99_Campaigns::operational_status();
				$cron_runner = is_array( $status ) ? ( $status['cron_runner'] ?? null ) : null;
				$failed      = ! self::campaign_worker_heartbeat_is_fresh( $cron_runner );
			}
		} catch ( \Throwable $error ) {
			unset( $error );
			$failed = true;
		} finally {
			try {
				wp_set_current_user( $authenticated_user_id );
				$restore_failed = $authenticated_user_id !== get_current_user_id();
			} catch ( \Throwable $restore_error ) {
				unset( $restore_error );
				$restore_failed = true;
			}
		}

		if ( $failed || $restore_failed ) {
			return self::campaign_worker_unavailable_response();
		}

		return self::campaign_worker_response(
			array(
				'schemaVersion'   => self::WORKER_SCHEMA,
				'workerCompleted' => true,
				'cronRunner'      => array(
					'ready'         => true,
					'inspectable'   => true,
					'lastAt'        => (string) $cron_runner['lastAt'],
					'ageSeconds'    => (int) $cron_runner['ageSeconds'],
					'maxAgeSeconds' => self::WORKER_MAX_AGE,
				),
			),
			200
		);
	}

	/** Apply a no-store boundary to every response from the worker route. */
	public static function prevent_worker_response_caching( $response, $server, $request ) {
		unset( $server );
		$route = is_object( $request ) && is_callable( array( $request, 'get_route' ) ) ? (string) $request->get_route() : '';
		if ( '/' . self::REST_NAMESPACE . self::WORKER_ROUTE === $route ) {
			self::apply_worker_no_store_headers( $response );
		}
		return $response;
	}

	/**
	 * Render a truthful Today foundation and operational campaign entrypoint.
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
		$campaign_operational = ! empty( $status['campaign_view_enabled'] ) && ! empty( $status['campaigns']['ready'] );
		?>
		<div class="wrap c99-ops" data-c99-ops-shell="<?php echo esc_attr( $campaign_operational ? 'campaign-operational' : 'campaign-unavailable' ); ?>">
			<header class="c99-ops__hero">
				<div>
					<p class="c99-ops__eyebrow"><?php echo esc_html__( 'Private WordPress workspace', 'complete99-platform' ); ?></p>
					<h1><?php echo esc_html__( 'Complete99 OS: Today', 'complete99-platform' ); ?></h1>
					<p class="c99-ops__lede"><?php echo esc_html( $campaign_operational ? __( 'Campaign Studio is a verified WordPress-native operational module for this account. Other daily modules and legacy records remain pending and are never presented as migrated truth.', 'complete99-platform' ) : __( 'Campaign Studio is unavailable until its schema, rollback capacity, durable capabilities and this account’s access are verified. Other daily modules and legacy records remain pending.', 'complete99-platform' ) ); ?></p>
				</div>
				<div class="c99-ops__status <?php echo $ready ? 'is-ready' : 'is-blocked'; ?>" data-c99-ops-status>
					<strong data-c99-ops-status-label><?php echo esc_html( $ready ? __( 'Foundation ready', 'complete99-platform' ) : __( 'Migration incomplete', 'complete99-platform' ) ); ?></strong>
					<span data-c99-ops-status-detail><?php echo esc_html( $ready ? __( 'Verified from durable WordPress storage', 'complete99-platform' ) : __( 'No operational actions are available', 'complete99-platform' ) ); ?></span>
				</div>
			</header>

			<div class="c99-ops__facts" aria-label="<?php echo esc_attr__( 'Foundation guarantees', 'complete99-platform' ); ?>">
				<span><?php echo esc_html__( 'Authentication: WordPress', 'complete99-platform' ); ?></span>
				<span><?php echo esc_html__( 'ChatGPT login: not required', 'complete99-platform' ); ?></span>
				<span><?php echo esc_html( ! empty( $status['write_commands_enabled'] ) ? __( 'Campaign writes: enabled for this account', 'complete99-platform' ) : __( 'Campaign writes: unavailable for this account', 'complete99-platform' ) ); ?></span>
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
								<span><strong><?php if ( ! empty( $module['url'] ) ) : ?><a href="<?php echo esc_url( $module['url'] ); ?>"><?php echo esc_html( $module['label'] ); ?></a><?php else : ?><?php echo esc_html( $module['label'] ); ?><?php endif; ?></strong><small><?php echo esc_html( $module['description'] ); ?></small></span>
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
		$worker_role      = self::worker_role_status();
		$campaign         = class_exists( 'Complete99_Campaigns' ) ? Complete99_Campaigns::operational_status() : array( 'ready' => false, 'campaign_count' => 0 );
		$campaign_ready   = class_exists( 'Complete99_Campaigns' ) && ! empty( $campaign['ready'] );
		$campaign_view    = $campaign_ready && current_user_can( Complete99_Campaigns::VIEW_CAPABILITY );
		$campaign_write   = false;
		if ( $campaign_view ) {
			foreach ( array( Complete99_Campaigns::MANAGE_CAPABILITY, Complete99_Campaigns::APPROVE_CAPABILITY, Complete99_Campaigns::SCHEDULE_CAPABILITY, Complete99_Campaigns::EVIDENCE_CAPABILITY, Complete99_Campaigns::RESULTS_CAPABILITY, Complete99_Campaigns::MODERATE_CAPABILITY ) as $capability ) {
				if ( current_user_can( $capability ) ) { $campaign_write = true; break; }
			}
		}
		$database_version = (string) get_option( 'complete99_platform_version', '' );
		$migration_failed = ! is_callable( array( 'Complete99_Platform', 'migration_failed' ) )
			|| Complete99_Platform::migration_failed();
		$ready = ! $migration_failed
			&& COMPLETE99_PLATFORM_VERSION === $database_version
			&& ! empty( $schema['ready'] )
			&& ! empty( $worker_role['ready'] )
			&& $campaign_ready;
		$campaign_operational_ready = $ready && $campaign_ready;
		$campaign_operational_view  = $campaign_operational_ready && $campaign_view;

		return array(
			'schema'                 => $schema,
			'status_schema'          => 'complete99-ops-status/v1',
			'ready'                  => $ready,
			'mode'                   => $campaign_operational_view ? 'campaign_operational_other_modules_pending' : 'campaign_unavailable_other_modules_pending',
			'auth_provider'          => 'wordpress',
			'chatgpt_login_required' => false,
			'write_commands_enabled' => $ready && $campaign_write,
			'campaign_view_enabled'  => $campaign_operational_view,
			'campaigns'              => $campaign,
			'campaign_worker'        => $worker_role,
			'plugin_version'         => COMPLETE99_PLATFORM_VERSION,
			'database_version'       => $database_version,
			'ops_schema_version'     => $schema['stored_version'],
			'modules'                => self::module_statuses( $campaign_operational_ready, $campaign_operational_view ),
		);
	}

	/**
	 * Truthful migration states shown in the read-only shell.
	 */
	private static function module_statuses( $campaign_ready, $campaign_view ) {
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
				'key'         => 'campaigns',
				'label'       => __( 'Campaign Studio', 'complete99-platform' ),
				'description' => $campaign_ready ? ( $campaign_view ? __( 'Private drafting, approval, owned-site scheduling, evidence, moderation and results.', 'complete99-platform' ) : __( 'The module is migrated, but this WordPress account lacks its view capability.', 'complete99-platform' ) ) : __( 'The module remains unavailable because its schema, rollback capacity or durable capabilities could not be verified.', 'complete99-platform' ),
				'state'       => $campaign_view ? 'operational' : ( $campaign_ready ? 'restricted' : 'unavailable' ),
				'state_label' => $campaign_view ? __( 'Operational', 'complete99-platform' ) : ( $campaign_ready ? __( 'Restricted', 'complete99-platform' ) : __( 'Unavailable', 'complete99-platform' ) ),
				'url'         => $campaign_view ? admin_url( 'admin.php?page=' . Complete99_Campaigns::PAGE_SLUG ) : '',
			),
			array(
				'key'         => 'growth',
				'label'       => __( 'SEO, finance and projects', 'complete99-platform' ),
				'description' => __( 'These remaining private workflows still require later migration slices.', 'complete99-platform' ),
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
	 * Grant private status access and normalize the dedicated least-privilege role.
	 */
	private static function install_capability() {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			throw new \RuntimeException( 'The WordPress administrator role is unavailable.' );
		}
		$administrator->add_cap( self::CAPABILITY );
		$administrator->remove_cap( self::WORKER_CAPABILITY );

		$allowed = self::worker_role_capabilities();
		$worker  = get_role( self::WORKER_ROLE );
		if ( ! $worker ) {
			$worker = add_role(
				self::WORKER_ROLE,
				__( 'Complete99 Campaign Worker', 'complete99-platform' ),
				$allowed
			);
		}
		if ( ! $worker ) {
			throw new \RuntimeException( 'The Complete99 Campaign worker role could not be created.' );
		}
		foreach ( array_keys( (array) $worker->capabilities ) as $capability ) {
			if ( ! array_key_exists( $capability, $allowed ) ) {
				$worker->remove_cap( $capability );
			}
		}
		foreach ( $allowed as $capability => $grant ) {
			$worker->add_cap( $capability, $grant );
		}
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
	 * Verify the administrator capability and exact worker role from storage.
	 */
	private static function assert_capability() {
		$roles = self::persisted_roles();
		if ( true !== ( $roles['administrator']['capabilities'][ self::CAPABILITY ] ?? null ) ) {
			throw new \RuntimeException( 'The Complete99 operations capability is not durable.' );
		}
		if ( array_key_exists( self::WORKER_CAPABILITY, (array) ( $roles['administrator']['capabilities'] ?? array() ) ) ) {
			throw new \RuntimeException( 'The administrator role must not hold the Campaign worker capability.' );
		}
		$stored_worker = (array) ( $roles[ self::WORKER_ROLE ]['capabilities'] ?? array() );
		ksort( $stored_worker );
		$expected_worker = self::worker_role_capabilities();
		ksort( $expected_worker );
		if ( $expected_worker !== $stored_worker ) {
			throw new \RuntimeException( 'The Complete99 Campaign worker role is not exact and durable.' );
		}
	}

	/** Exact capabilities allowed on the dedicated monitor role. */
	private static function worker_role_capabilities() {
		return array(
			'read'                  => true,
			self::WORKER_CAPABILITY => true,
		);
	}

	/** Read the current site's role definitions directly from durable storage. */
	private static function persisted_roles() {
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
		if ( ! is_array( $roles ) ) {
			throw new \RuntimeException( 'The durable WordPress roles option is malformed.' );
		}
		return $roles;
	}

	/** Return a bounded readiness projection for the dedicated worker role. */
	private static function worker_role_status() {
		$ready = false;
		try {
			$roles         = self::persisted_roles();
			$administrator = (array) ( $roles['administrator']['capabilities'] ?? array() );
			$worker        = (array) ( $roles[ self::WORKER_ROLE ]['capabilities'] ?? array() );
			$expected      = self::worker_role_capabilities();
			ksort( $worker );
			ksort( $expected );
			$ready = true === ( $administrator[ self::CAPABILITY ] ?? null )
				&& ! array_key_exists( self::WORKER_CAPABILITY, $administrator )
				&& $expected === $worker;
		} catch ( \Throwable $error ) {
			unset( $error );
			$ready = false;
		}
		return array(
			'ready'          => $ready,
			'role'           => self::WORKER_ROLE,
			'capability'     => self::WORKER_CAPABILITY,
			'authentication' => 'wordpress_application_password',
		);
	}

	/** Accept only exact enabled-state values returned by WordPress user storage. */
	private static function worker_user_is_enabled( $user ) {
		if ( ! is_object( $user ) ) {
			return false;
		}
		if ( ! isset( $user->user_status ) || ! in_array( $user->user_status, array( 0, '0' ), true ) ) {
			return false;
		}
		foreach ( array( 'spam', 'deleted' ) as $property ) {
			if ( isset( $user->{$property} ) && ! in_array( $user->{$property}, array( 0, '0' ), true ) ) {
				return false;
			}
			if ( is_multisite() && ! isset( $user->{$property} ) ) {
				return false;
			}
		}
		return true;
	}

	/** Prove a user has exactly the current-site worker role and no direct grants. */
	private static function worker_user_assignment_is_exact( $user_id ) {
		global $wpdb;

		if ( 1 > (int) $user_id ) {
			return false;
		}
		$meta_key = $wpdb->get_blog_prefix( get_current_blog_id() ) . 'capabilities';
		$wpdb->last_error = '';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id ASC LIMIT 2",
				(int) $user_id,
				$meta_key
			),
			ARRAY_A
		);
		if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) || 1 !== count( $rows ) ) {
			return false;
		}
		$assignment = maybe_unserialize( (string) ( $rows[0]['meta_value'] ?? '' ) );
		if ( array( self::WORKER_ROLE => true ) !== $assignment ) {
			return false;
		}

		return self::worker_user_has_only_current_site_membership(
			(int) $user_id,
			(int) ( $rows[0]['umeta_id'] ?? 0 )
		);
	}

	/**
	 * Bound multisite membership proof to two durable rows and require the
	 * current assignment row to be the only registered-site membership.
	 */
	private static function worker_user_has_only_current_site_membership( $user_id, $current_umeta_id ) {
		global $wpdb;

		if ( ! is_multisite() ) {
			return true;
		}
		$blogs_table = isset( $wpdb->blogs ) ? (string) $wpdb->blogs : '';
		$base_prefix = isset( $wpdb->base_prefix ) ? (string) $wpdb->base_prefix : '';
		if ( 1 > (int) $user_id || 1 > (int) $current_umeta_id || '' === $blogs_table || '' === $base_prefix ) {
			return false;
		}

		$wpdb->last_error = '';
		$memberships = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.blog_id,um.umeta_id FROM {$blogs_table} b INNER JOIN {$wpdb->usermeta} um ON um.user_id=%d AND um.meta_key=CONCAT(%s,CASE WHEN b.blog_id=1 THEN '' ELSE CONCAT(b.blog_id,'_') END,'capabilities') ORDER BY b.blog_id ASC,um.umeta_id ASC LIMIT 2",
				(int) $user_id,
				$base_prefix
			),
			ARRAY_A
		);
		return '' === (string) $wpdb->last_error
			&& is_array( $memberships )
			&& 1 === count( $memberships )
			&& get_current_blog_id() === (int) ( $memberships[0]['blog_id'] ?? 0 )
			&& (int) $current_umeta_id === (int) ( $memberships[0]['umeta_id'] ?? 0 );
	}

	/** Refuse worker writes until the platform and Campaign write foundations are ready. */
	private static function campaign_worker_prerequisites_ready() {
		try {
			if ( ! class_exists( 'Complete99_Campaigns' )
				|| ! is_callable( array( 'Complete99_Campaigns', 'reconcile_schedules' ) )
				|| ! is_callable( array( 'Complete99_Campaigns', 'operational_status' ) )
				|| ! is_callable( array( 'Complete99_Campaigns', 'begin_worker_execution_fence' ) )
				|| ! is_callable( array( 'Complete99_Campaigns', 'end_worker_execution_fence' ) )
				|| ! is_callable( array( 'Complete99_Campaigns', 'worker_execution_fence_lock_name' ) )
				|| ! is_callable( array( 'Complete99_Campaigns', 'worker_quiescence_status' ) )
				|| ! defined( 'Complete99_Campaigns::SCHEMA_VERSION' )
				|| ! defined( 'Complete99_Campaigns::SYSTEM_CRON_INTERVAL_SECONDS' )
				|| ! defined( 'Complete99_Campaigns::CRON_HEARTBEAT_MAX_AGE' )
				|| self::WORKER_INTERVAL !== Complete99_Campaigns::SYSTEM_CRON_INTERVAL_SECONDS
				|| self::WORKER_MAX_AGE !== Complete99_Campaigns::CRON_HEARTBEAT_MAX_AGE
				|| ! is_callable( array( 'Complete99_Platform', 'migration_failed' ) )
				|| Complete99_Platform::migration_failed()
				|| COMPLETE99_PLATFORM_VERSION !== (string) get_option( 'complete99_platform_version', '' ) ) {
				return false;
			}
			$ops_schema = self::schema_status();
			$worker_role = self::worker_role_status();
			if ( ! is_array( $ops_schema )
				|| true !== ( $ops_schema['ready'] ?? null )
				|| self::SCHEMA_VERSION !== ( $ops_schema['stored_version'] ?? null )
				|| true !== ( $worker_role['ready'] ?? null ) ) {
				return false;
			}

			$lock_name = Complete99_Campaigns::worker_execution_fence_lock_name();
			$quiescence = Complete99_Campaigns::worker_quiescence_status();
			if ( ! is_string( $lock_name )
				|| '' === $lock_name
				|| 64 < strlen( $lock_name )
				|| ! is_array( $quiescence )
				|| true !== ( $quiescence['ready'] ?? null ) ) {
				return false;
			}

			$status   = Complete99_Campaigns::operational_status();
			$capacity = is_array( $status ) ? ( $status['capacity'] ?? null ) : null;
			$cohorts  = is_array( $capacity ) ? ( $capacity['cohorts'] ?? null ) : null;
			return is_array( $status )
				&& Complete99_Campaigns::SCHEMA_VERSION === (string) ( $status['schema_version'] ?? '' )
				&& true === ( $status['capabilities_ready'] ?? null )
				&& is_array( $capacity )
				&& true === ( $capacity['ready'] ?? null )
				&& true === ( $capacity['inspectable'] ?? null )
				&& is_array( $cohorts )
				&& true === ( $cohorts['operations']['writeReady'] ?? null )
				&& true === ( $cohorts['campaign']['writeReady'] ?? null );
		} catch ( \Throwable $error ) {
			unset( $error );
			return false;
		}
	}

	/** Validate the exact 75-minute durable heartbeat acceptance contract. */
	private static function campaign_worker_heartbeat_is_fresh( $heartbeat ) {
		if ( ! is_array( $heartbeat )
			|| true !== ( $heartbeat['ready'] ?? null )
			|| true !== ( $heartbeat['inspectable'] ?? null )
			|| ! is_string( $heartbeat['lastAt'] ?? null )
			|| 1 !== preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/D', $heartbeat['lastAt'] )
			|| ! is_int( $heartbeat['ageSeconds'] ?? null )
			|| 0 > $heartbeat['ageSeconds']
			|| self::WORKER_MAX_AGE < $heartbeat['ageSeconds']
			|| self::WORKER_MAX_AGE !== ( $heartbeat['maxAgeSeconds'] ?? null ) ) {
			return false;
		}
		$last_at = strtotime( $heartbeat['lastAt'] );
		if ( false === $last_at || $last_at > time() + 300 ) {
			return false;
		}
		$observed_age = max( 0, time() - $last_at );
		return 5 >= abs( $observed_age - $heartbeat['ageSeconds'] );
	}

	/** Return one generic, bounded worker failure without exposing internal state. */
	private static function campaign_worker_unavailable_response() {
		return self::campaign_worker_response(
			array(
				'schemaVersion'   => self::WORKER_SCHEMA,
				'workerCompleted' => false,
				'state'           => 'unavailable',
			),
			503
		);
	}

	/** Build a bounded response and apply the route's cache prohibition immediately. */
	private static function campaign_worker_response( $payload, $status ) {
		$response = new WP_REST_Response( $payload, (int) $status );
		self::apply_worker_no_store_headers( $response );
		return $response;
	}

	/** Apply headers without assuming a particular REST response implementation. */
	private static function apply_worker_no_store_headers( $response ) {
		if ( ! is_object( $response ) || ! is_callable( array( $response, 'header' ) ) ) {
			return;
		}
		$response->header( 'Cache-Control', 'no-store, private, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );
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
