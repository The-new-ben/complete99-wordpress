<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Settings {
	const OPTION_APP_URL   = 'complete99_os_url';
	const OPTION_ASSET_URL = 'complete99_os_public_url';
	const OPTION_SECRET    = 'complete99_sync_secret';
	const LEGACY_DEFAULT_APP_URL    = 'https://complete99-public.benben777.chatgpt.site/platform';
	const LEGACY_DEFAULT_APP_URL_EN = 'https://complete99-public.benben777.chatgpt.site/en/platform';
	const LEGACY_DEFAULT_ASSET_URL  = 'https://complete99-public.benben777.chatgpt.site';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function install_defaults() {
		$default_app_url   = self::default_app_url();
		$default_asset_url = self::default_asset_url();
		if ( '' === $default_app_url || '' === $default_asset_url ) {
			throw new \RuntimeException( 'WordPress-owned Complete99 URLs could not be established.' );
		}

		self::upgrade_legacy_default(
			self::OPTION_APP_URL,
			array( self::LEGACY_DEFAULT_APP_URL, self::LEGACY_DEFAULT_APP_URL_EN ),
			$default_app_url
		);
		self::upgrade_legacy_default(
			self::OPTION_ASSET_URL,
			array( self::LEGACY_DEFAULT_ASSET_URL ),
			$default_asset_url
		);
		self::install_default( self::OPTION_APP_URL, $default_app_url );
		self::install_default( self::OPTION_ASSET_URL, $default_asset_url );
		self::install_default( self::OPTION_SECRET, '' );
		self::assert_defaults();
	}

	/**
	 * Upgrade only known plugin defaults, preserving every owner-configured URL.
	 *
	 * @param string $name          Option name.
	 * @param array  $legacy_values Exact historical defaults.
	 * @param string $replacement   WordPress-owned replacement.
	 */
	private static function upgrade_legacy_default( $name, $legacy_values, $replacement ) {
		$stored = self::read_persisted_option( $name, false );
		if ( ! is_string( $stored ) ) {
			return;
		}

		$should_upgrade = false;
		foreach ( $legacy_values as $legacy_value ) {
			if ( is_string( $legacy_value ) && hash_equals( $legacy_value, $stored ) ) {
				$should_upgrade = true;
				break;
			}
		}
		if ( ! $should_upgrade ) {
			return;
		}

		update_option( $name, $replacement, false );
		$readback = self::read_persisted_option( $name, true );
		if ( ! is_string( $readback ) || ! hash_equals( $replacement, $readback ) ) {
			throw new \RuntimeException( 'A legacy Complete99 URL could not be upgraded safely.' );
		}
	}

	/**
	 * Fail closed when a plugin-owned option cannot be persisted.
	 *
	 * add_option() legitimately returns false when another request already
	 * created the row. The independent readback is therefore the source of
	 * truth rather than the mutation response.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Initial value.
	 */
	private static function install_default( $name, $value ) {
		if ( null === self::read_persisted_option( $name, false ) ) {
			add_option( $name, $value, '', false );
		}
		self::read_persisted_option( $name, true );
	}

	/**
	 * Verify the durable settings contract before a migration can commit.
	 */
	public static function assert_defaults() {
		foreach ( array( self::OPTION_APP_URL, self::OPTION_ASSET_URL ) as $option ) {
			$value     = self::read_persisted_option( $option, true );
			$canonical = is_string( $value ) ? self::canonical_https_url( $value ) : '';
			if ( '' === $canonical || ! hash_equals( $canonical, (string) $value ) ) {
				throw new \RuntimeException( 'A required Complete99 HTTPS option is invalid.' );
			}
		}
		$app_url   = (string) self::read_persisted_option( self::OPTION_APP_URL, true );
		$asset_url = (string) self::read_persisted_option( self::OPTION_ASSET_URL, true );
		if ( hash_equals( self::LEGACY_DEFAULT_APP_URL, $app_url )
			|| hash_equals( self::LEGACY_DEFAULT_APP_URL_EN, $app_url )
			|| hash_equals( self::LEGACY_DEFAULT_ASSET_URL, $asset_url ) ) {
			throw new \RuntimeException( 'A legacy ChatGPT Sites default remains configured.' );
		}

		if ( ! is_string( self::read_persisted_option( self::OPTION_SECRET, true ) ) ) {
			throw new \RuntimeException( 'The Complete99 sync-secret option is missing.' );
		}
	}

	/**
	 * Read one option directly from the database so migration success never
	 * depends on the object cache or on a filtered get_option() response.
	 *
	 * @param string $name     Option name.
	 * @param bool   $required Whether a missing row is an invariant failure.
	 * @return mixed|null
	 */
	private static function read_persisted_option( $name, $required ) {
		global $wpdb;

		$wpdb->last_error = '';
		$row              = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				(string) $name
			),
			ARRAY_A
		);
		if ( '' !== (string) $wpdb->last_error ) {
			throw new \RuntimeException( 'A required Complete99 option could not be verified.' );
		}
		if ( ! is_array( $row ) || ! array_key_exists( 'option_value', $row ) ) {
			if ( $required ) {
				throw new \RuntimeException( 'A required Complete99 option could not be stored.' );
			}
			return null;
		}
		return maybe_unserialize( $row['option_value'] );
	}

	public static function admin_menu() {
		add_options_page(
			__( 'Complete99 Platform', 'complete99-platform' ),
			__( 'Complete99 Platform', 'complete99-platform' ),
			'manage_options',
			'complete99-platform',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'complete99_platform',
			self::OPTION_APP_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_app_url' ),
				'default'           => self::default_app_url(),
			)
		);
		register_setting(
			'complete99_platform',
			self::OPTION_ASSET_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_asset_url' ),
				'default'           => self::default_asset_url(),
			)
		);
		register_setting(
			'complete99_platform',
			self::OPTION_SECRET,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
				'default'           => '',
			)
		);

		add_settings_section(
			'complete99_connection',
			__( 'Complete99 OS connection', 'complete99-platform' ),
			array( __CLASS__, 'render_section' ),
			'complete99-platform'
		);
		add_settings_field(
			self::OPTION_APP_URL,
			__( 'Operations app URL', 'complete99-platform' ),
			array( __CLASS__, 'render_url_field' ),
			'complete99-platform',
			'complete99_connection',
			array( 'option' => self::OPTION_APP_URL )
		);
		add_settings_field(
			self::OPTION_ASSET_URL,
			__( 'Owned asset base URL', 'complete99-platform' ),
			array( __CLASS__, 'render_url_field' ),
			'complete99-platform',
			'complete99_connection',
			array( 'option' => self::OPTION_ASSET_URL )
		);
		add_settings_field(
			self::OPTION_SECRET,
			__( 'Read-model sync secret', 'complete99-platform' ),
			array( __CLASS__, 'render_secret_field' ),
			'complete99-platform',
			'complete99_connection'
		);
	}

	public static function sanitize_https_url( $value ) {
		$value = self::canonical_https_url( $value );
		if ( '' === $value ) {
			add_settings_error(
				'complete99_platform',
				'complete99_https_required',
				__( 'Complete99 URLs must use canonical HTTPS with a public hostname.', 'complete99-platform' )
			);
		}
		return $value;
	}

	public static function sanitize_app_url( $value ) {
		return self::sanitize_url_option( $value, self::OPTION_APP_URL, self::default_app_url() );
	}

	public static function sanitize_asset_url( $value ) {
		return self::sanitize_url_option( $value, self::OPTION_ASSET_URL, self::default_asset_url() );
	}

	private static function sanitize_url_option( $value, $option, $fallback ) {
		$canonical = self::sanitize_https_url( $value );
		if ( '' !== $canonical ) {
			return $canonical;
		}

		$stored = self::canonical_https_url( get_option( $option, '' ) );
		return '' !== $stored ? $stored : $fallback;
	}

	/**
	 * Return one stable public HTTPS representation or an empty string.
	 *
	 * Public launch URLs cannot contain credentials, non-default ports, local
	 * hostnames or ambiguous host syntax. Paths, queries and fragments are kept
	 * only after WordPress has sanitised the complete URL.
	 *
	 * @param mixed $value Candidate URL.
	 * @return string
	 */
	private static function canonical_https_url( $value ) {
		$url = esc_url_raw( trim( (string) $value ), array( 'https' ) );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts )
			|| 'https' !== strtolower( isset( $parts['scheme'] ) ? (string) $parts['scheme'] : '' )
			|| empty( $parts['host'] )
			|| isset( $parts['user'] )
			|| isset( $parts['pass'] )
			|| ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) ) {
			return '';
		}

		$host = strtolower( rtrim( (string) $parts['host'], '.' ) );
		if ( false === strpos( $host, '.' )
			|| ! preg_match( '/^(?=.{1,253}$)(?!.*\.\.)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host ) ) {
			return '';
		}

		$canonical = 'https://' . $host;
		if ( ! empty( $parts['path'] ) && '/' !== $parts['path'] ) {
			$canonical .= '/' . ltrim( (string) $parts['path'], '/' );
		}
		if ( isset( $parts['query'] ) && '' !== $parts['query'] ) {
			$canonical .= '?' . $parts['query'];
		}
		if ( isset( $parts['fragment'] ) && '' !== $parts['fragment'] ) {
			$canonical .= '#' . $parts['fragment'];
		}

		return untrailingslashit( $canonical );
	}

	public static function sanitize_secret( $value ) {
		$value = trim( (string) $value );
		$old   = (string) get_option( self::OPTION_SECRET, '' );
		if ( '' === $value ) {
			return $old;
		}
		if ( strlen( $value ) < 32 ) {
			add_settings_error(
				'complete99_platform',
				'complete99_secret_short',
				__( 'The sync secret must contain at least 32 characters. The previous value was kept.', 'complete99-platform' )
			);
			return $old;
		}
		return $value;
	}

	public static function render_section() {
		echo '<p>';
		echo esc_html__( 'The operations URL defaults to the private WordPress OS shell, and owned assets default to this plugin. An owner-configured canonical HTTPS destination is preserved. The sync secret is never printed back to the browser.', 'complete99-platform' );
		echo '</p>';
	}

	public static function render_url_field( $args ) {
		$option = (string) $args['option'];
		$value  = (string) get_option( $option, '' );
		printf(
			'<input class="regular-text code" type="url" name="%1$s" value="%2$s" required pattern="https://.*" autocomplete="url" />',
			esc_attr( $option ),
			esc_attr( $value )
		);
	}

	public static function render_secret_field() {
		$configured = '' !== (string) get_option( self::OPTION_SECRET, '' );
		printf(
			'<input class="regular-text code" type="password" name="%1$s" value="" minlength="32" autocomplete="new-password" placeholder="%2$s" />',
			esc_attr( self::OPTION_SECRET ),
			esc_attr( $configured ? __( 'Configured - enter a new value only to rotate', 'complete99-platform' ) : __( 'Enter 32+ random characters', 'complete99-platform' ) )
		);
		echo '<p class="description">';
		echo esc_html__( 'Generate this once in a password manager, store the matching value in the Complete99 OS secret store, and rotate it if exposure is suspected.', 'complete99-platform' );
		echo '</p>';
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Complete99 Platform', 'complete99-platform' ); ?></h1>
			<?php settings_errors( 'complete99_platform' ); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'complete99_platform' );
				do_settings_sections( 'complete99-platform' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public static function app_url( $language = 'he' ) {
		unset( $language );
		$default = self::default_app_url();
		$url     = self::canonical_https_url( get_option( self::OPTION_APP_URL, $default ) );
		if ( '' === $url ) {
			$url = $default;
		}
		return $url;
	}

	/**
	 * Return the private WordPress-owned operations shell URL.
	 */
	public static function default_app_url() {
		$url = set_url_scheme( admin_url( 'admin.php?page=complete99-os' ), 'https' );
		return self::canonical_https_url( $url );
	}

	/**
	 * Return the plugin-owned original-asset directory.
	 */
	public static function default_asset_url() {
		$url = set_url_scheme( COMPLETE99_PLATFORM_URL . 'assets/images/original', 'https' );
		return self::canonical_https_url( $url );
	}

	public static function owned_asset_url( $filename ) {
		$filename = sanitize_file_name( (string) $filename );
		if ( 0 !== strpos( $filename, 'c99-' ) || ! preg_match( '/\.(?:jpe?g|png|webp|avif)$/i', $filename ) ) {
			return '';
		}
		$default = self::default_asset_url();
		$base    = self::canonical_https_url( get_option( self::OPTION_ASSET_URL, $default ) );
		if ( '' === $base ) {
			$base = $default;
		}
		if ( '' === $base ) {
			return '';
		}
		if ( hash_equals( $default, $base ) ) {
			$owned_filename = self::plugin_owned_asset_filename( $filename );
			return '' !== $owned_filename ? trailingslashit( $base ) . rawurlencode( $owned_filename ) : '';
		}
		return trailingslashit( $base ) . 'assets/original/' . rawurlencode( $filename );
	}

	/**
	 * Resolve a requested legacy filename to a file actually owned by the plugin.
	 *
	 * The bundled source set is optimized to WebP/AVIF. Unknown legacy files fail
	 * closed instead of producing a broken or externally hosted URL.
	 */
	private static function plugin_owned_asset_filename( $filename ) {
		$filename  = sanitize_file_name( (string) $filename );
		$directory = trailingslashit( COMPLETE99_PLATFORM_DIR . 'assets/images/original' );
		$candidates = array( $filename );
		$extension  = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
		$stem       = (string) pathinfo( $filename, PATHINFO_FILENAME );
		if ( in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) && '' !== $stem ) {
			$candidates[] = $stem . '.webp';
			$candidates[] = $stem . '.avif';
		}
		foreach ( array_unique( $candidates ) as $candidate ) {
			if ( is_file( $directory . $candidate ) ) {
				return $candidate;
			}
		}
		return '';
	}
}
