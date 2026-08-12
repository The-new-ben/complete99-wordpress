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
			'token'                    => '__C99_TOKEN__',
			'deployment_id'            => '__C99_DEPLOYMENT_ID__',
			'slug'                     => 'complete99-platform',
			'plugin_file'              => 'complete99-platform/complete99-platform.php',
			'max_bytes'     => __C99_MAX_BYTES__,
			'min_free_bytes'=> __C99_MIN_FREE_BYTES__,
			'expected_artifact_sha256' => '__C99_EXPECTED_ARTIFACT_SHA256__',
			'expected_artifact_size'   => __C99_EXPECTED_ARTIFACT_SIZE__,
			'expected_plugin_sha256'   => '__C99_EXPECTED_PLUGIN_SHA256__',
			'expected_version'         => '__C99_EXPECTED_VERSION__',
			'stage_chunk_max_bytes'    => 1048576,
			'local_test'    => __C99_LOCAL_TEST__,
			'test_fault'    => '__C99_TEST_FAULT__',
			'target_host'   => '__C99_TARGET_HOST__',
			'allowed_hosts' => __C99_ALLOWED_HOSTS__,
			'recovery_lease_seconds'=> 240,
			'interrupted_forward' => array(
				'adoption_schema'                    => '__C99_INTERRUPTED_FORWARD_ADOPTION_SCHEMA__',
				'finalized_attestation_enabled'     => __C99_INTERRUPTED_FORWARD_FINALIZED_ATTESTATION__,
				'target_deployment_id'              => '__C99_INTERRUPTED_FORWARD_TARGET_DEPLOYMENT_ID__',
				'expected_artifact_sha256'          => '',
				'expected_plugin_sha256'            => '',
				'expected_version'                  => '',
				'proof_sha256'                      => '__C99_INTERRUPTED_FORWARD_PROOF_SHA256__',
				'reviewed_database_fingerprint'     => '__C99_REVIEWED_DATABASE_FINGERPRINT__',
				'reviewed_database_manifest'        => json_decode( base64_decode( '__C99_REVIEWED_DATABASE_MANIFEST_BASE64__' ), true ),
				'reviewed_database_manifest_sha256' => '__C99_REVIEWED_DATABASE_MANIFEST_SHA256__',
				'reviewed_database_storage'         => json_decode( base64_decode( '__C99_REVIEWED_DATABASE_STORAGE_BASE64__' ), true ),
				'reviewed_safe_status'              => json_decode( base64_decode( '__C99_REVIEWED_SAFE_STATUS_BASE64__' ), true ),
				'reviewed_safe_status_sha256'       => '__C99_REVIEWED_SAFE_STATUS_SHA256__',
				'prior_database_fingerprint'        => '__C99_PRIOR_DATABASE_FINGERPRINT__',
				'prior_plugin_sha256'                => '__C99_PRIOR_PLUGIN_SHA256__',
				'prior_deployment'                   => '__C99_PRIOR_DEPLOYMENT_ID__',
				'prior_version'                      => '__C99_PRIOR_VERSION__',
				'prior_robots_sha256'                => '__C99_PRIOR_ROBOTS_SHA256__',
			),
		);
		$config['interrupted_forward']['expected_artifact_sha256'] = $config['expected_artifact_sha256'];
		$config['interrupted_forward']['expected_plugin_sha256']   = $config['expected_plugin_sha256'];
		$config['interrupted_forward']['expected_version']         = $config['expected_version'];
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

		$canonicalize_json_value = null;
		$canonicalize_json_value = static function ( $value ) use ( &$canonicalize_json_value ) {
			if ( ! is_array( $value ) ) {
				return $value;
			}
			$is_list = empty( $value ) || array_keys( $value ) === range( 0, count( $value ) - 1 );
			if ( $is_list ) {
				return array_map( $canonicalize_json_value, $value );
			}
			ksort( $value, SORT_STRING );
			$canonical = array();
			foreach ( $value as $key => $entry ) {
				$canonical[ $key ] = $canonicalize_json_value( $entry );
			}
			return $canonical;
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
		$staging_root = trailingslashit( WP_CONTENT_DIR ) . '.complete99-deploy-staging';
		$staging_directory = static function ( $deployment_id ) use ( $staging_root ) {
			return trailingslashit( $staging_root ) . $deployment_id;
		};
		$staged_artifact_path = static function ( $deployment_id ) use ( $staging_directory ) {
			return trailingslashit( $staging_directory( $deployment_id ) ) . 'artifact.zip';
		};
		$staged_metadata_path = static function ( $deployment_id ) use ( $staging_directory ) {
			return trailingslashit( $staging_directory( $deployment_id ) ) . 'stage.json';
		};

		$lock_option = 'complete99_deploy_lock';
		$lock_owner  = hash_hmac( 'sha256', $config['deployment_id'], $config['token'] );
		$deployment_id_valid = static function ( $value ) {
			return is_string( $value ) && 1 === preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $value );
		};
		$core_plugin_active_persisted = static function ( $plugin_file ) {
			global $wpdb;
			$wpdb->last_error = '';
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_id,option_value FROM {$wpdb->options} WHERE option_name=%s ORDER BY option_id ASC LIMIT 2", 'active_plugins' ), ARRAY_A );
			if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) || 1 !== count( $rows ) || ! is_numeric( $rows[0]['option_id'] ?? null ) || 0 >= (int) $rows[0]['option_id'] ) { return new WP_Error( 'c99_core_active_plugins_unavailable', 'Persisted core plugin activation truth is unavailable.', array( 'status' => 503 ) ); }
			$plugins = maybe_unserialize( (string) ( $rows[0]['option_value'] ?? '' ) );
			if ( ! is_array( $plugins ) ) { return new WP_Error( 'c99_core_active_plugins_invalid', 'Persisted core plugin activation truth is malformed.', array( 'status' => 503 ) ); }
			foreach ( $plugins as $plugin ) { if ( ! is_string( $plugin ) || '' === $plugin ) { return new WP_Error( 'c99_core_active_plugins_invalid', 'Persisted core plugin activation truth is malformed.', array( 'status' => 503 ) ); } }
			return in_array( (string) $plugin_file, $plugins, true );
		};
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

		/*
		 * Serialize every deployment mutation with the complete Campaign worker,
		 * including its cron, cache, HTTP and protected-file side effects. The
		 * durable deployment option is created first; a worker that has not yet
		 * entered sees it and refuses, while this advisory fence drains a worker
		 * that began immediately before the reservation became visible.
		 */
		$lifecycle_role_protocol = 'complete99-campaign-lifecycle-reservation/v1';
		$lifecycle_role_name = 'c99_campaign_lifecycle_' . substr( hash( 'sha256', $lifecycle_role_protocol ), 0, 40 );
		$worker_fence_protocol = 'complete99-campaign-worker-fence/v1';
		$worker_fence_name = 'c99_campaign_worker_' . substr( hash( 'sha256', $worker_fence_protocol ), 0, 40 );
		$rollback_capacity_name = 'c99_campaign_slot_' . substr( hash( 'sha256', 'rollback-capacity' ), 0, 40 );
		$deploy_reservation_exists = static function () use ( $read_lock ) {
			$lock = $read_lock( true );
			return '' !== (string) ( $lock['deployment_id'] ?? '' )
				&& '' !== (string) ( $lock['phase'] ?? '' )
				&& '' !== (string) ( $lock['owner_id'] ?? '' );
		};
		$acquire_worker_fence = static function () use ( $config, $lifecycle_role_protocol, $lifecycle_role_name, $worker_fence_protocol, $worker_fence_name, $rollback_capacity_name, $deploy_reservation_exists ) {
			global $wpdb;
			$before = $deploy_reservation_exists();
			if ( is_wp_error( $before ) ) {
				return $before;
			}
			if ( true !== $before ) {
				return new WP_Error( 'c99_worker_fence_reservation_missing', 'Deployment worker exclusion requires the durable deployment reservation.', array( 'status' => 409 ) );
			}
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			$sqlite = $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) );
			if ( ! $sqlite ) {
				if ( true !== $wpdb->is_mysql ) {
					return new WP_Error( 'c99_worker_fence_driver', 'The deployment cannot serialize with the Campaign worker on this database driver.', array( 'status' => 409 ) );
				}
				$previous_suppress = $wpdb->suppress_errors( true );
				$wpdb->last_error = '';
				$lifecycle_acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lifecycle_role_name, 10 ) );
				$lifecycle_error = (string) $wpdb->last_error;
				if ( '' !== $lifecycle_error || 1 !== (int) $lifecycle_acquired ) {
					$wpdb->suppress_errors( $previous_suppress );
					return new WP_Error( 'c99_lifecycle_role_busy', 'An active Campaign lifecycle transition did not drain before deployment mutation.', array( 'status' => 423 ) );
				}
				$wpdb->last_error = '';
				$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $worker_fence_name, 10 ) );
				$acquire_error = (string) $wpdb->last_error;
				if ( '' !== $acquire_error || 1 !== (int) $acquired ) {
					$wpdb->last_error = '';
					$lifecycle_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lifecycle_role_name ) );
					$lifecycle_release_error = (string) $wpdb->last_error;
					$wpdb->suppress_errors( $previous_suppress );
					if ( '' !== $lifecycle_release_error || 1 !== (int) $lifecycle_released ) {
						return new WP_Error( 'c99_lifecycle_role_release', 'Deployment could not prove release of the Campaign lifecycle role.', array( 'status' => 500 ) );
					}
					return new WP_Error( 'c99_worker_fence_busy', 'The active Campaign worker did not drain before deployment mutation.', array( 'status' => 423 ) );
				}
				$wpdb->last_error = '';
				$capacity_acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $rollback_capacity_name, 10 ) );
				$capacity_acquire_error = (string) $wpdb->last_error;
				if ( '' !== $capacity_acquire_error || 1 !== (int) $capacity_acquired ) {
					$wpdb->last_error = '';
					$worker_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $worker_fence_name ) );
					$worker_release_error = (string) $wpdb->last_error;
					$wpdb->last_error = '';
					$lifecycle_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lifecycle_role_name ) );
					$lifecycle_release_error = (string) $wpdb->last_error;
					$wpdb->suppress_errors( $previous_suppress );
					if ( '' !== $worker_release_error || 1 !== (int) $worker_released || '' !== $lifecycle_release_error || 1 !== (int) $lifecycle_released ) {
						return new WP_Error( 'c99_lifecycle_worker_release', 'Deployment could not prove release after the Campaign rollback-capacity boundary stayed busy.', array( 'status' => 500 ) );
					}
					return new WP_Error( 'c99_rollback_capacity_busy', 'An admitted Campaign database transaction did not drain before deployment mutation.', array( 'status' => 423 ) );
				}
				$wpdb->suppress_errors( $previous_suppress );
			}
			$after = $deploy_reservation_exists();
			if ( is_wp_error( $after ) || true !== $after ) {
				if ( ! $sqlite ) {
					$previous_suppress = $wpdb->suppress_errors( true );
					$wpdb->last_error = '';
					$capacity_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $rollback_capacity_name ) );
					$capacity_release_error = (string) $wpdb->last_error;
					$wpdb->last_error = '';
					$worker_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $worker_fence_name ) );
					$worker_release_error = (string) $wpdb->last_error;
					$wpdb->last_error = '';
					$lifecycle_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lifecycle_role_name ) );
					$lifecycle_release_error = (string) $wpdb->last_error;
					$wpdb->suppress_errors( $previous_suppress );
					if ( '' !== $capacity_release_error || 1 !== (int) $capacity_released || '' !== $worker_release_error || 1 !== (int) $worker_released || '' !== $lifecycle_release_error || 1 !== (int) $lifecycle_released ) {
						return new WP_Error( 'c99_lifecycle_worker_release', 'Deployment reservation changed with uncertain Campaign lock ownership.', array( 'status' => 500 ) );
					}
				}
				return is_wp_error( $after )
					? $after
					: new WP_Error( 'c99_worker_fence_reservation_lost', 'The deployment reservation changed while acquiring worker exclusion.', array( 'status' => 409 ) );
			}
			return array(
				'acquired' => true,
				'lifecycle_protocol' => $lifecycle_role_protocol,
				'lifecycle_lock_name'=> $lifecycle_role_name,
				'protocol' => $worker_fence_protocol,
				'lock_name'=> $worker_fence_name,
				'rollback_capacity_lock_name'=> $rollback_capacity_name,
				'sqlite'   => $sqlite,
			);
		};
		$release_worker_fence = static function ( $fence ) {
			global $wpdb;
			if ( ! is_array( $fence ) || true !== ( $fence['acquired'] ?? null ) ) {
				return new WP_Error( 'c99_worker_fence_not_owned', 'Deployment worker-fence ownership is invalid.', array( 'status' => 500 ) );
			}
			if ( true === ( $fence['sqlite'] ?? null ) ) {
				return true;
			}
			$previous_suppress = $wpdb->suppress_errors( true );
			$wpdb->last_error = '';
			$capacity_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', (string) ( $fence['rollback_capacity_lock_name'] ?? '' ) ) );
			$capacity_release_error = (string) $wpdb->last_error;
			$wpdb->last_error = '';
			$released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', (string) $fence['lock_name'] ) );
			$release_error = (string) $wpdb->last_error;
			$wpdb->last_error = '';
			$lifecycle_released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', (string) ( $fence['lifecycle_lock_name'] ?? '' ) ) );
			$lifecycle_release_error = (string) $wpdb->last_error;
			$wpdb->suppress_errors( $previous_suppress );
			if ( '' !== $capacity_release_error || 1 !== (int) $capacity_released || '' !== $release_error || 1 !== (int) $released || '' !== $lifecycle_release_error || 1 !== (int) $lifecycle_released ) {
				return new WP_Error( 'c99_worker_fence_release', 'Deployment completed with uncertain Campaign worker-fence ownership.', array( 'status' => 500 ) );
			}
			return true;
		};

		$acquire_lock = static function ( $deployment_id, $phase = 'reserved' ) use ( $lock_option, $lock_owner, $read_lock, $cas_lock, $deployment_id_valid ) {
			if ( ! $deployment_id_valid( $deployment_id ) ) { return new WP_Error( 'c99_lock_deployment_id', 'The deployment reservation identity is invalid.', array( 'status' => 400 ) ); }
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

		$claim_lock = static function ( $deployment_id, $allowed_phases, $next_phase, $require_current_owner = false, $require_stale = false ) use ( $config, $lock_owner, $read_lock, $cas_lock, $deployment_id_valid ) {
			if ( ! $deployment_id_valid( $deployment_id ) ) { return new WP_Error( 'c99_lock_claim_deployment_id', 'The deployment reservation identity is invalid.', array( 'status' => 400 ) ); }
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

		$validate_embedded_artifact_identity = static function () use ( $config ) {
			if (
				! preg_match( '/^[a-f0-9]{64}$/', (string) $config['expected_artifact_sha256'] )
				|| ! is_int( $config['expected_artifact_size'] )
				|| 0 >= $config['expected_artifact_size']
				|| $config['expected_artifact_size'] > (int) $config['max_bytes']
				|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $config['expected_plugin_sha256'] )
				|| ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', (string) $config['expected_version'] )
			) {
				return new WP_Error( 'c99_stage_embedded_identity', 'The bridge does not contain one complete immutable release identity.', array( 'status' => 409 ) );
			}
			return true;
		};

		$protect_staging_directory = static function ( $deployment_id ) use ( $config, $staging_root, $staging_directory ) {
			global $wp_filesystem;
			if (
				$config['deployment_id'] !== $deployment_id
				|| ! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $deployment_id )
			) {
				return new WP_Error( 'c99_stage_path_identity', 'The artifact staging identity is invalid.', array( 'status' => 400 ) );
			}
			if ( is_link( $staging_root ) || ( file_exists( $staging_root ) && ! is_dir( $staging_root ) ) ) {
				return new WP_Error( 'c99_stage_root_unsafe', 'The artifact staging root is unsafe.', array( 'status' => 409 ) );
			}
			if ( ! is_dir( $staging_root ) && ! $wp_filesystem->mkdir( $staging_root, FS_CHMOD_DIR ) ) {
				return new WP_Error( 'c99_stage_root_create', 'The artifact staging root could not be created.', array( 'status' => 500 ) );
			}
			if ( is_link( $staging_root ) || ! is_dir( $staging_root ) ) {
				return new WP_Error( 'c99_stage_root_readback', 'The artifact staging root failed safety readback.', array( 'status' => 409 ) );
			}
			$guard_files = array(
				'index.php'  => "<?php\n// Silence is golden.\n",
				'.htaccess'  => "Require all denied\nDeny from all\n",
				'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
			);
			foreach ( $guard_files as $guard_name => $guard_contents ) {
				$guard_path = trailingslashit( $staging_root ) . $guard_name;
				if (
					is_link( $guard_path )
					|| is_dir( $guard_path )
					|| ! $wp_filesystem->put_contents( $guard_path, $guard_contents, FS_CHMOD_FILE )
				) {
					return new WP_Error( 'c99_stage_guard', 'The artifact staging root could not be protected.', array( 'status' => 500 ) );
				}
				$guard_readback = $wp_filesystem->get_contents( $guard_path );
				if ( ! is_string( $guard_readback ) || ! hash_equals( hash( 'sha256', $guard_contents ), hash( 'sha256', $guard_readback ) ) ) {
					return new WP_Error( 'c99_stage_guard_readback', 'An artifact staging guard failed readback.', array( 'status' => 500 ) );
				}
			}

			$stage_dir = $staging_directory( $deployment_id );
			if ( is_link( $stage_dir ) || ( file_exists( $stage_dir ) && ! is_dir( $stage_dir ) ) ) {
				return new WP_Error( 'c99_stage_directory_unsafe', 'The isolated artifact staging directory is unsafe.', array( 'status' => 409 ) );
			}
			if ( ! is_dir( $stage_dir ) && ! $wp_filesystem->mkdir( $stage_dir, FS_CHMOD_DIR ) ) {
				return new WP_Error( 'c99_stage_directory_create', 'The isolated artifact staging directory could not be created.', array( 'status' => 500 ) );
			}
			$resolved_root = realpath( $staging_root );
			$resolved_dir  = realpath( $stage_dir );
			if (
				false === $resolved_root
				|| false === $resolved_dir
				|| is_link( $stage_dir )
				|| wp_normalize_path( dirname( $resolved_dir ) ) !== wp_normalize_path( $resolved_root )
			) {
				return new WP_Error( 'c99_stage_directory_readback', 'The isolated artifact staging directory failed safety readback.', array( 'status' => 409 ) );
			}
			return $stage_dir;
		};

		$cleanup_staging = static function ( $deployment_id ) use ( $config, $staging_root, $staging_directory ) {
			if ( $config['deployment_id'] !== $deployment_id || ! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $deployment_id ) ) {
				return new WP_Error( 'c99_stage_cleanup_identity', 'The artifact staging cleanup identity is invalid.', array( 'status' => 400 ) );
			}
			if ( is_link( $staging_root ) || ( file_exists( $staging_root ) && ! is_dir( $staging_root ) ) ) {
				return new WP_Error( 'c99_stage_cleanup_root', 'The artifact staging root is unsafe for cleanup.', array( 'status' => 409 ) );
			}
			$stage_dir = $staging_directory( $deployment_id );
			if ( is_link( $stage_dir ) ) {
				return new WP_Error( 'c99_stage_cleanup_directory_link', 'The isolated artifact staging directory is a symbolic link.', array( 'status' => 409 ) );
			}
			if ( ! file_exists( $stage_dir ) ) {
				return true;
			}
			if ( ! is_dir( $stage_dir ) ) {
				return new WP_Error( 'c99_stage_cleanup_directory', 'The isolated artifact staging path is not a directory.', array( 'status' => 409 ) );
			}
			$resolved_root = realpath( $staging_root );
			$resolved_dir  = realpath( $stage_dir );
			if (
				false === $resolved_root
				|| false === $resolved_dir
				|| wp_normalize_path( dirname( $resolved_dir ) ) !== wp_normalize_path( $resolved_root )
			) {
				return new WP_Error( 'c99_stage_cleanup_path', 'The isolated artifact staging path failed safety validation.', array( 'status' => 409 ) );
			}
			$entries = @scandir( $stage_dir );
			if ( ! is_array( $entries ) ) {
				return new WP_Error( 'c99_stage_cleanup_scan', 'The isolated artifact staging directory could not be inspected.', array( 'status' => 500 ) );
			}
			foreach ( $entries as $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					continue;
				}
				if (
					'artifact.zip' !== $entry
					&& 'stage.json' !== $entry
					&& ! preg_match( '/^stage\.json\.tmp-[a-f0-9]{16}$/', $entry )
				) {
					return new WP_Error( 'c99_stage_cleanup_unexpected', 'The isolated artifact staging directory contains an unexpected entry.', array( 'status' => 409 ) );
				}
				$entry_path = trailingslashit( $stage_dir ) . $entry;
				if ( is_link( $entry_path ) || is_dir( $entry_path ) || ! is_file( $entry_path ) ) {
					return new WP_Error( 'c99_stage_cleanup_entry', 'The isolated artifact staging directory contains an unsafe entry.', array( 'status' => 409 ) );
				}
				if ( ! @unlink( $entry_path ) ) {
					return new WP_Error( 'c99_stage_cleanup_unlink', 'An artifact staging residue file could not be removed.', array( 'status' => 500 ) );
				}
			}
			if ( ! @rmdir( $stage_dir ) ) {
				return new WP_Error( 'c99_stage_cleanup_rmdir', 'The isolated artifact staging directory could not be removed.', array( 'status' => 500 ) );
			}
			return true;
		};

		$read_stage_metadata = static function ( $deployment_id ) use ( $config, $staged_metadata_path ) {
			$metadata_path = $staged_metadata_path( $deployment_id );
			if ( is_link( $metadata_path ) || is_dir( $metadata_path ) || ! is_file( $metadata_path ) ) {
				return new WP_Error( 'c99_stage_metadata_missing', 'Completed artifact staging metadata was not found.', array( 'status' => 409 ) );
			}
			$metadata_size = @filesize( $metadata_path );
			if ( false === $metadata_size || 0 >= $metadata_size || 16384 < $metadata_size ) {
				return new WP_Error( 'c99_stage_metadata_size', 'Artifact staging metadata has an invalid size.', array( 'status' => 409 ) );
			}
			$contents = @file_get_contents( $metadata_path );
			$metadata = is_string( $contents ) ? json_decode( $contents, true ) : null;
			$expected_keys = array(
				'artifact_sha256',
				'complete',
				'deployment_id',
				'expected_artifact_sha256',
				'expected_artifact_size',
				'last_final',
				'last_offset',
				'last_sha256',
				'last_size',
				'received_bytes',
				'schema',
				'updated_at',
			);
			$actual_keys = is_array( $metadata ) ? array_keys( $metadata ) : array();
			sort( $actual_keys, SORT_STRING );
			if (
				$expected_keys !== $actual_keys
				|| 'complete99-artifact-stage/v1' !== (string) ( $metadata['schema'] ?? '' )
				|| $config['deployment_id'] !== (string) ( $metadata['deployment_id'] ?? '' )
				|| ! hash_equals( (string) $config['expected_artifact_sha256'], (string) ( $metadata['expected_artifact_sha256'] ?? '' ) )
				|| (int) $config['expected_artifact_size'] !== ( $metadata['expected_artifact_size'] ?? null )
				|| ! is_int( $metadata['received_bytes'] ?? null )
				|| ! is_bool( $metadata['complete'] ?? null )
				|| ! is_int( $metadata['last_offset'] ?? null )
				|| ! is_int( $metadata['last_size'] ?? null )
				|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $metadata['last_sha256'] ?? '' ) )
				|| ! is_bool( $metadata['last_final'] ?? null )
				|| ! is_int( $metadata['updated_at'] ?? null )
				|| 0 >= (int) ( $metadata['received_bytes'] ?? 0 )
				|| (int) ( $metadata['received_bytes'] ?? 0 ) > (int) $config['expected_artifact_size']
				|| 0 > (int) ( $metadata['last_offset'] ?? -1 )
				|| 0 >= (int) ( $metadata['last_size'] ?? 0 )
				|| (int) ( $metadata['last_size'] ?? 0 ) > (int) $config['stage_chunk_max_bytes']
				|| (int) ( $metadata['last_offset'] ?? -1 ) + (int) ( $metadata['last_size'] ?? 0 ) !== (int) ( $metadata['received_bytes'] ?? -1 )
				|| (bool) ( $metadata['complete'] ?? false ) !== ( (int) ( $metadata['received_bytes'] ?? 0 ) === (int) $config['expected_artifact_size'] )
				|| (bool) ( $metadata['last_final'] ?? false ) !== (bool) ( $metadata['complete'] ?? false )
				|| ( ! empty( $metadata['complete'] ) && ! hash_equals( (string) $config['expected_artifact_sha256'], (string) ( $metadata['artifact_sha256'] ?? '' ) ) )
				|| ( empty( $metadata['complete'] ) && '' !== (string) ( $metadata['artifact_sha256'] ?? '' ) )
			) {
				return new WP_Error( 'c99_stage_metadata_invalid', 'Artifact staging metadata failed immutable identity validation.', array( 'status' => 409 ) );
			}
			return $metadata;
		};

		$inspect_staged_artifact = static function ( $deployment_id ) use ( $config, $validate_embedded_artifact_identity, $staging_directory, $staged_artifact_path, $read_stage_metadata ) {
			$identity = $validate_embedded_artifact_identity();
			if ( is_wp_error( $identity ) ) {
				return $identity;
			}
			$metadata = $read_stage_metadata( $deployment_id );
			if ( is_wp_error( $metadata ) ) {
				return $metadata;
			}
			if (
				true !== $metadata['complete']
				|| (int) $config['expected_artifact_size'] !== $metadata['received_bytes']
				|| ! hash_equals( (string) $config['expected_artifact_sha256'], (string) $metadata['artifact_sha256'] )
			) {
				return new WP_Error( 'c99_stage_incomplete', 'The immutable release artifact has not completed staging.', array( 'status' => 409 ) );
			}
			$stage_dir    = $staging_directory( $deployment_id );
			$artifact_path = $staged_artifact_path( $deployment_id );
			if ( is_link( $stage_dir ) || is_link( $artifact_path ) || is_dir( $artifact_path ) || ! is_file( $artifact_path ) ) {
				return new WP_Error( 'c99_stage_artifact_path', 'The staged release artifact path is unsafe.', array( 'status' => 409 ) );
			}
			$resolved_dir      = realpath( $stage_dir );
			$resolved_artifact = realpath( $artifact_path );
			if (
				false === $resolved_dir
				|| false === $resolved_artifact
				|| wp_normalize_path( dirname( $resolved_artifact ) ) !== wp_normalize_path( $resolved_dir )
			) {
				return new WP_Error( 'c99_stage_artifact_escape', 'The staged release artifact escaped its isolated directory.', array( 'status' => 409 ) );
			}
			$before = @lstat( $artifact_path );
			$size   = @filesize( $artifact_path );
			$sha256 = @hash_file( 'sha256', $artifact_path );
			clearstatcache( true, $artifact_path );
			$after = @lstat( $artifact_path );
			if (
				! is_array( $before )
				|| ! is_array( $after )
				|| false === $size
				|| (int) $config['expected_artifact_size'] !== (int) $size
				|| false === $sha256
				|| ! hash_equals( (string) $config['expected_artifact_sha256'], $sha256 )
				|| (int) ( $before['size'] ?? -1 ) !== (int) ( $after['size'] ?? -2 )
				|| (int) ( $before['ino'] ?? -1 ) !== (int) ( $after['ino'] ?? -2 )
				|| (int) ( $before['dev'] ?? -1 ) !== (int) ( $after['dev'] ?? -2 )
			) {
				return new WP_Error( 'c99_stage_artifact_integrity', 'The staged release artifact failed exact size or digest validation.', array( 'status' => 422 ) );
			}
			return array(
				'path'     => $artifact_path,
				'size'     => (int) $size,
				'sha256'   => $sha256,
				'metadata' => $metadata,
			);
		};

		$validate_staged_archive = static function ( $artifact_path ) use ( $config ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return new WP_Error( 'c99_stage_zip_support', 'ZIP archive validation is unavailable.', array( 'status' => 500 ) );
			}
			$archive = new \ZipArchive();
			$opened  = $archive->open( $artifact_path, \ZipArchive::CHECKCONS );
			if ( true !== $opened ) {
				return new WP_Error( 'c99_stage_zip_open', 'The staged release artifact is not a valid ZIP archive.', array( 'status' => 422 ) );
			}
			$entry_count = (int) $archive->numFiles;
			if ( 0 >= $entry_count || 20000 < $entry_count ) {
				$archive->close();
				return new WP_Error( 'c99_stage_zip_entries', 'The staged release archive has an invalid entry count.', array( 'status' => 422 ) );
			}
			$seen = array();
			$total_uncompressed = 0;
			$plugin_main_found  = false;
			for ( $index = 0; $index < $entry_count; $index++ ) {
				$stat = $archive->statIndex( $index, \ZipArchive::FL_UNCHANGED );
				$name = is_array( $stat ) ? (string) ( $stat['name'] ?? '' ) : '';
				$segments = explode( '/', rtrim( $name, '/' ) );
				$canonical_name = strtolower( $name );
				if (
					'' === $name
					|| 1024 < strlen( $name )
					|| str_contains( $name, "\0" )
					|| str_contains( $name, '\\' )
					|| str_starts_with( $name, '/' )
					|| preg_match( '/^[A-Za-z]:/', $name )
					|| in_array( '', $segments, true )
					|| in_array( '.', $segments, true )
					|| in_array( '..', $segments, true )
					|| ( $config['slug'] !== rtrim( $name, '/' ) && ! str_starts_with( $name, $config['slug'] . '/' ) )
					|| isset( $seen[ $canonical_name ] )
				) {
					$archive->close();
					return new WP_Error( 'c99_stage_zip_path', 'The staged release archive contains an unsafe or duplicate path.', array( 'status' => 422 ) );
				}
				$seen[ $canonical_name ] = true;
				$operating_system = 0;
				$attributes       = 0;
				if ( $archive->getExternalAttributesIndex( $index, $operating_system, $attributes, \ZipArchive::FL_UNCHANGED ) ) {
					$file_type = ( $attributes >> 16 ) & 0170000;
					if ( 0120000 === $file_type || ( 0 !== $file_type && 0100000 !== $file_type && 0040000 !== $file_type ) ) {
						$archive->close();
						return new WP_Error( 'c99_stage_zip_link', 'The staged release archive contains a link or special filesystem entry.', array( 'status' => 422 ) );
					}
				}
				$entry_size = (int) ( $stat['size'] ?? -1 );
				if ( 0 > $entry_size ) {
					$archive->close();
					return new WP_Error( 'c99_stage_zip_entry_size', 'The staged release archive contains an invalid entry size.', array( 'status' => 422 ) );
				}
				$total_uncompressed += $entry_size;
				if ( $total_uncompressed > (int) $config['max_bytes'] * 32 ) {
					$archive->close();
					return new WP_Error( 'c99_stage_zip_expansion', 'The staged release archive exceeds the bounded extraction ceiling.', array( 'status' => 422 ) );
				}
				$plugin_main_found = $plugin_main_found || $config['plugin_file'] === $name;
			}
			$archive->close();
			if ( ! $plugin_main_found ) {
				return new WP_Error( 'c99_stage_zip_plugin_main', 'The staged release archive does not contain the allowlisted plugin entry point.', array( 'status' => 422 ) );
			}
			return true;
		};

		$consume_staged_artifact = static function ( $deployment_id, $destination ) use ( $config, $inspect_staged_artifact, $cleanup_staging ) {
			$staged = $inspect_staged_artifact( $deployment_id );
			if ( is_wp_error( $staged ) ) {
				return $staged;
			}
			$temp_root       = strtolower( trailingslashit( wp_normalize_path( get_temp_dir() ) ) );
			$normalized_temp = strtolower( wp_normalize_path( $destination ) );
			if (
				'' === $destination
				|| ! str_starts_with( $normalized_temp, $temp_root )
				|| ! str_ends_with( $normalized_temp, '.zip' )
				|| is_link( $destination )
				|| file_exists( $destination )
			) {
				return new WP_Error( 'c99_stage_consume_destination', 'The staged release destination is unsafe.', array( 'status' => 409 ) );
			}
			$source = (string) $staged['path'];
			$moved  = @rename( $source, $destination );
			$copied = false;
			if ( ! $moved ) {
				$copied = @copy( $source, $destination );
				if ( ! $copied ) {
					@unlink( $destination );
					return new WP_Error( 'c99_stage_consume_copy', 'The staged release artifact could not be moved to the installer.', array( 'status' => 500 ) );
				}
			}
			@chmod( $destination, FS_CHMOD_FILE );
			clearstatcache( true, $destination );
			$destination_size = @filesize( $destination );
			$destination_sha  = @hash_file( 'sha256', $destination );
			if (
				is_link( $destination )
				|| is_dir( $destination )
				|| false === $destination_size
				|| (int) $config['expected_artifact_size'] !== (int) $destination_size
				|| false === $destination_sha
				|| ! hash_equals( (string) $config['expected_artifact_sha256'], $destination_sha )
			) {
				@unlink( $destination );
				return new WP_Error( 'c99_stage_consume_integrity', 'The installer copy of the staged release artifact failed integrity validation.', array( 'status' => 422 ) );
			}
			if ( $copied && ( ! @unlink( $source ) || file_exists( $source ) || is_link( $source ) ) ) {
				@unlink( $destination );
				return new WP_Error( 'c99_stage_consume_source_cleanup', 'The verified staging source could not be consumed exactly once.', array( 'status' => 500 ) );
			}
			$cleaned = $cleanup_staging( $deployment_id );
			if ( is_wp_error( $cleaned ) ) {
				@unlink( $destination );
				return $cleaned;
			}
			return array(
				'path'   => $destination,
				'size'   => (int) $destination_size,
				'sha256' => $destination_sha,
			);
		};

		$protect_recovery_evidence_root = static function ( $root ) {
			global $wp_filesystem;
			if ( is_link( $root ) ) {
				return new WP_Error( 'c99_orphaned_receipt_root_symlink', 'The orphaned rollback evidence root is unsafe.', array( 'status' => 409 ) );
			}
			if ( ! $wp_filesystem->is_dir( $root ) && ! $wp_filesystem->mkdir( $root, FS_CHMOD_DIR ) ) {
				return new WP_Error( 'c99_orphaned_receipt_root', 'The orphaned rollback evidence root could not be created.', array( 'status' => 500 ) );
			}
			if ( is_link( $root ) || ! $wp_filesystem->is_dir( $root ) ) {
				return new WP_Error( 'c99_orphaned_receipt_root_unsafe', 'The orphaned rollback evidence root is unsafe.', array( 'status' => 409 ) );
			}
			$guard_files = array(
				'index.php'  => "<?php\n// Silence is golden.\n",
				'.htaccess'  => "Require all denied\nDeny from all\n",
				'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
			);
			foreach ( $guard_files as $guard_name => $guard_contents ) {
				$guard_path = trailingslashit( $root ) . $guard_name;
				if (
					is_link( $guard_path )
					|| is_dir( $guard_path )
					|| ! $wp_filesystem->put_contents( $guard_path, $guard_contents, FS_CHMOD_FILE )
				) {
					return new WP_Error( 'c99_orphaned_receipt_guard', 'The orphaned rollback evidence root could not be protected.', array( 'status' => 500 ) );
				}
				$guard_readback = $wp_filesystem->get_contents( $guard_path );
				if ( ! is_string( $guard_readback ) || ! hash_equals( hash( 'sha256', $guard_contents ), hash( 'sha256', $guard_readback ) ) ) {
					return new WP_Error( 'c99_orphaned_receipt_guard_readback', 'The orphaned rollback evidence guard failed readback.', array( 'status' => 500 ) );
				}
			}
			return array(
				'protected' => true,
				'guards'    => array_keys( $guard_files ),
			);
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

		$managed_robots_contents = static function () {
			return "User-agent: *\n"
				. "Disallow: /wp-admin/\n"
				. "Allow: /wp-admin/admin-ajax.php\n"
				. 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
		};

		$managed_robots_path = static function () {
			$root = realpath( ABSPATH );
			if ( false === $root || ! is_dir( $root ) ) {
				return new WP_Error( 'c99_robots_root', 'The WordPress document root is unavailable.', array( 'status' => 500 ) );
			}
			$path = rtrim( $root, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'robots.txt';
			if ( dirname( $path ) !== rtrim( $root, DIRECTORY_SEPARATOR ) ) {
				return new WP_Error( 'c99_robots_path', 'The managed robots path is unsafe.', array( 'status' => 500 ) );
			}
			return $path;
		};

		$capture_robots_snapshot = static function () use ( $managed_robots_path ) {
			$path = $managed_robots_path();
			if ( is_wp_error( $path ) ) {
				return $path;
			}
			if ( is_link( $path ) || is_dir( $path ) ) {
				return new WP_Error( 'c99_robots_unsafe', 'The existing robots target is not a regular file.', array( 'status' => 409 ) );
			}
			if ( ! file_exists( $path ) ) {
				return array(
					'robots_prior_exists' => false,
					'robots_prior_sha256'=> '',
					'robots_prior_base64'=> '',
				);
			}
			$size = @filesize( $path );
			if ( false === $size || $size > 65536 ) {
				return new WP_Error( 'c99_robots_size', 'The existing robots file cannot be journaled safely.', array( 'status' => 409 ) );
			}
			$contents = @file_get_contents( $path );
			if ( false === $contents ) {
				return new WP_Error( 'c99_robots_read', 'The existing robots file cannot be read.', array( 'status' => 500 ) );
			}
			return array(
				'robots_prior_exists' => true,
				'robots_prior_sha256'=> hash( 'sha256', $contents ),
				'robots_prior_base64'=> base64_encode( $contents ),
			);
		};

		$apply_managed_robots = static function ( $state_dir, $state ) use ( $managed_robots_contents, $managed_robots_path ) {
			$path = $managed_robots_path();
			if ( is_wp_error( $path ) ) {
				return $path;
			}
			if ( is_link( $path ) || is_dir( $path ) ) {
				return new WP_Error( 'c99_robots_unsafe', 'The robots target became unsafe during deployment.', array( 'status' => 409 ) );
			}
			$managed        = $managed_robots_contents();
			$managed_digest = hash( 'sha256', $managed );
			$current_exists = file_exists( $path );
			$current_digest = $current_exists ? @hash_file( 'sha256', $path ) : '';
			$prior_exists = ! empty( $state['robots_prior_exists'] );
			$prior_digest = (string) ( $state['robots_prior_sha256'] ?? '' );
			if (
				$prior_exists !== $current_exists
				|| ( ! $prior_exists && '' !== $prior_digest )
				|| ( $prior_exists && ( ! preg_match( '/^[a-f0-9]{64}$/', $prior_digest ) || ! hash_equals( $prior_digest, (string) $current_digest ) ) )
			) {
				return new WP_Error( 'c99_robots_conflict', 'The robots file changed after the rollback journal was captured.', array( 'status' => 409 ) );
			}
			if ( $current_exists && hash_equals( $managed_digest, (string) $current_digest ) ) {
				return array( 'sha256' => $managed_digest, 'already_applied' => true );
			}
			try {
				$suffix = bin2hex( random_bytes( 8 ) );
			} catch ( \Throwable $error ) {
				return new WP_Error( 'c99_robots_random', 'The managed robots file could not be staged safely.', array( 'status' => 500 ) );
			}
			$temp = dirname( $path ) . DIRECTORY_SEPARATOR . '.complete99-robots-' . $suffix . '.tmp';
			$written = @file_put_contents( $temp, $managed, LOCK_EX );
			if ( strlen( $managed ) !== $written || ! @chmod( $temp, FS_CHMOD_FILE ) ) {
				@unlink( $temp );
				return new WP_Error( 'c99_robots_stage', 'The managed robots file could not be staged.', array( 'status' => 500 ) );
			}
			$prior_live = trailingslashit( $state_dir ) . 'robots.prior-live';
			if ( $prior_exists && ! @rename( $path, $prior_live ) ) {
				@unlink( $temp );
				return new WP_Error( 'c99_robots_preserve', 'The prior robots file could not be preserved.', array( 'status' => 500 ) );
			}
			if ( ! @rename( $temp, $path ) ) {
				@unlink( $temp );
				if ( $prior_exists && file_exists( $prior_live ) && ! file_exists( $path ) ) {
					@rename( $prior_live, $path );
				}
				return new WP_Error( 'c99_robots_commit', 'The managed robots file could not be committed atomically.', array( 'status' => 500 ) );
			}
			clearstatcache( true, $path );
			$readback = @hash_file( 'sha256', $path );
			if ( false === $readback || ! hash_equals( $managed_digest, $readback ) ) {
				@unlink( $path );
				if ( $prior_exists && file_exists( $prior_live ) ) {
					@rename( $prior_live, $path );
				}
				return new WP_Error( 'c99_robots_readback', 'The managed robots file failed integrity validation.', array( 'status' => 500 ) );
			}
			return array( 'sha256' => $managed_digest, 'already_applied' => false );
		};

		$restore_managed_robots = static function ( $state_dir, $state ) use ( $managed_robots_path ) {
			$path = $managed_robots_path();
			if ( is_wp_error( $path ) ) {
				return $path;
			}
			$prior_exists  = ! empty( $state['robots_prior_exists'] );
			$prior_digest  = (string) ( $state['robots_prior_sha256'] ?? '' );
			$managed_digest= (string) ( $state['robots_managed_sha256'] ?? '' );
			$current_exists= file_exists( $path );
			$current_digest= $current_exists ? @hash_file( 'sha256', $path ) : '';
			if (
				! preg_match( '/^[a-f0-9]{64}$/', $managed_digest )
				|| ( $prior_exists && ! preg_match( '/^[a-f0-9]{64}$/', $prior_digest ) )
				|| ( ! $prior_exists && '' !== $prior_digest )
			) {
				return new WP_Error( 'c99_robots_rollback_journal', 'The robots rollback journal is invalid.', array( 'status' => 500 ) );
			}
			if (
				( $prior_exists && $current_exists && hash_equals( $prior_digest, (string) $current_digest ) )
				|| ( ! $prior_exists && ! $current_exists )
			) {
				return array( 'restored' => true, 'already_restored' => true );
			}
			if (
				! $current_exists
				|| ! hash_equals( $managed_digest, (string) $current_digest )
			) {
				return new WP_Error( 'c99_robots_rollback_conflict', 'Rollback refused because the managed robots file changed.', array( 'status' => 409 ) );
			}
			$forward = trailingslashit( $state_dir ) . 'robots.forward';
			if ( file_exists( $forward ) ) {
				$forward_digest = @hash_file( 'sha256', $forward );
				if ( false === $forward_digest || ! hash_equals( $managed_digest, $forward_digest ) || ! @unlink( $path ) ) {
					return new WP_Error( 'c99_robots_forward_invalid', 'The forward robots file could not be preserved.', array( 'status' => 500 ) );
				}
			} elseif ( ! @rename( $path, $forward ) ) {
				return new WP_Error( 'c99_robots_forward_preserve', 'The forward robots file could not be preserved.', array( 'status' => 500 ) );
			}
			if ( ! $prior_exists ) {
				return array( 'restored' => true, 'already_restored' => false );
			}
			$prior = base64_decode( (string) ( $state['robots_prior_base64'] ?? '' ), true );
			if ( false === $prior || ! hash_equals( $prior_digest, hash( 'sha256', $prior ) ) ) {
				@rename( $forward, $path );
				return new WP_Error( 'c99_robots_prior_invalid', 'The prior robots journal failed integrity validation.', array( 'status' => 500 ) );
			}
			try {
				$suffix = bin2hex( random_bytes( 8 ) );
			} catch ( \Throwable $error ) {
				@rename( $forward, $path );
				return new WP_Error( 'c99_robots_restore_random', 'The prior robots file could not be staged safely.', array( 'status' => 500 ) );
			}
			$temp = dirname( $path ) . DIRECTORY_SEPARATOR . '.complete99-robots-restore-' . $suffix . '.tmp';
			$written = @file_put_contents( $temp, $prior, LOCK_EX );
			if ( strlen( $prior ) !== $written || ! @chmod( $temp, FS_CHMOD_FILE ) || ! @rename( $temp, $path ) ) {
				@unlink( $temp );
				@rename( $forward, $path );
				return new WP_Error( 'c99_robots_restore', 'The prior robots file could not be restored atomically.', array( 'status' => 500 ) );
			}
			$readback = @hash_file( 'sha256', $path );
			if ( false === $readback || ! hash_equals( $prior_digest, $readback ) ) {
				@unlink( $path );
				@rename( $forward, $path );
				return new WP_Error( 'c99_robots_restore_readback', 'The restored robots file failed integrity validation.', array( 'status' => 500 ) );
			}
			return array( 'restored' => true, 'already_restored' => false );
		};

		$reapply_managed_robots = static function ( $state_dir, $state ) use ( $managed_robots_path ) {
			$path = $managed_robots_path();
			if ( is_wp_error( $path ) ) {
				return $path;
			}
			$managed_digest = (string) ( $state['robots_managed_sha256'] ?? '' );
			if ( ! preg_match( '/^[a-f0-9]{64}$/', $managed_digest ) ) {
				return new WP_Error( 'c99_robots_compensation_journal', 'The forward robots journal is invalid.', array( 'status' => 500 ) );
			}
			$current_digest = file_exists( $path ) ? @hash_file( 'sha256', $path ) : '';
			if ( $current_digest && hash_equals( $managed_digest, (string) $current_digest ) ) {
				return true;
			}
			$forward = trailingslashit( $state_dir ) . 'robots.forward';
			if ( ! file_exists( $forward ) || ! hash_equals( $managed_digest, (string) @hash_file( 'sha256', $forward ) ) ) {
				return new WP_Error( 'c99_robots_compensation_source', 'The forward robots file is unavailable for compensation.', array( 'status' => 500 ) );
			}
			if ( file_exists( $path ) ) {
				$rollback_prior = trailingslashit( $state_dir ) . 'robots.rollback-prior';
				if ( file_exists( $rollback_prior ) ) {
					if ( ! @unlink( $path ) ) {
						return new WP_Error( 'c99_robots_compensation_displace', 'The prior robots file could not be displaced.', array( 'status' => 500 ) );
					}
				} elseif ( ! @rename( $path, $rollback_prior ) ) {
					return new WP_Error( 'c99_robots_compensation_displace', 'The prior robots file could not be displaced.', array( 'status' => 500 ) );
				}
			}
			if ( ! @rename( $forward, $path ) ) {
				return new WP_Error( 'c99_robots_compensation_restore', 'The forward robots file could not be restored.', array( 'status' => 500 ) );
			}
			$readback = @hash_file( 'sha256', $path );
			return $readback && hash_equals( $managed_digest, $readback )
				? true
				: new WP_Error( 'c99_robots_compensation_readback', 'The compensated robots file failed integrity validation.', array( 'status' => 500 ) );
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

		$database_identifier = static function ( $identifier ) {
			$identifier = (string) $identifier;
			if ( '' === $identifier || 64 < strlen( $identifier ) || ! preg_match( '/^[A-Za-z0-9_]+$/', $identifier ) ) {
				return new WP_Error( 'c99_ops_table_identifier', 'An operations table identifier is invalid.', array( 'status' => 500 ) );
			}
			return '`' . $identifier . '`';
		};

		$ops_table_names = static function () {
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
		};

		$campaign_table_names = static function () {
			global $wpdb;
			return array(
				'campaigns'         => $wpdb->prefix . 'c99_campaigns',
				'revisions'         => $wpdb->prefix . 'c99_campaign_revisions',
				'prepared_packages' => $wpdb->prefix . 'c99_campaign_packages',
				'provider_receipts' => $wpdb->prefix . 'c99_campaign_provider_receipts',
				'results'           => $wpdb->prefix . 'c99_campaign_results',
				'placements'        => $wpdb->prefix . 'c99_campaign_placements',
				'event_aggregates'  => $wpdb->prefix . 'c99_campaign_event_aggregates',
			);
		};

		$ops_absent_snapshot = static function () use ( $ops_table_names ) {
			$snapshot = array();
			foreach ( $ops_table_names() as $key => $unused_table ) {
				$snapshot[ $key ] = array(
					'exists'        => false,
					'engine'        => '',
					'schema_sha256' => '',
					'rows'          => array(),
				);
			}
			return $snapshot;
		};

		$campaign_absent_snapshot = static function () use ( $campaign_table_names ) {
			$snapshot = array();
			foreach ( $campaign_table_names() as $key => $unused_table ) {
				$snapshot[ $key ] = array(
					'exists'        => false,
					'engine'        => '',
					'schema_sha256' => '',
					'rows'          => array(),
				);
			}
			return $snapshot;
		};

		$ops_quarantine_names = static function ( $deployment_id ) use ( $ops_table_names ) {
			global $wpdb;
			$canonical = $ops_table_names();
			$suffix    = substr( hash( 'sha256', (string) $deployment_id . '|' . (string) $wpdb->prefix ), 0, 20 );
			$base      = (string) $wpdb->prefix . 'c99rb_' . $suffix . '_';
			if ( 61 < strlen( $base ) ) {
				$base = 'c99rb_' . substr( hash( 'sha256', (string) $wpdb->prefix ), 0, 12 ) . '_' . $suffix . '_';
			}
			$tables = array();
			$index  = 0;
			foreach ( array_keys( $canonical ) as $key ) {
				$tables[ $key ] = $base . $index;
				++$index;
			}
			return $tables;
		};

		$campaign_quarantine_names = static function ( $deployment_id ) use ( $campaign_table_names ) {
			global $wpdb;
			$canonical = $campaign_table_names();
			$suffix    = substr( hash( 'sha256', (string) $deployment_id . '|' . (string) $wpdb->prefix ), 0, 20 );
			$base      = (string) $wpdb->prefix . 'c99rb_' . $suffix . '_';
			if ( 61 < strlen( $base ) ) {
				$base = 'c99rb_' . substr( hash( 'sha256', (string) $wpdb->prefix ), 0, 12 ) . '_' . $suffix . '_';
			}
			$tables = array();
			$index  = 7;
			foreach ( array_keys( $canonical ) as $key ) {
				$tables[ $key ] = $base . $index;
				++$index;
			}
			return $tables;
		};

		$ops_quarantine_residue = static function () {
			global $wpdb;
			$prefixes = array(
				(string) $wpdb->prefix . 'c99rb_',
				'c99rb_' . substr( hash( 'sha256', (string) $wpdb->prefix ), 0, 12 ) . '_',
			);
			$tables = array();
			foreach ( array_unique( $prefixes ) as $prefix ) {
				$like = method_exists( $wpdb, 'esc_like' ) ? $wpdb->esc_like( $prefix ) . '%' : addcslashes( $prefix, '_%\\' ) . '%';
				$wpdb->last_error = '';
				$matches = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
				if ( '' !== (string) $wpdb->last_error || ! is_array( $matches ) ) {
					return new WP_Error( 'c99_ops_residue_probe', 'The operations rollback residue could not be inspected safely.', array( 'status' => 500 ) );
				}
				$tables = array_merge( $tables, $matches );
			}
			$tables = array_values( array_unique( $tables ) );
			if ( 70 < count( $tables ) ) {
				return new WP_Error( 'c99_ops_residue_probe', 'The operations rollback residue could not be inspected safely.', array( 'status' => 500 ) );
			}
			foreach ( $tables as $table ) {
				$owned_prefix = false;
				foreach ( $prefixes as $prefix ) {
					$owned_prefix = $owned_prefix || str_starts_with( (string) $table, $prefix );
				}
				if ( ! is_string( $table ) || ! $owned_prefix || 64 < strlen( $table ) || ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
					return new WP_Error( 'c99_ops_residue_scope', 'An operations rollback residue table is outside the reserved namespace.', array( 'status' => 500 ) );
				}
			}
			sort( $tables, SORT_STRING );
			return array_values( $tables );
		};

		$capture_ops_tables = static function ( $physical_tables = null ) use ( $config, $canonicalize_json_value, $database_identifier, $ops_table_names ) {
			global $wpdb;
			$canonical = $ops_table_names();
			$tables    = null === $physical_tables ? $canonical : $physical_tables;
			if ( ! is_array( $tables ) || array_keys( $tables ) !== array_keys( $canonical ) ) {
				return new WP_Error( 'c99_ops_snapshot_scope', 'The operations table snapshot scope is invalid.', array( 'status' => 500 ) );
			}
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			$is_sqlite      = $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) );
			$result         = array();
			$total_bytes    = 0;
			foreach ( $tables as $key => $table ) {
				$quoted = $database_identifier( $table );
				if ( is_wp_error( $quoted ) ) {
					return $quoted;
				}
				$like = method_exists( $wpdb, 'esc_like' ) ? $wpdb->esc_like( $table ) : addcslashes( $table, '_%\\' );
				$wpdb->last_error = '';
				$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
				if ( '' !== (string) $wpdb->last_error ) {
					return new WP_Error( 'c99_ops_snapshot_probe', 'An operations table could not be inspected.', array( 'status' => 500 ) );
				}
				if ( ! is_string( $found ) || ! hash_equals( (string) $table, $found ) ) {
					$result[ $key ] = array(
						'exists'        => false,
						'engine'        => '',
						'schema_sha256' => '',
						'rows'          => array(),
					);
					continue;
				}

				if ( $is_sqlite ) {
					$engine = 'SQLITE';
					$wpdb->last_error = '';
					$create_sql = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT sql FROM sqlite_master WHERE type = 'table' AND name = %s LIMIT 1",
							$table
						)
					);
				} else {
					$wpdb->last_error = '';
					$engine = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s LIMIT 1',
							$table
						)
					);
					$engine = strtoupper( (string) $engine );
					if ( '' !== (string) $wpdb->last_error || ! in_array( $engine, array( 'INNODB', 'XTRADB' ), true ) ) {
						return new WP_Error( 'c99_ops_snapshot_engine', 'Every existing operations table must use transactional storage.', array( 'status' => 409 ) );
					}
					$wpdb->last_error = '';
					$create_row = $wpdb->get_row( 'SHOW CREATE TABLE ' . $quoted, ARRAY_N );
					$create_sql = is_array( $create_row ) ? (string) ( $create_row[1] ?? '' ) : '';
				}
				if ( '' !== (string) $wpdb->last_error || ! is_string( $create_sql ) || '' === $create_sql ) {
					return new WP_Error( 'c99_ops_snapshot_schema', 'An operations table schema could not be inspected.', array( 'status' => 500 ) );
				}
				$normalized_schema = str_replace( (string) $table, '__complete99_ops_' . $key . '__', $create_sql );
				$wpdb->last_error = '';
				$rows = $wpdb->get_results( 'SELECT * FROM ' . $quoted . ' ORDER BY `id` ASC LIMIT 5001', ARRAY_A );
				if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) || 5000 < count( $rows ) ) {
					return new WP_Error( 'c99_ops_snapshot_rows', 'An operations table exceeds the bounded rollback snapshot.', array( 'status' => 409 ) );
				}
				$rows = $canonicalize_json_value( $rows );
				$encoded_rows = wp_json_encode( $rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( false === $encoded_rows ) {
					return new WP_Error( 'c99_ops_snapshot_encode', 'An operations table snapshot could not be encoded.', array( 'status' => 500 ) );
				}
				$total_bytes += strlen( $encoded_rows );
				if ( 8 * 1024 * 1024 < $total_bytes ) {
					return new WP_Error( 'c99_ops_snapshot_capacity', 'The operations rollback snapshot exceeds its byte ceiling.', array( 'status' => 409 ) );
				}
				$result[ $key ] = array(
					'exists'        => true,
					'engine'        => $engine,
					'schema_sha256' => hash( 'sha256', $normalized_schema ),
					'rows'          => $rows,
				);
			}
			return $result;
		};

		$capture_campaign_tables = static function ( $physical_tables = null ) use ( $config, $canonicalize_json_value, $database_identifier, $campaign_table_names ) {
			global $wpdb;
			$canonical = $campaign_table_names();
			$tables    = null === $physical_tables ? $canonical : $physical_tables;
			if ( ! is_array( $tables ) || array_keys( $tables ) !== array_keys( $canonical ) ) {
				return new WP_Error( 'c99_campaign_snapshot_scope', 'The Campaign Studio table snapshot scope is invalid.', array( 'status' => 500 ) );
			}
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			$is_sqlite      = $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) );
			$result         = array();
			$total_bytes    = 0;
			foreach ( $tables as $key => $table ) {
				$quoted = $database_identifier( $table );
				if ( is_wp_error( $quoted ) ) {
					return $quoted;
				}
				$like = method_exists( $wpdb, 'esc_like' ) ? $wpdb->esc_like( $table ) : addcslashes( $table, '_%\\' );
				$wpdb->last_error = '';
				$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
				if ( '' !== (string) $wpdb->last_error ) {
					return new WP_Error( 'c99_campaign_snapshot_probe', 'A Campaign Studio table could not be inspected.', array( 'status' => 500 ) );
				}
				if ( ! is_string( $found ) || ! hash_equals( (string) $table, $found ) ) {
					$result[ $key ] = array(
						'exists'        => false,
						'engine'        => '',
						'schema_sha256' => '',
						'rows'          => array(),
					);
					continue;
				}

				if ( $is_sqlite ) {
					$engine = 'SQLITE';
					$wpdb->last_error = '';
					$create_sql = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT sql FROM sqlite_master WHERE type = 'table' AND name = %s LIMIT 1",
							$table
						)
					);
				} else {
					$wpdb->last_error = '';
					$engine = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s LIMIT 1',
							$table
						)
					);
					$engine = strtoupper( (string) $engine );
					if ( '' !== (string) $wpdb->last_error || ! in_array( $engine, array( 'INNODB', 'XTRADB' ), true ) ) {
						return new WP_Error( 'c99_campaign_snapshot_engine', 'Every existing Campaign Studio table must use transactional storage.', array( 'status' => 409 ) );
					}
					$wpdb->last_error = '';
					$create_row = $wpdb->get_row( 'SHOW CREATE TABLE ' . $quoted, ARRAY_N );
					$create_sql = is_array( $create_row ) ? (string) ( $create_row[1] ?? '' ) : '';
				}
				if ( '' !== (string) $wpdb->last_error || ! is_string( $create_sql ) || '' === $create_sql ) {
					return new WP_Error( 'c99_campaign_snapshot_schema', 'A Campaign Studio table schema could not be inspected.', array( 'status' => 500 ) );
				}
				$normalized_schema = str_replace( (string) $table, '__complete99_campaign_' . $key . '__', $create_sql );
				$wpdb->last_error = '';
				$rows = $wpdb->get_results( 'SELECT * FROM ' . $quoted . ' ORDER BY `id` ASC LIMIT 5001', ARRAY_A );
				if ( '' !== (string) $wpdb->last_error || ! is_array( $rows ) || 5000 < count( $rows ) ) {
					return new WP_Error( 'c99_campaign_snapshot_rows', 'A Campaign Studio table exceeds the bounded rollback snapshot.', array( 'status' => 409 ) );
				}
				$rows = $canonicalize_json_value( $rows );
				$encoded_rows = wp_json_encode( $rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( false === $encoded_rows ) {
					return new WP_Error( 'c99_campaign_snapshot_encode', 'A Campaign Studio table snapshot could not be encoded.', array( 'status' => 500 ) );
				}
				$total_bytes += strlen( $encoded_rows );
				if ( 8 * 1024 * 1024 < $total_bytes ) {
					return new WP_Error( 'c99_campaign_snapshot_capacity', 'The Campaign Studio rollback snapshot exceeds its byte ceiling.', array( 'status' => 409 ) );
				}
				$result[ $key ] = array(
					'exists'        => true,
					'engine'        => $engine,
					'schema_sha256' => hash( 'sha256', $normalized_schema ),
					'rows'          => $rows,
				);
			}
			return $result;
		};

		$ops_snapshot_valid = static function ( $snapshot ) use ( $ops_table_names ) {
			if ( ! is_array( $snapshot ) || array_keys( $snapshot ) !== array_keys( $ops_table_names() ) ) {
				return false;
			}
			foreach ( $snapshot as $record ) {
				if ( ! is_array( $record ) || array( 'exists', 'engine', 'schema_sha256', 'rows' ) !== array_keys( $record ) || ! is_bool( $record['exists'] ) || ! is_array( $record['rows'] ) || 5000 < count( $record['rows'] ) ) {
					return false;
				}
				if ( $record['exists'] ) {
					if ( ! in_array( $record['engine'], array( 'INNODB', 'XTRADB', 'SQLITE' ), true ) || ! is_string( $record['schema_sha256'] ) || ! preg_match( '/^[a-f0-9]{64}$/', $record['schema_sha256'] ) ) {
						return false;
					}
					foreach ( $record['rows'] as $row ) {
						if ( ! is_array( $row ) ) {
							return false;
						}
					}
				} elseif ( '' !== $record['engine'] || '' !== $record['schema_sha256'] || array() !== $record['rows'] ) {
					return false;
				}
			}
			return true;
		};

		$ops_snapshot_digest = static function ( $snapshot ) use ( $canonicalize_json_value, $ops_snapshot_valid ) {
			if ( ! $ops_snapshot_valid( $snapshot ) ) {
				return '';
			}
			$encoded = wp_json_encode( $canonicalize_json_value( $snapshot ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			return false === $encoded ? '' : hash( 'sha256', $encoded );
		};

		$ops_snapshot_has_tables = static function ( $snapshot ) use ( $ops_snapshot_valid ) {
			if ( ! $ops_snapshot_valid( $snapshot ) ) {
				return false;
			}
			foreach ( $snapshot as $record ) {
				if ( true === $record['exists'] ) {
					return true;
				}
			}
			return false;
		};

		$ops_reconstruct_forward = static function ( $baseline, $canonical, $quarantine ) use ( $canonicalize_json_value, $ops_snapshot_valid ) {
			if ( ! $ops_snapshot_valid( $baseline ) || ! $ops_snapshot_valid( $canonical ) || ! $ops_snapshot_valid( $quarantine ) ) {
				return new WP_Error( 'c99_ops_restore_snapshot', 'The operations rollback table state is invalid.', array( 'status' => 500 ) );
			}
			$forward = array();
			foreach ( $baseline as $key => $baseline_record ) {
				$canonical_record  = $canonical[ $key ];
				$quarantine_record = $quarantine[ $key ];
				if ( $baseline_record['exists'] ) {
					if (
						$quarantine_record['exists']
						|| $canonicalize_json_value( $baseline_record ) !== $canonicalize_json_value( $canonical_record )
					) {
						return new WP_Error(
							'c99_ops_restore_existing_changed',
							'Rollback refused because a baseline operations table changed during the candidate deployment.',
							array( 'status' => 409, 'table' => $key )
						);
					}
					$forward[ $key ] = $canonical_record;
					continue;
				}
				if ( $canonical_record['exists'] && $quarantine_record['exists'] ) {
					return new WP_Error(
						'c99_ops_restore_ambiguous',
						'Rollback found both canonical and quarantined copies of a candidate operations table.',
						array( 'status' => 409, 'table' => $key )
					);
				}
				$forward[ $key ] = $quarantine_record['exists'] ? $quarantine_record : $canonical_record;
			}
			return $forward;
		};

		$campaign_snapshot_valid = static function ( $snapshot ) use ( $campaign_table_names ) {
			if ( ! is_array( $snapshot ) || array_keys( $snapshot ) !== array_keys( $campaign_table_names() ) ) {
				return false;
			}
			foreach ( $snapshot as $record ) {
				if ( ! is_array( $record ) || array( 'exists', 'engine', 'schema_sha256', 'rows' ) !== array_keys( $record ) || ! is_bool( $record['exists'] ) || ! is_array( $record['rows'] ) || 5000 < count( $record['rows'] ) ) {
					return false;
				}
				if ( $record['exists'] ) {
					if ( ! in_array( $record['engine'], array( 'INNODB', 'XTRADB', 'SQLITE' ), true ) || ! is_string( $record['schema_sha256'] ) || ! preg_match( '/^[a-f0-9]{64}$/', $record['schema_sha256'] ) ) {
						return false;
					}
					foreach ( $record['rows'] as $row ) {
						if ( ! is_array( $row ) ) {
							return false;
						}
					}
				} elseif ( '' !== $record['engine'] || '' !== $record['schema_sha256'] || array() !== $record['rows'] ) {
					return false;
				}
			}
			return true;
		};

		$campaign_snapshot_digest = static function ( $snapshot ) use ( $canonicalize_json_value, $campaign_snapshot_valid ) {
			if ( ! $campaign_snapshot_valid( $snapshot ) ) {
				return '';
			}
			$encoded = wp_json_encode( $canonicalize_json_value( $snapshot ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			return false === $encoded ? '' : hash( 'sha256', $encoded );
		};

		$campaign_snapshot_has_tables = static function ( $snapshot ) use ( $campaign_snapshot_valid ) {
			if ( ! $campaign_snapshot_valid( $snapshot ) ) {
				return false;
			}
			foreach ( $snapshot as $record ) {
				if ( true === $record['exists'] ) {
					return true;
				}
			}
			return false;
		};

		$campaign_snapshot_all_tables = static function ( $snapshot ) use ( $campaign_snapshot_valid ) {
			if ( ! $campaign_snapshot_valid( $snapshot ) ) {
				return false;
			}
			foreach ( $snapshot as $record ) {
				if ( true !== $record['exists'] ) {
					return false;
				}
			}
			return true;
		};

		$campaign_reconstruct_forward = static function ( $baseline, $canonical, $quarantine ) use ( $canonicalize_json_value, $campaign_snapshot_valid ) {
			if ( ! $campaign_snapshot_valid( $baseline ) || ! $campaign_snapshot_valid( $canonical ) || ! $campaign_snapshot_valid( $quarantine ) ) {
				return new WP_Error( 'c99_campaign_restore_snapshot', 'The Campaign Studio rollback table state is invalid.', array( 'status' => 500 ) );
			}
			$forward = array();
			foreach ( $baseline as $key => $baseline_record ) {
				$canonical_record  = $canonical[ $key ];
				$quarantine_record = $quarantine[ $key ];
				if ( $baseline_record['exists'] ) {
					if (
						$quarantine_record['exists']
						|| $canonicalize_json_value( $baseline_record ) !== $canonicalize_json_value( $canonical_record )
					) {
						return new WP_Error(
							'c99_campaign_restore_existing_changed',
							'Rollback refused because a baseline Campaign Studio table changed during the candidate deployment.',
							array( 'status' => 409, 'table' => $key )
						);
					}
					$forward[ $key ] = $canonical_record;
					continue;
				}
				if ( $canonical_record['exists'] && $quarantine_record['exists'] ) {
					return new WP_Error(
						'c99_campaign_restore_ambiguous',
						'Rollback found both canonical and quarantined copies of a candidate Campaign Studio table.',
						array( 'status' => 409, 'table' => $key )
					);
				}
				$forward[ $key ] = $quarantine_record['exists'] ? $quarantine_record : $canonical_record;
			}
			return $forward;
		};

		$ops_atomic_rename = static function ( $pairs ) use ( $config, $database_identifier ) {
			global $wpdb;
			if ( ! is_array( $pairs ) || empty( $pairs ) ) {
				return true;
			}
			$clauses = array();
			foreach ( $pairs as $source => $target ) {
				$quoted_source = $database_identifier( $source );
				$quoted_target = $database_identifier( $target );
				if ( is_wp_error( $quoted_source ) || is_wp_error( $quoted_target ) || $source === $target ) {
					return new WP_Error( 'c99_ops_rename_scope', 'An operations rollback rename is invalid.', array( 'status' => 500 ) );
				}
				$clauses[] = $quoted_source . ' TO ' . $quoted_target;
			}
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			$is_sqlite      = $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) );
			if ( $is_sqlite ) {
				if ( false === $wpdb->query( 'BEGIN IMMEDIATE TRANSACTION' ) ) {
					return new WP_Error( 'c99_ops_rename_begin', 'The operations rollback rename could not start.', array( 'status' => 500 ) );
				}
				foreach ( $pairs as $source => $target ) {
					$quoted_source = $database_identifier( $source );
					$quoted_target = $database_identifier( $target );
					if ( false === $wpdb->query( 'ALTER TABLE ' . $quoted_source . ' RENAME TO ' . $quoted_target ) ) {
						$wpdb->query( 'ROLLBACK' );
						return new WP_Error( 'c99_ops_rename', 'The operations rollback rename failed.', array( 'status' => 500 ) );
					}
				}
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'c99_ops_rename_commit', 'The operations rollback rename could not commit.', array( 'status' => 500 ) );
				}
				return true;
			}
			$wpdb->last_error = '';
			$renamed = $wpdb->query( 'RENAME TABLE ' . implode( ', ', $clauses ) );
			return false === $renamed || '' !== (string) $wpdb->last_error
				? new WP_Error( 'c99_ops_rename', 'The operations rollback rename failed.', array( 'status' => 500 ) )
				: true;
		};

		$ops_drop_tables = static function ( $tables ) use ( $config, $database_identifier ) {
			global $wpdb;
			if ( ! is_array( $tables ) || empty( $tables ) ) {
				return true;
			}
			$quoted = array();
			foreach ( $tables as $table ) {
				$name = $database_identifier( $table );
				if ( is_wp_error( $name ) ) {
					return $name;
				}
				$quoted[] = $name;
			}
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			$is_sqlite      = $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) );
			if ( $is_sqlite ) {
				if ( false === $wpdb->query( 'BEGIN IMMEDIATE TRANSACTION' ) ) {
					return new WP_Error( 'c99_ops_drop_begin', 'Operations rollback cleanup could not start.', array( 'status' => 500 ) );
				}
				foreach ( $quoted as $table ) {
					if ( false === $wpdb->query( 'DROP TABLE ' . $table ) ) {
						$wpdb->query( 'ROLLBACK' );
						return new WP_Error( 'c99_ops_drop', 'Operations rollback cleanup failed.', array( 'status' => 500 ) );
					}
				}
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'c99_ops_drop_commit', 'Operations rollback cleanup could not commit.', array( 'status' => 500 ) );
				}
				return true;
			}
			$wpdb->last_error = '';
			$dropped = $wpdb->query( 'DROP TABLE ' . implode( ', ', $quoted ) );
			return false === $dropped || '' !== (string) $wpdb->last_error
				? new WP_Error( 'c99_ops_drop', 'Operations rollback cleanup failed.', array( 'status' => 500 ) )
				: true;
		};

		$protected_rejoin_forward = static function ( $deployment_id, $ops_baseline, $campaign_baseline, $expected_ops_sha256, $expected_campaign_sha256 ) use ( $capture_ops_tables, $capture_campaign_tables, $ops_table_names, $campaign_table_names, $ops_quarantine_names, $campaign_quarantine_names, $ops_snapshot_digest, $campaign_snapshot_digest, $ops_snapshot_has_tables, $campaign_snapshot_has_tables, $ops_reconstruct_forward, $campaign_reconstruct_forward, $ops_atomic_rename ) {
			if (
				! is_string( $expected_ops_sha256 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_ops_sha256 )
				|| ! is_string( $expected_campaign_sha256 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_campaign_sha256 )
			) {
				return new WP_Error( 'c99_protected_forward_digest', 'The recorded protected-table fingerprints are invalid.', array( 'status' => 500 ) );
			}
			$ops_names                 = $ops_table_names();
			$campaign_names            = $campaign_table_names();
			$ops_quarantine_names      = $ops_quarantine_names( $deployment_id );
			$campaign_quarantine_names = $campaign_quarantine_names( $deployment_id );
			$ops_canonical             = $capture_ops_tables( $ops_names );
			$campaign_canonical        = $capture_campaign_tables( $campaign_names );
			$ops_quarantine            = $capture_ops_tables( $ops_quarantine_names );
			$campaign_quarantine       = $capture_campaign_tables( $campaign_quarantine_names );
			if ( is_wp_error( $ops_canonical ) || is_wp_error( $campaign_canonical ) || is_wp_error( $ops_quarantine ) || is_wp_error( $campaign_quarantine ) ) {
				return new WP_Error( 'c99_protected_rejoin_probe', 'The protected rollback retry state could not be inspected.', array( 'status' => 500 ) );
			}
			$forward_ops      = $ops_reconstruct_forward( $ops_baseline, $ops_canonical, $ops_quarantine );
			$forward_campaign = $campaign_reconstruct_forward( $campaign_baseline, $campaign_canonical, $campaign_quarantine );
			if ( is_wp_error( $forward_ops ) ) {
				return $forward_ops;
			}
			if ( is_wp_error( $forward_campaign ) ) {
				return $forward_campaign;
			}
			$ops_sha256      = $ops_snapshot_digest( $forward_ops );
			$campaign_sha256 = $campaign_snapshot_digest( $forward_campaign );
			if ( '' === $ops_sha256 || ! hash_equals( $expected_ops_sha256, $ops_sha256 ) || '' === $campaign_sha256 || ! hash_equals( $expected_campaign_sha256, $campaign_sha256 ) ) {
				return new WP_Error( 'c99_protected_forward_changed', 'Rollback refused because the candidate protected tables changed.', array( 'status' => 409 ) );
			}
			$pairs = array();
			foreach ( $ops_baseline as $key => $record ) {
				if ( ! $record['exists'] && $ops_quarantine[ $key ]['exists'] ) {
					$pairs[ $ops_quarantine_names[ $key ] ] = $ops_names[ $key ];
				}
			}
			foreach ( $campaign_baseline as $key => $record ) {
				if ( ! $record['exists'] && $campaign_quarantine[ $key ]['exists'] ) {
					$pairs[ $campaign_quarantine_names[ $key ] ] = $campaign_names[ $key ];
				}
			}
			if ( ! empty( $pairs ) ) {
				$renamed = $ops_atomic_rename( $pairs );
				if ( is_wp_error( $renamed ) ) {
					return $renamed;
				}
			}
			$ops_rejoined             = $capture_ops_tables( $ops_names );
			$campaign_rejoined        = $capture_campaign_tables( $campaign_names );
			$ops_quarantine_after     = $capture_ops_tables( $ops_quarantine_names );
			$campaign_quarantine_after= $capture_campaign_tables( $campaign_quarantine_names );
			if (
				is_wp_error( $ops_rejoined )
				|| is_wp_error( $campaign_rejoined )
				|| is_wp_error( $ops_quarantine_after )
				|| is_wp_error( $campaign_quarantine_after )
				|| ! hash_equals( $expected_ops_sha256, $ops_snapshot_digest( $ops_rejoined ) )
				|| ! hash_equals( $expected_campaign_sha256, $campaign_snapshot_digest( $campaign_rejoined ) )
				|| $ops_snapshot_has_tables( $ops_quarantine_after )
				|| $campaign_snapshot_has_tables( $campaign_quarantine_after )
			) {
				return new WP_Error( 'c99_protected_rejoin_readback', 'The exact forward protected-table state could not be re-established.', array( 'status' => 500 ) );
			}
			return array(
				'forward_ops_sha256'      => $expected_ops_sha256,
				'forward_campaign_sha256' => $expected_campaign_sha256,
				'tables_rejoined'          => count( $pairs ),
			);
		};

		$protected_cleanup_quarantine = static function ( $deployment_id, $ops_baseline, $campaign_baseline, $expected_ops_sha256, $expected_campaign_sha256 ) use ( $canonicalize_json_value, $capture_ops_tables, $capture_campaign_tables, $ops_table_names, $campaign_table_names, $ops_quarantine_names, $campaign_quarantine_names, $ops_snapshot_valid, $campaign_snapshot_valid, $ops_snapshot_digest, $campaign_snapshot_digest, $ops_snapshot_has_tables, $campaign_snapshot_has_tables, $ops_reconstruct_forward, $campaign_reconstruct_forward, $ops_drop_tables ) {
			if (
				! $ops_snapshot_valid( $ops_baseline )
				|| ! $campaign_snapshot_valid( $campaign_baseline )
				|| ! is_string( $expected_ops_sha256 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_ops_sha256 )
				|| ! is_string( $expected_campaign_sha256 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_campaign_sha256 )
			) {
				return new WP_Error( 'c99_protected_cleanup_proof', 'The protected rollback cleanup proof is invalid.', array( 'status' => 500 ) );
			}
			$ops_names                 = $ops_table_names();
			$campaign_names            = $campaign_table_names();
			$ops_quarantine_names      = $ops_quarantine_names( $deployment_id );
			$campaign_quarantine_names = $campaign_quarantine_names( $deployment_id );
			$ops_canonical             = $capture_ops_tables( $ops_names );
			$campaign_canonical        = $capture_campaign_tables( $campaign_names );
			$ops_quarantine            = $capture_ops_tables( $ops_quarantine_names );
			$campaign_quarantine       = $capture_campaign_tables( $campaign_quarantine_names );
			if ( is_wp_error( $ops_canonical ) || is_wp_error( $campaign_canonical ) || is_wp_error( $ops_quarantine ) || is_wp_error( $campaign_quarantine ) ) {
				return new WP_Error( 'c99_protected_cleanup_probe', 'The protected rollback quarantine could not be inspected.', array( 'status' => 500 ) );
			}
			if ( $canonicalize_json_value( $ops_baseline ) !== $canonicalize_json_value( $ops_canonical ) || $canonicalize_json_value( $campaign_baseline ) !== $canonicalize_json_value( $campaign_canonical ) ) {
				return new WP_Error( 'c99_protected_cleanup_baseline', 'Protected rollback cleanup requires an exact canonical baseline readback.', array( 'status' => 409 ) );
			}
			if ( ! $ops_snapshot_has_tables( $ops_quarantine ) && ! $campaign_snapshot_has_tables( $campaign_quarantine ) ) {
				return array( 'already_clean' => true, 'tables_dropped' => 0 );
			}
			$forward_ops      = $ops_reconstruct_forward( $ops_baseline, $ops_canonical, $ops_quarantine );
			$forward_campaign = $campaign_reconstruct_forward( $campaign_baseline, $campaign_canonical, $campaign_quarantine );
			if ( is_wp_error( $forward_ops ) ) {
				return $forward_ops;
			}
			if ( is_wp_error( $forward_campaign ) ) {
				return $forward_campaign;
			}
			if ( ! hash_equals( $expected_ops_sha256, $ops_snapshot_digest( $forward_ops ) ) || ! hash_equals( $expected_campaign_sha256, $campaign_snapshot_digest( $forward_campaign ) ) ) {
				return new WP_Error( 'c99_protected_cleanup_forward_changed', 'The quarantined protected tables do not match the recorded forward state.', array( 'status' => 409 ) );
			}
			$drop = array();
			foreach ( $ops_quarantine as $key => $record ) {
				if ( $record['exists'] ) {
					$drop[] = $ops_quarantine_names[ $key ];
				}
			}
			foreach ( $campaign_quarantine as $key => $record ) {
				if ( $record['exists'] ) {
					$drop[] = $campaign_quarantine_names[ $key ];
				}
			}
			$dropped = $ops_drop_tables( $drop );
			if ( is_wp_error( $dropped ) ) {
				return $dropped;
			}
			$ops_after                 = $capture_ops_tables( $ops_names );
			$campaign_after            = $capture_campaign_tables( $campaign_names );
			$ops_quarantine_after      = $capture_ops_tables( $ops_quarantine_names );
			$campaign_quarantine_after = $capture_campaign_tables( $campaign_quarantine_names );
			if (
				is_wp_error( $ops_after )
				|| is_wp_error( $campaign_after )
				|| is_wp_error( $ops_quarantine_after )
				|| is_wp_error( $campaign_quarantine_after )
				|| $canonicalize_json_value( $ops_baseline ) !== $canonicalize_json_value( $ops_after )
				|| $canonicalize_json_value( $campaign_baseline ) !== $canonicalize_json_value( $campaign_after )
				|| $ops_snapshot_has_tables( $ops_quarantine_after )
				|| $campaign_snapshot_has_tables( $campaign_quarantine_after )
			) {
				return new WP_Error( 'c99_protected_cleanup_readback', 'The protected rollback quarantine cleanup could not be verified.', array( 'status' => 500 ) );
			}
			return array( 'already_clean' => false, 'tables_dropped' => count( $drop ) );
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
			$engines = array_values( array_unique( $found ) );
			sort( $engines, SORT_STRING );
			return array(
				'engine' => implode( ',', $engines ),
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

		$capture_database_state = static function () use ( $config, $capture_ops_tables, $capture_campaign_tables, $campaign_snapshot_has_tables ) {
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
			$ops_tables = $capture_ops_tables();
			if ( is_wp_error( $ops_tables ) ) {
				return $ops_tables;
			}
			$campaign_tables = $capture_campaign_tables();
			if ( is_wp_error( $campaign_tables ) ) {
				return $campaign_tables;
			}
			$wpdb->last_error = '';
			$campaign_marker = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s",
					'complete99_campaign_schema_version'
				),
				ARRAY_A
			);
			if ( '' !== (string) $wpdb->last_error || ( null !== $campaign_marker && ! is_array( $campaign_marker ) ) ) {
				return $query_error( 'campaign_marker' );
			}
			$wpdb->last_error = '';
			$campaign_lifecycle_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 2",
					'complete99_campaign_lifecycle_reservation_v1'
				),
				ARRAY_A
			);
			if (
				'' !== (string) $wpdb->last_error
				|| ! is_array( $campaign_lifecycle_rows )
				|| 1 < count( $campaign_lifecycle_rows )
			) {
				return $query_error( 'campaign_lifecycle_reservation_cardinality' );
			}
			$campaign_lifecycle_reservation = 1 === count( $campaign_lifecycle_rows )
				? $campaign_lifecycle_rows[0]
				: null;
			$campaign_component = is_array( $campaign_marker )
				|| is_array( $campaign_lifecycle_reservation )
				|| $campaign_snapshot_has_tables( $campaign_tables );
			$option_names = array(
				'active_plugins',
				'complete99_last_deployment_id',
				'complete99_evaluation_catalog_receipt',
				'complete99_os_public_url',
				'complete99_os_url',
			);
			if ( $campaign_component ) {
				$option_names[] = 'complete99_campaign_schema_version';
				$option_names[] = 'complete99_campaign_lifecycle_reservation_v1';
			}
			$option_names = array_merge(
				$option_names,
				array(
					'complete99_ops_schema_version',
					'complete99_platform_version',
					'page_on_front',
					'rewrite_rules',
					'show_on_front',
					$wpdb->prefix . 'user_roles',
				)
			);
			$options = array();
			foreach ( $option_names as $option_name ) {
				if ( 'complete99_campaign_schema_version' === $option_name ) {
					$row = $campaign_marker;
				} elseif ( 'complete99_campaign_lifecycle_reservation_v1' === $option_name ) {
					$row = $campaign_lifecycle_reservation;
				} else {
					$wpdb->last_error = '';
					$row = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s",
							$option_name
						),
						ARRAY_A
					);
				}
				if ( '' !== (string) $wpdb->last_error || ( null !== $row && ! is_array( $row ) ) ) {
					return $query_error( 'option' );
				}
				$options[ $option_name ] = is_array( $row ) ? $row : null;
			}
			$wpdb->last_error = '';
			$sync_secret_state = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS row_count, COALESCE(MAX(CASE WHEN option_value <> '' THEN 1 ELSE 0 END), 0) AS configured FROM {$wpdb->options} WHERE option_name = %s",
					'complete99_sync_secret'
				),
				ARRAY_A
			);
			if (
				'' !== (string) $wpdb->last_error
				|| ! is_array( $sync_secret_state )
				|| ! isset( $sync_secret_state['row_count'], $sync_secret_state['configured'] )
				|| ! is_numeric( $sync_secret_state['row_count'] )
				|| ! is_numeric( $sync_secret_state['configured'] )
			) {
				return $query_error( 'sync_secret' );
			}
			$sync_secret_existed    = 0 < (int) $sync_secret_state['row_count'];
			$sync_secret_configured = 0 < (int) $sync_secret_state['configured'];

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
			$wpdb->last_error = '';
			$evaluation_rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s ORDER BY post_id",
					'_complete99_evaluation_catalog_managed',
					'1'
				)
			);
			if ( '' !== (string) $wpdb->last_error || ! is_array( $evaluation_rows ) ) {
				return $query_error( 'evaluation_ids' );
			}
			$evaluation_ids = array_map( 'intval', $evaluation_rows );
			$managed_ids    = array_values( array_unique( array_merge( $seed_ids, $evaluation_ids ) ) );
			sort( $managed_ids, SORT_NUMERIC );
			$posts    = array();
			$postmeta = array();
			if ( ! empty( $managed_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $managed_ids ), '%d' ) );
				$post_query   = $wpdb->prepare(
					"SELECT * FROM {$wpdb->posts} WHERE ID IN ({$placeholders}) OR (post_type = 'revision' AND post_parent IN ({$placeholders})) ORDER BY ID",
					array_merge( $managed_ids, $managed_ids )
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
			$snapshot = array(
				'options'   => $options,
				'postmeta'  => $postmeta,
				'posts'     => $posts,
				'seed_ids'  => $seed_ids,
				'evaluation_ids'=> $evaluation_ids,
				'ops_tables'=> $ops_tables,
			);
			if ( $campaign_component ) {
				$snapshot['campaign_tables'] = $campaign_tables;
			}
			$snapshot['sync_secret_existed']    = $sync_secret_existed;
			$snapshot['sync_secret_configured'] = $sync_secret_configured;
			return $snapshot;
		};

		/**
		 * Drain any mutation that began before the option reservation and hold the
		 * exact Campaign/Ops writer advisory lock through baseline capture. Once the
		 * baseline is released, the durable deploy option keeps every later writer
		 * fail closed through forward install, rollback and finalization.
		 */
		$capture_quiescent_database_state = static function () use ( $config, $capture_database_state ) {
			global $wpdb;
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type  = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			if ( $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) ) ) {
				return $capture_database_state();
			}
			if ( true !== $wpdb->is_mysql ) {
				return new WP_Error( 'c99_campaign_quiescence_driver', 'The deployment cannot serialize the Campaign rollback baseline.', array( 'status' => 409 ) );
			}
			$name = 'c99_campaign_slot_' . substr( hash( 'sha256', 'rollback-capacity' ), 0, 40 );
			$previous_suppress = $wpdb->suppress_errors( true );
			$wpdb->last_error = '';
			$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 10 ) );
			$acquire_error = (string) $wpdb->last_error;
			if ( '' !== $acquire_error || 1 !== (int) $acquired ) {
				$wpdb->suppress_errors( $previous_suppress );
				return new WP_Error( 'c99_campaign_quiescence_busy', 'The Campaign mutation boundary did not drain before baseline capture.', array( 'status' => 423 ) );
			}
			try {
				$snapshot = $capture_database_state();
			} catch ( \Throwable $error ) {
				$snapshot = new WP_Error( 'c99_campaign_quiescence_capture', 'The quiescent rollback baseline raised an exception.', array( 'status' => 500 ) );
			}
			$wpdb->last_error = '';
			$released = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
			$release_error = (string) $wpdb->last_error;
			$wpdb->suppress_errors( $previous_suppress );
			if ( '' !== $release_error || 1 !== (int) $released ) {
				return new WP_Error( 'c99_campaign_quiescence_release', 'The Campaign baseline lock could not be released cleanly.', array( 'status' => 500 ) );
			}
			return $snapshot;
		};

		$database_snapshot_generation = static function ( $snapshot ) {
			if ( ! is_array( $snapshot ) ) {
				return 0;
			}
			if ( array_key_exists( 'campaign_tables', $snapshot ) ) {
				return array_key_exists( 'ops_tables', $snapshot ) ? 3 : 0;
			}
			return array_key_exists( 'ops_tables', $snapshot ) ? 2 : 1;
		};

		$campaign_lifecycle_reservation_valid = static function ( $row ) {
			if (
				! is_array( $row )
				|| array( 'option_name', 'option_value', 'autoload' ) !== array_keys( $row )
				|| ! is_string( $row['option_name'] )
				|| ! is_string( $row['option_value'] )
				|| ! is_string( $row['autoload'] )
				|| 'complete99_campaign_lifecycle_reservation_v1' !== $row['option_name']
				|| 'no' !== $row['autoload']
			) {
				return false;
			}
			$value = json_decode( $row['option_value'], true, 8, JSON_BIGINT_AS_STRING );
			if (
				JSON_ERROR_NONE !== json_last_error()
				|| ! is_array( $value )
				|| array( 'changedAt', 'generation', 'schemaVersion', 'state' ) !== array_keys( $value )
				|| ! is_string( $value['changedAt'] )
				|| ! is_int( $value['generation'] )
				|| 1 > $value['generation']
				|| PHP_INT_MAX === $value['generation']
				|| 'complete99-campaign-lifecycle-reservation/v1' !== ( $value['schemaVersion'] ?? null )
				|| ! in_array( $value['state'] ?? null, array( 'active', 'suspending', 'inactive' ), true )
				|| ! preg_match( '/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value['changedAt'] )
			) {
				return false;
			}
			$date = \DateTimeImmutable::createFromFormat(
				'!Y-m-d\\TH:i:s\\Z',
				$value['changedAt'],
				new \DateTimeZone( 'UTC' )
			);
			$date_errors = \DateTimeImmutable::getLastErrors();
			if (
				false === $date
				|| $value['changedAt'] !== $date->format( 'Y-m-d\\TH:i:s\\Z' )
				|| ( is_array( $date_errors ) && ( 0 !== $date_errors['warning_count'] || 0 !== $date_errors['error_count'] ) )
			) {
				return false;
			}
			if ( ! defined( 'JSON_UNESCAPED_LINE_TERMINATORS' ) ) {
				return false;
			}
			$canonical = wp_json_encode(
				$value,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS
			);
			return is_string( $canonical ) && hash_equals( $row['option_value'], $canonical );
		};

		$campaign_snapshot_coherent = static function ( $snapshot ) use ( $database_snapshot_generation, $campaign_snapshot_valid, $campaign_snapshot_all_tables, $campaign_lifecycle_reservation_valid ) {
			$generation = $database_snapshot_generation( $snapshot );
			$options    = is_array( $snapshot ) && is_array( $snapshot['options'] ?? null ) ? $snapshot['options'] : null;
			if ( ! is_array( $options ) ) {
				return false;
			}
			if ( 1 === $generation || 2 === $generation ) {
				return ! array_key_exists( 'complete99_campaign_schema_version', $options )
					&& ! array_key_exists( 'complete99_campaign_lifecycle_reservation_v1', $options );
			}
			if ( 3 !== $generation || ! $campaign_snapshot_valid( $snapshot['campaign_tables'] ?? null ) ) {
				return false;
			}
			$marker = $options['complete99_campaign_schema_version'] ?? null;
			$lifecycle_reservation = $options['complete99_campaign_lifecycle_reservation_v1'] ?? null;
			return is_array( $marker )
				&& array( 'option_name', 'option_value', 'autoload' ) === array_keys( $marker )
				&& 'complete99_campaign_schema_version' === (string) $marker['option_name']
				&& 'complete99-campaign-schema/v1' === (string) $marker['option_value']
				&& $campaign_lifecycle_reservation_valid( $lifecycle_reservation )
				&& $campaign_snapshot_all_tables( $snapshot['campaign_tables'] );
		};

		$normalize_database_snapshot = static function ( $snapshot, $generation ) use ( $ops_absent_snapshot, $campaign_absent_snapshot ) {
			if ( ! is_array( $snapshot ) || ! in_array( $generation, array( 1, 2, 3 ), true ) || ! is_array( $snapshot['options'] ?? null ) ) {
				return new WP_Error( 'c99_db_snapshot_generation', 'The database rollback journal generation is invalid.', array( 'status' => 500 ) );
			}
			$options = array();
			foreach ( $snapshot['options'] as $option_name => $row ) {
				if ( 1 === $generation && 'complete99_platform_version' === $option_name ) {
					$options['complete99_campaign_schema_version'] = null;
					$options['complete99_campaign_lifecycle_reservation_v1'] = null;
					$options['complete99_ops_schema_version']      = null;
				} elseif ( 2 === $generation && 'complete99_ops_schema_version' === $option_name ) {
					$options['complete99_campaign_schema_version'] = null;
					$options['complete99_campaign_lifecycle_reservation_v1'] = null;
				}
				$options[ $option_name ] = $row;
			}
			return array(
				'options'                   => $options,
				'postmeta'                  => $snapshot['postmeta'],
				'posts'                     => $snapshot['posts'],
				'seed_ids'                  => $snapshot['seed_ids'],
				'evaluation_ids'            => $snapshot['evaluation_ids'],
				'ops_tables'                => 1 === $generation ? $ops_absent_snapshot() : $snapshot['ops_tables'],
				'campaign_tables'           => 3 === $generation ? $snapshot['campaign_tables'] : $campaign_absent_snapshot(),
				'sync_secret_existed'       => $snapshot['sync_secret_existed'],
				'sync_secret_configured'    => $snapshot['sync_secret_configured'],
			);
		};

		$project_database_snapshot = static function ( $snapshot, $generation ) {
			if ( ! is_array( $snapshot ) || ! in_array( $generation, array( 1, 2, 3 ), true ) || ! is_array( $snapshot['options'] ?? null ) ) {
				return new WP_Error( 'c99_db_snapshot_projection', 'The database rollback journal projection is invalid.', array( 'status' => 500 ) );
			}
			$projected = $snapshot;
			if ( 3 > $generation ) {
				unset(
					$projected['options']['complete99_campaign_schema_version'],
					$projected['options']['complete99_campaign_lifecycle_reservation_v1'],
					$projected['campaign_tables']
				);
			}
			if ( 2 > $generation ) {
				unset( $projected['options']['complete99_ops_schema_version'], $projected['ops_tables'] );
			}
			return $projected;
		};

		$capture_database_state_consistent = static function () use ( $capture_database_state, $config ) {
			global $wpdb;
			$database_class = strtolower( get_class( $wpdb ) );
			$database_type = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
			if ( $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) ) ) {
				return $capture_database_state();
			}
			if ( ! isset( $wpdb->is_mysql ) || true !== $wpdb->is_mysql ) {
				return new WP_Error( 'c99_db_observation_driver', 'The database observation driver is unsupported.', array( 'status' => 409 ) );
			}
			$wpdb->last_error = '';
			$isolation = $wpdb->query( 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ' );
			if ( false === $isolation || '' !== (string) $wpdb->last_error ) {
				return new WP_Error( 'c99_db_observation_isolation', 'The database observation could not require repeatable-read isolation.', array( 'status' => 500 ) );
			}
			$wpdb->last_error = '';
			$started = $wpdb->query( 'START TRANSACTION WITH CONSISTENT SNAPSHOT' );
			if ( false === $started || '' !== (string) $wpdb->last_error ) {
				return new WP_Error( 'c99_db_observation_begin', 'The consistent database observation could not start.', array( 'status' => 500 ) );
			}
			$snapshot = $capture_database_state();
			if ( is_wp_error( $snapshot ) ) {
				$wpdb->query( 'ROLLBACK' );
				return $snapshot;
			}
			$wpdb->last_error = '';
			$committed = $wpdb->query( 'COMMIT' );
			if ( false === $committed || '' !== (string) $wpdb->last_error ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'c99_db_observation_commit', 'The consistent database observation could not finish.', array( 'status' => 500 ) );
			}
			return $snapshot;
		};

		$database_snapshot_manifest = static function ( $snapshot ) use ( $canonicalize_json_value, $campaign_snapshot_coherent ) {
			if ( ! is_array( $snapshot ) ) {
				return new WP_Error( 'c99_db_manifest_snapshot', 'The database observation snapshot is invalid.', array( 'status' => 500 ) );
			}
			if ( ! $campaign_snapshot_coherent( $snapshot ) ) {
				return new WP_Error( 'c99_db_manifest_campaign_schema', 'The Campaign Studio schema marker and exact seven-table cohort are incoherent.', array( 'status' => 409 ) );
			}
			$options_without_deployment_marker = $snapshot['options'] ?? null;
			if ( ! is_array( $options_without_deployment_marker ) ) {
				return new WP_Error( 'c99_db_manifest_component', 'The database observation options component is invalid.', array( 'status' => 500 ) );
			}
			unset( $options_without_deployment_marker['complete99_last_deployment_id'] );
			$components = array(
				'options_without_deployment_marker' => $options_without_deployment_marker,
				'posts'                             => $snapshot['posts'] ?? null,
				'postmeta'                          => $snapshot['postmeta'] ?? null,
				'seed_ids'                          => $snapshot['seed_ids'] ?? null,
				'evaluation_ids'                    => $snapshot['evaluation_ids'] ?? null,
			);
			$schema = 'complete99-database-snapshot-manifest/v1';
			if ( array_key_exists( 'ops_tables', $snapshot ) ) {
				$components['ops_tables'] = $snapshot['ops_tables'];
				$schema = 'complete99-database-snapshot-manifest/v2';
			}
			if ( array_key_exists( 'campaign_tables', $snapshot ) ) {
				if ( ! array_key_exists( 'ops_tables', $snapshot ) ) {
					return new WP_Error( 'c99_db_manifest_generation', 'Campaign Studio tables require the operations-table manifest component.', array( 'status' => 500 ) );
				}
				$components['campaign_tables'] = $snapshot['campaign_tables'];
				$schema = 'complete99-database-snapshot-manifest/v3';
			}
			$manifest = array(
				'schema'                 => $schema,
				'sync_secret_existed'    => true === ( $snapshot['sync_secret_existed'] ?? null ),
				'sync_secret_configured' => true === ( $snapshot['sync_secret_configured'] ?? null ),
			);
			foreach ( $components as $component_name => $component ) {
				if ( ! is_array( $component ) ) {
					return new WP_Error( 'c99_db_manifest_component', 'A database observation component is invalid.', array( 'status' => 500, 'component' => $component_name ) );
				}
				$encoded = wp_json_encode(
					$canonicalize_json_value( $component ),
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
				);
				if ( false === $encoded ) {
					return new WP_Error( 'c99_db_manifest_encode', 'A database observation component could not be encoded.', array( 'status' => 500, 'component' => $component_name ) );
				}
				$manifest[ $component_name . '_count' ]  = count( $component );
				$manifest[ $component_name . '_sha256' ] = hash( 'sha256', $encoded );
			}
			$manifest_json = wp_json_encode(
				$canonicalize_json_value( $manifest ),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
			if ( false === $manifest_json ) {
				return new WP_Error( 'c99_db_manifest_encode', 'The database observation manifest could not be encoded.', array( 'status' => 500 ) );
			}
			return array(
				'manifest'        => $manifest,
				'manifest_sha256' => hash( 'sha256', $manifest_json ),
			);
		};

		$database_snapshot_manifest_valid = static function ( $manifest, $manifest_sha256 ) use ( $canonicalize_json_value ) {
			if ( ! is_array( $manifest ) || ! is_string( $manifest_sha256 ) || ! preg_match( '/^[a-f0-9]{64}$/', $manifest_sha256 ) ) {
				return false;
			}
			$schema = (string) ( $manifest['schema'] ?? '' );
			$components = array( 'options_without_deployment_marker', 'posts', 'postmeta', 'seed_ids', 'evaluation_ids' );
			if ( 'complete99-database-snapshot-manifest/v3' === $schema ) {
				$components[] = 'ops_tables';
				$components[] = 'campaign_tables';
			} elseif ( 'complete99-database-snapshot-manifest/v2' === $schema ) {
				$components[] = 'ops_tables';
			} elseif ( 'complete99-database-snapshot-manifest/v1' !== $schema ) {
				return false;
			}
			$expected_keys = array( 'schema', 'sync_secret_configured', 'sync_secret_existed' );
			foreach ( $components as $component ) {
				$expected_keys[] = $component . '_count';
				$expected_keys[] = $component . '_sha256';
			}
			$actual_keys = array_keys( $manifest );
			sort( $actual_keys, SORT_STRING );
			sort( $expected_keys, SORT_STRING );
			if (
				$actual_keys !== $expected_keys
				|| true !== ( $manifest['sync_secret_existed'] ?? null )
				|| true !== ( $manifest['sync_secret_configured'] ?? null )
			) {
				return false;
			}
			foreach ( $components as $component ) {
				$count_key  = $component . '_count';
				$digest_key = $component . '_sha256';
				if (
					! is_int( $manifest[ $count_key ] ?? null )
					|| 0 > $manifest[ $count_key ]
					|| ( in_array( $component, array( 'ops_tables', 'campaign_tables' ), true ) && 7 !== $manifest[ $count_key ] )
					|| ! is_string( $manifest[ $digest_key ] ?? null )
					|| ! preg_match( '/^[a-f0-9]{64}$/', $manifest[ $digest_key ] )
				) {
					return false;
				}
			}
			$manifest_json = wp_json_encode(
				$canonicalize_json_value( $manifest ),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
			return false !== $manifest_json && hash_equals( $manifest_sha256, hash( 'sha256', $manifest_json ) );
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

		$restore_database_state = static function ( $snapshot, $deployment_id, $snapshot_generation, $expected_forward_ops_sha256, $expected_forward_campaign_sha256 ) use ( $capture_database_state, $database_snapshot_generation, $normalize_database_snapshot, $capture_ops_tables, $capture_campaign_tables, $ops_table_names, $campaign_table_names, $ops_quarantine_names, $campaign_quarantine_names, $ops_snapshot_valid, $campaign_snapshot_valid, $ops_snapshot_digest, $campaign_snapshot_digest, $ops_reconstruct_forward, $campaign_reconstruct_forward, $ops_atomic_rename, $protected_rejoin_forward ) {
			global $wpdb;
			if (
				! is_array( $snapshot )
				|| ! in_array( $snapshot_generation, array( 1, 2, 3 ), true )
				|| ! isset( $snapshot['options'], $snapshot['posts'], $snapshot['postmeta'], $snapshot['seed_ids'], $snapshot['evaluation_ids'], $snapshot['ops_tables'], $snapshot['campaign_tables'], $snapshot['sync_secret_existed'], $snapshot['sync_secret_configured'] )
				|| ! is_array( $snapshot['seed_ids'] )
				|| ! is_array( $snapshot['evaluation_ids'] )
				|| ! $ops_snapshot_valid( $snapshot['ops_tables'] )
				|| ! $campaign_snapshot_valid( $snapshot['campaign_tables'] )
				|| ! is_bool( $snapshot['sync_secret_existed'] )
				|| ! is_bool( $snapshot['sync_secret_configured'] )
				|| ( $snapshot['sync_secret_configured'] && ! $snapshot['sync_secret_existed'] )
			) {
				return new WP_Error( 'c99_db_snapshot_invalid', 'The database rollback journal is invalid.', array( 'status' => 500 ) );
			}
			if (
				! is_string( $expected_forward_ops_sha256 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_forward_ops_sha256 )
				|| ! is_string( $expected_forward_campaign_sha256 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_forward_campaign_sha256 )
			) {
				return new WP_Error( 'c99_protected_forward_digest', 'The recorded forward protected-table fingerprints are invalid.', array( 'status' => 500 ) );
			}

			$ops_names                  = $ops_table_names();
			$campaign_names             = $campaign_table_names();
			$ops_quarantine_names       = $ops_quarantine_names( $deployment_id );
			$campaign_quarantine_names  = $campaign_quarantine_names( $deployment_id );
			$ops_canonical              = $capture_ops_tables( $ops_names );
			$campaign_canonical         = $capture_campaign_tables( $campaign_names );
			$ops_quarantine             = $capture_ops_tables( $ops_quarantine_names );
			$campaign_quarantine        = $capture_campaign_tables( $campaign_quarantine_names );
			if ( is_wp_error( $ops_canonical ) || is_wp_error( $campaign_canonical ) || is_wp_error( $ops_quarantine ) || is_wp_error( $campaign_quarantine ) ) {
				return new WP_Error( 'c99_protected_restore_probe', 'The protected tables could not be inspected before rollback.', array( 'status' => 500 ) );
			}
			$forward_ops      = $ops_reconstruct_forward( $snapshot['ops_tables'], $ops_canonical, $ops_quarantine );
			$forward_campaign = $campaign_reconstruct_forward( $snapshot['campaign_tables'], $campaign_canonical, $campaign_quarantine );
			if ( is_wp_error( $forward_ops ) || is_wp_error( $forward_campaign ) ) {
				return is_wp_error( $forward_ops ) ? $forward_ops : $forward_campaign;
			}
			$forward_ops_sha256      = $ops_snapshot_digest( $forward_ops );
			$forward_campaign_sha256 = $campaign_snapshot_digest( $forward_campaign );
			if (
				'' === $forward_ops_sha256
				|| '' === $forward_campaign_sha256
				|| ! hash_equals( $expected_forward_ops_sha256, $forward_ops_sha256 )
				|| ! hash_equals( $expected_forward_campaign_sha256, $forward_campaign_sha256 )
			) {
				return new WP_Error( 'c99_protected_forward_changed', 'Rollback refused because the candidate protected tables changed.', array( 'status' => 409 ) );
			}
			$detach_pairs = array();
			foreach ( $snapshot['ops_tables'] as $key => $baseline_record ) {
				if ( ! $baseline_record['exists'] && $ops_canonical[ $key ]['exists'] ) {
					$detach_pairs[ $ops_names[ $key ] ] = $ops_quarantine_names[ $key ];
				}
			}
			foreach ( $snapshot['campaign_tables'] as $key => $baseline_record ) {
				if ( ! $baseline_record['exists'] && $campaign_canonical[ $key ]['exists'] ) {
					$detach_pairs[ $campaign_names[ $key ] ] = $campaign_quarantine_names[ $key ];
				}
			}
			if ( ! empty( $detach_pairs ) ) {
				$detached = $ops_atomic_rename( $detach_pairs );
				if ( is_wp_error( $detached ) ) {
					return $detached;
				}
			}
			$ops_quarantine      = $capture_ops_tables( $ops_quarantine_names );
			$campaign_quarantine = $capture_campaign_tables( $campaign_quarantine_names );
			if ( is_wp_error( $ops_quarantine ) || is_wp_error( $campaign_quarantine ) ) {
				return new WP_Error( 'c99_protected_detach_readback', 'The protected rollback quarantine could not be verified.', array( 'status' => 500 ) );
			}
			$ops_quarantined_count = 0;
			foreach ( $ops_quarantine as $record ) {
				$ops_quarantined_count += $record['exists'] ? 1 : 0;
			}
			$campaign_quarantined_count = 0;
			foreach ( $campaign_quarantine as $record ) {
				$campaign_quarantined_count += $record['exists'] ? 1 : 0;
			}
			$started = false !== $wpdb->query( 'START TRANSACTION' );
			if ( ! $started ) {
				$rejoined = $protected_rejoin_forward( $deployment_id, $snapshot['ops_tables'], $snapshot['campaign_tables'], $expected_forward_ops_sha256, $expected_forward_campaign_sha256 );
				return is_wp_error( $rejoined )
					? new WP_Error( 'c99_db_transaction_protected_detached', 'The database rollback transaction could not start and the protected quarantine requires operator recovery.', array( 'status' => 500 ) )
					: new WP_Error( 'c99_db_transaction', 'The database rollback transaction could not start.', array( 'status' => 500 ) );
			}
			$commit_attempted = false;
			try {
				foreach ( $snapshot['options'] as $option_name => $row ) {
					if ( false === $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ), array( '%s' ) ) ) {
						throw new \RuntimeException( 'option_delete' );
					}
					if ( is_array( $row ) && false === $wpdb->insert( $wpdb->options, $row ) ) {
						throw new \RuntimeException( 'option_restore' );
					}
				}
				if ( $snapshot['sync_secret_configured'] ) {
					// A configured baseline is never mutated by this bridge, so no value is journaled or restored.
				} elseif ( $snapshot['sync_secret_existed'] ) {
					if (
						false === $wpdb->query(
							$wpdb->prepare(
								"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s",
								'',
								'complete99_sync_secret'
							)
						)
					) {
						throw new \RuntimeException( 'sync_secret_empty_restore' );
					}
					$sync_empty_count = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
							'complete99_sync_secret'
						)
					);
					if ( null === $sync_empty_count || ! is_numeric( $sync_empty_count ) ) {
						throw new \RuntimeException( 'sync_secret_empty_probe' );
					}
					if (
						0 === (int) $sync_empty_count
						&& false === $wpdb->insert(
							$wpdb->options,
							array(
								'option_name'  => 'complete99_sync_secret',
								'option_value' => '',
								'autoload'     => 'no',
							),
							array( '%s', '%s', '%s' )
						)
					) {
						throw new \RuntimeException( 'sync_secret_empty_insert' );
					}
				} else {
					if ( false === $wpdb->delete( $wpdb->options, array( 'option_name' => 'complete99_sync_secret' ), array( '%s' ) ) ) {
						throw new \RuntimeException( 'sync_secret_delete' );
					}
				}
				$wpdb->last_error = '';
				$restored_sync_state = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT(*) AS row_count, COALESCE(MAX(CASE WHEN option_value <> '' THEN 1 ELSE 0 END), 0) AS configured FROM {$wpdb->options} WHERE option_name = %s",
						'complete99_sync_secret'
					),
					ARRAY_A
				);
				$restored_sync_exists = is_array( $restored_sync_state )
					&& isset( $restored_sync_state['row_count'] )
					&& 0 < (int) $restored_sync_state['row_count'];
				$restored_sync_configured = is_array( $restored_sync_state )
					&& isset( $restored_sync_state['configured'] )
					&& 0 < (int) $restored_sync_state['configured'];
				if (
					'' !== (string) $wpdb->last_error
					|| $restored_sync_exists !== $snapshot['sync_secret_existed']
					|| $restored_sync_configured !== $snapshot['sync_secret_configured']
				) {
					throw new \RuntimeException( 'sync_secret_readback' );
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
				$wpdb->last_error = '';
				$current_evaluation_rows = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s ORDER BY post_id",
						'_complete99_evaluation_catalog_managed',
						'1'
					)
				);
				if ( '' !== (string) $wpdb->last_error || ! is_array( $current_evaluation_rows ) ) {
					throw new \RuntimeException( 'evaluation_read' );
				}
				$current_evaluation_ids = array_map( 'intval', $current_evaluation_rows );
				$seed_ids = array_values(
					array_unique(
						array_merge(
							array_map( 'intval', $snapshot['seed_ids'] ),
							array_map( 'intval', $snapshot['evaluation_ids'] ),
							$current_seed_ids,
							$current_evaluation_ids
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
				$precommit_snapshot   = $capture_database_state();
				$precommit_generation = is_wp_error( $precommit_snapshot ) ? 0 : $database_snapshot_generation( $precommit_snapshot );
				$precommit_normalized = is_wp_error( $precommit_snapshot )
					? $precommit_snapshot
					: $normalize_database_snapshot( $precommit_snapshot, $precommit_generation );
				$precommit_json = is_wp_error( $precommit_normalized ) ? false : wp_json_encode( $precommit_normalized );
				$baseline_json  = wp_json_encode( $snapshot );
				if (
					false === $precommit_json
					|| false === $baseline_json
					|| ! hash_equals( hash( 'sha256', $baseline_json ), hash( 'sha256', $precommit_json ) )
				) {
					throw new \RuntimeException( 'baseline_readback' );
				}
				$commit_attempted = true;
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					throw new \RuntimeException( 'commit' );
				}
			} catch ( \Throwable $error ) {
				if ( $commit_attempted ) {
					return new WP_Error(
						'c99_db_restore_commit_unknown',
						'The database rollback commit outcome is unknown; deterministic retry must reconcile the protected quarantine.',
						array( 'status' => 500 )
					);
				}
				$rolled_back = false !== $wpdb->query( 'ROLLBACK' );
				$rejoined    = $rolled_back
					? $protected_rejoin_forward( $deployment_id, $snapshot['ops_tables'], $snapshot['campaign_tables'], $expected_forward_ops_sha256, $expected_forward_campaign_sha256 )
					: new WP_Error( 'c99_db_restore_rollback', 'The failed database transaction could not be rolled back.', array( 'status' => 500 ) );
				return is_wp_error( $rejoined )
					? new WP_Error( 'c99_db_restore_protected_detached', 'The database rollback failed and the protected quarantine requires operator recovery.', array( 'status' => 500 ) )
					: new WP_Error( 'c99_db_restore', 'The database rollback journal could not be restored.', array( 'status' => 500, 'stage' => sanitize_key( $error->getMessage() ) ) );
			}
			wp_cache_flush();
			return array(
				'options_restored' => count( $snapshot['options'] ),
				'posts_restored'   => count( $snapshot['posts'] ),
				'meta_restored'    => count( $snapshot['postmeta'] ),
				'ops_tables_quarantined'=> $ops_quarantined_count,
				'campaign_tables_quarantined'=> $campaign_quarantined_count,
				'ops_quarantine_pending'=> 0 < $ops_quarantined_count,
				'campaign_quarantine_pending'=> 0 < $campaign_quarantined_count,
				'protected_quarantine_pending'=> 0 < ( $ops_quarantined_count + $campaign_quarantined_count ),
				'sync_configuration_restored'=> true,
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
				'callback'            => static function () use ( $config, $bootstrap_filesystem, $verify_site_identity, $auto_update_enabled, $acquire_lock, $release_lock, $process_lock_available, $verify_transactional_storage, $verify_migration_advisory_lock, $capture_database_state, $campaign_snapshot_coherent, $capture_robots_snapshot, $ops_quarantine_residue ) {
					global $wp_filesystem;
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$ops_residue = $ops_quarantine_residue();
					if ( is_wp_error( $ops_residue ) || ! empty( $ops_residue ) ) {
						return is_wp_error( $ops_residue )
							? $ops_residue
							: new WP_Error( 'c99_deploy_ops_rollback_residue', 'A prior operations rollback quarantine must be reconciled before redeployment.', array( 'status' => 409, 'table_count' => count( $ops_residue ) ) );
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
					if ( ! $campaign_snapshot_coherent( $database_snapshot ) ) {
						return new WP_Error(
							'c99_deploy_campaign_schema_drift',
							'Campaign Studio schema marker and exact seven-table cohort must be wholly absent or wholly valid before deployment.',
							array( 'status' => 409 )
						);
					}
					$database_json     = wp_json_encode( $database_snapshot );
					if ( false === $database_json ) {
						return new WP_Error( 'c99_db_snapshot_encode', 'The database rollback journal could not be encoded.', array( 'status' => 500 ) );
					}
					$robots_snapshot = $capture_robots_snapshot();
					if ( is_wp_error( $robots_snapshot ) ) {
						return $robots_snapshot;
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
						'robots_prior_exists'=> ! empty( $robots_snapshot['robots_prior_exists'] ),
						'robots_prior_sha256'=> (string) $robots_snapshot['robots_prior_sha256'],
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
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $read_lock, $process_lock_available, $directory_sha256, $verify_transactional_storage, $capture_database_state, $capture_database_state_consistent, $database_snapshot_manifest, $decrypt_database_state, $managed_robots_path, $ops_quarantine_residue, $campaign_lifecycle_reservation_valid ) {
					global $wpdb, $wp_filesystem;
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
					$phase = (string) ( $state['phase'] ?? ( $lock_owned ? ( $lock['phase'] ?? 'locked' ) : 'finalized' ) );
					$interrupted_installing_status = $lock_owned && 'installing' === $phase;
					$rollback_journal_status = $lock_owned && in_array(
						$phase,
						array( 'installing', 'candidate_activation_pending', 'candidate_activation_complete', 'installed_pending_stabilization' ),
						true
					);
					$interrupted_adopted_status = $lock_owned
						&& in_array( $phase, array( 'installed', 'committing', 'commit_failed', 'committed', 'cleanup_failed' ), true )
						&& ! empty( $state['adopted_forward_no_rollback'] ?? $lock['adopted_forward_no_rollback'] ?? false );
					$projected_deployment_id = sanitize_text_field( (string) $request->get_param( 'projected_deployment_id' ) );
					$orphaned_consistent_status = $lock_owned
						&& 'complete99-orphaned-rollback-receipt/v2' === (string) ( $lock['orphaned_recovery_receipt_schema'] ?? '' )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $lock['orphaned_recovery_proof_sha256'] ?? '' ) );
					$consistent_database_status = '' !== $projected_deployment_id
						|| $orphaned_consistent_status
						|| $interrupted_installing_status
						|| $interrupted_adopted_status;
					$database_storage = array();
					if ( $consistent_database_status ) {
						$database_storage = $verify_transactional_storage();
						if ( is_wp_error( $database_storage ) ) {
							return $database_storage;
						}
					}
					$database_snapshot = $consistent_database_status
						? $capture_database_state_consistent()
						: $capture_database_state();
					$database_json = is_wp_error( $database_snapshot ) ? false : wp_json_encode( $database_snapshot );
					$database_fingerprint = false === $database_json ? '' : hash( 'sha256', $database_json );
					$database_manifest_record = is_wp_error( $database_snapshot )
						? $database_snapshot
						: $database_snapshot_manifest( $database_snapshot );
					$projected_database_fingerprint = '';
					if ( '' !== $projected_deployment_id ) {
						if (
							! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $projected_deployment_id )
							|| ! str_starts_with( $projected_deployment_id, 'c99-' )
							|| ! is_array( $database_snapshot )
							|| ! is_array( $database_snapshot['options']['complete99_last_deployment_id'] ?? null )
						) {
							return new WP_Error( 'c99_status_projection', 'The requested database observation projection is invalid.', array( 'status' => 400 ) );
						}
						$projected_snapshot = $database_snapshot;
						$projected_snapshot['options']['complete99_last_deployment_id']['option_value'] = $projected_deployment_id;
						$projected_json = wp_json_encode( $projected_snapshot );
						if ( false === $projected_json ) {
							return new WP_Error( 'c99_status_projection_encode', 'The database observation projection could not be encoded.', array( 'status' => 500 ) );
						}
						$projected_database_fingerprint = hash( 'sha256', $projected_json );
					}
					$active_plugins_row = is_array( $database_snapshot ) ? ( $database_snapshot['options']['active_plugins'] ?? null ) : null;
					$active_plugins = is_array( $active_plugins_row )
						? maybe_unserialize( (string) ( $active_plugins_row['option_value'] ?? '' ) )
						: array();
					$current_active = is_array( $active_plugins ) && in_array( $config['plugin_file'], $active_plugins, true );
					$deployment_row = is_array( $database_snapshot ) ? ( $database_snapshot['options']['complete99_last_deployment_id'] ?? null ) : null;
					$database_version_row = is_array( $database_snapshot ) ? ( $database_snapshot['options']['complete99_platform_version'] ?? null ) : null;
					$current_deployment = is_array( $deployment_row ) ? (string) ( $deployment_row['option_value'] ?? '' ) : '';
					$current_database_version = is_array( $database_version_row ) ? (string) ( $database_version_row['option_value'] ?? '' ) : '';
					if ( '' === $current_deployment && $current_active && defined( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID' ) ) {
						$current_deployment = (string) COMPLETE99_PLATFORM_DEPLOYMENT_ID;
					}
					$robots_path = $managed_robots_path();
					if ( is_wp_error( $robots_path ) ) {
						return $robots_path;
					}
					$current_robots_sha256 = file_exists( $robots_path ) && ! is_link( $robots_path ) && ! is_dir( $robots_path )
						? (string) @hash_file( 'sha256', $robots_path )
						: '';
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
					$status_expected_version = (string) ( $state['expected_version'] ?? $state['installed_version'] ?? $lock['expected_version'] ?? '' );
					$runtime_version = defined( 'COMPLETE99_PLATFORM_VERSION' ) ? (string) COMPLETE99_PLATFORM_VERSION : '';
					$runtime_loaded = '' !== $status_expected_version
						&& $status_expected_version === $runtime_version
						&& class_exists( 'Complete99_Platform', false )
						&& method_exists( 'Complete99_Platform', 'migration_failed' )
						&& method_exists( 'Complete99_Platform', 'assert_evaluation_catalog_invariants' )
						&& class_exists( 'Complete99_Ops', false )
						&& method_exists( 'Complete99_Ops', 'assert_invariants' )
						&& class_exists( 'Complete99_Campaigns', false )
						&& method_exists( 'Complete99_Campaigns', 'assert_invariants' )
						&& class_exists( 'Complete99_Culinary_Science', false )
						&& method_exists( 'Complete99_Culinary_Science', 'assert_invariants' );
					$migration_failed = $runtime_loaded ? (bool) Complete99_Platform::migration_failed() : true;
					$migration_invariant_callbacks = array(
						'campaigns'         => array( 'Complete99_Campaigns', 'assert_invariants' ),
						'content'           => array( 'Complete99_Content', 'assert_migration_invariants' ),
						'culinary_science'  => array( 'Complete99_Culinary_Science', 'assert_invariants' ),
						'evaluation_catalog' => array( 'Complete99_Platform', 'assert_evaluation_catalog_invariants' ),
						'ops'               => array( 'Complete99_Ops', 'assert_invariants' ),
						'settings'          => array( 'Complete99_Settings', 'assert_defaults' ),
					);
					$migration_invariant_checks = array_fill_keys( array_keys( $migration_invariant_callbacks ), false );
					if ( $runtime_loaded && ! $migration_failed ) {
						foreach ( $migration_invariant_callbacks as $component => $callback ) {
							try {
								call_user_func( $callback );
								$migration_invariant_checks[ $component ] = true;
							} catch ( \Throwable $error ) {
								$migration_invariant_checks[ $component ] = false;
							}
						}
					}
					$migration_invariants_valid = ! in_array( false, $migration_invariant_checks, true );
					$campaign_operational = array(
						'cache_ready'                    => false,
						'capabilities_ready'             => false,
						'capacity_inspectable'           => false,
						'capacity_ready'                 => false,
						'capacity_write_ready'           => false,
						'cron_inspectable'               => false,
						'cron_ready'                     => false,
						'evidence_inspectable'           => false,
						'evidence_ready'                 => false,
						'ready'                          => false,
						'suppression_inspectable'        => false,
						'suppression_invalid'            => false,
						'suppression_ready'              => false,
						'suppression_recoverable_pending' => false,
					);
					$campaign_capacity_diagnostic = array(
						'campaign_cohort_inspectable'   => false,
						'fresh_install_empty'            => false,
						'lifecycle_reserve_inspectable'  => false,
						'operations_cohort_inspectable' => false,
						'prior_inactive_receipt_valid'   => false,
						'quarantine_reserve_inspectable' => false,
					);
					if ( $runtime_loaded && 'installed_pending_stabilization' === $phase && method_exists( 'Complete99_Ops', 'status_snapshot' ) ) {
						try {
							$ops_status = Complete99_Ops::status_snapshot();
							$campaign_status = is_array( $ops_status ) && is_array( $ops_status['campaigns'] ?? null ) ? $ops_status['campaigns'] : array();
							$capacity_status = is_array( $campaign_status['capacity'] ?? null ) ? $campaign_status['capacity'] : array();
							$cron_status = is_array( $campaign_status['cron_runner'] ?? null ) ? $campaign_status['cron_runner'] : array();
							$evidence_status = is_array( $campaign_status['evidence_recovery'] ?? null ) ? $campaign_status['evidence_recovery'] : array();
							$suppression_status = is_array( $campaign_status['public_suppression_backlog'] ?? null ) ? $campaign_status['public_suppression_backlog'] : array();
							$campaign_operational = array(
								'cache_ready'                    => true === ( $campaign_status['cache_ready'] ?? false ),
								'capabilities_ready'             => true === ( $campaign_status['capabilities_ready'] ?? false ),
								'capacity_inspectable'           => true === ( $capacity_status['inspectable'] ?? false ),
								'capacity_ready'                 => true === ( $capacity_status['ready'] ?? false ),
								'capacity_write_ready'           => true === ( $capacity_status['writeReady'] ?? false ),
								'cron_inspectable'               => true === ( $cron_status['inspectable'] ?? false ),
								'cron_ready'                     => true === ( $cron_status['ready'] ?? false ),
								'evidence_inspectable'           => true === ( $evidence_status['inspectable'] ?? false ),
								'evidence_ready'                 => true === ( $evidence_status['ready'] ?? false ),
								'ready'                          => true === ( $campaign_status['ready'] ?? false ),
								'suppression_inspectable'        => true === ( $suppression_status['inspectable'] ?? false ),
								'suppression_invalid'            => true === ( $suppression_status['invalid'] ?? false ),
								'suppression_ready'              => true === ( $suppression_status['ready'] ?? false ),
								'suppression_recoverable_pending' => true === ( $suppression_status['recoverablePending'] ?? false ),
							);
							$campaign_capacity_diagnostic['campaign_cohort_inspectable'] = is_array( $capacity_status['cohorts']['campaign'] ?? null );
							$campaign_capacity_diagnostic['operations_cohort_inspectable'] = is_array( $capacity_status['cohorts']['operations'] ?? null );
						} catch ( \Throwable $error ) {
							// Preserve the all-false bounded projection on diagnostic failure.
						}
					}
					$campaign_lifecycle = array( 'canonical' => false, 'generation' => 0, 'state' => '' );
					$lifecycle_row = is_array( $database_snapshot ) ? ( $database_snapshot['options']['complete99_campaign_lifecycle_reservation_v1'] ?? null ) : null;
					if ( $campaign_lifecycle_reservation_valid( $lifecycle_row ) ) {
						$lifecycle_payload = json_decode( (string) $lifecycle_row['option_value'], true );
						if ( is_array( $lifecycle_payload ) ) {
							$campaign_lifecycle = array(
								'canonical'  => true,
								'generation' => (int) ( $lifecycle_payload['generation'] ?? 0 ),
								'state'      => (string) ( $lifecycle_payload['state'] ?? '' ),
							);
						}
					}
					if ( $runtime_loaded && 'installed_pending_stabilization' === $phase && $campaign_lifecycle['canonical'] ) {
						$invoke_campaign_private = static function ( $method, $arguments = array() ) {
							try {
								$reflection = new \ReflectionMethod( 'Complete99_Campaigns', (string) $method );
								$reflection->setAccessible( true );
								return array( 'invoked' => true, 'value' => $reflection->invokeArgs( null, array_values( (array) $arguments ) ) );
							} catch ( \Throwable $error ) {
								return array( 'invoked' => false, 'value' => null );
							}
						};
						$lifecycle_reserve = $invoke_campaign_private( 'lifecycle_capacity_reservation' );
						$campaign_capacity_diagnostic['lifecycle_reserve_inspectable'] = ! empty( $lifecycle_reserve['invoked'] ) && is_array( $lifecycle_reserve['value'] ) && ! is_wp_error( $lifecycle_reserve['value'] );
						$quarantine_reserve = $invoke_campaign_private( 'public_quarantine_capacity_reservation' );
						$campaign_capacity_diagnostic['quarantine_reserve_inspectable'] = ! empty( $quarantine_reserve['invoked'] ) && is_array( $quarantine_reserve['value'] ) && ! is_wp_error( $quarantine_reserve['value'] );
						if ( 1 < $campaign_lifecycle['generation'] ) {
							$prior_receipt = $invoke_campaign_private( 'stored_lifecycle_receipt', array( $campaign_lifecycle['generation'] - 1, 'inactive', false ) );
							$campaign_capacity_diagnostic['prior_inactive_receipt_valid'] = ! empty( $prior_receipt['invoked'] ) && is_array( $prior_receipt['value'] ) && ! is_wp_error( $prior_receipt['value'] );
						}
						$sentinel = $invoke_campaign_private( 'public_quarantine_placement_id' );
						$campaign_rows = is_array( $database_snapshot['campaign_tables']['campaigns']['rows'] ?? null ) ? $database_snapshot['campaign_tables']['campaigns']['rows'] : null;
						$placement_rows = is_array( $database_snapshot['campaign_tables']['placements']['rows'] ?? null ) ? $database_snapshot['campaign_tables']['placements']['rows'] : null;
						if ( ! empty( $sentinel['invoked'] ) && is_string( $sentinel['value'] ) && is_array( $campaign_rows ) && is_array( $placement_rows ) ) {
							$non_sentinel = array_values( array_filter( $placement_rows, static fn( $row ) => ! is_array( $row ) || ! hash_equals( (string) $sentinel['value'], (string) ( $row['placement_id'] ?? '' ) ) ) );
							$campaign_capacity_diagnostic['fresh_install_empty'] = empty( $campaign_rows ) && empty( $non_sentinel );
						}
					}
					$baseline_database_snapshot = $rollback_journal_status
						? $decrypt_database_state( $state['database_journal'] ?? array() )
						: array();
					$baseline_database_json = $rollback_journal_status && ! is_wp_error( $baseline_database_snapshot )
						? wp_json_encode( $baseline_database_snapshot )
						: false;
					$baseline_database_journal_valid = $rollback_journal_status
						&& is_array( $baseline_database_snapshot )
						&& false !== $baseline_database_json
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $state['database_fingerprint'] ?? '' ) )
						&& hash_equals( (string) $state['database_fingerprint'], hash( 'sha256', $baseline_database_json ) )
						&& true === ( $baseline_database_snapshot['sync_secret_existed'] ?? null )
						&& true === ( $baseline_database_snapshot['sync_secret_configured'] ?? null );
					$swap_suffix = substr( hash( 'sha256', $deployment_id ), 0, 20 );
					$rollback_filesystem_artifacts = array(
						trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix,
						trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix,
						trailingslashit( $state_dir ) . 'robots.forward',
						trailingslashit( $state_dir ) . 'robots.rollback-prior',
					);
					$rollback_filesystem_artifacts_exist = false;
					foreach ( $rollback_filesystem_artifacts as $rollback_artifact ) {
						if ( file_exists( $rollback_artifact ) || is_link( $rollback_artifact ) ) {
							$rollback_filesystem_artifacts_exist = true;
							break;
						}
					}
					$ops_rollback_residue = $ops_quarantine_residue();
					if ( is_wp_error( $ops_rollback_residue ) ) {
						return $ops_rollback_residue;
					}
					$no_rollback_artifacts = empty( $state['rollback_applied'] )
						&& empty( $state['database_restored'] )
						&& empty( $state['rollback_compensated'] )
						&& empty( $state['rollback_compensation_error'] )
						&& empty( $state['robots_restored'] )
						&& ! $rollback_filesystem_artifacts_exist
						&& empty( $ops_rollback_residue );
					$robots_forward_ready = ! empty( $state['robots_applied'] )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $state['robots_managed_sha256'] ?? '' ) )
						&& hash_equals( (string) $state['robots_managed_sha256'], $current_robots_sha256 );
					$legacy_clean_installed = 'installed' === $phase
						&& empty( $state['stabilized'] )
						&& ! empty( $state['temp_removed'] )
						&& '' === (string) ( $state['temp_path'] ?? '' )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					$clean_pending_stabilization = 'installed_pending_stabilization' === $phase
						&& ! empty( $state['forward_ready'] )
						&& ! empty( $state['temp_removed'] )
						&& '' === (string) ( $state['temp_path'] ?? '' )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					$clean_pending_cleanup = 'installed_pending_cleanup' === $phase
						&& ! empty( $state['forward_ready'] )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					$forward_stabilization_candidate = $legacy_clean_installed
						|| $clean_pending_stabilization
						|| $clean_pending_cleanup;
					$status_interrupted_config = is_array( $config['interrupted_forward'] ?? null )
						? $config['interrupted_forward']
						: array();
					$interrupted_forward_candidate = $interrupted_installing_status
						&& $recovery_ready
						&& $runtime_loaded
						&& ! $migration_failed
						&& $migration_invariants_valid
						&& $baseline_database_journal_valid
						&& $no_rollback_artifacts
						&& $current_dir_exists
						&& $current_main_exists
						&& $current_active
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['proof_sha256'] ?? '' ) )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['expected_artifact_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['expected_artifact_sha256'], (string) ( $state['expected_sha256'] ?? '' ) )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['expected_plugin_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['expected_plugin_sha256'], $current_plugin_sha256 )
						&& (string) ( $status_interrupted_config['expected_version'] ?? '' ) === (string) ( $state['expected_version'] ?? '' )
						&& (string) ( $status_interrupted_config['expected_version'] ?? '' ) === (string) ( $current['Version'] ?? '' )
						&& (string) ( $status_interrupted_config['expected_version'] ?? '' ) === $current_database_version
						&& $deployment_id === $current_deployment
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['reviewed_database_fingerprint'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['reviewed_database_fingerprint'], $database_fingerprint )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['reviewed_database_manifest_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['reviewed_database_manifest_sha256'], is_array( $database_manifest_record ) ? (string) ( $database_manifest_record['manifest_sha256'] ?? '' ) : '' )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['prior_database_fingerprint'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['prior_database_fingerprint'], (string) ( $state['database_fingerprint'] ?? '' ) )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['prior_plugin_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['prior_plugin_sha256'], (string) ( $state['prior_plugin_sha256'] ?? '' ) )
						&& (string) ( $status_interrupted_config['prior_deployment'] ?? '' ) === (string) ( $state['prior_deployment'] ?? '' )
						&& (string) ( $status_interrupted_config['prior_version'] ?? '' ) === (string) ( $state['prior_version'] ?? '' )
						&& true === ( $state['had_plugin'] ?? null )
						&& true === ( $state['prior_target_dir_exists'] ?? null )
						&& true === ( $state['prior_plugin_main_exists'] ?? null )
						&& true === ( $state['was_active'] ?? null )
						&& true === ( $state['robots_prior_exists'] ?? null )
						&& true === ( $state['robots_applied'] ?? null )
						&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $status_interrupted_config['prior_robots_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['prior_robots_sha256'], (string) ( $state['robots_prior_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['prior_robots_sha256'], (string) ( $state['robots_managed_sha256'] ?? '' ) )
						&& hash_equals( (string) $status_interrupted_config['prior_robots_sha256'], $current_robots_sha256 )
						&& is_array( $database_storage )
						&& 3 === (int) ( $database_storage['tables'] ?? 0 )
						&& '' !== (string) ( $database_storage['engine'] ?? '' )
						&& is_array( $database_snapshot )
						&& true === ( $database_snapshot['sync_secret_existed'] ?? null )
						&& true === ( $database_snapshot['sync_secret_configured'] ?? null );
					$status = array(
						'deployment_id'    => $deployment_id,
						'phase'            => $phase,
						'state_exists'     => $wp_filesystem->exists( $state_file ),
						'lock_owned'       => $lock_owned,
						'lock_age_seconds' => $lock_age,
						'recovery_lease_seconds'=> (int) $config['recovery_lease_seconds'],
						'recovery_ready'   => $recovery_ready,
						'runtime_loaded'   => $runtime_loaded,
						'runtime_version'  => $runtime_version,
						'migration_failed' => $migration_failed,
						'migration_invariant_checks' => $migration_invariant_checks,
						'migration_invariants_valid'=> $migration_invariants_valid,
						'campaign_operational' => $campaign_operational,
						'campaign_capacity_diagnostic' => $campaign_capacity_diagnostic,
						'campaign_lifecycle'   => $campaign_lifecycle,
						'no_rollback_artifacts'=> $no_rollback_artifacts,
						'ops_rollback_residue_present'=> ! empty( $ops_rollback_residue ),
						'ops_rollback_residue_count'=> count( $ops_rollback_residue ),
						'baseline_database_journal_valid'=> $baseline_database_journal_valid,
						'baseline_sync_secret_existed'=> $interrupted_installing_status && true === ( $baseline_database_snapshot['sync_secret_existed'] ?? null ),
						'baseline_sync_configured'=> $interrupted_installing_status && true === ( $baseline_database_snapshot['sync_secret_configured'] ?? null ),
						'interrupted_forward_candidate'=> $interrupted_forward_candidate,
						'forward_stabilization_candidate'=> $forward_stabilization_candidate,
						'stabilized'      => ! empty( $state['stabilized'] ?? $lock['stabilized'] ?? false ),
						'forward_ready'   => ! empty( $state['forward_ready'] ?? $lock['forward_ready'] ?? false ),
						'adopted_forward_no_rollback'=> ! empty( $state['adopted_forward_no_rollback'] ?? $lock['adopted_forward_no_rollback'] ?? false ),
						'interrupted_forward_proof_sha256'=> (string) ( $state['interrupted_forward_proof_sha256'] ?? $lock['interrupted_forward_proof_sha256'] ?? '' ),
						'interrupted_forward_database_manifest_sha256'=> (string) ( $state['interrupted_forward_database_manifest_sha256'] ?? $lock['interrupted_forward_database_manifest_sha256'] ?? '' ),
						'process_lock_available'=> $process_available,
						'expected_sha256'  => (string) ( $state['expected_sha256'] ?? $lock['expected_sha256'] ?? '' ),
						'expected_version' => (string) ( $state['expected_version'] ?? $state['installed_version'] ?? $lock['expected_version'] ?? '' ),
						'installed_plugin_sha256'=> (string) ( $state['installed_plugin_sha256'] ?? $lock['installed_plugin_sha256'] ?? '' ),
						'candidate_activation_required'=> ! empty( $state['candidate_activation_required'] ?? $lock['candidate_activation_required'] ?? false ),
						'candidate_activation_phase'=> (string) ( $state['candidate_activation_phase'] ?? $lock['candidate_activation_phase'] ?? '' ),
						'candidate_activation_completed_at'=> isset( $state['candidate_activation_completed_at'] ) ? (int) $state['candidate_activation_completed_at'] : 0,
						'candidate_database_fingerprint'=> (string) ( $state['candidate_database_fingerprint'] ?? $lock['candidate_database_fingerprint'] ?? '' ),
						'candidate_requested_active'=> ! empty( $state['candidate_requested_active'] ?? $lock['candidate_requested_active'] ?? false ),
						'candidate_prior_active'=> ! empty( $state['candidate_prior_active'] ?? $lock['candidate_prior_active'] ?? false ),
						'committed_outcome'=> (string) ( $state['committed_outcome'] ?? $lock['committed_outcome'] ?? '' ),
						'committed_expected_active'=> (bool) ( $state['committed_expected_active'] ?? $lock['committed_expected_active'] ?? false ),
						'committed_expected_absent'=> (bool) ( $state['committed_expected_absent'] ?? $lock['committed_expected_absent'] ?? false ),
						'committed_expected_version'=> (string) ( $state['committed_expected_version'] ?? $lock['committed_expected_version'] ?? '' ),
						'committed_expected_deployment'=> (string) ( $state['committed_expected_deployment'] ?? $lock['committed_expected_deployment'] ?? '' ),
						'committed_expected_plugin_sha256'=> (string) ( $state['committed_expected_plugin_sha256'] ?? $lock['committed_expected_plugin_sha256'] ?? '' ),
						'committed_expected_database_fingerprint'=> (string) ( $state['committed_expected_database_fingerprint'] ?? $lock['committed_expected_database_fingerprint'] ?? '' ),
						'committed_expected_robots_exists'=> (bool) ( $state['committed_expected_robots_exists'] ?? $lock['committed_expected_robots_exists'] ?? false ),
						'committed_expected_robots_sha256'=> (string) ( $state['committed_expected_robots_sha256'] ?? $lock['committed_expected_robots_sha256'] ?? '' ),
						'committed_expected_sync_configured'=> (bool) ( $state['committed_expected_sync_configured'] ?? $lock['committed_expected_sync_configured'] ?? false ),
						'orphaned_recovery_proof_sha256'=> (string) ( $lock['orphaned_recovery_proof_sha256'] ?? '' ),
						'orphaned_recovery_receipt_sha256'=> (string) ( $lock['orphaned_recovery_receipt_sha256'] ?? '' ),
						'orphaned_recovery_evidence_exists'=> (bool) ( $lock['orphaned_recovery_evidence_exists'] ?? false ),
						'orphaned_recovery_evidence_sha256'=> (string) ( $lock['orphaned_recovery_evidence_sha256'] ?? '' ),
						'orphaned_reconciled_from'=> (string) ( $lock['orphaned_reconciled_from'] ?? '' ),
						'orphaned_observed_deployment'=> (string) ( $lock['orphaned_observed_deployment'] ?? '' ),
						'temp_removed'     => ! empty( $state['temp_removed'] ),
						'had_plugin'       => ! empty( $state['had_plugin'] ?? $lock['had_plugin'] ?? false ),
						'prior_target_dir_exists' => ! empty( $state['prior_target_dir_exists'] ?? $lock['prior_target_dir_exists'] ?? false ),
						'prior_plugin_main_exists'=> ! empty( $state['prior_plugin_main_exists'] ?? $lock['prior_plugin_main_exists'] ?? false ),
						'prior_plugin_sha256'=> (string) ( $state['prior_plugin_sha256'] ?? $lock['prior_plugin_sha256'] ?? '' ),
						'prior_version'    => (string) ( $state['prior_version'] ?? $lock['prior_version'] ?? '' ),
						'prior_active'     => ! empty( $state['was_active'] ?? $lock['was_active'] ?? false ),
						'prior_deployment' => (string) ( $state['prior_deployment'] ?? $lock['prior_deployment'] ?? '' ),
						'current_version'  => isset( $current['Version'] ) ? (string) $current['Version'] : '',
						'current_target_dir_exists' => $current_dir_exists,
						'current_plugin_main_exists'=> $current_main_exists,
						'current_plugin_sha256'=> $current_plugin_sha256,
						'current_active'   => $current_active,
						'current_deployment'=> $current_deployment,
						'current_database_version'=> $current_database_version,
						'database_restored' => ! empty( $state['database_restored'] ),
						'baseline_database_fingerprint'=> (string) ( $state['database_fingerprint'] ?? '' ),
						'post_install_database_fingerprint'=> (string) ( $state['post_install_database_fingerprint'] ?? $lock['post_install_database_fingerprint'] ?? '' ),
						'database_fingerprint'=> $database_fingerprint,
						'database_fingerprint_available'=> false !== $database_json,
						'database_manifest'=> is_array( $database_manifest_record ) ? ( $database_manifest_record['manifest'] ?? array() ) : array(),
						'database_manifest_sha256'=> is_array( $database_manifest_record ) ? (string) ( $database_manifest_record['manifest_sha256'] ?? '' ) : '',
						'database_storage'=> $database_storage,
						'projected_deployment_id'=> $projected_deployment_id,
						'projected_database_fingerprint'=> $projected_database_fingerprint,
						'current_sync_configured'=> is_array( $database_snapshot ) && ! empty( $database_snapshot['sync_secret_configured'] ),
						'sync_configuration_pending'=> ! empty( $state['sync_configuration_pending'] ),
						'sync_configuration_checkpointed'=> ! empty( $state['sync_configuration_checkpointed'] ),
						'robots_applied'    => ! empty( $state['robots_applied'] ?? $lock['robots_applied'] ?? false ),
						'robots_restored'   => ! empty( $state['robots_restored'] ?? $lock['robots_restored'] ?? false ),
						'robots_prior_exists'=> ! empty( $state['robots_prior_exists'] ?? $lock['robots_prior_exists'] ?? false ),
						'robots_prior_sha256'=> (string) ( $state['robots_prior_sha256'] ?? $lock['robots_prior_sha256'] ?? '' ),
						'robots_managed_sha256'=> (string) ( $state['robots_managed_sha256'] ?? $lock['robots_managed_sha256'] ?? '' ),
						'current_robots_sha256'=> $current_robots_sha256,
						'site_identity'      => $site_identity,
					);
					if ( $orphaned_consistent_status ) {
						$status = array_merge(
							$status,
							array(
								'orphaned_recovery_receipt_schema'=> (string) $lock['orphaned_recovery_receipt_schema'],
								'orphaned_reconciliation_mode'=> (string) ( $lock['orphaned_reconciliation_mode'] ?? '' ),
								'orphaned_prior_proof_sha256'=> (string) ( $lock['orphaned_prior_proof_sha256'] ?? '' ),
								'orphaned_attestation_run_id'=> isset( $lock['orphaned_attestation_run_id'] ) ? (int) $lock['orphaned_attestation_run_id'] : null,
								'orphaned_attestation_sha256'=> (string) ( $lock['orphaned_attestation_sha256'] ?? '' ),
								'orphaned_attestation_audit_sha256'=> (string) ( $lock['orphaned_attestation_audit_sha256'] ?? '' ),
								'orphaned_attestation_source_commit'=> (string) ( $lock['orphaned_attestation_source_commit'] ?? '' ),
								'orphaned_historical_baseline_database_fingerprint'=> (string) ( $lock['orphaned_historical_baseline_database_fingerprint'] ?? '' ),
								'orphaned_observed_database_fingerprint'=> (string) ( $lock['orphaned_observed_database_fingerprint'] ?? '' ),
								'orphaned_preserved_manifest_sha256'=> (string) ( $lock['orphaned_preserved_manifest_sha256'] ?? '' ),
								'orphaned_marker_rows_affected'=> isset( $lock['orphaned_marker_rows_affected'] ) ? (int) $lock['orphaned_marker_rows_affected'] : null,
								'orphaned_marker_transition'=> (string) ( $lock['orphaned_marker_transition'] ?? '' ),
							)
						);
					}
					return $status;
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/attest-interrupted-finalized',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $read_lock, $acquire_process_lock, $release_process_lock, $directory_sha256, $capture_database_state_consistent, $database_snapshot_manifest, $database_snapshot_manifest_valid, $verify_transactional_storage, $managed_robots_path, $managed_robots_contents, $canonicalize_json_value ) {
					global $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$json_params = $request->get_json_params();
					$request_keys = is_array( $json_params ) ? array_keys( $json_params ) : array();
					sort( $request_keys, SORT_STRING );
					if ( array( 'deployment_id', 'interrupted_forward_proof_sha256', 'token' ) !== $request_keys ) {
						return new WP_Error( 'c99_interrupted_finalized_request', 'Finalized attestation accepts only its exact proof request.', array( 'status' => 400 ) );
					}
					$probe_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					$request_proof_sha256 = strtolower( sanitize_text_field( (string) $request->get_param( 'interrupted_forward_proof_sha256' ) ) );
					$interrupted = is_array( $config['interrupted_forward'] ?? null ) ? $config['interrupted_forward'] : array();
					$target_deployment_id = (string) ( $interrupted['target_deployment_id'] ?? '' );
					$expected_version = (string) ( $interrupted['expected_version'] ?? '' );
					$expected_plugin_sha256 = (string) ( $interrupted['expected_plugin_sha256'] ?? '' );
					$expected_database_fingerprint = (string) ( $interrupted['reviewed_database_fingerprint'] ?? '' );
					$expected_manifest = $interrupted['reviewed_database_manifest'] ?? null;
					$expected_manifest_sha256 = (string) ( $interrupted['reviewed_database_manifest_sha256'] ?? '' );
					$expected_storage = $interrupted['reviewed_database_storage'] ?? null;
					$expected_robots_sha256 = (string) ( $interrupted['prior_robots_sha256'] ?? '' );
					$pending_repair = 'complete99-interrupted-forward-adoption/v4' === (string) ( $interrupted['adoption_schema'] ?? '' );
					$storage_keys = is_array( $expected_storage ) ? array_keys( $expected_storage ) : array();
					sort( $storage_keys, SORT_STRING );
					$digest_keys = array(
						'expected_artifact_sha256',
						'expected_plugin_sha256',
						'proof_sha256',
						'reviewed_database_fingerprint',
						'reviewed_database_manifest_sha256',
						'prior_database_fingerprint',
						'prior_plugin_sha256',
						'prior_robots_sha256',
					);
					$config_valid = true;
					foreach ( $digest_keys as $digest_key ) {
						$config_valid = $config_valid && preg_match( '/^[a-f0-9]{64}$/', (string) ( $interrupted[ $digest_key ] ?? '' ) );
					}
					$config_valid = $config_valid
						&& true === ( $interrupted['finalized_attestation_enabled'] ?? null )
						&& $config['deployment_id'] === $probe_id
						&& preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $probe_id )
						&& preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $target_deployment_id )
						&& str_starts_with( $target_deployment_id, 'c99-' )
						&& $target_deployment_id !== $probe_id
						&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', $expected_version )
						&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', (string) ( $interrupted['prior_version'] ?? '' ) )
						&& preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', (string) ( $interrupted['prior_deployment'] ?? '' ) )
						&& is_array( $expected_manifest )
						&& ! empty( $expected_manifest )
						&& $database_snapshot_manifest_valid( $expected_manifest, $expected_manifest_sha256 )
						&& array( 'engine', 'tables' ) === $storage_keys
						&& in_array( (string) ( $expected_storage['engine'] ?? '' ), array( 'INNODB', 'XTRADB', 'INNODB,XTRADB' ), true )
						&& 3 === ( $expected_storage['tables'] ?? null );
					if ( ! $config_valid ) {
						return new WP_Error( 'c99_interrupted_finalized_disabled', 'Finalized attestation requires the exact embedded reviewed v2 identities.', array( 'status' => 403 ) );
					}
					if (
						! preg_match( '/^[a-f0-9]{64}$/', $request_proof_sha256 )
						|| ! hash_equals( (string) $interrupted['proof_sha256'], $request_proof_sha256 )
					) {
						return new WP_Error( 'c99_interrupted_finalized_proof', 'Finalized attestation proof does not match this single-use bridge.', array( 'status' => 403 ) );
					}

					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
						$probe_lock = $read_lock( true );
						$probe_updated_at = (int) ( $probe_lock['updated_at'] ?? $probe_lock['started_at'] ?? 0 );
						$probe_lock_valid = is_array( $probe_lock )
							&& $probe_id === (string) ( $probe_lock['deployment_id'] ?? '' )
							&& 'reserved' === (string) ( $probe_lock['phase'] ?? '' )
							&& '' !== (string) ( $probe_lock['owner_id'] ?? '' )
							&& 0 < (int) ( $probe_lock['fence'] ?? 0 )
							&& 0 < $probe_updated_at
							&& max( 0, time() - $probe_updated_at ) < (int) $config['recovery_lease_seconds'];
						if ( ! $probe_lock_valid ) {
							return new WP_Error( 'c99_interrupted_finalized_probe_lock', 'Finalized attestation requires the exact fresh reserved probe lock.', array( 'status' => 409 ) );
						}

						$target_state_dir = $state_directory( $target_deployment_id );
						$target_state_file = trailingslashit( $target_state_dir ) . 'state.json';
						$swap_suffix = substr( hash( 'sha256', $target_deployment_id ), 0, 20 );
						$target_artifacts = array(
							trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix,
							trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix,
							trailingslashit( $target_state_dir ) . 'robots.forward',
							trailingslashit( $target_state_dir ) . 'robots.rollback-prior',
							trailingslashit( $target_state_dir ) . 'robots.prior-live',
						);
						$target_state_absent = ! file_exists( $target_state_dir ) && ! is_link( $target_state_dir ) && ! is_dir( $target_state_dir )
							&& ! file_exists( $target_state_file ) && ! is_link( $target_state_file ) && ! is_dir( $target_state_file );
						$target_artifacts_absent = true;
						foreach ( $target_artifacts as $target_artifact ) {
							if ( file_exists( $target_artifact ) || is_link( $target_artifact ) || is_dir( $target_artifact ) ) {
								$target_artifacts_absent = false;
								break;
							}
						}
						if ( ! $target_state_absent || ! $target_artifacts_absent || $target_deployment_id === (string) ( $probe_lock['deployment_id'] ?? '' ) ) {
							return new WP_Error( 'c99_interrupted_finalized_target_residue', 'Finalized attestation refused target state, lock, or rollback residue.', array( 'status' => 409 ) );
						}

						require_once ABSPATH . 'wp-admin/includes/plugin.php';
						$target_dir = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
						$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
						$current_plugin_sha256 = ! is_link( $target_dir ) && $wp_filesystem->is_dir( $target_dir )
							? $directory_sha256( $target_dir )
							: new WP_Error( 'c99_interrupted_finalized_plugin_path' );
						clearstatcache( true, $plugin_path );
						$current_plugin = ! is_link( $plugin_path ) && ! is_dir( $plugin_path ) && $wp_filesystem->exists( $plugin_path )
							? get_plugin_data( $plugin_path, false, false )
							: array();
						$runtime_loaded = defined( 'COMPLETE99_PLATFORM_VERSION' )
							&& $expected_version === (string) COMPLETE99_PLATFORM_VERSION
							&& class_exists( 'Complete99_Platform', false )
							&& method_exists( 'Complete99_Platform', 'migration_failed' )
							&& method_exists( 'Complete99_Platform', 'assert_evaluation_catalog_invariants' )
						&& class_exists( 'Complete99_Ops', false )
						&& method_exists( 'Complete99_Ops', 'assert_invariants' )
						&& class_exists( 'Complete99_Campaigns', false )
						&& method_exists( 'Complete99_Campaigns', 'assert_invariants' )
						&& class_exists( 'Complete99_Culinary_Science', false )
							&& method_exists( 'Complete99_Culinary_Science', 'assert_invariants' );
						$migration_failed = ! $runtime_loaded || (bool) Complete99_Platform::migration_failed();
						$migration_invariants_valid = false;
						if ( $runtime_loaded && ! $migration_failed ) {
							try {
								Complete99_Content::assert_migration_invariants();
								Complete99_Settings::assert_defaults();
								Complete99_Platform::assert_evaluation_catalog_invariants();
								Complete99_Ops::assert_invariants();
								if ( ! $pending_repair ) {
									Complete99_Campaigns::assert_invariants();
								}
								Complete99_Culinary_Science::assert_invariants();
								$migration_invariants_valid = true;
							} catch ( \Throwable $error ) {
								$migration_invariants_valid = false;
							}
						}
						if (
							is_wp_error( $current_plugin_sha256 )
							|| ! hash_equals( $expected_plugin_sha256, (string) $current_plugin_sha256 )
							|| $expected_version !== (string) ( $current_plugin['Version'] ?? '' )
							|| ! is_plugin_active( $config['plugin_file'] )
							|| ! $runtime_loaded
							|| $migration_failed
							|| ! $migration_invariants_valid
						) {
							return new WP_Error( 'c99_interrupted_finalized_plugin', 'Finalized attestation could not prove the exact active plugin and runtime.', array( 'status' => 409 ) );
						}

						$current_storage = $verify_transactional_storage();
						$current_snapshot = $capture_database_state_consistent();
						$current_database_json = is_wp_error( $current_snapshot ) ? false : wp_json_encode( $current_snapshot );
						$current_database_fingerprint = false === $current_database_json ? '' : hash( 'sha256', $current_database_json );
						$current_manifest_record = is_wp_error( $current_snapshot ) ? $current_snapshot : $database_snapshot_manifest( $current_snapshot );
						$current_manifest = is_array( $current_manifest_record ) ? ( $current_manifest_record['manifest'] ?? null ) : null;
						$current_manifest_sha256 = is_array( $current_manifest_record ) ? (string) ( $current_manifest_record['manifest_sha256'] ?? '' ) : '';
						$active_plugins_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['active_plugins'] ?? null ) : null;
						$active_plugins = is_array( $active_plugins_row ) ? maybe_unserialize( (string) ( $active_plugins_row['option_value'] ?? '' ) ) : null;
						$marker_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_last_deployment_id'] ?? null ) : null;
						$version_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_platform_version'] ?? null ) : null;
						$database_valid = ! is_wp_error( $current_storage )
							&& is_array( $current_storage )
							&& (string) ( $current_storage['engine'] ?? '' ) === (string) $expected_storage['engine']
							&& ( $current_storage['tables'] ?? null ) === $expected_storage['tables']
							&& ! is_wp_error( $current_snapshot )
							&& false !== $current_database_json
							&& hash_equals( $expected_database_fingerprint, $current_database_fingerprint )
							&& is_array( $current_manifest_record )
							&& $database_snapshot_manifest_valid( $current_manifest, $current_manifest_sha256 )
							&& hash_equals( $expected_manifest_sha256, $current_manifest_sha256 )
							&& $canonicalize_json_value( $expected_manifest ) === $canonicalize_json_value( $current_manifest )
							&& true === ( $current_snapshot['sync_secret_existed'] ?? null )
							&& true === ( $current_snapshot['sync_secret_configured'] ?? null )
							&& is_array( $active_plugins )
							&& in_array( $config['plugin_file'], $active_plugins, true )
							&& is_array( $marker_row )
							&& $target_deployment_id === (string) ( $marker_row['option_value'] ?? '' )
							&& is_array( $version_row )
							&& $expected_version === (string) ( $version_row['option_value'] ?? '' );
						if ( ! $database_valid ) {
							return new WP_Error( 'c99_interrupted_finalized_database', 'Finalized attestation could not prove the exact pre-commerce database.', array( 'status' => 409 ) );
						}

						$robots_path = $managed_robots_path();
						$current_robots_sha256 = ! is_wp_error( $robots_path ) && ! is_link( $robots_path ) && ! is_dir( $robots_path ) && file_exists( $robots_path )
							? (string) @hash_file( 'sha256', $robots_path )
							: '';
						$managed_robots_sha256 = hash( 'sha256', $managed_robots_contents() );
						if (
							! hash_equals( $expected_robots_sha256, $managed_robots_sha256 )
							|| ! hash_equals( $expected_robots_sha256, $current_robots_sha256 )
						) {
							return new WP_Error( 'c99_interrupted_finalized_robots', 'Finalized attestation could not prove the exact managed robots file.', array( 'status' => 409 ) );
						}

						$post_plugin_sha256 = $directory_sha256( $target_dir );
						clearstatcache( true, $plugin_path );
						$post_plugin = ! is_link( $plugin_path ) && ! is_dir( $plugin_path ) && $wp_filesystem->exists( $plugin_path )
							? get_plugin_data( $plugin_path, false, false )
							: array();
						clearstatcache( true, $robots_path );
						$post_robots_sha256 = ! is_wp_error( $robots_path ) && ! is_link( $robots_path ) && ! is_dir( $robots_path ) && file_exists( $robots_path )
							? (string) @hash_file( 'sha256', $robots_path )
							: '';
						$post_probe_lock = $read_lock( true );
						if (
							$post_probe_lock !== $probe_lock
							|| is_wp_error( $post_plugin_sha256 )
							|| ! hash_equals( $expected_plugin_sha256, (string) $post_plugin_sha256 )
							|| $expected_version !== (string) ( $post_plugin['Version'] ?? '' )
							|| ! is_plugin_active( $config['plugin_file'] )
							|| ! hash_equals( $expected_robots_sha256, $post_robots_sha256 )
						) {
							return new WP_Error( 'c99_interrupted_finalized_race', 'Finalized attestation identity changed during observation.', array( 'status' => 409 ) );
						}
						return array(
							'schema'                    => 'complete99-interrupted-forward-finalized-attestation/v1',
							'already_finalized'         => true,
							'proof_sha256'              => $request_proof_sha256,
							'probe_deployment_id'       => $probe_id,
							'finalized_deployment_id'   => $target_deployment_id,
							'version'                   => $expected_version,
							'plugin_sha256'             => (string) $post_plugin_sha256,
							'database_fingerprint'      => $current_database_fingerprint,
							'database_manifest'         => $current_manifest,
							'database_manifest_sha256'  => $current_manifest_sha256,
							'database_storage'          => $current_storage,
							'current_deployment'        => (string) ( $marker_row['option_value'] ?? '' ),
							'current_database_version'  => (string) ( $version_row['option_value'] ?? '' ),
							'active'                    => true,
							'runtime_loaded'            => true,
							'migration_failed'          => false,
							'migration_invariants_valid'=> ! $pending_repair,
							'sync_configured'           => true,
							'robots_sha256'             => $post_robots_sha256,
							'target_state_absent'       => true,
							'target_artifacts_absent'   => true,
							'probe_lock_phase'          => 'reserved',
						);
					} finally {
						$release_process_lock( $process_lock );
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/stabilize',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $cleanup_staging, $state_directory, $purge_caches, $read_lock, $claim_lock, $acquire_process_lock, $release_process_lock, $adopt_state_lease, $set_state_phase, $directory_sha256, $capture_database_state, $capture_database_state_consistent, $database_snapshot_manifest, $database_snapshot_manifest_valid, $verify_transactional_storage, $decrypt_database_state, $managed_robots_path, $managed_robots_contents, $canonicalize_json_value ) {
					global $wpdb, $wp_filesystem;
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
					$pre_interrupted_config = is_array( $config['interrupted_forward'] ?? null ) ? $config['interrupted_forward'] : array();
					$pre_interrupted_proof = (string) $request->get_param( 'interrupted_forward_proof_sha256' );
					$pending_repair_request = '' !== $pre_interrupted_proof
						&& 'complete99-interrupted-forward-adoption/v4' === (string) ( $pre_interrupted_config['adoption_schema'] ?? '' );
					if ( $pending_repair_request ) {
						$reviewed_safe_status = $pre_interrupted_config['reviewed_safe_status'] ?? null;
						$reviewed_safe_status_sha256 = (string) ( $pre_interrupted_config['reviewed_safe_status_sha256'] ?? '' );
						$reviewed_safe_json = is_array( $reviewed_safe_status )
							? wp_json_encode( $canonicalize_json_value( $reviewed_safe_status ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
							: false;
						if (
							! is_array( $reviewed_safe_status )
							|| false === $reviewed_safe_json
							|| ! preg_match( '/^[a-f0-9]{64}$/', $reviewed_safe_status_sha256 )
							|| ! hash_equals( $reviewed_safe_status_sha256, hash( 'sha256', $reviewed_safe_json ) )
							|| ! preg_match( '/^[a-f0-9]{64}$/', $pre_interrupted_proof )
							|| ! hash_equals( (string) ( $pre_interrupted_config['proof_sha256'] ?? '' ), $pre_interrupted_proof )
						) {
							return new WP_Error( 'c99_stabilize_pending_review_config', 'Pending-stabilization repair lacks its exact reviewed status.', array( 'status' => 409 ) );
						}
						$status_request = new WP_REST_Request( 'POST', '/complete99-deploy/v1/' . $deployment_id . '/status' );
						$status_request->set_param( 'token', (string) $request->get_param( 'token' ) );
						$status_request->set_param( 'deployment_id', $deployment_id );
						$status_request->set_param( 'projected_deployment_id', $deployment_id );
						$status_response = rest_do_request( $status_request );
						$status_data = $status_response instanceof WP_REST_Response ? $status_response->get_data() : null;
						$live_safe_status = array();
						if ( is_array( $status_data ) ) {
							foreach ( array_keys( $reviewed_safe_status ) as $reviewed_status_key ) {
								if ( ! array_key_exists( $reviewed_status_key, $status_data ) ) {
									$live_safe_status = array();
									break;
								}
								$live_safe_status[ $reviewed_status_key ] = $status_data[ $reviewed_status_key ];
							}
						}
						if ( $canonicalize_json_value( $live_safe_status ) !== $canonicalize_json_value( $reviewed_safe_status ) ) {
							return new WP_Error( 'c99_stabilize_pending_review_changed', 'Pending-stabilization repair checkpoint changed after review.', array( 'status' => 409 ) );
						}
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
					$staging_cleaned = $cleanup_staging( $deployment_id );
					if ( is_wp_error( $staging_cleaned ) ) {
						return $staging_cleaned;
					}
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
					$no_rollback_artifacts = empty( $state['rollback_applied'] )
						&& empty( $state['database_restored'] )
						&& empty( $state['rollback_compensated'] )
						&& empty( $state['rollback_compensation_error'] );
					$interrupted_forward_proof_sha256 = (string) $request->get_param( 'interrupted_forward_proof_sha256' );
					$interrupted_forward_pending = $pending_repair_request && 'installed_pending_stabilization' === $phase;
					$interrupted_forward = 'installing' === $phase || $interrupted_forward_pending;
					$interrupted_forward_adopted = 'installed' === $phase && ! empty( $state['adopted_forward_no_rollback'] );
					$interrupted_config  = is_array( $config['interrupted_forward'] ?? null )
						? $config['interrupted_forward']
						: array();
					$interrupted_temp_path = '';
					$interrupted_baseline_snapshot = array();
					if ( '' !== $interrupted_forward_proof_sha256 && ! $interrupted_forward && ! $interrupted_forward_adopted ) {
						return new WP_Error(
							'c99_stabilize_interrupted_phase',
							'Interrupted-forward proof is accepted only for a reviewed stale forward phase.',
							array( 'status' => 409, 'phase' => $phase )
						);
					}
					if ( $interrupted_forward_adopted && '' !== $interrupted_forward_proof_sha256 ) {
						$configured_proof = (string) ( $interrupted_config['proof_sha256'] ?? '' );
						$state_proof = (string) ( $state['interrupted_forward_proof_sha256'] ?? '' );
						if (
							! preg_match( '/^[a-f0-9]{64}$/', $configured_proof )
							|| ! preg_match( '/^[a-f0-9]{64}$/', $interrupted_forward_proof_sha256 )
							|| ! hash_equals( $configured_proof, $interrupted_forward_proof_sha256 )
							|| ! hash_equals( $configured_proof, $state_proof )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_proof', 'Interrupted-forward proof does not match the durable adoption checkpoint.', array( 'status' => 409 ) );
						}
					}
					if ( $interrupted_forward ) {
						$interrupted_sha_keys = array(
							'expected_artifact_sha256',
							'expected_plugin_sha256',
							'proof_sha256',
							'reviewed_database_fingerprint',
							'reviewed_database_manifest_sha256',
							'prior_database_fingerprint',
							'prior_plugin_sha256',
							'prior_robots_sha256',
						);
						$interrupted_config_valid = true;
						foreach ( $interrupted_sha_keys as $sha_key ) {
							if ( ! is_string( $interrupted_config[ $sha_key ] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/', $interrupted_config[ $sha_key ] ) ) {
								$interrupted_config_valid = false;
								break;
							}
						}
						$interrupted_config_valid = $interrupted_config_valid
							&& in_array( (string) ( $interrupted_config['adoption_schema'] ?? '' ), array( 'complete99-interrupted-forward-adoption/v1', 'complete99-interrupted-forward-adoption/v2', 'complete99-interrupted-forward-adoption/v3', 'complete99-interrupted-forward-adoption/v4' ), true )
							&& is_string( $interrupted_config['expected_version'] ?? null )
							&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $interrupted_config['expected_version'] )
							&& is_string( $interrupted_config['prior_version'] ?? null )
							&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $interrupted_config['prior_version'] )
							&& is_string( $interrupted_config['prior_deployment'] ?? null )
							&& preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $interrupted_config['prior_deployment'] )
							&& str_starts_with( $interrupted_config['prior_deployment'], 'c99-' )
							&& is_array( $interrupted_config['reviewed_database_storage'] ?? null )
							&& array( 'engine', 'tables' ) === array_keys( $interrupted_config['reviewed_database_storage'] )
							&& in_array( (string) ( $interrupted_config['reviewed_database_storage']['engine'] ?? '' ), array( 'INNODB', 'XTRADB', 'INNODB,XTRADB' ), true )
							&& 3 === ( $interrupted_config['reviewed_database_storage']['tables'] ?? null )
							&& $deployment_id !== $interrupted_config['prior_deployment'];
						if ( ! $interrupted_config_valid ) {
							return new WP_Error( 'c99_stabilize_interrupted_config', 'Interrupted-forward identities are not fully configured.', array( 'status' => 500 ) );
						}
						if (
							! preg_match( '/^[a-f0-9]{64}$/', $interrupted_forward_proof_sha256 )
							|| ! hash_equals( $interrupted_config['proof_sha256'], $interrupted_forward_proof_sha256 )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_proof', 'Interrupted-forward proof does not match this single-use recovery bridge.', array( 'status' => 409 ) );
						}
						$lock_updated_at = (int) ( $lock['updated_at'] ?? $lock['started_at'] ?? 0 );
						$lock_age = 0 < $lock_updated_at ? max( 0, time() - $lock_updated_at ) : 0;
						$interrupted_source_phase = $interrupted_forward_pending ? 'installed_pending_stabilization' : 'installing';
						if (
							$interrupted_source_phase !== (string) ( $lock['phase'] ?? '' )
							|| 0 >= $lock_updated_at
							|| $lock_age < (int) $config['recovery_lease_seconds']
							|| '' === (string) ( $state['owner_id'] ?? '' )
							|| (string) ( $state['owner_id'] ?? '' ) !== (string) ( $lock['owner_id'] ?? '' )
							|| 0 >= (int) ( $state['fence'] ?? 0 )
							|| (int) ( $state['fence'] ?? 0 ) !== (int) ( $lock['fence'] ?? 0 )
						) {
							return new WP_Error(
								'c99_stabilize_interrupted_lease',
								'Interrupted-forward adoption requires the exact stale recovery-ready lease.',
								array(
									'status'                 => 409,
									'phase'                  => $phase,
									'lock_age_seconds'       => $lock_age,
									'recovery_lease_seconds' => (int) $config['recovery_lease_seconds'],
								)
							);
						}
						if (
							! $no_rollback_artifacts
							|| ! empty( $state['robots_restored'] )
							|| ! empty( $state['adopted_forward_no_rollback'] )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_rollback_artifacts', 'Interrupted-forward adoption refused rollback or prior-adoption state.', array( 'status' => 409 ) );
						}
						$state_identity_valid = $deployment_id === (string) ( $state['deployment_id'] ?? '' )
							&& hash_equals( $interrupted_config['expected_artifact_sha256'], (string) ( $state['expected_sha256'] ?? '' ) )
							&& $interrupted_config['expected_version'] === (string) ( $state['expected_version'] ?? '' )
							&& hash_equals( $interrupted_config['prior_database_fingerprint'], (string) ( $state['database_fingerprint'] ?? '' ) )
							&& hash_equals( $interrupted_config['prior_plugin_sha256'], (string) ( $state['prior_plugin_sha256'] ?? '' ) )
							&& $interrupted_config['prior_deployment'] === (string) ( $state['prior_deployment'] ?? '' )
							&& $interrupted_config['prior_version'] === (string) ( $state['prior_version'] ?? '' )
							&& true === ( $state['had_plugin'] ?? null )
							&& true === ( $state['prior_target_dir_exists'] ?? null )
							&& true === ( $state['prior_plugin_main_exists'] ?? null )
							&& true === ( $state['was_active'] ?? null )
							&& true === ( $state['robots_prior_exists'] ?? null )
							&& hash_equals( $interrupted_config['prior_robots_sha256'], (string) ( $state['robots_prior_sha256'] ?? '' ) )
							&& ( $interrupted_forward_pending ? true === ( $state['temp_removed'] ?? null ) : empty( $state['temp_removed'] ) )
							&& ( ! $interrupted_forward_pending || '' === (string) ( $state['temp_path'] ?? '' ) )
							&& ( ! $interrupted_forward_pending || true === ( $state['forward_ready'] ?? null ) )
							&& ( ! $interrupted_forward_pending || true === ( $state['installed_active'] ?? null ) )
							&& ( ! $interrupted_forward_pending || 'complete' === (string) ( $state['candidate_activation_phase'] ?? '' ) )
							&& ( ! $interrupted_forward_pending || hash_equals( $interrupted_config['reviewed_database_fingerprint'], (string) ( $state['candidate_database_fingerprint'] ?? '' ) ) );
						if ( ! $state_identity_valid ) {
							return new WP_Error( 'c99_stabilize_interrupted_state_identity', 'Interrupted-forward state does not match the reviewed release and baseline identities.', array( 'status' => 409 ) );
						}
						$prior_robots_bytes = base64_decode( (string) ( $state['robots_prior_base64'] ?? '' ), true );
						if (
							false === $prior_robots_bytes
							|| strlen( $prior_robots_bytes ) > 65536
							|| ! hash_equals( $interrupted_config['prior_robots_sha256'], hash( 'sha256', $prior_robots_bytes ) )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_robots_journal', 'Interrupted-forward robots baseline failed integrity validation.', array( 'status' => 409 ) );
						}
						$interrupted_baseline_snapshot = $decrypt_database_state( $state['database_journal'] ?? array() );
						$interrupted_baseline_json = is_wp_error( $interrupted_baseline_snapshot )
							? false
							: wp_json_encode( $interrupted_baseline_snapshot );
						if (
							is_wp_error( $interrupted_baseline_snapshot )
							|| false === $interrupted_baseline_json
							|| ! hash_equals( $interrupted_config['prior_database_fingerprint'], hash( 'sha256', $interrupted_baseline_json ) )
							|| true !== ( $interrupted_baseline_snapshot['sync_secret_existed'] ?? null )
							|| true !== ( $interrupted_baseline_snapshot['sync_secret_configured'] ?? null )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_database_journal', 'Interrupted-forward database baseline or configured sync journal failed exact validation.', array( 'status' => 409 ) );
						}
						$interrupted_temp_path = (string) ( $state['temp_path'] ?? '' );
						if ( ! $interrupted_forward_pending ) {
							$temp_root = rtrim( strtolower( wp_normalize_path( get_temp_dir() ) ), '/' );
							$normalized_temp = strtolower( wp_normalize_path( $interrupted_temp_path ) );
							if (
								'' === $interrupted_temp_path
								|| '' === $temp_root
								|| dirname( $normalized_temp ) !== $temp_root
								|| ! str_ends_with( $normalized_temp, '.zip' )
								|| str_contains( $normalized_temp, '/../' )
								|| is_link( $interrupted_temp_path )
								|| is_dir( $interrupted_temp_path )
							) {
								return new WP_Error( 'c99_stabilize_interrupted_temp_path', 'Interrupted-forward package cleanup path is unsafe.', array( 'status' => 409 ) );
							}
						}
						foreach ( array( 'robots.forward', 'robots.rollback-prior' ) as $rollback_artifact ) {
							if ( file_exists( trailingslashit( $state_dir ) . $rollback_artifact ) || is_link( trailingslashit( $state_dir ) . $rollback_artifact ) ) {
								return new WP_Error( 'c99_stabilize_interrupted_rollback_file', 'Interrupted-forward adoption refused a rollback filesystem artifact.', array( 'status' => 409 ) );
							}
						}
						$prior_live_robots = trailingslashit( $state_dir ) . 'robots.prior-live';
						if (
							( file_exists( $prior_live_robots ) || is_link( $prior_live_robots ) || is_dir( $prior_live_robots ) )
							&& (
								is_link( $prior_live_robots )
								|| is_dir( $prior_live_robots )
								|| ! hash_equals( $interrupted_config['prior_robots_sha256'], (string) @hash_file( 'sha256', $prior_live_robots ) )
							)
						) {
							return new WP_Error( 'c99_stabilize_interrupted_prior_robots', 'Interrupted-forward adoption requires the exact preserved prior robots file.', array( 'status' => 409 ) );
						}
					}
					$robots_path = $managed_robots_path();
					if ( is_wp_error( $robots_path ) ) {
						return $robots_path;
					}
					$managed_robots_sha256 = (string) ( $state['robots_managed_sha256'] ?? '' );
					$current_robots_sha256 = file_exists( $robots_path ) ? (string) @hash_file( 'sha256', $robots_path ) : '';
					$robots_forward_ready = ! empty( $state['robots_applied'] )
						&& preg_match( '/^[a-f0-9]{64}$/', $managed_robots_sha256 )
						&& hash_equals( $managed_robots_sha256, $current_robots_sha256 );
					$legacy_clean_installed = 'installed' === $phase
						&& empty( $state['stabilized'] )
						&& ! empty( $state['temp_removed'] )
						&& '' === (string) ( $state['temp_path'] ?? '' )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					$clean_pending_stabilization = 'installed_pending_stabilization' === $phase
						&& ! empty( $state['forward_ready'] )
						&& ! empty( $state['temp_removed'] )
						&& '' === (string) ( $state['temp_path'] ?? '' )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					$clean_pending_cleanup = 'installed_pending_cleanup' === $phase
						&& ! empty( $state['forward_ready'] )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					$already_stabilized = 'installed' === $phase
						&& ! empty( $state['stabilized'] )
						&& ! empty( $state['temp_removed'] )
						&& '' === (string) ( $state['temp_path'] ?? '' )
						&& ! empty( $state['installed_active'] )
						&& $robots_forward_ready
						&& $no_rollback_artifacts;
					if ( ! $interrupted_forward && ! $legacy_clean_installed && ! $clean_pending_stabilization && ! $clean_pending_cleanup && ! $already_stabilized ) {
						return new WP_Error(
							'c99_stabilize_not_ready',
							'Deployment stabilization requires a clean forward-pending release.',
							array( 'status' => 409, 'phase' => $phase )
						);
					}

					$expected_version = $interrupted_forward
						? (string) $interrupted_config['expected_version']
						: (string) ( $state['expected_version'] ?? $state['installed_version'] ?? '' );
					$installed_plugin_sha256 = $interrupted_forward
						? (string) $interrupted_config['expected_plugin_sha256']
						: (string) ( $state['installed_plugin_sha256'] ?? '' );
					if (
						! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $expected_version )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $installed_plugin_sha256 )
					) {
						return new WP_Error( 'c99_stabilize_identity', 'The recorded forward release identity is incomplete.', array( 'status' => 409 ) );
					}
					$target_dir  = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
					$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					if ( $interrupted_forward ) {
						$backup_dir  = trailingslashit( $state_dir ) . 'plugin';
						$backup_main = trailingslashit( $backup_dir ) . basename( $config['plugin_file'] );
						$backup_digest = $directory_sha256( $backup_dir );
						$backup_data = ! is_link( $backup_main ) && $wp_filesystem->exists( $backup_main )
							? get_plugin_data( $backup_main, false, false )
							: array();
						if (
							is_wp_error( $backup_digest )
							|| ! hash_equals( $interrupted_config['prior_plugin_sha256'], (string) $backup_digest )
							|| $interrupted_config['prior_version'] !== (string) ( $backup_data['Version'] ?? '' )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_plugin_baseline', 'Interrupted-forward plugin baseline failed exact integrity validation.', array( 'status' => 409 ) );
						}
					}
					$current_data = $wp_filesystem->exists( $plugin_path )
						? get_plugin_data( $plugin_path, false, false )
						: array();
					$current_plugin_sha256 = $wp_filesystem->is_dir( $target_dir )
						? $directory_sha256( $target_dir )
						: new WP_Error( 'c99_stabilize_plugin_missing', 'The installed plugin directory is missing.', array( 'status' => 409 ) );
					$wpdb->last_error = '';
					$current_database_version = (string) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
							'complete99_platform_version'
						)
					);
					$database_version_error = (string) $wpdb->last_error;
					$current_deployment_id = '';
					$deployment_marker_error = '';
					if ( $interrupted_forward ) {
						$wpdb->last_error = '';
						$current_deployment_id = (string) $wpdb->get_var(
							$wpdb->prepare(
								"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
								'complete99_last_deployment_id'
							)
						);
						$deployment_marker_error = (string) $wpdb->last_error;
					}
					$runtime_loaded = defined( 'COMPLETE99_PLATFORM_VERSION' )
						&& $expected_version === (string) COMPLETE99_PLATFORM_VERSION
						&& class_exists( 'Complete99_Platform', false )
						&& method_exists( 'Complete99_Platform', 'migration_failed' )
						&& method_exists( 'Complete99_Platform', 'assert_evaluation_catalog_invariants' )
						&& class_exists( 'Complete99_Ops', false )
						&& method_exists( 'Complete99_Ops', 'assert_invariants' )
						&& class_exists( 'Complete99_Campaigns', false )
						&& method_exists( 'Complete99_Campaigns', 'assert_invariants' )
						&& class_exists( 'Complete99_Culinary_Science', false )
						&& method_exists( 'Complete99_Culinary_Science', 'assert_invariants' );
					$runtime_version        = defined( 'COMPLETE99_PLATFORM_VERSION' ) ? (string) COMPLETE99_PLATFORM_VERSION : '';
					$migration_failed       = $runtime_loaded ? (bool) Complete99_Platform::migration_failed() : null;
					$current_version        = (string) ( $current_data['Version'] ?? '' );
					$plugin_digest_match    = ! is_wp_error( $current_plugin_sha256 )
						&& hash_equals( $installed_plugin_sha256, (string) $current_plugin_sha256 );
					$plugin_header_match    = $expected_version === $current_version;
					$database_version_match = $expected_version === $current_database_version;
					$plugin_active          = is_plugin_active( $config['plugin_file'] );
					$database_error         = '' !== $database_version_error;
					$retryable_forward_mismatch = ! $database_error
						&& $plugin_digest_match
						&& $plugin_header_match
						&& $plugin_active
						&& ( ! $runtime_loaded || true === $migration_failed );
					if (
						$database_error
						|| ! $runtime_loaded
						|| true === $migration_failed
						|| ! $plugin_digest_match
						|| ! $plugin_header_match
						|| ! $database_version_match
						|| ! $plugin_active
						|| ( $interrupted_forward && ( '' !== $deployment_marker_error || $deployment_id !== $current_deployment_id ) )
					) {
						if ( $retryable_forward_mismatch ) {
							wp_opcache_invalidate_directory( $target_dir );
							clearstatcache( true, $plugin_path );
						}
						return new WP_Error(
							'c99_stabilize_forward_mismatch',
							'The forward plugin or its completed database migration does not match the recorded release.',
							array(
								'status'                     => 409,
								'runtime_loaded'             => $runtime_loaded,
								'runtime_version'            => $runtime_version,
								'migration_failed'           => $migration_failed,
								'plugin_digest_match'        => $plugin_digest_match,
								'plugin_header_match'        => $plugin_header_match,
								'database_version_match'     => $database_version_match,
								'plugin_active'              => $plugin_active,
								'database_error'             => $database_error,
								'current_version'            => $current_version,
								'current_database_version'   => $current_database_version,
								'retryable_forward_mismatch' => $retryable_forward_mismatch,
							)
						);
					}
					try {
						Complete99_Content::assert_migration_invariants();
						Complete99_Settings::assert_defaults();
						Complete99_Platform::assert_evaluation_catalog_invariants();
						Complete99_Ops::assert_invariants();
						if ( ! $interrupted_forward_pending ) {
							Complete99_Campaigns::assert_invariants();
						}
						Complete99_Culinary_Science::assert_invariants();
					} catch ( \Throwable $error ) {
						return new WP_Error(
							'c99_stabilize_migration_invariants',
							'The completed database migration did not satisfy the release invariants.',
							array( 'status' => 409 )
						);
					}
					$swap_suffix = substr( hash( 'sha256', $deployment_id ), 0, 20 );
					$restore_stage = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix;
					$displaced_dir = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix;
					if ( $wp_filesystem->exists( $restore_stage ) || $wp_filesystem->exists( $displaced_dir ) ) {
						return new WP_Error(
							'c99_stabilize_swap_artifacts',
							'Deployment stabilization refused rollback swap artifacts.',
							array( 'status' => 409 )
						);
					}
					if ( $interrupted_forward ) {
						$database_storage = $verify_transactional_storage();
						if (
							is_wp_error( $database_storage )
							|| 3 !== (int) ( $database_storage['tables'] ?? 0 )
							|| '' === (string) ( $database_storage['engine'] ?? '' )
							|| (string) ( $database_storage['engine'] ?? '' ) !== (string) $interrupted_config['reviewed_database_storage']['engine']
							|| ( $database_storage['tables'] ?? null ) !== $interrupted_config['reviewed_database_storage']['tables']
						) {
							return is_wp_error( $database_storage )
								? $database_storage
								: new WP_Error( 'c99_stabilize_interrupted_storage', 'Interrupted-forward transactional storage proof is incomplete.', array( 'status' => 409 ) );
						}
						$current_database_snapshot = $capture_database_state_consistent();
						$current_database_json = is_wp_error( $current_database_snapshot )
							? false
							: wp_json_encode( $current_database_snapshot );
						$current_database_fingerprint = false === $current_database_json
							? ''
							: hash( 'sha256', $current_database_json );
						$current_manifest_record = is_wp_error( $current_database_snapshot )
							? $current_database_snapshot
							: $database_snapshot_manifest( $current_database_snapshot );
						$current_manifest = is_array( $current_manifest_record )
							? ( $current_manifest_record['manifest'] ?? null )
							: null;
						$current_manifest_sha256 = is_array( $current_manifest_record )
							? (string) ( $current_manifest_record['manifest_sha256'] ?? '' )
							: '';
						$current_marker_row = is_array( $current_database_snapshot )
							? ( $current_database_snapshot['options']['complete99_last_deployment_id'] ?? null )
							: null;
						$current_version_row = is_array( $current_database_snapshot )
							? ( $current_database_snapshot['options']['complete99_platform_version'] ?? null )
							: null;
						if (
							is_wp_error( $current_database_snapshot )
							|| false === $current_database_json
							|| ! hash_equals( $interrupted_config['reviewed_database_fingerprint'], $current_database_fingerprint )
							|| ! is_array( $current_manifest_record )
							|| ! $database_snapshot_manifest_valid( $current_manifest, $current_manifest_sha256 )
							|| ! hash_equals( $interrupted_config['reviewed_database_manifest_sha256'], $current_manifest_sha256 )
							|| true !== ( $current_database_snapshot['sync_secret_existed'] ?? null )
							|| true !== ( $current_database_snapshot['sync_secret_configured'] ?? null )
							|| ! is_array( $current_marker_row )
							|| $deployment_id !== (string) ( $current_marker_row['option_value'] ?? '' )
							|| ! is_array( $current_version_row )
							|| $expected_version !== (string) ( $current_version_row['option_value'] ?? '' )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_database_proof', 'Interrupted-forward database fingerprint, manifest, marker, version or configured sync proof changed.', array( 'status' => 409 ) );
						}
						$managed_robots_sha256 = hash( 'sha256', $managed_robots_contents() );
						$current_robots_sha256 = file_exists( $robots_path ) && ! is_link( $robots_path ) && ! is_dir( $robots_path )
							? (string) @hash_file( 'sha256', $robots_path )
							: '';
						if (
							! hash_equals( $interrupted_config['prior_robots_sha256'], $managed_robots_sha256 )
							|| ! hash_equals( $interrupted_config['prior_robots_sha256'], $current_robots_sha256 )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_robots', 'Interrupted-forward robots.txt is not the exact reviewed managed file.', array( 'status' => 409 ) );
						}
						if ( file_exists( $interrupted_temp_path ) ) {
							$temp_artifact_sha256 = @hash_file( 'sha256', $interrupted_temp_path );
							if ( false === $temp_artifact_sha256 || ! hash_equals( $interrupted_config['expected_artifact_sha256'], $temp_artifact_sha256 ) ) {
								return new WP_Error( 'c99_stabilize_interrupted_artifact', 'Interrupted-forward temporary package does not match the reviewed artifact.', array( 'status' => 409 ) );
							}
						}

						$lease = $claim_lock(
							$deployment_id,
							array( $interrupted_source_phase ),
							$interrupted_source_phase,
							false,
							true
						);
						if ( is_wp_error( $lease ) ) {
							return $lease;
						}
						$adopted = $adopt_state_lease( $state_dir, $deployment_id, $lease );
						if ( is_wp_error( $adopted ) ) {
							return $adopted;
						}
						$state = $adopted;
						if (
							$interrupted_source_phase !== (string) ( $state['phase'] ?? '' )
							|| ! empty( $state['rollback_applied'] )
							|| ! empty( $state['database_restored'] )
							|| ! empty( $state['rollback_compensated'] )
							|| ! empty( $state['rollback_compensation_error'] )
							|| ! empty( $state['robots_restored'] )
							|| ! hash_equals( $interrupted_config['expected_artifact_sha256'], (string) ( $state['expected_sha256'] ?? '' ) )
							|| $interrupted_config['expected_version'] !== (string) ( $state['expected_version'] ?? '' )
							|| ! hash_equals( $interrupted_config['prior_database_fingerprint'], (string) ( $state['database_fingerprint'] ?? '' ) )
							|| ! hash_equals( $interrupted_config['prior_plugin_sha256'], (string) ( $state['prior_plugin_sha256'] ?? '' ) )
							|| $interrupted_config['prior_deployment'] !== (string) ( $state['prior_deployment'] ?? '' )
							|| $interrupted_config['prior_version'] !== (string) ( $state['prior_version'] ?? '' )
							|| $interrupted_temp_path !== (string) ( $state['temp_path'] ?? '' )
							|| ( $interrupted_forward_pending && true !== ( $state['forward_ready'] ?? null ) )
							|| ( $interrupted_forward_pending && true !== ( $state['temp_removed'] ?? null ) )
							|| ( $interrupted_forward_pending && true !== ( $state['installed_active'] ?? null ) )
							|| ( $interrupted_forward_pending && 'complete' !== (string) ( $state['candidate_activation_phase'] ?? '' ) )
							|| ( $interrupted_forward_pending && ! hash_equals( $interrupted_config['reviewed_database_fingerprint'], (string) ( $state['candidate_database_fingerprint'] ?? '' ) ) )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_adoption_state', 'Interrupted-forward state changed while the stale lease was adopted.', array( 'status' => 409 ) );
						}
						$post_claim_plugin_sha256 = $directory_sha256( $target_dir );
						clearstatcache( true, $plugin_path );
						$post_claim_data = $wp_filesystem->exists( $plugin_path )
							? get_plugin_data( $plugin_path, false, false )
							: array();
						$post_claim_runtime_valid = defined( 'COMPLETE99_PLATFORM_VERSION' )
							&& $expected_version === (string) COMPLETE99_PLATFORM_VERSION
							&& class_exists( 'Complete99_Platform', false )
							&& method_exists( 'Complete99_Platform', 'migration_failed' )
							&& method_exists( 'Complete99_Platform', 'assert_evaluation_catalog_invariants' )
						&& class_exists( 'Complete99_Ops', false )
						&& method_exists( 'Complete99_Ops', 'assert_invariants' )
						&& class_exists( 'Complete99_Campaigns', false )
						&& method_exists( 'Complete99_Campaigns', 'assert_invariants' )
						&& class_exists( 'Complete99_Culinary_Science', false )
							&& method_exists( 'Complete99_Culinary_Science', 'assert_invariants' )
							&& false === (bool) Complete99_Platform::migration_failed();
						if (
							is_wp_error( $post_claim_plugin_sha256 )
							|| ! hash_equals( $installed_plugin_sha256, (string) $post_claim_plugin_sha256 )
							|| $expected_version !== (string) ( $post_claim_data['Version'] ?? '' )
							|| ! is_plugin_active( $config['plugin_file'] )
							|| ! $post_claim_runtime_valid
						) {
							return new WP_Error( 'c99_stabilize_interrupted_post_claim_plugin', 'Interrupted-forward plugin identity changed after lease adoption.', array( 'status' => 409 ) );
						}
						try {
							Complete99_Content::assert_migration_invariants();
							Complete99_Settings::assert_defaults();
							Complete99_Platform::assert_evaluation_catalog_invariants();
							Complete99_Ops::assert_invariants();
							if ( ! $interrupted_forward_pending ) {
								Complete99_Campaigns::assert_invariants();
							}
							Complete99_Culinary_Science::assert_invariants();
						} catch ( \Throwable $error ) {
							return new WP_Error( 'c99_stabilize_interrupted_post_claim_invariants', 'Interrupted-forward migration invariants changed after lease adoption.', array( 'status' => 409 ) );
						}
						$post_claim_storage = $verify_transactional_storage();
						$post_claim_snapshot = $capture_database_state_consistent();
						$post_claim_json = is_wp_error( $post_claim_snapshot ) ? false : wp_json_encode( $post_claim_snapshot );
						$post_claim_fingerprint = false === $post_claim_json ? '' : hash( 'sha256', $post_claim_json );
						$post_claim_manifest_record = is_wp_error( $post_claim_snapshot )
							? $post_claim_snapshot
							: $database_snapshot_manifest( $post_claim_snapshot );
						$post_claim_manifest = is_array( $post_claim_manifest_record )
							? ( $post_claim_manifest_record['manifest'] ?? null )
							: null;
						$post_claim_manifest_sha256 = is_array( $post_claim_manifest_record )
							? (string) ( $post_claim_manifest_record['manifest_sha256'] ?? '' )
							: '';
						$post_claim_marker_row = is_array( $post_claim_snapshot )
							? ( $post_claim_snapshot['options']['complete99_last_deployment_id'] ?? null )
							: null;
						$post_claim_version_row = is_array( $post_claim_snapshot )
							? ( $post_claim_snapshot['options']['complete99_platform_version'] ?? null )
							: null;
						if (
							is_wp_error( $post_claim_storage )
							|| 3 !== (int) ( $post_claim_storage['tables'] ?? 0 )
							|| '' === (string) ( $post_claim_storage['engine'] ?? '' )
							|| (string) ( $post_claim_storage['engine'] ?? '' ) !== (string) $interrupted_config['reviewed_database_storage']['engine']
							|| ( $post_claim_storage['tables'] ?? null ) !== $interrupted_config['reviewed_database_storage']['tables']
							|| is_wp_error( $post_claim_snapshot )
							|| false === $post_claim_json
							|| ! hash_equals( $interrupted_config['reviewed_database_fingerprint'], $post_claim_fingerprint )
							|| ! is_array( $post_claim_manifest_record )
							|| ! $database_snapshot_manifest_valid( $post_claim_manifest, $post_claim_manifest_sha256 )
							|| ! hash_equals( $interrupted_config['reviewed_database_manifest_sha256'], $post_claim_manifest_sha256 )
							|| true !== ( $post_claim_snapshot['sync_secret_existed'] ?? null )
							|| true !== ( $post_claim_snapshot['sync_secret_configured'] ?? null )
							|| ! is_array( $post_claim_marker_row )
							|| $deployment_id !== (string) ( $post_claim_marker_row['option_value'] ?? '' )
							|| ! is_array( $post_claim_version_row )
							|| $expected_version !== (string) ( $post_claim_version_row['option_value'] ?? '' )
						) {
							return new WP_Error( 'c99_stabilize_interrupted_post_claim_database', 'Interrupted-forward database proof changed after lease adoption.', array( 'status' => 409 ) );
						}
						$post_claim_robots_sha256 = file_exists( $robots_path ) && ! is_link( $robots_path ) && ! is_dir( $robots_path )
							? (string) @hash_file( 'sha256', $robots_path )
							: '';
						if ( ! hash_equals( $interrupted_config['prior_robots_sha256'], $post_claim_robots_sha256 ) ) {
							return new WP_Error( 'c99_stabilize_interrupted_post_claim_robots', 'Interrupted-forward robots.txt changed after lease adoption.', array( 'status' => 409 ) );
						}
						if ( '' !== $interrupted_temp_path && file_exists( $interrupted_temp_path ) ) {
							$post_claim_artifact_sha256 = @hash_file( 'sha256', $interrupted_temp_path );
							if (
								false === $post_claim_artifact_sha256
								|| ! hash_equals( $interrupted_config['expected_artifact_sha256'], $post_claim_artifact_sha256 )
								|| ! $wp_filesystem->delete( $interrupted_temp_path )
							) {
								return new WP_Error( 'c99_stabilize_interrupted_temp_cleanup', 'Interrupted-forward reviewed package could not be removed safely.', array( 'status' => 500 ) );
							}
						}
						if ( '' !== $interrupted_temp_path ) {
							clearstatcache( true, $interrupted_temp_path );
						}
						if ( '' !== $interrupted_temp_path && ( file_exists( $interrupted_temp_path ) || is_link( $interrupted_temp_path ) || is_dir( $interrupted_temp_path ) ) ) {
							return new WP_Error( 'c99_stabilize_interrupted_temp_readback', 'Interrupted-forward package cleanup failed readback.', array( 'status' => 500 ) );
						}
						$stabilized = $set_state_phase(
							$state_dir,
							$deployment_id,
							'installed',
							array(
								'installed_plugin_sha256'                  => $installed_plugin_sha256,
								'installed_version'                        => $expected_version,
								'installed_active'                         => true,
								'temp_removed'                             => true,
								'temp_path'                                => '',
								'forward_ready'                            => true,
								'sync_configuration_pending'               => false,
								'robots_applied'                           => true,
								'robots_restored'                          => false,
								'robots_managed_sha256'                    => $interrupted_config['prior_robots_sha256'],
								'post_install_database_fingerprint'        => $post_claim_fingerprint,
								'interrupted_forward_current_database_fingerprint'=> $post_claim_fingerprint,
								'interrupted_forward_database_manifest'       => $post_claim_manifest,
								'interrupted_forward_database_manifest_sha256'=> $post_claim_manifest_sha256,
								'interrupted_forward_database_storage'       => $post_claim_storage,
								'interrupted_forward_proof_sha256'         => $interrupted_config['proof_sha256'],
								'adopted_forward_no_rollback'              => true,
								'adopted_forward_at'                       => time(),
								'stabilized'                               => true,
								'stabilized_from_phase'                    => $interrupted_source_phase,
							)
						);
						if ( is_wp_error( $stabilized ) ) {
							return $stabilized;
						}
						return array(
							'stabilized'                       => true,
							'idempotent'                       => false,
							'adopted_forward_no_rollback'      => true,
							'stabilized_from_phase'            => $interrupted_source_phase,
							'version'                          => $expected_version,
							'database_version'                 => $expected_version,
							'deployment_id'                    => $deployment_id,
							'installed_plugin_sha256'          => $installed_plugin_sha256,
							'post_install_database_fingerprint'=> $post_claim_fingerprint,
							'interrupted_forward_proof_sha256' => $interrupted_config['proof_sha256'],
							'database_manifest_sha256'         => $post_claim_manifest_sha256,
							'database_manifest'                => $post_claim_manifest,
							'database_storage'                 => $post_claim_storage,
							'cache_purge'                      => array( 'deferred_to_finalize' => true ),
						);
					}

					$current_database_snapshot = $interrupted_forward_adopted
						? $capture_database_state_consistent()
						: $capture_database_state();
					$current_database_json = is_wp_error( $current_database_snapshot )
						? false
						: wp_json_encode( $current_database_snapshot );
					if ( is_wp_error( $current_database_snapshot ) || false === $current_database_json ) {
						return new WP_Error( 'c99_stabilize_database_probe', 'The current database fingerprint could not be captured.', array( 'status' => 500 ) );
					}
					$current_database_fingerprint = hash( 'sha256', $current_database_json );
					$recorded_fingerprint = (string) ( $state['post_install_database_fingerprint'] ?? '' );
					if ( $already_stabilized ) {
						$wpdb->last_error = '';
						$current_deployment_id = (string) $wpdb->get_var(
							$wpdb->prepare(
								"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
								'complete99_last_deployment_id'
							)
						);
						if (
							'' !== (string) $wpdb->last_error
							|| $deployment_id !== $current_deployment_id
							|| ! preg_match( '/^[a-f0-9]{64}$/', $recorded_fingerprint )
							|| ! hash_equals( $recorded_fingerprint, $current_database_fingerprint )
						) {
							return new WP_Error(
								'c99_stabilize_idempotency_conflict',
								'The stabilized release changed after its durable checkpoint.',
								array( 'status' => 409 )
							);
						}
						$idempotent_manifest = array();
						$idempotent_manifest_sha256 = '';
						$idempotent_storage = array();
						if ( $interrupted_forward_adopted ) {
							$idempotent_manifest_record = $database_snapshot_manifest( $current_database_snapshot );
							$idempotent_manifest = is_array( $idempotent_manifest_record )
								? ( $idempotent_manifest_record['manifest'] ?? null )
								: null;
							$idempotent_manifest_sha256 = is_array( $idempotent_manifest_record )
								? (string) ( $idempotent_manifest_record['manifest_sha256'] ?? '' )
								: '';
							$idempotent_storage = $verify_transactional_storage();
							$configured_proof = (string) ( $interrupted_config['proof_sha256'] ?? '' );
							$configured_manifest_sha256 = (string) ( $interrupted_config['reviewed_database_manifest_sha256'] ?? '' );
							$configured_database_fingerprint = (string) ( $interrupted_config['reviewed_database_fingerprint'] ?? '' );
							$configured_plugin_sha256 = (string) ( $interrupted_config['expected_plugin_sha256'] ?? '' );
							$configured_robots_sha256 = (string) ( $interrupted_config['prior_robots_sha256'] ?? '' );
							if (
								! preg_match( '/^[a-f0-9]{64}$/', $configured_proof )
								|| ! preg_match( '/^[a-f0-9]{64}$/', $configured_manifest_sha256 )
								|| ! preg_match( '/^[a-f0-9]{64}$/', $configured_database_fingerprint )
								|| ! preg_match( '/^[a-f0-9]{64}$/', $configured_plugin_sha256 )
								|| ! preg_match( '/^[a-f0-9]{64}$/', $configured_robots_sha256 )
								|| ! hash_equals( $configured_proof, (string) ( $state['interrupted_forward_proof_sha256'] ?? '' ) )
								|| ! hash_equals( $configured_manifest_sha256, (string) ( $state['interrupted_forward_database_manifest_sha256'] ?? '' ) )
								|| ! hash_equals( $configured_manifest_sha256, $idempotent_manifest_sha256 )
								|| ! hash_equals( $configured_database_fingerprint, $current_database_fingerprint )
								|| ! hash_equals( $configured_plugin_sha256, $installed_plugin_sha256 )
								|| ! hash_equals( $configured_robots_sha256, $current_robots_sha256 )
								|| ! hash_equals( $configured_robots_sha256, hash( 'sha256', $managed_robots_contents() ) )
								|| (string) ( $interrupted_config['expected_version'] ?? '' ) !== $expected_version
								|| true !== ( $current_database_snapshot['sync_secret_existed'] ?? null )
								|| true !== ( $current_database_snapshot['sync_secret_configured'] ?? null )
								|| ! is_array( $idempotent_manifest_record )
								|| ! $database_snapshot_manifest_valid( $idempotent_manifest, $idempotent_manifest_sha256 )
								|| is_wp_error( $idempotent_storage )
								|| 3 !== (int) ( $idempotent_storage['tables'] ?? 0 )
								|| '' === (string) ( $idempotent_storage['engine'] ?? '' )
								|| ! is_array( $interrupted_config['reviewed_database_storage'] ?? null )
								|| (string) ( $idempotent_storage['engine'] ?? '' ) !== (string) ( $interrupted_config['reviewed_database_storage']['engine'] ?? '' )
								|| ( $idempotent_storage['tables'] ?? null ) !== ( $interrupted_config['reviewed_database_storage']['tables'] ?? null )
								|| $idempotent_manifest !== ( $state['interrupted_forward_database_manifest'] ?? null )
								|| $idempotent_storage !== ( $state['interrupted_forward_database_storage'] ?? null )
							) {
								return new WP_Error( 'c99_stabilize_interrupted_idempotency_proof', 'The durable interrupted-forward adoption no longer matches its reviewed proof.', array( 'status' => 409 ) );
							}
						}
						return array(
							'stabilized'                       => true,
							'idempotent'                       => true,
							'adopted_forward_no_rollback'      => ! empty( $state['adopted_forward_no_rollback'] ),
							'stabilized_from_phase'            => (string) ( $state['stabilized_from_phase'] ?? 'installed' ),
							'version'                          => $expected_version,
							'database_version'                 => $current_database_version,
							'deployment_id'                    => $deployment_id,
							'installed_plugin_sha256'          => $installed_plugin_sha256,
							'post_install_database_fingerprint'=> $recorded_fingerprint,
							'interrupted_forward_proof_sha256' => (string) ( $state['interrupted_forward_proof_sha256'] ?? '' ),
							'database_manifest_sha256'         => $interrupted_forward_adopted ? $idempotent_manifest_sha256 : '',
							'database_manifest'                => $interrupted_forward_adopted ? $idempotent_manifest : array(),
							'database_storage'                 => $interrupted_forward_adopted ? $idempotent_storage : array(),
							'cache_purge'                       => $interrupted_forward_adopted
								? array( 'deferred_to_finalize' => true )
								: array( 'not_required' => true ),
						);
					}

					if ( $clean_pending_cleanup ) {
						$temp_path = (string) ( $state['temp_path'] ?? '' );
						$temp_root = strtolower( trailingslashit( wp_normalize_path( get_temp_dir() ) ) );
						$normalized_temp = strtolower( wp_normalize_path( $temp_path ) );
						if (
							'' === $temp_path
							|| ! str_starts_with( $normalized_temp, $temp_root )
							|| ! str_ends_with( $normalized_temp, '.zip' )
						) {
							return new WP_Error( 'c99_stabilize_temp_path', 'The pending package path is invalid.', array( 'status' => 409 ) );
						}
						if ( $wp_filesystem->exists( $temp_path ) && ! $wp_filesystem->delete( $temp_path ) ) {
							return new WP_Error( 'c99_stabilize_temp_cleanup', 'The pending package could not be removed.', array( 'status' => 500 ) );
						}
					}

					$lease = $claim_lock(
						$deployment_id,
						array( 'installed', 'installed_pending_stabilization', 'installed_pending_cleanup' ),
						$phase,
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

					update_option( 'complete99_last_deployment_id', $deployment_id, false );
					wp_cache_delete( 'complete99_last_deployment_id', 'options' );
					$wpdb->last_error = '';
					$persisted_deployment_id = (string) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
							'complete99_last_deployment_id'
						)
					);
					if ( '' !== (string) $wpdb->last_error || $deployment_id !== $persisted_deployment_id ) {
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
							'temp_removed'                      => true,
							'temp_path'                         => '',
							'forward_ready'                     => true,
							'pre_migration_database_fingerprint'=> $recorded_fingerprint,
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
						'idempotent'                       => false,
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
			$route_prefix . '/configure-sync',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $purge_caches, $read_lock, $claim_lock, $acquire_process_lock, $release_process_lock, $adopt_state_lease, $set_state_phase, $capture_database_state, $decrypt_database_state ) {
					global $wpdb, $wp_filesystem;
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
						return new WP_Error( 'c99_sync_configure_id', 'The sync configuration deployment ID is invalid.', array( 'status' => 400 ) );
					}
					$json_parameters = $request->get_json_params();
					if ( ! is_array( $json_parameters ) || ! array_key_exists( 'sync_secret', $json_parameters ) ) {
						return new WP_Error( 'c99_sync_configure_transport', 'The sync configuration requires a JSON request body.', array( 'status' => 400 ) );
					}
					$provided_secret = (string) $json_parameters['sync_secret'];
					if ( strlen( $provided_secret ) < 32 || strlen( $provided_secret ) > 4096 ) {
						return new WP_Error( 'c99_sync_configure_value', 'The sync configuration value is invalid.', array( 'status' => 400 ) );
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
						$state_dir  = $state_directory( $deployment_id );
						$state_file = trailingslashit( $state_dir ) . 'state.json';
						if ( ! $wp_filesystem->exists( $state_file ) ) {
							return new WP_Error( 'c99_sync_configure_state', 'The deployment state was not found.', array( 'status' => 404 ) );
						}
						$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
						if ( ! is_array( $state ) ) {
							return new WP_Error( 'c99_sync_configure_state_invalid', 'The deployment state is invalid.', array( 'status' => 500 ) );
						}
						$lock = $read_lock( true );
						if ( $deployment_id !== (string) ( $lock['deployment_id'] ?? '' ) ) {
							return new WP_Error( 'c99_sync_configure_lock', 'The deployment does not own the mutation lock.', array( 'status' => 409 ) );
						}
						if (
							'installed' !== (string) ( $state['phase'] ?? '' )
							|| empty( $state['stabilized'] )
							|| ! empty( $state['rollback_applied'] )
							|| ! empty( $state['database_restored'] )
							|| ! empty( $state['rollback_compensated'] )
						) {
							return new WP_Error(
								'c99_sync_configure_not_ready',
								'Sync configuration requires the final stabilized forward release.',
								array( 'status' => 409, 'phase' => (string) ( $state['phase'] ?? '' ) )
							);
						}
						$lease = $claim_lock(
							$deployment_id,
							array( 'installed' ),
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

						$baseline = $decrypt_database_state( $state['database_journal'] ?? array() );
						if (
							is_wp_error( $baseline )
							|| ! isset( $baseline['sync_secret_existed'], $baseline['sync_secret_configured'] )
							|| ! is_bool( $baseline['sync_secret_existed'] )
							|| ! is_bool( $baseline['sync_secret_configured'] )
							|| ( $baseline['sync_secret_configured'] && ! $baseline['sync_secret_existed'] )
						) {
							return new WP_Error( 'c99_sync_configure_journal', 'The sync configuration rollback journal is invalid.', array( 'status' => 500 ) );
						}

						$wpdb->last_error = '';
						$current_row = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
								'complete99_sync_secret'
							),
							ARRAY_A
						);
						if ( '' !== (string) $wpdb->last_error || ( null !== $current_row && ! is_array( $current_row ) ) ) {
							return new WP_Error( 'c99_sync_configure_probe', 'The current sync configuration could not be verified.', array( 'status' => 500 ) );
						}
						$current_exists = is_array( $current_row ) && array_key_exists( 'option_value', $current_row );
						$current_value = $current_exists ? maybe_unserialize( $current_row['option_value'] ) : '';
						if ( ! is_string( $current_value ) ) {
							return new WP_Error( 'c99_sync_configure_type', 'The current sync configuration has an unsupported type.', array( 'status' => 409 ) );
						}
						$current_configured = $current_exists && '' !== $current_value;
						if ( $current_configured && ! hash_equals( $current_value, $provided_secret ) ) {
							return new WP_Error(
								'c99_sync_rotation_refused',
								'An existing sync configuration cannot be rotated by the deployment bridge.',
								array( 'status' => 409 )
							);
						}
						if ( $baseline['sync_secret_configured'] && ! $current_configured ) {
							return new WP_Error(
								'c99_sync_baseline_changed',
								'The configured sync baseline changed before finalization.',
								array( 'status' => 409 )
							);
						}

						$current_snapshot = $capture_database_state();
						$current_json = is_wp_error( $current_snapshot ) ? false : wp_json_encode( $current_snapshot );
						if ( is_wp_error( $current_snapshot ) || false === $current_json ) {
							return new WP_Error( 'c99_sync_configure_database_probe', 'The current database checkpoint could not be captured.', array( 'status' => 500 ) );
						}
						$current_fingerprint = hash( 'sha256', $current_json );
						$recorded_fingerprint = (string) ( $state['post_install_database_fingerprint'] ?? '' );
						$pending_fingerprint = (string) ( $state['sync_configured_database_fingerprint'] ?? '' );
						$pre_sync_fingerprint = (string) ( $state['pre_sync_database_fingerprint'] ?? '' );
						$checkpoint_matches = preg_match( '/^[a-f0-9]{64}$/', $recorded_fingerprint )
							&& hash_equals( $recorded_fingerprint, $current_fingerprint );
						$unconfigured_projection_fingerprint = '';
						if ( ! $baseline['sync_secret_configured'] && $current_configured ) {
							$unconfigured_projection = $current_snapshot;
							$unconfigured_projection['sync_secret_configured'] = false;
							$unconfigured_projection_json = wp_json_encode( $unconfigured_projection );
							$unconfigured_projection_fingerprint = false === $unconfigured_projection_json
								? ''
								: hash( 'sha256', $unconfigured_projection_json );
							$checkpoint_matches = $checkpoint_matches || (
								preg_match( '/^[a-f0-9]{64}$/', $recorded_fingerprint )
								&& hash_equals( $recorded_fingerprint, $unconfigured_projection_fingerprint )
							);
						}
						if ( ! $checkpoint_matches && ! empty( $state['sync_configuration_pending'] ) ) {
							$checkpoint_matches = (
								preg_match( '/^[a-f0-9]{64}$/', $pending_fingerprint )
								&& hash_equals( $pending_fingerprint, $current_fingerprint )
							) || (
								preg_match( '/^[a-f0-9]{64}$/', $pre_sync_fingerprint )
								&& hash_equals( $pre_sync_fingerprint, $current_fingerprint )
							);
						}
						if ( ! $checkpoint_matches ) {
							return new WP_Error(
								'c99_sync_configure_database_conflict',
								'Plugin-owned data changed after stabilization.',
								array( 'status' => 409 )
							);
						}

						$configured_snapshot = $current_snapshot;
						$configured_snapshot['sync_secret_existed']    = true;
						$configured_snapshot['sync_secret_configured'] = true;
						$configured_json = wp_json_encode( $configured_snapshot );
						if ( false === $configured_json ) {
							return new WP_Error( 'c99_sync_configure_checkpoint', 'The sync configuration checkpoint could not be encoded.', array( 'status' => 500 ) );
						}
						$configured_fingerprint = hash( 'sha256', $configured_json );
						$pre_write_fingerprint = ! empty( $state['sync_configuration_pending'] )
							? $pre_sync_fingerprint
							: (
								'' !== $unconfigured_projection_fingerprint
									? $unconfigured_projection_fingerprint
									: $current_fingerprint
							);
						if (
							! preg_match( '/^[a-f0-9]{64}$/', $pre_write_fingerprint )
							|| (
								! empty( $state['sync_configuration_pending'] )
								&& (
									! preg_match( '/^[a-f0-9]{64}$/', $pending_fingerprint )
									|| ! hash_equals( $pending_fingerprint, $configured_fingerprint )
								)
							)
						) {
							return new WP_Error( 'c99_sync_configure_pending_conflict', 'The pending sync configuration checkpoint is invalid.', array( 'status' => 409 ) );
						}

						$changed = false;
						if ( ! $baseline['sync_secret_configured'] && ! $current_configured ) {
							$pending = $set_state_phase(
								$state_dir,
								$deployment_id,
								'installed',
								array(
									'pre_sync_database_fingerprint'       => $pre_write_fingerprint,
									'sync_configured_database_fingerprint'=> $configured_fingerprint,
									'sync_configuration_pending'          => true,
									'sync_configuration_checkpointed'     => false,
								)
							);
							if ( is_wp_error( $pending ) ) {
								return $pending;
							}
							$state = $pending;
							$wpdb->last_error = '';
							$stored_secret = maybe_serialize( $provided_secret );
							if ( $current_exists ) {
								$write_result = $wpdb->query(
									$wpdb->prepare(
										"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s",
										$stored_secret,
										'complete99_sync_secret'
									)
								);
							} else {
								$write_result = $wpdb->insert(
									$wpdb->options,
									array(
										'option_name'  => 'complete99_sync_secret',
										'option_value' => $stored_secret,
										'autoload'     => 'no',
									),
									array( '%s', '%s', '%s' )
								);
							}
							if ( false === $write_result || '' !== (string) $wpdb->last_error ) {
								return new WP_Error( 'c99_sync_configure_write', 'The sync configuration could not be stored.', array( 'status' => 500 ) );
							}
							wp_cache_delete( 'complete99_sync_secret', 'options' );
							$changed = true;
						}

						$wpdb->last_error = '';
						$readback_row = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
								'complete99_sync_secret'
							),
							ARRAY_A
						);
						$readback_value = is_array( $readback_row ) && array_key_exists( 'option_value', $readback_row )
							? maybe_unserialize( $readback_row['option_value'] )
							: null;
						if (
							'' !== (string) $wpdb->last_error
							|| ! is_string( $readback_value )
							|| ! hash_equals( $provided_secret, $readback_value )
						) {
							return new WP_Error( 'c99_sync_configure_readback', 'The sync configuration readback did not match.', array( 'status' => 500 ) );
						}
						$final_snapshot = $capture_database_state();
						$final_json = is_wp_error( $final_snapshot ) ? false : wp_json_encode( $final_snapshot );
						$final_fingerprint = false === $final_json ? '' : hash( 'sha256', $final_json );
						if (
							is_wp_error( $final_snapshot )
							|| empty( $final_snapshot['sync_secret_configured'] )
							|| ! hash_equals( $configured_fingerprint, $final_fingerprint )
						) {
							return new WP_Error( 'c99_sync_configure_fingerprint', 'The sync configuration checkpoint did not match.', array( 'status' => 500 ) );
						}
						$cache_purge = $purge_caches();
						if ( is_wp_error( $cache_purge ) ) {
							return $cache_purge;
						}
						$checkpoint = $set_state_phase(
							$state_dir,
							$deployment_id,
							'installed',
							array(
								'pre_sync_database_fingerprint'       => $pre_write_fingerprint,
								'sync_configured_database_fingerprint'=> $final_fingerprint,
								'post_install_database_fingerprint'   => $final_fingerprint,
								'sync_configuration_pending'          => false,
								'sync_configuration_checkpointed'     => true,
							)
						);
						if ( is_wp_error( $checkpoint ) ) {
							return $checkpoint;
						}
						return array(
							'configured'          => true,
							'changed'             => $changed,
							'idempotent'          => ! $changed,
							'database_fingerprint'=> $final_fingerprint,
							'cache_purge'         => $cache_purge,
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
			$route_prefix . '/stage',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $lock_owner, $bootstrap_filesystem, $verify_site_identity, $validate_embedded_artifact_identity, $protect_staging_directory, $cleanup_staging, $state_directory, $staged_artifact_path, $staged_metadata_path, $read_stage_metadata, $validate_staged_archive, $write_state_file, $read_lock, $heartbeat_lock, $acquire_process_lock, $release_process_lock ) {
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$embedded_identity = $validate_embedded_artifact_identity();
					if ( is_wp_error( $embedded_identity ) ) {
						return $embedded_identity;
					}

					$json_params = $request->get_json_params();
					$request_keys = is_array( $json_params ) ? array_keys( $json_params ) : array();
					$expected_request_keys = array(
						'chunk_base64',
						'chunk_sha256',
						'deployment_id',
						'expected_artifact_sha256',
						'expected_artifact_size',
						'final',
						'offset',
						'token',
					);
					sort( $request_keys, SORT_STRING );
					if ( $expected_request_keys !== $request_keys ) {
						return new WP_Error( 'c99_stage_request_shape', 'The artifact staging request shape is invalid.', array( 'status' => 400 ) );
					}
					$deployment_id = (string) $request->get_param( 'deployment_id' );
					$expected_sha  = (string) $request->get_param( 'expected_artifact_sha256' );
					$expected_size = $request->get_param( 'expected_artifact_size' );
					$offset        = $request->get_param( 'offset' );
					$chunk_sha     = (string) $request->get_param( 'chunk_sha256' );
					$encoded       = $request->get_param( 'chunk_base64' );
					$final         = $request->get_param( 'final' );
					if (
						$config['deployment_id'] !== $deployment_id
						|| ! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $deployment_id )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $expected_sha )
						|| ! hash_equals( (string) $config['expected_artifact_sha256'], $expected_sha )
						|| ! is_int( $expected_size )
						|| (int) $config['expected_artifact_size'] !== $expected_size
						|| ! is_int( $offset )
						|| 0 > $offset
						|| ! preg_match( '/^[a-f0-9]{64}$/', $chunk_sha )
						|| ! is_string( $encoded )
						|| ! is_bool( $final )
					) {
						return new WP_Error( 'c99_stage_metadata', 'Artifact staging metadata does not match the embedded immutable release.', array( 'status' => 400 ) );
					}
					$max_encoded_bytes = 4 * (int) ceil( (int) $config['stage_chunk_max_bytes'] / 3 );
					$encoded_length    = strlen( $encoded );
					if (
						'' === $encoded
						|| $encoded_length > $max_encoded_bytes
						|| 0 !== ( $encoded_length % 4 )
					) {
						return new WP_Error( 'c99_stage_chunk_encoding', 'The artifact staging chunk is not bounded canonical base64.', array( 'status' => 413 ) );
					}
					$chunk = base64_decode( $encoded, true );
					if (
						false === $chunk
						|| '' === $chunk
						|| strlen( $chunk ) > (int) $config['stage_chunk_max_bytes']
						|| ! hash_equals( $encoded, base64_encode( $chunk ) )
					) {
						return new WP_Error( 'c99_stage_chunk_encoding', 'The artifact staging chunk is not bounded canonical base64.', array( 'status' => 413 ) );
					}
					if ( ! hash_equals( $chunk_sha, hash( 'sha256', $chunk ) ) ) {
						return new WP_Error( 'c99_stage_chunk_integrity', 'The artifact staging chunk failed size or digest validation.', array( 'status' => 422 ) );
					}
					$chunk_size = strlen( $chunk );
					$next_offset = $offset + $chunk_size;
					if (
						$next_offset > $expected_size
						|| $final !== ( $next_offset === $expected_size )
					) {
						return new WP_Error( 'c99_stage_chunk_boundary', 'The artifact staging chunk exceeds or misstates the immutable artifact boundary.', array( 'status' => 422 ) );
					}

					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					try {
						$lock = $read_lock( true );
						$lock_age = max( 0, time() - (int) ( $lock['updated_at'] ?? $lock['started_at'] ?? 0 ) );
						if (
							$deployment_id !== (string) ( $lock['deployment_id'] ?? '' )
							|| $lock_owner !== (string) ( $lock['owner_id'] ?? '' )
							|| 'reserved' !== (string) ( $lock['phase'] ?? '' )
							|| 1 > (int) ( $lock['fence'] ?? 0 )
							|| $lock_age >= (int) $config['recovery_lease_seconds']
						) {
							return new WP_Error( 'c99_stage_lock', 'Artifact staging requires the exact fresh owned deployment reservation.', array( 'status' => 409 ) );
						}
						if ( file_exists( $state_directory( $deployment_id ) ) || is_link( $state_directory( $deployment_id ) ) ) {
							return new WP_Error( 'c99_stage_state_exists', 'Artifact staging is closed after rollback state preparation begins.', array( 'status' => 409 ) );
						}
						$stage_dir = $protect_staging_directory( $deployment_id );
						if ( is_wp_error( $stage_dir ) ) {
							return $stage_dir;
						}
						$artifact_path = $staged_artifact_path( $deployment_id );
						$metadata_path = $staged_metadata_path( $deployment_id );
						$metadata_exists = file_exists( $metadata_path ) || is_link( $metadata_path );
						if ( ! $metadata_exists ) {
							if ( 0 !== $offset ) {
								return new WP_Error( 'c99_stage_gap', 'Artifact staging must begin at exact byte offset zero.', array( 'status' => 409 ) );
							}
							$reset = $cleanup_staging( $deployment_id );
							if ( is_wp_error( $reset ) ) {
								return $reset;
							}
							$stage_dir = $protect_staging_directory( $deployment_id );
							if ( is_wp_error( $stage_dir ) ) {
								return $stage_dir;
							}
							$handle = @fopen( $artifact_path, 'x+b' );
							if ( false === $handle ) {
								return new WP_Error( 'c99_stage_artifact_create', 'The isolated staging artifact could not be created exclusively.', array( 'status' => 500 ) );
							}
							@chmod( $artifact_path, FS_CHMOD_FILE );
							$metadata = array(
								'schema'                   => 'complete99-artifact-stage/v1',
								'deployment_id'            => $deployment_id,
								'expected_artifact_sha256' => $expected_sha,
								'expected_artifact_size'   => $expected_size,
								'received_bytes'           => 0,
								'complete'                 => false,
								'artifact_sha256'          => '',
								'last_offset'              => 0,
								'last_size'                => 0,
								'last_sha256'              => str_repeat( '0', 64 ),
								'last_final'               => false,
								'updated_at'               => time(),
							);
						} else {
							$metadata = $read_stage_metadata( $deployment_id );
							if ( is_wp_error( $metadata ) ) {
								return $metadata;
							}
							if ( is_link( $artifact_path ) || is_dir( $artifact_path ) || ! is_file( $artifact_path ) ) {
								return new WP_Error( 'c99_stage_artifact_unsafe', 'The isolated staging artifact is unsafe.', array( 'status' => 409 ) );
							}
							$handle = @fopen( $artifact_path, 'r+b' );
							if ( false === $handle ) {
								return new WP_Error( 'c99_stage_artifact_open', 'The isolated staging artifact could not be opened.', array( 'status' => 500 ) );
							}
						}

						if ( ! @flock( $handle, LOCK_EX ) ) {
							@fclose( $handle );
							return new WP_Error( 'c99_stage_artifact_flock', 'The isolated staging artifact could not be locked.', array( 'status' => 500 ) );
						}
						$file_stat = @fstat( $handle );
						$path_stat = @lstat( $artifact_path );
						$resolved_stage_dir = realpath( $stage_dir );
						$resolved_artifact  = realpath( $artifact_path );
						$received  = (int) $metadata['received_bytes'];
						if (
							! is_array( $file_stat )
							|| ! is_array( $path_stat )
							|| false === $resolved_stage_dir
							|| false === $resolved_artifact
							|| wp_normalize_path( dirname( $resolved_artifact ) ) !== wp_normalize_path( $resolved_stage_dir )
							|| 0100000 !== ( (int) ( $file_stat['mode'] ?? 0 ) & 0170000 )
							|| (int) ( $file_stat['ino'] ?? -1 ) !== (int) ( $path_stat['ino'] ?? -2 )
							|| (int) ( $file_stat['dev'] ?? -1 ) !== (int) ( $path_stat['dev'] ?? -2 )
							|| $received !== (int) ( $file_stat['size'] ?? -1 )
							|| $received > $expected_size
						) {
							@flock( $handle, LOCK_UN );
							@fclose( $handle );
							return new WP_Error( 'c99_stage_length_state', 'Artifact staging byte state or file identity does not match its isolated file.', array( 'status' => 409 ) );
						}
						if ( $offset < $received ) {
							$identical_replay = $offset === (int) $metadata['last_offset']
								&& $chunk_size === (int) $metadata['last_size']
								&& $next_offset === $received
								&& $final === $metadata['last_final']
								&& hash_equals( $chunk_sha, (string) $metadata['last_sha256'] );
							if ( $identical_replay && 0 === @fseek( $handle, $offset, SEEK_SET ) ) {
								$existing = '';
								while ( strlen( $existing ) < $chunk_size && ! feof( $handle ) ) {
									$part = @fread( $handle, $chunk_size - strlen( $existing ) );
									if ( false === $part || '' === $part ) {
										break;
									}
									$existing .= $part;
								}
								$identical_replay = strlen( $existing ) === $chunk_size && hash_equals( $chunk, $existing );
							}
							@flock( $handle, LOCK_UN );
							@fclose( $handle );
							if ( ! $identical_replay ) {
								$error_code = $offset === (int) $metadata['last_offset'] ? 'c99_stage_replay_changed' : 'c99_stage_overlap';
								return new WP_Error( $error_code, 'Artifact staging rejected an overlap or changed replay.', array( 'status' => 409 ) );
							}
							$heartbeat = $heartbeat_lock( $deployment_id, $lock_owner, (int) $lock['fence'], 'reserved' );
							if ( is_wp_error( $heartbeat ) ) {
								return $heartbeat;
							}
							return array(
								'deployment_id'  => $deployment_id,
								'accepted_offset'=> $offset,
								'next_offset'    => $next_offset,
								'total_bytes'    => $next_offset,
								'complete'       => $final,
								'artifact_sha256'=> $final ? $expected_sha : '',
							);
						}
						if ( $offset > $received ) {
							@flock( $handle, LOCK_UN );
							@fclose( $handle );
							return new WP_Error( 'c99_stage_gap', 'Artifact staging rejected a nonsequential byte gap.', array( 'status' => 409 ) );
						}
						if ( true === $metadata['complete'] ) {
							@flock( $handle, LOCK_UN );
							@fclose( $handle );
							return new WP_Error( 'c99_stage_complete_overlap', 'The completed staged artifact cannot be extended.', array( 'status' => 409 ) );
						}
						if ( 0 !== @fseek( $handle, $offset, SEEK_SET ) ) {
							@flock( $handle, LOCK_UN );
							@fclose( $handle );
							return new WP_Error( 'c99_stage_seek', 'The staged artifact offset could not be selected.', array( 'status' => 500 ) );
						}
						$written = 0;
						while ( $written < $chunk_size ) {
							$count = @fwrite( $handle, substr( $chunk, $written ) );
							if ( false === $count || 0 === $count ) {
								break;
							}
							$written += $count;
						}
						$flushed = $written === $chunk_size && @fflush( $handle );
						if ( $flushed && function_exists( 'fsync' ) ) {
							$flushed = @fsync( $handle );
						}
						$post_write_stat = @fstat( $handle );
						@flock( $handle, LOCK_UN );
						@fclose( $handle );
						if ( ! $flushed || ! is_array( $post_write_stat ) || $next_offset !== (int) ( $post_write_stat['size'] ?? -1 ) ) {
							$cleanup = $cleanup_staging( $deployment_id );
							return is_wp_error( $cleanup )
								? $cleanup
								: new WP_Error( 'c99_stage_write', 'The artifact staging chunk could not be committed exactly.', array( 'status' => 500 ) );
						}

						$artifact_sha = '';
						if ( $final ) {
							$artifact_sha = (string) @hash_file( 'sha256', $artifact_path );
							if ( ! hash_equals( $expected_sha, $artifact_sha ) ) {
								$cleanup = $cleanup_staging( $deployment_id );
								return is_wp_error( $cleanup )
									? $cleanup
									: new WP_Error( 'c99_stage_artifact_digest', 'The completed staged artifact digest does not match the embedded release.', array( 'status' => 422 ) );
							}
							$archive_valid = $validate_staged_archive( $artifact_path );
							if ( is_wp_error( $archive_valid ) ) {
								$cleanup = $cleanup_staging( $deployment_id );
								return is_wp_error( $cleanup ) ? $cleanup : $archive_valid;
							}
						}
						$metadata = array(
							'schema'                   => 'complete99-artifact-stage/v1',
							'deployment_id'            => $deployment_id,
							'expected_artifact_sha256' => $expected_sha,
							'expected_artifact_size'   => $expected_size,
							'received_bytes'           => $next_offset,
							'complete'                 => $final,
							'artifact_sha256'          => $artifact_sha,
							'last_offset'              => $offset,
							'last_size'                => $chunk_size,
							'last_sha256'              => $chunk_sha,
							'last_final'               => $final,
							'updated_at'               => time(),
						);
						$metadata_written = $write_state_file( $metadata_path, $metadata );
						if ( is_wp_error( $metadata_written ) ) {
							$rollback_handle = @fopen( $artifact_path, 'r+b' );
							$rolled_back = false;
							if ( false !== $rollback_handle && @flock( $rollback_handle, LOCK_EX ) ) {
								$rolled_back = @ftruncate( $rollback_handle, $offset ) && @fflush( $rollback_handle );
								@flock( $rollback_handle, LOCK_UN );
							}
							if ( is_resource( $rollback_handle ) ) {
								@fclose( $rollback_handle );
							}
							if ( ! $rolled_back ) {
								$cleanup = $cleanup_staging( $deployment_id );
								return is_wp_error( $cleanup ) ? $cleanup : $metadata_written;
							}
							return $metadata_written;
						}
						$heartbeat = $heartbeat_lock( $deployment_id, $lock_owner, (int) $lock['fence'], 'reserved' );
						if ( is_wp_error( $heartbeat ) ) {
							$cleanup = $cleanup_staging( $deployment_id );
							return is_wp_error( $cleanup ) ? $cleanup : $heartbeat;
						}
						return array(
							'deployment_id'  => $deployment_id,
							'accepted_offset'=> $offset,
							'next_offset'    => $next_offset,
							'total_bytes'    => $next_offset,
							'complete'       => $final,
							'artifact_sha256'=> $artifact_sha,
						);
					} finally {
						$release_process_lock( $process_lock );
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/run',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $validate_embedded_artifact_identity, $inspect_staged_artifact, $validate_staged_archive, $consume_staged_artifact, $cleanup_staging, $state_directory, $auto_update_enabled, $purge_caches, $claim_lock, $release_lock, $acquire_process_lock, $release_process_lock, $acquire_worker_fence, $release_worker_fence, $write_state_file, $heartbeat_state, $set_state_phase, $make_test_lock_stale, $directory_sha256, $verify_transactional_storage, $verify_migration_advisory_lock, $capture_database_state, $capture_quiescent_database_state, $campaign_snapshot_coherent, $encrypt_database_state, $decrypt_database_state, $capture_robots_snapshot, $apply_managed_robots, $restore_managed_robots, $ops_quarantine_residue ) {
					global $wp_filesystem;

					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$ops_residue = $ops_quarantine_residue();
					if ( is_wp_error( $ops_residue ) || ! empty( $ops_residue ) ) {
						return is_wp_error( $ops_residue )
							? $ops_residue
							: new WP_Error( 'c99_deploy_ops_rollback_residue', 'A prior operations rollback quarantine must be reconciled before redeployment.', array( 'status' => 409, 'table_count' => count( $ops_residue ) ) );
					}
					if ( $auto_update_enabled() ) {
						return new WP_Error(
							'c99_deploy_auto_update_enabled',
							'Automatic updates must be disabled for the deliberate deployment plugin.',
							array( 'status' => 409 )
						);
					}
					$embedded_identity = $validate_embedded_artifact_identity();
					if ( is_wp_error( $embedded_identity ) ) {
						return $embedded_identity;
					}

					$slug          = sanitize_key( (string) $request->get_param( 'slug' ) );
					$type          = sanitize_key( (string) $request->get_param( 'type' ) );
					$version       = sanitize_text_field( (string) $request->get_param( 'version' ) );
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					$expected      = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_sha256' ) ) );
					$activate      = rest_sanitize_boolean( $request->get_param( 'activate' ) );
					$staged        = $request->get_param( 'staged' );
					$json_params   = $request->get_json_params();
					$run_request_keys = is_array( $json_params ) ? array_keys( $json_params ) : array();
					$expected_run_request_keys = array( 'activate', 'deployment_id', 'expected_sha256', 'slug', 'staged', 'token', 'type', 'version' );
					sort( $run_request_keys, SORT_STRING );
					if (
						$expected_run_request_keys !== $run_request_keys
						|| ( is_array( $json_params ) && array_key_exists( 'package_base64', $json_params ) )
						|| null !== $request->get_param( 'package_base64' )
					) {
						return new WP_Error( 'c99_deploy_transport', 'The run route accepts only a previously completed staged artifact.', array( 'status' => 400 ) );
					}

					if ( $config['slug'] !== $slug || 'plugin' !== $type ) {
						return new WP_Error( 'c99_deploy_allowlist', 'The requested component is not allowlisted.', array( 'status' => 403 ) );
					}
					if ( $config['deployment_id'] !== $deployment_id || ! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $deployment_id ) ) {
						return new WP_Error( 'c99_deploy_id', 'The deployment ID is invalid.', array( 'status' => 400 ) );
					}
					if (
						true !== $staged
						|| ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', $version )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $expected )
						|| ! hash_equals( (string) $config['expected_artifact_sha256'], $expected )
						|| ! hash_equals( (string) $config['expected_version'], $version )
					) {
						return new WP_Error( 'c99_deploy_metadata', 'Version or digest metadata is invalid.', array( 'status' => 400 ) );
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
					$stage_cleanup_on_exit = false;
					$stage_cleanup_result  = true;
					$deployment_worker_fence = null;
					try {
					$staged_before_claim = $inspect_staged_artifact( $deployment_id );
					if ( is_wp_error( $staged_before_claim ) ) {
						$cleanup_staging( $deployment_id );
						return $staged_before_claim;
					}
					$lock = $claim_lock( $deployment_id, array( 'reserved' ), 'locked', true, false );
					if ( is_wp_error( $lock ) ) {
						return $lock;
					}
					$deployment_worker_fence = $acquire_worker_fence();
					if ( is_wp_error( $deployment_worker_fence ) ) {
						$release_lock( $deployment_id, $lock );
						return $deployment_worker_fence;
					}
					$stage_cleanup_on_exit = true;
					$staged_after_claim = $inspect_staged_artifact( $deployment_id );
					if ( is_wp_error( $staged_after_claim ) ) {
						$release_lock( $deployment_id, $lock );
						return $staged_after_claim;
					}
					$archive_valid = $validate_staged_archive( (string) $staged_after_claim['path'] );
					if ( is_wp_error( $archive_valid ) ) {
						$release_lock( $deployment_id, $lock );
						return $archive_valid;
					}
					$actual = (string) $staged_after_claim['sha256'];
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
					$database_snapshot = $capture_quiescent_database_state();
					if ( is_wp_error( $database_snapshot ) ) {
						$release_lock( $deployment_id, $lock );
						return $database_snapshot;
					}
					if ( ! $campaign_snapshot_coherent( $database_snapshot ) ) {
						$release_lock( $deployment_id, $lock );
						return new WP_Error(
							'c99_deploy_campaign_schema_drift',
							'Campaign Studio schema marker and exact seven-table cohort must be wholly absent or wholly valid before deployment.',
							array( 'status' => 409 )
						);
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
					$robots_snapshot = $capture_robots_snapshot();
					if ( is_wp_error( $robots_snapshot ) ) {
						$release_lock( $deployment_id, $lock );
						return $robots_snapshot;
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
						'robots_prior_exists'=> ! empty( $robots_snapshot['robots_prior_exists'] ),
						'robots_prior_sha256'=> (string) $robots_snapshot['robots_prior_sha256'],
						'robots_prior_base64'=> (string) $robots_snapshot['robots_prior_base64'],
						'robots_applied'    => false,
						'robots_restored'   => false,
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
					$persisted_robots_exists = ! empty( $persisted_state['robots_prior_exists'] );
					$persisted_robots_sha256 = (string) ( $persisted_state['robots_prior_sha256'] ?? '' );
					$persisted_robots_base64 = (string) ( $persisted_state['robots_prior_base64'] ?? '' );
					$persisted_robots_bytes  = base64_decode( $persisted_robots_base64, true );
					$persisted_robots_valid  = $persisted_robots_exists
						? (
							false !== $persisted_robots_bytes
							&& strlen( $persisted_robots_bytes ) <= 65536
							&& preg_match( '/^[a-f0-9]{64}$/', $persisted_robots_sha256 )
							&& hash_equals( $persisted_robots_sha256, hash( 'sha256', $persisted_robots_bytes ) )
						)
						: ( '' === $persisted_robots_sha256 && '' === $persisted_robots_base64 );
					if ( ! $persisted_robots_valid ) {
						$wp_filesystem->delete( $state_dir, true );
						$release_lock( $deployment_id, $lock );
						return new WP_Error( 'c99_robots_journal_readback', 'The persisted robots.txt rollback journal failed integrity validation.', array( 'status' => 500 ) );
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
					$consumed = $temp ? $consume_staged_artifact( $deployment_id, $temp ) : new WP_Error( 'c99_deploy_temp', 'Could not allocate the verified package installer path.', array( 'status' => 500 ) );
					if ( is_wp_error( $consumed ) ) {
						$temp_removed = ! $temp || ! $wp_filesystem->exists( $temp ) || ( $wp_filesystem->delete( $temp ) && ! $wp_filesystem->exists( $temp ) );
						$set_state_phase( $state_dir, $deployment_id, 'failed', array( 'temp_removed' => $temp_removed, 'temp_path' => '' ) );
						if ( ! $temp_removed ) {
							return new WP_Error( 'c99_deploy_temp_cleanup', 'The partial temporary package could not be removed.', array( 'status' => 500 ) );
						}
						return $consumed;
					}
					$stage_cleanup_on_exit = false;
					$actual = (string) $consumed['sha256'];
					$installing = $set_state_phase( $state_dir, $deployment_id, 'installing' );
					if ( is_wp_error( $installing ) ) {
						$wp_filesystem->delete( $temp );
						return $installing;
					}

					try {
						$install_response = ( static function () use ( $temp, $plugin_path, $target_dir, $version, $was_active, $activate, $config, $deployment_id, $actual, $slug, $purge_caches, $capture_database_state, $set_state_phase, $heartbeat_state, $directory_sha256, $state_dir, $apply_managed_robots, $restore_managed_robots ) {
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
						wp_opcache_invalidate_directory( $target_dir );
						clearstatcache( true, $plugin_path );
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
							/* PHP classes cannot be replaced in-process. Persist a candidate-code handoff. */
							$installed_plugin_sha256 = $directory_sha256( $target_dir );
							if ( is_wp_error( $installed_plugin_sha256 ) || ! hash_equals( (string) $config['expected_plugin_sha256'], (string) $installed_plugin_sha256 ) ) {
								return new WP_Error( 'c99_candidate_handoff_digest', 'The candidate plugin bytes could not be authenticated for fresh-request activation.', array( 'status' => 500 ) );
							}
							return array(
								'installed' => true,
								'continuation_required' => true,
								'deployment_id' => $deployment_id,
								'installed_plugin_sha256' => $installed_plugin_sha256,
								'prior_active' => $was_active,
								'requested_active' => true,
								'slug' => $slug,
								'version' => $version,
								'sha256' => $actual,
								'active' => is_plugin_active( $config['plugin_file'] ),
								'backup_ready' => true,
							);
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
						$state = json_decode( file_get_contents( trailingslashit( $state_dir ) . 'state.json' ), true );
						if ( ! is_array( $state ) ) {
							return new WP_Error( 'c99_robots_state', 'The robots rollback journal is unavailable.', array( 'status' => 500 ) );
						}
						$robots = $apply_managed_robots( $state_dir, $state );
						if ( is_wp_error( $robots ) ) {
							return $robots;
						}
						$robots_recorded = $set_state_phase(
							$state_dir,
							$deployment_id,
							'installing',
							array(
								'robots_applied'       => true,
								'robots_managed_sha256'=> (string) $robots['sha256'],
							)
						);
						if ( is_wp_error( $robots_recorded ) ) {
							$state['robots_applied']        = true;
							$state['robots_managed_sha256'] = (string) $robots['sha256'];
							$restored = $restore_managed_robots( $state_dir, $state );
							if ( is_wp_error( $restored ) ) {
								return $restored;
							}
							return $robots_recorded;
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
						if (
							is_wp_error( $installed_plugin_sha256 )
							|| ! preg_match( '/^[a-f0-9]{64}$/', $installed_plugin_sha256 )
							|| ! hash_equals( (string) $config['expected_plugin_sha256'], (string) $installed_plugin_sha256 )
						) {
							return new WP_Error( 'c99_installed_plugin_digest', 'The installed plugin directory fingerprint could not be captured.', array( 'status' => 500 ) );
						}
						$post_install_recorded = $set_state_phase(
							$state_dir,
							$deployment_id,
							'installing',
							array(
								'post_install_database_fingerprint' => $post_install_fingerprint,
								'installed_plugin_sha256'           => $installed_plugin_sha256,
								'installed_version'                 => $version,
								'installed_active'                  => is_plugin_active( $config['plugin_file'] ),
								'forward_ready'                     => true,
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
							'robots_sha256'=> (string) $robots['sha256'],
						);
						} )();
					} catch ( \Throwable $error ) {
						$install_response = new WP_Error( 'c99_deploy_exception', 'The plugin installation raised an exception.', array( 'status' => 500 ) );
					}
					if ( is_array( $install_response ) ) {
						$pending_cleanup = $set_state_phase(
							$state_dir,
							$deployment_id,
							! empty( $install_response['continuation_required'] ) ? 'candidate_activation_pending' : 'installed_pending_cleanup',
							array(
								'forward_ready'     => empty( $install_response['continuation_required'] ),
								'installed_version' => $version,
								'installed_active'  => ! empty( $install_response['active'] ),
								'candidate_activation_required' => ! empty( $install_response['continuation_required'] ),
								'candidate_activation_phase' => ! empty( $install_response['continuation_required'] ) ? 'pending' : '',
								'candidate_requested_active' => ! empty( $install_response['requested_active'] ),
								'candidate_prior_active' => ! empty( $install_response['prior_active'] ),
								'installed_plugin_sha256' => (string) ( $install_response['installed_plugin_sha256'] ?? '' ),
								'temp_removed'      => false,
								'temp_path'         => $temp,
							)
						);
						if ( is_wp_error( $pending_cleanup ) ) {
							return $pending_cleanup;
						}
					}
					if ( $config['local_test'] && 'after_install' === $config['test_fault'] && is_array( $install_response ) ) {
						$make_test_lock_stale( $deployment_id );
						return new WP_Error( 'c99_test_interrupt_install', 'Injected local interruption after plugin installation.', array( 'status' => 500 ) );
					}
					$temp_removed = ! $wp_filesystem->exists( $temp ) || ( $wp_filesystem->delete( $temp ) && ! $wp_filesystem->exists( $temp ) );
					if ( ! $temp_removed ) {
					$failure_phase = is_array( $install_response ) && ! empty( $install_response['continuation_required'] ) ? 'candidate_activation_pending' : ( is_array( $install_response ) ? 'installed_pending_cleanup' : 'failed' );
						$set_state_phase( $state_dir, $deployment_id, $failure_phase, array( 'temp_removed' => false, 'temp_path' => $temp ) );
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
						$install_response['robots_prior_exists'] = ! empty( $robots_snapshot['robots_prior_exists'] );
						$install_response['robots_prior_sha256'] = (string) $robots_snapshot['robots_prior_sha256'];
						$installed_state = $set_state_phase(
							$state_dir,
							$deployment_id,
							! empty( $install_response['continuation_required'] ) ? 'candidate_activation_pending' : 'installed_pending_stabilization',
							array(
								'temp_removed'      => true,
								'temp_path'         => '',
								'installed_version' => $version,
								'installed_active'  => ! empty( $install_response['active'] ),
								'forward_ready'     => empty( $install_response['continuation_required'] ),
								'candidate_activation_required' => ! empty( $install_response['continuation_required'] ),
								'candidate_activation_phase' => ! empty( $install_response['continuation_required'] ) ? 'pending' : '',
								'candidate_requested_active' => ! empty( $install_response['requested_active'] ),
								'candidate_prior_active' => ! empty( $install_response['prior_active'] ),
								'installed_plugin_sha256' => (string) ( $install_response['installed_plugin_sha256'] ?? '' ),
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
						$worker_fence_release = is_array( $deployment_worker_fence ) ? $release_worker_fence( $deployment_worker_fence ) : true;
						if ( $stage_cleanup_on_exit ) {
							$stage_cleanup_result = $cleanup_staging( $deployment_id );
						}
						$release_process_lock( $process_lock );
						if ( is_wp_error( $worker_fence_release ) ) {
							return $worker_fence_release;
						}
						if ( is_wp_error( $stage_cleanup_result ) ) {
							return $stage_cleanup_result;
						}
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/continue-activation',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $read_lock, $heartbeat_state, $set_state_phase, $acquire_process_lock, $release_process_lock, $acquire_worker_fence, $release_worker_fence, $directory_sha256, $apply_managed_robots, $purge_caches, $capture_database_state, $decrypt_database_state, $campaign_snapshot_coherent, $core_plugin_active_persisted, $deployment_id_valid ) {
					global $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) { return $filesystem; }
					$site = $verify_site_identity();
					if ( is_wp_error( $site ) ) { return $site; }
					$params = $request->get_json_params();
					$keys = is_array( $params ) ? array_keys( $params ) : array();
					sort( $keys, SORT_STRING );
					$deployment_id = (string) $request->get_param( 'deployment_id' );
					if ( array( 'deployment_id', 'token' ) !== $keys || ! $deployment_id_valid( $deployment_id ) || ! hash_equals( (string) $config['deployment_id'], $deployment_id ) ) { return new WP_Error( 'c99_candidate_activation_request', 'Candidate activation continuation request is invalid.', array( 'status' => 400 ) ); }
					$process = $acquire_process_lock();
					if ( is_wp_error( $process ) ) { return $process; }
					$fence = null;
					try {
						$state_dir = $state_directory( $deployment_id );
						$state_path = trailingslashit( $state_dir ) . 'state.json';
						$state = $wp_filesystem->exists( $state_path ) ? json_decode( $wp_filesystem->get_contents( $state_path ), true ) : null;
						$lock = $read_lock( true );
						$phase = is_array( $state ) ? (string) ( $state['phase'] ?? '' ) : '';
						if ( ! is_array( $state ) || ! is_array( $lock ) || ! in_array( $phase, array( 'candidate_activation_pending', 'candidate_activation_complete', 'installed_pending_stabilization' ), true ) || ! hash_equals( $deployment_id, (string) ( $state['deployment_id'] ?? '' ) ) || ! hash_equals( $deployment_id, (string) ( $lock['deployment_id'] ?? '' ) ) || (int) ( $state['fence'] ?? 0 ) !== (int) ( $lock['fence'] ?? -1 ) || ! hash_equals( (string) ( $state['owner_id'] ?? '' ), (string) ( $lock['owner_id'] ?? '' ) ) ) { return new WP_Error( 'c99_candidate_activation_state', 'Candidate activation continuation lacks exact durable reservation and journal ownership.', array( 'status' => 409, 'phase' => $phase ) ); }
						$fence = $acquire_worker_fence();
						if ( is_wp_error( $fence ) ) { return $fence; }
						$fresh_state = json_decode( $wp_filesystem->get_contents( $state_path ), true );
						$fresh_lock = $read_lock( true );
						if ( ! is_array( $fresh_state ) || ! is_array( $fresh_lock ) || ! hash_equals( wp_json_encode( $state ), wp_json_encode( $fresh_state ) ) || ! hash_equals( wp_json_encode( $lock ), wp_json_encode( $fresh_lock ) ) ) { return new WP_Error( 'c99_candidate_activation_race', 'Candidate activation ownership changed after worker-fence acquisition.', array( 'status' => 409 ) ); }
						$journal = $decrypt_database_state( $state['database_journal'] ?? array() );
						$journal_json = is_wp_error( $journal ) ? false : wp_json_encode( $journal );
						if ( is_wp_error( $journal ) || false === $journal_json || ! preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $state['database_fingerprint'] ?? '' ) ) || ! hash_equals( (string) $state['database_fingerprint'], hash( 'sha256', $journal_json ) ) || ! $campaign_snapshot_coherent( $journal ) ) { return new WP_Error( 'c99_candidate_activation_journal', 'Candidate activation continuation could not authenticate its exact rollback journal.', array( 'status' => 409 ) ); }
						$target_dir = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
						$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
						$digest = $directory_sha256( $target_dir );
						if ( is_wp_error( $digest ) || ! hash_equals( (string) $config['expected_plugin_sha256'], (string) $digest ) || ! hash_equals( (string) ( $state['installed_plugin_sha256'] ?? '' ), (string) $digest ) || ! file_exists( $plugin_path ) ) { return new WP_Error( 'c99_candidate_activation_plugin', 'Candidate plugin bytes do not match the authenticated handoff.', array( 'status' => 409 ) ); }
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
						require_once $plugin_path;
						if ( ! defined( 'COMPLETE99_PLATFORM_VERSION' ) || ! hash_equals( (string) $config['expected_version'], (string) COMPLETE99_PLATFORM_VERSION ) || ! class_exists( 'Complete99_Platform', false ) || ! method_exists( 'Complete99_Platform', 'recover_active_upgrade' ) || ! class_exists( 'Complete99_Campaigns', false ) || ! method_exists( 'Complete99_Campaigns', 'assert_invariants' ) ) { return new WP_Error( 'c99_candidate_activation_runtime', 'Fresh candidate classes are not the reviewed runtime.', array( 'status' => 409 ) ); }
						$core_active = $core_plugin_active_persisted( $config['plugin_file'] );
						if ( is_wp_error( $core_active ) ) { return $core_active; }
						if ( in_array( $phase, array( 'candidate_activation_complete', 'installed_pending_stabilization' ), true ) ) {
							$complete_exact = true === $core_active && ! empty( $state['forward_ready'] ) && ! empty( $state['installed_active'] ) && 'complete' === (string) ( $state['candidate_activation_phase'] ?? '' ) && is_int( $state['candidate_activation_completed_at'] ?? null ) && 0 < $state['candidate_activation_completed_at'] && preg_match( '/\A[a-f0-9]{64}\z/', (string) ( $state['candidate_database_fingerprint'] ?? '' ) );
							try { $complete_exact = $complete_exact && true === Complete99_Campaigns::assert_invariants(); } catch ( \Throwable $error ) { $complete_exact = false; }
							$current_snapshot = $complete_exact ? $capture_database_state() : null;
							$current_snapshot_json = is_wp_error( $current_snapshot ) || ! is_array( $current_snapshot ) ? false : wp_json_encode( $current_snapshot );
							$complete_exact = $complete_exact && false !== $current_snapshot_json && hash_equals( (string) $state['candidate_database_fingerprint'], hash( 'sha256', $current_snapshot_json ) );
							if ( ! $complete_exact ) { return new WP_Error( 'c99_candidate_activation_complete_invalid', 'Candidate activation completion proof is not exact.', array( 'status' => 409 ) ); }
							if ( 'installed_pending_stabilization' === $phase ) {
								$aligned = $set_state_phase( $state_dir, $deployment_id, $phase, array( 'candidate_activation_phase' => 'complete', 'candidate_activation_completed_at' => (int) $state['candidate_activation_completed_at'], 'candidate_database_fingerprint' => (string) $state['candidate_database_fingerprint'], 'forward_ready' => true, 'installed_active' => true ) );
								return is_wp_error( $aligned ) ? $aligned : array( 'continued' => true, 'idempotent' => true, 'phase' => $phase, 'active' => true, 'deployment_id' => $deployment_id );
							}
							$pending = $set_state_phase( $state_dir, $deployment_id, 'installed_pending_stabilization', array( 'candidate_activation_phase' => 'complete', 'candidate_activation_completed_at' => (int) $state['candidate_activation_completed_at'], 'candidate_database_fingerprint' => (string) $state['candidate_database_fingerprint'], 'forward_ready' => true, 'installed_active' => true ) );
							return is_wp_error( $pending ) ? $pending : array( 'continued' => true, 'idempotent' => true, 'phase' => 'installed_pending_stabilization', 'active' => true, 'deployment_id' => $deployment_id );
						}
						if ( ! empty( $state['candidate_prior_active'] ) ) {
							if ( true !== $core_active ) { return new WP_Error( 'c99_candidate_activation_core_state', 'Active upgrade lost persisted core plugin membership.', array( 'status' => 409 ) ); }
							$activation = Complete99_Platform::recover_active_upgrade();
						} elseif ( true === $core_active ) {
							/* Prior activate_plugin may have persisted core truth before its response was lost. */
							$activation = Complete99_Platform::recover_active_upgrade();
						} else {
							$activation = activate_plugin( $config['plugin_file'] );
						}
						if ( is_wp_error( $activation ) ) { return $activation; }
						$core_after = $core_plugin_active_persisted( $config['plugin_file'] );
						if ( true !== $core_after ) { return is_wp_error( $core_after ) ? $core_after : new WP_Error( 'c99_candidate_activation_core_readback', 'Core active plugin persistence was not acknowledged.', array( 'status' => 500 ) ); }
						update_option( 'complete99_last_deployment_id', $deployment_id, false );
						$robots = $apply_managed_robots( $state_dir, $state );
						if ( is_wp_error( $robots ) ) { return $robots; }
						$purged = $purge_caches();
						if ( is_wp_error( $purged ) ) { return $purged; }
						$snapshot = $capture_database_state();
						$snapshot_json = is_wp_error( $snapshot ) || ! is_array( $snapshot ) ? false : wp_json_encode( $snapshot );
						if ( is_wp_error( $snapshot ) || false === $snapshot_json || ! $campaign_snapshot_coherent( $snapshot ) ) { return new WP_Error( 'c99_candidate_activation_snapshot', 'Candidate activation database proof is unavailable.', array( 'status' => 500 ) ); }
						$complete = $set_state_phase( $state_dir, $deployment_id, 'candidate_activation_complete', array( 'candidate_activation_phase' => 'complete', 'candidate_activation_completed_at' => time(), 'candidate_database_fingerprint' => hash( 'sha256', $snapshot_json ), 'forward_ready' => true, 'installed_active' => true, 'robots_applied' => true, 'robots_managed_sha256' => (string) ( $robots['sha256'] ?? '' ) ) );
						if ( is_wp_error( $complete ) ) { return $complete; }
						$pending = $set_state_phase( $state_dir, $deployment_id, 'installed_pending_stabilization', array( 'candidate_activation_phase' => 'complete', 'candidate_activation_completed_at' => (int) $complete['candidate_activation_completed_at'], 'candidate_database_fingerprint' => (string) $complete['candidate_database_fingerprint'], 'forward_ready' => true, 'installed_active' => true ) );
						return is_wp_error( $pending ) ? $pending : array( 'continued' => true, 'phase' => 'installed_pending_stabilization', 'active' => true, 'deployment_id' => $deployment_id );
					} finally {
						$fence_release = is_array( $fence ) ? $release_worker_fence( $fence ) : true;
						$release_process_lock( $process );
						if ( is_wp_error( $fence_release ) ) { return $fence_release; }
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
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $cleanup_staging, $state_directory, $purge_caches, $read_lock, $claim_lock, $acquire_process_lock, $release_process_lock, $acquire_worker_fence, $release_worker_fence, $adopt_state_lease, $heartbeat_state, $set_state_phase, $make_test_lock_stale, $directory_sha256, $capture_database_state, $database_snapshot_generation, $campaign_snapshot_coherent, $normalize_database_snapshot, $project_database_snapshot, $restore_database_state, $decrypt_database_state, $restore_managed_robots, $reapply_managed_robots, $capture_ops_tables, $capture_campaign_tables, $ops_quarantine_names, $campaign_quarantine_names, $ops_quarantine_residue, $ops_snapshot_valid, $campaign_snapshot_valid, $ops_snapshot_digest, $campaign_snapshot_digest, $ops_snapshot_has_tables, $campaign_snapshot_has_tables, $ops_reconstruct_forward, $campaign_reconstruct_forward, $protected_rejoin_forward, $protected_cleanup_quarantine ) {
					global $wpdb, $wp_filesystem;
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
					$deployment_worker_fence = null;
					try {
					$staging_cleaned = $cleanup_staging( $deployment_id );
					if ( is_wp_error( $staging_cleaned ) ) {
						return $staging_cleaned;
					}
					$state_dir  = $state_directory( $deployment_id );
					$state_file = trailingslashit( $state_dir ) . 'state.json';
					if ( ! $wp_filesystem->exists( $state_file ) ) {
						return new WP_Error( 'c99_rollback_state', 'Rollback state was not found.', array( 'status' => 404 ) );
					}
					$state = json_decode( $wp_filesystem->get_contents( $state_file ), true );
					if ( ! is_array( $state ) ) {
						return new WP_Error( 'c99_rollback_state_invalid', 'Rollback state is invalid.', array( 'status' => 500 ) );
					}
					if ( ! empty( $state['adopted_forward_no_rollback'] ) ) {
						return new WP_Error(
							'c99_rollback_adopted_forward',
							'Rollback is categorically refused after proof-gated interrupted-forward adoption.',
							array(
								'status'      => 409,
								'phase'       => (string) ( $state['phase'] ?? '' ),
								'deployment_id'=> $deployment_id,
							)
						);
					}
					$lock = $read_lock( true );
					if ( $deployment_id !== (string) ( $lock['deployment_id'] ?? '' ) ) {
						return new WP_Error( 'c99_rollback_lock', 'The deployment does not own the mutation lock.', array( 'status' => 409 ) );
					}
					$phase = (string) ( $state['phase'] ?? '' );
					if ( 'rolled_back' === $phase ) {
						$residue = $ops_quarantine_residue();
						if ( is_wp_error( $residue ) || ! empty( $residue ) ) {
							return is_wp_error( $residue )
								? $residue
								: new WP_Error( 'c99_ops_rollback_residue', 'Rollback cannot report completion while operations quarantine tables remain.', array( 'status' => 409, 'table_count' => count( $residue ) ) );
						}
						return array(
							'rolled_back'     => true,
							'had_plugin'      => ! empty( $state['had_plugin'] ),
							'baseline_database_fingerprint'=> (string) ( $state['database_fingerprint'] ?? '' ),
							'prior_plugin_sha256'=> (string) ( $state['prior_plugin_sha256'] ?? '' ),
							'prior_version'   => isset( $state['prior_version'] ) ? (string) $state['prior_version'] : '',
							'prior_active'    => ! empty( $state['was_active'] ),
							'prior_deployment'=> isset( $state['prior_deployment'] ) ? (string) $state['prior_deployment'] : '',
							'database_restore'=> ! empty( $state['database_restored'] ) ? array( 'already_restored' => true ) : array(),
							'robots_prior_exists'=> ! empty( $state['robots_prior_exists'] ),
							'robots_prior_sha256'=> (string) ( $state['robots_prior_sha256'] ?? '' ),
							'robots_restore'  => ! empty( $state['robots_restored'] )
								? array( 'restored' => true, 'already_restored' => true )
								: array( 'restored' => false, 'not_managed' => true ),
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
					if ( ! $interrupted_phase && ! in_array( $phase, array( 'candidate_activation_pending', 'candidate_activation_complete', 'installed', 'installed_pending_stabilization', 'installed_pending_cleanup', 'failed', 'rollback_failed', 'commit_failed' ), true ) ) {
						return new WP_Error(
							'c99_rollback_not_ready',
							'Rollback is refused while the deployment is not in a terminal mutable phase.',
							array( 'status' => 409, 'phase' => $phase )
						);
					}
					$lease = $claim_lock(
						$deployment_id,
						array( 'prepared', 'installing', 'candidate_activation_pending', 'candidate_activation_complete', 'rolling_back', 'committing', 'installed', 'installed_pending_stabilization', 'installed_pending_cleanup', 'failed', 'rollback_failed', 'commit_failed' ),
						$phase,
						false,
						$interrupted_phase
					);
					if ( is_wp_error( $lease ) ) {
						return $lease;
					}
					$deployment_worker_fence = $acquire_worker_fence();
					if ( is_wp_error( $deployment_worker_fence ) ) {
						return $deployment_worker_fence;
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
					$journal_snapshot = $decrypt_database_state( $state['database_journal'] ?? array() );
					if ( is_wp_error( $journal_snapshot ) ) {
						return $journal_snapshot;
					}
					$journal_keys = array_keys( $journal_snapshot );
					$v1_journal_keys = array( 'options', 'postmeta', 'posts', 'seed_ids', 'evaluation_ids', 'sync_secret_existed', 'sync_secret_configured' );
					$v2_journal_keys = array( 'options', 'postmeta', 'posts', 'seed_ids', 'evaluation_ids', 'ops_tables', 'sync_secret_existed', 'sync_secret_configured' );
					$v3_journal_keys = array( 'options', 'postmeta', 'posts', 'seed_ids', 'evaluation_ids', 'ops_tables', 'campaign_tables', 'sync_secret_existed', 'sync_secret_configured' );
					$v1_option_keys = array(
						'active_plugins',
						'complete99_last_deployment_id',
						'complete99_evaluation_catalog_receipt',
						'complete99_os_public_url',
						'complete99_os_url',
						'complete99_platform_version',
						'page_on_front',
						'rewrite_rules',
						'show_on_front',
						$wpdb->prefix . 'user_roles',
					);
					$v2_option_keys = $v1_option_keys;
					array_splice( $v2_option_keys, 5, 0, array( 'complete99_ops_schema_version' ) );
					$v3_option_keys = $v2_option_keys;
					array_splice( $v3_option_keys, 5, 0, array( 'complete99_campaign_schema_version' ) );
					array_splice( $v3_option_keys, 6, 0, array( 'complete99_campaign_lifecycle_reservation_v1' ) );
					$journal_generation = $v1_journal_keys === $journal_keys
						? 1
						: ( $v2_journal_keys === $journal_keys ? 2 : ( $v3_journal_keys === $journal_keys ? 3 : 0 ) );
					$expected_option_keys = 1 === $journal_generation
						? $v1_option_keys
						: ( 2 === $journal_generation ? $v2_option_keys : $v3_option_keys );
					if (
						0 === $journal_generation
						|| ! is_array( $journal_snapshot['options'] ?? null )
						|| ! is_array( $journal_snapshot['posts'] ?? null )
						|| ! is_array( $journal_snapshot['postmeta'] ?? null )
						|| ! is_array( $journal_snapshot['seed_ids'] ?? null )
						|| ! is_array( $journal_snapshot['evaluation_ids'] ?? null )
						|| $expected_option_keys !== array_keys( $journal_snapshot['options'] )
						|| ( 2 <= $journal_generation && ! $ops_snapshot_valid( $journal_snapshot['ops_tables'] ?? null ) )
						|| ( 3 === $journal_generation && ! $campaign_snapshot_valid( $journal_snapshot['campaign_tables'] ?? null ) )
						|| ( 3 === $journal_generation && ! $campaign_snapshot_coherent( $journal_snapshot ) )
						|| ! is_bool( $journal_snapshot['sync_secret_existed'] ?? null )
						|| ! is_bool( $journal_snapshot['sync_secret_configured'] ?? null )
						|| ( $journal_snapshot['sync_secret_configured'] && ! $journal_snapshot['sync_secret_existed'] )
					) {
						return new WP_Error( 'c99_db_snapshot_invalid', 'The database rollback journal is invalid.', array( 'status' => 500 ) );
					}
					$journal_json = wp_json_encode( $journal_snapshot );
					$baseline_fingerprint = (string) ( $state['database_fingerprint'] ?? '' );
					if (
						false === $journal_json
						|| ! preg_match( '/^[a-f0-9]{64}$/', $baseline_fingerprint )
						|| ! hash_equals( $baseline_fingerprint, hash( 'sha256', $journal_json ) )
					) {
						return new WP_Error( 'c99_db_snapshot_digest', 'The database rollback journal failed integrity validation.', array( 'status' => 500 ) );
					}
					$database_snapshot = $normalize_database_snapshot( $journal_snapshot, $journal_generation );
					if ( is_wp_error( $database_snapshot ) ) {
						return $database_snapshot;
					}
					$normalized_baseline_json = wp_json_encode( $database_snapshot );
					if ( false === $normalized_baseline_json ) {
						return new WP_Error( 'c99_db_snapshot_encode', 'The normalized database rollback journal could not be encoded.', array( 'status' => 500 ) );
					}
					$comparison_baseline_fingerprint = hash( 'sha256', $normalized_baseline_json );
					$current_database_snapshot   = $capture_database_state();
					$current_database_generation = is_wp_error( $current_database_snapshot ) ? 0 : $database_snapshot_generation( $current_database_snapshot );
					$current_normalized_snapshot = is_wp_error( $current_database_snapshot )
						? $current_database_snapshot
						: $normalize_database_snapshot( $current_database_snapshot, $current_database_generation );
					$current_database_json = is_wp_error( $current_normalized_snapshot )
						? false
						: wp_json_encode( $current_normalized_snapshot );
					if ( is_wp_error( $current_normalized_snapshot ) || false === $current_database_json ) {
						return new WP_Error( 'c99_rollback_database_probe', 'The current plugin-owned database fingerprint could not be captured.', array( 'status' => 500 ) );
					}
					$current_database_fingerprint = hash( 'sha256', $current_database_json );
					$current_recorded_snapshot = 3 > $journal_generation
						? $project_database_snapshot( $current_normalized_snapshot, $journal_generation )
						: $current_database_snapshot;
					if ( is_wp_error( $current_recorded_snapshot ) ) {
						return $current_recorded_snapshot;
					}
					$current_recorded_json = wp_json_encode( $current_recorded_snapshot );
					$current_recorded_fingerprint = false === $current_recorded_json ? '' : hash( 'sha256', $current_recorded_json );
					$post_install_fingerprint = (string) ( $state['post_install_database_fingerprint'] ?? '' );
					$pending_sync_fingerprint = (string) ( $state['sync_configured_database_fingerprint'] ?? '' );
					$recorded_forward_ops_sha256 = (string) ( $state['rollback_forward_ops_sha256'] ?? '' );
					$recorded_forward_campaign_sha256 = (string) ( $state['rollback_forward_campaign_sha256'] ?? '' );
					if (
						( '' !== $recorded_forward_ops_sha256 && ! preg_match( '/^[a-f0-9]{64}$/', $recorded_forward_ops_sha256 ) )
						|| ( '' !== $recorded_forward_campaign_sha256 && ! preg_match( '/^[a-f0-9]{64}$/', $recorded_forward_campaign_sha256 ) )
					) {
						return new WP_Error( 'c99_protected_forward_digest', 'A recorded forward protected-table fingerprint is invalid.', array( 'status' => 500 ) );
					}
					$ops_quarantine_names      = $ops_quarantine_names( $deployment_id );
					$campaign_quarantine_names = $campaign_quarantine_names( $deployment_id );
					$ops_quarantine_snapshot      = $capture_ops_tables( $ops_quarantine_names );
					$campaign_quarantine_snapshot = $capture_campaign_tables( $campaign_quarantine_names );
					if ( is_wp_error( $ops_quarantine_snapshot ) || is_wp_error( $campaign_quarantine_snapshot ) ) {
						return new WP_Error( 'c99_protected_rollback_quarantine_probe', 'The protected rollback quarantine could not be inspected.', array( 'status' => 500 ) );
					}
					$ops_quarantine_present      = $ops_snapshot_has_tables( $ops_quarantine_snapshot );
					$campaign_quarantine_present = $campaign_snapshot_has_tables( $campaign_quarantine_snapshot );
					if (
						1 === $journal_generation
						&& (
							$ops_quarantine_present
							|| $campaign_quarantine_present
							|| $ops_snapshot_has_tables( $current_normalized_snapshot['ops_tables'] )
							|| $campaign_snapshot_has_tables( $current_normalized_snapshot['campaign_tables'] )
						)
					) {
						return new WP_Error(
							'c99_protected_legacy_journal_conflict',
							'Historical v1 rollback refused because protected tables exist outside the authenticated journal.',
							array( 'status' => 409 )
						);
					}
					$reconstructed_forward_ops = $ops_reconstruct_forward(
						$database_snapshot['ops_tables'],
						$current_normalized_snapshot['ops_tables'],
						$ops_quarantine_snapshot
					);
					$reconstructed_forward_campaign = $campaign_reconstruct_forward(
						$database_snapshot['campaign_tables'],
						$current_normalized_snapshot['campaign_tables'],
						$campaign_quarantine_snapshot
					);
					if ( is_wp_error( $reconstructed_forward_ops ) || is_wp_error( $reconstructed_forward_campaign ) ) {
						return is_wp_error( $reconstructed_forward_ops ) ? $reconstructed_forward_ops : $reconstructed_forward_campaign;
					}
					$reconstructed_forward_ops_sha256      = $ops_snapshot_digest( $reconstructed_forward_ops );
					$reconstructed_forward_campaign_sha256 = $campaign_snapshot_digest( $reconstructed_forward_campaign );
					if ( '' === $reconstructed_forward_ops_sha256 || '' === $reconstructed_forward_campaign_sha256 ) {
						return new WP_Error( 'c99_protected_forward_digest', 'A forward protected-table fingerprint could not be calculated.', array( 'status' => 500 ) );
					}
					$historical_campaign_projection = 3 > $journal_generation
						&& $ops_quarantine_present
						&& ! $campaign_quarantine_present
						&& ! $campaign_snapshot_has_tables( $database_snapshot['campaign_tables'] )
						&& ! $campaign_snapshot_has_tables( $current_normalized_snapshot['campaign_tables'] )
						&& ! array_key_exists( 'complete99_campaign_schema_version', $current_database_snapshot['options'] ?? array() )
						&& ! array_key_exists( 'complete99_campaign_lifecycle_reservation_v1', $current_database_snapshot['options'] ?? array() );
					if ( '' === $recorded_forward_campaign_sha256 && $historical_campaign_projection ) {
						$campaign_projection_checkpoint = $set_state_phase(
							$state_dir,
							$deployment_id,
							$phase,
							array( 'rollback_forward_campaign_sha256' => $reconstructed_forward_campaign_sha256 )
						);
						if ( is_wp_error( $campaign_projection_checkpoint ) ) {
							return $campaign_projection_checkpoint;
						}
						$state                              = $campaign_projection_checkpoint;
						$recorded_forward_campaign_sha256 = $reconstructed_forward_campaign_sha256;
					}

					$protected_quarantine_present = $ops_quarantine_present || $campaign_quarantine_present;
					if ( $protected_quarantine_present ) {
						if (
							'' === $recorded_forward_ops_sha256
							|| ! hash_equals( $recorded_forward_ops_sha256, $reconstructed_forward_ops_sha256 )
							|| '' === $recorded_forward_campaign_sha256
							|| ! hash_equals( $recorded_forward_campaign_sha256, $reconstructed_forward_campaign_sha256 )
						) {
							return new WP_Error( 'c99_protected_rollback_retry_proof', 'Rollback retry refused because the quarantined protected tables lack exact forward-state proof.', array( 'status' => 409 ) );
						}
						$synthetic_forward_snapshot               = $current_normalized_snapshot;
						$synthetic_forward_snapshot['ops_tables'] = $reconstructed_forward_ops;
						$synthetic_forward_snapshot['campaign_tables'] = $reconstructed_forward_campaign;
						$synthetic_recorded_generation = 1 === $journal_generation
							? 1
							: ( $campaign_snapshot_has_tables( $reconstructed_forward_campaign ) ? 3 : 2 );
						$synthetic_recorded_snapshot = $project_database_snapshot( $synthetic_forward_snapshot, $synthetic_recorded_generation );
						if ( is_wp_error( $synthetic_recorded_snapshot ) ) {
							return $synthetic_recorded_snapshot;
						}
						$synthetic_forward_json = wp_json_encode( $synthetic_recorded_snapshot );
						$synthetic_forward_fingerprint = false === $synthetic_forward_json ? '' : hash( 'sha256', $synthetic_forward_json );
						$synthetic_is_recorded_forward = (
							preg_match( '/^[a-f0-9]{64}$/', $post_install_fingerprint )
							&& hash_equals( $post_install_fingerprint, $synthetic_forward_fingerprint )
						) || (
							! empty( $state['sync_configuration_pending'] )
							&& preg_match( '/^[a-f0-9]{64}$/', $pending_sync_fingerprint )
							&& hash_equals( $pending_sync_fingerprint, $synthetic_forward_fingerprint )
						);
						if ( ! hash_equals( $comparison_baseline_fingerprint, $current_database_fingerprint ) ) {
							if ( ! $synthetic_is_recorded_forward ) {
								return new WP_Error( 'c99_protected_rollback_retry_conflict', 'Rollback retry found an unrecognized database state around the protected quarantine.', array( 'status' => 409 ) );
							}
							$rejoined = $protected_rejoin_forward( $deployment_id, $database_snapshot['ops_tables'], $database_snapshot['campaign_tables'], $recorded_forward_ops_sha256, $recorded_forward_campaign_sha256 );
							if ( is_wp_error( $rejoined ) ) {
								return $rejoined;
							}
							$current_database_snapshot   = $capture_database_state();
							$current_database_generation = is_wp_error( $current_database_snapshot ) ? 0 : $database_snapshot_generation( $current_database_snapshot );
							$current_normalized_snapshot = is_wp_error( $current_database_snapshot )
								? $current_database_snapshot
								: $normalize_database_snapshot( $current_database_snapshot, $current_database_generation );
							$current_database_json = is_wp_error( $current_normalized_snapshot ) ? false : wp_json_encode( $current_normalized_snapshot );
							if ( is_wp_error( $current_normalized_snapshot ) || false === $current_database_json ) {
								return new WP_Error( 'c99_protected_rejoin_database_probe', 'The database could not be verified after rejoining the forward protected tables.', array( 'status' => 500 ) );
							}
							$current_database_fingerprint = hash( 'sha256', $current_database_json );
							$current_recorded_snapshot = 3 > $journal_generation
								? $project_database_snapshot( $current_normalized_snapshot, $journal_generation )
								: $current_database_snapshot;
							if ( is_wp_error( $current_recorded_snapshot ) ) {
								return $current_recorded_snapshot;
							}
							$current_recorded_json = wp_json_encode( $current_recorded_snapshot );
							$current_recorded_fingerprint = false === $current_recorded_json ? '' : hash( 'sha256', $current_recorded_json );
							$ops_quarantine_snapshot      = $capture_ops_tables( $ops_quarantine_names );
							$campaign_quarantine_snapshot = $capture_campaign_tables( $campaign_quarantine_names );
							if (
								is_wp_error( $ops_quarantine_snapshot )
								|| is_wp_error( $campaign_quarantine_snapshot )
								|| $ops_snapshot_has_tables( $ops_quarantine_snapshot )
								|| $campaign_snapshot_has_tables( $campaign_quarantine_snapshot )
							) {
								return new WP_Error( 'c99_protected_rejoin_residue', 'The protected quarantine remains after forward-state recovery.', array( 'status' => 500 ) );
							}
							$ops_quarantine_present = false;
							$campaign_quarantine_present = false;
							$protected_quarantine_present = false;
						}
					}

					if ( hash_equals( $comparison_baseline_fingerprint, $current_database_fingerprint ) ) {
						$database_restore_required = false;
					} elseif (
						preg_match( '/^[a-f0-9]{64}$/', $post_install_fingerprint )
						&& hash_equals( $post_install_fingerprint, $current_recorded_fingerprint )
					) {
						$database_restore_required = true;
					} elseif (
						! empty( $state['sync_configuration_pending'] )
						&& preg_match( '/^[a-f0-9]{64}$/', $pending_sync_fingerprint )
						&& hash_equals( $pending_sync_fingerprint, $current_recorded_fingerprint )
					) {
						$database_restore_required = true;
					} else {
						return new WP_Error(
							'c99_rollback_database_conflict',
							'Rollback refused because plugin-owned data changed after installation.',
							array( 'status' => 409 )
						);
					}
					$forward_ops_sha256      = $recorded_forward_ops_sha256;
					$forward_campaign_sha256 = $recorded_forward_campaign_sha256;
					if ( $database_restore_required ) {
						$current_forward_ops_sha256      = $ops_snapshot_digest( $current_normalized_snapshot['ops_tables'] );
						$current_forward_campaign_sha256 = $campaign_snapshot_digest( $current_normalized_snapshot['campaign_tables'] );
						if ( '' === $current_forward_ops_sha256 || '' === $current_forward_campaign_sha256 ) {
							return new WP_Error( 'c99_protected_forward_digest', 'A current forward protected-table fingerprint is invalid.', array( 'status' => 500 ) );
						}
						if (
							( '' !== $forward_ops_sha256 && ! hash_equals( $forward_ops_sha256, $current_forward_ops_sha256 ) )
							|| ( '' !== $forward_campaign_sha256 && ! hash_equals( $forward_campaign_sha256, $current_forward_campaign_sha256 ) )
						) {
							return new WP_Error( 'c99_protected_forward_changed', 'Rollback refused because the candidate protected tables changed.', array( 'status' => 409 ) );
						}
						$forward_ops_sha256      = $current_forward_ops_sha256;
						$forward_campaign_sha256 = $current_forward_campaign_sha256;
						if ( '' === $recorded_forward_ops_sha256 || '' === $recorded_forward_campaign_sha256 ) {
							$protected_checkpoint = $set_state_phase(
								$state_dir,
								$deployment_id,
								$phase,
								array(
									'rollback_forward_ops_sha256'      => $forward_ops_sha256,
									'rollback_forward_campaign_sha256' => $forward_campaign_sha256,
								)
							);
							if ( is_wp_error( $protected_checkpoint ) ) {
								return $protected_checkpoint;
							}
							$state = $protected_checkpoint;
						}
					} elseif (
						$protected_quarantine_present
						&& ( '' === $forward_ops_sha256 || '' === $forward_campaign_sha256 )
					) {
						return new WP_Error( 'c99_protected_rollback_retry_proof', 'Rollback quarantine cleanup requires both recorded forward protected-table fingerprints.', array( 'status' => 409 ) );
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
						&& hash_equals( $comparison_baseline_fingerprint, $current_database_fingerprint )
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
					$compensate_forward = static function ( $error_code, $message, $status, $expected_database_fingerprint = '' ) use ( $config, $wp_filesystem, $target_dir, $restore_stage, $displaced_dir, $plugin_path, $forward_plugin_sha256, $forward_was_active, $directory_sha256, $capture_database_state, $database_snapshot_generation, $normalize_database_snapshot, $set_state_phase, $state_dir, $deployment_id, $state, $reapply_managed_robots ) {
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
						if ( '' === $compensation_error && ! empty( $state['robots_applied'] ) ) {
							$robots_compensation = $reapply_managed_robots( $state_dir, $state );
							if ( is_wp_error( $robots_compensation ) ) {
								$compensation_error = 'robots_restore_forward';
							}
						}
						if ( '' === $compensation_error && '' !== $expected_database_fingerprint ) {
							$compensated_snapshot = $capture_database_state();
							$compensated_generation = is_wp_error( $compensated_snapshot ) ? 0 : $database_snapshot_generation( $compensated_snapshot );
							$compensated_normalized = is_wp_error( $compensated_snapshot )
								? $compensated_snapshot
								: $normalize_database_snapshot( $compensated_snapshot, $compensated_generation );
							$compensated_json = is_wp_error( $compensated_normalized )
								? false
								: wp_json_encode( $compensated_normalized );
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
					$robots_restore = array( 'restored' => false, 'not_managed' => true );
					if ( ! empty( $state['robots_applied'] ) ) {
						$robots_restore = $restore_managed_robots( $state_dir, $state );
						if ( is_wp_error( $robots_restore ) ) {
							return $compensate_forward(
								'c99_robots_rollback_failed',
								'The plugin rollback was compensated because robots.txt could not be restored.',
								500
							);
						}
						$robots_checkpoint = $set_state_phase(
							$state_dir,
							$deployment_id,
							'rolling_back',
							array( 'robots_restored' => true )
						);
						if ( is_wp_error( $robots_checkpoint ) ) {
							return $compensate_forward(
								'c99_robots_rollback_checkpoint',
								'The plugin rollback was compensated because the robots.txt checkpoint could not be recorded.',
								500
							);
						}
					}
					$owned = $heartbeat_state( $state_dir, $deployment_id, 'rolling_back' );
					if ( is_wp_error( $owned ) ) {
						return $owned;
					}
					$pre_restore_snapshot = $capture_database_state();
					$pre_restore_generation = is_wp_error( $pre_restore_snapshot ) ? 0 : $database_snapshot_generation( $pre_restore_snapshot );
					$pre_restore_normalized = is_wp_error( $pre_restore_snapshot )
						? $pre_restore_snapshot
						: $normalize_database_snapshot( $pre_restore_snapshot, $pre_restore_generation );
					$pre_restore_json = is_wp_error( $pre_restore_normalized ) ? false : wp_json_encode( $pre_restore_normalized );
					$pre_restore_fingerprint = false === $pre_restore_json ? '' : hash( 'sha256', $pre_restore_json );
					if (
						is_wp_error( $pre_restore_normalized )
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
						$database_restore = $restore_database_state( $database_snapshot, $deployment_id, $journal_generation, $forward_ops_sha256, $forward_campaign_sha256 );
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
					$restored_database_generation = is_wp_error( $restored_database_snapshot ) ? 0 : $database_snapshot_generation( $restored_database_snapshot );
					$restored_database_normalized = is_wp_error( $restored_database_snapshot )
						? $restored_database_snapshot
						: $normalize_database_snapshot( $restored_database_snapshot, $restored_database_generation );
					$restored_database_json = is_wp_error( $restored_database_normalized )
						? false
						: wp_json_encode( $restored_database_normalized );
					$restored_database_fingerprint = false === $restored_database_json
						? ''
						: hash( 'sha256', $restored_database_json );
					if (
						is_wp_error( $restored_database_normalized )
						|| ! hash_equals( $comparison_baseline_fingerprint, $restored_database_fingerprint )
					) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_database_readback', 'The restored database did not match the exact rollback baseline.', array( 'status' => 500 ) );
					}
					if ( (bool) is_plugin_active( $config['plugin_file'] ) !== ! empty( $state['was_active'] ) ) {
						$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
						return new WP_Error( 'c99_rollback_activation_state', 'The restored plugin activation state does not match the rollback journal.', array( 'status' => 500 ) );
					}
					$protected_cleanup = array( 'already_clean' => true, 'tables_dropped' => 0 );
					if ( $database_restore_required || $protected_quarantine_present ) {
						$protected_cleanup = $protected_cleanup_quarantine( $deployment_id, $database_snapshot['ops_tables'], $database_snapshot['campaign_tables'], $forward_ops_sha256, $forward_campaign_sha256 );
						if ( is_wp_error( $protected_cleanup ) ) {
							$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
							return $protected_cleanup;
						}
						$clean_database_snapshot = $capture_database_state();
						$clean_database_generation = is_wp_error( $clean_database_snapshot ) ? 0 : $database_snapshot_generation( $clean_database_snapshot );
						$clean_database_normalized = is_wp_error( $clean_database_snapshot )
							? $clean_database_snapshot
							: $normalize_database_snapshot( $clean_database_snapshot, $clean_database_generation );
						$clean_database_json = is_wp_error( $clean_database_normalized ) ? false : wp_json_encode( $clean_database_normalized );
						$clean_database_fingerprint = false === $clean_database_json ? '' : hash( 'sha256', $clean_database_json );
						if ( is_wp_error( $clean_database_normalized ) || ! hash_equals( $comparison_baseline_fingerprint, $clean_database_fingerprint ) ) {
							$set_state_phase( $state_dir, $deployment_id, 'rollback_failed' );
							return new WP_Error( 'c99_protected_cleanup_database_readback', 'The database baseline changed during protected quarantine cleanup.', array( 'status' => 500 ) );
						}
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
							'ops_quarantine_cleaned'=> true,
							'protected_quarantine_cleaned'=> true,
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
						'ops_quarantine_cleanup'=> $protected_cleanup,
						'protected_quarantine_cleanup'=> $protected_cleanup,
						'robots_prior_exists'=> ! empty( $state['robots_prior_exists'] ),
						'robots_prior_sha256'=> (string) ( $state['robots_prior_sha256'] ?? '' ),
						'robots_restore'  => $robots_restore,
						'cache_purge'     => $cache_purge,
					);
					} finally {
						$worker_fence_release = is_array( $deployment_worker_fence ) ? $release_worker_fence( $deployment_worker_fence ) : true;
						$release_process_lock( $process_lock );
						if ( is_wp_error( $worker_fence_release ) ) {
							return $worker_fence_release;
						}
					}
				},
			)
		);

		register_rest_route(
			'complete99-deploy/v1',
			$route_prefix . '/reconcile-orphaned-rollback',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $bootstrap_filesystem, $verify_site_identity, $state_directory, $read_lock, $claim_lock, $heartbeat_lock, $acquire_process_lock, $release_process_lock, $acquire_worker_fence, $release_worker_fence, $directory_sha256, $capture_database_state_consistent, $database_snapshot_manifest, $database_snapshot_manifest_valid, $managed_robots_path, $purge_caches, $write_state_file, $protect_recovery_evidence_root, $verify_transactional_storage, $canonicalize_json_value ) {
					global $wpdb, $wp_filesystem;
					$filesystem = $bootstrap_filesystem();
					if ( is_wp_error( $filesystem ) ) {
						return $filesystem;
					}
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					$deployment_id = sanitize_text_field( (string) $request->get_param( 'deployment_id' ) );
					$proof_sha256 = strtolower( sanitize_text_field( (string) $request->get_param( 'proof_sha256' ) ) );
					$observed_deployment = sanitize_text_field( (string) $request->get_param( 'expected_observed_deployment' ) );
					$prior_deployment = sanitize_text_field( (string) $request->get_param( 'expected_prior_deployment' ) );
					$prior_version = sanitize_text_field( (string) $request->get_param( 'expected_prior_version' ) );
					$prior_database_version = sanitize_text_field( (string) $request->get_param( 'expected_prior_database_version' ) );
					$prior_active = rest_sanitize_boolean( $request->get_param( 'expected_prior_active' ) );
					$prior_plugin_sha256 = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_prior_plugin_sha256' ) ) );
					$baseline_database_fingerprint = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_baseline_database_fingerprint' ) ) );
					$prior_robots_exists = rest_sanitize_boolean( $request->get_param( 'expected_prior_robots_exists' ) );
					$prior_robots_sha256 = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_prior_robots_sha256' ) ) );
					$sync_configured = rest_sanitize_boolean( $request->get_param( 'expected_sync_configured' ) );
					$expected_observed_database_fingerprint = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_observed_database_fingerprint' ) ) );
					$expected_reconciled_database_fingerprint = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_reconciled_database_fingerprint' ) ) );
					$expected_preserved_manifest_sha256 = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_preserved_manifest_sha256' ) ) );
					$expected_attestation_sha256 = strtolower( sanitize_text_field( (string) $request->get_param( 'expected_attestation_sha256' ) ) );
					$expected_attestation_run_id = $request->get_param( 'expected_attestation_run_id' );
					$reviewed_proof = $request->get_param( 'reviewed_proof' );
					$failed_proof = is_array( $reviewed_proof ) ? ( $reviewed_proof['failed_run'] ?? null ) : null;
					$prior_proof = is_array( $reviewed_proof ) ? ( $reviewed_proof['prior_run'] ?? null ) : null;
					$database_reconciliation = is_array( $reviewed_proof ) ? ( $reviewed_proof['database_reconciliation'] ?? null ) : null;
					$proof_is_v2 = is_array( $database_reconciliation );
					$has_exact_keys = static function ( $record, $expected_keys ) {
						if ( ! is_array( $record ) ) {
							return false;
						}
						$actual_keys = array_keys( $record );
						sort( $actual_keys, SORT_STRING );
						sort( $expected_keys, SORT_STRING );
						return $actual_keys === $expected_keys;
					};
					$reviewed_proof_keys = $proof_is_v2
						? array( 'database_reconciliation', 'failed_run', 'prior_run' )
						: array( 'failed_run', 'prior_run' );
					$proof_shape_valid = $has_exact_keys( $reviewed_proof, $reviewed_proof_keys )
						&& $has_exact_keys(
							$failed_proof,
							array( 'artifact_sha256', 'audit_sha256', 'candidate_database_fingerprint', 'candidate_plugin_sha256', 'candidate_version', 'commit', 'deployment_id', 'run_id' )
						)
						&& $has_exact_keys(
							$prior_proof,
							array( 'active', 'audit_sha256', 'commit', 'database_fingerprint', 'database_version', 'deployment_id', 'plugin_sha256', 'robots_exists', 'robots_sha256', 'run_id', 'sync_configured', 'version' )
						);
					$canonical_proof_json = $proof_shape_valid
						? wp_json_encode( $canonicalize_json_value( $reviewed_proof ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
						: false;
					$canonical_proof_sha256 = false === $canonical_proof_json ? '' : hash( 'sha256', $canonical_proof_json );
					$base_reviewed_proof = array(
						'failed_run' => $failed_proof,
						'prior_run'  => $prior_proof,
					);
					$canonical_base_proof_json = $proof_shape_valid
						? wp_json_encode( $canonicalize_json_value( $base_reviewed_proof ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
						: false;
					$canonical_base_proof_sha256 = false === $canonical_base_proof_json ? '' : hash( 'sha256', $canonical_base_proof_json );
					$v2_reconciliation_valid = true;
					if ( $proof_is_v2 ) {
						$v2_reconciliation_valid = $has_exact_keys(
							$database_reconciliation,
							array(
								'attestation_audit_sha256',
								'attestation_path',
								'attestation_run_id',
								'attestation_sha256',
								'attestation_source_commit',
								'baseline_database_fingerprint',
								'expected_reconciled_database_fingerprint',
								'mode',
								'observed_database_fingerprint',
								'observed_deployment',
								'preserved_manifest',
								'preserved_manifest_sha256',
								'prior_proof_sha256',
								'schema',
								'target_deployment',
								'transactional_storage',
							)
						);
						$v2_digest_fields = array(
							'attestation_audit_sha256',
							'attestation_sha256',
							'baseline_database_fingerprint',
							'expected_reconciled_database_fingerprint',
							'observed_database_fingerprint',
							'preserved_manifest_sha256',
							'prior_proof_sha256',
						);
						foreach ( $v2_digest_fields as $v2_digest_field ) {
							$v2_reconciliation_valid = $v2_reconciliation_valid
								&& is_string( $database_reconciliation[ $v2_digest_field ] ?? null )
								&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $database_reconciliation[ $v2_digest_field ] ?? '' ) );
						}
						$v2_storage = $database_reconciliation['transactional_storage'] ?? null;
						$v2_attestation_path = (string) ( $database_reconciliation['attestation_path'] ?? '' );
						$v2_reconciliation_valid = $v2_reconciliation_valid
							&& 'complete99-orphaned-database-reconciliation/v1' === (string) ( $database_reconciliation['schema'] ?? '' )
							&& 'preserve-reviewed-drift-marker-only' === (string) ( $database_reconciliation['mode'] ?? '' )
							&& is_int( $database_reconciliation['attestation_run_id'] ?? null )
							&& (int) ( $database_reconciliation['attestation_run_id'] ?? 0 ) > (int) ( $failed_proof['run_id'] ?? 0 )
							&& is_string( $database_reconciliation['attestation_source_commit'] ?? null )
							&& preg_match( '/^[a-f0-9]{40}$/', (string) ( $database_reconciliation['attestation_source_commit'] ?? '' ) )
							&& (string) ( $database_reconciliation['attestation_source_commit'] ?? '' ) !== (string) ( $failed_proof['commit'] ?? '' )
							&& (string) ( $database_reconciliation['attestation_source_commit'] ?? '' ) !== (string) ( $prior_proof['commit'] ?? '' )
							&& is_string( $database_reconciliation['attestation_path'] ?? null )
							&& str_starts_with( $v2_attestation_path, 'docs/recovery-proofs/observations/' )
							&& ! str_contains( $v2_attestation_path, '..' )
							&& ! str_contains( $v2_attestation_path, '\\' )
							&& ! str_contains( $v2_attestation_path, '/./' )
							&& ! str_contains( $v2_attestation_path, '//' )
							&& str_ends_with( $v2_attestation_path, '.json' )
							&& $observed_deployment === (string) ( $database_reconciliation['observed_deployment'] ?? '' )
							&& $prior_deployment === (string) ( $database_reconciliation['target_deployment'] ?? '' )
							&& $baseline_database_fingerprint === (string) ( $database_reconciliation['baseline_database_fingerprint'] ?? '' )
							&& $expected_observed_database_fingerprint === (string) ( $database_reconciliation['observed_database_fingerprint'] ?? '' )
							&& $expected_reconciled_database_fingerprint === (string) ( $database_reconciliation['expected_reconciled_database_fingerprint'] ?? '' )
							&& $expected_preserved_manifest_sha256 === (string) ( $database_reconciliation['preserved_manifest_sha256'] ?? '' )
							&& $expected_attestation_sha256 === (string) ( $database_reconciliation['attestation_sha256'] ?? '' )
							&& hash_equals(
								(string) ( $database_reconciliation['attestation_sha256'] ?? '' ),
								(string) ( $database_reconciliation['attestation_audit_sha256'] ?? '' )
							)
							&& is_int( $expected_attestation_run_id )
							&& $expected_attestation_run_id === ( $database_reconciliation['attestation_run_id'] ?? null )
							&& hash_equals( $canonical_base_proof_sha256, (string) ( $database_reconciliation['prior_proof_sha256'] ?? '' ) )
							&& ! hash_equals( (string) ( $database_reconciliation['observed_database_fingerprint'] ?? '' ), (string) ( $database_reconciliation['expected_reconciled_database_fingerprint'] ?? '' ) )
							&& ! hash_equals( $baseline_database_fingerprint, (string) ( $database_reconciliation['observed_database_fingerprint'] ?? '' ) )
							&& ! hash_equals( $baseline_database_fingerprint, (string) ( $database_reconciliation['expected_reconciled_database_fingerprint'] ?? '' ) )
							&& $database_snapshot_manifest_valid(
								$database_reconciliation['preserved_manifest'] ?? null,
								$database_reconciliation['preserved_manifest_sha256'] ?? null
							)
							&& $has_exact_keys( $v2_storage, array( 'engine', 'tables' ) )
							&& is_string( $v2_storage['engine'] ?? null )
							&& in_array( $v2_storage['engine'], array( 'INNODB', 'XTRADB', 'INNODB,XTRADB' ), true )
							&& is_int( $v2_storage['tables'] ?? null )
							&& 3 === $v2_storage['tables'];
					}
					$proof_string_types_valid = $proof_shape_valid;
					foreach ( array( 'artifact_sha256', 'audit_sha256', 'candidate_database_fingerprint', 'candidate_plugin_sha256', 'candidate_version', 'commit', 'deployment_id' ) as $proof_key ) {
						$proof_string_types_valid = is_string( $failed_proof[ $proof_key ] ?? null ) && $proof_string_types_valid;
					}
					foreach ( array( 'audit_sha256', 'commit', 'database_fingerprint', 'database_version', 'deployment_id', 'plugin_sha256', 'robots_sha256', 'version' ) as $proof_key ) {
						$proof_string_types_valid = is_string( $prior_proof[ $proof_key ] ?? null ) && $proof_string_types_valid;
					}
					if (
						$config['deployment_id'] !== $deployment_id
						|| ! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $deployment_id )
						|| ! str_starts_with( $deployment_id, 'c99-' )
						|| $deployment_id !== $observed_deployment
						|| ! preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $prior_deployment )
						|| ! str_starts_with( $prior_deployment, 'c99-' )
						|| $deployment_id === $prior_deployment
						|| ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', $prior_version )
						|| $prior_version !== $prior_database_version
						|| ! $prior_active
						|| ! $prior_robots_exists
						|| ! $sync_configured
						|| ! preg_match( '/^[a-f0-9]{64}$/', $proof_sha256 )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $prior_plugin_sha256 )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $baseline_database_fingerprint )
						|| ! preg_match( '/^[a-f0-9]{64}$/', $prior_robots_sha256 )
						|| ! $proof_shape_valid
						|| ! $proof_string_types_valid
						|| ! $v2_reconciliation_valid
						|| ! is_int( $failed_proof['run_id'] ?? null )
						|| 0 >= (int) ( $failed_proof['run_id'] ?? 0 )
						|| ! is_int( $prior_proof['run_id'] ?? null )
						|| 0 >= (int) ( $prior_proof['run_id'] ?? 0 )
						|| (int) ( $failed_proof['run_id'] ?? 0 ) <= (int) ( $prior_proof['run_id'] ?? 0 )
						|| ! preg_match( '/^[a-f0-9]{40}$/', (string) ( $failed_proof['commit'] ?? '' ) )
						|| ! preg_match( '/^[a-f0-9]{40}$/', (string) ( $prior_proof['commit'] ?? '' ) )
						|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $failed_proof['artifact_sha256'] ?? '' ) )
						|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $failed_proof['audit_sha256'] ?? '' ) )
						|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $failed_proof['candidate_database_fingerprint'] ?? '' ) )
						|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $failed_proof['candidate_plugin_sha256'] ?? '' ) )
						|| ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', (string) ( $failed_proof['candidate_version'] ?? '' ) )
						|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $prior_proof['audit_sha256'] ?? '' ) )
						|| $deployment_id !== (string) ( $failed_proof['deployment_id'] ?? '' )
						|| $prior_deployment !== (string) ( $prior_proof['deployment_id'] ?? '' )
						|| ! str_starts_with( (string) ( $failed_proof['deployment_id'] ?? '' ), 'c99-' )
						|| ! str_starts_with( (string) ( $prior_proof['deployment_id'] ?? '' ), 'c99-' )
						|| (string) ( $failed_proof['deployment_id'] ?? '' ) === (string) ( $prior_proof['deployment_id'] ?? '' )
						|| ! str_contains( (string) ( $failed_proof['deployment_id'] ?? '' ), '-' . (string) ( $failed_proof['run_id'] ?? '' ) . '-' )
						|| ! str_contains( (string) ( $prior_proof['deployment_id'] ?? '' ), '-' . (string) ( $prior_proof['run_id'] ?? '' ) . '-' )
						|| $prior_version !== (string) ( $prior_proof['version'] ?? '' )
						|| $prior_database_version !== (string) ( $prior_proof['database_version'] ?? '' )
						|| true !== ( $prior_proof['active'] ?? null )
						|| true !== ( $prior_proof['robots_exists'] ?? null )
						|| true !== ( $prior_proof['sync_configured'] ?? null )
						|| $prior_plugin_sha256 !== (string) ( $prior_proof['plugin_sha256'] ?? '' )
						|| $baseline_database_fingerprint !== (string) ( $prior_proof['database_fingerprint'] ?? '' )
						|| $prior_robots_sha256 !== (string) ( $prior_proof['robots_sha256'] ?? '' )
						|| (string) ( $failed_proof['candidate_version'] ?? '' ) === (string) ( $prior_proof['version'] ?? '' )
						|| (string) ( $failed_proof['candidate_plugin_sha256'] ?? '' ) === (string) ( $prior_proof['plugin_sha256'] ?? '' )
						|| (string) ( $failed_proof['candidate_database_fingerprint'] ?? '' ) === (string) ( $prior_proof['database_fingerprint'] ?? '' )
						|| ! hash_equals( $proof_sha256, $canonical_proof_sha256 )
					) {
						return new WP_Error( 'c99_orphaned_proof_invalid', 'The orphaned rollback proof is invalid.', array( 'status' => 400 ) );
					}
					$process_lock = $acquire_process_lock();
					if ( is_wp_error( $process_lock ) ) {
						return $process_lock;
					}
					$deployment_worker_fence = null;
					try {
						$state_dir = $state_directory( $deployment_id );
						$state_file = trailingslashit( $state_dir ) . 'state.json';
						$receipt_root = trailingslashit( WP_CONTENT_DIR ) . '.complete99-deploy-recovery-evidence';
						$receipt_dir = trailingslashit( $receipt_root ) . substr( hash( 'sha256', $deployment_id ), 0, 32 );
						$receipt_file = trailingslashit( $receipt_dir ) . 'orphan-recovery-receipt.json';
						if ( is_link( $state_dir ) || is_link( $receipt_root ) || is_link( $receipt_dir ) || is_link( $receipt_file ) || is_dir( $receipt_file ) ) {
							return new WP_Error( 'c99_orphaned_recovery_path_unsafe', 'Orphaned rollback reconciliation found an unsafe recovery path.', array( 'status' => 409 ) );
						}
						if ( $wp_filesystem->exists( $state_file ) ) {
							return new WP_Error( 'c99_orphaned_state_present', 'Orphaned rollback reconciliation refuses a present state journal.', array( 'status' => 409 ) );
						}
						$lock = $read_lock( true );
						if (
							$deployment_id !== (string) ( $lock['deployment_id'] ?? '' )
							|| 'rolling_back' !== (string) ( $lock['phase'] ?? '' )
						) {
							return new WP_Error( 'c99_orphaned_lock_state', 'The orphaned rollback lock is not in the reviewed phase.', array( 'status' => 409 ) );
						}
						$reviewed_candidate_identity = array(
							'expected_sha256'                  => (string) $failed_proof['artifact_sha256'],
							'expected_version'                 => (string) $failed_proof['candidate_version'],
							'installed_plugin_sha256'          => (string) $failed_proof['candidate_plugin_sha256'],
							'post_install_database_fingerprint'=> (string) $failed_proof['candidate_database_fingerprint'],
						);
						foreach ( $reviewed_candidate_identity as $identity_key => $reviewed_identity_value ) {
							$locked_identity_value = (string) ( $lock[ $identity_key ] ?? '' );
							if ( '' !== $locked_identity_value && ! hash_equals( $reviewed_identity_value, $locked_identity_value ) ) {
								return new WP_Error( 'c99_orphaned_lock_identity', 'The orphaned rollback lock conflicts with the reviewed failed release.', array( 'status' => 409, 'field' => $identity_key ) );
							}
						}
						$lease = $claim_lock(
							$deployment_id,
							array( 'rolling_back' ),
							'rolling_back',
							false,
							true
						);
						if ( is_wp_error( $lease ) ) {
							return $lease;
						}
						$deployment_worker_fence = $acquire_worker_fence();
						if ( is_wp_error( $deployment_worker_fence ) ) {
							return $deployment_worker_fence;
						}
						$storage = $verify_transactional_storage();
						if ( is_wp_error( $storage ) ) {
							return $storage;
						}
						$database_class = strtolower( get_class( $wpdb ) );
						$database_type = defined( 'DB_ENGINE' ) ? strtolower( (string) DB_ENGINE ) : '';
						$database_is_sqlite = $config['local_test'] && ( 'sqlite' === $database_type || str_contains( $database_class, 'sqlite' ) );
						$database_is_mysql = ! $database_is_sqlite && isset( $wpdb->is_mysql ) && true === $wpdb->is_mysql;
						if ( ! $database_is_mysql && ! $database_is_sqlite ) {
							return new WP_Error( 'c99_orphaned_marker_driver', 'The orphaned rollback marker transaction driver is unsupported.', array( 'status' => 409 ) );
						}
						$marker_cas_transaction = static function ( $expected, $replacement, $operation ) use ( $wpdb, $database_is_mysql ) {
							$error_prefix = 'c99_orphaned_' . ( 'compensation' === $operation ? 'compensation' : 'marker' );
							$rollback_transaction = static function () use ( $wpdb ) {
								$wpdb->last_error = '';
								$rolled_back = $wpdb->query( 'ROLLBACK' );
								return false !== $rolled_back && '' === (string) $wpdb->last_error;
							};
							$begin_sql = $database_is_mysql ? 'START TRANSACTION' : 'BEGIN IMMEDIATE TRANSACTION';
							$wpdb->last_error = '';
							$started = $wpdb->query( $begin_sql );
							if ( false === $started || '' !== (string) $wpdb->last_error ) {
								return new WP_Error(
									$error_prefix . '_begin',
									'The orphaned rollback marker transaction could not start.',
									array( 'status' => 500 )
								);
							}
							$select_sql = "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1";
							if ( $database_is_mysql ) {
								$select_sql .= ' FOR UPDATE';
							}
							$wpdb->last_error = '';
							$locked_marker = $wpdb->get_var(
								$wpdb->prepare( $select_sql, 'complete99_last_deployment_id' )
							);
							if (
								'' !== (string) $wpdb->last_error
								|| ! is_string( $locked_marker )
								|| ! hash_equals( $expected, $locked_marker )
							) {
								$rolled_back = $rollback_transaction();
								return new WP_Error(
									$error_prefix . '_race',
									'The deployment marker changed during orphaned rollback reconciliation.',
									array( 'status' => 409, 'rollback_confirmed' => $rolled_back )
								);
							}
							$exact_value = $database_is_mysql
								? 'BINARY option_value = BINARY %s'
								: 'option_value = %s COLLATE BINARY';
							$wpdb->last_error = '';
							$updated = $wpdb->query(
								$wpdb->prepare(
									"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND {$exact_value}",
									$replacement,
									'complete99_last_deployment_id',
									$expected
								)
							);
							if ( 1 !== (int) $updated || '' !== (string) $wpdb->last_error ) {
								$rolled_back = $rollback_transaction();
								return new WP_Error(
									$error_prefix . '_cas',
									'The orphaned rollback marker compare-and-swap failed.',
									array(
										'status'             => 409,
										'rollback_confirmed' => $rolled_back,
									)
								);
							}
							$wpdb->last_error = '';
							$committed = $wpdb->query( 'COMMIT' );
							$commit_error = (string) $wpdb->last_error;
							if ( false === $committed || '' !== $commit_error ) {
								$rolled_back = $rollback_transaction();
								wp_cache_delete( 'complete99_last_deployment_id', 'options' );
								return new WP_Error(
									$error_prefix . '_commit',
									'The orphaned rollback marker transaction could not commit.',
									array(
										'status'             => 500,
										'rollback_confirmed' => $rolled_back,
									)
								);
							}
							wp_cache_delete( 'complete99_last_deployment_id', 'options' );
							$wpdb->last_error = '';
							$readback = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
									'complete99_last_deployment_id'
								)
							);
							if (
								'' !== (string) $wpdb->last_error
								|| ! is_string( $readback )
								|| ! hash_equals( $replacement, $readback )
							) {
								return new WP_Error(
									$error_prefix . '_readback',
									'The orphaned rollback marker transaction failed authoritative readback.',
									array( 'status' => 500 )
								);
							}
							return true;
						};
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
						$target_dir = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
						$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
						$swap_suffix = substr( hash( 'sha256', $deployment_id ), 0, 20 );
						$restore_stage = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix;
						$displaced_dir = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix;
						if (
							! $wp_filesystem->is_dir( $target_dir )
							|| ! $wp_filesystem->exists( $plugin_path )
							|| $wp_filesystem->exists( $restore_stage )
							|| $wp_filesystem->exists( $displaced_dir )
						) {
							return new WP_Error( 'c99_orphaned_filesystem_state', 'Orphaned rollback reconciliation found an unsafe plugin filesystem state.', array( 'status' => 409 ) );
						}
						$current = get_plugin_data( $plugin_path, false, false );
						$current_plugin_sha256 = $directory_sha256( $target_dir );
						$current_snapshot = $capture_database_state_consistent();
						$current_json = is_wp_error( $current_snapshot ) ? false : wp_json_encode( $current_snapshot );
						$current_fingerprint = false === $current_json ? '' : hash( 'sha256', $current_json );
						$active_plugins_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['active_plugins'] ?? null ) : null;
						$database_version_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_platform_version'] ?? null ) : null;
						$deployment_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_last_deployment_id'] ?? null ) : null;
						$active_plugins = is_array( $active_plugins_row )
							? maybe_unserialize( (string) ( $active_plugins_row['option_value'] ?? '' ) )
							: array();
						$current_active = is_array( $active_plugins ) && in_array( $config['plugin_file'], $active_plugins, true );
						$current_database_version = is_array( $database_version_row ) ? (string) ( $database_version_row['option_value'] ?? '' ) : '';
						$current_deployment = is_array( $deployment_row ) ? (string) ( $deployment_row['option_value'] ?? '' ) : '';
						if (
							is_wp_error( $current_snapshot )
							|| false === $current_json
							|| ! is_array( $active_plugins_row )
							|| ! is_array( $active_plugins )
							|| ! is_array( $database_version_row )
							|| ! is_array( $deployment_row )
							|| empty( $current_snapshot['sync_secret_configured'] )
							|| ! in_array( $current_deployment, array( $observed_deployment, $prior_deployment ), true )
						) {
							return new WP_Error( 'c99_orphaned_database_state', 'Orphaned rollback reconciliation found an invalid database state.', array( 'status' => 409 ) );
						}
						if (
							is_wp_error( $current_plugin_sha256 )
							|| ! $current_active
							|| $prior_version !== (string) ( $current['Version'] ?? '' )
							|| ! hash_equals( $prior_plugin_sha256, (string) $current_plugin_sha256 )
							|| $prior_database_version !== $current_database_version
						) {
							return new WP_Error( 'c99_orphaned_plugin_identity', 'Orphaned rollback reconciliation did not find the exact prior plugin identity.', array( 'status' => 409 ) );
						}
						$robots_path = $managed_robots_path();
						if ( is_wp_error( $robots_path ) ) {
							return $robots_path;
						}
						if ( is_link( $robots_path ) || is_dir( $robots_path ) || ! file_exists( $robots_path ) ) {
							return new WP_Error( 'c99_orphaned_robots_unsafe', 'Orphaned rollback reconciliation found an unsafe robots.txt target.', array( 'status' => 409 ) );
						}
						$current_robots_sha256 = (string) @hash_file( 'sha256', $robots_path );
						if ( ! hash_equals( $prior_robots_sha256, $current_robots_sha256 ) ) {
							return new WP_Error( 'c99_orphaned_robots_identity', 'Orphaned rollback reconciliation did not find the exact prior robots.txt identity.', array( 'status' => 409 ) );
						}
						$receipt_schema = 'complete99-orphaned-rollback-receipt/v1';
						$expected_committed_database_fingerprint = $baseline_database_fingerprint;
						$marker_corrected = false;
						$marker_rows_affected = 0;
						$marker_transition = 'already-correct';
						$reconciled_fingerprint = '';
						$reconciled_manifest_record = null;
						if ( $proof_is_v2 ) {
							$receipt_schema = 'complete99-orphaned-rollback-receipt/v2';
							$reviewed_observed_fingerprint = (string) $database_reconciliation['observed_database_fingerprint'];
							$reviewed_reconciled_fingerprint = (string) $database_reconciliation['expected_reconciled_database_fingerprint'];
							$reviewed_manifest = $database_reconciliation['preserved_manifest'];
							$reviewed_manifest_sha256 = (string) $database_reconciliation['preserved_manifest_sha256'];
							$expected_committed_database_fingerprint = $reviewed_reconciled_fingerprint;
							$current_manifest_record = $database_snapshot_manifest( $current_snapshot );
							$current_manifest_matches = is_array( $current_manifest_record )
								&& $database_snapshot_manifest_valid(
									$current_manifest_record['manifest'] ?? null,
									$current_manifest_record['manifest_sha256'] ?? null
								)
								&& hash_equals( $reviewed_manifest_sha256, (string) ( $current_manifest_record['manifest_sha256'] ?? '' ) );
							if (
								! $current_manifest_matches
								|| $storage !== $database_reconciliation['transactional_storage']
							) {
								return new WP_Error( 'c99_orphaned_v2_manifest', 'The database no longer matches the reviewed marker-neutral manifest.', array( 'status' => 409 ) );
							}
							$projected_snapshot = $current_snapshot;
							if ( $observed_deployment === $current_deployment ) {
								if ( ! hash_equals( $reviewed_observed_fingerprint, $current_fingerprint ) ) {
									return new WP_Error( 'c99_orphaned_v2_observed_state', 'The database no longer matches the reviewed observed state.', array( 'status' => 409 ) );
								}
								$projected_snapshot['options']['complete99_last_deployment_id']['option_value'] = $prior_deployment;
								$projected_json = wp_json_encode( $projected_snapshot );
								$projected_fingerprint = false === $projected_json ? '' : hash( 'sha256', $projected_json );
								if ( ! hash_equals( $reviewed_reconciled_fingerprint, $projected_fingerprint ) ) {
									return new WP_Error( 'c99_orphaned_v2_projection', 'The reviewed reconciled database is not an exact marker-only projection.', array( 'status' => 409 ) );
								}
								$lease = $heartbeat_lock(
									$deployment_id,
									(string) ( $lease['owner_id'] ?? '' ),
									(int) ( $lease['fence'] ?? 0 ),
									'rolling_back'
								);
								if ( is_wp_error( $lease ) ) {
									return $lease;
								}
								$corrected = $marker_cas_transaction( $observed_deployment, $prior_deployment, 'marker' );
								if ( is_wp_error( $corrected ) ) {
									return $corrected;
								}
								$marker_corrected = true;
								$marker_rows_affected = 1;
								$marker_transition = 'corrected';
							} else {
								if ( ! hash_equals( $reviewed_reconciled_fingerprint, $current_fingerprint ) ) {
									return new WP_Error( 'c99_orphaned_v2_reconciled_state', 'The interrupted reconciliation does not match its reviewed marker-only state.', array( 'status' => 409 ) );
								}
								$projected_snapshot['options']['complete99_last_deployment_id']['option_value'] = $observed_deployment;
								$projected_json = wp_json_encode( $projected_snapshot );
								$projected_fingerprint = false === $projected_json ? '' : hash( 'sha256', $projected_json );
								if ( ! hash_equals( $reviewed_observed_fingerprint, $projected_fingerprint ) ) {
									return new WP_Error( 'c99_orphaned_v2_inverse_projection', 'The interrupted reconciliation is not the reviewed marker-only projection.', array( 'status' => 409 ) );
								}
							}
							$reconciled_snapshot = $capture_database_state_consistent();
							$reconciled_json = is_wp_error( $reconciled_snapshot ) ? false : wp_json_encode( $reconciled_snapshot );
							$reconciled_fingerprint = false === $reconciled_json ? '' : hash( 'sha256', $reconciled_json );
							$reconciled_manifest_record = is_array( $reconciled_snapshot )
								? $database_snapshot_manifest( $reconciled_snapshot )
								: $reconciled_snapshot;
							$reconciled_storage = $verify_transactional_storage();
							$reconciled_manifest_matches = is_array( $reconciled_manifest_record )
								&& $database_snapshot_manifest_valid(
									$reconciled_manifest_record['manifest'] ?? null,
									$reconciled_manifest_record['manifest_sha256'] ?? null
								)
								&& hash_equals( $reviewed_manifest_sha256, (string) ( $reconciled_manifest_record['manifest_sha256'] ?? '' ) );
							if (
								! hash_equals( $reviewed_reconciled_fingerprint, $reconciled_fingerprint )
								|| ! $reconciled_manifest_matches
								|| is_wp_error( $reconciled_storage )
								|| $reconciled_storage !== $database_reconciliation['transactional_storage']
							) {
								if ( ! $marker_corrected ) {
									return new WP_Error( 'c99_orphaned_v2_interrupted_drift', 'The interrupted reconciliation changed after its reviewed attestation.', array( 'status' => 409, 'marker_compensated' => false ) );
								}
								$compensation_basis = is_array( $reconciled_snapshot ) ? $reconciled_snapshot : $capture_database_state_consistent();
								$compensation_marker_row = is_array( $compensation_basis ) ? ( $compensation_basis['options']['complete99_last_deployment_id'] ?? null ) : null;
								if ( ! is_array( $compensation_marker_row ) || $prior_deployment !== (string) ( $compensation_marker_row['option_value'] ?? '' ) ) {
									return new WP_Error( 'c99_orphaned_v2_compensation_basis', 'The corrected marker could not be compensated from an exact database basis.', array( 'status' => 500, 'marker_compensated' => false ) );
								}
								$compensation_basis_manifest = $database_snapshot_manifest( $compensation_basis );
								$compensation_basis_storage = $verify_transactional_storage();
								$compensation_expected = $compensation_basis;
								$compensation_expected['options']['complete99_last_deployment_id']['option_value'] = $observed_deployment;
								$compensation_expected_json = wp_json_encode( $compensation_expected );
								$compensation_expected_fingerprint = false === $compensation_expected_json ? '' : hash( 'sha256', $compensation_expected_json );
								if (
									! is_array( $compensation_basis_manifest )
									|| ! preg_match( '/^[a-f0-9]{64}$/', $compensation_expected_fingerprint )
								) {
									return new WP_Error( 'c99_orphaned_v2_compensation_encode', 'The compensation state could not be encoded safely.', array( 'status' => 500, 'marker_compensated' => false ) );
								}
								$lease = $heartbeat_lock(
									$deployment_id,
									(string) ( $lease['owner_id'] ?? '' ),
									(int) ( $lease['fence'] ?? 0 ),
									'rolling_back'
								);
								if ( is_wp_error( $lease ) ) {
									return $lease;
								}
								$compensated = $marker_cas_transaction( $prior_deployment, $observed_deployment, 'compensation' );
								if ( is_wp_error( $compensated ) ) {
									return $compensated;
								}
								$compensated_snapshot = $capture_database_state_consistent();
								$compensated_json = is_wp_error( $compensated_snapshot ) ? false : wp_json_encode( $compensated_snapshot );
								$compensated_fingerprint = false === $compensated_json ? '' : hash( 'sha256', $compensated_json );
								$compensated_manifest = is_array( $compensated_snapshot ) ? $database_snapshot_manifest( $compensated_snapshot ) : $compensated_snapshot;
								$compensated_storage = $verify_transactional_storage();
								$compensation_verified = hash_equals( $compensation_expected_fingerprint, $compensated_fingerprint )
									&& is_array( $compensated_manifest )
									&& $database_snapshot_manifest_valid(
										$compensated_manifest['manifest'] ?? null,
										$compensated_manifest['manifest_sha256'] ?? null
									)
									&& hash_equals(
										(string) ( $compensation_basis_manifest['manifest_sha256'] ?? '' ),
										(string) ( $compensated_manifest['manifest_sha256'] ?? '' )
									)
									&& ! is_wp_error( $compensation_basis_storage )
									&& ! is_wp_error( $compensated_storage )
									&& $compensation_basis_storage === $compensated_storage;
								if ( ! $compensation_verified ) {
									return new WP_Error( 'c99_orphaned_v2_compensation_readback', 'The marker compensation could not prove preservation of concurrent database drift.', array( 'status' => 500, 'marker_compensated' => true, 'compensation_verified' => false ) );
								}
								return new WP_Error( 'c99_orphaned_v2_database_readback', 'The corrected database changed during reconciliation and its marker was compensated.', array( 'status' => 409, 'marker_compensated' => true, 'compensation_verified' => true ) );
							}
						} else {
							$canonical_snapshot = $current_snapshot;
							$canonical_snapshot['options']['complete99_last_deployment_id']['option_value'] = $prior_deployment;
							$canonical_json = wp_json_encode( $canonical_snapshot );
							$canonical_fingerprint = false === $canonical_json ? '' : hash( 'sha256', $canonical_json );
							if ( ! hash_equals( $baseline_database_fingerprint, $canonical_fingerprint ) ) {
								return new WP_Error( 'c99_orphaned_database_proof', 'The database differs from the reviewed baseline beyond the deployment marker.', array( 'status' => 409 ) );
							}
							if ( $observed_deployment === $current_deployment ) {
								$lease = $heartbeat_lock(
									$deployment_id,
									(string) ( $lease['owner_id'] ?? '' ),
									(int) ( $lease['fence'] ?? 0 ),
									'rolling_back'
								);
								if ( is_wp_error( $lease ) ) {
									return $lease;
								}
								$corrected = $marker_cas_transaction( $observed_deployment, $prior_deployment, 'marker' );
								if ( is_wp_error( $corrected ) ) {
									return $corrected;
								}
								$marker_corrected = true;
								$marker_rows_affected = 1;
								$marker_transition = 'corrected';
							} elseif ( ! hash_equals( $baseline_database_fingerprint, $current_fingerprint ) ) {
								return new WP_Error( 'c99_orphaned_marker_readback', 'The prior deployment marker does not match the reviewed database baseline.', array( 'status' => 409 ) );
							}
							$reconciled_snapshot = $capture_database_state_consistent();
							$reconciled_json = is_wp_error( $reconciled_snapshot ) ? false : wp_json_encode( $reconciled_snapshot );
							$reconciled_fingerprint = false === $reconciled_json ? '' : hash( 'sha256', $reconciled_json );
							if ( ! hash_equals( $baseline_database_fingerprint, $reconciled_fingerprint ) ) {
								if ( $marker_corrected ) {
									$lease = $heartbeat_lock(
										$deployment_id,
										(string) ( $lease['owner_id'] ?? '' ),
										(int) ( $lease['fence'] ?? 0 ),
										'rolling_back'
									);
									if ( is_wp_error( $lease ) ) {
										return $lease;
									}
									$compensated = $marker_cas_transaction( $prior_deployment, $observed_deployment, 'compensation' );
									if ( is_wp_error( $compensated ) ) {
										return $compensated;
									}
								}
								return new WP_Error(
									'c99_orphaned_database_readback',
									'The corrected database did not match the reviewed rollback baseline.',
									array( 'status' => 500, 'marker_compensated' => $marker_corrected )
								);
							}
						}
						$cache_purge = $purge_caches();
						if ( is_wp_error( $cache_purge ) ) {
							return $cache_purge;
						}
						if ( is_link( $state_dir ) ) {
							return new WP_Error( 'c99_orphaned_evidence_symlink', 'The orphaned rollback evidence directory is unsafe.', array( 'status' => 409 ) );
						}
						$evidence_directory_exists = $wp_filesystem->is_dir( $state_dir );
						$evidence_directory_sha256 = '';
						if ( $evidence_directory_exists ) {
							$evidence_directory_sha256 = $directory_sha256( $state_dir );
							if ( is_wp_error( $evidence_directory_sha256 ) ) {
								return $evidence_directory_sha256;
							}
						}
						$receipt = array(
							'schema'                         => 'complete99-orphaned-rollback-receipt/v1',
							'deployment_id'                  => $deployment_id,
							'proof_sha256'                   => $proof_sha256,
							'observed_deployment'            => $observed_deployment,
							'prior_deployment'               => $prior_deployment,
							'prior_version'                  => $prior_version,
							'prior_plugin_sha256'            => $prior_plugin_sha256,
							'baseline_database_fingerprint'  => $baseline_database_fingerprint,
							'prior_robots_sha256'            => $prior_robots_sha256,
							'evidence_directory_exists'      => $evidence_directory_exists,
							'evidence_directory_sha256'      => (string) $evidence_directory_sha256,
						);
						if ( $proof_is_v2 ) {
							$receipt = array(
								'schema'                                   => 'complete99-orphaned-rollback-receipt/v2',
								'mode'                                     => 'preserve-reviewed-drift-marker-only',
								'deployment_id'                            => $deployment_id,
								'proof_sha256'                             => $proof_sha256,
								'failed_artifact_sha256'                   => (string) $failed_proof['artifact_sha256'],
								'failed_candidate_version'                 => (string) $failed_proof['candidate_version'],
								'failed_candidate_plugin_sha256'           => (string) $failed_proof['candidate_plugin_sha256'],
								'failed_candidate_database_fingerprint'    => (string) $failed_proof['candidate_database_fingerprint'],
								'prior_proof_sha256'                       => (string) $database_reconciliation['prior_proof_sha256'],
								'attestation_path'                         => (string) $database_reconciliation['attestation_path'],
								'attestation_sha256'                       => (string) $database_reconciliation['attestation_sha256'],
								'attestation_audit_sha256'                 => (string) $database_reconciliation['attestation_audit_sha256'],
								'attestation_run_id'                       => (int) $database_reconciliation['attestation_run_id'],
								'attestation_source_commit'                => (string) $database_reconciliation['attestation_source_commit'],
								'observed_deployment'                      => $observed_deployment,
								'target_deployment'                        => $prior_deployment,
								'prior_version'                            => $prior_version,
								'prior_database_version'                   => $prior_database_version,
								'prior_active'                             => true,
								'prior_plugin_sha256'                      => $prior_plugin_sha256,
								'prior_robots_exists'                      => true,
								'prior_robots_sha256'                      => $prior_robots_sha256,
								'sync_configured'                          => true,
								'historical_baseline_database_fingerprint'=> $baseline_database_fingerprint,
								'observed_database_fingerprint'            => (string) $database_reconciliation['observed_database_fingerprint'],
								'reconciled_database_fingerprint'          => (string) $database_reconciliation['expected_reconciled_database_fingerprint'],
								'preserved_manifest'                       => $database_reconciliation['preserved_manifest'],
								'preserved_manifest_sha256'                => (string) $database_reconciliation['preserved_manifest_sha256'],
								'transactional_storage'                    => $database_reconciliation['transactional_storage'],
								'evidence_directory_exists'                => $evidence_directory_exists,
								'evidence_directory_sha256'                => (string) $evidence_directory_sha256,
							);
						}
						$receipt_json = wp_json_encode( $receipt );
						$receipt_sha256 = false === $receipt_json ? '' : hash( 'sha256', $receipt_json );
						if ( ! preg_match( '/^[a-f0-9]{64}$/', $receipt_sha256 ) ) {
							return new WP_Error( 'c99_orphaned_receipt_encode', 'The orphaned rollback receipt could not be encoded.', array( 'status' => 500 ) );
						}
						$lease = $heartbeat_lock(
							$deployment_id,
							(string) ( $lease['owner_id'] ?? '' ),
							(int) ( $lease['fence'] ?? 0 ),
							'rolling_back'
						);
						if ( is_wp_error( $lease ) ) {
							return $lease;
						}
						if ( is_link( $receipt_root ) || is_link( $receipt_dir ) || is_link( $receipt_file ) || is_dir( $receipt_file ) ) {
							return new WP_Error( 'c99_orphaned_receipt_path_unsafe', 'The orphaned rollback receipt path is unsafe.', array( 'status' => 409 ) );
						}
						$receipt_root_protection = $protect_recovery_evidence_root( $receipt_root );
						if ( is_wp_error( $receipt_root_protection ) ) {
							return $receipt_root_protection;
						}
						if ( ! $wp_filesystem->is_dir( $receipt_dir ) && ! $wp_filesystem->mkdir( $receipt_dir, FS_CHMOD_DIR ) ) {
							return new WP_Error( 'c99_orphaned_receipt_directory', 'The orphaned rollback evidence directory could not be created.', array( 'status' => 500 ) );
						}
						if ( is_link( $receipt_dir ) || ! $wp_filesystem->is_dir( $receipt_dir ) || is_link( $receipt_file ) || is_dir( $receipt_file ) ) {
							return new WP_Error( 'c99_orphaned_receipt_directory_unsafe', 'The orphaned rollback evidence directory is unsafe.', array( 'status' => 409 ) );
						}
						if ( $wp_filesystem->exists( $receipt_file ) ) {
							$stored_receipt = $wp_filesystem->get_contents( $receipt_file );
							if ( ! is_string( $stored_receipt ) || ! hash_equals( $receipt_sha256, hash( 'sha256', $stored_receipt ) ) ) {
								return new WP_Error( 'c99_orphaned_receipt_conflict', 'The durable orphaned rollback receipt conflicts with the reviewed proof.', array( 'status' => 409 ) );
							}
						} else {
							$lease = $heartbeat_lock(
								$deployment_id,
								(string) ( $lease['owner_id'] ?? '' ),
								(int) ( $lease['fence'] ?? 0 ),
								'rolling_back'
							);
							if ( is_wp_error( $lease ) ) {
								return $lease;
							}
							$written = $write_state_file( $receipt_file, $receipt );
							if ( is_wp_error( $written ) ) {
								return $written;
							}
						}
						$receipt_readback = $wp_filesystem->get_contents( $receipt_file );
						if ( ! is_string( $receipt_readback ) || ! hash_equals( $receipt_sha256, hash( 'sha256', $receipt_readback ) ) ) {
							return new WP_Error( 'c99_orphaned_receipt_readback', 'The durable orphaned rollback receipt failed readback.', array( 'status' => 500 ) );
						}
						$terminal_identity = array(
							'committed_outcome'                         => 'rolled_back',
							'committed_expected_active'                 => true,
							'committed_expected_absent'                 => false,
							'committed_expected_version'                => $prior_version,
							'committed_expected_deployment'             => $prior_deployment,
							'committed_expected_plugin_sha256'          => $prior_plugin_sha256,
							'committed_expected_database_fingerprint'   => $expected_committed_database_fingerprint,
							'committed_expected_robots_exists'          => true,
							'committed_expected_robots_sha256'          => $prior_robots_sha256,
							'committed_expected_sync_configured'        => true,
							'orphaned_recovery_proof_sha256'            => $proof_sha256,
							'orphaned_recovery_receipt_sha256'          => $receipt_sha256,
							'orphaned_recovery_evidence_exists'         => $evidence_directory_exists,
							'orphaned_recovery_evidence_sha256'         => (string) $evidence_directory_sha256,
							'orphaned_reconciled_from'                  => 'rolling_back',
							'orphaned_observed_deployment'              => $observed_deployment,
						);
						if ( $proof_is_v2 ) {
							$terminal_identity = array_merge(
								$terminal_identity,
								array(
									'expected_sha256'                              => (string) $failed_proof['artifact_sha256'],
									'expected_version'                             => (string) $failed_proof['candidate_version'],
									'installed_plugin_sha256'                      => (string) $failed_proof['candidate_plugin_sha256'],
									'post_install_database_fingerprint'             => (string) $failed_proof['candidate_database_fingerprint'],
									'orphaned_reconciliation_mode'                 => 'preserve-reviewed-drift-marker-only',
									'orphaned_prior_proof_sha256'                  => (string) $database_reconciliation['prior_proof_sha256'],
									'orphaned_attestation_run_id'                  => (int) $database_reconciliation['attestation_run_id'],
									'orphaned_attestation_sha256'                  => (string) $database_reconciliation['attestation_sha256'],
									'orphaned_attestation_audit_sha256'            => (string) $database_reconciliation['attestation_audit_sha256'],
									'orphaned_attestation_source_commit'           => (string) $database_reconciliation['attestation_source_commit'],
									'orphaned_recovery_receipt_schema'             => $receipt_schema,
									'orphaned_historical_baseline_database_fingerprint'=> $baseline_database_fingerprint,
									'orphaned_observed_database_fingerprint'       => (string) $database_reconciliation['observed_database_fingerprint'],
									'orphaned_preserved_manifest_sha256'           => (string) $database_reconciliation['preserved_manifest_sha256'],
									'orphaned_marker_rows_affected'                => $marker_rows_affected,
									'orphaned_marker_transition'                   => $marker_transition,
								)
							);
						}
						$terminal = $heartbeat_lock(
							$deployment_id,
							(string) ( $lease['owner_id'] ?? '' ),
							(int) ( $lease['fence'] ?? 0 ),
							'committed',
							$terminal_identity
						);
						if ( is_wp_error( $terminal ) ) {
							return $terminal;
						}
						$response = array(
							'reconciled'                  => true,
							'phase'                       => 'committed',
							'lock_retained'               => true,
							'marker_corrected'            => $marker_corrected,
							'receipt_sha256'              => $receipt_sha256,
							'evidence_directory_exists'   => $evidence_directory_exists,
							'evidence_directory_sha256'   => (string) $evidence_directory_sha256,
							'cache_purge'                 => $cache_purge,
							'site_identity'               => $site_identity,
						);
						if ( $proof_is_v2 ) {
							$response = array_merge(
								$response,
								array(
									'marker_rows_affected'         => $marker_rows_affected,
									'marker_transition'            => $marker_transition,
									'receipt_schema'               => $receipt_schema,
									'historical_baseline_database_fingerprint'=> $baseline_database_fingerprint,
									'observed_database_fingerprint'=> (string) $database_reconciliation['observed_database_fingerprint'],
									'reconciled_database_fingerprint'=> $reconciled_fingerprint,
									'preserved_manifest_sha256'   => (string) $database_reconciliation['preserved_manifest_sha256'],
								)
							);
						}
						return $response;
					} finally {
						$worker_fence_release = is_array( $deployment_worker_fence ) ? $release_worker_fence( $deployment_worker_fence ) : true;
						$release_process_lock( $process_lock );
						if ( is_wp_error( $worker_fence_release ) ) {
							return $worker_fence_release;
						}
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
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $lock_owner, $bootstrap_filesystem, $verify_site_identity, $cleanup_staging, $state_directory, $purge_caches, $read_lock, $claim_lock, $heartbeat_lock, $release_lock, $acquire_process_lock, $release_process_lock, $acquire_worker_fence, $release_worker_fence, $adopt_state_lease, $heartbeat_state, $set_state_phase, $managed_robots_path, $capture_database_state, $capture_database_state_consistent, $database_snapshot_manifest, $database_snapshot_manifest_valid, $verify_transactional_storage, $directory_sha256, $protect_recovery_evidence_root, $ops_quarantine_residue ) {
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
					$deployment_worker_fence = null;
					try {
					$staging_cleaned = $cleanup_staging( $deployment_id );
					if ( is_wp_error( $staging_cleaned ) ) {
						return $staging_cleaned;
					}
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
					$state_backup_intact = false;
					if ( $state_exists ) {
						$state_backup_dir = trailingslashit( $state_dir ) . 'plugin';
						$state_backup_main = trailingslashit( $state_backup_dir ) . basename( $config['plugin_file'] );
						$state_backup_sha256 = ! is_link( $state_backup_dir ) && $wp_filesystem->is_dir( $state_backup_dir )
							? $directory_sha256( $state_backup_dir )
							: new WP_Error( 'c99_finalize_partial_backup' );
						$state_backup_intact = ! is_link( $state_backup_main )
							&& ! is_dir( $state_backup_main )
							&& $wp_filesystem->exists( $state_backup_main )
							&& ! is_wp_error( $state_backup_sha256 )
							&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $lock['prior_plugin_sha256'] ?? '' ) )
							&& hash_equals( (string) $lock['prior_plugin_sha256'], (string) $state_backup_sha256 );
					}
					$adopted_forward_cleanup_residue = $lock_owned
						&& in_array( (string) ( $lock['phase'] ?? '' ), array( 'committed', 'cleanup_failed' ), true )
						&& ! empty( $lock['adopted_forward_no_rollback'] )
						&& ! is_link( $state_dir )
						&& $wp_filesystem->is_dir( $state_dir )
						&& ( ! $state_exists || ! $state_backup_intact );
					if ( $adopted_forward_cleanup_residue ) {
						$phase = (string) $lock['phase'];
					}
					$owner_changed = $lock_owner !== (string) ( $lock['owner_id'] ?? '' );
					$orphaned_marker_present = false;
					foreach (
						array(
							'orphaned_recovery_proof_sha256',
							'orphaned_recovery_receipt_sha256',
							'orphaned_recovery_evidence_exists',
							'orphaned_recovery_evidence_sha256',
							'orphaned_reconciled_from',
							'orphaned_observed_deployment',
							'orphaned_reconciliation_mode',
							'orphaned_prior_proof_sha256',
							'orphaned_attestation_run_id',
							'orphaned_attestation_sha256',
							'orphaned_attestation_audit_sha256',
							'orphaned_attestation_source_commit',
							'orphaned_recovery_receipt_schema',
							'orphaned_historical_baseline_database_fingerprint',
							'orphaned_observed_database_fingerprint',
							'orphaned_preserved_manifest_sha256',
							'orphaned_marker_rows_affected',
							'orphaned_marker_transition',
						) as $orphaned_marker_key
					) {
						$orphaned_marker_present = array_key_exists( $orphaned_marker_key, $lock ) || $orphaned_marker_present;
					}
					if (
						$orphaned_marker_present
						&& ( $state_exists || ! in_array( $phase, array( 'committed', 'cleanup_failed' ), true ) )
					) {
						return new WP_Error( 'c99_finalize_orphaned_marker_state', 'Finalization found orphaned rollback markers outside a valid terminal lock.', array( 'status' => 409 ) );
					}
					$orphaned_recovery = ! $state_exists && $orphaned_marker_present;
					$require_stale = ( 'locked' === $phase )
						|| ( 'reserved' === $phase && $owner_changed )
						|| ( 'committing' === $phase && $owner_changed )
						|| ( $orphaned_recovery && $owner_changed );
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
					$deployment_worker_fence = $acquire_worker_fence();
					if ( is_wp_error( $deployment_worker_fence ) ) {
						return $deployment_worker_fence;
					}
					if ( $state_exists && ! $adopted_forward_cleanup_residue ) {
						$adopted = $adopt_state_lease( $state_dir, $deployment_id, $lease );
						if ( is_wp_error( $adopted ) ) {
							return $adopted;
						}
						$state = $adopted;
					}
					$lock = $lease;
					$lock_owned = true;
					if ( $adopted_forward_cleanup_residue ) {
						$state_exists = false;
						$state = array();
					}
					$adopted_forward_finalize = $state_exists && ! empty( $state['adopted_forward_no_rollback'] );
					$adopted_forward_lock_finalize = ! $state_exists
						&& ! $adopted_forward_cleanup_residue
						&& in_array( (string) ( $lock['phase'] ?? '' ), array( 'committed', 'cleanup_failed' ), true )
						&& ! empty( $lock['adopted_forward_no_rollback'] );
					$validate_adopted_forward_finalize = static function ( $stage, $lock_only = false, $require_lock_identity = false, $cleanup_residue = false ) use ( $config, $deployment_id, $state_dir, $state_file, $read_lock, $directory_sha256, $capture_database_state_consistent, $database_snapshot_manifest, $database_snapshot_manifest_valid, $verify_transactional_storage, $managed_robots_path ) {
						global $wp_filesystem;
						$attestation_error = static function () use ( $stage ) {
							return new WP_Error(
								'c99_finalize_interrupted_forward_attestation',
								'Interrupted-forward finalization could not re-attest the exact adopted release.',
								array( 'status' => 409, 'stage' => sanitize_key( (string) $stage ) )
							);
						};
						$current_lock = $read_lock( true );
						if ( $lock_only ) {
							if ( $cleanup_residue ) {
								if ( is_link( $state_dir ) || ! $wp_filesystem->is_dir( $state_dir ) ) {
									return $attestation_error();
								}
								$residue_root = realpath( $state_dir );
								$residue_safe = is_string( $residue_root ) && '' !== $residue_root;
								if ( $residue_safe ) {
									try {
										$residue_iterator = new RecursiveIteratorIterator(
											new RecursiveDirectoryIterator( $residue_root, FilesystemIterator::SKIP_DOTS ),
											RecursiveIteratorIterator::SELF_FIRST
										);
										foreach ( $residue_iterator as $residue_entry ) {
											$residue_path = $residue_entry->getPathname();
											$residue_relative = str_replace( '\\', '/', substr( $residue_path, strlen( $residue_root ) + 1 ) );
											$residue_parts = explode( '/', $residue_relative );
											$residue_top = (string) ( $residue_parts[0] ?? '' );
											$residue_safe = $residue_safe
												&& ! $residue_entry->isLink()
												&& in_array( $residue_top, array( 'state.json', 'plugin', 'robots.prior-live' ), true )
												&& ( $residue_entry->isDir() || $residue_entry->isFile() )
												&& ( 'plugin' === $residue_top || ( $residue_relative === $residue_top && $residue_entry->isFile() ) );
											if ( ! $residue_safe ) {
												break;
											}
										}
									} catch ( \Throwable $error ) {
										$residue_safe = false;
									}
								}
								if ( ! $residue_safe ) {
									return $attestation_error();
								}
							} elseif ( $wp_filesystem->exists( $state_file ) || $wp_filesystem->exists( $state_dir ) || is_link( $state_dir ) ) {
								return $attestation_error();
							}
							$current_state = $current_lock;
						} else {
							if ( ! $wp_filesystem->exists( $state_file ) || is_link( $state_file ) || is_dir( $state_file ) ) {
								return $attestation_error();
							}
							$state_contents = $wp_filesystem->get_contents( $state_file );
							$current_state = is_string( $state_contents ) ? json_decode( $state_contents, true ) : null;
						}
						$phase = is_array( $current_state ) ? (string) ( $current_state['phase'] ?? '' ) : '';
						$interrupted_config = is_array( $config['interrupted_forward'] ?? null )
							? $config['interrupted_forward']
							: array();
						$pending_repair = 'complete99-interrupted-forward-adoption/v4' === (string) ( $interrupted_config['adoption_schema'] ?? '' );
						$expected_storage = $interrupted_config['reviewed_database_storage'] ?? null;
						$storage_valid = static function ( $storage ) {
							$keys = is_array( $storage ) ? array_keys( $storage ) : array();
							sort( $keys, SORT_STRING );
							return array( 'engine', 'tables' ) === $keys
								&& in_array( (string) ( $storage['engine'] ?? '' ), array( 'INNODB', 'XTRADB', 'INNODB,XTRADB' ), true )
								&& 3 === ( $storage['tables'] ?? null );
						};
						$digest_keys = array(
							'expected_artifact_sha256',
							'expected_plugin_sha256',
							'proof_sha256',
							'reviewed_database_fingerprint',
							'reviewed_database_manifest_sha256',
							'prior_database_fingerprint',
							'prior_plugin_sha256',
							'prior_robots_sha256',
						);
						$config_valid = true;
						foreach ( $digest_keys as $digest_key ) {
							$config_valid = $config_valid
								&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $interrupted_config[ $digest_key ] ?? '' ) );
						}
						$config_valid = $config_valid
							&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', (string) ( $interrupted_config['expected_version'] ?? '' ) )
							&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', (string) ( $interrupted_config['prior_version'] ?? '' ) )
							&& preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', (string) ( $interrupted_config['prior_deployment'] ?? '' ) )
							&& $storage_valid( $expected_storage );
						$cleanup_prior_live = trailingslashit( $state_dir ) . 'robots.prior-live';
						if (
							$cleanup_residue
							&& ( file_exists( $cleanup_prior_live ) || is_link( $cleanup_prior_live ) || is_dir( $cleanup_prior_live ) )
							&& (
								is_link( $cleanup_prior_live )
								|| is_dir( $cleanup_prior_live )
								|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $interrupted_config['prior_robots_sha256'] ?? '' ) )
								|| ! hash_equals( (string) $interrupted_config['prior_robots_sha256'], (string) @hash_file( 'sha256', $cleanup_prior_live ) )
							)
						) {
							return $attestation_error();
						}
						if (
							! $config_valid
							|| ! is_array( $current_state )
							|| ! is_array( $current_lock )
							|| ! in_array( $phase, $lock_only ? array( 'committed', 'cleanup_failed' ) : array( 'installed', 'committing', 'commit_failed', 'committed', 'cleanup_failed' ), true )
							|| $deployment_id !== (string) ( $current_state['deployment_id'] ?? '' )
							|| $deployment_id !== (string) ( $current_lock['deployment_id'] ?? '' )
							|| (string) ( $current_state['owner_id'] ?? '' ) !== (string) ( $current_lock['owner_id'] ?? '' )
							|| 0 >= (int) ( $current_state['fence'] ?? 0 )
							|| (int) ( $current_state['fence'] ?? 0 ) !== (int) ( $current_lock['fence'] ?? 0 )
							|| true !== ( $current_state['adopted_forward_no_rollback'] ?? null )
							|| true !== ( $current_state['stabilized'] ?? null )
							|| ( $pending_repair && 'installed_pending_stabilization' !== (string) ( $current_state['stabilized_from_phase'] ?? '' ) )
							|| true !== ( $current_state['forward_ready'] ?? null )
							|| true !== ( $current_state['temp_removed'] ?? null )
							|| '' !== (string) ( $current_state['temp_path'] ?? '' )
							|| ! hash_equals( (string) $interrupted_config['expected_artifact_sha256'], (string) ( $current_state['expected_sha256'] ?? '' ) )
							|| (string) $interrupted_config['expected_version'] !== (string) ( $current_state['expected_version'] ?? '' )
							|| (string) $interrupted_config['expected_version'] !== (string) ( $current_state['installed_version'] ?? '' )
							|| ! hash_equals( (string) $interrupted_config['expected_plugin_sha256'], (string) ( $current_state['installed_plugin_sha256'] ?? '' ) )
							|| true !== ( $current_state['installed_active'] ?? null )
							|| ! empty( $current_state['sync_configuration_pending'] )
							|| ! hash_equals( (string) $interrupted_config['proof_sha256'], (string) ( $current_state['interrupted_forward_proof_sha256'] ?? '' ) )
							|| ! hash_equals( (string) $interrupted_config['reviewed_database_fingerprint'], (string) ( $current_state['post_install_database_fingerprint'] ?? '' ) )
							|| ! hash_equals( (string) $interrupted_config['reviewed_database_manifest_sha256'], (string) ( $current_state['interrupted_forward_database_manifest_sha256'] ?? '' ) )
							|| ! is_array( $current_state['interrupted_forward_database_manifest'] ?? null )
							|| ! $storage_valid( $current_state['interrupted_forward_database_storage'] ?? null )
							|| (string) ( $current_state['interrupted_forward_database_storage']['engine'] ?? '' ) !== (string) $expected_storage['engine']
							|| ( $current_state['interrupted_forward_database_storage']['tables'] ?? null ) !== $expected_storage['tables']
							|| ! hash_equals( (string) $interrupted_config['prior_database_fingerprint'], (string) ( $current_state['database_fingerprint'] ?? '' ) )
							|| ! hash_equals( (string) $interrupted_config['prior_plugin_sha256'], (string) ( $current_state['prior_plugin_sha256'] ?? '' ) )
							|| (string) $interrupted_config['prior_deployment'] !== (string) ( $current_state['prior_deployment'] ?? '' )
							|| (string) $interrupted_config['prior_version'] !== (string) ( $current_state['prior_version'] ?? '' )
							|| true !== ( $current_state['had_plugin'] ?? null )
							|| true !== ( $current_state['prior_target_dir_exists'] ?? null )
							|| true !== ( $current_state['prior_plugin_main_exists'] ?? null )
							|| true !== ( $current_state['was_active'] ?? null )
							|| true !== ( $current_state['robots_prior_exists'] ?? null )
							|| true !== ( $current_state['robots_applied'] ?? null )
							|| false !== ( $current_state['robots_restored'] ?? null )
							|| ! hash_equals( (string) $interrupted_config['prior_robots_sha256'], (string) ( $current_state['robots_prior_sha256'] ?? '' ) )
							|| ! hash_equals( (string) $interrupted_config['prior_robots_sha256'], (string) ( $current_state['robots_managed_sha256'] ?? '' ) )
							|| ! empty( $current_state['rollback_applied'] )
							|| ! empty( $current_state['database_restored'] )
							|| ! empty( $current_state['rollback_compensated'] )
							|| ! empty( $current_state['rollback_compensation_error'] )
						) {
							return $attestation_error();
						}
						if ( $require_lock_identity ) {
							$lock_identity_keys = array(
								'expected_sha256',
								'expected_version',
								'installed_plugin_sha256',
								'installed_version',
								'installed_active',
								'temp_removed',
								'temp_path',
								'sync_configuration_pending',
								'adopted_forward_no_rollback',
								'stabilized',
								'forward_ready',
								'interrupted_forward_proof_sha256',
								'interrupted_forward_database_manifest',
								'interrupted_forward_database_manifest_sha256',
								'interrupted_forward_database_storage',
								'post_install_database_fingerprint',
								'database_fingerprint',
								'had_plugin',
								'prior_target_dir_exists',
								'prior_plugin_main_exists',
								'prior_plugin_sha256',
								'prior_version',
								'was_active',
								'prior_deployment',
								'robots_applied',
								'robots_restored',
								'robots_prior_exists',
								'robots_prior_sha256',
								'robots_managed_sha256',
								'committed_outcome',
								'committed_expected_active',
								'committed_expected_absent',
								'committed_expected_version',
								'committed_expected_deployment',
								'committed_expected_plugin_sha256',
								'committed_expected_robots_exists',
								'committed_expected_robots_sha256',
							);
							foreach ( $lock_identity_keys as $lock_identity_key ) {
								if (
									! array_key_exists( $lock_identity_key, $current_lock )
									|| ( $current_state[ $lock_identity_key ] ?? null ) !== $current_lock[ $lock_identity_key ]
								) {
									return $attestation_error();
								}
							}
						}

						$target_dir = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
						$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
						$backup_dir = trailingslashit( $state_dir ) . 'plugin';
						$backup_main = trailingslashit( $backup_dir ) . basename( $config['plugin_file'] );
						$swap_suffix = substr( hash( 'sha256', $deployment_id ), 0, 20 );
						$unsafe_paths = array(
							trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix,
							trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix,
							trailingslashit( $state_dir ) . 'robots.forward',
							trailingslashit( $state_dir ) . 'robots.rollback-prior',
						);
						$unsafe_artifact = false;
						foreach ( $unsafe_paths as $unsafe_path ) {
							$unsafe_artifact = $unsafe_artifact
								|| file_exists( $unsafe_path )
								|| is_link( $unsafe_path )
								|| is_dir( $unsafe_path );
						}
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
						$current_plugin_sha256 = ! is_link( $target_dir ) && $wp_filesystem->is_dir( $target_dir )
							? $directory_sha256( $target_dir )
							: new WP_Error( 'c99_finalize_interrupted_forward_plugin_path' );
						$backup_plugin_sha256 = $lock_only
							? (string) $interrupted_config['prior_plugin_sha256']
							: ( ! is_link( $backup_dir ) && $wp_filesystem->is_dir( $backup_dir )
								? $directory_sha256( $backup_dir )
								: new WP_Error( 'c99_finalize_interrupted_forward_backup_path' ) );
						clearstatcache( true, $plugin_path );
						$current_plugin = ! is_link( $plugin_path ) && ! is_dir( $plugin_path ) && $wp_filesystem->exists( $plugin_path )
							? get_plugin_data( $plugin_path, false, false )
							: array();
						$runtime_valid = defined( 'COMPLETE99_PLATFORM_VERSION' )
							&& (string) $interrupted_config['expected_version'] === (string) COMPLETE99_PLATFORM_VERSION
							&& class_exists( 'Complete99_Platform', false )
							&& method_exists( 'Complete99_Platform', 'migration_failed' )
							&& false === (bool) Complete99_Platform::migration_failed()
							&& method_exists( 'Complete99_Platform', 'assert_evaluation_catalog_invariants' )
						&& class_exists( 'Complete99_Ops', false )
						&& method_exists( 'Complete99_Ops', 'assert_invariants' )
						&& class_exists( 'Complete99_Campaigns', false )
						&& method_exists( 'Complete99_Campaigns', 'assert_invariants' )
						&& class_exists( 'Complete99_Culinary_Science', false )
							&& method_exists( 'Complete99_Culinary_Science', 'assert_invariants' );
						$invariants_valid = false;
						if ( $runtime_valid ) {
							try {
								Complete99_Content::assert_migration_invariants();
								Complete99_Settings::assert_defaults();
								Complete99_Platform::assert_evaluation_catalog_invariants();
								Complete99_Ops::assert_invariants();
								if ( ! $pending_repair ) {
									Complete99_Campaigns::assert_invariants();
								}
								Complete99_Culinary_Science::assert_invariants();
								$invariants_valid = true;
							} catch ( \Throwable $error ) {
								$invariants_valid = false;
							}
						}
						if (
							$unsafe_artifact
							|| is_link( $plugin_path )
							|| ( ! $lock_only && ( is_link( $backup_main ) || ! $wp_filesystem->exists( $backup_main ) ) )
							|| is_wp_error( $current_plugin_sha256 )
							|| is_wp_error( $backup_plugin_sha256 )
							|| ! hash_equals( (string) $interrupted_config['expected_plugin_sha256'], (string) $current_plugin_sha256 )
							|| ! hash_equals( (string) $interrupted_config['prior_plugin_sha256'], (string) $backup_plugin_sha256 )
							|| (string) $interrupted_config['expected_version'] !== (string) ( $current_plugin['Version'] ?? '' )
							|| ! is_plugin_active( $config['plugin_file'] )
							|| ! $runtime_valid
							|| ! $invariants_valid
						) {
							return $attestation_error();
						}

						$current_storage = $verify_transactional_storage();
						$current_snapshot = $capture_database_state_consistent();
						$current_database_json = is_wp_error( $current_snapshot ) ? false : wp_json_encode( $current_snapshot );
						$current_database_fingerprint = false === $current_database_json ? '' : hash( 'sha256', $current_database_json );
						$current_manifest_record = is_wp_error( $current_snapshot )
							? $current_snapshot
							: $database_snapshot_manifest( $current_snapshot );
						$current_manifest = is_array( $current_manifest_record )
							? ( $current_manifest_record['manifest'] ?? null )
							: null;
						$current_manifest_sha256 = is_array( $current_manifest_record )
							? (string) ( $current_manifest_record['manifest_sha256'] ?? '' )
							: '';
						$active_plugins_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['active_plugins'] ?? null ) : null;
						$active_plugins = is_array( $active_plugins_row )
							? maybe_unserialize( (string) ( $active_plugins_row['option_value'] ?? '' ) )
							: null;
						$marker_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_last_deployment_id'] ?? null ) : null;
						$version_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_platform_version'] ?? null ) : null;
						if (
							is_wp_error( $current_storage )
							|| ! $storage_valid( $current_storage )
							|| (string) ( $current_storage['engine'] ?? '' ) !== (string) $expected_storage['engine']
							|| ( $current_storage['tables'] ?? null ) !== $expected_storage['tables']
							|| is_wp_error( $current_snapshot )
							|| false === $current_database_json
							|| ! hash_equals( (string) $interrupted_config['reviewed_database_fingerprint'], $current_database_fingerprint )
							|| ! is_array( $current_manifest_record )
							|| ! $database_snapshot_manifest_valid( $current_manifest, $current_manifest_sha256 )
							|| ! hash_equals( (string) $interrupted_config['reviewed_database_manifest_sha256'], $current_manifest_sha256 )
							|| $current_manifest !== $current_state['interrupted_forward_database_manifest']
							|| true !== ( $current_snapshot['sync_secret_existed'] ?? null )
							|| true !== ( $current_snapshot['sync_secret_configured'] ?? null )
							|| ! is_array( $active_plugins )
							|| ! in_array( $config['plugin_file'], $active_plugins, true )
							|| ! is_array( $marker_row )
							|| $deployment_id !== (string) ( $marker_row['option_value'] ?? '' )
							|| ! is_array( $version_row )
							|| (string) $interrupted_config['expected_version'] !== (string) ( $version_row['option_value'] ?? '' )
						) {
							return $attestation_error();
						}

						$robots_path = $managed_robots_path();
						$prior_live_robots = trailingslashit( $state_dir ) . 'robots.prior-live';
						$current_robots_sha256 = ! is_wp_error( $robots_path ) && ! is_link( $robots_path ) && ! is_dir( $robots_path ) && file_exists( $robots_path )
							? (string) @hash_file( 'sha256', $robots_path )
							: '';
						$prior_robots_bytes = $lock_only ? null : base64_decode( (string) ( $current_state['robots_prior_base64'] ?? '' ), true );
						if (
							( ! $lock_only && false === $prior_robots_bytes )
							|| ( ! $lock_only && (
								( file_exists( $prior_live_robots ) || is_link( $prior_live_robots ) || is_dir( $prior_live_robots ) )
								&& (
									is_link( $prior_live_robots )
									|| is_dir( $prior_live_robots )
									|| ! hash_equals( (string) $interrupted_config['prior_robots_sha256'], (string) @hash_file( 'sha256', $prior_live_robots ) )
								)
							) )
							|| ( ! $lock_only && ! hash_equals( (string) $interrupted_config['prior_robots_sha256'], hash( 'sha256', $prior_robots_bytes ) ) )
							|| ! hash_equals( (string) $interrupted_config['prior_robots_sha256'], $current_robots_sha256 )
						) {
							return $attestation_error();
						}

						if ( 'installed' !== $phase ) {
							$commit_identity_valid = 'installed' === (string) ( $current_state['committed_outcome'] ?? '' )
								&& true === ( $current_state['committed_expected_active'] ?? null )
								&& false === ( $current_state['committed_expected_absent'] ?? null )
								&& (string) $interrupted_config['expected_version'] === (string) ( $current_state['committed_expected_version'] ?? '' )
								&& $deployment_id === (string) ( $current_state['committed_expected_deployment'] ?? '' )
								&& hash_equals( (string) $interrupted_config['expected_plugin_sha256'], (string) ( $current_state['committed_expected_plugin_sha256'] ?? '' ) )
								&& true === ( $current_state['committed_expected_robots_exists'] ?? null )
								&& hash_equals( (string) $interrupted_config['prior_robots_sha256'], (string) ( $current_state['committed_expected_robots_sha256'] ?? '' ) );
							if ( ! $commit_identity_valid ) {
								return $attestation_error();
							}
						}
						return $current_state;
					};
					if ( $adopted_forward_finalize ) {
						$attested_state = $validate_adopted_forward_finalize( 'post_lease_adoption' );
						if ( is_wp_error( $attested_state ) ) {
							return $attested_state;
						}
						$state = $attested_state;
						$phase = (string) $state['phase'];
					}
					if ( $adopted_forward_lock_finalize ) {
						$attested_lock = $validate_adopted_forward_finalize( 'post_lock_lease_adoption', true );
						if ( is_wp_error( $attested_lock ) ) {
							return $attested_lock;
						}
						$lock = $attested_lock;
						$lease = $attested_lock;
						$phase = (string) $lock['phase'];
					}
					if ( $adopted_forward_cleanup_residue ) {
						$attested_residue = $validate_adopted_forward_finalize( 'post_cleanup_residue_lease_adoption', true, false, true );
						if ( is_wp_error( $attested_residue ) ) {
							return $attested_residue;
						}
						$lock = $attested_residue;
						$lease = $attested_residue;
						$phase = (string) $lock['phase'];
					}
					$preserve_orphaned_evidence = false;
					$cache_purge = array( 'already_purged' => false );
					if ( $state_exists ) {
						if ( in_array( $phase, array( 'installed', 'rolled_back', 'commit_failed', 'committing' ), true ) ) {
							$robots_path = $managed_robots_path();
							if ( is_wp_error( $robots_path ) ) {
								return $robots_path;
							}
							if ( is_link( $robots_path ) || is_dir( $robots_path ) ) {
								return new WP_Error( 'c99_finalize_robots_unsafe', 'Finalization found an unsafe robots.txt target.', array( 'status' => 409 ) );
							}
							$current_robots_exists = file_exists( $robots_path );
							$current_robots_sha256 = $current_robots_exists ? (string) @hash_file( 'sha256', $robots_path ) : '';
							if ( 'installed' === $phase ) {
								if ( empty( $state['stabilized'] ) ) {
									return new WP_Error(
										'c99_finalize_unstabilized',
										'Forward deployment finalization requires a durable post-migration checkpoint.',
										array( 'status' => 409 )
									);
								}
								if ( ! empty( $state['sync_configuration_pending'] ) ) {
									return new WP_Error(
										'c99_finalize_sync_pending',
										'Forward finalization is refused while sync configuration is pending.',
										array( 'status' => 409 )
									);
								}
								$current_database_snapshot = $capture_database_state();
								$current_database_json = is_wp_error( $current_database_snapshot )
									? false
									: wp_json_encode( $current_database_snapshot );
								$current_database_fingerprint = false === $current_database_json
									? ''
									: hash( 'sha256', $current_database_json );
								$recorded_database_fingerprint = (string) ( $state['post_install_database_fingerprint'] ?? '' );
								if (
									is_wp_error( $current_database_snapshot )
									|| ! preg_match( '/^[a-f0-9]{64}$/', $recorded_database_fingerprint )
									|| ! hash_equals( $recorded_database_fingerprint, $current_database_fingerprint )
									|| (
										! empty( $state['sync_configuration_checkpointed'] )
										&& empty( $current_database_snapshot['sync_secret_configured'] )
									)
								) {
									return new WP_Error(
										'c99_finalize_database_checkpoint',
										'Forward finalization requires the exact post-configuration database checkpoint.',
										array( 'status' => 409 )
									);
								}
								if (
									empty( $state['robots_applied'] )
									|| ! empty( $state['robots_restored'] )
									|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $state['robots_managed_sha256'] ?? '' ) )
								) {
									return new WP_Error(
										'c99_finalize_robots_forward',
										'Forward finalization requires the exact managed robots.txt checkpoint.',
										array( 'status' => 409 )
									);
								}
								$commit_identity = array(
									'committed_outcome'                 => 'installed',
									'committed_expected_active'         => ! empty( $state['installed_active'] ),
									'committed_expected_absent'         => false,
									'committed_expected_version'        => (string) ( $state['expected_version'] ?? '' ),
									'committed_expected_deployment'     => $deployment_id,
									'committed_expected_plugin_sha256'  => (string) ( $state['installed_plugin_sha256'] ?? '' ),
									'committed_expected_robots_exists'  => true,
									'committed_expected_robots_sha256'  => (string) ( $state['robots_managed_sha256'] ?? '' ),
								);
							} elseif ( 'rolled_back' === $phase ) {
								$rollback_had_plugin = ! empty( $state['had_plugin'] );
								if ( ! empty( $state['robots_applied'] ) && empty( $state['robots_restored'] ) ) {
									return new WP_Error(
										'c99_finalize_robots_rollback',
										'Rollback finalization requires the exact prior robots.txt checkpoint.',
										array( 'status' => 409 )
									);
								}
								$commit_identity = array(
									'committed_outcome'                 => 'rolled_back',
									'committed_expected_active'         => $rollback_had_plugin && ! empty( $state['was_active'] ),
									'committed_expected_absent'         => ! $rollback_had_plugin,
									'committed_expected_version'        => $rollback_had_plugin ? (string) ( $state['prior_version'] ?? '' ) : '',
									'committed_expected_deployment'     => (string) ( $state['prior_deployment'] ?? '' ),
									'committed_expected_plugin_sha256'  => $rollback_had_plugin ? (string) ( $state['prior_plugin_sha256'] ?? '' ) : '',
									'committed_expected_robots_exists'  => ! empty( $state['robots_prior_exists'] ),
									'committed_expected_robots_sha256'  => (string) ( $state['robots_prior_sha256'] ?? '' ),
								);
							} else {
								$commit_identity = array(
									'committed_outcome'                 => (string) ( $state['committed_outcome'] ?? '' ),
									'committed_expected_active'         => ! empty( $state['committed_expected_active'] ),
									'committed_expected_absent'         => ! empty( $state['committed_expected_absent'] ),
									'committed_expected_version'        => (string) ( $state['committed_expected_version'] ?? '' ),
									'committed_expected_deployment'     => (string) ( $state['committed_expected_deployment'] ?? '' ),
									'committed_expected_plugin_sha256'  => (string) ( $state['committed_expected_plugin_sha256'] ?? '' ),
									'committed_expected_robots_exists'  => ! empty( $state['committed_expected_robots_exists'] ),
									'committed_expected_robots_sha256'  => (string) ( $state['committed_expected_robots_sha256'] ?? '' ),
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
							if ( $commit_identity['committed_expected_robots_exists'] ) {
								$identity_valid = $identity_valid
									&& preg_match( '/^[a-f0-9]{64}$/', $commit_identity['committed_expected_robots_sha256'] )
									&& $current_robots_exists
									&& hash_equals( $commit_identity['committed_expected_robots_sha256'], $current_robots_sha256 );
							} else {
								$identity_valid = $identity_valid
									&& '' === $commit_identity['committed_expected_robots_sha256']
									&& ! $current_robots_exists;
							}
							if ( 'installed' === $commit_identity['committed_outcome'] && ! $commit_identity['committed_expected_robots_exists'] ) {
								$identity_valid = false;
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
						if ( $orphaned_recovery ) {
							require_once ABSPATH . 'wp-admin/includes/plugin.php';
							$target_dir = trailingslashit( WP_PLUGIN_DIR ) . $config['slug'];
							$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $config['plugin_file'];
							$swap_suffix = substr( hash( 'sha256', $deployment_id ), 0, 20 );
							$restore_stage = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-restore-' . $swap_suffix;
							$displaced_dir = trailingslashit( WP_PLUGIN_DIR ) . '.complete99-displaced-' . $swap_suffix;
							$expected_version = (string) ( $lock['committed_expected_version'] ?? '' );
							$expected_deployment = (string) ( $lock['committed_expected_deployment'] ?? '' );
							$expected_plugin_sha256 = (string) ( $lock['committed_expected_plugin_sha256'] ?? '' );
							$expected_database_fingerprint = (string) ( $lock['committed_expected_database_fingerprint'] ?? '' );
							$expected_robots_sha256 = (string) ( $lock['committed_expected_robots_sha256'] ?? '' );
							$expected_receipt_sha256 = (string) ( $lock['orphaned_recovery_receipt_sha256'] ?? '' );
							$expected_evidence_exists = true === ( $lock['orphaned_recovery_evidence_exists'] ?? null );
							$expected_evidence_sha256 = (string) ( $lock['orphaned_recovery_evidence_sha256'] ?? '' );
							$expected_receipt_schema = (string) ( $lock['orphaned_recovery_receipt_schema'] ?? '' );
							$orphaned_receipt_is_v2 = 'complete99-orphaned-rollback-receipt/v2' === $expected_receipt_schema;
							$plugin_paths_safe = ! is_link( $target_dir ) && ! is_link( $plugin_path );
							$current = $plugin_paths_safe && $wp_filesystem->exists( $plugin_path ) ? get_plugin_data( $plugin_path, false, false ) : array();
							$current_plugin_sha256 = $wp_filesystem->is_dir( $target_dir ) ? $directory_sha256( $target_dir ) : '';
							$current_snapshot = $orphaned_receipt_is_v2
								? $capture_database_state_consistent()
								: $capture_database_state();
							$current_json = is_wp_error( $current_snapshot ) ? false : wp_json_encode( $current_snapshot );
							$current_database_fingerprint = false === $current_json ? '' : hash( 'sha256', $current_json );
							$active_plugins_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['active_plugins'] ?? null ) : null;
							$database_version_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_platform_version'] ?? null ) : null;
							$deployment_row = is_array( $current_snapshot ) ? ( $current_snapshot['options']['complete99_last_deployment_id'] ?? null ) : null;
							$active_plugins = is_array( $active_plugins_row )
								? maybe_unserialize( (string) ( $active_plugins_row['option_value'] ?? '' ) )
								: array();
							$current_active = is_array( $active_plugins ) && in_array( $config['plugin_file'], $active_plugins, true );
							$current_database_version = is_array( $database_version_row ) ? (string) ( $database_version_row['option_value'] ?? '' ) : '';
							$current_deployment = is_array( $deployment_row ) ? (string) ( $deployment_row['option_value'] ?? '' ) : '';
							$robots_path = $managed_robots_path();
							if ( is_wp_error( $robots_path ) ) {
								return $robots_path;
							}
							if ( is_link( $robots_path ) || is_dir( $robots_path ) || ! file_exists( $robots_path ) ) {
								return new WP_Error( 'c99_finalize_orphaned_robots_unsafe', 'Finalization found an unsafe orphaned rollback robots.txt target.', array( 'status' => 409 ) );
							}
							$current_robots_sha256 = (string) @hash_file( 'sha256', $robots_path );
							if ( is_link( $state_dir ) ) {
								return new WP_Error( 'c99_finalize_orphaned_evidence_unsafe', 'Finalization found an unsafe orphaned rollback evidence directory.', array( 'status' => 409 ) );
							}
							$current_evidence_exists = $wp_filesystem->is_dir( $state_dir );
							$current_evidence_sha256 = '';
							if ( $current_evidence_exists ) {
								$current_evidence_sha256 = $directory_sha256( $state_dir );
								if ( is_wp_error( $current_evidence_sha256 ) ) {
									return $current_evidence_sha256;
								}
							}
							$receipt_root = trailingslashit( WP_CONTENT_DIR ) . '.complete99-deploy-recovery-evidence';
							$receipt_dir = trailingslashit( $receipt_root ) . substr( hash( 'sha256', $deployment_id ), 0, 32 );
							$receipt_file = trailingslashit( $receipt_dir ) . 'orphan-recovery-receipt.json';
							$receipt_root_protection = $protect_recovery_evidence_root( $receipt_root );
							if ( is_wp_error( $receipt_root_protection ) ) {
								return $receipt_root_protection;
							}
							if (
								is_link( $receipt_root )
								|| is_link( $receipt_dir )
								|| is_link( $receipt_file )
								|| ! $wp_filesystem->is_dir( $receipt_root )
								|| ! $wp_filesystem->is_dir( $receipt_dir )
								|| is_dir( $receipt_file )
							) {
								return new WP_Error( 'c99_finalize_orphaned_receipt_path', 'Finalization found an unsafe orphaned rollback receipt path.', array( 'status' => 409 ) );
							}
							$receipt_contents = $wp_filesystem->exists( $receipt_file ) ? $wp_filesystem->get_contents( $receipt_file ) : false;
							$receipt_record = is_string( $receipt_contents ) ? json_decode( $receipt_contents, true ) : null;
							$receipt_keys = is_array( $receipt_record ) ? array_keys( $receipt_record ) : array();
							$expected_receipt_keys = array( 'baseline_database_fingerprint', 'deployment_id', 'evidence_directory_exists', 'evidence_directory_sha256', 'observed_deployment', 'prior_deployment', 'prior_plugin_sha256', 'prior_robots_sha256', 'prior_version', 'proof_sha256', 'schema' );
							$receipt_identity_valid = false;
							if ( $orphaned_receipt_is_v2 ) {
								$expected_receipt_keys = array(
									'attestation_audit_sha256',
									'attestation_path',
									'attestation_run_id',
									'attestation_sha256',
									'attestation_source_commit',
									'deployment_id',
									'evidence_directory_exists',
									'evidence_directory_sha256',
									'failed_artifact_sha256',
									'failed_candidate_database_fingerprint',
									'failed_candidate_plugin_sha256',
									'failed_candidate_version',
									'historical_baseline_database_fingerprint',
									'mode',
									'observed_database_fingerprint',
									'observed_deployment',
									'preserved_manifest',
									'preserved_manifest_sha256',
									'prior_active',
									'prior_database_version',
									'prior_plugin_sha256',
									'prior_proof_sha256',
									'prior_robots_exists',
									'prior_robots_sha256',
									'prior_version',
									'proof_sha256',
									'reconciled_database_fingerprint',
									'schema',
									'sync_configured',
									'target_deployment',
									'transactional_storage',
								);
							}
							sort( $receipt_keys, SORT_STRING );
							sort( $expected_receipt_keys, SORT_STRING );
							if ( $orphaned_receipt_is_v2 ) {
								$current_manifest_record = is_array( $current_snapshot ) ? $database_snapshot_manifest( $current_snapshot ) : $current_snapshot;
								$current_storage = $verify_transactional_storage();
								$marker_rows_affected = $lock['orphaned_marker_rows_affected'] ?? null;
								$marker_transition = (string) ( $lock['orphaned_marker_transition'] ?? '' );
								$receipt_identity_valid = $receipt_keys === $expected_receipt_keys
									&& 'complete99-orphaned-rollback-receipt/v2' === (string) ( $receipt_record['schema'] ?? '' )
									&& 'preserve-reviewed-drift-marker-only' === (string) ( $receipt_record['mode'] ?? '' )
									&& 'preserve-reviewed-drift-marker-only' === (string) ( $lock['orphaned_reconciliation_mode'] ?? '' )
									&& $deployment_id === (string) ( $receipt_record['deployment_id'] ?? '' )
									&& $deployment_id === (string) ( $receipt_record['observed_deployment'] ?? '' )
									&& (string) ( $lock['expected_sha256'] ?? '' ) === (string) ( $receipt_record['failed_artifact_sha256'] ?? '' )
									&& (string) ( $lock['expected_version'] ?? '' ) === (string) ( $receipt_record['failed_candidate_version'] ?? '' )
									&& (string) ( $lock['installed_plugin_sha256'] ?? '' ) === (string) ( $receipt_record['failed_candidate_plugin_sha256'] ?? '' )
									&& (string) ( $lock['post_install_database_fingerprint'] ?? '' ) === (string) ( $receipt_record['failed_candidate_database_fingerprint'] ?? '' )
									&& $expected_deployment === (string) ( $receipt_record['target_deployment'] ?? '' )
									&& $expected_version === (string) ( $receipt_record['prior_version'] ?? '' )
									&& $expected_version === (string) ( $receipt_record['prior_database_version'] ?? '' )
									&& true === ( $receipt_record['prior_active'] ?? null )
									&& $expected_plugin_sha256 === (string) ( $receipt_record['prior_plugin_sha256'] ?? '' )
									&& true === ( $receipt_record['prior_robots_exists'] ?? null )
									&& $expected_robots_sha256 === (string) ( $receipt_record['prior_robots_sha256'] ?? '' )
									&& true === ( $receipt_record['sync_configured'] ?? null )
									&& (string) ( $lock['orphaned_recovery_proof_sha256'] ?? '' ) === (string) ( $receipt_record['proof_sha256'] ?? '' )
									&& (string) ( $lock['orphaned_prior_proof_sha256'] ?? '' ) === (string) ( $receipt_record['prior_proof_sha256'] ?? '' )
									&& (int) ( $lock['orphaned_attestation_run_id'] ?? 0 ) === ( $receipt_record['attestation_run_id'] ?? null )
									&& (string) ( $lock['orphaned_attestation_sha256'] ?? '' ) === (string) ( $receipt_record['attestation_sha256'] ?? '' )
									&& (string) ( $lock['orphaned_attestation_audit_sha256'] ?? '' ) === (string) ( $receipt_record['attestation_audit_sha256'] ?? '' )
									&& (string) ( $lock['orphaned_attestation_source_commit'] ?? '' ) === (string) ( $receipt_record['attestation_source_commit'] ?? '' )
									&& (string) ( $lock['orphaned_historical_baseline_database_fingerprint'] ?? '' ) === (string) ( $receipt_record['historical_baseline_database_fingerprint'] ?? '' )
									&& (string) ( $lock['orphaned_observed_database_fingerprint'] ?? '' ) === (string) ( $receipt_record['observed_database_fingerprint'] ?? '' )
									&& $expected_database_fingerprint === (string) ( $receipt_record['reconciled_database_fingerprint'] ?? '' )
									&& (string) ( $lock['orphaned_preserved_manifest_sha256'] ?? '' ) === (string) ( $receipt_record['preserved_manifest_sha256'] ?? '' )
									&& $database_snapshot_manifest_valid( $receipt_record['preserved_manifest'] ?? null, $receipt_record['preserved_manifest_sha256'] ?? null )
									&& is_array( $current_manifest_record )
									&& $database_snapshot_manifest_valid(
										$current_manifest_record['manifest'] ?? null,
										$current_manifest_record['manifest_sha256'] ?? null
									)
									&& hash_equals( (string) $receipt_record['preserved_manifest_sha256'], (string) ( $current_manifest_record['manifest_sha256'] ?? '' ) )
									&& ! is_wp_error( $current_storage )
									&& $current_storage === ( $receipt_record['transactional_storage'] ?? null )
									&& is_int( $marker_rows_affected )
									&& in_array( $marker_rows_affected, array( 0, 1 ), true )
									&& in_array( $marker_transition, array( 'corrected', 'already-correct' ), true )
									&& ( 1 === $marker_rows_affected ) === ( 'corrected' === $marker_transition )
									&& $expected_evidence_exists === ( $receipt_record['evidence_directory_exists'] ?? null )
									&& $expected_evidence_sha256 === (string) ( $receipt_record['evidence_directory_sha256'] ?? '' );
							} else {
								$receipt_identity_valid = $receipt_keys === $expected_receipt_keys
									&& 'complete99-orphaned-rollback-receipt/v1' === (string) ( $receipt_record['schema'] ?? '' )
									&& '' === $expected_receipt_schema
									&& $deployment_id === (string) ( $receipt_record['deployment_id'] ?? '' )
									&& (string) ( $lock['orphaned_recovery_proof_sha256'] ?? '' ) === (string) ( $receipt_record['proof_sha256'] ?? '' )
									&& $deployment_id === (string) ( $receipt_record['observed_deployment'] ?? '' )
									&& $expected_deployment === (string) ( $receipt_record['prior_deployment'] ?? '' )
									&& $expected_version === (string) ( $receipt_record['prior_version'] ?? '' )
									&& $expected_plugin_sha256 === (string) ( $receipt_record['prior_plugin_sha256'] ?? '' )
									&& $expected_database_fingerprint === (string) ( $receipt_record['baseline_database_fingerprint'] ?? '' )
									&& $expected_robots_sha256 === (string) ( $receipt_record['prior_robots_sha256'] ?? '' )
									&& $expected_evidence_exists === ( $receipt_record['evidence_directory_exists'] ?? null )
									&& $expected_evidence_sha256 === (string) ( $receipt_record['evidence_directory_sha256'] ?? '' );
							}
							$identity_valid = 'rolled_back' === (string) ( $lock['committed_outcome'] ?? '' )
								&& true === ( $lock['committed_expected_active'] ?? null )
								&& false === ( $lock['committed_expected_absent'] ?? null )
								&& true === ( $lock['committed_expected_robots_exists'] ?? null )
								&& true === ( $lock['committed_expected_sync_configured'] ?? null )
								&& is_bool( $lock['orphaned_recovery_evidence_exists'] ?? null )
								&& 'rolling_back' === (string) ( $lock['orphaned_reconciled_from'] ?? '' )
								&& $deployment_id === (string) ( $lock['orphaned_observed_deployment'] ?? '' )
								&& preg_match( '/^[a-f0-9]{64}$/', (string) ( $lock['orphaned_recovery_proof_sha256'] ?? '' ) )
								&& preg_match( '/^[a-f0-9]{64}$/', $expected_receipt_sha256 )
								&& preg_match( '/^[a-f0-9]{64}$/', $expected_plugin_sha256 )
								&& preg_match( '/^[a-f0-9]{64}$/', $expected_database_fingerprint )
								&& preg_match( '/^[a-f0-9]{64}$/', $expected_robots_sha256 )
								&& preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$/', $expected_version )
								&& preg_match( '/\A[A-Za-z0-9._-]{8,96}\z/', $expected_deployment )
								&& $plugin_paths_safe
								&& $wp_filesystem->is_dir( $target_dir )
								&& $wp_filesystem->exists( $plugin_path )
								&& ! is_wp_error( $current_plugin_sha256 )
								&& $current_active
								&& $expected_version === (string) ( $current['Version'] ?? '' )
								&& $expected_version === $current_database_version
								&& $expected_deployment === $current_deployment
								&& hash_equals( $expected_plugin_sha256, (string) $current_plugin_sha256 )
								&& ! is_wp_error( $current_snapshot )
								&& is_array( $current_snapshot )
								&& is_array( $active_plugins_row )
								&& is_array( $active_plugins )
								&& is_array( $database_version_row )
								&& is_array( $deployment_row )
								&& ! empty( $current_snapshot['sync_secret_configured'] )
								&& hash_equals( $expected_database_fingerprint, $current_database_fingerprint )
								&& hash_equals( $expected_robots_sha256, $current_robots_sha256 )
								&& $expected_evidence_exists === $current_evidence_exists
								&& ( $expected_evidence_exists ? hash_equals( $expected_evidence_sha256, (string) $current_evidence_sha256 ) : '' === $expected_evidence_sha256 )
								&& is_string( $receipt_contents )
								&& hash_equals( $expected_receipt_sha256, hash( 'sha256', $receipt_contents ) )
								&& $receipt_identity_valid
								&& ! $wp_filesystem->exists( $restore_stage )
								&& ! $wp_filesystem->exists( $displaced_dir );
							if ( ! $identity_valid ) {
								return new WP_Error( 'c99_finalize_orphaned_receipt_identity', 'Finalization could not revalidate the durable orphaned rollback receipt.', array( 'status' => 409 ) );
							}
							$preserve_orphaned_evidence = true;
						}
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
							'installed_version'                 => (string) ( $state['installed_version'] ?? '' ),
							'installed_active'                  => ! empty( $state['installed_active'] ),
							'temp_removed'                      => ! empty( $state['temp_removed'] ),
							'temp_path'                         => (string) ( $state['temp_path'] ?? '' ),
							'sync_configuration_pending'        => ! empty( $state['sync_configuration_pending'] ),
							'adopted_forward_no_rollback'       => ! empty( $state['adopted_forward_no_rollback'] ),
							'stabilized'                        => ! empty( $state['stabilized'] ),
							'forward_ready'                     => ! empty( $state['forward_ready'] ),
							'interrupted_forward_proof_sha256'  => (string) ( $state['interrupted_forward_proof_sha256'] ?? '' ),
							'interrupted_forward_database_manifest'=> $state['interrupted_forward_database_manifest'] ?? array(),
							'interrupted_forward_database_manifest_sha256'=> (string) ( $state['interrupted_forward_database_manifest_sha256'] ?? '' ),
							'interrupted_forward_database_storage'=> $state['interrupted_forward_database_storage'] ?? array(),
							'post_install_database_fingerprint'=> (string) ( $state['post_install_database_fingerprint'] ?? '' ),
							'database_fingerprint'              => (string) ( $state['database_fingerprint'] ?? '' ),
							'had_plugin'                        => ! empty( $state['had_plugin'] ),
							'prior_target_dir_exists'           => ! empty( $state['prior_target_dir_exists'] ),
							'prior_plugin_main_exists'          => ! empty( $state['prior_plugin_main_exists'] ),
							'prior_plugin_sha256'               => (string) ( $state['prior_plugin_sha256'] ?? '' ),
							'prior_version'                     => (string) ( $state['prior_version'] ?? '' ),
							'was_active'                        => ! empty( $state['was_active'] ),
							'prior_deployment'                  => (string) ( $state['prior_deployment'] ?? '' ),
							'robots_applied'                    => ! empty( $state['robots_applied'] ),
							'robots_restored'                   => ! empty( $state['robots_restored'] ),
							'robots_prior_exists'               => ! empty( $state['robots_prior_exists'] ),
							'robots_prior_sha256'               => (string) ( $state['robots_prior_sha256'] ?? '' ),
							'robots_managed_sha256'             => (string) ( $state['robots_managed_sha256'] ?? '' ),
							'committed_outcome'                 => (string) ( $state['committed_outcome'] ?? '' ),
							'committed_expected_active'         => ! empty( $state['committed_expected_active'] ),
							'committed_expected_absent'         => ! empty( $state['committed_expected_absent'] ),
							'committed_expected_version'        => (string) ( $state['committed_expected_version'] ?? '' ),
							'committed_expected_deployment'     => (string) ( $state['committed_expected_deployment'] ?? '' ),
							'committed_expected_plugin_sha256'  => (string) ( $state['committed_expected_plugin_sha256'] ?? '' ),
							'committed_expected_database_fingerprint'=> (string) ( $state['post_install_database_fingerprint'] ?? '' ),
							'committed_expected_robots_exists'  => ! empty( $state['committed_expected_robots_exists'] ),
							'committed_expected_robots_sha256'  => (string) ( $state['committed_expected_robots_sha256'] ?? '' ),
							'committed_expected_sync_configured'=> true,
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
					if ( $adopted_forward_finalize ) {
						$attested_state = $validate_adopted_forward_finalize( 'pre_cleanup', false, true );
						if ( is_wp_error( $attested_state ) ) {
							return $attested_state;
						}
						$state = $attested_state;
						$phase = (string) $state['phase'];
					}
					if ( $adopted_forward_lock_finalize ) {
						$attested_lock = $validate_adopted_forward_finalize( 'pre_lock_release', true );
						if ( is_wp_error( $attested_lock ) ) {
							return $attested_lock;
						}
						$lock = $attested_lock;
						$lease = $attested_lock;
						$phase = (string) $lock['phase'];
					}
					if ( $adopted_forward_cleanup_residue ) {
						$attested_residue = $validate_adopted_forward_finalize( 'pre_cleanup_residue_removal', true, false, true );
						if ( is_wp_error( $attested_residue ) ) {
							return $attested_residue;
						}
						$lock = $attested_residue;
						$lease = $attested_residue;
						$phase = (string) $lock['phase'];
					}
					$ops_residue = $ops_quarantine_residue();
					if ( is_wp_error( $ops_residue ) || ! empty( $ops_residue ) ) {
						return is_wp_error( $ops_residue )
							? $ops_residue
							: new WP_Error( 'c99_finalize_ops_rollback_residue', 'Finalization is refused while operations rollback quarantine tables remain.', array( 'status' => 409, 'table_count' => count( $ops_residue ) ) );
					}
					$removed = $preserve_orphaned_evidence
						? ! $wp_filesystem->exists( $state_file )
						: ( ! $wp_filesystem->exists( $state_dir ) || $wp_filesystem->delete( $state_dir, true ) );
					if ( ! $removed ) {
						if ( $adopted_forward_cleanup_residue ) {
							$heartbeat_lock(
								$deployment_id,
								(string) ( $lease['owner_id'] ?? '' ),
								(int) ( $lease['fence'] ?? 0 ),
								'cleanup_failed'
							);
						} elseif ( $wp_filesystem->exists( $state_file ) ) {
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
						'state_removed'=> ! $wp_filesystem->exists( $state_file ) && ( $preserve_orphaned_evidence || ! $wp_filesystem->exists( $state_dir ) ),
						'evidence_preserved'=> $preserve_orphaned_evidence,
						'cache_purge' => $cache_purge,
					);
					} finally {
						$worker_fence_release = is_array( $deployment_worker_fence ) ? $release_worker_fence( $deployment_worker_fence ) : true;
						$release_process_lock( $process_lock );
						if ( is_wp_error( $worker_fence_release ) ) {
							return $worker_fence_release;
						}
					}
				},
			)
		);
	}
);
