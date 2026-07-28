<?php
/**
 * This file is a template for a temporary Code Snippets REST bridge.
 * The orchestrator replaces its reserved placeholder values, strips this opening tag,
 * creates the snippet, deploys, deletes the snippet in finally, and proves 404.
 *
 * No privileged work runs at snippet load time.
 */

add_action(
	'rest_api_init',
	static function () {
		$config = array(
			'token'         => '__C99_TOKEN__',
			'deployment_id' => '__C99_DEPLOYMENT_ID__',
			'slug'          => 'complete99-platform',
			'plugin_file'   => 'complete99-platform/complete99-platform.php',
			'max_bytes'     => __C99_MAX_BYTES__,
			'min_free_bytes'=> __C99_MIN_FREE_BYTES__,
			'local_test'    => __C99_LOCAL_TEST__,
			'test_fault'    => '__C99_TEST_FAULT__',
			'target_host'   => '__C99_TARGET_HOST__',
			'allowed_hosts' => __C99_ALLOWED_HOSTS__,
			'recovery_lease_seconds'=> 240,
		);
		$route_prefix = '/' . $config['deployment_id'];

		$permission = static function ( WP_REST_Request $request ) use ( $config ) {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return new WP_Error( 'c99_deploy_forbidden', 'Plugin update capability is required.', array( 'status' => 403 ) );
			}
			$token = (string) $request->get_param( 'token' );
			if ( '' === $token || ! hash_equals( $config['token'], $token ) ) {
				return new WP_Error( 'c99_deploy_token', 'The deployment token is invalid.', array( 'status' => 401 ) );
			}
			return true;
		};

		$bootstrap_filesystem = static function () {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( 'direct' !== get_filesystem_method() ) {
				return new WP_Error( 'c99_deploy_filesystem', 'The direct WordPress filesystem method is required.', array( 'status' => 409 ) );
			}
			if ( ! WP_Filesystem() ) {
				return new WP_Error( 'c99_deploy_filesystem_init', 'WordPress filesystem initialisation failed.', array( 'status' => 500 ) );
			}
			return true;
		};

		$verify_site_identity = static function () use ( $config ) {
			$identity_urls = array(
				'home'    => home_url( '/' ),
				'siteurl' => site_url( '/' ),
				'rest'    => rest_url(),
			);
			$identity = array();
			foreach ( $identity_urls as $label => $url ) {
				$host   = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
				$port   = wp_parse_url( $url, PHP_URL_PORT );
				$expected_scheme = $config['local_test'] ? 'http' : 'https';
				if (
					$config['target_host'] !== $host
					|| ! in_array( $host, $config['allowed_hosts'], true )
					|| $expected_scheme !== $scheme
					|| ( ! $config['local_test'] && null !== $port && 443 !== (int) $port )
				) {
					return new WP_Error(
						'c99_site_identity',
						'WordPress home, site URL and REST identity must match the exact deployment origin.',
						array( 'status' => 409, 'field' => sanitize_key( $label ) )
					);
				}
				$identity[ $label . '_host' ] = $host;
			}
			return $identity;
		};

		$state_directory = static function ( $deployment_id ) {
			return trailingslashit( WP_CONTENT_DIR ) . '.complete99-deploy-backups/' . $deployment_id;
		};

		$lock_option = 'complete99_deploy_lock';
		$lock_owner  = hash_hmac( 'sha256', $config['deployment_id'], $config['token'] );
		$process_lock_path = trailingslashit( WP_CONTENT_DIR ) . '.complete99-deploy-process.lock';

		$read_lock = static function ( $fresh = false ) use ( $lock_option ) {
			if ( $fresh ) {
				wp_cache_delete( $lock_option, 'options' );
			}
			$lock = get_option( $lock_option, array() );
			return is_array( $lock ) ? $lock : array();
		};

		$cas_lock = static function ( $expected, $replacement ) use ( $lock_option ) {
			global $wpdb;
			$exact_value = true === $wpdb->is_mysql
				? 'BINARY option_value = BINARY %s'
				: 'option_value = %s COLLATE BINARY';
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND {$exact_value}",
					maybe_serialize( $replacement ),
					$lock_option,
					maybe_serialize( $expected )
				)
			);
			wp_cache_delete( $lock_option, 'options' );
			return 1 === $updated;
		};

		$delete_lock_cas = static function ( $expected ) use ( $lock_option ) {
			global $wpdb;
			$exact_value = true === $wpdb->is_mysql
				? 'BINARY option_value = BINARY %s'
				: 'option_value = %s COLLATE BINARY';
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name = %s AND {$exact_value}",
					$lock_option,
					maybe_serialize( $expected )
				)
			);
			wp_cache_delete( $lock_option, 'options' );
			return 1 === $deleted;
		};

		$acquire_process_lock = static function () use ( $process_lock_path ) {
			$handle = @fopen( $process_lock_path, 'c+' );
			if ( false === $handle ) {
				return new WP_Error( 'c99_process_lock_open', 'The deployment process lock is unavailable.', array( 'status' => 500 ) );
			}
			@chmod( $process_lock_path, FS_CHMOD_FILE );
			if ( ! @flock( $handle, LOCK_EX | LOCK_NB ) ) {
				@fclose( $handle );
				return new WP_Error( 'c99_process_lock_busy', 'Another deployment mutation is still running.', array( 'status' => 409 ) );
			}
			return $handle;
		};

		$release_process_lock = static function ( $handle ) {
			if ( is_resource( $handle ) ) {
				@flock( $handle, LOCK_UN );
				@fclose( $handle );
			}
		};

		$process_lock_available = static function () use ( $acquire_process_lock, $release_process_lock ) {
			$handle = $acquire_process_lock();
			if ( is_wp_error( $handle ) ) {
				if ( 'c99_process_lock_busy' === $handle->get_error_code() ) {
					return false;
				}
				return $handle;
			}
			$release_process_lock( $handle );
			return true;
		};

		$acquire_lock = static function ( $deployment_id, $phase = 'reserved' ) use ( $lock_option, $lock_owner, $read_lock, $cas_lock ) {
			$lock = array(
				'deployment_id' => $deployment_id,
				'owner_id'      => $lock_owner,
				'fence'         => 1,
				'phase'         => sanitize_key( $phase ),
				'started_at'    => time(),
				'updated_at'    => time(),
				'heartbeat_seq' => 1,
			);
			if ( ! add_option( $lock_option, $lock, '', false ) ) {
				$current = $read_lock( true );
				$current_id    = is_array( $current ) ? (string) ( $current['deployment_id'] ?? '' ) : '';
				$current_phase = is_array( $current ) ? (string) ( $current['phase'] ?? '' ) : '';
				$current_owner = is_array( $current ) ? (string) ( $current['owner_id'] ?? '' ) : '';
				if ( $deployment_id === $current_id && $lock_owner === $current_owner && 'reserved' === $current_phase && 'reserved' === $lock['phase'] ) {
					$heartbeat = $current;
					$heartbeat['updated_at']    = time();
					$heartbeat['heartbeat_seq'] = (int) ( $current['heartbeat_seq'] ?? 0 ) + 1;
					if ( $cas_lock( $current, $heartbeat ) ) {
						return $heartbeat;
					}
					$current       = $read_lock( true );
					$current_id    = (string) ( $current['deployment_id'] ?? '' );
					$current_phase = (string) ( $current['phase'] ?? '' );
				}
				return new WP_Error(
					'c99_deploy_locked',
					'Another Complete99 deployment owns the mutation lock.',
					array(
						'status'        => 409,
						'deployment_id' => $current_id,
						'phase'         => $current_phase,
					)
				);
			}
			return $lock;
		};

		$claim_lock = static function ( $deployment_id, $allowed_phases, $next_phase, $require_current_owner = false, $require_stale = false ) use ( $config, $lock_owner, $read_lock, $cas_lock ) {
			$current = $read_lock( true );
			$phase   = (string) ( $current['phase'] ?? '' );
			if ( $deployment_id !== (string) ( $current['deployment_id'] ?? '' ) || ! in_array( $phase, $allowed_phases, true ) ) {
				return new WP_Error( 'c99_lock_claim_phase', 'The deployment lock is not claimable in its current phase.', array( 'status' => 409, 'phase' => $phase ) );
			}
			if ( $require_current_owner && $lock_owner !== (string) ( $current['owner_id'] ?? '' ) ) {
				return new WP_Error( 'c99_lock_claim_owner', 'The deployment reservation belongs to another route owner.', array( 'status' => 409 ) );
			}
			$age = max( 0, time() - (int) ( $current['updated_at'] ?? $current['started_at'] ?? 0 ) );
			if ( $require_stale && $age < (int) $config['recovery_lease_seconds'] ) {
				return new WP_Error(
					'c99_lock_claim_lease',
					'The deployment recovery lease has not expired.',
					array(
						'status'                 => 409,
						'lock_age_seconds'       => $age,
						'recovery_lease_seconds' => (int) $config['recovery_lease_seconds'],
					)
				);
			}
			$claimed = $current;
			$claimed['owner_id']      = $lock_owner;
			$claimed['fence']         = max( 1, (int) ( $current['fence'] ?? 0 ) + 1 );
			$claimed['phase']         = sanitize_key( $next_phase );
			$claimed['updated_at']    = time();
			$claimed['heartbeat_seq'] = (int) ( $current['heartbeat_seq'] ?? 0 ) + 1;
			if ( ! $cas_lock( $current, $claimed ) ) {
				return new WP_Error( 'c99_lock_claim_race', 'The deployment lock changed while ownership was being claimed.', array( 'status' => 409 ) );
			}
			return $claimed;
		};

		$heartbeat_lock = static function ( $deployment_id, $owner_id, $fence, $phase = '', $extra = array() ) use ( $read_lock, $cas_lock ) {
			$current = $read_lock( true );
			if (
				$deployment_id !== (string) ( $current['deployment_id'] ?? '' )
				|| $owner_id !== (string) ( $current['owner_id'] ?? '' )
				|| (int) $fence !== (int) ( $current['fence'] ?? 0 )
			) {
				return new WP_Error( 'c99_lock_fenced', 'This deployment worker no longer owns the mutation fence.', array( 'status' => 409 ) );
			}
			$heartbeat = $current;
			if ( '' !== $phase ) {
				$heartbeat['phase'] = sanitize_key( $phase );
			}
			foreach ( $extra as $key => $value ) {
				$heartbeat[ sanitize_key( $key ) ] = $value;
			}
			$heartbeat['updated_at']    = time();
			$heartbeat['heartbeat_seq'] = (int) ( $current['heartbeat_seq'] ?? 0 ) + 1;
			if ( ! $cas_lock( $current, $heartbeat ) ) {
				return new WP_Error( 'c99_lock_heartbeat_race', 'The deployment mutation fence changed during a heartbeat.', array( 'status' => 409 ) );
			}
			return $heartbeat;
		};

		$release_lock = static function ( $deployment_id, $lease ) use ( $read_lock, $delete_lock_cas ) {
			$current = $read_lock( true );
			if (
				! is_array( $lease )
				|| $deployment_id !== (string) ( $current['deployment_id'] ?? '' )
				|| (string) ( $lease['owner_id'] ?? '' ) !== (string) ( $current['owner_id'] ?? '' )
				|| (int) ( $lease['fence'] ?? 0 ) !== (int) ( $current['fence'] ?? 0 )
			) {
				return false;
			}
			return $delete_lock_cas( $current );
		};

		$write_state_file = static function ( $state_file, $state ) {
			$encoded = wp_json_encode( $state );
			if ( false === $encoded ) {
				return new WP_Error( 'c99_deploy_state_encode', 'Deployment state could not be encoded.', array( 'status' => 500 ) );
			}
			try {
				$suffix = bin2hex( random_bytes( 8 ) );
			} catch ( \Throwable $error ) {
				return new WP_Error( 'c99_deploy_state_random', 'Deployment state could not be written safely.', array( 'status' => 500 ) );
			}
			$temp_file = $state_file . '.tmp-' . $suffix;
			$written   = file_put_contents( $temp_file, $encoded, LOCK_EX );
			if ( false === $written || strlen( $encoded ) !== $written ) {
				@unlink( $temp_file );
				return new WP_Error( 'c99_deploy_state_write', 'Deployment state could not be written.', array( 'status' => 500 ) );
			}
			@chmod( $temp_file, FS_CHMOD_FILE );
			if ( ! @rename( $temp_file, $state_file ) ) {
				@unlink( $temp_file );
				return new WP_Error( 'c99_deploy_state_commit', 'Deployment state could not be committed atomically.', array( 'status' => 500 ) );
			}
			return true;
		};

		$heartbeat_state = static function ( $state_dir, $deployment_id, $phase = '' ) use ( $heartbeat_lock ) {
			global $wp_filesystem;
			$state_file = trailingslashit( $state_dir ) . 'state.json';
			if ( ! $wp_filesystem || ! $wp_filesystem->exists( $state_file ) ) {
				return new WP_Error( 'c99_deploy_state_missing', 'Deployment state is unavailable.', array( 'status' => 500 ) );
			}
			$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
			if ( ! is_array( $state ) ) {
				return new WP_Error( 'c99_deploy_state_invalid', 'Deployment state is invalid.', array( 'status' => 500 ) );
			}
			return $heartbeat_lock(
				$deployment_id,
				(string) ( $state['owner_id'] ?? '' ),
				(int) ( $state['fence'] ?? 0 ),
				$phase
			);
		};

		$adopt_state_lease = static function ( $state_dir, $deployment_id, $lease ) use ( $write_state_file, $heartbeat_lock ) {
			global $wp_filesystem;
			$state_file = trailingslashit( $state_dir ) . 'state.json';
			if ( ! $wp_filesystem || ! $wp_filesystem->exists( $state_file ) ) {
				return new WP_Error( 'c99_deploy_state_missing', 'Deployment state is unavailable.', array( 'status' => 500 ) );
			}
			$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
			if ( ! is_array( $state ) || ! is_array( $lease ) ) {
				return new WP_Error( 'c99_deploy_state_invalid', 'Deployment state is invalid.', array( 'status' => 500 ) );
			}
			$state['owner_id']   = (string) ( $lease['owner_id'] ?? '' );
			$state['fence']      = (int) ( $lease['fence'] ?? 0 );
			$state['updated_at'] = time();
			$written = $write_state_file( $state_file, $state );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$heartbeat = $heartbeat_lock( $deployment_id, $state['owner_id'], $state['fence'], (string) ( $state['phase'] ?? '' ) );
			return is_wp_error( $heartbeat ) ? $heartbeat : $state;
		};

		$set_state_phase = static function ( $state_dir, $deployment_id, $phase, $extra = array() ) use ( $write_state_file, $heartbeat_state, $heartbeat_lock ) {
			global $wp_filesystem;
			$state_file = trailingslashit( $state_dir ) . 'state.json';
			$owned = $heartbeat_state( $state_dir, $deployment_id );
			if ( is_wp_error( $owned ) ) {
				return $owned;
			}
			$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
			if ( ! is_array( $state ) ) {
				return new WP_Error( 'c99_deploy_state_invalid', 'Deployment state is invalid.', array( 'status' => 500 ) );
			}
			$state['phase']      = sanitize_key( $phase );
			$state['updated_at'] = time();
			foreach ( $extra as $key => $value ) {
				$state[ sanitize_key( $key ) ] = $value;
			}
			$written = $write_state_file( $state_file, $state );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$lock = $heartbeat_lock(
				$deployment_id,
				(string) ( $state['owner_id'] ?? '' ),
				(int) ( $state['fence'] ?? 0 ),
				$state['phase']
			);
			if ( is_wp_error( $lock ) ) {
				return $lock;
			}
			return $state;
		};

		$make_test_lock_stale = static function ( $deployment_id ) use ( $config, $read_lock, $cas_lock ) {
			if ( ! $config['local_test'] || '' === $config['test_fault'] ) {
				return false;
			}
			$lock = $read_lock( true );
			if ( ! is_array( $lock ) || $deployment_id !== (string) ( $lock['deployment_id'] ?? '' ) ) {
				return false;
			}
			$stale = $lock;
			$stale['updated_at'] = time() - (int) $config['recovery_lease_seconds'] - 5;
			$stale['heartbeat_seq'] = (int) ( $lock['heartbeat_seq'] ?? 0 ) + 1;
			return $cas_lock( $lock, $stale );
		};

		$directory_sha256 = static function ( $directory ) {
			if ( ! is_dir( $directory ) || is_link( $directory ) ) {
				return new WP_Error( 'c99_plugin_digest_directory', 'The plugin directory is unavailable or unsafe.', array( 'status' => 500 ) );
			}
			try {
				$iterator = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS ),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
				$entries = array();
				$prefix  = rtrim( wp_normalize_path( $directory ), '/' ) . '/';
				foreach ( $iterator as $file ) {
					if ( $file->isLink() || ! $file->isFile() ) {
						return new WP_Error( 'c99_plugin_digest_entry', 'The plugin backup contains an unsafe filesystem entry.', array( 'status' => 500 ) );
					}
					$path     = wp_normalize_path( $file->getPathname() );
					$relative = substr( $path, strlen( $prefix ) );
					if ( false === $relative || '' === $relative || str_contains( $relative, '..' ) ) {
						return new WP_Error( 'c99_plugin_digest_path', 'The plugin backup contains an invalid path.', array( 'status' => 500 ) );
					}
					$digest = hash_file( 'sha256', $file->getPathname() );
					if ( false === $digest ) {
						return new WP_Error( 'c99_plugin_digest_file', 'A plugin file could not be hashed.', array( 'status' => 500 ) );
					}
					$entries[] = $relative . "\0" . $digest;
				}
				sort( $entries, SORT_STRING );
				return hash( 'sha256', implode( "\n", $entries ) );
			} catch ( \Throwable $error ) {
				return new WP_Error( 'c99_plugin_digest_exception', 'The plugin directory could not be hashed.', array( 'status' => 500 ) );
			}
		};

		$verify_transactional_storage = static function () use ( $config ) {
			global $wpdb;
			$database_class = strtolower( get_class( $wpdb ) );
			if ( $config['local_test'] && str_contains( $database_class, 'sqlite' ) ) {
				return array(
					'engine' => 'SQLite',
					'tables' => 3,
				);
			}
			$tables       = array( $wpdb->options, $wpdb->posts, $wpdb->postmeta );
			$placeholders = implode( ',', array_fill( 0, count( $tables ), '%s' ) );
			$query        = $wpdb->prepare(
				"SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$placeholders})",
				$tables
			);
			$wpdb->last_error = '';
			$rows = $wpdb->get_results( $query, ARRAY_A );
			if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) || count( $rows ) !== count( $tables ) ) {
				return new WP_Error(
					'c99_db_engine_probe',
					'The deployment could not verify transactional WordPress storage.',
					array( 'status' => 500 )
				);
			}
			$found = array();
			foreach ( $rows as $row ) {
				$table  = (string) ( $row['TABLE_NAME'] ?? '' );
				$engine = strtoupper( (string) ( $row['ENGINE'] ?? '' ) );
				if ( ! in_array( $engine, array( 'INNODB', 'XTRADB' ), true ) ) {
					return new WP_Error(
						'c99_db_engine_nontransactional',
						'The deployment requires transactional WordPress tables.',
						array( 'status' => 409 )
					);
				}
				$found[ $table ] = $engine;
			}
			foreach ( $tables as $table ) {
				if ( ! isset( $found[ $table ] ) ) {
					return new WP_Error(
						'c99_db_engine_missing',
						'The deployment could not verify every required WordPress table.',
						array( 'status' => 500 )
					);
				}
			}
			return array(
				'engine' => implode( ',', array_values( array_unique( $found ) ) ),
				'tables' => count( $found ),
			);
		};

		$verify_migration_advisory_lock = static function () use ( $config ) {
			global $wpdb;

			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			if ( $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) ) ) {
				return array(
					'driver'          => 'filesystem',
					'bounded_seconds' => 10,
				);
			}
			if ( true !== $wpdb->is_mysql ) {
				return new WP_Error(
					'c99_migration_lock_driver',
					'The production database does not support the required migration advisory lock.',
					array( 'status' => 409 )
				);
			}

			$name = 'complete99-migration-' . substr(
				hash( 'sha256', get_current_blog_id() . '|' . $wpdb->prefix . '|' . home_url( '/' ) ),
				0,
				40
			);
			$previous_suppress = $wpdb->suppress_errors( true );
			$wpdb->last_error = '';
			$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 0 ) );
			$acquire_error = (string) $wpdb->last_error;
			$released = null;
			$release_error = '';
			if ( 1 === (int) $acquired ) {
				$wpdb->last_error = '';
				$released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
				$release_error = (string) $wpdb->last_error;
			}
			$wpdb->suppress_errors( $previous_suppress );
			if ( '' !== $acquire_error || 1 !== (int) $acquired ) {
				return new WP_Error(
					'c99_migration_lock_busy',
					'The production migration advisory lock is unavailable or already held.',
					array( 'status' => 409 )
				);
			}
			if ( '' !== $release_error || 1 !== (int) $released ) {
				return new WP_Error(
					'c99_migration_lock_release',
					'The production migration advisory-lock probe could not release its lease.',
					array( 'status' => 500 )
				);
			}
			return array(
				'driver'          => 'mysql',
				'bounded_seconds' => 10,
			);
		};

		$capture_database_state = static function () use ( $config ) {
			global $wpdb;
			$query_error = static function ( $stage ) {
				return new WP_Error(
					'c99_db_snapshot_query',
					'The database rollback journal could not be captured.',
					array( 'status' => 500, 'stage' => sanitize_key( $stage ) )
				);
			};
			if ( $config['local_test'] && 'db_capture' === $config['test_fault'] ) {
				return $query_error( 'injected' );
			}
			$option_names = array(
				'active_plugins',
				'complete99_last_deployment_id',
				'complete99_os_public_url',
				'complete99_os_url',
				'complete99_platform_version',
				'page_on_front',
				'rewrite_rules',
				'show_on_front',
				$wpdb->prefix . 'user_roles',
			);
			$options = array();
			foreach ( $option_names as $option_name ) {
				$wpdb->last_error = '';
				$row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s",
						$option_name
					),
					ARRAY_A
				);
				if ( '' !== (string) $wpdb->last_error || ( null !== $row && ! is_array( $row ) ) ) {
					return $query_error( 'option' );
				}
				$options[ $option_name ] = is_array( $row ) ? $row : null;
			}
			$wpdb->last_error = '';
			$sync_secret_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
					'complete99_sync_secret'
				)
			);
			if ( '' !== (string) $wpdb->last_error || null === $sync_secret_count || ! is_numeric( $sync_secret_count ) ) {
				return $query_error( 'sync_secret' );
			}
			$sync_secret_existed = 0 < (int) $sync_secret_count;

			$wpdb->last_error = '';
			$seed_rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY post_id",
					'_complete99_seed_key'
				)
			);
			if ( '' !== (string) $wpdb->last_error || ! is_array( $seed_rows ) ) {
				return $query_error( 'seed_ids' );
			}
			$seed_ids = array_map( 'intval', $seed_rows );
			$posts    = array();
			$postmeta = array();
			if ( ! empty( $seed_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $seed_ids ), '%d' ) );
				$post_query   = $wpdb->prepare(
					"SELECT * FROM {$wpdb->posts} WHERE ID IN ({$placeholders}) OR (post_type = 'revision' AND post_parent IN ({$placeholders})) ORDER BY ID",
					array_merge( $seed_ids, $seed_ids )
				);
				$wpdb->last_error = '';
				$posts        = $wpdb->get_results( $post_query, ARRAY_A );
				if ( '' !== (string) $wpdb->last_error || ! is_array( $posts ) ) {
					return $query_error( 'posts' );
				}
				$post_ids     = array_map(
					'intval',
					wp_list_pluck( $posts, 'ID' )
				);
				if ( ! empty( $post_ids ) ) {
					$meta_placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
					$meta_query        = $wpdb->prepare(
						"SELECT * FROM {$wpdb->postmeta} WHERE post_id IN ({$meta_placeholders}) ORDER BY meta_id",
						$post_ids
					);
					$wpdb->last_error = '';
					$postmeta         = $wpdb->get_results( $meta_query, ARRAY_A );
					if ( '' !== (string) $wpdb->last_error || ! is_array( $postmeta ) ) {
						return $query_error( 'postmeta' );
					}
				}
			}
			return array(
				'options'   => $options,
				'postmeta'  => $postmeta,
				'posts'     => $posts,
				'seed_ids'  => $seed_ids,
				'sync_secret_existed'=> $sync_secret_existed,
			);
		};

		$encrypt_database_state = static function ( $snapshot ) use ( $config ) {
			if ( ! function_exists( 'openssl_encrypt' ) ) {
				return new WP_Error( 'c99_db_journal_crypto', 'Database journal encryption is unavailable.', array( 'status' => 500 ) );
			}
			$plaintext = wp_json_encode( $snapshot );
			if ( false === $plaintext ) {
				return new WP_Error( 'c99_db_journal_encode', 'The database rollback journal could not be encoded.', array( 'status' => 500 ) );
			}
			try {
				$iv = random_bytes( 12 );
			} catch ( \Throwable $error ) {
				return new WP_Error( 'c99_db_journal_random', 'Database journal encryption could not initialize.', array( 'status' => 500 ) );
			}
			$tag        = '';
			$key        = hash_hmac( 'sha256', $config['deployment_id'], wp_salt( 'auth' ), true );
			$ciphertext = openssl_encrypt(
				$plaintext,
				'aes-256-gcm',
				$key,
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$config['deployment_id'],
				16
			);
			if ( false === $ciphertext ) {
				return new WP_Error( 'c99_db_journal_encrypt', 'The database rollback journal could not be encrypted.', array( 'status' => 500 ) );
			}
			return array(
				'algorithm'  => 'aes-256-gcm',
				'ciphertext' => base64_encode( $ciphertext ),
				'iv'         => base64_encode( $iv ),
				'tag'        => base64_encode( $tag ),
			);
		};

		$decrypt_database_state = static function ( $journal ) use ( $config ) {
			if ( ! function_exists( 'openssl_decrypt' ) || ! is_array( $journal ) || 'aes-256-gcm' !== ( $journal['algorithm'] ?? '' ) ) {
				return new WP_Error( 'c99_db_journal_invalid', 'The encrypted database rollback journal is invalid.', array( 'status' => 500 ) );
			}
			$ciphertext = base64_decode( (string) ( $journal['ciphertext'] ?? '' ), true );
			$iv         = base64_decode( (string) ( $journal['iv'] ?? '' ), true );
			$tag        = base64_decode( (string) ( $journal['tag'] ?? '' ), true );
			if ( false === $ciphertext || false === $iv || false === $tag ) {
				return new WP_Error( 'c99_db_journal_decode', 'The encrypted database rollback journal could not be decoded.', array( 'status' => 500 ) );
			}
			$plaintext = openssl_decrypt(
				$ciphertext,
				'aes-256-gcm',
				hash_hmac( 'sha256', $config['deployment_id'], wp_salt( 'auth' ), true ),
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				$config['deployment_id']
			);
			if ( false === $plaintext ) {
				return new WP_Error( 'c99_db_journal_decrypt', 'The encrypted database rollback journal could not be decrypted.', array( 'status' => 500 ) );
			}
			$snapshot = json_decode( $plaintext, true );
			return is_array( $snapshot )
				? $snapshot
				: new WP_Error( 'c99_db_journal_json', 'The decrypted database rollback journal is invalid.', array( 'status' => 500 ) );
		};

		$restore_database_state = static function ( $snapshot ) {
			global $wpdb;
			if ( ! is_array( $snapshot ) || ! isset( $snapshot['options'], $snapshot['posts'], $snapshot['postmeta'], $snapshot['seed_ids'], $snapshot['sync_secret_existed'] ) ) {
				return new WP_Error( 'c99_db_snapshot_invalid', 'The database rollback journal is invalid.', array( 'status' => 500 ) );
			}
			$started = false !== $wpdb->query( 'START TRANSACTION' );
			if ( ! $started ) {
				return new WP_Error( 'c99_db_transaction', 'The database rollback transaction could not start.', array( 'status' => 500 ) );
			}
			try {
				foreach ( $snapshot['options'] as $option_name => $row ) {
					if ( false === $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ), array( '%s' ) ) ) {
						throw new \RuntimeException( 'option_delete' );
					}
					if ( is_array( $row ) && false === $wpdb->insert( $wpdb->options, $row ) ) {
						throw new \RuntimeException( 'option_restore' );
					}
				}
				if ( ! $snapshot['sync_secret_existed'] ) {
					if ( false === $wpdb->delete( $wpdb->options, array( 'option_name' => 'complete99_sync_secret' ), array( '%s' ) ) ) {
						throw new \RuntimeException( 'sync_secret_delete' );
					}
				}

				$wpdb->last_error = '';
				$current_seed_rows = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY post_id",
						'_complete99_seed_key'
					)
				);
				if ( '' !== (string) $wpdb->last_error || ! is_array( $current_seed_rows ) ) {
					throw new \RuntimeException( 'seed_read' );
				}
				$current_seed_ids = array_map( 'intval', $current_seed_rows );
				$seed_ids = array_values(
					array_unique(
						array_merge(
							array_map( 'intval', $snapshot['seed_ids'] ),
							$current_seed_ids
						)
					)
				);
				$delete_ids = $seed_ids;
				if ( ! empty( $seed_ids ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $seed_ids ), '%d' ) );
					$wpdb->last_error = '';
					$revision_rows = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent IN ({$placeholders})",
							$seed_ids
						)
					);
					if ( '' !== (string) $wpdb->last_error || ! is_array( $revision_rows ) ) {
						throw new \RuntimeException( 'revision_read' );
					}
					$revision_ids = array_map( 'intval', $revision_rows );
					$delete_ids = array_values( array_unique( array_merge( $delete_ids, $revision_ids ) ) );
				}
				if ( ! empty( $delete_ids ) ) {
					$delete_placeholders = implode( ',', array_fill( 0, count( $delete_ids ), '%d' ) );
					if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$delete_placeholders})", $delete_ids ) ) ) {
						throw new \RuntimeException( 'postmeta_delete' );
					}
					if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$delete_placeholders})", $delete_ids ) ) ) {
						throw new \RuntimeException( 'posts_delete' );
					}
				}
				foreach ( $snapshot['posts'] as $row ) {
					if ( ! is_array( $row ) || false === $wpdb->insert( $wpdb->posts, $row ) ) {
						throw new \RuntimeException( 'post_restore' );
					}
				}
				foreach ( $snapshot['postmeta'] as $row ) {
					if ( ! is_array( $row ) || false === $wpdb->insert( $wpdb->postmeta, $row ) ) {
						throw new \RuntimeException( 'postmeta_restore' );
					}
				}
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					throw new \RuntimeException( 'commit' );
				}
			} catch ( \Throwable $error ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'c99_db_restore', 'The database rollback journal could not be restored.', array( 'status' => 500 ) );
			}
			wp_cache_flush();
			return array(
				'options_restored' => count( $snapshot['options'] ),
				'posts_restored'   => count( $snapshot['posts'] ),
				'meta_restored'    => count( $snapshot['postmeta'] ),
			);
		};

		$auto_update_enabled = static function () use ( $config ) {
			$enabled = get_site_option( 'auto_update_plugins', array() );
			return is_array( $enabled ) && in_array( $config['plugin_file'], $enabled, true );
		};

		$purge_caches = static function () {
			$ezcache_detected = class_exists( '\\Upress\\EzCache\\Cache' );
			if ( $ezcache_detected ) {
				try {
					if ( ! method_exists( '\\Upress\\EzCache\\Cache', 'instance' ) ) {
						return new WP_Error( 'c99_ezcache_instance', 'The UPress cache API is unavailable.', array( 'status' => 500 ) );
					}
					$cache = \Upress\EzCache\Cache::instance();
					if ( ! is_object( $cache ) || ! method_exists( $cache, 'clear_cache' ) ) {
						return new WP_Error( 'c99_ezcache_clear', 'The UPress cache purge API is unavailable.', array( 'status' => 500 ) );
					}
					$cache->clear_cache();
				} catch ( \Throwable $error ) {
					return new WP_Error( 'c99_ezcache_failure', 'The UPress cache purge failed.', array( 'status' => 500 ) );
				}
			}
			do_action( 'litespeed_purge_all' );
			wp_cache_flush();
			return array(
				'ezcache_detected'    => $ezcache_detected,
				'object_cache_flushed'=> true,
			);
		};

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/preflight',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function () use ( $config, $bootstrap_filesystem, $verify_site_identity, $auto_update_enabled, $acquire_lock, $release_lock, $process_lock_available, $verify_transactional_storage, $verify_migration_advisory_lock, $capture_database_state ) {
					global $wp_filesystem;
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					$target_dir        = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
					$plugin_path       = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
					$target_dir_exists = $wp_filesystem->is_dir( $target_dir );
					$plugin_main_exists= $wp_filesystem->exists( $plugin_path );
					if ( $target_dir_exists !== $plugin_main_exists ) {
						return new WP_Error(
							'c99_deploy_partial_plugin',
							'The target plugin has an inconsistent partial installation that must be recovered before deployment.',
							array( 'status' => 409 )
						);
					}
					$current     = $plugin_main_exists ? get_plugin_data( $plugin_path, false, false ) : array();
					$current_active = is_plugin_active( $config['plugin_file'] );
					if ( $current_active && ! $plugin_main_exists ) {
						return new WP_Error(
							'c99_deploy_missing_active_plugin',
							'The active plugin record points to a missing installation.',
							array( 'status' => 409 )
						);
					}
					$current_deployment = (string) get_option( 'complete99_last_deployment_id', '' );
					if ( '' === $current_deployment && $current_active && defined( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID' ) ) {
						$current_deployment = (string) COMPLETE99_PLATFORM_DEPLOYMENT_ID;
					}
					$target_auto_update = $auto_update_enabled();
					if ( $target_auto_update ) {
						return new WP_Error(
							'c99_deploy_auto_update_enabled',
							'Automatic updates must be disabled for the deliberate deployment plugin.',
							array( 'status' => 409 )
						);
					}
					$free_space  = function_exists( 'disk_free_space' ) ? @disk_free_space( WP_CONTENT_DIR ) : false;
					if ( false !== $free_space && $free_space < $config['min_free_bytes'] ) {
						return new WP_Error(
							'c99_deploy_disk_space',
							'The site does not have the required deployment headroom.',
							array(
								'status'              => 507,
								'free_bytes'          => (int) $free_space,
								'required_free_bytes' => (int) $config['min_free_bytes'],
							)
						);
					}
					if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'openssl_decrypt' ) ) {
						return new WP_Error( 'c99_db_journal_crypto', 'Encrypted database journaling is unavailable.', array( 'status' => 500 ) );
					}
					$transactional_storage = $verify_transactional_storage();
					if ( is_wp_error( $transactional_storage ) ) {
						return $transactional_storage;
					}
					$migration_lock = $verify_migration_advisory_lock();
					if ( is_wp_error( $migration_lock ) ) {
						return $migration_lock;
					}
					$database_snapshot = $capture_database_state();
					if ( is_wp_error( $database_snapshot ) ) {
						return $database_snapshot;
					}
					$database_json     = wp_json_encode( $database_snapshot );
					if ( false === $database_json ) {
						return new WP_Error( 'c99_db_snapshot_encode', 'The database rollback journal could not be encoded.', array( 'status' => 500 ) );
					}
					$reservation = $acquire_lock( $config['deployment_id'], 'reserved' );
					if ( is_wp_error( $reservation ) ) {
						return $reservation;
					}
					$process_available = $process_lock_available();
					if ( is_wp_error( $process_available ) || ! $process_available ) {
						$release_lock( $config['deployment_id'], $reservation );
						return is_wp_error( $process_available )
							? $process_available
							: new WP_Error( 'c99_process_lock_busy', 'Another deployment mutation is still running.', array( 'status' => 409 ) );
					}
					return array(
						'ready'             => true,
						'direct_filesystem' => true,
						'allowed_slug'      => $config['slug'],
						'allowed_type'      => 'plugin',
						'max_bytes'         => $config['max_bytes'],
						'current_version'   => isset( $current['Version'] ) ? (string) $current['Version'] : '',
						'current_active'    => $current_active,
						'current_deployment'=> $current_deployment,
						'had_plugin'        => $plugin_main_exists,
						'target_dir_exists' => $target_dir_exists,
						'plugin_main_exists'=> $plugin_main_exists,
						'auto_update_enabled'=> $target_auto_update,
						'lock_reserved'      => true,
						'free_bytes'        => false === $free_space ? null : (int) $free_space,
						'required_free_bytes'=> (int) $config['min_free_bytes'],
						'transactional_storage'=> $transactional_storage,
						'migration_lock'       => $migration_lock,
						'database_fingerprint'=> hash( 'sha256', $database_json ),
						'site_identity'      => $site_identity,
					);
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/status',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $read_lock, $process_lock_available, $directory_sha256, $capture_database_state ) {
					global $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					if ( $config['deployment_id'] !== $deployment_id ) {
						return new WP_Error( 'c99_status_id', 'The status deployment ID is invalid.', array( 'status' => 400 ) );
					}
					$state_dir  = $state_directory( $deployment_id );
					$state_file = trailingslashit( $state_dir ) . 'state.json';
					$state      = array();
					if ( $wp_filesystem->exists( $state_file ) ) {
						$decoded = json_decode( $wp_filesystem->get_contents( $state_file ), true );
						$state   = is_array( $decoded ) ? $decoded : array();
					}
					$lock        = $read_lock();
					$lock_owned  = $deployment_id === (string) ( $lock['deployment_id'] ?? '' );
					$target_dir  = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
					$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
					$current_dir_exists  = $wp_filesystem->is_dir( $target_dir );
					$current_main_exists = $wp_filesystem->exists( $plugin_path );
					$current_plugin_sha256 = '';
					if ( $current_dir_exists && $current_main_exists ) {
						$current_digest = $directory_sha256( $target_dir );
						$current_plugin_sha256 = is_wp_error( $current_digest ) ? '' : $current_digest;
					}
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					$current = file_exists( $plugin_path ) ? get_plugin_data( $plugin_path, false, false ) : array();
					$current_active = is_plugin_active( $config['plugin_file'] );
					$current_deployment = (string) get_option( 'complete99_last_deployment_id', '' );
					if ( '' === $current_deployment && $current_active && defined( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID' ) ) {
						$current_deployment = (string) COMPLETE99_PLATFORM_DEPLOYMENT_ID;
					}
					$current_database_version = (string) get_option( 'complete99_platform_version', '' );
					$database_snapshot = $capture_database_state();
					$database_json = is_wp_error( $database_snapshot ) ? false : wp_json_encode( $database_snapshot );
					$phase = (string) ( $state['phase'] ?? ( $lock_owned ? ( $lock['phase'] ?? 'locked' ) : 'finalized' ) );
					$lock_updated_at = (int) ( $lock['updated_at'] ?? $lock['started_at'] ?? 0 );
					$lock_age = $lock_owned && 0 < $lock_updated_at ? max( 0, time() - $lock_updated_at ) : 0;
					$process_available = $process_lock_available();
					if ( is_wp_error( $process_available ) ) {
						return $process_available;
					}
					$recovery_ready = $lock_owned
						&& $lock_age >= (int) $config['recovery_lease_seconds']
						&& $process_available
						&& in_array( $phase, array( 'reserved', 'locked', 'prepared', 'installing', 'rolling_back', 'committing' ), true );
					return array(
						'deployment_id'    => $deployment_id,
						'phase'            => $phase,
						'state_exists'     => $wp_filesystem->exists( $state_file ),
						'lock_owned'       => $lock_owned,
						'lock_age_seconds' => $lock_age,
						'recovery_lease_seconds'=> (int) $config['recovery_lease_seconds'],
						'recovery_ready'   => $recovery_ready,
						'process_lock_available'=> $process_available,
						'expected_sha256'  => (string) ( $state['expected_sha256'] ?? $lock['expected_sha256'] ?? '' ),
						'expected_version' => (string) ( $state['expected_version'] ?? $state['installed_version'] ?? $lock['expected_version'] ?? '' ),
						'installed_plugin_sha256'=> (string) ( $state['installed_plugin_sha256'] ?? $lock['installed_plugin_sha256'] ?? '' ),
						'committed_outcome'=> (string) ( $state['committed_outcome'] ?? $lock['committed_outcome'] ?? '' ),
						'committed_expected_active'=> (bool) ( $state['committed_expected_active'] ?? $lock['committed_expected_active'] ?? false ),
						'committed_expected_absent'=> (bool) ( $state['committed_expected_absent'] ?? $lock['committed_expected_absent'] ?? false ),
						'committed_expected_version'=> (string) ( $state['committed_expected_version'] ?? $lock['committed_expected_version'] ?? '' ),
						'committed_expected_deployment'=> (string) ( $state['committed_expected_deployment'] ?? $lock['committed_expected_deployment'] ?? '' ),
						'committed_expected_plugin_sha256'=> (string) ( $state['committed_expected_plugin_sha256'] ?? $lock['committed_expected_plugin_sha256'] ?? '' ),
						'temp_removed'     => ! empty( $state['temp_removed'] ),
						'had_plugin'       => ! empty( $state['had_plugin'] ),
						'prior_target_dir_exists' => ! empty( $state['prior_target_dir_exists'] ),
						'prior_plugin_main_exists'=> ! empty( $state['prior_plugin_main_exists'] ),
						'prior_plugin_sha256'=> (string) ( $state['prior_plugin_sha256'] ?? '' ),
						'prior_version'    => (string) ( $state['prior_version'] ?? '' ),
						'prior_active'     => ! empty( $state['was_active'] ),
						'prior_deployment' => (string) ( $state['prior_deployment'] ?? '' ),
						'current_version'  => isset( $current['Version'] ) ? (string) $current['Version'] : '',
						'current_target_dir_exists' => $current_dir_exists,
						'current_plugin_main_exists'=> $current_main_exists,
						'current_plugin_sha256'=> $current_plugin_sha256,
						'current_active'   => $current_active,
						'current_deployment'=> $current_deployment,
						'current_database_version'=> $current_database_version,
						'database_restored' => ! empty( $state['database_restored'] ),
						'baseline_database_fingerprint'=> (string) ( $state['database_fingerprint'] ?? '' ),
						'post_install_database_fingerprint'=> (string) ( $state['post_install_database_fingerprint'] ?? '' ),
						'database_fingerprint'=> false === $database_json ? '' : hash( 'sha256', $database_json ),
						'database_fingerprint_available'=> false !== $database_json,
						'site_identity'      => $site_identity,
					);
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/stabilize',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $purge_caches, $read_lock, $claim_lock, $acquire_process_lock, $release_process_lock, $adopt_state_lease, $set_state_phase, $directory_sha256, $capture_database_state ) {
					global $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					if ( $config['deployment_id'] !== $deployment_id ) {
						return new WP_Error( 'c99_stabilize_id', 'The stabilization deployment ID is invalid.', array( 'status' => 400 ) );
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
					$state_dir  = $state_directory( $deployment_id );
					$state_file = trailingslashit( $state_dir ) . 'state.json';
					if ( ! $wp_filesystem->exists( $state_file ) ) {
						return new WP_Error( 'c99_stabilize_state', 'Deployment stabilization state was not found.', array( 'status' => 404 ) );
					}
					$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
					if ( ! is_array( $state ) ) {
						return new WP_Error( 'c99_stabilize_state_invalid', 'Deployment stabilization state is invalid.', array( 'status' => 500 ) );
					}
					$lock = $read_lock( true );
					if ( $deployment_id !== (string) ( $lock['deployment_id'] ?? '' ) ) {
						return new WP_Error( 'c99_stabilize_lock', 'The deployment does not own the mutation lock.', array( 'status' => 409 ) );
					}
					$phase = (string) ( $state['phase'] ?? '' );
					if ( ! in_array( $phase, array( 'installed', 'failed', 'rollback_failed' ), true ) ) {
						return new WP_Error(
							'c99_stabilize_not_ready',
							'Deployment stabilization is allowed only after a complete forward install.',
							array( 'status' => 409, 'phase' => $phase )
						);
					}
					$lease = $claim_lock(
						$deployment_id,
						array( 'installed', 'failed', 'rollback_failed' ),
						'installed',
						false,
						false
					);
					if ( is_wp_error( $lease ) ) {
						return $lease;
					}
					$adopted = $adopt_state_lease( $state_dir, $deployment_id, $lease );
					if ( is_wp_error( $adopted ) ) {
						return $adopted;
					}
					$state = $adopted;

					$expected_version = (string) ( $state['expected_version'] ?? $state['installed_version'] ?? '' );
					$installed_plugin_sha256 = (string) ( $state['installed_plugin_sha256'] ?? '' );
					if (
						! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $expected_version )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $installed_plugin_sha256 )
					) {
						return new WP_Error( 'c99_stabilize_identity', 'The recorded forward release identity is incomplete.', array( 'status' => 409 ) );
					}
					$target_dir  = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
					$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					$current_data = $wp_filesystem->exists( $plugin_path )
						? get_plugin_data( $plugin_path, false, false )
						: array();
					$current_plugin_sha256 = $wp_filesystem->is_dir( $target_dir )
						? $directory_sha256( $target_dir )
						: new WP_Error( 'c99_stabilize_plugin_missing', 'The installed plugin directory is missing.', array( 'status' => 409 ) );
					$current_database_version = (string) get_option( 'complete99_platform_version', '' );
					$migration_failed = class_exists( 'Complete99_Platform', false )
						&& method_exists( 'Complete99_Platform', 'migration_failed' )
						&& Complete99_Platform::migration_failed();
					if (
						$migration_failed
						|| is_wp_error( $current_plugin_sha256 )
						|| ! hash_equals( $installed_plugin_sha256, (string) $current_plugin_sha256 )
						|| $expected_version !== (string) ( $current_data['Version'] ?? '' )
						|| $expected_version !== $current_database_version
						|| ! is_plugin_active( $config['plugin_file'] )
						|| empty( $state['installed_active'] )
					) {
						return new WP_Error(
							'c99_stabilize_forward_mismatch',
							'The forward plugin or its completed database migration does not match the recorded release.',
							array( 'status' => 409 )
						);
					}

					update_option( 'complete99_last_deployment_id', $deployment_id, false );
					wp_cache_delete( 'complete99_last_deployment_id', 'options' );
					if ( $deployment_id !== (string) get_option( 'complete99_last_deployment_id', '' ) ) {
						return new WP_Error( 'c99_stabilize_deployment_readback', 'The deployment identity could not be persisted.', array( 'status' => 500 ) );
					}
					$cache_purge = $purge_caches();
					if ( is_wp_error( $cache_purge ) ) {
						return $cache_purge;
					}
					$post_install_snapshot = $capture_database_state();
					$post_install_json = is_wp_error( $post_install_snapshot )
						? false
						: wp_json_encode( $post_install_snapshot );
					if ( is_wp_error( $post_install_snapshot ) || false === $post_install_json ) {
						return new WP_Error( 'c99_stabilize_database_snapshot', 'The stabilized database fingerprint could not be captured.', array( 'status' => 500 ) );
					}
					$post_install_fingerprint = hash( 'sha256', $post_install_json );
					$stabilized = $set_state_phase(
						$state_dir,
						$deployment_id,
						'installed',
						array(
							'installed_version'                 => $expected_version,
							'installed_active'                  => true,
							'post_install_database_fingerprint' => $post_install_fingerprint,
							'stabilized'                        => true,
							'stabilized_from_phase'             => $phase,
						)
					);
					if ( is_wp_error( $stabilized ) ) {
						return $stabilized;
					}
					return array(
						'stabilized'                       => true,
						'stabilized_from_phase'            => $phase,
						'version'                          => $expected_version,
						'database_version'                 => $current_database_version,
						'deployment_id'                    => $deployment_id,
						'installed_plugin_sha256'          => $installed_plugin_sha256,
						'post_install_database_fingerprint'=> $post_install_fingerprint,
						'cache_purge'                       => $cache_purge,
					);
					} finally {
						$release_process_lock( $process_lock );
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/retire',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $verify_site_identity ) {
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					if (
						! function_exists( 'Code_Snippets\\get_snippet' )
						|| ! function_exists( 'Code_Snippets\\delete_snippet' )
					) {
						return new WP_Error( 'c99_retire_api', 'The Code Snippets permanent-delete API is unavailable.', array( 'status' => 500 ) );
					}
					$raw_ids = $request->get_param( 'snippet_ids' );
					if ( ! is_array( $raw_ids ) || empty( $raw_ids ) || count( $raw_ids ) > 100 ) {
						return new WP_Error( 'c99_retire_ids', 'The snippet retirement target list is invalid.', array( 'status' => 400 ) );
					}
					$ids = array_values( array_unique( array_filter( array_map( 'absint', $raw_ids ) ) ) );
					if ( empty( $ids ) ) {
						return new WP_Error( 'c99_retire_ids', 'The snippet retirement target list is empty.', array( 'status' => 400 ) );
					}
					$rows = array();
					foreach ( $ids as $snippet_id ) {
						$snippet = \Code_Snippets\get_snippet( $snippet_id, false );
						if ( ! $snippet || empty( $snippet->id ) ) {
							continue;
						}
						$name = (string) $snippet->name;
						if (
							'c99-deploy-bootstrap' !== $name
							&& ! str_starts_with( $name, 'tmp-complete99-deploy-' )
						) {
							return new WP_Error( 'c99_retire_allowlist', 'Snippet retirement refused a non-deployment row.', array( 'status' => 403 ) );
						}
						$rows[ $snippet_id ] = $name;
					}
					$removed = array();
					foreach ( $rows as $snippet_id => $name ) {
						if ( ! \Code_Snippets\delete_snippet( (int) $snippet_id, false ) ) {
							return new WP_Error( 'c99_retire_delete', 'A deployment snippet row could not be permanently deleted.', array( 'status' => 500 ) );
						}
						$readback = \Code_Snippets\get_snippet( (int) $snippet_id, false );
						if ( $readback && ! empty( $readback->id ) ) {
							return new WP_Error( 'c99_retire_readback', 'A deployment snippet row remained after permanent deletion.', array( 'status' => 500 ) );
						}
						$removed[] = (int) $snippet_id;
					}
					return array(
						'permanently_deleted' => $removed,
						'requested_ids'       => $ids,
						'site_identity'       => $site_identity,
					);
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/run',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $auto_update_enabled, $purge_caches, $claim_lock, $release_lock, $acquire_process_lock, $release_process_lock, $write_state_file, $heartbeat_state, $set_state_phase, $make_test_lock_stale, $directory_sha256, $verify_transactional_storage, $verify_migration_advisory_lock, $capture_database_state, $encrypt_database_state, $decrypt_database_state ) {
					global $wp_filesystem;

					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					if ( $auto_update_enabled() ) {
						return new WP_Error(
							'c99_deploy_auto_update_enabled',
							'Automatic updates must be disabled for the deliberate deployment plugin.',
							array( 'status' => 409 )
						);
					}

					$slug          = sanitize_key( (string) $request->get_param( 'slug' ) );
					$type          = sanitize_key( (string) $request->get_param( 'type' ) );
					$version       = sanitize_text_field( (string) $request->get_param( 'version' ) );
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					$expected      = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_sha256' ) ) );
					$encoded       = (string) $request->get_param( 'package_base64' );
					$activate      = rest_sanitize_boolean( $request->get_param( 'activate' ) );

					if ( $config['slug'] !== $slug || 'plugin' !== $type ) {
						return new WP_Error( 'c99_deploy_allowlist', 'The requested component is not allowlisted.', array( 'status' => 403 ) );
					}
					if ( $config['deployment_id'] !== $deployment_id || ! preg_match( '/^[A-Za-z0-9._-]{8,96}$/', $deployment_id ) ) {
						return new WP_Error( 'c99_deploy_id', 'The deployment ID is invalid.', array( 'status' => 400 ) );
					}
					if ( ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', $version ) || ! preg_match( '/^[a-f0-9]{64}$/', $expected ) ) {
						return new WP_Error( 'c99_deploy_metadata', 'Version or digest metadata is invalid.', array( 'status' => 400 ) );
					}
					if ( strlen( $encoded ) > (int) ceil( $config['max_bytes'] * 1.38 ) ) {
						return new WP_Error( 'c99_deploy_size', 'The encoded package exceeds the upload ceiling.', array( 'status' => 413 ) );
					}

					$bytes = base64_decode( $encoded, true );
					if ( false === $bytes || 0 === strlen( $bytes ) || strlen( $bytes ) > $config['max_bytes'] ) {
						return new WP_Error( 'c99_deploy_package', 'The uploaded package is invalid or too large.', array( 'status' => 413 ) );
					}
					$actual = hash( 'sha256', $bytes );
					if ( ! hash_equals( $expected, $actual ) ) {
						return new WP_Error( 'c99_deploy_digest', 'The uploaded package digest does not match.', array( 'status' => 422 ) );
					}
					$free_space = function_exists( 'disk_free_space' ) ? @disk_free_space( WP_CONTENT_DIR ) : false;
					if ( false !== $free_space && $free_space < $config['min_free_bytes'] ) {
						return new WP_Error(
							'c99_deploy_disk_space',
							'The site no longer has the required deployment headroom.',
							array( 'status' => 507 )
						);
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
					$lock = $claim_lock( $deployment_id, array( 'reserved' ), 'locked', true, false );
					if ( is_wp_error( $lock ) ) {
						return $lock;
					}
					$transactional_storage = $verify_transactional_storage();
					if ( is_wp_error( $transactional_storage ) ) {
						$release_lock( $deployment_id, $lock );
						return $transactional_storage;
					}
					$migration_lock = $verify_migration_advisory_lock();
					if ( is_wp_error( $migration_lock ) ) {
						$release_lock( $deployment_id, $lock );
						return $migration_lock;
					}

					$state_dir   = $state_directory( $deployment_id );
					$state_root  = dirname( $state_dir );
					$backup_dir  = trailingslashit( $state_dir ) . 'plugin';
					$target_dir  = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
					$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
					if ( $wp_filesystem->exists( $state_dir ) ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_deploy_state_exists', 'A deployment state with this ID already exists.', array( 'status' => 409 ) );
					}

					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					$target_dir_exists  = $wp_filesystem->is_dir( $target_dir );
					$plugin_main_exists = $wp_filesystem->exists( $plugin_path );
					$was_active         = is_plugin_active( $config['plugin_file'] );
					if ( $target_dir_exists !== $plugin_main_exists || ( $was_active && ! $plugin_main_exists ) ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error(
							'c99_deploy_partial_plugin',
							'The target plugin has an inconsistent partial installation that must be recovered before deployment.',
							array( 'status' => 409 )
						);
					}
					$had_plugin       = $plugin_main_exists;
					$prior_deployment = (string) get_option( 'complete99_last_deployment_id', '' );
					if ( '' === $prior_deployment && $was_active && defined( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID' ) ) {
						$prior_deployment = (string) COMPLETE99_PLATFORM_DEPLOYMENT_ID;
					}
					$prior_version = '';
					if ( $had_plugin ) {
						$prior_data    = get_plugin_data( $plugin_path, false, false );
						$prior_version = isset( $prior_data['Version'] ) ? (string) $prior_data['Version'] : '';
						if ( '' === $prior_version ) {
							$release_lock( $deployment_id, $lock );
							return new WP_Error( 'c99_deploy_prior_version', 'The installed plugin version could not be validated.', array( 'status' => 409 ) );
						}
					}
					$prior_plugin_sha256 = '';
					if ( $had_plugin ) {
						$prior_plugin_sha256 = $directory_sha256( $target_dir );
						if ( is_wp_error( $prior_plugin_sha256 ) ) {
							$release_lock( $deployment_id, $lock );
							return $prior_plugin_sha256;
						}
					}
					$database_snapshot = $capture_database_state();
					if ( is_wp_error( $database_snapshot ) ) {
						$release_lock( $deployment_id, $lock );
						return $database_snapshot;
					}
					$database_json = wp_json_encode( $database_snapshot );
					if ( false === $database_json ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_db_snapshot_encode', 'The database rollback journal could not be encoded.', array( 'status' => 500 ) );
					}
					$database_fingerprint = hash( 'sha256', $database_json );
					$database_journal     = $encrypt_database_state( $database_snapshot );
					if ( is_wp_error( $database_journal ) ) {
						$release_lock( $deployment_id, $lock );
						return $database_journal;
					}

					if ( ! $wp_filesystem->is_dir( $state_root ) && ! $wp_filesystem->mkdir( $state_root, FS_CHMOD_DIR ) ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_deploy_backup_root', 'Could not create the isolated backup root.', array( 'status' => 500 ) );
					}
					$guard_files = array(
						'index.php'  => "<?php\n// Silence is golden.\n",
						'.htaccess'  => "Require all denied\nDeny from all\n",
						'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
					);
					$guards_written = true;
					foreach ( $guard_files as $guard_name => $guard_contents ) {
						$guards_written = $wp_filesystem->put_contents(
							trailingslashit( $state_root ) . $guard_name,
							$guard_contents,
							FS_CHMOD_FILE
						) && $guards_written;
					}
					if ( ! $guards_written ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_deploy_backup_guard', 'Could not protect the isolated backup root.', array( 'status' => 500 ) );
					}
					if ( ! $wp_filesystem->mkdir( $state_dir, FS_CHMOD_DIR ) ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_deploy_backup_dir', 'Could not create the isolated backup directory.', array( 'status' => 500 ) );
					}

					if ( $had_plugin ) {
						$copy_result = copy_dir( $target_dir, $backup_dir );
						if ( is_wp_error( $copy_result ) ) {
							$wp_filesystem->delete( $state_dir, true );
							$release_lock( $deployment_id, $lock );
							return $copy_result;
						}
						$backup_sha256 = $directory_sha256( $backup_dir );
						if ( is_wp_error( $backup_sha256 ) || ! hash_equals( $prior_plugin_sha256, $backup_sha256 ) ) {
							$wp_filesystem->delete( $state_dir, true );
							$release_lock( $deployment_id, $lock );
							return new WP_Error( 'c99_deploy_backup_digest', 'The rollback plugin backup failed integrity validation.', array( 'status' => 500 ) );
						}
					}
					$state = array(
						'deployment_id'   => $deployment_id,
						'owner_id'        => (string) $lock['owner_id'],
						'fence'           => (int) $lock['fence'],
						'had_plugin'      => $had_plugin,
						'prior_target_dir_exists' => $target_dir_exists,
						'prior_plugin_main_exists'=> $plugin_main_exists,
						'prior_plugin_sha256'=> $prior_plugin_sha256,
						'was_active'      => $was_active,
						'prior_version'   => $prior_version,
						'prior_deployment'=> $prior_deployment,
						'expected_sha256' => $expected,
						'expected_version'=> $version,
						'database_journal' => $database_journal,
						'database_fingerprint'=> $database_fingerprint,
						'phase'           => 'prepared',
						'temp_removed'    => false,
						'updated_at'      => time(),
					);
					$state_written = $write_state_file( trailingslashit( $state_dir ) . 'state.json', $state );
					if ( is_wp_error( $state_written ) ) {
						$wp_filesystem->delete( $state_dir, true );
						$release_lock( $deployment_id, $lock );
						return $state_written;
					}
					$prepared = $set_state_phase( $state_dir, $deployment_id, 'prepared' );
					if ( is_wp_error( $prepared ) ) {
						$wp_filesystem->delete( $state_dir, true );
						$release_lock( $deployment_id, $lock );
						return $prepared;
					}
					$persisted_state = json_decode(
						$wp_filesystem->get_contents( trailingslashit( $state_dir ) . 'state.json' ),
						true
					);
					$persisted_snapshot = is_array( $persisted_state )
						? $decrypt_database_state( $persisted_state['database_journal'] ?? array() )
						: new WP_Error( 'c99_deploy_state_readback', 'The persisted rollback state is invalid.', array( 'status' => 500 ) );
					$persisted_json = is_wp_error( $persisted_snapshot ) ? false : wp_json_encode( $persisted_snapshot );
					if (
						! is_array( $persisted_state )
						|| is_wp_error( $persisted_snapshot )
						|| false === $persisted_json
						|| ! hash_equals( $database_fingerprint, hash( 'sha256', $persisted_json ) )
						|| ! hash_equals( $database_fingerprint, (string) ( $persisted_state['database_fingerprint'] ?? '' ) )
					) {
						$wp_filesystem->delete( $state_dir, true );
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_deploy_journal_readback', 'The persisted rollback journal failed integrity validation.', array( 'status' => 500 ) );
					}
					if ( $config['local_test'] && 'after_prepare' === $config['test_fault'] ) {
						$make_test_lock_stale( $deployment_id );
						return new WP_Error( 'c99_test_interrupt_prepare', 'Injected local interruption after rollback preparation.', array( 'status' => 500 ) );
					}

					$temp_base = wp_tempnam( $slug );
					$temp      = $temp_base ? $temp_base . '.zip' : '';
					if ( $temp_base ) {
						$temp_base_removed = ! $wp_filesystem->exists( $temp_base ) || ( $wp_filesystem->delete( $temp_base ) && ! $wp_filesystem->exists( $temp_base ) );
						if ( ! $temp_base_removed ) {
							$set_state_phase( $state_dir, $deployment_id, 'failed', array( 'temp_removed' => false ) );
							return new WP_Error( 'c99_deploy_temp_base_cleanup', 'The temporary placeholder could not be removed.', array( 'status' => 500 ) );
						}
					}
					$temp_recorded = $set_state_phase(
						$state_dir,
						$deployment_id,
						'prepared',
						array( 'temp_path' => $temp )
					);
					if ( is_wp_error( $temp_recorded ) ) {
						return $temp_recorded;
					}
					if ( ! $temp || ! $wp_filesystem->put_contents( $temp, $bytes, FS_CHMOD_FILE ) ) {
						$temp_removed = ! $temp || ! $wp_filesystem->exists( $temp ) || ( $wp_filesystem->delete( $temp ) && ! $wp_filesystem->exists( $temp ) );
						$set_state_phase( $state_dir, $deployment_id, 'failed', array( 'temp_removed' => $temp_removed, 'temp_path' => '' ) );
						if ( ! $temp_removed ) {
							return new WP_Error( 'c99_deploy_temp_cleanup', 'The partial temporary package could not be removed.', array( 'status' => 500 ) );
						}
						return new WP_Error( 'c99_deploy_temp', 'Could not write the verified package to a temporary file.', array( 'status' => 500 ) );
					}
					$installing = $set_state_phase( $state_dir, $deployment_id, 'installing' );
					if ( is_wp_error( $installing ) ) {
						$wp_filesystem->delete( $temp );
						return $installing;
					}

					try {
						$install_response = ( static function () use ( $temp, $plugin_path, $target_dir, $version, $was_active, $activate, $config, $deployment_id, $actual, $slug, $purge_caches, $capture_database_state, $set_state_phase, $heartbeat_state, $directory_sha256, $state_dir ) {
						require_once ABSPATH . 'wp-admin/includes/misc.php';
						require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
						$owned = $heartbeat_state( $state_dir, $deployment_id, 'installing' );
						if ( is_wp_error( $owned ) ) {
							return $owned;
						}
						$skin     = new WP_Ajax_Upgrader_Skin();
						$upgrader = new Plugin_Upgrader( $skin );
						$result   = $upgrader->install(
							$temp,
							array(
								'overwrite_package'  => true,
								'clear_update_cache' => true,
							)
						);
						if ( is_wp_error( $result ) ) {
							return $result;
						}
						if ( ! $result || ! file_exists( $plugin_path ) ) {
							return new WP_Error( 'c99_deploy_install', 'WordPress did not install the expected plugin file.', array( 'status' => 500 ) );
						}
						$owned = $heartbeat_state( $state_dir, $deployment_id, 'installing' );
						if ( is_wp_error( $owned ) ) {
							return $owned;
						}
						$data = get_plugin_data( $plugin_path, false, false );
						if ( ! isset( $data['Version'] ) || $version !== (string) $data['Version'] ) {
							return new WP_Error( 'c99_deploy_version', 'The installed plugin version does not match the package metadata.', array( 'status' => 422 ) );
						}
						if ( $was_active || $activate ) {
							$owned = $heartbeat_state( $state_dir, $deployment_id, 'installing' );
							if ( is_wp_error( $owned ) ) {
								return $owned;
							}
							$activation = activate_plugin( $config['plugin_file'] );
							if ( is_wp_error( $activation ) ) {
								return $activation;
							}
						}
						$owned = $heartbeat_state( $state_dir, $deployment_id, 'installing' );
						if ( is_wp_error( $owned ) ) {
							return $owned;
						}
						update_option( 'complete99_last_deployment_id', $deployment_id, false );
						$owned = $heartbeat_state( $state_dir, $deployment_id, 'installing' );
						if ( is_wp_error( $owned ) ) {
							return $owned;
						}
						$cache_purge = $purge_caches();
						if ( is_wp_error( $cache_purge ) ) {
							return $cache_purge;
						}
						$post_install_snapshot = $capture_database_state();
						$post_install_json = is_wp_error( $post_install_snapshot )
							? false
							: wp_json_encode( $post_install_snapshot );
						if ( is_wp_error( $post_install_snapshot ) || false === $post_install_json ) {
							return new WP_Error( 'c99_post_install_snapshot', 'The post-install database fingerprint could not be captured.', array( 'status' => 500 ) );
						}
						$post_install_fingerprint = hash( 'sha256', $post_install_json );
						$installed_plugin_sha256 = $directory_sha256( $target_dir );
						if ( is_wp_error( $installed_plugin_sha256 ) || ! preg_match( '/^[a-f0-9]{64}$/', $installed_plugin_sha256 ) ) {
							return new WP_Error( 'c99_installed_plugin_digest', 'The installed plugin directory fingerprint could not be captured.', array( 'status' => 500 ) );
						}
						$post_install_recorded = $set_state_phase(
							$state_dir,
							$deployment_id,
							'installing',
							array(
								'post_install_database_fingerprint' => $post_install_fingerprint,
								'installed_plugin_sha256'           => $installed_plugin_sha256,
							)
						);
						if ( is_wp_error( $post_install_recorded ) ) {
							return $post_install_recorded;
						}
						return array(
							'installed'     => true,
							'slug'          => $slug,
							'version'       => $version,
							'deployment_id' => $deployment_id,
							'sha256'        => $actual,
							'active'        => is_plugin_active( $config['plugin_file'] ),
							'backup_ready'  => true,
							'cache_purge'   => $cache_purge,
							'post_install_database_fingerprint'=> $post_install_fingerprint,
							'installed_plugin_sha256'=> $installed_plugin_sha256,
						);
						} )();
					} catch ( \Throwable $error ) {
						$install_response = new WP_Error( 'c99_deploy_exception', 'The plugin installation raised an exception.', array( 'status' => 500 ) );
					}
					if ( $config['local_test'] && 'after_install' === $config['test_fault'] && is_array( $install_response ) ) {
						$make_test_lock_stale( $deployment_id );
						return new WP_Error( 'c99_test_interrupt_install', 'Injected local interruption after plugin installation.', array( 'status' => 500 ) );
					}
					$temp_removed = ! $wp_filesystem->exists( $temp ) || ( $wp_filesystem->delete( $temp ) && ! $wp_filesystem->exists( $temp ) );
					if ( ! $temp_removed ) {
						$set_state_phase( $state_dir, $deployment_id, 'failed', array( 'temp_removed' => false, 'temp_path' => $temp ) );
						return new WP_Error(
							'c99_deploy_temp_cleanup',
							'The verified temporary package could not be removed.',
							array( 'status' => 500 )
						);
					}
					if ( is_array( $install_response ) ) {
						$install_response['temp_removed'] = true;
						$install_response['baseline_database_fingerprint'] = $database_fingerprint;
						$install_response['had_plugin']       = $had_plugin;
						$install_response['prior_active']     = $was_active;
						$install_response['prior_deployment'] = $prior_deployment;
						$install_response['prior_version']    = $prior_version;
						$install_response['prior_plugin_sha256'] = $prior_plugin_sha256;
						$installed_state = $set_state_phase(
							$state_dir,
							$deployment_id,
							'installed',
							array(
								'temp_removed'      => true,
								'temp_path'         => '',
								'installed_version' => $version,
								'installed_active'  => ! empty( $install_response['active'] ),
							)
						);
						if ( is_wp_error( $installed_state ) ) {
							return $installed_state;
						}
					} elseif ( is_wp_error( $install_response ) ) {
						$set_state_phase( $state_dir, $deployment_id, 'failed', array( 'temp_removed' => true, 'temp_path' => '' ) );
						$data = $install_response->get_error_data();
						$data = is_array( $data ) ? $data : array( 'status' => 500 );
						$data['temp_removed'] = true;
						$install_response->add_data( $data );
					}
					return $install_response;
					} finally {
						$release_process_lock( $process_lock );
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/rollback',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $purge_caches, $read_lock, $claim_lock, $acquire_process_lock, $release_process_lock, $adopt_state_lease, $heartbeat_state, $set_state_phase, $make_test_lock_stale, $directory_sha256, $capture_database_state, $restore_database_state, $decrypt_database_state ) {
					global $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					if ( $config['deployment_id'] !== $deployment_id ) {
						return new WP_Error( 'c99_rollback_id', 'The rollback deployment ID is invalid.', array( 'status' => 400 ) );
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
					$state_dir  = $state_directory( $deployment_id );
					$state_file = trailingslashit( $state_dir ) . 'state.json';
					if ( ! $wp_filesystem->exists( $state_file ) ) {
						return new WP_Error( 'c99_rollback_state', 'Rollback state was not found.', array( 'status' => 404 ) );
					}
					$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
					if ( ! is_array( $state ) ) {
						return new WP_Error( 'c99_rollback_state_invalid', 'Rollback state is invalid.', array( 'status' => 500 ) );
					}
					$lock = $read_lock( true );
					if ( $deployment_id !== (string) ( $lock['deployment_id'] ?? '' ) ) {
						return new WP_Error( 'c99_rollback_lock', 'The deployment does not own the mutation lock.', array( 'status' => 409 ) );
					}
					$phase = (string) ( $state['phase'] ?? '' );
					if ( 'rolled_back' === $phase ) {
						return array(
							'rolled_back'     => true,
							'had_plugin'      => ! empty( $state['had_plugin'] ),
							'baseline_database_fingerprint'=> (string) ( $state['database_fingerprint'] ?? '' ),
							'prior_plugin_sha256'=> (string) ( $state['prior_plugin_sha256'] ?? '' ),
							'prior_version'   => isset( $state['prior_version'] ) ? (string) $state['prior_version'] : '',
							'prior_active'    => ! empty( $state['was_active'] ),
							'prior_deployment'=> isset( $state['prior_deployment'] ) ? (string) $state['prior_deployment'] : '',
							'database_restore'=> ! empty( $state['database_restored'] ) ? array( 'already_restored' => true ) : array(),
							'idempotent'      => true,
						);
					}
					$interrupted_phase = in_array( $phase, array( 'prepared', 'installing', 'rolling_back', 'committing' ), true );
					$lock_updated_at   = (int) ( $lock['updated_at'] ?? $lock['started_at'] ?? 0 );
					$lock_age          = 0 < $lock_updated_at ? max( 0, time() - $lock_updated_at ) : 0;
					if ( $interrupted_phase && $lock_age < (int) $config['recovery_lease_seconds'] ) {
						return new WP_Error(
							'c99_rollback_lease',
							'Rollback is waiting for the interrupted-request recovery lease.',
							array(
								'status'                 => 409,
								'phase'                  => $phase,
								'lock_age_seconds'       => $lock_age,
								'recovery_lease_seconds' => (int) $config['recovery_lease_seconds'],
							)
						);
					}
					if ( ! $interrupted_phase && ! in_array( $phase, array( 'installed', 'failed', 'rollback_failed', 'commit_failed' ), true ) ) {
						return new WP_Error(
							'c99_rollback_not_ready',
							'Rollback is refused while the deployment is not in a terminal mutable phase.',
							array( 'status' => 409, 'phase' => $phase )
						);
					}
					$lease = $claim_lock(
						$deployment_id,
						array( 'prepared', 'installing', 'rolling_back', 'committing', 'installed', 'failed', 'rollback_failed', 'commit_failed' ),
						$phase,
						false,
						$interrupted_phase
					);
					if ( is_wp_error( $lease ) ) {
						return $lease;
					}
					$adopted = $adopt_state_lease( $state_dir, $deployment_id, $lease );
					if ( is_wp_error( $adopted ) ) {
						return $adopted;
					}
					$state = $adopted;

					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					$target_dir  = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
					$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
					$backup_dir  = trailingslashit( $state_dir ) . 'plugin';
					$backup_main = trailingslashit( $backup_dir ) . basename( $config['plugin_file'] );
					$swap_suffix = substr( hash( 'sha256', $deployment_id ), 0, 20 );
					$restore_stage = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix;
					$displaced_dir = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix;
					$database_snapshot = $decrypt_database_state( $state['database_journal'] ?? array() );
					if ( is_wp_error( $database_snapshot ) ) {
						return $database_snapshot;
					}
					if (
						! isset( $database_snapshot['options'], $database_snapshot['posts'], $database_snapshot['postmeta'], $database_snapshot['seed_ids'], $database_snapshot['sync_secret_existed'] )
						|| ! is_array( $database_snapshot['options'] )
						|| ! is_array( $database_snapshot['posts'] )
						|| ! is_array( $database_snapshot['postmeta'] )
						|| ! is_array( $database_snapshot['seed_ids'] )
					) {
						return new WP_Error( 'c99_db_snapshot_invalid', 'The database rollback journal is invalid.', array( 'status' => 500 ) );
					}
					$database_json = wp_json_encode( $database_snapshot );
					$baseline_fingerprint = (string) ( $state['database_fingerprint'] ?? '' );
					if (
						false === $database_json
						|| ! preg_match( '/^[a-f0-9]{64}$/', $baseline_fingerprint )
						|| ! hash_equals( $baseline_fingerprint, hash( 'sha256', $database_json ) )
					) {
						return new WP_Error( 'c99_db_snapshot_digest', 'The database rollback journal failed integrity validation.', array( 'status' => 500 ) );
					}
					$current_database_snapshot = $capture_database_state();
					$current_database_json = is_wp_error( $current_database_snapshot )
						? false
						: wp_json_encode( $current_database_snapshot );
					if ( is_wp_error( $current_database_snapshot ) || false === $current_database_json ) {
						return new WP_Error( 'c99_rollback_database_probe', 'The current plugin-owned database fingerprint could not be captured.', array( 'status' => 500 ) );
					}
					$current_database_fingerprint = hash( 'sha256', $current_database_json );
					$post_install_fingerprint = (string) ( $state['post_install_database_fingerprint'] ?? '' );
					if ( hash_equals( $baseline_fingerprint, $current_database_fingerprint ) ) {
						$database_restore_required = false;
					} elseif (
						preg_match( '/^[a-f0-9]{64}$/', $post_install_fingerprint )
						&& hash_equals( $post_install_fingerprint, $current_database_fingerprint )
					) {
						$database_restore_required = true;
					} else {
						return new WP_Error(
							'c99_rollback_database_conflict',
							'Rollback refused because plugin-owned data changed after installation.',
							array( 'status' => 409 )
						);
					}

					$had_plugin       = ! empty( $state['had_plugin'] );
					$prior_dir        = ! empty( $state['prior_target_dir_exists'] );
					$prior_main       = ! empty( $state['prior_plugin_main_exists'] );
					$prior_version    = (string) ( $state['prior_version'] ?? '' );
					$prior_plugin_sha = (string) ( $state['prior_plugin_sha256'] ?? '' );
					if ( $had_plugin ) {
						if ( ! $prior_dir || ! $prior_main || ! $wp_filesystem->is_dir( $backup_dir ) || ! $wp_filesystem->exists( $backup_main ) ) {
							return new WP_Error( 'c99_rollback_backup_missing', 'The rollback plugin backup is incomplete.', array( 'status' => 500 ) );
						}
						$backup_digest = $directory_sha256( $backup_dir );
						$backup_data   = get_plugin_data( $backup_main, false, false );
						if (
							is_wp_error( $backup_digest )
							|| ! preg_match( '/^[a-f0-9]{64}$/', $prior_plugin_sha )
							|| ! hash_equals( $prior_plugin_sha, $backup_digest )
							|| '' === $prior_version
							|| $prior_version !== (string) ( $backup_data['Version'] ?? '' )
						) {
							return new WP_Error( 'c99_rollback_backup_invalid', 'The rollback plugin backup failed integrity validation.', array( 'status' => 500 ) );
						}
					} elseif ( $prior_dir || $prior_main || '' !== $prior_version || '' !== $prior_plugin_sha || $wp_filesystem->exists( $backup_dir ) ) {
						return new WP_Error( 'c99_rollback_absent_baseline', 'The first-install rollback baseline is inconsistent.', array( 'status' => 500 ) );
					}

					$temp_path = (string) ( $state['temp_path'] ?? '' );
					if ( '' !== $temp_path ) {
						$temp_root       = strtolower( trailingslashit( wp_normalize_path( get_temp_dir() ) ) );
						$normalized_temp = strtolower( wp_normalize_path( $temp_path ) );
						if ( ! str_starts_with( $normalized_temp, $temp_root ) || ! str_ends_with( $normalized_temp, '.zip' ) ) {
							return new WP_Error( 'c99_rollback_temp_path', 'The recorded temporary package path is invalid.', array( 'status' => 500 ) );
						}
						if ( $wp_filesystem->exists( $temp_path ) && ! $wp_filesystem->delete( $temp_path ) ) {
							return new WP_Error( 'c99_rollback_temp_cleanup', 'The interrupted temporary package could not be removed.', array( 'status' => 500 ) );
						}
					}

					$rollback_files_already_restored = false;
					if (
						( $wp_filesystem->exists( $displaced_dir ) || ! empty( $state['rollback_applied'] ) )
						&& hash_equals( $baseline_fingerprint, $current_database_fingerprint )
					) {
						if ( $had_plugin && $wp_filesystem->is_dir( $target_dir ) ) {
							$already_restored_digest = $directory_sha256( $target_dir );
							$rollback_files_already_restored = ! is_wp_error( $already_restored_digest )
								&& hash_equals( $prior_plugin_sha, $already_restored_digest );
						} elseif ( ! $had_plugin && ! $wp_filesystem->exists( $target_dir ) ) {
							$rollback_files_already_restored = true;
						}
					}
					$forward_was_active = array_key_exists( 'forward_was_active', $state )
						? ! empty( $state['forward_was_active'] )
						: is_plugin_active( $config['plugin_file'] );
					$forward_plugin_sha256 = (string) ( $state['forward_plugin_sha256'] ?? '' );
					if ( '' === $forward_plugin_sha256 ) {
						$forward_source = $wp_filesystem->is_dir( $displaced_dir )
							? $displaced_dir
							: ( $wp_filesystem->is_dir( $target_dir ) ? $target_dir : '' );
						if ( '' !== $forward_source ) {
							$forward_digest = $directory_sha256( $forward_source );
							if ( is_wp_error( $forward_digest ) ) {
								return $forward_digest;
							}
							$forward_plugin_sha256 = $forward_digest;
						}
						$forward_recorded = $set_state_phase(
							$state_dir,
							$deployment_id,
							$phase,
							array(
								'forward_plugin_sha256' => $forward_plugin_sha256,
								'forward_was_active'    => $forward_was_active,
							)
						);
						if ( is_wp_error( $forward_recorded ) ) {
							return $forward_recorded;
						}
						$state = $forward_recorded;
					} elseif ( ! preg_match( '/^[a-f0-9]{64}$/', $forward_plugin_sha256 ) ) {
						return new WP_Error( 'c99_rollback_forward_digest', 'The recorded forward plugin fingerprint is invalid.', array( 'status' => 500 ) );
					}

					/*
					 * A request may stop after the forward plugin is displaced. Restore
					 * that exact forward tree first, then restart the rollback from the
					 * immutable backup. Never delete the only forward copy.
					 */
					if ( $wp_filesystem->exists( $displaced_dir ) ) {
						if ( '' === $forward_plugin_sha256 ) {
							return new WP_Error( 'c99_rollback_forward_missing_digest', 'The displaced forward plugin has no recorded fingerprint.', array( 'status' => 500 ) );
						}
						$displaced_digest = $directory_sha256( $displaced_dir );
						if ( is_wp_error( $displaced_digest ) || ! hash_equals( $forward_plugin_sha256, $displaced_digest ) ) {
							return new WP_Error( 'c99_rollback_displaced_digest', 'The displaced forward plugin failed integrity validation.', array( 'status' => 500 ) );
						}
						if ( ! $rollback_files_already_restored && $wp_filesystem->exists( $target_dir ) ) {
							if ( $wp_filesystem->exists( $restore_stage ) ) {
								return new WP_Error( 'c99_rollback_resume_ambiguous', 'Rollback recovery found an ambiguous three-directory swap state.', array( 'status' => 500 ) );
							}
							$target_digest = $directory_sha256( $target_dir );
							if (
								! $had_plugin
								|| is_wp_error( $target_digest )
								|| ! hash_equals( $prior_plugin_sha, $target_digest )
								|| ! @rename( $target_dir, $restore_stage )
							) {
								return new WP_Error( 'c99_rollback_resume_target', 'Rollback recovery could not preserve the restored prior plugin.', array( 'status' => 500 ) );
							}
						}
						if ( ! $rollback_files_already_restored && ! @rename( $displaced_dir, $target_dir ) ) {
							if ( $wp_filesystem->exists( $restore_stage ) && ! $wp_filesystem->exists( $target_dir ) ) {
								@rename( $restore_stage, $target_dir );
							}
							return new WP_Error( 'c99_rollback_resume_forward', 'Rollback recovery could not restore the exact forward plugin.', array( 'status' => 500 ) );
						}
						if ( ! $rollback_files_already_restored ) {
							wp_opcache_invalidate_directory( $target_dir );
							clearstatcache( true, $plugin_path );
							$resumed_digest = $directory_sha256( $target_dir );
							if ( is_wp_error( $resumed_digest ) || ! hash_equals( $forward_plugin_sha256, $resumed_digest ) ) {
								return new WP_Error( 'c99_rollback_resume_digest', 'The resumed forward plugin failed integrity validation.', array( 'status' => 500 ) );
							}
						}
					}
					if ( ! $rollback_files_already_restored && (bool) is_plugin_active( $config['plugin_file'] ) !== $forward_was_active ) {
						return new WP_Error( 'c99_rollback_resume_activation', 'Rollback recovery found a changed forward activation state.', array( 'status' => 409 ) );
					}
					if ( $wp_filesystem->exists( $restore_stage ) && ! $wp_filesystem->delete( $restore_stage, true ) ) {
						return new WP_Error( 'c99_rollback_stage_cleanup', 'The preserved prior staging directory could not be reset.', array( 'status' => 500 ) );
					}
					if ( $had_plugin && ! $rollback_files_already_restored ) {
						$stage_result = copy_dir( $backup_dir, $restore_stage );
						$stage_digest = is_wp_error( $stage_result ) ? $stage_result : $directory_sha256( $restore_stage );
						$stage_main   = trailingslashit( $restore_stage ) . basename( $config['plugin_file'] );
						$stage_data   = $wp_filesystem->exists( $stage_main ) ? get_plugin_data( $stage_main, false, false ) : array();
						if (
							is_wp_error( $stage_result )
							|| is_wp_error( $stage_digest )
							|| ! hash_equals( $prior_plugin_sha, $stage_digest )
							|| $prior_version !== (string) ( $stage_data['Version'] ?? '' )
						) {
							$wp_filesystem->delete( $restore_stage, true );
							return new WP_Error( 'c99_rollback_stage_invalid', 'The staged rollback plugin failed integrity validation.', array( 'status' => 500 ) );
						}
					}

					$rolling_back = $set_state_phase(
						$state_dir,
						$deployment_id,
						'rolling_back',
						array(
							'recovered_from_phase' => $interrupted_phase ? $phase : '',
							'temp_removed'         => true,
							'temp_path'            => '',
							'forward_plugin_sha256'=> $forward_plugin_sha256,
							'forward_was_active'   => $forward_was_active,
							'rollback_files_already_restored'=> $rollback_files_already_restored,
						)
					);
					if ( is_wp_error( $rolling_back ) ) {
						$wp_filesystem->delete( $restore_stage, true );
						return $rolling_back;
					}
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					if ( ! $rollback_files_already_restored ) {
					if ( is_link( $target_dir ) ) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_target_link', 'The deployed plugin path is unsafe.', array( 'status' => 500 ) );
					}
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					if ( $wp_filesystem->exists( $target_dir ) ) {
						$current_forward_digest = $directory_sha256( $target_dir );
						if (
							is_wp_error( $current_forward_digest )
							|| '' === $forward_plugin_sha256
							|| ! hash_equals( $forward_plugin_sha256, $current_forward_digest )
						) {
							$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
							return new WP_Error( 'c99_rollback_forward_changed', 'Rollback refused because the forward plugin changed before displacement.', array( 'status' => 409 ) );
						}
						if ( ! @rename( $target_dir, $displaced_dir ) ) {
							$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
							return new WP_Error( 'c99_rollback_displace', 'Could not atomically displace the deployed plugin.', array( 'status' => 500 ) );
						}
					} elseif ( '' !== $forward_plugin_sha256 ) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_forward_missing', 'The recorded forward plugin is missing before rollback.', array( 'status' => 409 ) );
					}
					if (
						$config['local_test']
						&& 'during_rollback' === $config['test_fault']
						&& empty( $state['test_fault_triggered'] )
					) {
						$set_state_phase(
							$state_dir,
							$deployment_id,
							'rolling_back',
							array( 'test_fault_triggered' => true )
						);
						$make_test_lock_stale( $deployment_id );
						return new WP_Error( 'c99_test_interrupt_rollback', 'Injected local interruption during atomic rollback.', array( 'status' => 500 ) );
					}
					if ( $had_plugin ) {
						$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
						if ( is_wp_error( $owned ) ) {
							return $owned;
						}
						if ( ! @rename( $restore_stage, $target_dir ) ) {
							$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
							if ( ! $wp_filesystem->exists( $target_dir ) && $wp_filesystem->exists( $displaced_dir ) ) {
								@rename( $displaced_dir, $target_dir );
								wp_opcache_invalidate_directory( $target_dir );
							}
							return new WP_Error( 'c99_rollback_swap', 'Could not atomically restore the prior plugin.', array( 'status' => 500 ) );
						}
						wp_opcache_invalidate_directory( $target_dir );
						clearstatcache( true, $plugin_path );
						$restored_digest = $directory_sha256( $target_dir );
						$restored_data   = $wp_filesystem->exists( $plugin_path ) ? get_plugin_data( $plugin_path, false, false ) : array();
						if (
							is_wp_error( $restored_digest )
							|| ! hash_equals( $prior_plugin_sha, $restored_digest )
							|| $prior_version !== (string) ( $restored_data['Version'] ?? '' )
						) {
							$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
							return new WP_Error( 'c99_rollback_restore_digest', 'The restored plugin failed integrity validation.', array( 'status' => 500 ) );
						}
					}
					}
					$compensate_forward = static function ( $error_code, $message, $status, $expected_database_fingerprint = '' ) use ( $config, $wp_filesystem, $target_dir, $restore_stage, $displaced_dir, $plugin_path, $forward_plugin_sha256, $forward_was_active, $directory_sha256, $capture_database_state, $set_state_phase, $state_dir, $deployment_id ) {
						$compensation_error = '';
						if ( $wp_filesystem->exists( $target_dir ) ) {
							if ( $wp_filesystem->exists( $restore_stage ) || ! @rename( $target_dir, $restore_stage ) ) {
								$compensation_error = 'preserve_prior';
							}
						}
						if ( '' === $compensation_error && '' !== $forward_plugin_sha256 ) {
							if ( ! $wp_filesystem->exists( $displaced_dir ) || ! @rename( $displaced_dir, $target_dir ) ) {
								$compensation_error = 'restore_forward';
								if ( ! $wp_filesystem->exists( $target_dir ) && $wp_filesystem->exists( $restore_stage ) ) {
									@rename( $restore_stage, $target_dir );
								}
							}
						} elseif ( '' === $compensation_error && $wp_filesystem->exists( $displaced_dir ) ) {
							$compensation_error = 'unexpected_forward';
						}
						if ( '' === $compensation_error ) {
							if ( '' !== $forward_plugin_sha256 ) {
								wp_opcache_invalidate_directory( $target_dir );
								clearstatcache( true, $plugin_path );
								$forward_digest = $directory_sha256( $target_dir );
								if ( is_wp_error( $forward_digest ) || ! hash_equals( $forward_plugin_sha256, $forward_digest ) ) {
									$compensation_error = 'forward_digest';
								}
							} elseif ( $wp_filesystem->exists( $target_dir ) ) {
								$compensation_error = 'forward_absence';
							}
						}
						if ( '' === $compensation_error && (bool) is_plugin_active( $config['plugin_file'] ) !== $forward_was_active ) {
							if ( $forward_was_active && '' !== $forward_plugin_sha256 ) {
								$activation = activate_plugin( $config['plugin_file'] );
								if ( is_wp_error( $activation ) ) {
									$compensation_error = 'forward_activation';
								}
							} elseif ( ! $forward_was_active && is_plugin_active( $config['plugin_file'] ) ) {
								deactivate_plugins( $config['plugin_file'], true );
							}
						}
						if ( '' === $compensation_error && (bool) is_plugin_active( $config['plugin_file'] ) !== $forward_was_active ) {
							$compensation_error = 'forward_activation_state';
						}
						if ( '' === $compensation_error && '' !== $expected_database_fingerprint ) {
							$compensated_snapshot = $capture_database_state();
							$compensated_json = is_wp_error( $compensated_snapshot )
								? false
								: wp_json_encode( $compensated_snapshot );
							$compensated_fingerprint = false === $compensated_json ? '' : hash( 'sha256', $compensated_json );
							if ( ! hash_equals( $expected_database_fingerprint, $compensated_fingerprint ) ) {
								$compensation_error = 'database_fingerprint';
							}
						}
						$compensation_state = $set_state_phase(
							$state_dir,
							$deployment_id,
							'rollback_failed',
							array(
								'rollback_compensated'       => '' === $compensation_error,
								'rollback_compensation_error'=> $compensation_error,
							)
						);
						if ( is_wp_error( $compensation_state ) || '' !== $compensation_error ) {
							return new WP_Error(
								'c99_rollback_compensation_failed',
								'Rollback failed and the exact forward plugin could not be fully restored.',
								array( 'status' => 500, 'stage' => sanitize_key( $compensation_error ) )
							);
						}
						return new WP_Error(
							$error_code,
							$message,
							array( 'status' => (int) $status, 'forward_compensated' => true )
						);
					};
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					$pre_restore_snapshot = $capture_database_state();
					$pre_restore_json = is_wp_error( $pre_restore_snapshot )
						? false
						: wp_json_encode( $pre_restore_snapshot );
					$pre_restore_fingerprint = false === $pre_restore_json ? '' : hash( 'sha256', $pre_restore_json );
					if (
						is_wp_error( $pre_restore_snapshot )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $pre_restore_fingerprint )
						|| ! hash_equals( $current_database_fingerprint, $pre_restore_fingerprint )
					) {
						return $compensate_forward(
							'c99_rollback_database_conflict',
							'Rollback refused because plugin-owned data changed during rollback.',
							409
						);
					}
					if ( $database_restore_required ) {
						$database_restore = $restore_database_state( $database_snapshot );
						if ( is_wp_error( $database_restore ) ) {
							return $compensate_forward(
								'c99_db_restore_compensated',
								'The database rollback failed; the exact forward plugin and activation state were restored.',
								500,
								$current_database_fingerprint
							);
						}
					} else {
						$database_restore = array(
							'already_baseline' => true,
							'options_restored' => 0,
							'posts_restored'   => 0,
							'meta_restored'    => 0,
						);
					}
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					$restored_database_snapshot = $capture_database_state();
					$restored_database_json = is_wp_error( $restored_database_snapshot )
						? false
						: wp_json_encode( $restored_database_snapshot );
					$restored_database_fingerprint = false === $restored_database_json
						? ''
						: hash( 'sha256', $restored_database_json );
					if (
						is_wp_error( $restored_database_snapshot )
						|| ! hash_equals( $baseline_fingerprint, $restored_database_fingerprint )
					) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_database_readback', 'The restored database did not match the exact rollback baseline.', array( 'status' => 500 ) );
					}
					if ( (bool) is_plugin_active( $config['plugin_file'] ) !== ! empty( $state['was_active'] ) ) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_activation_state', 'The restored plugin activation state does not match the rollback journal.', array( 'status' => 500 ) );
					}
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					$cache_purge = $purge_caches();
					if ( is_wp_error( $cache_purge ) ) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return $cache_purge;
					}
					$rollback_checkpoint = $set_state_phase(
						$state_dir,
						$deployment_id,
						'rolling_back',
						array(
							'database_restored' => true,
							'rollback_applied'  => true,
						)
					);
					if ( is_wp_error( $rollback_checkpoint ) ) {
						return $rollback_checkpoint;
					}
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					if ( $wp_filesystem->exists( $displaced_dir ) && ! $wp_filesystem->delete( $displaced_dir, true ) ) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_displaced_cleanup', 'The displaced plugin directory could not be removed.', array( 'status' => 500 ) );
					}
					$rolled_back = $set_state_phase(
						$state_dir,
						$deployment_id,
						'rolled_back',
						array( 'database_restored' => true )
					);
					if ( is_wp_error( $rolled_back ) ) {
						return $rolled_back;
					}
					return array(
						'rolled_back'     => true,
						'had_plugin'      => ! empty( $state['had_plugin'] ),
						'baseline_database_fingerprint'=> $baseline_fingerprint,
						'prior_plugin_sha256'=> $prior_plugin_sha,
						'prior_version'   => isset( $state['prior_version'] ) ? (string) $state['prior_version'] : '',
						'prior_active'    => ! empty( $state['was_active'] ),
						'prior_deployment'=> isset( $state['prior_deployment'] ) ? (string) $state['prior_deployment'] : '',
						'database_restore'=> $database_restore,
						'cache_purge'     => $cache_purge,
					);
					} finally {
						$release_process_lock( $process_lock );
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/finalize',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $lock_owner, $bootstrap_filesystem, $verify_site_identity, $state_directory, $purge_caches, $read_lock, $claim_lock, $heartbeat_lock, $release_lock, $acquire_process_lock, $release_process_lock, $adopt_state_lease, $heartbeat_state, $set_state_phase ) {
					global $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					if ( $config['deployment_id'] !== $deployment_id ) {
						return new WP_Error( 'c99_finalize_id', 'The finalize deployment ID is invalid.', array( 'status' => 400 ) );
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
					$state_dir  = $state_directory( $deployment_id );
					$state_file = trailingslashit( $state_dir ) . 'state.json';
					$lock       = $read_lock( true );
					$lock_owned = $deployment_id === (string) ( $lock['deployment_id'] ?? '' );
					$state_exists = $wp_filesystem->exists( $state_file );
					if ( ! $state_exists && ! $lock_owned ) {
						return array(
							'finalized'    => true,
							'idempotent'   => true,
							'lock_released'=> true,
							'state_removed'=> true,
							'cache_purge'  => array( 'not_required' => true ),
						);
					}
					$state = $state_exists ? json_decode( $wp_filesystem->get_contents( $state_file ), true ) : array();
					if ( $state_exists && ! is_array( $state ) ) {
						return new WP_Error( 'c99_finalize_state_invalid', 'Deployment state is invalid.', array( 'status' => 500 ) );
					}
					$phase = $state_exists ? (string) ( $state['phase'] ?? '' ) : (string) ( $lock['phase'] ?? '' );
					$owner_changed = $lock_owner !== (string) ( $lock['owner_id'] ?? '' );
					$require_stale = ( 'locked' === $phase ) || ( 'reserved' === $phase && $owner_changed ) || ( 'committing' === $phase && $owner_changed );
					$lease = $claim_lock(
						$deployment_id,
						array( 'reserved', 'locked', 'installed', 'rolled_back', 'commit_failed', 'committing', 'committed', 'cleanup_failed' ),
						$phase,
						false,
						$require_stale
					);
					if ( is_wp_error( $lease ) ) {
						return $lease;
					}
					if ( $state_exists ) {
						$adopted = $adopt_state_lease( $state_dir, $deployment_id, $lease );
						if ( is_wp_error( $adopted ) ) {
							return $adopted;
						}
						$state = $adopted;
					}
					$lock = $lease;
					$lock_owned = true;
					$cache_purge = array( 'already_purged' => false );
					if ( $state_exists ) {
						if ( in_array( $phase, array( 'installed', 'rolled_back', 'commit_failed', 'committing' ), true ) ) {
							if ( 'installed' === $phase ) {
								$commit_identity = array(
									'committed_outcome'                 => 'installed',
									'committed_expected_active'         => ! empty( $state['installed_active'] ),
									'committed_expected_absent'         => false,
									'committed_expected_version'        => (string) ( $state['expected_version'] ?? '' ),
									'committed_expected_deployment'     => $deployment_id,
									'committed_expected_plugin_sha256'  => (string) ( $state['installed_plugin_sha256'] ?? '' ),
								);
							} elseif ( 'rolled_back' === $phase ) {
								$rollback_had_plugin = ! empty( $state['had_plugin'] );
								$commit_identity = array(
									'committed_outcome'                 => 'rolled_back',
									'committed_expected_active'         => $rollback_had_plugin && ! empty( $state['was_active'] ),
									'committed_expected_absent'         => ! $rollback_had_plugin,
									'committed_expected_version'        => $rollback_had_plugin ? (string) ( $state['prior_version'] ?? '' ) : '',
									'committed_expected_deployment'     => (string) ( $state['prior_deployment'] ?? '' ),
									'committed_expected_plugin_sha256'  => $rollback_had_plugin ? (string) ( $state['prior_plugin_sha256'] ?? '' ) : '',
								);
							} else {
								$commit_identity = array(
									'committed_outcome'                 => (string) ( $state['committed_outcome'] ?? '' ),
									'committed_expected_active'         => ! empty( $state['committed_expected_active'] ),
									'committed_expected_absent'         => ! empty( $state['committed_expected_absent'] ),
									'committed_expected_version'        => (string) ( $state['committed_expected_version'] ?? '' ),
									'committed_expected_deployment'     => (string) ( $state['committed_expected_deployment'] ?? '' ),
									'committed_expected_plugin_sha256'  => (string) ( $state['committed_expected_plugin_sha256'] ?? '' ),
								);
							}
							$identity_valid = in_array( $commit_identity['committed_outcome'], array( 'installed', 'rolled_back' ), true );
							if ( $commit_identity['committed_expected_absent'] ) {
								$identity_valid = $identity_valid
									&& ! $commit_identity['committed_expected_active']
									&& '' === $commit_identity['committed_expected_version']
									&& '' === $commit_identity['committed_expected_plugin_sha256'];
							} else {
								$identity_valid = $identity_valid
									&& '' !== $commit_identity['committed_expected_version']
									&& preg_match( '/^[a-f0-9]{64}$/', $commit_identity['committed_expected_plugin_sha256'] );
							}
							if ( ! $identity_valid ) {
								return new WP_Error( 'c99_finalize_identity', 'Finalization requires an exact committed release identity.', array( 'status' => 409 ) );
							}
							$committing = $set_state_phase( $state_dir, $deployment_id, 'committing', $commit_identity );
							if ( is_wp_error( $committing ) ) {
								return $committing;
							}
							$state = $committing;
							$cache_purge = $purge_caches();
							if ( is_wp_error( $cache_purge ) ) {
								$set_state_phase( $state_dir, $deployment_id, 'commit_failed' );
								return $cache_purge;
							}
							$committed = $set_state_phase( $state_dir, $deployment_id, 'committed' );
							if ( is_wp_error( $committed ) ) {
								return $committed;
							}
							$state = $committed;
							$phase = 'committed';
							if (
								$config['local_test']
								&& 'after_commit' === $config['test_fault']
								&& empty( $state['test_fault_triggered'] )
							) {
								$set_state_phase(
									$state_dir,
									$deployment_id,
									'cleanup_failed',
									array( 'test_fault_triggered' => true )
								);
								return new WP_Error( 'c99_test_interrupt_commit', 'Injected local interruption after deployment commit.', array( 'status' => 500 ) );
							}
						} elseif ( in_array( $phase, array( 'committed', 'cleanup_failed' ), true ) ) {
							$cache_purge = array( 'already_purged' => true );
						} else {
							return new WP_Error(
								'c99_finalize_not_ready',
								'Finalization is refused before install or rollback reaches a terminal phase.',
								array( 'status' => 409, 'phase' => $phase )
							);
						}
					} elseif ( $lock_owned && 'reserved' === (string) ( $lock['phase'] ?? '' ) ) {
						$cache_purge = array( 'not_required' => true );
					} elseif ( $lock_owned && 'locked' === (string) ( $lock['phase'] ?? '' ) ) {
						$cache_purge = array( 'unstarted_recovery' => true );
					} elseif ( $lock_owned && in_array( (string) ( $lock['phase'] ?? '' ), array( 'committed', 'cleanup_failed' ), true ) ) {
						$cache_purge = array( 'already_purged' => true );
					} elseif ( $lock_owned ) {
						return new WP_Error(
							'c99_finalize_lock_phase',
							'The deployment lock is not ready for finalization.',
							array( 'status' => 409, 'phase' => (string) ( $lock['phase'] ?? '' ) )
						);
					}
					if ( $state_exists ) {
						$owned = $heartbeat_state( $state_dir, $deployment_id, (string) ( $state['phase'] ?? $phase ) );
					} else {
						$owned = $heartbeat_lock( $deployment_id, (string) $lease['owner_id'], (int) $lease['fence'], (string) $lease['phase'] );
					}
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					if ( $state_exists ) {
						$lock_identity = array(
							'expected_sha256'                   => (string) ( $state['expected_sha256'] ?? '' ),
							'expected_version'                  => (string) ( $state['expected_version'] ?? '' ),
							'installed_plugin_sha256'           => (string) ( $state['installed_plugin_sha256'] ?? '' ),
							'committed_outcome'                 => (string) ( $state['committed_outcome'] ?? '' ),
							'committed_expected_active'         => ! empty( $state['committed_expected_active'] ),
							'committed_expected_absent'         => ! empty( $state['committed_expected_absent'] ),
							'committed_expected_version'        => (string) ( $state['committed_expected_version'] ?? '' ),
							'committed_expected_deployment'     => (string) ( $state['committed_expected_deployment'] ?? '' ),
							'committed_expected_plugin_sha256'  => (string) ( $state['committed_expected_plugin_sha256'] ?? '' ),
						);
						$owned = $heartbeat_lock(
							$deployment_id,
							(string) $lease['owner_id'],
							(int) $lease['fence'],
							(string) ( $state['phase'] ?? $phase ),
							$lock_identity
						);
						if ( is_wp_error( $owned ) ) {
							return $owned;
						}
						$lease = $owned;
					}
					$removed = ! $wp_filesystem->exists( $state_dir ) || $wp_filesystem->delete( $state_dir, true );
					if ( ! $removed ) {
						if ( $wp_filesystem->exists( $state_file ) ) {
							$set_state_phase( $state_dir, $deployment_id, 'cleanup_failed' );
						}
						return new WP_Error( 'c99_finalize_cleanup', 'Could not remove the isolated deployment backup.', array( 'status' => 500 ) );
					}
					if ( $lock_owned && ! $release_lock( $deployment_id, $lease ) ) {
						return new WP_Error( 'c99_finalize_unlock', 'Could not release the deployment mutation lock.', array( 'status' => 500 ) );
					}
					$remaining_lock = $read_lock( true );
					if ( $deployment_id === (string) ( $remaining_lock['deployment_id'] ?? '' ) ) {
						return new WP_Error( 'c99_finalize_lock_present', 'The deployment mutation lock remains present.', array( 'status' => 500 ) );
					}
					return array(
						'finalized'   => true,
						'idempotent'  => ! $lock_owned,
						'lock_released'=> true,
						'state_removed'=> ! $wp_filesystem->exists( $state_dir ),
						'cache_purge' => $cache_purge,
					);
					} finally {
						$release_process_lock( $process_lock );
					}
				},
			)
		);
	}
);
