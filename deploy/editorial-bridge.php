<?php
/* Temporary, reviewed, admin-only presentation release. No core recovery mutation. */
add_action( 'rest_api_init', static function () {
	$config = json_decode( '__EDITORIAL_CONFIG_JSON__', true );
	$prefix = '/release';
	$permission = static function ( $request ) use ( $config ) {
		return current_user_can( 'update_plugins' ) && current_user_can( 'activate_plugins' )
			&& hash_equals( $config['token'], (string) $request->get_param( 'token' ) );
	};
	$files_digest = static function ( $directory ) {
		if ( ! is_dir( $directory ) || is_link( $directory ) ) { return ''; }
		$records = array();
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
			if ( $file->isLink() || ! $file->isFile() ) { return ''; }
			$name = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $directory ) + 1 ) );
			$records[ $name ] = hash_file( 'sha256', $file->getPathname() );
		}
		ksort( $records );
		return $records;
	};
	$snapshot = static function () use ( $files_digest ) {
		global $wpdb;
		$option_rows = $wpdb->get_results( "SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name LIKE 'complete99_%' ORDER BY option_name", ARRAY_A );
		if ( '' !== $wpdb->last_error ) { return new WP_Error( 'c99_editorial_snapshot', 'Cannot verify core options.' ); }
		$state = WP_CONTENT_DIR . '/.complete99-deploy-backups/c99-prod-31620203121-1/state.json';
		if ( ! is_file( $state ) || is_link( $state ) ) { return new WP_Error( 'c99_editorial_state', 'Expected recovery journal is unavailable.' ); }
		$state_json = json_decode( file_get_contents( $state ), true );
		if ( 'candidate_activation_pending' !== ( $state_json['phase'] ?? '' ) ) { return new WP_Error( 'c99_editorial_phase', 'Core recovery phase changed.' ); }
		$core = $files_digest( WP_PLUGIN_DIR . '/complete99-platform' );
		if ( ! is_array( $core ) || empty( $core ) ) { return new WP_Error( 'c99_editorial_core', 'Core files unavailable.' ); }
		return array( 'core_files' => hash( 'sha256', wp_json_encode( $core ) ), 'core_options' => hash( 'sha256', wp_json_encode( $option_rows ) ), 'journal' => hash_file( 'sha256', $state ) );
	};
	$purge = static function () {
		do_action( 'litespeed_purge_all' );
		wp_cache_flush();
	};
	register_rest_route( 'c99-editorial-deploy/v1', $prefix, array(
		'methods' => 'POST', 'permission_callback' => $permission,
		'callback' => static function ( $request ) use ( $config, $files_digest, $snapshot, $purge ) {
			if ( 'complete99.co.il' !== strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ) { return new WP_Error( 'c99_editorial_host', 'Unexpected website.' ); }
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugin = 'complete99-editorial-home/complete99-editorial-home.php';
			$directory = WP_PLUGIN_DIR . '/complete99-editorial-home';
			$backup_key = 'c99_editorial_release_' . $config['commit'];
			$action = (string) $request->get_param( 'action' );
			$process = fopen( WP_CONTENT_DIR . '/.complete99-deploy-process.lock', 'c+' );
			if ( ! $process || ! flock( $process, LOCK_EX | LOCK_NB ) ) { if ( $process ) { fclose( $process ); } return new WP_Error( 'c99_editorial_busy', 'Another core deployment operation is running.' ); }
			try {
				$before = $snapshot();
				if ( is_wp_error( $before ) ) { return $before; }
				$backup = get_option( $backup_key, null );
				if ( 'status' === $action ) {
					return array( 'active' => is_plugin_active( $plugin ), 'files' => $files_digest( $directory ), 'snapshot' => $before, 'backup' => is_array( $backup ), 'core_unchanged' => is_array( $backup ) && $before === $backup['snapshot'], 'version' => defined( 'C99_EDITORIAL_VERSION' ) ? C99_EDITORIAL_VERSION : '' );
				}
				if ( 'rollback' === $action ) {
					if ( ! is_array( $backup ) || $backup['snapshot'] !== $before ) { return new WP_Error( 'c99_editorial_rollback_guard', 'Core state changed; preserve evidence.' ); }
					deactivate_plugins( $plugin, true );
					$purge();
					return array( 'active' => is_plugin_active( $plugin ), 'snapshot' => $snapshot() );
				}
				if ( 'install' !== $action || ! defined( 'COMPLETE99_PLATFORM_VERSION' ) || '1.22.1' !== COMPLETE99_PLATFORM_VERSION ) { return new WP_Error( 'c99_editorial_request', 'Unsupported release request.' ); }
				if ( is_array( $backup ) ) {
					if ( $backup['snapshot'] !== $before || $files_digest( $directory ) !== $config['files'] ) { return new WP_Error( 'c99_editorial_retry', 'Existing attempt requires inspection, not overwrite.' ); }
				} else {
					if ( file_exists( $directory ) ) { return new WP_Error( 'c99_editorial_existing', 'Will not overwrite an existing presentation plugin.' ); }
					$backup = array( 'snapshot' => $before, 'active_plugins' => get_option( 'active_plugins', array() ), 'artifact_sha256' => $config['sha256'], 'commit' => $config['commit'], 'prior_plugin_absent' => true );
					if ( ! add_option( $backup_key, $backup, '', false ) || get_option( $backup_key ) !== $backup ) { return new WP_Error( 'c99_editorial_backup', 'Backup readback failed.' ); }
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/misc.php';
					require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
					$temp = download_url( $config['url'], 60 );
					if ( is_wp_error( $temp ) ) { return $temp; }
					try {
						if ( ! hash_equals( $config['sha256'], hash_file( 'sha256', $temp ) ) ) { return new WP_Error( 'c99_editorial_zip', 'Package checksum mismatch.' ); }
						$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
						$result = $upgrader->install( $temp, array( 'overwrite_package' => false ) );
						if ( is_wp_error( $result ) || true !== $result ) { return is_wp_error( $result ) ? $result : new WP_Error( 'c99_editorial_install', 'Installation failed.' ); }
					} finally { if ( is_file( $temp ) ) { unlink( $temp ); } }
				}
				if ( $files_digest( $directory ) !== $config['files'] || $snapshot() !== $backup['snapshot'] ) { return new WP_Error( 'c99_editorial_verify', 'Installed files or core state mismatch.' ); }
				$result = activate_plugin( $plugin, '', false, true );
				if ( is_wp_error( $result ) ) { return $result; }
				$expected = array_values( array_unique( array_merge( $backup['active_plugins'], array( $plugin ) ) ) );
				$actual = get_option( 'active_plugins', array() ); sort( $expected ); sort( $actual );
				if ( $expected !== $actual || $snapshot() !== $backup['snapshot'] ) { return new WP_Error( 'c99_editorial_membership', 'Unexpected activation changes.' ); }
				$purge();
				return array( 'active' => is_plugin_active( $plugin ), 'files' => $files_digest( $directory ), 'core_unchanged' => $snapshot() === $backup['snapshot'], 'backup_retained' => true );
			} finally { flock( $process, LOCK_UN ); fclose( $process ); }
		},
	) );
	register_rest_route( 'c99-editorial-deploy/v1', '/retire', array(
		'methods' => 'POST', 'permission_callback' => $permission,
		'callback' => static function () use ( $config ) {
			global $wpdb;
			$name = 'tmp-c99-editorial-' . $config['commit'];
			$table = $wpdb->prefix . 'snippets';
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,name,code FROM {$table} WHERE name=%s", $name ), ARRAY_A );
			if ( ! is_array( $rows ) || 1 !== count( $rows ) || false === strpos( $rows[0]['code'], $config['token'] ) || ! function_exists( 'Code_Snippets\\delete_snippet' ) ) { return new WP_Error( 'c99_editorial_retire', 'Exact temporary row not identified.' ); }
			$id = (int) $rows[0]['id'];
			$deleted = \Code_Snippets\delete_snippet( $id, false );
			wp_cache_flush();
			return array( 'deleted_id' => $id, 'deleted' => (bool) $deleted );
		},
	) );
} );
