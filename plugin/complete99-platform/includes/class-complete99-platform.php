<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Platform {
	const MIGRATION_LOCK_TIMEOUT = 10;

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

		Complete99_Content::boot_governance();
		Complete99_Settings::boot();
		Complete99_Leads::boot();
		Complete99_REST::boot();
		Complete99_Commerce::boot();
		Complete99_Catalog_Graph::boot();
		Complete99_Evaluation_Catalog::boot();
		Complete99_Live_Catalog::boot();
		Complete99_Inventory_Bridge::boot();
		Complete99_Culinary_Science::boot();
		Complete99_Culinary_Commerce::boot();
		Complete99_Review_Lab::boot();
		Complete99_Frontend::boot();
		Complete99_Live_Dish_Sitemap_Provider::boot();
		Complete99_Culinary_Museum_Frontend::boot();
		Complete99_Culinary_Museum_Sitemap_Provider::boot();
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

	private static function migration_lock_name() {
		global $wpdb;

		$identity = get_current_blog_id() . '|' . $wpdb->prefix . '|' . home_url( '/' );
		return 'complete99-migration-' . substr( hash( 'sha256', $identity ), 0, 40 );
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

	/**
	 * Serialize production migrations across PHP requests.
	 *
	 * MySQL named locks are connection-scoped and release automatically if PHP
	 * exits unexpectedly. The SQLite integration used by local acceptance runs
	 * receives an equivalent bounded filesystem lock.
	 *
	 * @return array|\WP_Error
	 */
	private static function acquire_migration_lock() {
		global $wpdb;

		if ( self::is_sqlite_database() ) {
			$path   = trailingslashit( WP_CONTENT_DIR ) . '.complete99-platform-migration.lock';
			$handle = @fopen( $path, 'c+' );
			if ( false === $handle ) {
				return new WP_Error( 'complete99_migration_lock_open', 'The Complete99 migration lock is unavailable.' );
			}
			$deadline = microtime( true ) + self::MIGRATION_LOCK_TIMEOUT;
			do {
				if ( @flock( $handle, LOCK_EX | LOCK_NB ) ) {
					return array(
						'driver' => 'file',
						'handle' => $handle,
					);
				}
				usleep( 100000 );
			} while ( microtime( true ) < $deadline );

			@fclose( $handle );
			return new WP_Error( 'complete99_migration_locked', 'Another Complete99 migration is still running.' );
		}

		if ( true !== $wpdb->is_mysql ) {
			return new WP_Error( 'complete99_migration_lock_driver', 'The production database does not support the required Complete99 advisory lock.' );
		}

		$name             = self::migration_lock_name();
		$previous_suppress = $wpdb->suppress_errors( true );
		$wpdb->last_error = '';
		$acquired = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT GET_LOCK(%s, %d)',
				$name,
				self::MIGRATION_LOCK_TIMEOUT
			)
		);
		$error = (string) $wpdb->last_error;
		$wpdb->suppress_errors( $previous_suppress );
		if ( '' !== $error || 1 !== (int) $acquired ) {
			return new WP_Error( 'complete99_migration_locked', 'The Complete99 migration advisory lock could not be acquired.' );
		}

		return array(
			'driver' => 'mysql',
			'name'   => $name,
		);
	}

	private static function release_migration_lock( $lock ) {
		global $wpdb;

		if ( ! is_array( $lock ) ) {
			return false;
		}
		if ( 'file' === ( $lock['driver'] ?? '' ) ) {
			$handle = $lock['handle'] ?? null;
			if ( is_resource( $handle ) ) {
				@flock( $handle, LOCK_UN );
				@fclose( $handle );
			}
			return true;
		}
		if ( 'mysql' !== ( $lock['driver'] ?? '' ) || empty( $lock['name'] ) ) {
			return false;
		}

		$previous_suppress = $wpdb->suppress_errors( true );
		$released = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT RELEASE_LOCK(%s)',
				(string) $lock['name']
			)
		);
		$wpdb->suppress_errors( $previous_suppress );
		return 1 === (int) $released;
	}

	private static function persisted_option( $name ) {
		global $wpdb;

		$wpdb->last_error = '';
		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				(string) $name
			)
		);
		if ( '' !== (string) $wpdb->last_error ) {
			throw new \RuntimeException( 'option-readback' );
		}
		return null === $raw ? null : maybe_unserialize( $raw );
	}

	/**
	 * Return a price-free durable evaluation catalog status.
	 *
	 * @return array
	 */
	public static function evaluation_catalog_status() {
		$fallback = array(
			'schema'       => 'complete99-evaluation-catalog-status/v1',
			'ready'        => false,
			'reason'       => 'durable_read_failed',
			'receipt'      => array(
				'present'            => false,
				'valid'              => false,
				'status'             => '',
				'mode'               => '',
				'seed_count'         => 0,
				'ingredient_count'   => 0,
				'product_plan_count' => 0,
				'woo_product_count'  => 0,
				'woo_materialized'   => false,
			),
			'materialized' => array(
				'ingredient_count'   => 0,
				'product_plan_count' => 0,
			),
		);
		if ( ! class_exists( 'Complete99_Evaluation_Catalog', false )
			|| ! is_callable( array( 'Complete99_Evaluation_Catalog', 'persisted_status' ) ) ) {
			$fallback['reason'] = 'module_unavailable';
			return $fallback;
		}
		try {
			$receipt = self::persisted_option( Complete99_Evaluation_Catalog::OPTION_RECEIPT );
			$status  = Complete99_Evaluation_Catalog::persisted_status( $receipt );
			return is_array( $status ) ? $status : $fallback;
		} catch ( \Throwable $error ) {
			return $fallback;
		}
	}

	/**
	 * Whether the exact private evaluation catalog is durably ready.
	 */
	public static function evaluation_catalog_ready() {
		$status = self::evaluation_catalog_status();
		return true === ( $status['ready'] ?? false );
	}

	/**
	 * Fail the migration or deployment checkpoint on any catalog drift.
	 */
	public static function assert_evaluation_catalog_invariants() {
		if ( ! self::evaluation_catalog_ready() ) {
			throw new \RuntimeException( 'evaluation-catalog-invariants' );
		}
		return true;
	}

	/**
	 * Run every plugin-owned database mutation in one transaction.
	 *
	 * An interrupted connection rolls back seed posts, metadata and options
	 * together. Role definitions remain dormant until a later explicit activation.
	 * The deployment bridge separately verifies that the affected WordPress tables
	 * use transactional storage.
	 *
	 * @param bool $hard_flush Whether to write a full rewrite-rule refresh.
	 * @return true|\WP_Error
	 */
	private static function run_migration( $hard_flush ) {
		global $wpdb;

		$lock = self::acquire_migration_lock();
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		$transaction_started = false;
		try {
			$current = self::persisted_option( 'complete99_platform_version' );
			if ( ! $hard_flush && COMPLETE99_PLATFORM_VERSION === (string) $current ) {
				return true;
			}
			$stored_deployment_id = self::persisted_option( 'complete99_last_deployment_id' );
			$deployment_id = trim( (string) $stored_deployment_id );
			if ( '' === $deployment_id ) {
				$deployment_id = COMPLETE99_PLATFORM_DEPLOYMENT_ID;
			}
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				throw new \RuntimeException( 'transaction' );
			}
			$transaction_started = true;

			Complete99_Content::register();
			Complete99_Content::register_rewrites();
			Complete99_Leads::register_post_type();
			Complete99_Commerce::register_product_planning_type();
			Complete99_Catalog_Graph::register_meta();
			Complete99_Evaluation_Catalog::register_meta();
			Complete99_Inventory_Bridge::register_meta();
			Complete99_Settings::install_defaults();
			Complete99_Content::seed_launch_content();
			$evaluation = Complete99_Evaluation_Catalog::materialize(
				Complete99_Evaluation_Catalog::MODE_PRIVATE_ONLY
			);
			if ( is_wp_error( $evaluation ) ) {
				throw new \RuntimeException( 'evaluation-catalog-materialization' );
			}
			Complete99_Content::assert_migration_invariants();
			Complete99_Settings::assert_defaults();
			self::assert_evaluation_catalog_invariants();
			Complete99_Culinary_Science::assert_invariants();
			Complete99_Culinary_Commerce::assert_invariants();
			update_option( 'complete99_platform_version', COMPLETE99_PLATFORM_VERSION, false );
			if ( '' === trim( (string) $stored_deployment_id ) ) {
				update_option( 'complete99_last_deployment_id', $deployment_id, false );
			}
			if ( COMPLETE99_PLATFORM_VERSION !== (string) self::persisted_option( 'complete99_platform_version' )
				|| $deployment_id !== (string) self::persisted_option( 'complete99_last_deployment_id' ) ) {
				throw new \RuntimeException( 'version-readback' );
			}
			flush_rewrite_rules( (bool) $hard_flush );
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new \RuntimeException( 'commit' );
			}
			$transaction_started = false;
			wp_cache_flush();
			return true;
		} catch ( \Throwable $error ) {
			if ( $transaction_started ) {
				$wpdb->query( 'ROLLBACK' );
			}
			wp_cache_flush();
			return new WP_Error( 'complete99_migration_failed', 'The Complete99 database migration could not be committed.' );
		} finally {
			self::release_migration_lock( $lock );
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
