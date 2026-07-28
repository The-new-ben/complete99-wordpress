<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Leads {
	const ACTION = 'complete99_submit_lead';

	public static function boot() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_filter( 'manage_c99_lead_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_c99_lead_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );
	}

	public static function register_post_type() {
		register_post_type(
			'c99_lead',
			array(
				'labels' => array(
					'name'          => 'פניות',
					'singular_name' => 'פנייה',
					'edit_item'     => 'צפייה בפנייה',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'supports'            => array( 'title' ),
				'menu_icon'           => 'dashicons-email-alt',
				'capability_type'     => array( 'c99_lead', 'c99_leads' ),
				'capabilities'        => array(
					'create_posts'           => 'do_not_allow',
					'publish_posts'          => 'do_not_allow',
					'edit_post'              => 'edit_c99_lead',
					'read_post'              => 'read_c99_lead',
					'delete_post'            => 'delete_c99_lead',
					'edit_posts'             => 'edit_c99_leads',
					'edit_others_posts'      => 'edit_others_c99_leads',
					'read_private_posts'     => 'read_private_c99_leads',
					'delete_posts'           => 'delete_c99_leads',
					'delete_others_posts'    => 'delete_others_c99_leads',
					'delete_published_posts' => 'delete_c99_leads',
					'edit_published_posts'   => 'edit_c99_leads',
				),
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
			)
		);
	}

	public static function render_form( $language = 'he', $interest = 'institutional-service' ) {
		$is_he = 'he' === $language;
		$sent  = isset( $_GET['c99_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['c99_sent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $sent ) {
			echo '<div class="c99-form-success" role="status">';
			echo esc_html( $is_he ? 'הפנייה נשמרה. נציג מורשה יבחן אותה לפי תהליך הטיפול.' : 'Your enquiry was stored. An authorised representative can review it under the handling process.' );
			echo '</div>';
		}
		?>
		<form class="c99-lead-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<input type="hidden" name="language" value="<?php echo esc_attr( $language ); ?>" />
			<input type="hidden" name="interest" value="<?php echo esc_attr( sanitize_key( $interest ) ); ?>" />
			<?php wp_nonce_field( 'complete99_lead_form', 'complete99_lead_nonce' ); ?>
			<div class="c99-honeypot" aria-hidden="true">
				<label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off" /></label>
			</div>
			<label>
				<span><?php echo esc_html( $is_he ? 'שם איש קשר' : 'Contact name' ); ?></span>
				<input type="text" name="contact_name" maxlength="120" required autocomplete="name" />
			</label>
			<label>
				<span><?php echo esc_html( $is_he ? 'ארגון' : 'Organisation' ); ?></span>
				<input type="text" name="organisation" maxlength="160" required autocomplete="organization" />
			</label>
			<label>
				<span><?php echo esc_html( $is_he ? 'דוא״ל' : 'Email' ); ?></span>
				<input type="email" name="email" maxlength="190" required autocomplete="email" inputmode="email" />
			</label>
			<label>
				<span><?php echo esc_html( $is_he ? 'טלפון (רשות)' : 'Telephone (optional)' ); ?></span>
				<input type="tel" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" />
			</label>
			<label class="c99-field-wide">
				<span><?php echo esc_html( $is_he ? 'מה נדרש?' : 'What do you need?' ); ?></span>
				<textarea name="message" rows="5" maxlength="3000" required></textarea>
			</label>
			<label class="c99-consent c99-field-wide">
				<input type="checkbox" name="consent" value="1" required />
				<span><?php echo esc_html( $is_he ? 'אני מסכים/ה לשמירת הפרטים לצורך טיפול בפנייה. אין לכלול מידע רפואי, פרטי עובדים או מסמכים רגישים.' : 'I consent to storing these details to handle the enquiry. Do not include medical information, employee data or sensitive documents.' ); ?></span>
			</label>
			<div class="c99-field-wide">
				<button class="c99-button c99-button-primary" type="submit"><?php echo esc_html( $is_he ? 'שמירת הפנייה' : 'Store enquiry' ); ?></button>
				<p class="c99-form-note"><?php echo esc_html( $is_he ? 'הטופס נשמר באתר ואינו נשלח אוטומטית לשירות שיווק חיצוני.' : 'The form is stored on this site and is not automatically sent to an external marketing service.' ); ?></p>
			</div>
		</form>
		<?php
	}

	public static function handle() {
		$nonce = isset( $_POST['complete99_lead_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['complete99_lead_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'complete99_lead_form' ) ) {
			wp_die( esc_html__( 'The form expired. Please return and try again.', 'complete99-platform' ), 403 );
		}
		if ( ! empty( $_POST['website'] ) ) {
			self::redirect_back( false );
		}
		if ( ! isset( $_POST['consent'] ) || '1' !== (string) $_POST['consent'] ) {
			wp_die( esc_html__( 'Consent is required to store the enquiry.', 'complete99-platform' ), 400 );
		}

		$rate_key = self::rate_key();
		$count    = (int) get_transient( $rate_key );
		if ( $count >= 5 ) {
			wp_die( esc_html__( 'Too many submissions. Please wait before trying again.', 'complete99-platform' ), 429 );
		}
		set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

		$contact_name = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
		$organisation = isset( $_POST['organisation'] ) ? sanitize_text_field( wp_unslash( $_POST['organisation'] ) ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$language     = isset( $_POST['language'] ) && 'en' === sanitize_key( wp_unslash( $_POST['language'] ) ) ? 'en' : 'he';
		$interest     = isset( $_POST['interest'] ) ? sanitize_key( wp_unslash( $_POST['interest'] ) ) : 'general';

		if ( '' === $contact_name || '' === $organisation || ! is_email( $email ) || '' === $message ) {
			wp_die( esc_html__( 'Please complete all required fields.', 'complete99-platform' ), 400 );
		}

		$lead_id = wp_insert_post(
			array(
				'post_type'   => 'c99_lead',
				'post_status' => 'private',
				'post_title'  => sprintf( 'C99-%s-%s', gmdate( 'Ymd-His' ), wp_generate_password( 6, false, false ) ),
			),
			true
		);
		if ( is_wp_error( $lead_id ) ) {
			wp_die( esc_html__( 'The enquiry could not be stored. Please try again later.', 'complete99-platform' ), 500 );
		}

		$fields = array(
			'_c99_contact_name' => $contact_name,
			'_c99_organisation' => $organisation,
			'_c99_email'        => $email,
			'_c99_phone'        => $phone,
			'_c99_message'      => $message,
			'_c99_language'     => $language,
			'_c99_interest'     => $interest,
			'_c99_consent_at'   => gmdate( 'c' ),
			'_c99_source_url'   => self::safe_source_url(),
		);
		foreach ( $fields as $key => $value ) {
			update_post_meta( $lead_id, $key, $value );
		}
		self::redirect_back( true );
	}

	private static function rate_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		return 'c99_lead_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	}

	private static function safe_source_url() {
		$referrer = wp_get_referer();
		if ( ! $referrer || wp_parse_url( $referrer, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return '';
		}
		return esc_url_raw( $referrer );
	}

	private static function redirect_back( $success ) {
		$url = self::safe_source_url();
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		if ( $success ) {
			$url = add_query_arg( 'c99_sent', '1', $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	public static function columns( $columns ) {
		return array(
			'cb'           => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
			'title'        => __( 'Reference', 'complete99-platform' ),
			'organisation' => __( 'Organisation', 'complete99-platform' ),
			'contact'      => __( 'Contact', 'complete99-platform' ),
			'interest'     => __( 'Interest', 'complete99-platform' ),
			'date'         => __( 'Received', 'complete99-platform' ),
		);
	}

	public static function column_value( $column, $post_id ) {
		if ( 'organisation' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_organisation', true ) );
		} elseif ( 'contact' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_contact_name', true ) );
			$email = (string) get_post_meta( $post_id, '_c99_email', true );
			if ( $email ) {
				echo '<br><a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
			}
		} elseif ( 'interest' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_interest', true ) );
		}
	}
}
