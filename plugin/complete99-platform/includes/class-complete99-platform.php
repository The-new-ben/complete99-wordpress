<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Platform {
	/**
	 * Retain the update checker instance for the request lifetime.
	 *
	 * @var object|null
	 */
	private static $update_checker = null;

	/**
	 * Generic per-request migration failure marker. No database details are exposed.
	 *
	 * @var bool
	 */
	private static $migration_failed = false;

	/**
	 * Register all runtime hooks.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'boot_update_checker' ), 5 );
		add_action( 'init', array( 'Complete99_Content', 'register' ), 5 );
		add_action( 'init', array( 'Complete99_Content', 'register_rewrites' ), 20 );
		add_action( 'init', array( 'Complete99_Leads', 'register_post_type' ), 6 );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 40 );

		Complete99_Settings::boot();
		Complete99_Leads::boot();
		Complete99_REST::boot();
		Complete99_Frontend::boot();
		Complete99_SEO_Registry::boot();
	}

	/**
	 * Boot the vendored PUC 5.6 library as a guarded human fallback.
	 *
	 * Deliberate releases still use the temporary, authenticated deployment
	 * bridge. This checker only supplies the normal wp-admin update path.
	 */
	public static function boot_update_checker() {
		$loader  = COMPLETE99_PLATFORM_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
		$factory = '\YahnisElsts\PluginUpdateChecker\v5\PucFactory';

		if ( ! file_exists( $loader ) ) {
			return;
		}

		try {
			require_once $loader;
			if ( ! class_exists( $factory ) ) {
				return;
			}

			self::$update_checker = $factory::buildUpdateChecker(
				COMPLETE99_PLATFORM_UPDATE_MANIFEST_URL,
				COMPLETE99_PLATFORM_FILE,
				'complete99-platform'
			);
		} catch ( \Throwable $error ) {
			/**
			 * Fires when the optional wp-admin update fallback cannot start.
			 *
			 * The main plugin remains available; deliberate deployments use
			 * the independently authenticated deployment bridge.
			 *
			 * @param \Throwable $error Update-checker boot failure.
			 */
			do_action( 'complete99_platform_update_checker_error', $error );
		}
	}

	/**
	 * Install data model, roles, settings and deterministic launch content.
	 */
	public static function activate() {
		$result = self::run_migration( true );
		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( 'Complete99 activation could not commit its database migration.' );
		}
	}

	/**
	 * Seed additions on upgrades without overwriting editor changes.
	 */
	public static function maybe_upgrade() {
		$current = (string) get_option( 'complete99_platform_version', '' );
		if ( COMPLETE99_PLATFORM_VERSION === $current ) {
			return;
		}

		$result = self::run_migration( false );
		if ( is_wp_error( $result ) ) {
			self::$migration_failed = true;
			do_action( 'complete99_platform_migration_error', $result );
		}
	}

	/**
	 * Run every plugin-owned database mutation in one transaction.
	 *
	 * An interrupted connection rolls back seed posts, metadata, options and role
	 * changes together. The deployment bridge separately verifies that the three
	 * affected WordPress tables use transactional storage.
	 *
	 * @param bool $hard_flush Whether to write a full rewrite-rule refresh.
	 * @return true|\WP_Error
	 */
	private static function run_migration( $hard_flush ) {
		global $wpdb;
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return new WP_Error( 'complete99_migration_transaction', 'The Complete99 database migration could not start.' );
		}
		try {
			Complete99_Content::register();
			Complete99_Content::register_rewrites();
			Complete99_Leads::register_post_type();
			Complete99_Content::install_roles();
			Complete99_Settings::install_defaults();
			Complete99_Content::seed_launch_content();
			update_option( 'complete99_platform_version', COMPLETE99_PLATFORM_VERSION, false );
			update_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID, false );
			flush_rewrite_rules( (bool) $hard_flush );
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new \RuntimeException( 'commit' );
			}
			wp_cache_flush();
			return true;
		} catch ( \Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			wp_cache_flush();
			return new WP_Error( 'complete99_migration_failed', 'The Complete99 database migration could not be committed.' );
		}
	}

	/**
	 * Whether this request observed a migration failure.
	 */
	public static function migration_failed() {
		return self::$migration_failed;
	}

	/**
	 * Rewrite rules are removed on deactivation; content and settings remain.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
