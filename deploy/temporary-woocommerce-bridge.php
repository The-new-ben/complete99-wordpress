<?php
/**
 * Temporary admin-only bridge for the pinned WooCommerce dependency.
 *
 * The orchestrator replaces every reserved marker, strips this opening tag,
 * creates one global Code Snippets row, calls the routes, permanently deletes
 * that exact row and proves that these routes return 404.
 *
 * No privileged work runs when this snippet is loaded.
 */

add_action(
	'rest_api_init',
	static function () {
		$config = array(
			'token_sha256'    => '__C99_WOO_TOKEN_SHA256__',
			'deployment_id'   => '__C99_WOO_DEPLOYMENT_ID__',
			'snippet_name'    => '__C99_WOO_SNIPPET_NAME__',
			'target_host'     => '__C99_WOO_TARGET_HOST__',
			'plugin'          => '__C99_WOO_PLUGIN__',
			'version'         => '__C99_WOO_VERSION__',
			'package_url'     => '__C99_WOO_PACKAGE_URL__',
			'package_sha256'  => '__C99_WOO_PACKAGE_SHA256__',
			'tree_file_count' => __C99_WOO_TREE_FILE_COUNT__,
			'tree_bytes'      => __C99_WOO_TREE_BYTES__,
			'tree_sha256'     => '__C99_WOO_TREE_SHA256__',
		);

		$route_prefix = '/' . $config['deployment_id'];
		$permission   = static function ( WP_REST_Request $request ) use ( $config ) {
			$provided_token = (string) $request->get_param( 'token' );
			$provided_id    = (string) $request->get_param( 'deployment_id' );
			if ( ! current_user_can( 'update_plugins' )
				|| ! hash_equals( $config['token_sha256'], hash( 'sha256', $provided_token ) )
				|| ! hash_equals( $config['deployment_id'], $provided_id ) ) {
				return new WP_Error(
					'complete99_woocommerce_deploy_forbidden',
					'This temporary deployment route is restricted.',
					array( 'status' => 403 )
				);
			}
			return true;
		};

		$verify_site_identity = static function () use ( $config ) {
			$origins = array(
				'home'    => home_url( '/' ),
				'siteurl' => site_url( '/' ),
				'rest'    => rest_url(),
			);
			$hosts = array();
			foreach ( $origins as $key => $origin ) {
				$host = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );
				if ( '' === $host || ! hash_equals( $config['target_host'], $host ) ) {
					return new WP_Error(
						'complete99_woocommerce_deploy_site_identity',
						'The temporary bridge site identity does not match the approved target.',
						array( 'status' => 409 )
					);
				}
				$hosts[ $key ] = $host;
			}
			return $hosts;
		};

		$install_marker_option = 'complete99_woocommerce_install_recovery';
		$plugin_directory_name = dirname( $config['plugin'] );
		$plugin_root           = untrailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ) . '/' . $plugin_directory_name;
		$marker_fields         = array(
			'schema',
			'deployment_id',
			'target_host',
			'plugin',
			'target_directory',
			'version',
			'package_url',
			'package_sha256',
			'tree_file_count',
			'tree_bytes',
			'tree_sha256',
			'started_at',
			'created_by',
		);

		$marker_payload = static function ( $value ) use ( $config, $plugin_root, $marker_fields ) {
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'complete99_woocommerce_install_marker_type', 'The WooCommerce install marker is invalid.' );
			}
			$keys = array_keys( $value );
			sort( $keys, SORT_STRING );
			$expected_keys = $marker_fields;
			sort( $expected_keys, SORT_STRING );
			if ( $keys !== $expected_keys ) {
				return new WP_Error( 'complete99_woocommerce_install_marker_fields', 'The WooCommerce install marker fields differ.' );
			}
			if ( 'complete99-woocommerce-install-recovery/v1' !== $value['schema']
				|| ! is_string( $value['deployment_id'] )
				|| 1 !== preg_match( '/^c99-commerce-[A-Za-z0-9._-]{1,82}$/D', $value['deployment_id'] )
				|| ! is_string( $value['target_host'] )
				|| ! hash_equals( $config['target_host'], $value['target_host'] )
				|| ! is_string( $value['plugin'] )
				|| ! hash_equals( $config['plugin'], $value['plugin'] )
				|| ! is_string( $value['target_directory'] )
				|| ! hash_equals( $plugin_root, wp_normalize_path( $value['target_directory'] ) )
				|| ! is_string( $value['version'] )
				|| 1 !== preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:-[A-Za-z0-9.-]+)?$/D', $value['version'] )
				|| ! is_string( $value['package_url'] )
				|| ! hash_equals( 'https://downloads.wordpress.org/plugin/woocommerce.' . $value['version'] . '.zip', $value['package_url'] )
				|| ! is_string( $value['package_sha256'] )
				|| 1 !== preg_match( '/^[a-f0-9]{64}$/D', $value['package_sha256'] )
				|| ! is_int( $value['tree_file_count'] ) || $value['tree_file_count'] < 1
				|| ! is_int( $value['tree_bytes'] ) || $value['tree_bytes'] < 1
				|| ! is_string( $value['tree_sha256'] )
				|| 1 !== preg_match( '/^[a-f0-9]{64}$/D', $value['tree_sha256'] )
				|| ! is_string( $value['started_at'] )
				|| 1 !== preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value['started_at'] )
				|| ! is_int( $value['created_by'] ) || $value['created_by'] < 1 ) {
				return new WP_Error( 'complete99_woocommerce_install_marker_values', 'The WooCommerce install marker values are invalid.' );
			}
			$payload = array();
			foreach ( $marker_fields as $field ) {
				$payload[ $field ] = $value[ $field ];
			}
			return $payload;
		};

		$marker_authentication = static function ( $payload ) {
			$encoded = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
			if ( ! is_string( $encoded ) ) {
				return '';
			}
			return hash_hmac( 'sha256', $encoded, wp_salt( 'auth' ) );
		};

		$read_install_marker = static function () use ( $install_marker_option, $marker_payload, $marker_authentication ) {
			$missing = '__complete99_install_marker_missing__';
			$raw     = get_option( $install_marker_option, $missing );
			if ( $missing === $raw ) {
				return array(
					'exists' => false,
					'valid'  => false,
				);
			}
			if ( ! is_array( $raw ) || ! isset( $raw['authentication'] ) || ! is_string( $raw['authentication'] ) ) {
				return array(
					'exists'     => true,
					'valid'      => false,
					'error_code' => 'complete99_woocommerce_install_marker_authentication',
				);
			}
			$authentication = $raw['authentication'];
			unset( $raw['authentication'] );
			$payload = $marker_payload( $raw );
			if ( is_wp_error( $payload ) ) {
				return array(
					'exists'     => true,
					'valid'      => false,
					'error_code' => $payload->get_error_code(),
				);
			}
			$expected = $marker_authentication( $payload );
			if ( '' === $expected || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $authentication ) || ! hash_equals( $expected, $authentication ) ) {
				return array(
					'exists'     => true,
					'valid'      => false,
					'error_code' => 'complete99_woocommerce_install_marker_authentication',
				);
			}
			return array(
				'exists'  => true,
				'valid'   => true,
				'payload' => $payload,
			);
		};

		$marker_evidence = static function ( $marker ) {
			$evidence = array(
				'exists' => ! empty( $marker['exists'] ),
				'valid'  => ! empty( $marker['valid'] ),
			);
			if ( isset( $marker['error_code'] ) ) {
				$evidence['error_code'] = (string) $marker['error_code'];
			}
			if ( ! empty( $marker['valid'] ) && isset( $marker['payload'] ) && is_array( $marker['payload'] ) ) {
				$evidence['schema']           = $marker['payload']['schema'];
				$evidence['deployment_id']    = $marker['payload']['deployment_id'];
				$evidence['target_directory'] = $marker['payload']['target_directory'];
				$evidence['version']          = $marker['payload']['version'];
				$evidence['package_url']      = $marker['payload']['package_url'];
				$evidence['package_sha256']   = $marker['payload']['package_sha256'];
				$evidence['tree_sha256']      = $marker['payload']['tree_sha256'];
			}
			return $evidence;
		};

		$verify_plugin_target = static function () use ( $config, $plugin_root ) {
			if ( 'woocommerce' !== dirname( $config['plugin'] )
				|| ! hash_equals( untrailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ) . '/woocommerce', $plugin_root )
				|| is_link( $plugin_root ) ) {
				return new WP_Error(
					'complete99_woocommerce_install_target',
					'The WooCommerce recovery target is not the exact plugin directory.',
					array( 'status' => 409 )
				);
			}
			$resolved_parent = realpath( WP_PLUGIN_DIR );
			if ( ! is_string( $resolved_parent ) || '' === $resolved_parent ) {
				return new WP_Error(
					'complete99_woocommerce_install_parent',
					'The WordPress plugin directory could not be resolved.',
					array( 'status' => 500 )
				);
			}
			$resolved_target = untrailingslashit( wp_normalize_path( $resolved_parent ) ) . '/woocommerce';
			if ( file_exists( $plugin_root ) ) {
				$actual_target = realpath( $plugin_root );
				if ( ! is_string( $actual_target ) || ! hash_equals( $resolved_target, wp_normalize_path( $actual_target ) ) ) {
					return new WP_Error(
						'complete99_woocommerce_install_resolved_target',
						'The existing WooCommerce recovery target resolves outside the exact plugin directory.',
						array( 'status' => 409 )
					);
				}
			}
			return array(
				'configured' => $plugin_root,
				'resolved'   => $resolved_target,
			);
		};

		$verify_partial_tree_subset = static function ( $payload ) use ( $plugin_root, $plugin_directory_name ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return new WP_Error(
					'complete99_woocommerce_recovery_zip_support',
					'The authenticated WooCommerce recovery requires ZipArchive support.',
					array( 'status' => 500 )
				);
			}
			$temporary_file = download_url( $payload['package_url'], 300, false );
			if ( is_wp_error( $temporary_file ) ) {
				return new WP_Error(
					'complete99_woocommerce_recovery_package_download',
					'The recovery package bound to the authenticated marker could not be downloaded.',
					array( 'status' => 502 )
				);
			}
			$zip        = null;
			$zip_opened = false;
			try {
				$package_bytes = is_file( $temporary_file ) ? filesize( $temporary_file ) : false;
				$package_sha   = is_file( $temporary_file ) ? strtolower( (string) hash_file( 'sha256', $temporary_file ) ) : '';
				if ( false === $package_bytes || $package_bytes < 1 || $package_bytes > 134217728
					|| ! hash_equals( $payload['package_sha256'], $package_sha ) ) {
					return new WP_Error(
						'complete99_woocommerce_recovery_package_digest',
						'The recovery package did not match the exact authenticated marker digest.',
						array( 'status' => 422 )
					);
				}
				$zip    = new ZipArchive();
				$opened = $zip->open( $temporary_file );
				if ( true !== $opened ) {
					return new WP_Error(
						'complete99_woocommerce_recovery_package_zip',
						'The authenticated WooCommerce recovery package is not a readable ZIP archive.',
						array( 'status' => 422 )
					);
				}
				$zip_opened = true;

				$records       = array();
				$verified_bytes = 0;
				$iterator      = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $plugin_root, FilesystemIterator::SKIP_DOTS ),
					RecursiveIteratorIterator::SELF_FIRST
				);
				foreach ( $iterator as $candidate ) {
					if ( $candidate->isLink() ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_symlink',
							'The authenticated WooCommerce recovery directory contains a symbolic link.',
							array( 'status' => 409 )
						);
					}
					if ( $candidate->isDir() ) {
						continue;
					}
					if ( ! $candidate->isFile() ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_node_type',
							'The WooCommerce recovery directory contains an unsupported filesystem node.',
							array( 'status' => 409 )
						);
					}
					$path = wp_normalize_path( (string) $candidate->getPathname() );
					if ( 0 !== strpos( $path, $plugin_root . '/' ) ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_relative_path',
							'A WooCommerce recovery file resolved outside the exact target directory.',
							array( 'status' => 409 )
						);
					}
					$relative = substr( $path, strlen( $plugin_root ) + 1 );
					if ( ! is_string( $relative ) || '' === $relative
						|| 1 === preg_match( '#(^|/)\.\.?(/|$)#', $relative ) ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_relative_path',
							'A WooCommerce recovery file has an invalid relative path.',
							array( 'status' => 409 )
						);
					}
					$entry = $plugin_directory_name . '/' . $relative;
					$index = $zip->locateName( $entry, 0 );
					if ( false === $index ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_unknown_file',
							'The partial WooCommerce directory contains a file absent from its authenticated package.',
							array( 'status' => 409 )
						);
					}
					$stat = $zip->statIndex( $index, ZipArchive::FL_UNCHANGED );
					if ( ! is_array( $stat ) || ! isset( $stat['name'], $stat['size'] )
						|| ! hash_equals( $entry, (string) $stat['name'] ) ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_package_entry',
							'The authenticated package entry metadata differs from the recovery path.',
							array( 'status' => 422 )
						);
					}
					$existing_size = (int) $candidate->getSize();
					if ( $existing_size !== (int) $stat['size'] ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_file_size',
							'A partial WooCommerce file size differs from its authenticated package entry.',
							array( 'status' => 409 )
						);
					}
					$stream = $zip->getStream( $entry );
					if ( ! is_resource( $stream ) ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_package_stream',
							'An authenticated WooCommerce package entry could not be read.',
							array( 'status' => 422 )
						);
					}
					$context    = hash_init( 'sha256' );
					$read_bytes = 0;
					$read_error = false;
					while ( ! feof( $stream ) ) {
						$chunk = fread( $stream, 1048576 );
						if ( false === $chunk ) {
							$read_error = true;
							break;
						}
						$read_bytes += strlen( $chunk );
						hash_update( $context, $chunk );
					}
					fclose( $stream );
					$package_file_sha = hash_final( $context );
					$existing_sha     = strtolower( (string) hash_file( 'sha256', $path ) );
					if ( $read_error || $read_bytes !== $existing_size
						|| ! hash_equals( $package_file_sha, $existing_sha ) ) {
						return new WP_Error(
							'complete99_woocommerce_recovery_file_digest',
							'A partial WooCommerce file differs from its authenticated package entry.',
							array( 'status' => 409 )
						);
					}
					$records[]      = array( $relative, $existing_size, $existing_sha );
					$verified_bytes += $existing_size;
				}
				usort(
					$records,
					static function ( $left, $right ) {
						return strcmp( (string) $left[0], (string) $right[0] );
					}
				);
				$encoded_records = wp_json_encode( $records, JSON_UNESCAPED_SLASHES );
				return array(
					'package_url'         => $payload['package_url'],
					'package_sha256'      => $package_sha,
					'package_bytes'       => (int) $package_bytes,
					'verified_file_count' => count( $records ),
					'verified_file_bytes' => $verified_bytes,
					'subset_manifest_sha256' => is_string( $encoded_records ) ? hash( 'sha256', $encoded_records ) : '',
					'byte_exact_subset'   => true,
					'unknown_files'       => 0,
					'mismatched_files'    => 0,
					'symlinks'            => 0,
				);
			} finally {
				if ( $zip_opened && $zip instanceof ZipArchive ) {
					$zip->close();
				}
				if ( is_string( $temporary_file ) && is_file( $temporary_file ) ) {
					wp_delete_file( $temporary_file );
				}
			}
		};

		$inspect = static function () use ( $config, $plugin_root, $read_install_marker, $marker_evidence ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugin_file = WP_PLUGIN_DIR . '/' . $config['plugin'];
			$plugin_data = is_file( $plugin_file )
				? get_plugin_data( $plugin_file, false, false )
				: array();
			$namespaces = rest_get_server()->get_namespaces();
			$records     = array();
			$total_bytes = 0;
			if ( is_dir( $plugin_root ) ) {
				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $plugin_root, FilesystemIterator::SKIP_DOTS )
				);
				foreach ( $iterator as $file ) {
					if ( ! $file->isFile() || $file->isLink() ) {
						continue;
					}
					$path = (string) $file->getPathname();
					$relative = str_replace( '\\', '/', ltrim( substr( $path, strlen( $plugin_root ) ), '/\\' ) );
					$size = (int) $file->getSize();
					$sha  = (string) hash_file( 'sha256', $path );
					$records[] = array( $relative, $size, $sha );
					$total_bytes += $size;
				}
			}
			usort(
				$records,
				static function ( $left, $right ) {
					return strcmp( (string) $left[0], (string) $right[0] );
				}
			);
			$tree_json = wp_json_encode( $records, JSON_UNESCAPED_SLASHES );
			return array(
				'plugin'             => $config['plugin'],
				'expected_version'   => $config['version'],
				'header_version'     => (string) ( $plugin_data['Version'] ?? '' ),
				'active'             => is_plugin_active( $config['plugin'] ),
				'runtime_loaded'     => class_exists( 'WooCommerce' ) && function_exists( 'WC' ),
				'runtime_version'    => defined( 'WC_VERSION' ) ? (string) WC_VERSION : '',
				'product_post_type'  => post_type_exists( 'product' ),
				'rest_namespace'     => in_array( 'wc/v3', $namespaces, true ),
				'tree_file_count'    => count( $records ),
				'tree_bytes'         => $total_bytes,
				'tree_sha256'        => is_string( $tree_json ) ? hash( 'sha256', $tree_json ) : '',
				'expected_tree_file_count' => $config['tree_file_count'],
				'expected_tree_bytes'      => $config['tree_bytes'],
				'expected_tree_sha256'     => $config['tree_sha256'],
				'install_recovery_marker'  => $marker_evidence( $read_install_marker() ),
			);
		};

		register_rest_route(
			'complete99-woocommerce-deploy/v1',
			$route_prefix . '/install',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function () use ( $config, $verify_site_identity, $inspect, $plugin_root, $install_marker_option, $marker_payload, $marker_authentication, $read_install_marker, $marker_evidence, $verify_plugin_target, $verify_partial_tree_subset ) {
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}

					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/misc.php';
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

					$target = $verify_plugin_target();
					if ( is_wp_error( $target ) ) {
						return $target;
					}
					$marker = $read_install_marker();
					if ( ! empty( $marker['exists'] ) && empty( $marker['valid'] ) ) {
						return new WP_Error(
							'complete99_woocommerce_install_marker_untrusted',
							'An unauthenticated WooCommerce install marker blocks dependency changes.',
							array( 'status' => 409 )
						);
					}

					$current       = $inspect();
					$root_exists   = file_exists( $plugin_root ) || is_link( $plugin_root );
					$exact_current = $root_exists
						&& is_dir( $plugin_root )
						&& ! is_link( $plugin_root )
						&& hash_equals( $config['version'], (string) ( $current['header_version'] ?? '' ) )
						&& (int) $config['tree_file_count'] === (int) ( $current['tree_file_count'] ?? -1 )
						&& (int) $config['tree_bytes'] === (int) ( $current['tree_bytes'] ?? -1 )
						&& hash_equals( $config['tree_sha256'], (string) ( $current['tree_sha256'] ?? '' ) );
					$recovery      = array(
						'prior_marker' => $marker_evidence( $marker ),
						'target'       => $target,
						'cleanup'      => array(
							'attempted' => false,
							'verified'  => false,
						),
					);

					if ( $exact_current ) {
						if ( ! is_plugin_active( $config['plugin'] ) ) {
							$activated = activate_plugin( $config['plugin'], '', false, true );
							if ( is_wp_error( $activated ) ) {
								return $activated;
							}
						}
						$verified_reuse = $inspect();
						if ( empty( $verified_reuse['active'] )
							|| ! hash_equals( $config['version'], (string) ( $verified_reuse['header_version'] ?? '' ) )
							|| (int) $config['tree_file_count'] !== (int) ( $verified_reuse['tree_file_count'] ?? -1 )
							|| (int) $config['tree_bytes'] !== (int) ( $verified_reuse['tree_bytes'] ?? -1 )
							|| ! hash_equals( $config['tree_sha256'], (string) ( $verified_reuse['tree_sha256'] ?? '' ) ) ) {
							return new WP_Error(
								'complete99_woocommerce_reuse_readback',
								'The exact WooCommerce dependency did not pass reuse readback.',
								array( 'status' => 500 )
							);
						}
						if ( ! empty( $marker['exists'] ) ) {
							delete_option( $install_marker_option );
							if ( ! empty( $read_install_marker()['exists'] ) ) {
								return new WP_Error(
									'complete99_woocommerce_install_marker_clear',
									'The WooCommerce install marker remained after exact reuse verification.',
									array( 'status' => 500 )
								);
							}
						}
						do_action( 'litespeed_purge_all' );
						wp_cache_flush();
						$recovery['marker_cleared'] = true;
						return array(
							'installed_pending_fresh_status' => true,
							'installation_action'            => 'reuse_verified',
							'package_sha256'                 => $config['package_sha256'],
							'site_identity'                  => $site_identity,
							'install_recovery'               => $recovery,
							'state'                          => $inspect(),
						);
					}

					$installation_action = 'fresh_install';
					if ( $root_exists ) {
						if ( ! empty( $current['active'] ) ) {
							return new WP_Error(
								'complete99_woocommerce_active_dependency_conflict',
								'An active non-exact WooCommerce directory cannot be recovered automatically.',
								array( 'status' => 409 )
							);
						}
						if ( empty( $marker['valid'] ) || ! is_dir( $plugin_root ) || is_link( $plugin_root ) ) {
							return new WP_Error(
								'complete99_woocommerce_unowned_dependency_conflict',
								'A non-exact WooCommerce directory lacks an authenticated Complete99 recovery marker.',
								array( 'status' => 409 )
							);
						}
						$partial_tree_proof = $verify_partial_tree_subset( $marker['payload'] );
						if ( is_wp_error( $partial_tree_proof ) ) {
							return $partial_tree_proof;
						}
						$recovery['partial_tree_proof'] = $partial_tree_proof;
						$link_iterator = new RecursiveIteratorIterator(
							new RecursiveDirectoryIterator( $plugin_root, FilesystemIterator::SKIP_DOTS ),
							RecursiveIteratorIterator::SELF_FIRST
						);
						foreach ( $link_iterator as $candidate ) {
							if ( $candidate->isLink() ) {
								return new WP_Error(
									'complete99_woocommerce_recovery_symlink',
									'The authenticated WooCommerce recovery directory contains a symbolic link.',
									array( 'status' => 409 )
								);
							}
							if ( ! $candidate->isDir() && ! $candidate->isFile() ) {
								return new WP_Error(
									'complete99_woocommerce_recovery_node_type',
									'The WooCommerce recovery directory changed to include an unsupported filesystem node.',
									array( 'status' => 409 )
								);
							}
						}
						$predelete_state = $inspect();
						if ( ! hash_equals( $partial_tree_proof['subset_manifest_sha256'], (string) ( $predelete_state['tree_sha256'] ?? '' ) ) ) {
							return new WP_Error(
								'complete99_woocommerce_recovery_tree_changed',
								'The partial WooCommerce directory changed after package verification.',
								array( 'status' => 409 )
							);
						}
						$recovery['predelete_snapshot'] = array(
							'tree_sha256' => $predelete_state['tree_sha256'],
							'verified'    => true,
						);
						if ( ! WP_Filesystem() ) {
							return new WP_Error(
								'complete99_woocommerce_recovery_filesystem',
								'The WordPress filesystem could not be initialized for exact recovery.',
								array( 'status' => 500 )
							);
						}
						global $wp_filesystem;
						if ( ! isset( $wp_filesystem->method ) || 'direct' !== $wp_filesystem->method ) {
							return new WP_Error(
								'complete99_woocommerce_recovery_filesystem_method',
								'The exact WooCommerce recovery requires the direct WordPress filesystem method.',
								array( 'status' => 500 )
							);
						}
						$recovery['cleanup']['attempted'] = true;
						$deleted = $wp_filesystem->delete( $plugin_root, true, 'd' );
						if ( ! $deleted || file_exists( $plugin_root ) || is_link( $plugin_root ) ) {
							return new WP_Error(
								'complete99_woocommerce_recovery_delete',
								'The authenticated partial WooCommerce directory could not be removed exactly.',
								array( 'status' => 500 )
							);
						}
						$recovery['cleanup']['verified'] = true;
						$installation_action             = 'recovered_partial_reinstall';
					}

					$new_payload = array(
						'schema'           => 'complete99-woocommerce-install-recovery/v1',
						'deployment_id'    => $config['deployment_id'],
						'target_host'      => $config['target_host'],
						'plugin'           => $config['plugin'],
						'target_directory' => $plugin_root,
						'version'          => $config['version'],
						'package_url'      => $config['package_url'],
						'package_sha256'   => $config['package_sha256'],
						'tree_file_count'  => (int) $config['tree_file_count'],
						'tree_bytes'       => (int) $config['tree_bytes'],
						'tree_sha256'      => $config['tree_sha256'],
						'started_at'       => gmdate( 'Y-m-d\TH:i:s\Z' ),
						'created_by'       => get_current_user_id(),
					);
					$new_payload = $marker_payload( $new_payload );
					if ( is_wp_error( $new_payload ) ) {
						return $new_payload;
					}
					$new_marker                   = $new_payload;
					$new_marker['authentication'] = $marker_authentication( $new_payload );
					if ( '' === $new_marker['authentication'] ) {
						return new WP_Error( 'complete99_woocommerce_install_marker_encode', 'The WooCommerce install marker could not be authenticated.', array( 'status' => 500 ) );
					}
					update_option( $install_marker_option, $new_marker, false );
					$persisted_marker = $read_install_marker();
					if ( empty( $persisted_marker['valid'] )
						|| $new_payload !== $persisted_marker['payload'] ) {
						return new WP_Error(
							'complete99_woocommerce_install_marker_persist',
							'The exact WooCommerce install marker did not persist.',
							array( 'status' => 500 )
						);
					}
					$recovery['persisted_marker'] = $marker_evidence( $persisted_marker );

					$temporary_file = download_url( $config['package_url'], 300, false );
					if ( is_wp_error( $temporary_file ) ) {
						return new WP_Error(
							'complete99_woocommerce_download_failed',
							'The pinned WooCommerce package could not be downloaded.',
							array( 'status' => 502 )
						);
					}

					try {
						$actual_sha256 = is_file( $temporary_file )
							? strtolower( (string) hash_file( 'sha256', $temporary_file ) )
							: '';
						if ( ! hash_equals( $config['package_sha256'], $actual_sha256 ) ) {
							return new WP_Error(
								'complete99_woocommerce_package_digest',
								'The downloaded WooCommerce package did not match the pinned SHA256.',
								array( 'status' => 422 )
							);
						}

						$skin     = new WP_Ajax_Upgrader_Skin();
						$upgrader = new Plugin_Upgrader( $skin );
						$installed = $upgrader->install(
							$temporary_file,
							array(
								'overwrite_package'  => false,
								'clear_update_cache' => false,
							)
						);
						if ( is_wp_error( $installed ) ) {
							return $installed;
						}
						if ( false === $installed ) {
							return new WP_Error(
								'complete99_woocommerce_install_failed',
								'The pinned WooCommerce package was not installed.',
								array( 'status' => 500 )
							);
						}

						wp_clean_plugins_cache( true );
						$installed_state = $inspect();
						if ( ! hash_equals( $config['version'], (string) ( $installed_state['header_version'] ?? '' ) )
							|| (int) $config['tree_file_count'] !== (int) ( $installed_state['tree_file_count'] ?? -1 )
							|| (int) $config['tree_bytes'] !== (int) ( $installed_state['tree_bytes'] ?? -1 )
							|| ! hash_equals( $config['tree_sha256'], (string) ( $installed_state['tree_sha256'] ?? '' ) ) ) {
							return new WP_Error(
								'complete99_woocommerce_install_tree_mismatch',
								'The installed WooCommerce code tree does not match the pinned dependency.',
								array( 'status' => 409 )
							);
						}

						if ( ! is_plugin_active( $config['plugin'] ) ) {
							$activated = activate_plugin( $config['plugin'], '', false, true );
							if ( is_wp_error( $activated ) ) {
								return $activated;
							}
						}
						if ( ! is_plugin_active( $config['plugin'] ) ) {
							return new WP_Error(
								'complete99_woocommerce_activation_failed',
								'WooCommerce did not remain active after installation.',
								array( 'status' => 500 )
							);
						}

						$final_state = $inspect();
						if ( empty( $final_state['active'] )
							|| ! hash_equals( $config['version'], (string) ( $final_state['header_version'] ?? '' ) )
							|| (int) $config['tree_file_count'] !== (int) ( $final_state['tree_file_count'] ?? -1 )
							|| (int) $config['tree_bytes'] !== (int) ( $final_state['tree_bytes'] ?? -1 )
							|| ! hash_equals( $config['tree_sha256'], (string) ( $final_state['tree_sha256'] ?? '' ) ) ) {
							return new WP_Error(
								'complete99_woocommerce_install_final_readback',
								'The pinned WooCommerce dependency failed final exact readback.',
								array( 'status' => 500 )
							);
						}
						delete_option( $install_marker_option );
						if ( ! empty( $read_install_marker()['exists'] ) ) {
							return new WP_Error(
								'complete99_woocommerce_install_marker_clear',
								'The WooCommerce install marker remained after exact installation verification.',
								array( 'status' => 500 )
							);
						}
						$recovery['marker_cleared'] = true;
						do_action( 'litespeed_purge_all' );
						wp_cache_flush();
						return array(
							'installed_pending_fresh_status' => true,
							'installation_action'            => $installation_action,
							'package_sha256'                 => $actual_sha256,
							'site_identity'                  => $site_identity,
							'install_recovery'               => $recovery,
							'state'                          => $inspect(),
						);
					} finally {
						if ( is_string( $temporary_file ) && is_file( $temporary_file ) ) {
							wp_delete_file( $temporary_file );
						}
					}
				},
			)
		);

		register_rest_route(
			'complete99-woocommerce-deploy/v1',
			$route_prefix . '/status',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function () use ( $verify_site_identity, $inspect ) {
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					return array(
						'site_identity' => $site_identity,
						'state'         => $inspect(),
					);
				},
			)
		);

		register_rest_route(
			'complete99-woocommerce-deploy/v1',
			$route_prefix . '/retire',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => static function ( WP_REST_Request $request ) use ( $config, $verify_site_identity ) {
					$site_identity = $verify_site_identity();
					if ( is_wp_error( $site_identity ) ) {
						return $site_identity;
					}
					if ( ! function_exists( 'Code_Snippets\\get_snippet' )
						|| ! function_exists( 'Code_Snippets\\delete_snippet' ) ) {
						return new WP_Error(
							'complete99_woocommerce_retire_api',
							'The Code Snippets permanent-delete API is unavailable.',
							array( 'status' => 500 )
						);
					}
					$raw_ids = $request->get_param( 'snippet_ids' );
					if ( ! is_array( $raw_ids ) || 1 !== count( $raw_ids ) ) {
						return new WP_Error(
							'complete99_woocommerce_retire_ids',
							'Exactly one temporary snippet row must be retired.',
							array( 'status' => 400 )
						);
					}
					$snippet_id = absint( reset( $raw_ids ) );
					$snippet    = \Code_Snippets\get_snippet( $snippet_id, false );
					if ( ! $snippet || empty( $snippet->id )
						|| ! hash_equals( $config['snippet_name'], (string) $snippet->name ) ) {
						return new WP_Error(
							'complete99_woocommerce_retire_allowlist',
							'The requested snippet row is not this deployment bridge.',
							array( 'status' => 403 )
						);
					}
					if ( ! \Code_Snippets\delete_snippet( $snippet_id, false ) ) {
						return new WP_Error(
							'complete99_woocommerce_retire_delete',
							'The temporary snippet row could not be permanently deleted.',
							array( 'status' => 500 )
						);
					}
					$readback = \Code_Snippets\get_snippet( $snippet_id, false );
					if ( $readback && ! empty( $readback->id ) ) {
						return new WP_Error(
							'complete99_woocommerce_retire_readback',
							'The temporary snippet row remained after deletion.',
							array( 'status' => 500 )
						);
					}
					return array(
						'permanently_deleted' => array( $snippet_id ),
						'site_identity'       => $site_identity,
					);
				},
			)
		);
	}
);
