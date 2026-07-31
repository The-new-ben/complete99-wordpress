<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_Leads {
	const ACTION = 'complete99_submit_lead';
	const GROUP_ORDER_INTEREST = 'group-order';
	const GROUP_SIZE_MIN = 2;
	const GROUP_SIZE_MAX = 5000;
	const BUDGET_PER_PERSON_MIN = 1;
	const BUDGET_PER_PERSON_MAX = 10000;
	const REQUESTED_DATE_MAX_DAYS = 365;

	public static function boot() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_filter( 'manage_c99_lead_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_c99_lead_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );
		add_action( 'add_meta_boxes_c99_lead', array( __CLASS__, 'register_detail_meta_box' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_dashboard_widget' ) );
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
				'supports'            => array(),
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
		$is_he          = 'he' === $language;
		$interest       = sanitize_key( (string) $interest );
		$is_group_order = self::is_group_order_interest( $interest );
		$date_bounds    = self::requested_date_bounds();
		$sent           = isset( $_GET['c99_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['c99_sent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $sent ) {
			echo '<div class="c99-form-success" role="status">';
			echo esc_html( $is_he ? 'קיבלנו את הבקשה. נחזור אליכם כדי לבדוק את הפרטים ואת האפשרויות למועד שביקשתם.' : 'We received your request. We will contact you to review the details and the options for your requested date.' );
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
			<?php if ( $is_group_order ) : ?>
				<div class="c99-field-wide">
					<h2><?php echo esc_html( $is_he ? 'פרטי הארוחה הקבוצתית' : 'Group meal details' ); ?></h2>
					<p><?php echo esc_html( $is_he ? 'הפרטים עוזרים לנו לבדוק תפריט, כמויות, אריזה ואופן מסירה לפני שחוזרים אליכם.' : 'These details help us check menu, quantities, packaging and fulfilment before we contact you.' ); ?></p>
				</div>
				<label>
					<span><?php echo esc_html( $is_he ? 'מספר סועדים' : 'Number of diners' ); ?></span>
					<input type="number" name="group_size" min="<?php echo esc_attr( self::GROUP_SIZE_MIN ); ?>" max="<?php echo esc_attr( self::GROUP_SIZE_MAX ); ?>" step="1" required inputmode="numeric" />
				</label>
				<label>
					<span><?php echo esc_html( $is_he ? 'תאריך רצוי' : 'Requested date' ); ?></span>
					<input type="date" name="requested_date" min="<?php echo esc_attr( $date_bounds['minimum'] ); ?>" max="<?php echo esc_attr( $date_bounds['maximum'] ); ?>" required />
				</label>
				<label>
					<span><?php echo esc_html( $is_he ? 'חלון הארוחה' : 'Meal window' ); ?></span>
					<select name="service_window" required>
						<option value="" selected disabled><?php echo esc_html( $is_he ? 'בחרו זמן' : 'Choose a time' ); ?></option>
						<?php foreach ( self::group_order_options()['service_window'] as $value => $labels ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $labels[ $is_he ? 'he' : 'en' ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( $is_he ? 'איסוף או משלוח' : 'Pickup or delivery' ); ?></span>
					<select name="fulfilment" required>
						<option value="" selected disabled><?php echo esc_html( $is_he ? 'בחרו אפשרות' : 'Choose an option' ); ?></option>
						<?php foreach ( self::group_order_options()['fulfilment'] as $value => $labels ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $labels[ $is_he ? 'he' : 'en' ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( $is_he ? 'העדפת אריזה' : 'Packaging preference' ); ?></span>
					<select name="packaging" required>
						<option value="" selected disabled><?php echo esc_html( $is_he ? 'בחרו אפשרות' : 'Choose an option' ); ?></option>
						<?php foreach ( self::group_order_options()['packaging'] as $value => $labels ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $labels[ $is_he ? 'he' : 'en' ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( $is_he ? 'תקציב משוער לאדם בש״ח (רשות)' : 'Estimated budget per person in ILS (optional)' ); ?></span>
					<input type="number" name="budget_per_person" min="<?php echo esc_attr( self::BUDGET_PER_PERSON_MIN ); ?>" max="<?php echo esc_attr( self::BUDGET_PER_PERSON_MAX ); ?>" step="0.01" inputmode="decimal" />
				</label>
				<label class="c99-field-wide">
					<span><?php echo esc_html( $is_he ? 'סיכום העדפות של הקבוצה (רשות)' : 'Aggregate group preferences (optional)' ); ?></span>
					<textarea name="preference_summary" rows="4" maxlength="1000" aria-describedby="c99-preference-summary-note"></textarea>
					<small id="c99-preference-summary-note"><?php echo esc_html( $is_he ? 'כתבו כמויות כלליות בלבד, למשל 8 מנות צמחוניות. אין לכתוב שמות, אבחנות רפואיות או מידע אישי על סועדים.' : 'Use aggregate quantities only, for example 8 vegetarian meals. Do not include names, medical diagnoses or personal information about diners.' ); ?></small>
				</label>
			<?php endif; ?>
			<label class="c99-field-wide">
				<span><?php echo esc_html( $is_he ? 'מה עוד חשוב שנדע?' : 'What else should we know?' ); ?></span>
				<textarea name="message" rows="5" maxlength="3000" required></textarea>
			</label>
			<label class="c99-consent c99-field-wide">
				<input type="checkbox" name="consent" value="1" required />
				<span><?php echo esc_html( $is_he ? 'אני מסכים/ה לשמירת הפרטים כדי שיחזרו אליי בקשר לבקשה. אין לכלול מידע רפואי אישי, פרטי סועדים או עובדים, פרטי תשלום או מסמכים רגישים.' : 'I consent to storing these details so I can be contacted about the request. Do not include personal health information, diner or employee records, payment details or sensitive documents.' ); ?></span>
			</label>
			<div class="c99-field-wide">
				<button class="c99-button c99-button-primary" type="submit"><?php echo esc_html( $is_he ? 'שליחת בקשה' : 'Send request' ); ?></button>
				<p class="c99-form-note"><?php echo esc_html( $is_he ? 'הפרטים נשמרים באתר לצורך חזרה אליכם בקשר לבקשה בלבד.' : 'The details are stored on this site so we can contact you about this request.' ); ?></p>
			</div>
		</form>
		<?php
	}

	public static function handle() {
		$nonce = isset( $_POST['complete99_lead_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['complete99_lead_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'complete99_lead_form' ) ) {
			wp_die( esc_html__( 'The form expired. Please return and try again.', 'complete99-platform' ), '', array( 'response' => 403 ) );
		}
		if ( ! empty( $_POST['website'] ) ) {
			self::redirect_back( false );
		}
		if ( ! isset( $_POST['consent'] ) || '1' !== (string) $_POST['consent'] ) {
			wp_die( esc_html__( 'Consent is required to store the enquiry.', 'complete99-platform' ), '', array( 'response' => 400 ) );
		}

		$rate_key = self::rate_key();
		$count    = (int) get_transient( $rate_key );
		if ( $count >= 5 ) {
			wp_die( esc_html__( 'Too many submissions. Please wait before trying again.', 'complete99-platform' ), '', array( 'response' => 429 ) );
		}
		set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

		$contact_name = isset( $_POST['contact_name'] ) ? self::limit_text( sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ), 120 ) : '';
		$organisation = isset( $_POST['organisation'] ) ? self::limit_text( sanitize_text_field( wp_unslash( $_POST['organisation'] ) ), 160 ) : '';
		$email        = isset( $_POST['email'] ) ? self::limit_text( sanitize_email( wp_unslash( $_POST['email'] ) ), 190 ) : '';
		$phone        = isset( $_POST['phone'] ) ? self::limit_text( sanitize_text_field( wp_unslash( $_POST['phone'] ) ), 40 ) : '';
		$message      = isset( $_POST['message'] ) ? self::limit_text( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ), 3000 ) : '';
		$language     = isset( $_POST['language'] ) && 'en' === sanitize_key( wp_unslash( $_POST['language'] ) ) ? 'en' : 'he';
		$interest     = isset( $_POST['interest'] ) ? self::limit_text( sanitize_key( wp_unslash( $_POST['interest'] ) ), 80 ) : 'general';
		$interest     = '' !== $interest ? $interest : 'general';
		$is_group_order = self::is_group_order_interest( $interest );
		$group_size      = 0;
		$requested_date  = '';
		$service_window  = '';
		$fulfilment      = '';
		$packaging       = '';
		$budget          = '';
		$preference_summary = '';

		if ( $is_group_order ) {
			$group_size = self::sanitize_group_size( isset( $_POST['group_size'] ) ? wp_unslash( $_POST['group_size'] ) : '' );
			$requested_date = self::sanitize_requested_date( isset( $_POST['requested_date'] ) ? wp_unslash( $_POST['requested_date'] ) : '' );
			$service_window = self::sanitize_group_order_option(
				'service_window',
				isset( $_POST['service_window'] ) ? wp_unslash( $_POST['service_window'] ) : ''
			);
			$fulfilment = self::sanitize_group_order_option(
				'fulfilment',
				isset( $_POST['fulfilment'] ) ? wp_unslash( $_POST['fulfilment'] ) : ''
			);
			$packaging = self::sanitize_group_order_option(
				'packaging',
				isset( $_POST['packaging'] ) ? wp_unslash( $_POST['packaging'] ) : ''
			);
			$budget = self::sanitize_budget_per_person( isset( $_POST['budget_per_person'] ) ? wp_unslash( $_POST['budget_per_person'] ) : '' );
			$preference_summary = isset( $_POST['preference_summary'] )
				? self::limit_text( sanitize_textarea_field( wp_unslash( $_POST['preference_summary'] ) ), 1000 )
				: '';

			if ( 0 === $group_size
				|| '' === $requested_date
				|| '' === $service_window
				|| '' === $fulfilment
				|| '' === $packaging
				|| false === $budget ) {
				$message_text = 'he' === $language
					? 'פרטי הבקשה הקבוצתית אינם תקינים. בדקו את מספר הסועדים, התאריך והאפשרויות שבחרתם.'
					: 'The group request details are invalid. Check the number of diners, date and selected options.';
				wp_die( esc_html( $message_text ), '', array( 'response' => 400 ) );
			}
		}

		if ( '' === $contact_name || '' === $organisation || ! is_email( $email ) || '' === $message ) {
			wp_die( esc_html__( 'Please complete all required fields.', 'complete99-platform' ), '', array( 'response' => 400 ) );
		}

		$lead_id = wp_insert_post(
			array(
				'post_type'   => 'c99_lead',
				'post_status' => 'private',
				'post_title'  => sprintf( 'C99-%s-%s', gmdate( 'Ymd-His' ), wp_generate_password( 6, false, false ) ),
			),
			true
		);
		if ( is_wp_error( $lead_id ) || 0 >= (int) $lead_id ) {
			wp_die( esc_html__( 'The enquiry could not be stored. Please try again later.', 'complete99-platform' ), '', array( 'response' => 500 ) );
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
		if ( $is_group_order ) {
			$fields['_c99_group_size']            = (string) $group_size;
			$fields['_c99_requested_date']         = $requested_date;
			$fields['_c99_service_window']         = $service_window;
			$fields['_c99_fulfilment']             = $fulfilment;
			$fields['_c99_packaging']               = $packaging;
			$fields['_c99_budget_per_person']       = $budget;
			$fields['_c99_preference_summary']      = $preference_summary;
		}
		if ( ! self::store_and_verify_fields( $lead_id, $fields ) ) {
			self::discard_failed_lead( $lead_id, array_keys( $fields ) );
			wp_die( esc_html__( 'The enquiry could not be stored. Please try again later.', 'complete99-platform' ), '', array( 'response' => 500 ) );
		}
		self::redirect_back( true );
	}

	private static function limit_text( $value, $maximum ) {
		$value   = (string) $value;
		$maximum = max( 0, (int) $maximum );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $maximum, 'UTF-8' );
		}
		$characters = preg_split( '//u', $value, -1, PREG_SPLIT_NO_EMPTY );
		if ( is_array( $characters ) ) {
			return implode( '', array_slice( $characters, 0, $maximum ) );
		}
		return substr( $value, 0, $maximum );
	}

	private static function text_length( $value ) {
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( (string) $value, 'UTF-8' );
		}
		$matched = preg_match_all( '/./us', (string) $value, $characters );
		return false === $matched ? strlen( (string) $value ) : $matched;
	}

	private static function is_group_order_interest( $interest ) {
		return in_array(
			sanitize_key( (string) $interest ),
			array( self::GROUP_ORDER_INTEREST, 'group-meals', 'workplace-meals' ),
			true
		);
	}

	private static function group_order_options() {
		return array(
			'service_window' => array(
				'breakfast' => array( 'he' => 'בוקר', 'en' => 'Breakfast' ),
				'lunch'     => array( 'he' => 'צהריים', 'en' => 'Lunch' ),
				'afternoon' => array( 'he' => 'אחר הצהריים', 'en' => 'Afternoon' ),
				'evening'   => array( 'he' => 'ערב', 'en' => 'Evening' ),
				'flexible'  => array( 'he' => 'גמיש', 'en' => 'Flexible' ),
			),
			'fulfilment' => array(
				'pickup'   => array( 'he' => 'איסוף מאבן גבירול 99', 'en' => 'Pickup from 99 Ibn Gabirol' ),
				'delivery' => array( 'he' => 'משלוח לכתובת אחת', 'en' => 'Delivery to one address' ),
				'either'   => array( 'he' => 'אפשר לבדוק את שתי האפשרויות', 'en' => 'Either option can be considered' ),
			),
			'packaging' => array(
				'shared'     => array( 'he' => 'מגשים וכלים משותפים', 'en' => 'Shared trays and containers' ),
				'individual' => array( 'he' => 'מנות אישיות', 'en' => 'Individual meals' ),
				'mixed'      => array( 'he' => 'שילוב של משותף ואישי', 'en' => 'A mixture of shared and individual' ),
				'discuss'    => array( 'he' => 'נחליט יחד', 'en' => 'Decide together' ),
			),
		);
	}

	private static function sanitize_group_size( $value ) {
		$value = filter_var(
			trim( (string) $value ),
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => self::GROUP_SIZE_MIN,
					'max_range' => self::GROUP_SIZE_MAX,
				),
			)
		);
		return false === $value ? 0 : (int) $value;
	}

	private static function requested_date_bounds() {
		$today = current_datetime()->setTime( 0, 0, 0 );
		return array(
			'minimum' => $today->format( 'Y-m-d' ),
			'maximum' => $today->modify( '+' . self::REQUESTED_DATE_MAX_DAYS . ' days' )->format( 'Y-m-d' ),
		);
	}

	private static function sanitize_requested_date( $value ) {
		$value  = trim( sanitize_text_field( (string) $value ) );
		$bounds = self::requested_date_bounds();
		if ( 1 !== preg_match( '/\A\d{4}-\d{2}-\d{2}\z/', $value ) ) {
			return '';
		}
		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $date
			|| ( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) )
			|| $date->format( 'Y-m-d' ) !== $value
			|| $value < $bounds['minimum']
			|| $value > $bounds['maximum'] ) {
			return '';
		}
		return $value;
	}

	private static function sanitize_group_order_option( $field, $value ) {
		$field   = sanitize_key( (string) $field );
		$value   = sanitize_key( (string) $value );
		$options = self::group_order_options();
		return isset( $options[ $field ][ $value ] ) ? $value : '';
	}

	private static function sanitize_budget_per_person( $value ) {
		$value = trim( str_replace( ',', '.', (string) $value ) );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 !== preg_match( '/\A\d+(?:\.\d{1,2})?\z/', $value ) || ! is_numeric( $value ) ) {
			return false;
		}
		$number = (float) $value;
		if ( $number < self::BUDGET_PER_PERSON_MIN || $number > self::BUDGET_PER_PERSON_MAX ) {
			return false;
		}
		return number_format( $number, 2, '.', '' );
	}

	private static function group_order_option_label( $field, $value, $language ) {
		$field   = sanitize_key( (string) $field );
		$value   = sanitize_key( (string) $value );
		$language = 'he' === $language ? 'he' : 'en';
		$options = self::group_order_options();
		return isset( $options[ $field ][ $value ][ $language ] )
			? (string) $options[ $field ][ $value ][ $language ]
			: '';
	}

	private static function store_and_verify_fields( $lead_id, $fields ) {
		foreach ( $fields as $key => $value ) {
			$updated = update_post_meta( $lead_id, $key, wp_slash( $value ) );
			if (
				false === $updated
				&& (
					! metadata_exists( 'post', $lead_id, $key )
					|| (string) get_post_meta( $lead_id, $key, true ) !== (string) $value
				)
			) {
				return false;
			}
		}

		/*
		 * Evict the metadata cache so the success decision comes from a fresh
		 * database read, not from the values just handed to update_post_meta().
		 */
		wp_cache_delete( $lead_id, 'post_meta' );
		foreach ( $fields as $key => $value ) {
			if (
				! metadata_exists( 'post', $lead_id, $key )
				|| (string) get_post_meta( $lead_id, $key, true ) !== (string) $value
			) {
				return false;
			}
		}
		return true;
	}

	private static function discard_failed_lead( $lead_id, $field_keys ) {
		$deleted = wp_delete_post( $lead_id, true );
		if ( $deleted ) {
			return;
		}

		/*
		 * A failed post deletion must not leave a partially written PII record
		 * visible to operators. Remove every known field and retain, at worst,
		 * a private reference-only shell for host-level diagnosis.
		 */
		foreach ( $field_keys as $key ) {
			delete_post_meta( $lead_id, $key );
		}
		wp_cache_delete( $lead_id, 'post_meta' );
		wp_update_post(
			array(
				'ID'          => $lead_id,
				'post_status' => 'private',
				'post_title'  => 'C99-STORAGE-FAILED-' . (int) $lead_id,
			)
		);
	}

	private static function rate_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		return 'c99_lead_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	}

	private static function safe_source_url() {
		return self::normalise_source_url( wp_get_referer() );
	}

	private static function normalise_source_url( $url ) {
		$url = (string) $url;
		if ( '' === $url || strlen( $url ) > 2048 ) {
			return '';
		}
		$source = wp_parse_url( $url );
		$home   = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $source ) || ! is_array( $home ) ) {
			return '';
		}
		$source_scheme = strtolower( (string) ( $source['scheme'] ?? '' ) );
		$home_scheme   = strtolower( (string) ( $home['scheme'] ?? '' ) );
		$source_host   = strtolower( (string) ( $source['host'] ?? '' ) );
		$home_host     = strtolower( (string) ( $home['host'] ?? '' ) );
		$source_port   = isset( $source['port'] ) ? (int) $source['port'] : ( 'https' === $source_scheme ? 443 : 80 );
		$home_port     = isset( $home['port'] ) ? (int) $home['port'] : ( 'https' === $home_scheme ? 443 : 80 );
		$path          = (string) ( $source['path'] ?? '/' );
		if (
			! in_array( $source_scheme, array( 'http', 'https' ), true )
			|| $source_scheme !== $home_scheme
			|| '' === $source_host
			|| $source_host !== $home_host
			|| $source_port !== $home_port
			|| isset( $source['user'] )
			|| isset( $source['pass'] )
			|| '' === $path
			|| '/' !== $path[0]
			|| strlen( $path ) > 1024
		) {
			return '';
		}
		$origin = $source_scheme . '://' . $source_host;
		$default_port = 'https' === $source_scheme ? 443 : 80;
		if ( $source_port !== $default_port ) {
			$origin .= ':' . $source_port;
		}
		return esc_url_raw( $origin . $path );
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

	private static function can_view_leads() {
		return current_user_can( 'edit_c99_leads' ) && current_user_can( 'read_private_c99_leads' );
	}

	public static function register_detail_meta_box( $post ) {
		if (
			! $post instanceof WP_Post
			|| 'c99_lead' !== $post->post_type
			|| ! current_user_can( 'read_post', $post->ID )
		) {
			return;
		}
		remove_meta_box( 'submitdiv', 'c99_lead', 'side' );
		remove_meta_box( 'slugdiv', 'c99_lead', 'normal' );
		add_meta_box(
			'complete99-lead-details',
			__( 'Enquiry details', 'complete99-platform' ),
			array( __CLASS__, 'render_detail_meta_box' ),
			'c99_lead',
			'normal',
			'high'
		);
	}

	public static function render_detail_meta_box( $post ) {
		if (
			! $post instanceof WP_Post
			|| 'c99_lead' !== $post->post_type
			|| ! current_user_can( 'read_post', $post->ID )
		) {
			wp_die( esc_html__( 'You are not allowed to view this enquiry.', 'complete99-platform' ), '', array( 'response' => 403 ) );
		}

		$contact           = (string) get_post_meta( $post->ID, '_c99_contact_name', true );
		$organisation      = (string) get_post_meta( $post->ID, '_c99_organisation', true );
		$email             = sanitize_email( (string) get_post_meta( $post->ID, '_c99_email', true ) );
		$phone             = (string) get_post_meta( $post->ID, '_c99_phone', true );
		$message           = (string) get_post_meta( $post->ID, '_c99_message', true );
		$interest          = (string) get_post_meta( $post->ID, '_c99_interest', true );
		$language_code     = 'en' === (string) get_post_meta( $post->ID, '_c99_language', true ) ? 'en' : 'he';
		$consent_at        = self::format_consent_time( (string) get_post_meta( $post->ID, '_c99_consent_at', true ) );
		$source_url        = self::normalise_source_url( (string) get_post_meta( $post->ID, '_c99_source_url', true ) );
		$language          = 'en' === $language_code ? __( 'English', 'complete99-platform' ) : __( 'Hebrew', 'complete99-platform' );
		$is_group_order    = self::is_group_order_interest( $interest );
		$group_size        = (string) get_post_meta( $post->ID, '_c99_group_size', true );
		$requested_date    = (string) get_post_meta( $post->ID, '_c99_requested_date', true );
		$service_window    = self::group_order_option_label( 'service_window', (string) get_post_meta( $post->ID, '_c99_service_window', true ), $language_code );
		$fulfilment        = self::group_order_option_label( 'fulfilment', (string) get_post_meta( $post->ID, '_c99_fulfilment', true ), $language_code );
		$packaging         = self::group_order_option_label( 'packaging', (string) get_post_meta( $post->ID, '_c99_packaging', true ), $language_code );
		$budget            = (string) get_post_meta( $post->ID, '_c99_budget_per_person', true );
		$preference_summary = (string) get_post_meta( $post->ID, '_c99_preference_summary', true );
		?>
		<p><strong><?php echo esc_html__( 'Read-only record.', 'complete99-platform' ); ?></strong>
			<?php echo esc_html__( 'Use the reference when recording follow-up in the authorised operating process.', 'complete99-platform' ); ?></p>
		<table class="widefat striped c99-lead-details">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Contact name', 'complete99-platform' ); ?></th>
					<td><?php echo esc_html( $contact ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Organisation', 'complete99-platform' ); ?></th>
					<td><?php echo esc_html( $organisation ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Email', 'complete99-platform' ); ?></th>
					<td>
						<?php if ( $email && is_email( $email ) ) : ?>
							<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
						<?php else : ?>
							<?php echo esc_html__( 'Not provided', 'complete99-platform' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Telephone', 'complete99-platform' ); ?></th>
					<td><?php echo esc_html( '' !== $phone ? $phone : __( 'Not provided', 'complete99-platform' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Full request', 'complete99-platform' ); ?></th>
					<td><div class="c99-lead-full-request" style="white-space: pre-wrap;"><?php echo esc_html( $message ); ?></div></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Interest', 'complete99-platform' ); ?></th>
					<td><?php echo esc_html( $interest ); ?></td>
				</tr>
				<?php if ( $is_group_order ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Group size', 'complete99-platform' ); ?></th>
						<td><?php echo esc_html( $group_size ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Requested date', 'complete99-platform' ); ?></th>
						<td><?php echo esc_html( $requested_date ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Meal window', 'complete99-platform' ); ?></th>
						<td><?php echo esc_html( $service_window ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Fulfilment', 'complete99-platform' ); ?></th>
						<td><?php echo esc_html( $fulfilment ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Packaging', 'complete99-platform' ); ?></th>
						<td><?php echo esc_html( $packaging ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Estimated budget per person', 'complete99-platform' ); ?></th>
						<td><?php echo esc_html( '' !== $budget ? $budget . ' ILS' : __( 'Not provided', 'complete99-platform' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Aggregate group preferences', 'complete99-platform' ); ?></th>
						<td><div style="white-space: pre-wrap;"><?php echo esc_html( '' !== $preference_summary ? $preference_summary : __( 'Not provided', 'complete99-platform' ) ); ?></div></td>
					</tr>
				<?php endif; ?>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Language', 'complete99-platform' ); ?></th>
					<td><?php echo esc_html( $language ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Consent recorded', 'complete99-platform' ); ?></th>
					<td><?php echo esc_html( $consent_at ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Safe source', 'complete99-platform' ); ?></th>
					<td>
						<?php if ( $source_url ) : ?>
							<a href="<?php echo esc_url( $source_url ); ?>"><?php echo esc_html( $source_url ); ?></a>
						<?php else : ?>
							<?php echo esc_html__( 'Not available', 'complete99-platform' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function format_consent_time( $value ) {
		$timestamp = strtotime( (string) $value );
		if ( false === $timestamp ) {
			return (string) $value;
		}
		return wp_date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$timestamp
		);
	}

	private static function bounded_preview( $value, $maximum ) {
		$value = trim( (string) preg_replace( '/\s+/u', ' ', (string) $value ) );
		if ( '' === $value ) {
			return '';
		}
		$maximum = max( 4, (int) $maximum );
		$length  = self::text_length( $value );
		if ( $length <= $maximum ) {
			return $value;
		}
		return rtrim( self::limit_text( $value, $maximum - 1 ) ) . '…';
	}

	public static function register_dashboard_widget() {
		if ( ! self::can_view_leads() ) {
			return;
		}
		wp_add_dashboard_widget(
			'complete99-latest-enquiries',
			__( 'Latest enquiries', 'complete99-platform' ),
			array( __CLASS__, 'render_dashboard_widget' )
		);
	}

	public static function render_dashboard_widget() {
		if ( ! self::can_view_leads() ) {
			echo '<p>' . esc_html__( 'You are not allowed to view enquiries.', 'complete99-platform' ) . '</p>';
			return;
		}
		$leads = get_posts(
			array(
				'post_type'              => 'c99_lead',
				'post_status'            => 'private',
				'posts_per_page'         => 5,
				'orderby'                => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'no_found_rows'          => true,
				'perm'                   => 'readable',
				'suppress_filters'       => false,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);
		if ( empty( $leads ) ) {
			echo '<p>' . esc_html__( 'No enquiries have been stored yet.', 'complete99-platform' ) . '</p>';
			return;
		}
		echo '<ul class="c99-dashboard-enquiries">';
		foreach ( $leads as $lead ) {
			if ( ! $lead instanceof WP_Post || ! current_user_can( 'read_post', $lead->ID ) ) {
				continue;
			}
			$contact      = (string) get_post_meta( $lead->ID, '_c99_contact_name', true );
			$organisation = (string) get_post_meta( $lead->ID, '_c99_organisation', true );
			$preview      = self::bounded_preview( (string) get_post_meta( $lead->ID, '_c99_message', true ), 120 );
			$link         = get_edit_post_link( $lead->ID, '' );
			echo '<li>';
			if ( $link ) {
				echo '<a href="' . esc_url( $link ) . '"><strong>' . esc_html( $organisation ) . '</strong> - ' . esc_html( $contact ) . '</a>';
			} else {
				echo '<strong>' . esc_html( $organisation ) . '</strong> - ' . esc_html( $contact );
			}
			echo '<br><span>' . esc_html( $preview ) . '</span>';
			echo '<br><small>' . esc_html( get_the_date( '', $lead ) ) . '</small>';
			echo '</li>';
		}
		echo '</ul>';
		echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=c99_lead' ) ) . '">' . esc_html__( 'View all enquiries', 'complete99-platform' ) . '</a></p>';
	}

	public static function columns( $columns ) {
		return array(
			'cb'           => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
			'title'        => __( 'Reference', 'complete99-platform' ),
			'organisation' => __( 'Organisation', 'complete99-platform' ),
			'contact'      => __( 'Contact', 'complete99-platform' ),
			'phone'        => __( 'Telephone', 'complete99-platform' ),
			'request'      => __( 'Request preview', 'complete99-platform' ),
			'interest'     => __( 'Interest', 'complete99-platform' ),
			'group_order'  => __( 'Group and date', 'complete99-platform' ),
			'date'         => __( 'Received', 'complete99-platform' ),
		);
	}

	public static function column_value( $column, $post_id ) {
		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return;
		}
		if ( 'organisation' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_organisation', true ) );
		} elseif ( 'contact' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_contact_name', true ) );
			$email = (string) get_post_meta( $post_id, '_c99_email', true );
			if ( $email ) {
				echo '<br><a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
			}
		} elseif ( 'phone' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_phone', true ) );
		} elseif ( 'request' === $column ) {
			echo esc_html( self::bounded_preview( (string) get_post_meta( $post_id, '_c99_message', true ), 160 ) );
		} elseif ( 'interest' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_c99_interest', true ) );
		} elseif ( 'group_order' === $column ) {
			$interest = (string) get_post_meta( $post_id, '_c99_interest', true );
			if ( ! self::is_group_order_interest( $interest ) ) {
				echo esc_html( '-' );
				return;
			}
			$group_size     = (string) get_post_meta( $post_id, '_c99_group_size', true );
			$requested_date = (string) get_post_meta( $post_id, '_c99_requested_date', true );
			echo esc_html( sprintf( '%s people', $group_size ) );
			echo '<br><span>' . esc_html( $requested_date ) . '</span>';
		}
	}
}
