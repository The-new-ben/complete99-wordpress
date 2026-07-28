<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Settings {
	const OPTION_APP_URL   = 'complete99_os_url';
	const OPTION_ASSET_URL = 'complete99_os_public_url';
	const OPTION_SECRET    = 'complete99_sync_secret';
	const DEFAULT_APP_URL  = 'https://complete99-os.benben777.chatgpt.site';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function install_defaults() {
		if ( false === get_option( self::OPTION_APP_URL, false ) ) {
			add_option( self::OPTION_APP_URL, self::DEFAULT_APP_URL, '', false );
		}
		if ( false === get_option( self::OPTION_ASSET_URL, false ) ) {
			add_option( self::OPTION_ASSET_URL, self::DEFAULT_APP_URL, '', false );
		}
		if ( false === get_option( self::OPTION_SECRET, false ) ) {
			add_option( self::OPTION_SECRET, '', '', false );
		}
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
				'sanitize_callback' => array( __CLASS__, 'sanitize_https_url' ),
				'default'           => self::DEFAULT_APP_URL,
			)
		);
		register_setting(
			'complete99_platform',
			self::OPTION_ASSET_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_https_url' ),
				'default'           => self::DEFAULT_APP_URL,
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
		$value = esc_url_raw( trim( (string) $value ), array( 'https' ) );
		if ( '' === $value || 'https' !== wp_parse_url( $value, PHP_URL_SCHEME ) ) {
			add_settings_error(
				'complete99_platform',
				'complete99_https_required',
				__( 'Complete99 URLs must use HTTPS.', 'complete99-platform' )
			);
			return '';
		}
		return untrailingslashit( $value );
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
		echo esc_html__( 'These values are stored in WordPress options. The secret is never printed back to the browser and is used only to verify signed server-to-server updates.', 'complete99-platform' );
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
			esc_attr( $configured ? __( 'Configured — enter a new value only to rotate', 'complete99-platform' ) : __( 'Enter 32+ random characters', 'complete99-platform' ) )
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

	public static function app_url() {
		return (string) get_option( self::OPTION_APP_URL, self::DEFAULT_APP_URL );
	}

	public static function owned_asset_url( $filename ) {
		$filename = sanitize_file_name( (string) $filename );
		if ( 0 !== strpos( $filename, 'c99-' ) || ! preg_match( '/\.(?:jpe?g|png|webp|avif)$/i', $filename ) ) {
			return '';
		}
		$base = (string) get_option( self::OPTION_ASSET_URL, self::DEFAULT_APP_URL );
		return trailingslashit( $base ) . 'assets/original/' . rawurlencode( $filename );
	}
}
