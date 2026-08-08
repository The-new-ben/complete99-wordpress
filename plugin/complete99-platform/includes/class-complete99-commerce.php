<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commerce infrastructure and private operational handoff.
 *
 * WooCommerce remains the transaction engine. This class never invents a
 * checkout, product, price, stock state or worker assignment. It provides:
 *
 * - private product-development records;
 * - explicit live-commerce readiness checks;
 * - a public, non-sensitive store status;
 * - a private order and inventory event outbox for Complete99 OS.
 */
final class Complete99_Commerce {
	const NAMESPACE          = 'complete99/v1';
	const OPTION_ENABLED     = 'complete99_commerce_enabled';
	const OPTION_OUTBOX      = 'complete99_commerce_outbox';
	const OPTION_OUTBOX_ERROR= 'complete99_commerce_outbox_error';
	const OPTION_OUTBOX_ERROR_PREFIX = 'complete99_commerce_outbox_error_';
	const OPTION_OUTBOX_FAILURES = 'complete99_commerce_outbox_failures';
	const OPTION_OUTBOX_FAILURE_EVENT_PREFIX = 'complete99_commerce_outbox_failure_event_';
	const OPTION_OUTBOX_AUDIT = 'complete99_commerce_outbox_ack_audit';
	const OUTBOX_SCHEMA       = 'complete99-commerce-outbox/v2';
	const OUTBOX_ACK_SCHEMA   = 'complete99-commerce-outbox-ack/v2';
	const OUTBOX_FAILURE_SCHEMA = 'complete99-commerce-outbox-failure/v2';
	const OUTBOX_ID_VERSION   = 3;
	const OPTION_ACCEPTANCE  = 'complete99_commerce_acceptance_receipt';
	const OPTION_LEGAL       = 'complete99_commerce_legal_receipt';
	const OPTION_PREVIEW     = 'complete99_commerce_acceptance_preview';
	const OPTION_EVER_LAUNCHED = 'complete99_commerce_ever_launched';
	const OPTION_LAUNCH_AUDIT = 'complete99_commerce_launch_audit';
	const PRODUCT_APPROVED   = '_complete99_store_approved';
	const PRODUCT_KIND       = '_complete99_product_kind';
	const STOCK_AUTHORITY    = '_complete99_stock_authority';
	const NAME_HE            = '_complete99_product_name_he';
	const NAME_EN            = '_complete99_product_name_en';
	const DESCRIPTION_HE     = '_complete99_product_description_he';
	const DESCRIPTION_EN     = '_complete99_product_description_en';
	const INGREDIENTS_HE     = '_complete99_product_ingredients_he';
	const INGREDIENTS_EN     = '_complete99_product_ingredients_en';
	const ALLERGENS_HE       = '_complete99_product_allergens_he';
	const ALLERGENS_EN       = '_complete99_product_allergens_en';
	const STORAGE_HE         = '_complete99_product_storage_he';
	const STORAGE_EN         = '_complete99_product_storage_en';
	const FULFILMENT_HE      = '_complete99_product_fulfilment_he';
	const FULFILMENT_EN      = '_complete99_product_fulfilment_en';
	const ORIGIN_HE          = '_complete99_product_origin_he';
	const ORIGIN_EN          = '_complete99_product_origin_en';
	const MODEL_HE           = '_complete99_product_model_he';
	const MODEL_EN           = '_complete99_product_model_en';
	const MATERIAL_HE        = '_complete99_product_material_he';
	const MATERIAL_EN        = '_complete99_product_material_en';
	const DIMENSIONS_HE      = '_complete99_product_dimensions_he';
	const DIMENSIONS_EN      = '_complete99_product_dimensions_en';
	const CARE_HE            = '_complete99_product_care_he';
	const CARE_EN            = '_complete99_product_care_en';
	const SAFETY_HE          = '_complete99_product_safety_he';
	const SAFETY_EN          = '_complete99_product_safety_en';
	const LABEL_REVIEWED     = '_complete99_product_label_reviewed';
	const ORIGIN_REVIEWED    = '_complete99_product_origin_reviewed';
	const CHECKOUT_ELIGIBLE  = '_complete99_product_checkout_eligible';
	const RIGHTS_REVIEWED    = '_complete99_product_rights_reviewed';
	const TAX_REVIEWED       = '_complete99_product_tax_reviewed';
	const MEDIA_PUBLIC_SAFE  = '_complete99_media_public_safe';
	const ORDER_LANGUAGE     = '_complete99_order_language';
	const ORDER_RECEIVED_SEEN= '_complete99_order_received_seen';
	const ORDER_EMAIL_SENT   = '_complete99_customer_order_email_sent';
	const ORDER_STOCK_RECEIPT= '_complete99_order_stock_reduction_receipt';
	const ORDER_FULFILMENT_RECEIPT = '_complete99_order_fulfilment_receipt';
	const ORDER_GATEWAY_RECEIPT = '_complete99_order_gateway_receipt';
	const ITEM_NAME_HE       = '_complete99_item_name_he';
	const ITEM_NAME_EN       = '_complete99_item_name_en';
	const CUSTOMER_LANGUAGE  = 'complete99_customer_language';
	const MAX_OUTBOX_EVENTS  = 500;
	const MAX_OUTBOX_FAILURES= 500;
	const MAX_OUTBOX_AUDIT   = 5000;
	const ACCEPTANCE_MAX_AGE = 2592000;
	const ACCEPTANCE_EVIDENCE_MAX_AGE = 86400;
	const LEGAL_MAX_AGE      = 31536000;
	const VERIFIED_ORDER_HE  = 'https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete';
	const VERIFIED_ORDER_EN  = 'https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete';
	const STOREFRONT_PAGE_SIZE        = 12;
	const STOREFRONT_LEGACY_MAP_LIMIT = 100;

	private static $evaluating_readiness = false;
	private static $email_locale_switches = array();
	private static $pending_customer_email_languages = array();
	private static $pending_customer_email_content = array();
	private static $refund_delete_context = array();
	private static $stock_reduction_lines = array();
	private static $product_configuration_dirty = false;
	private static $emergency_outbox_readback_failed = false;
	private static $outbox_corruption_detected = false;
	private static $readiness_cache = null;
	private static $approved_products_cache = null;

	private static function invalidate_commerce_state_cache() {
		self::$readiness_cache         = null;
		self::$approved_products_cache = null;
	}

	private static function mark_public_commerce_state_dirty() {
		self::invalidate_commerce_state_cache();
		self::$product_configuration_dirty = true;
	}

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register_product_planning_type' ), 7 );
		add_action( 'wp_loaded', array( __CLASS__, 'remember_transaction_language' ), 1 );
		add_action( 'shutdown', array( __CLASS__, 'restore_stranded_email_locales' ), 0 );
		add_action( 'shutdown', array( __CLASS__, 'flush_product_configuration_caches' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'woocommerce_product_options_inventory_product_data', array( __CLASS__, 'render_product_readiness_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_readiness_fields' ) );
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'mark_product_cache_dirty' ), 20, 2 );
		add_action( 'updated_option', array( __CLASS__, 'handle_commerce_option_change' ), 20, 3 );
		add_action( 'added_option', array( __CLASS__, 'handle_commerce_option_change' ), 20, 2 );
		add_action( 'deleted_option', array( __CLASS__, 'handle_commerce_option_change' ), 20, 1 );
		add_action( 'wp_after_insert_post', array( __CLASS__, 'handle_commerce_page_change' ), 20, 4 );
		add_action( 'transition_post_status', array( __CLASS__, 'handle_commerce_page_status_change' ), 20, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'handle_commerce_post_delete' ), 20, 2 );
		add_action( 'edit_attachment', array( __CLASS__, 'handle_commerce_attachment_change' ), 20, 1 );
		add_action( 'delete_attachment', array( __CLASS__, 'handle_commerce_attachment_change' ), 1, 1 );
		add_filter( 'wp_update_attachment_metadata', array( __CLASS__, 'handle_commerce_attachment_metadata_change' ), 20, 2 );
		add_action( 'added_post_meta', array( __CLASS__, 'handle_commerce_post_meta_change' ), 20, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'handle_commerce_post_meta_change' ), 20, 4 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'handle_commerce_post_meta_change' ), 20, 4 );
		add_action( 'set_object_terms', array( __CLASS__, 'handle_commerce_product_terms_change' ), 20, 6 );
		add_action( 'woocommerce_feature_enabled_changed', array( __CLASS__, 'handle_commerce_feature_change' ), 20, 2 );
		foreach (
			array(
				'woocommerce_after_shipping_zone_object_save',
				'woocommerce_delete_shipping_zone',
				'woocommerce_shipping_zone_method_added',
				'woocommerce_shipping_zone_method_deleted',
				'woocommerce_delete_shipping_zone_method',
				'woocommerce_shipping_zone_method_status_toggled',
				'woocommerce_tax_rate_added',
				'woocommerce_tax_rate_updated',
				'woocommerce_tax_rate_deleted',
				'woocommerce_update_options_shipping',
				'woocommerce_update_options_tax',
				'woocommerce_rest_insert_tax',
				'woocommerce_rest_insert_tax_class',
				'woocommerce_rest_delete_tax',
			) as $commerce_configuration_hook
		) {
			add_action( $commerce_configuration_hook, array( __CLASS__, 'mark_material_commerce_configuration_dirty' ), 20, 4 );
		}
		add_action( 'woocommerce_after_order_object_save', array( __CLASS__, 'capture_order_snapshot' ), 20, 2 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'capture_order_status' ), 20, 4 );
		add_action( 'woocommerce_refund_created', array( __CLASS__, 'capture_refund_created' ), 20, 2 );
		add_action( 'woocommerce_refund_deleted', array( __CLASS__, 'capture_refund_deleted' ), 20, 2 );
		add_action( 'woocommerce_delete_order_refund', array( __CLASS__, 'capture_refund_deleted' ), 20, 2 );
		add_action( 'woocommerce_update_order_refund', array( __CLASS__, 'capture_refund_updated' ), 20, 2 );
		add_filter( 'woocommerce_pre_delete_order_refund', array( __CLASS__, 'capture_refund_pre_delete' ), 1, 3 );
		add_action( 'woocommerce_before_delete_order', array( __CLASS__, 'capture_refund_before_delete' ), 1, 2 );
		add_action( 'before_delete_post', array( __CLASS__, 'capture_refund_before_post_delete' ), 1, 2 );
		add_action( 'woocommerce_fulfillment_after_create', array( __CLASS__, 'capture_fulfilment_change' ), 20, 3 );
		add_action( 'woocommerce_fulfillment_after_update', array( __CLASS__, 'capture_fulfilment_change' ), 20, 3 );
		add_action( 'woocommerce_fulfillment_after_fulfill', array( __CLASS__, 'capture_fulfilment_change' ), 20, 3 );
		add_action( 'woocommerce_fulfillment_after_delete', array( __CLASS__, 'capture_fulfilment_change' ), 20, 3 );
		add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'capture_product_stock' ), 20, 1 );
		add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'capture_product_stock' ), 20, 1 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'remember_checkout_order_language' ), 10, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'gate_classic_payment_gateways' ), PHP_INT_MAX, 1 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'guard_classic_checkout_validation' ), 1, 2 );
		add_filter( 'woocommerce_create_order', array( __CLASS__, 'guard_classic_order_creation' ), 1, 2 );
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'remember_customer_language' ), 1, 3 );
		add_action( 'woocommerce_created_customer_notification', array( __CLASS__, 'prepare_new_account_email_language' ), 1, 3 );
		add_action( 'woocommerce_reset_password_notification', array( __CLASS__, 'prepare_reset_password_email_language' ), 1, 2 );
		foreach (
			array(
				'woocommerce_order_status_cancelled_to_processing_notification',
				'woocommerce_order_status_failed_to_processing_notification',
				'woocommerce_order_status_on-hold_to_processing_notification',
				'woocommerce_order_status_pending_to_processing_notification',
				'woocommerce_order_status_completed_notification',
				'woocommerce_order_status_pending_to_on-hold_notification',
				'woocommerce_order_status_failed_to_on-hold_notification',
				'woocommerce_order_status_cancelled_to_on-hold_notification',
				'woocommerce_order_status_processing_to_cancelled_notification',
				'woocommerce_order_status_on-hold_to_cancelled_notification',
				'woocommerce_order_status_failed_notification',
				'woocommerce_order_fully_refunded_notification',
				'woocommerce_order_partially_refunded_notification',
				'woocommerce_send_review_request_notification',
				'woocommerce_new_customer_note_notification',
				'woocommerce_fulfillment_created_notification',
				'woocommerce_fulfillment_updated_notification',
				'woocommerce_fulfillment_deleted_notification',
				'woocommerce_before_resend_order_emails',
			) as $customer_email_hook
		) {
			add_action( $customer_email_hook, array( __CLASS__, 'prepare_customer_order_email_language' ), 1, 4 );
		}
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'localize_checkout_line_item' ), 10, 4 );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( __CLASS__, 'remember_store_api_order_language' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'record_order_received_seen' ), 10, 1 );
		add_action( 'woocommerce_email_sent', array( __CLASS__, 'record_customer_order_email_sent' ), 10, 3 );
		add_action( 'woocommerce_reduce_order_item_stock', array( __CLASS__, 'capture_order_item_stock_reduction' ), 20, 3 );
		add_action( 'woocommerce_reduce_order_stock', array( __CLASS__, 'record_order_stock_reduction' ), 20, 1 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'record_gateway_payment_receipt' ), 20, 2 );
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'gate_product_visibility' ), 999, 2 );
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'gate_product_purchasability' ), 999, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( __CLASS__, 'gate_product_purchasability' ), 999, 2 );
		add_filter( 'woocommerce_product_get_name', array( __CLASS__, 'localize_product_name' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_name', array( __CLASS__, 'localize_product_name' ), 20, 2 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'localize_cart_item_name' ), 20, 3 );
		add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'localize_order_item_name' ), 20, 3 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_bilingual_order_item_meta' ), 20, 1 );
		add_filter( 'woocommerce_get_cart_url', array( __CLASS__, 'retain_transaction_language_in_url' ), 20, 1 );
		add_filter( 'woocommerce_get_checkout_url', array( __CLASS__, 'retain_transaction_language_in_url' ), 20, 1 );
		add_filter( 'woocommerce_get_myaccount_page_permalink', array( __CLASS__, 'retain_transaction_language_in_url' ), 20, 1 );
		add_filter( 'woocommerce_get_checkout_payment_url', array( __CLASS__, 'retain_order_language_in_url' ), 20, 2 );
		add_filter( 'woocommerce_get_checkout_order_received_url', array( __CLASS__, 'retain_order_language_in_url' ), 20, 2 );
		add_filter( 'woocommerce_get_view_order_url', array( __CLASS__, 'retain_order_language_in_url' ), 20, 2 );
		add_filter( 'woocommerce_terms_and_conditions_page_id', array( __CLASS__, 'localized_terms_page_id' ), 20, 1 );
		add_filter( 'woocommerce_privacy_policy_page_id', array( __CLASS__, 'localized_privacy_page_id' ), 20, 1 );
		add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', array( __CLASS__, 'localized_terms_checkbox_text' ), 20, 1 );
		add_filter( 'woocommerce_get_privacy_policy_text', array( __CLASS__, 'localized_privacy_policy_text' ), 20, 2 );
		add_filter( 'woocommerce_mail_callback_params', array( __CLASS__, 'inspect_customer_email_content' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_allow_switching_email_locale', array( __CLASS__, 'switch_customer_email_locale' ), 20, 2 );
		add_filter( 'woocommerce_allow_restoring_email_locale', array( __CLASS__, 'restore_customer_email_locale' ), 20, 2 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'gate_store_api' ), 1, 3 );
		add_filter( 'rest_post_search_query', array( __CLASS__, 'exclude_products_from_rest_search' ), 20, 2 );
		add_filter( 'auto_update_plugin', array( __CLASS__, 'disable_woocommerce_auto_update' ), 999, 2 );
		add_filter( 'wp_robots', array( __CLASS__, 'noindex_native_woocommerce_routes' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'enforce_commerce_no_cache' ), -1 );
		add_action( 'template_redirect', array( __CLASS__, 'gate_public_woocommerce_routes' ), 0 );
		add_action( 'wp', array( __CLASS__, 'configure_catalog_cart_continuation' ), 20 );
		add_action( 'pre_get_posts', array( __CLASS__, 'exclude_products_from_public_search' ), 20 );
	}

	public static function register_product_planning_type() {
		register_post_type(
			'c99_product_plan',
			array(
				'labels' => array(
					'name'          => 'תכנון מוצרי חנות',
					'singular_name' => 'תכנון מוצר',
					'add_new_item'  => 'הוספת תכנון מוצר',
					'edit_item'     => 'עריכת תכנון מוצר',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-products',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
				'capabilities'        => array(
					'edit_post'              => 'manage_options',
					'read_post'              => 'manage_options',
					'delete_post'            => 'manage_options',
					'edit_posts'             => 'manage_options',
					'edit_others_posts'      => 'manage_options',
					'publish_posts'          => 'manage_options',
					'read_private_posts'     => 'manage_options',
					'delete_posts'           => 'manage_options',
					'delete_private_posts'   => 'manage_options',
					'delete_published_posts' => 'manage_options',
					'delete_others_posts'    => 'manage_options',
					'edit_private_posts'     => 'manage_options',
					'edit_published_posts'   => 'manage_options',
					'create_posts'           => 'manage_options',
				),
				'map_meta_cap'        => false,
			)
		);

		$fields = array(
			'_complete99_product_status'      => 'string',
			'_complete99_product_sku'         => 'string',
			'_complete99_product_kind'        => 'string',
			'_complete99_product_name_he'     => 'string',
			'_complete99_product_name_en'     => 'string',
			'_complete99_product_unit'        => 'string',
			'_complete99_product_weight'      => 'number',
			'_complete99_product_price'       => 'number',
			'_complete99_product_currency'    => 'string',
			'_complete99_product_ingredients' => 'string',
			'_complete99_product_allergens'   => 'string',
			'_complete99_product_storage'     => 'string',
			'_complete99_product_stock_source'=> 'string',
			'_complete99_product_rights'      => 'string',
			'_complete99_product_model_he'    => 'string',
			'_complete99_product_model_en'    => 'string',
			'_complete99_product_material_he' => 'string',
			'_complete99_product_material_en' => 'string',
			'_complete99_product_dimensions_he' => 'string',
			'_complete99_product_dimensions_en' => 'string',
			'_complete99_product_care_he'     => 'string',
			'_complete99_product_care_en'     => 'string',
			'_complete99_product_safety_he'   => 'string',
			'_complete99_product_safety_en'   => 'string',
		);
		foreach ( $fields as $key => $type ) {
			register_post_meta(
				'c99_product_plan',
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'number' === $type ? array( __CLASS__, 'sanitize_number' ) : 'sanitize_text_field',
					'auth_callback'     => static function () {
						return current_user_can( 'manage_options' );
					},
				)
			);
		}

		$product_text_fields = array(
			self::NAME_HE,
			self::NAME_EN,
			self::DESCRIPTION_HE,
			self::DESCRIPTION_EN,
			self::INGREDIENTS_HE,
			self::INGREDIENTS_EN,
			self::ALLERGENS_HE,
			self::ALLERGENS_EN,
			self::STORAGE_HE,
			self::STORAGE_EN,
			self::FULFILMENT_HE,
			self::FULFILMENT_EN,
			self::ORIGIN_HE,
			self::ORIGIN_EN,
			self::MODEL_HE,
			self::MODEL_EN,
			self::MATERIAL_HE,
			self::MATERIAL_EN,
			self::DIMENSIONS_HE,
			self::DIMENSIONS_EN,
			self::CARE_HE,
			self::CARE_EN,
			self::SAFETY_HE,
			self::SAFETY_EN,
		);
		foreach ( $product_text_fields as $key ) {
			register_post_meta(
				'product',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => static function () {
						return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
					},
				)
			);
		}

		foreach ( array( self::PRODUCT_APPROVED, self::PRODUCT_KIND, self::STOCK_AUTHORITY, self::LABEL_REVIEWED, self::ORIGIN_REVIEWED, self::CHECKOUT_ELIGIBLE, self::RIGHTS_REVIEWED, self::TAX_REVIEWED, self::MEDIA_PUBLIC_SAFE ) as $key ) {
			register_post_meta(
				'product',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'sanitize_key',
					'auth_callback'     => static function () {
						return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
					},
				)
			);
		}
		register_post_meta(
			'attachment',
			self::MEDIA_PUBLIC_SAFE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => static function () {
					return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
				},
			)
		);
	}

	public static function sanitize_number( $value ) {
		return is_numeric( $value ) ? (float) $value : 0;
	}

	public static function order_url( $lang = 'he' ) {
		$language = 'en' === strtolower( trim( (string) $lang ) ) ? 'en' : 'he';
		$verified = class_exists( 'Complete99_Order_Connectors' )
			? Complete99_Order_Connectors::primary_url( $language )
			: '';
		if ( '' !== $verified ) {
			return $verified;
		}
		return 'en' === $language ? self::VERIFIED_ORDER_EN : self::VERIFIED_ORDER_HE;
	}

	public static function render_product_readiness_fields() {
		if ( ! function_exists( 'woocommerce_wp_checkbox' )
			|| ! function_exists( 'woocommerce_wp_select' )
			|| ! function_exists( 'woocommerce_wp_text_input' )
			|| ! function_exists( 'woocommerce_wp_textarea_input' ) ) {
			return;
		}
		echo '<div class="options_group">';
		woocommerce_wp_select(
			array(
				'id'          => self::PRODUCT_KIND,
				'label'       => 'Complete99 product kind',
				'description' => 'Food uses ingredient, allergen and storage copy. Equipment uses model, material, dimensions, care and safety copy.',
				'options'     => array(
					'food'      => 'Food',
					'equipment' => 'Equipment',
				),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => self::NAME_HE,
				'label'       => 'Store name in Hebrew',
				'description' => 'The reviewed public Hebrew product name.',
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => self::NAME_EN,
				'label'       => 'Store name in English',
				'description' => 'The reviewed public English product name.',
			)
		);
		foreach (
			array(
				self::DESCRIPTION_HE => 'Description in Hebrew',
				self::DESCRIPTION_EN => 'Description in English',
				self::INGREDIENTS_HE => 'Ingredients in Hebrew',
				self::INGREDIENTS_EN => 'Ingredients in English',
				self::ALLERGENS_HE   => 'Allergen statement in Hebrew',
				self::ALLERGENS_EN   => 'Allergen statement in English',
				self::STORAGE_HE     => 'Storage instructions in Hebrew',
				self::STORAGE_EN     => 'Storage instructions in English',
				self::FULFILMENT_HE  => 'Pickup and delivery terms in Hebrew',
				self::FULFILMENT_EN  => 'Pickup and delivery terms in English',
				self::ORIGIN_HE      => 'Country of origin in Hebrew',
				self::ORIGIN_EN      => 'Country of origin in English',
				self::MODEL_HE       => 'Equipment model in Hebrew',
				self::MODEL_EN       => 'Equipment model in English',
				self::MATERIAL_HE    => 'Equipment material in Hebrew',
				self::MATERIAL_EN    => 'Equipment material in English',
				self::DIMENSIONS_HE  => 'Equipment dimensions in Hebrew',
				self::DIMENSIONS_EN  => 'Equipment dimensions in English',
				self::CARE_HE        => 'Equipment care in Hebrew',
				self::CARE_EN        => 'Equipment care in English',
				self::SAFETY_HE      => 'Equipment safety in Hebrew',
				self::SAFETY_EN      => 'Equipment safety in English',
			) as $id => $label
		) {
			woocommerce_wp_textarea_input(
				array(
					'id'          => $id,
					'label'       => $label,
					'description' => 'Required before this product can appear in the Complete99 pantry.',
				)
			);
		}
		woocommerce_wp_checkbox(
			array(
				'id'          => self::PRODUCT_APPROVED,
				'label'       => 'Complete99 catalog publication authorized',
				'description' => 'Confirms product identity, public image, opening price and fulfilment for catalog display. Checkout uses separate gates.',
			)
		);
		woocommerce_wp_select(
			array(
				'id'          => self::STOCK_AUTHORITY,
				'label'       => 'Complete99 stock authority',
				'description' => 'The public store opens only when WooCommerce is the recorded stock authority.',
				'options'     => array(
					''            => 'Not configured',
					'woocommerce' => 'WooCommerce',
				),
			)
		);
		foreach (
			array(
				self::LABEL_REVIEWED  => 'Retail label reviewed',
				self::ORIGIN_REVIEWED => 'Country of origin reviewed',
				self::CHECKOUT_ELIGIBLE => 'Product approved for checkout',
				self::RIGHTS_REVIEWED => 'Image and content rights reviewed',
				self::TAX_REVIEWED    => 'Tax treatment reviewed',
				self::MEDIA_PUBLIC_SAFE => 'Product media approved as public-safe',
			) as $id => $label
		) {
			woocommerce_wp_checkbox(
				array(
					'id'          => $id,
					'label'       => $label,
					'description' => 'Recorded review is required before public sale.',
				)
			);
		}
		echo '</div>';
	}

	public static function save_product_readiness_fields( $product ) {
		if ( ! is_object( $product )
			|| ! method_exists( $product, 'update_meta_data' )
			|| ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) ) {
			return;
		}
		self::invalidate_commerce_state_cache();
		$text_fields = array(
			self::NAME_HE,
			self::NAME_EN,
			self::DESCRIPTION_HE,
			self::DESCRIPTION_EN,
			self::INGREDIENTS_HE,
			self::INGREDIENTS_EN,
			self::ALLERGENS_HE,
			self::ALLERGENS_EN,
			self::STORAGE_HE,
			self::STORAGE_EN,
			self::FULFILMENT_HE,
			self::FULFILMENT_EN,
			self::ORIGIN_HE,
			self::ORIGIN_EN,
			self::MODEL_HE,
			self::MODEL_EN,
			self::MATERIAL_HE,
			self::MATERIAL_EN,
			self::DIMENSIONS_HE,
			self::DIMENSIONS_EN,
			self::CARE_HE,
			self::CARE_EN,
			self::SAFETY_HE,
			self::SAFETY_EN,
		);
		foreach ( $text_fields as $key ) {
			$value = isset( $_POST[ $key ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				: '';
			$product->update_meta_data( $key, $value );
		}
		$product_kind = isset( $_POST[ self::PRODUCT_KIND ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_key( wp_unslash( $_POST[ self::PRODUCT_KIND ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: 'food';
		$product->update_meta_data( self::PRODUCT_KIND, 'equipment' === $product_kind ? 'equipment' : 'food' );
		$approved = isset( $_POST[ self::PRODUCT_APPROVED ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$authority = isset( $_POST[ self::STOCK_AUTHORITY ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_key( wp_unslash( $_POST[ self::STOCK_AUTHORITY ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: '';
		$product->update_meta_data( self::PRODUCT_APPROVED, $approved );
		$product->update_meta_data( self::STOCK_AUTHORITY, 'woocommerce' === $authority ? $authority : '' );
		foreach ( array( self::LABEL_REVIEWED, self::ORIGIN_REVIEWED, self::CHECKOUT_ELIGIBLE, self::RIGHTS_REVIEWED, self::TAX_REVIEWED, self::MEDIA_PUBLIC_SAFE ) as $key ) {
			$product->update_meta_data( $key, isset( $_POST[ $key ] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		$media_public_safe = isset( $_POST[ self::MEDIA_PUBLIC_SAFE ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$attachment_ids    = array_merge(
			array( absint( $product->get_image_id() ) ),
			array_map( 'absint', (array) $product->get_gallery_image_ids() )
		);
		foreach ( array_values( array_unique( array_filter( $attachment_ids ) ) ) as $attachment_id ) {
			update_post_meta( $attachment_id, self::MEDIA_PUBLIC_SAFE, $media_public_safe );
		}
		self::mark_commerce_configuration_dirty( true );
	}

	private static function mark_commerce_configuration_dirty( $invalidate_acceptance ) {
		self::mark_public_commerce_state_dirty();
		if ( ! $invalidate_acceptance ) {
			return;
		}
		delete_option( self::OPTION_ACCEPTANCE );
		wp_cache_delete( self::OPTION_ACCEPTANCE, 'options' );
		if ( false === get_option( self::OPTION_ACCEPTANCE, false ) ) {
			self::clear_outbox_error( 'configuration_state' );
		} else {
			self::record_outbox_error( 'configuration_state' );
		}
	}

	public static function mark_material_commerce_configuration_dirty() {
		self::mark_commerce_configuration_dirty( true );
	}

	public static function mark_product_cache_dirty() {
		self::mark_commerce_configuration_dirty( false );
	}

	private static function commerce_configuration_option_names() {
		return array(
			'woocommerce_gateway_order',
			'woocommerce_cart_save_for_later_enabled',
			'woocommerce_product_wishlist_enabled',
			'woocommerce_shop_page_id',
			'woocommerce_cart_page_id',
			'woocommerce_checkout_page_id',
			'woocommerce_myaccount_page_id',
			'woocommerce_terms_page_id',
			'wp_page_for_privacy_policy',
			'woocommerce_store_address',
			'woocommerce_store_address_2',
			'woocommerce_store_city',
			'woocommerce_store_postcode',
			'woocommerce_default_country',
			'woocommerce_allowed_countries',
			'woocommerce_all_except_countries',
			'woocommerce_specific_allowed_countries',
			'woocommerce_ship_to_countries',
			'woocommerce_specific_ship_to_countries',
			'woocommerce_default_customer_address',
			'woocommerce_ship_to_destination',
			'woocommerce_enable_shipping_calc',
			'woocommerce_shipping_cost_requires_address',
			'woocommerce_shipping_hide_rates_when_free',
			'woocommerce_weight_unit',
			'woocommerce_dimension_unit',
			'woocommerce_currency',
			'woocommerce_manage_stock',
			'woocommerce_hold_stock_minutes',
			'woocommerce_hide_out_of_stock_items',
			'woocommerce_calc_taxes',
			'woocommerce_prices_include_tax',
			'woocommerce_tax_based_on',
			'woocommerce_shipping_tax_class',
			'woocommerce_tax_round_at_subtotal',
			'woocommerce_tax_display_shop',
			'woocommerce_tax_display_cart',
			'woocommerce_price_display_suffix',
			'woocommerce_tax_total_display',
			'woocommerce_enable_guest_checkout',
			'woocommerce_enable_checkout_login_reminder',
			'woocommerce_enable_signup_and_login_from_checkout',
			'woocommerce_registration_generate_password',
			'woocommerce_registration_generate_username',
			'woocommerce_checkout_terms_and_conditions_checkbox_text',
			'woocommerce_checkout_privacy_policy_text',
			'woocommerce_registration_privacy_policy_text',
			'woocommerce_tax_classes',
			'woocommerce_price_num_decimals',
			'woocommerce_currency_pos',
			'woocommerce_price_thousand_sep',
			'woocommerce_price_decimal_sep',
			'woocommerce_checkout_pay_endpoint',
			'woocommerce_checkout_order_received_endpoint',
			'woocommerce_add_payment_method_endpoint',
			'woocommerce_delete_payment_method_endpoint',
			'woocommerce_set_default_payment_method_endpoint',
			'woocommerce_myaccount_orders_endpoint',
			'woocommerce_myaccount_view_order_endpoint',
			'woocommerce_myaccount_downloads_endpoint',
			'woocommerce_myaccount_edit_account_endpoint',
			'woocommerce_myaccount_edit_address_endpoint',
			'woocommerce_myaccount_payment_methods_endpoint',
			'woocommerce_myaccount_lost_password_endpoint',
			'woocommerce_logout_endpoint',
		);
	}

	private static function commerce_configuration_option_is_material( $option ) {
		$option = (string) $option;
		return in_array( $option, self::commerce_configuration_option_names(), true )
			|| 1 === preg_match( '/^woocommerce_[a-z0-9_-]+_settings$/', $option )
			|| 1 === preg_match( '/^woocommerce_(?:checkout|myaccount)_[a-z0-9_-]+_endpoint$/', $option )
			|| 'woocommerce_logout_endpoint' === $option;
	}

	public static function handle_commerce_option_change( $option ) {
		if ( self::commerce_configuration_option_is_material( $option ) ) {
			self::mark_commerce_configuration_dirty( true );
			return;
		}
		$option = (string) $option;
		if ( in_array(
			$option,
			array(
				self::OPTION_ENABLED,
				self::OPTION_PREVIEW,
				self::OPTION_EVER_LAUNCHED,
				self::OPTION_LAUNCH_AUDIT,
				self::OPTION_OUTBOX,
				self::OPTION_OUTBOX_FAILURES,
				self::OPTION_OUTBOX_AUDIT,
				self::OPTION_ACCEPTANCE,
				self::OPTION_LEGAL,
			),
			true
		)
			|| 0 === strpos( $option, self::OPTION_OUTBOX_ERROR_PREFIX )
			|| 0 === strpos( $option, self::OPTION_OUTBOX_FAILURE_EVENT_PREFIX ) ) {
			self::mark_public_commerce_state_dirty();
		}
	}

	private static function is_relevant_commerce_page( $post_id, $post = null ) {
		$post_id = absint( $post_id );
		$post = is_object( $post ) ? $post : get_post( $post_id );
		if ( ! $post || 'page' !== (string) $post->post_type ) {
			return false;
		}
		$woo_page_ids = array_filter(
			array_map(
				'absint',
				array(
					get_option( 'woocommerce_shop_page_id', 0 ),
					get_option( 'woocommerce_cart_page_id', 0 ),
					get_option( 'woocommerce_checkout_page_id', 0 ),
					get_option( 'woocommerce_myaccount_page_id', 0 ),
					get_option( 'woocommerce_terms_page_id', 0 ),
					get_option( 'wp_page_for_privacy_policy', 0 ),
				)
			)
		);
		if ( in_array( $post_id, $woo_page_ids, true ) ) {
			return true;
		}
		return in_array(
			(string) get_post_meta( $post_id, '_complete99_translation_key', true ),
			array( 'store', 'terms', 'privacy', 'accessibility' ),
			true
		);
	}

	private static function approved_product_source_id( $post_id ) {
		$post_id = absint( $post_id );
		$post_type = (string) get_post_type( $post_id );
		if ( 'product_variation' === $post_type ) {
			$post_id = absint( wp_get_post_parent_id( $post_id ) );
		}
		return 'product' === (string) get_post_type( $post_id ) ? $post_id : 0;
	}

	private static function product_is_acceptance_relevant( $post_id, $meta_key = '' ) {
		$source_id = self::approved_product_source_id( $post_id );
		return 0 < $source_id
			&& ( self::PRODUCT_APPROVED === $meta_key
				|| 'yes' === (string) get_post_meta( $source_id, self::PRODUCT_APPROVED, true ) );
	}

	private static function attachment_is_approved_product_image( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( 1 > $attachment_id ) {
			return false;
		}
		foreach ( (array) self::approved_products()['reviewed_ids'] as $product_id ) {
			$product_id = absint( $product_id );
			if ( $attachment_id === absint( get_post_thumbnail_id( $product_id ) ) ) {
				return true;
			}
			$gallery_ids = array_values(
				array_filter(
					array_map(
						'absint',
						explode( ',', (string) get_post_meta( $product_id, '_product_image_gallery', true ) )
					)
				)
			);
			if ( in_array( $attachment_id, $gallery_ids, true ) ) {
				return true;
			}
		}
		return false;
	}

	public static function handle_commerce_attachment_change( $attachment_id ) {
		if ( self::attachment_is_approved_product_image( $attachment_id ) ) {
			self::mark_commerce_configuration_dirty( true );
		}
	}

	public static function handle_commerce_attachment_metadata_change( $metadata, $attachment_id ) {
		self::handle_commerce_attachment_change( $attachment_id );
		return $metadata;
	}

	public static function handle_commerce_page_change( $post_id, $post, $update, $post_before ) {
		if ( self::is_relevant_commerce_page( $post_id, $post ) ) {
			if ( ! $update
				|| ! is_object( $post_before )
				|| (string) $post->post_title !== (string) $post_before->post_title
				|| (string) $post->post_content !== (string) $post_before->post_content
				|| (string) $post->post_excerpt !== (string) $post_before->post_excerpt
				|| (string) $post->post_name !== (string) $post_before->post_name
				|| (string) $post->post_parent !== (string) $post_before->post_parent
				|| (string) $post->post_status !== (string) $post_before->post_status ) {
				self::mark_commerce_configuration_dirty( true );
			}
			return;
		}
		if ( self::product_is_acceptance_relevant( $post_id )
			&& ( ! $update
				|| ! is_object( $post_before )
				|| (string) $post->post_title !== (string) $post_before->post_title
				|| (string) $post->post_content !== (string) $post_before->post_content
				|| (string) $post->post_excerpt !== (string) $post_before->post_excerpt
				|| (string) $post->post_name !== (string) $post_before->post_name
				|| (string) $post->post_parent !== (string) $post_before->post_parent
				|| (string) $post->post_status !== (string) $post_before->post_status ) ) {
			self::mark_commerce_configuration_dirty( true );
		}
	}

	public static function handle_commerce_page_status_change( $new_status, $old_status, $post ) {
		if ( $new_status === $old_status || ! is_object( $post ) ) {
			return;
		}
		if ( self::is_relevant_commerce_page( $post->ID, $post )
			|| self::product_is_acceptance_relevant( $post->ID ) ) {
			self::mark_commerce_configuration_dirty( true );
		}
	}

	public static function handle_commerce_post_delete( $post_id, $post = null ) {
		if ( self::is_relevant_commerce_page( $post_id, $post )
			|| self::product_is_acceptance_relevant( $post_id ) ) {
			self::mark_commerce_configuration_dirty( true );
		}
	}

	public static function handle_commerce_post_meta_change( $meta_id, $object_id, $meta_key, $meta_value = null ) {
		$meta_key = (string) $meta_key;
		if ( 'attachment' === (string) get_post_type( $object_id )
			&& in_array( $meta_key, array( '_wp_attached_file', '_wp_attachment_metadata', '_wp_attachment_image_alt', self::MEDIA_PUBLIC_SAFE ), true )
			&& self::attachment_is_approved_product_image( $object_id ) ) {
			self::mark_commerce_configuration_dirty( true );
			return;
		}
		if ( self::is_relevant_commerce_page( $object_id )
			&& in_array(
				$meta_key,
				array(
					'_complete99_translation_key',
					'_complete99_language',
					'_wp_page_template',
					'_complete99_index_eligible',
					'_complete99_verification_state',
				),
				true
			) ) {
			$launch_only = in_array( $meta_key, array( '_complete99_index_eligible', '_complete99_verification_state' ), true );
			self::mark_commerce_configuration_dirty( ! $launch_only );
			return;
		}
		$material_product_meta = array(
			'_sku',
			'_price',
			'_regular_price',
			'_sale_price',
			'_sale_price_dates_from',
			'_sale_price_dates_to',
			'_tax_status',
			'_tax_class',
			'_manage_stock',
			'_backorders',
			'_sold_individually',
			'_weight',
			'_length',
			'_width',
			'_height',
			'_virtual',
			'_downloadable',
			'_downloadable_files',
			'_download_limit',
			'_download_expiry',
			'_thumbnail_id',
			'_product_image_gallery',
			'_product_attributes',
			'_purchase_note',
			self::PRODUCT_APPROVED,
			self::PRODUCT_KIND,
			self::STOCK_AUTHORITY,
			self::NAME_HE,
			self::NAME_EN,
			self::DESCRIPTION_HE,
			self::DESCRIPTION_EN,
			self::INGREDIENTS_HE,
			self::INGREDIENTS_EN,
			self::ALLERGENS_HE,
			self::ALLERGENS_EN,
			self::STORAGE_HE,
			self::STORAGE_EN,
			self::FULFILMENT_HE,
			self::FULFILMENT_EN,
			self::ORIGIN_HE,
			self::ORIGIN_EN,
			self::MODEL_HE,
			self::MODEL_EN,
			self::MATERIAL_HE,
			self::MATERIAL_EN,
			self::DIMENSIONS_HE,
			self::DIMENSIONS_EN,
			self::CARE_HE,
			self::CARE_EN,
			self::SAFETY_HE,
			self::SAFETY_EN,
			self::LABEL_REVIEWED,
			self::ORIGIN_REVIEWED,
			self::CHECKOUT_ELIGIBLE,
			self::RIGHTS_REVIEWED,
			self::TAX_REVIEWED,
			self::MEDIA_PUBLIC_SAFE,
		);
		if ( in_array( $meta_key, array( '_stock', '_stock_status' ), true )
			&& self::product_is_acceptance_relevant( $object_id ) ) {
			self::mark_commerce_configuration_dirty( false );
		} elseif ( in_array( $meta_key, $material_product_meta, true )
			&& self::product_is_acceptance_relevant( $object_id, $meta_key ) ) {
			self::mark_commerce_configuration_dirty( true );
		}
	}

	public static function handle_commerce_product_terms_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( ! self::product_is_acceptance_relevant( $object_id )
			|| ! in_array( $taxonomy, array( 'product_type', 'product_shipping_class', 'product_visibility', 'product_cat', 'product_tag' ), true ) ) {
			return;
		}
		$changed = array_values( array_unique( array_merge( array_diff( (array) $tt_ids, (array) $old_tt_ids ), array_diff( (array) $old_tt_ids, (array) $tt_ids ) ) ) );
		if ( empty( $changed ) ) {
			return;
		}
		if ( 'product_visibility' === $taxonomy ) {
			$material = false;
			foreach ( $changed as $tt_id ) {
				$term = get_term_by( 'term_taxonomy_id', absint( $tt_id ), 'product_visibility' );
				$slug = $term && ! is_wp_error( $term ) ? (string) $term->slug : '';
				if ( 'outofstock' !== $slug && 0 !== strpos( $slug, 'rated-' ) ) {
					$material = true;
				}
			}
			self::mark_commerce_configuration_dirty( $material );
			return;
		}
		self::mark_commerce_configuration_dirty( true );
	}

	public static function handle_commerce_feature_change( $feature_id, $enabled ) {
		if ( in_array( (string) $feature_id, array( 'cart_save_for_later', 'product_wishlist' ), true ) ) {
			self::mark_commerce_configuration_dirty( true );
		}
	}

	public static function flush_product_configuration_caches() {
		if ( ! self::$product_configuration_dirty ) {
			return;
		}
		self::$product_configuration_dirty = false;
		self::invalidate_commerce_state_cache();
		if ( ! self::purge_commerce_caches_with_retry() ) {
			self::record_outbox_error( 'cache' );
		}
	}

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/store/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'public_status' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/readiness',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( __CLASS__, 'can_manage_commerce' ),
				'callback'            => array( __CLASS__, 'private_readiness' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/acceptance',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_govern_commerce' ),
				'callback'            => array( __CLASS__, 'record_checkout_acceptance' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/acceptance-preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_govern_commerce' ),
				'callback'            => array( __CLASS__, 'set_acceptance_preview' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/legal-acceptance',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_govern_commerce' ),
				'callback'            => array( __CLASS__, 'record_legal_acceptance' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/launch',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_govern_commerce' ),
				'callback'            => array( __CLASS__, 'set_store_launch_state' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/operations/outbox',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => array( __CLASS__, 'can_operate_commerce_outbox' ),
					'callback'            => array( __CLASS__, 'read_outbox' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( __CLASS__, 'can_operate_commerce_outbox' ),
					'callback'            => array( __CLASS__, 'acknowledge_outbox' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/operations/outbox/replay',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( __CLASS__, 'can_operate_commerce_outbox' ),
				'callback'            => array( __CLASS__, 'replay_outbox_failures' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/store/operations/orders/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( __CLASS__, 'can_view_commerce_order_details' ),
				'callback'            => array( __CLASS__, 'read_order_operation_details' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ) {
							return 0 < absint( $value );
						},
					),
				),
			)
		);
	}

	public static function can_manage_commerce() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	public static function can_govern_commerce() {
		return current_user_can( 'manage_options' );
	}

	public static function can_operate_commerce_outbox() {
		return current_user_can( 'manage_options' );
	}

	public static function can_view_commerce_order_details() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	public static function can_preview_commerce() {
		return 'yes' === (string) get_option( self::OPTION_PREVIEW, 'no' )
			&& self::can_govern_commerce();
	}

	private static function published_store_page_ids() {
		$store_ids = array();
		foreach ( array( 'he', 'en' ) as $lang ) {
			$store_id = Complete99_Content::find_translation_post_id( 'store', $lang, true );
			if ( 1 > $store_id || 'publish' !== get_post_status( $store_id ) ) {
				return array();
			}
			$store_ids[] = $store_id;
		}
		return $store_ids;
	}

	private static function commit_store_hold_state( $store_ids, $preview_enabled ) {
		update_option( self::OPTION_ENABLED, 'no', false );
		update_option( self::OPTION_PREVIEW, $preview_enabled ? 'yes' : 'no', false );
		self::invalidate_commerce_state_cache();
		$pages_closed = self::write_store_page_launch_state( $store_ids, false );
		$audit        = self::store_launch_audit( false, self::readiness() );
		update_option( self::OPTION_LAUNCH_AUDIT, $audit, false );
		$audit_verified = self::option_matches( self::OPTION_LAUNCH_AUDIT, $audit );
		$cache_purged   = self::purge_commerce_caches_with_retry();
		$option_closed  = 'no' === (string) get_option( self::OPTION_ENABLED, '__missing__' );
		$preview_stored = ( $preview_enabled ? 'yes' : 'no' )
			=== (string) get_option( self::OPTION_PREVIEW, '__missing__' );
		$held = $option_closed && $preview_stored && $pages_closed && $audit_verified && $cache_purged;
		if ( $option_closed && $pages_closed ) {
			self::clear_outbox_error( 'launch_state' );
		} else {
			self::record_outbox_error( 'launch_state' );
		}
		if ( ! $cache_purged ) {
			self::record_outbox_error( 'cache' );
		}
		return array(
			'held'                    => $held,
			'store_enabled'           => 'yes' === (string) get_option( self::OPTION_ENABLED, 'no' ),
			'preview_enabled'         => 'yes' === (string) get_option( self::OPTION_PREVIEW, 'no' ),
			'store_pages_closed'      => $pages_closed,
			'hold_audit_verified'     => $audit_verified,
			'cache_purge_verified'    => $cache_purged,
		);
	}

	public static function set_acceptance_preview( WP_REST_Request $request ) {
		$enabled = filter_var( $request->get_param( 'enabled' ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		if ( null === $enabled ) {
			return new WP_Error( 'complete99_commerce_preview', 'The enabled value must be a boolean.', array( 'status' => 400 ) );
		}
		if ( ! self::acquire_store_launch_lock() ) {
			return new WP_Error( 'complete99_commerce_launch_lock', 'Another store-state change is in progress.', array( 'status' => 503 ) );
		}
		try {
			$store_ids = self::published_store_page_ids();
			if ( 2 !== count( $store_ids ) ) {
				return new WP_Error( 'complete99_commerce_store_pages', 'Published Hebrew and English pantry pages are required.', array( 'status' => 422 ) );
			}
			if ( $enabled ) {
				delete_option( self::OPTION_ACCEPTANCE );
				if ( false !== get_option( self::OPTION_ACCEPTANCE, false ) ) {
					return new WP_Error( 'complete99_commerce_acceptance_delete', 'The previous acceptance receipt could not be removed.', array( 'status' => 500 ) );
				}
			}
			$hold = self::commit_store_hold_state( $store_ids, $enabled );
			if ( empty( $hold['held'] ) ) {
				return new WP_Error(
					'complete99_commerce_preview_readback',
					'The acceptance hold state could not be fully verified.',
					array_merge( array( 'status' => 500 ), $hold )
				);
			}
			$readiness = self::readiness();
			return rest_ensure_response(
				array(
					'acceptance_preview' => $enabled,
					'public_store_ready' => (bool) $readiness['ready'],
					'readiness'          => $readiness,
				)
			);
		} finally {
			self::release_store_launch_lock();
		}
	}

	public static function set_store_launch_state( WP_REST_Request $request ) {
		$enabled = filter_var( $request->get_param( 'enabled' ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		if ( null === $enabled ) {
			return new WP_Error( 'complete99_commerce_launch', 'The enabled value must be a boolean.', array( 'status' => 400 ) );
		}
		if ( ! self::acquire_store_launch_lock() ) {
			return new WP_Error( 'complete99_commerce_launch_lock', 'Another store-state change is in progress.', array( 'status' => 503 ) );
		}
		try {
			self::invalidate_commerce_state_cache();
			$store_ids = array();
			foreach ( array( 'he', 'en' ) as $lang ) {
				$store_id = Complete99_Content::find_translation_post_id( 'store', $lang, true );
				if ( 1 > $store_id || 'publish' !== get_post_status( $store_id ) ) {
					return new WP_Error( 'complete99_commerce_store_pages', 'Published Hebrew and English pantry pages are required.', array( 'status' => 422 ) );
				}
				$store_ids[] = $store_id;
			}

			$previous_enabled       = (string) get_option( self::OPTION_ENABLED, 'no' );
			$previous_ever_launched = (string) get_option( self::OPTION_EVER_LAUNCHED, 'no' );
			$previous_audit         = get_option( self::OPTION_LAUNCH_AUDIT, array() );
			$previous_index         = array();
			$previous_state         = array();
			foreach ( $store_ids as $store_id ) {
				$previous_index[ $store_id ] = get_post_meta( $store_id, '_complete99_index_eligible', true );
				$previous_state[ $store_id ] = get_post_meta( $store_id, '_complete99_verification_state', true );
			}

			if ( ! $enabled ) {
				update_option( self::OPTION_ENABLED, 'no', false );
				self::invalidate_commerce_state_cache();
				$option_closed = 'no' === (string) get_option( self::OPTION_ENABLED, '__missing__' );
				$pages_closed  = self::write_store_page_launch_state( $store_ids, false );
				$audit         = self::store_launch_audit( false, self::readiness() );
				update_option( self::OPTION_LAUNCH_AUDIT, $audit, false );
				$audit_verified = self::option_matches( self::OPTION_LAUNCH_AUDIT, $audit );
				$cache_purged   = self::purge_commerce_caches_with_retry();
				$actual_enabled = 'yes' === (string) get_option( self::OPTION_ENABLED, 'no' );
				if ( ! $option_closed || ! $pages_closed || $actual_enabled ) {
					self::record_outbox_error( 'launch_state' );
					return new WP_Error(
						'complete99_commerce_close_readback',
						'The requested store close state could not be fully verified.',
						array(
							'status'                       => 500,
							'store_enabled'                => $actual_enabled,
							'store_pages_closed'           => $pages_closed,
							'close_audit_verified'         => $audit_verified,
							'cache_purge_verified'         => $cache_purged,
							'manual_intervention_required' => true,
						)
					);
				}
				self::clear_outbox_error( 'launch_state' );
				if ( ! $audit_verified ) {
					return new WP_Error(
						'complete99_commerce_launch_audit',
						'The store was closed, but its close audit could not be verified.',
						array( 'status' => 500, 'store_enabled' => false )
					);
				}
				if ( ! $cache_purged ) {
					self::record_outbox_error( 'cache' );
					return new WP_Error(
						'complete99_commerce_cache',
						'The store is closed, but public cache invalidation requires operator attention.',
						array(
							'status'                      => 503,
							'store_enabled'               => false,
							'manual_cache_purge_required' => true,
						)
					);
				}
				return rest_ensure_response( self::readiness() );
			}

			if ( ! self::purge_commerce_caches_with_retry() ) {
				self::record_outbox_error( 'cache' );
				return new WP_Error(
					'complete99_commerce_cache',
					'The store remains held because pre-launch cache invalidation could not be verified.',
					array( 'status' => 503, 'store_enabled' => false )
				);
			}
			self::invalidate_commerce_state_cache();
			$staged_readiness = self::readiness( true, true, true );
			if ( ! $staged_readiness['ready'] ) {
				return new WP_Error(
					'complete99_commerce_not_ready',
					'The store remains held because one or more launch requirements failed.',
					array(
						'status'               => 422,
						'missing_requirements' => $staged_readiness['missing_requirements'],
					)
				);
			}
			$audit = self::store_launch_audit( true, $staged_readiness );
			update_option( self::OPTION_LAUNCH_AUDIT, $audit, false );
			if ( ! self::option_matches( self::OPTION_LAUNCH_AUDIT, $audit )
				|| ! self::write_store_page_launch_state( $store_ids, true ) ) {
				$rollback_verified = self::restore_store_launch_snapshot(
					$previous_enabled,
					$previous_index,
					$previous_state,
					$previous_audit,
					$previous_ever_launched
				);
				$rollback_cache_purged = self::purge_commerce_caches_with_retry();
				return new WP_Error(
					'complete99_commerce_launch_staging',
					'Launch staging failed and rollback required verification.',
					array(
						'status'                       => 500,
						'store_enabled'                => 'yes' === (string) get_option( self::OPTION_ENABLED, 'no' ),
						'rollback_verified'            => $rollback_verified,
						'rollback_cache_purge_verified'=> $rollback_cache_purged,
					)
				);
			}

			update_option( self::OPTION_ENABLED, 'yes', false );
			update_option( self::OPTION_EVER_LAUNCHED, 'yes', false );
			self::invalidate_commerce_state_cache();
			$actual_readiness = self::readiness();
			if ( 'yes' !== (string) get_option( self::OPTION_ENABLED, 'no' )
				|| 'yes' !== (string) get_option( self::OPTION_EVER_LAUNCHED, 'no' )
				|| ! $actual_readiness['ready'] ) {
				$rollback_verified = self::restore_store_launch_snapshot(
					$previous_enabled,
					$previous_index,
					$previous_state,
					$previous_audit,
					$previous_ever_launched
				);
				$rollback_cache_purged = self::purge_commerce_caches_with_retry();
				return new WP_Error(
					'complete99_commerce_launch_readback',
					'The committed launch state could not be verified.',
					array(
						'status'                 => 500,
						'store_enabled'          => 'yes' === (string) get_option( self::OPTION_ENABLED, 'no' ),
						'rollback_verified'      => $rollback_verified,
						'rollback_cache_purged'  => $rollback_cache_purged,
					)
				);
			}
			if ( ! self::purge_commerce_caches_with_retry() ) {
				$rollback_verified = self::restore_store_launch_snapshot(
					$previous_enabled,
					$previous_index,
					$previous_state,
					$previous_audit,
					$previous_ever_launched
				);
				self::record_outbox_error( 'cache' );
				$rollback_cache_purged = self::purge_commerce_caches_with_retry();
				return new WP_Error(
					'complete99_commerce_cache',
					'Post-launch cache invalidation failed and rollback required verification.',
					array(
						'status'                       => 503,
						'store_enabled'                => 'yes' === (string) get_option( self::OPTION_ENABLED, 'no' ),
						'rollback_verified'            => $rollback_verified,
						'rollback_cache_purge_verified'=> $rollback_cache_purged,
					)
				);
			}

			return rest_ensure_response( self::readiness() );
		} finally {
			self::release_store_launch_lock();
		}
	}

	private static function acquire_store_launch_lock() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return false;
		}
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::outbox_lock_name() . '-launch', 5 ) );
		return '1' === (string) $locked;
	}

	private static function release_store_launch_lock() {
		global $wpdb;
		if ( is_object( $wpdb ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'get_var' ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::outbox_lock_name() . '-launch' ) );
		}
	}

	private static function option_matches( $name, $expected ) {
		$stored = get_option( $name, array() );
		return is_array( $stored )
			&& hash_equals( hash( 'sha256', serialize( $expected ) ), hash( 'sha256', serialize( $stored ) ) );
	}

	private static function write_store_page_launch_state( $store_ids, $enabled ) {
		$success = true;
		foreach ( $store_ids as $store_id ) {
			update_post_meta( $store_id, '_complete99_index_eligible', $enabled ? 1 : 0 );
			update_post_meta( $store_id, '_complete99_verification_state', $enabled ? 'launch_ready' : 'configuration_required' );
			clean_post_cache( $store_id );
			if ( (bool) rest_sanitize_boolean( get_post_meta( $store_id, '_complete99_index_eligible', true ) ) !== (bool) $enabled
				|| ( $enabled ? 'launch_ready' : 'configuration_required' )
					!== (string) get_post_meta( $store_id, '_complete99_verification_state', true ) ) {
				$success = false;
			}
		}
		return $success;
	}

	private static function store_launch_audit( $enabled, $readiness ) {
		$readiness = self::canonicalize_digest_value( is_array( $readiness ) ? $readiness : array() );
		$store_ids = self::published_store_page_ids();
		sort( $store_ids, SORT_NUMERIC );
		return array(
			'schema'            => 'complete99-commerce-launch-audit/v3',
			'enabled'           => (bool) $enabled,
			'changed_at'        => gmdate( 'c' ),
			'changed_by'        => function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0,
			'readiness'         => $readiness,
			'readiness_digest'  => self::digest_value( $readiness ),
			'acceptance_digest' => self::digest_value( get_option( self::OPTION_ACCEPTANCE, array() ) ),
			'legal_digest'      => self::digest_value( get_option( self::OPTION_LEGAL, array() ) ),
			'store_page_ids'    => $store_ids,
		);
	}

	private static function launch_audit_is_valid() {
		$audit = get_option( self::OPTION_LAUNCH_AUDIT, array() );
		if ( ! is_array( $audit )
			|| 'complete99-commerce-launch-audit/v3' !== ( $audit['schema'] ?? '' )
			|| true !== ( $audit['enabled'] ?? null )
			|| 1 > absint( $audit['changed_by'] ?? 0 )
			|| ! isset( $audit['readiness'], $audit['readiness_digest'], $audit['acceptance_digest'], $audit['legal_digest'], $audit['store_page_ids'] )
			|| ! is_array( $audit['readiness'] )
			|| ! is_array( $audit['store_page_ids'] )
			|| empty( $audit['readiness']['ready'] )
			|| ! empty( $audit['readiness']['missing_requirements'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $audit['readiness_digest'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $audit['acceptance_digest'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $audit['legal_digest'] )
			|| ! hash_equals( (string) $audit['readiness_digest'], self::digest_value( $audit['readiness'] ) )
			|| ! hash_equals( (string) $audit['acceptance_digest'], self::digest_value( get_option( self::OPTION_ACCEPTANCE, array() ) ) )
			|| ! hash_equals( (string) $audit['legal_digest'], self::digest_value( get_option( self::OPTION_LEGAL, array() ) ) ) ) {
			return false;
		}
		$changed_at = strtotime( (string) ( $audit['changed_at'] ?? '' ) );
		if ( false === $changed_at || $changed_at > time() + 300 ) {
			return false;
		}
		$expected_ids = self::published_store_page_ids();
		$stored_ids   = array_values( array_unique( array_map( 'absint', $audit['store_page_ids'] ) ) );
		sort( $expected_ids, SORT_NUMERIC );
		sort( $stored_ids, SORT_NUMERIC );
		if ( 2 !== count( $stored_ids )
			|| ! hash_equals( self::digest_value( $expected_ids ), self::digest_value( $stored_ids ) ) ) {
			return false;
		}
		foreach ( $stored_ids as $store_id ) {
			if ( ! rest_sanitize_boolean( get_post_meta( $store_id, '_complete99_index_eligible', true ) )
				|| 'launch_ready' !== (string) get_post_meta( $store_id, '_complete99_verification_state', true ) ) {
				return false;
			}
		}
		return true;
	}

	private static function restore_store_launch_snapshot( $previous_enabled, $previous_index, $previous_state, $previous_audit, $previous_ever_launched ) {
		$target_enabled = 'yes' === $previous_enabled ? 'yes' : 'no';
		$target_ever    = 'yes' === $previous_ever_launched ? 'yes' : 'no';
		update_option( self::OPTION_ENABLED, $target_enabled, false );
		update_option( self::OPTION_EVER_LAUNCHED, $target_ever, false );
		foreach ( $previous_index as $store_id => $value ) {
			update_post_meta( $store_id, '_complete99_index_eligible', $value );
			update_post_meta( $store_id, '_complete99_verification_state', $previous_state[ $store_id ] ?? '' );
			clean_post_cache( $store_id );
		}
		if ( is_array( $previous_audit ) && ! empty( $previous_audit ) ) {
			update_option( self::OPTION_LAUNCH_AUDIT, $previous_audit, false );
		} else {
			delete_option( self::OPTION_LAUNCH_AUDIT );
		}
		self::invalidate_commerce_state_cache();
		$verified = $target_enabled === (string) get_option( self::OPTION_ENABLED, '' )
			&& $target_ever === (string) get_option( self::OPTION_EVER_LAUNCHED, '' );
		foreach ( $previous_index as $store_id => $value ) {
			if ( (string) $value !== (string) get_post_meta( $store_id, '_complete99_index_eligible', true )
				|| (string) ( $previous_state[ $store_id ] ?? '' )
					!== (string) get_post_meta( $store_id, '_complete99_verification_state', true ) ) {
				$verified = false;
			}
		}
		if ( is_array( $previous_audit ) && ! empty( $previous_audit ) ) {
			$verified = $verified && self::option_matches( self::OPTION_LAUNCH_AUDIT, $previous_audit );
		} else {
			$verified = $verified && false === get_option( self::OPTION_LAUNCH_AUDIT, false );
		}
		if ( $verified ) {
			self::clear_outbox_error( 'launch_state' );
		} else {
			self::record_outbox_error( 'launch_state' );
		}
		return $verified;
	}

	private static function purge_commerce_caches_with_retry() {
		return self::purge_commerce_caches() || self::purge_commerce_caches();
	}

	private static function purge_commerce_caches() {
		$success = true;
		if ( class_exists( '\\Upress\\EzCache\\Cache' ) ) {
			try {
				$cache = \Upress\EzCache\Cache::instance();
				if ( ! is_object( $cache ) || ! method_exists( $cache, 'clear_cache' ) || false === $cache->clear_cache() ) {
					$success = false;
				}
			} catch ( \Throwable $error ) {
				$success = false;
			}
		}
		try {
			do_action( 'litespeed_purge_all' );
		} catch ( \Throwable $error ) {
			$success = false;
		}
		try {
			if ( false === wp_cache_flush() ) {
				$success = false;
			}
		} catch ( \Throwable $error ) {
			$success = false;
		}
		if ( $success ) {
			self::clear_outbox_error( 'cache' );
		}
		return $success;
	}

	public static function public_status() {
		$readiness = self::readiness();
		$catalog_ready = self::catalog_is_ready();
		$cart_ready = self::cart_is_ready();
		$product_count = $catalog_ready && class_exists( 'Complete99_Live_Catalog' )
			? count( Complete99_Live_Catalog::product_ids() )
			: 0;
		$response = rest_ensure_response(
			array(
				'status'              => $readiness['ready'] ? 'checkout_ready' : ( $catalog_ready ? 'catalog_ready' : 'external_ordering' ),
				'catalog_ready'       => $catalog_ready,
				'cart_ready'          => $cart_ready,
				'checkout_ready'      => $readiness['ready'],
				'product_count'       => $readiness['ready'] ? $readiness['product_count'] : $product_count,
				'current_order_url'   => self::order_url( 'he' ),
				'current_order_urls'  => array(
					'he' => self::order_url( 'he' ),
					'en' => self::order_url( 'en' ),
				),
				'currency'            => $readiness['currency'],
				'storefront_contract' => 'complete99-commerce/v1',
			)
		);
		if ( is_object( $response ) && method_exists( $response, 'header' ) ) {
			$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'Pragma', 'no-cache' );
		}
		return $response;
	}

	public static function private_readiness() {
		return rest_ensure_response( self::readiness() );
	}

	public static function is_ready() {
		return true === self::readiness()['ready'];
	}

	/**
	 * The public product catalog is intentionally independent of checkout.
	 * Electronic payment remains protected by is_ready().
	 */
	public static function catalog_is_ready() {
		return class_exists( 'Complete99_Live_Catalog' ) && Complete99_Live_Catalog::is_ready();
	}

	/**
	 * The editable classic cart is a public catalog surface, not a payment gate.
	 */
	public static function cart_is_ready() {
		if ( ! self::catalog_is_ready() ) {
			return false;
		}
		$cart_id = absint( get_option( 'woocommerce_cart_page_id', 0 ) );
		if ( 1 > $cart_id || 'publish' !== (string) get_post_status( $cart_id ) ) {
			return false;
		}
		$content = trim( (string) get_post_field( 'post_content', $cart_id ) );
		return '[woocommerce_cart]' === $content;
	}

	private static function text_script_counts( $text ) {
		$text   = (string) $text;
		$hebrew = preg_match_all( '/\p{Hebrew}/u', $text, $matches );
		$latin  = preg_match_all( '/\p{Latin}/u', $text, $matches );
		return array(
			'hebrew' => false === $hebrew ? 0 : absint( $hebrew ),
			'latin'  => false === $latin ? 0 : absint( $latin ),
		);
	}

	private static function text_matches_language( $text, $lang, $minimum_expected, $minimum_share = 60 ) {
		$counts   = self::text_script_counts( $text );
		$expected = 'en' === $lang ? $counts['latin'] : $counts['hebrew'];
		$other    = 'en' === $lang ? $counts['hebrew'] : $counts['latin'];
		$total    = $expected + $other;
		return $expected >= absint( $minimum_expected )
			&& 0 < $total
			&& ( 100 * $expected ) >= ( absint( $minimum_share ) * $total );
	}

	private static function product_copy_matches_declared_languages( $product_id ) {
		$product_kind = sanitize_key( (string) get_post_meta( $product_id, self::PRODUCT_KIND, true ) );
		if ( ! in_array( $product_kind, array( 'food', 'equipment' ), true ) ) {
			return false;
		}
		$contracts = array(
			array( self::NAME_HE, 'he', 2, 55 ),
			array( self::NAME_EN, 'en', 2, 55 ),
			array( self::DESCRIPTION_HE, 'he', 12, 60 ),
			array( self::DESCRIPTION_EN, 'en', 12, 60 ),
			array( self::FULFILMENT_HE, 'he', 3, 60 ),
			array( self::FULFILMENT_EN, 'en', 3, 60 ),
			array( self::ORIGIN_HE, 'he', 2, 60 ),
			array( self::ORIGIN_EN, 'en', 2, 60 ),
		);
		if ( 'food' === $product_kind ) {
			$contracts = array_merge(
				$contracts,
				array(
					array( self::INGREDIENTS_HE, 'he', 2, 60 ),
					array( self::INGREDIENTS_EN, 'en', 2, 60 ),
					array( self::ALLERGENS_HE, 'he', 2, 60 ),
					array( self::ALLERGENS_EN, 'en', 2, 60 ),
					array( self::STORAGE_HE, 'he', 3, 60 ),
					array( self::STORAGE_EN, 'en', 3, 60 ),
				)
			);
		} else {
			$contracts = array_merge(
				$contracts,
				array(
					array( self::MODEL_HE, 'he', 2, 45 ),
					array( self::MODEL_EN, 'en', 2, 45 ),
					array( self::MATERIAL_HE, 'he', 2, 60 ),
					array( self::MATERIAL_EN, 'en', 2, 60 ),
					array( self::DIMENSIONS_HE, 'he', 3, 45 ),
					array( self::DIMENSIONS_EN, 'en', 3, 45 ),
					array( self::CARE_HE, 'he', 8, 60 ),
					array( self::CARE_EN, 'en', 8, 60 ),
					array( self::SAFETY_HE, 'he', 6, 60 ),
					array( self::SAFETY_EN, 'en', 6, 60 ),
				)
			);
		}
		foreach ( $contracts as $contract ) {
			list( $key, $lang, $minimum_expected, $minimum_share ) = $contract;
			$value = (string) get_post_meta( $product_id, $key, true );
			$value = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $value ) : strip_tags( $value );
			if ( ! self::text_matches_language( $value, $lang, $minimum_expected, $minimum_share ) ) {
				return false;
			}
		}
		return true;
	}

	public static function storefront_product_ids() {
		if ( self::catalog_is_ready() ) {
			return Complete99_Live_Catalog::product_ids();
		}
		$products = self::approved_products();
		return $products['valid_ids'];
	}

	/**
	 * Public storefront facets. These values are the only accepted URL filter states.
	 *
	 * @return array<string,string>
	 */
	public static function storefront_filter_options() {
		return array(
			'all'             => 'all',
			'pantry'          => 'pantry',
			'japanese-pantry' => 'japanese-pantry',
			'fresh-produce'   => 'fresh-produce',
			'chilled-frozen'  => 'chilled-frozen',
			'bakery'          => 'bakery',
			'equipment'       => 'equipment',
			'regulated'       => 'regulated',
		);
	}

	/**
	 * Read and bound the storefront state from the public query string.
	 *
	 * @return array{product_type:string,product_page:int,query_is_valid:bool,query_is_canonical:bool}
	 */
	public static function storefront_listing_state() {
		$query_keys = array_map( 'strval', array_keys( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$unexpected_query_keys = array_diff( $query_keys, array( 'product-type', 'product-page' ) );
		$has_filter = isset( $_GET['product-type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_filter = $has_filter && is_scalar( $_GET['product-type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? wp_unslash( (string) $_GET['product-type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';
		$filter     = sanitize_key( $raw_filter );
		$filter_ok  = ! $has_filter || ( $raw_filter === $filter && isset( self::storefront_filter_options()[ $filter ] ) );
		if ( ! $filter_ok ) {
			$filter = 'all';
		} elseif ( ! $has_filter ) {
			$filter = 'all';
		}

		$has_page = isset( $_GET['product-page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_page = $has_page && is_scalar( $_GET['product-page'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? wp_unslash( (string) $_GET['product-page'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';
		$page_ok  = ! $has_page || 1 === preg_match( '/^[1-9][0-9]*$/', $raw_page );
		$page     = $page_ok && $has_page ? intval( $raw_page ) : 1;
		$page_ok  = $page_ok && 0 < $page;
		$canonical = empty( $unexpected_query_keys )
			&& $filter_ok
			&& $page_ok
			&& ( ! $has_filter || 'all' !== $filter )
			&& ( ! $has_page || 1 !== $page );

		return array(
			'product_type'       => $filter,
			'product_page'       => 0 < $page ? $page : 1,
			'query_is_valid'     => $filter_ok && $page_ok,
			'query_is_canonical' => $canonical,
		);
	}

	/**
	 * Return one deterministic, server-rendered storefront page.
	 *
	 * @param string|null $product_type Allowlisted product facet, or null for the URL state.
	 * @param int|null    $product_page Requested one-based page, or null for the URL state.
	 * @return array<string,mixed>
	 */
	public static function storefront_listing( $product_type = null, $product_page = null ) {
		$state = self::storefront_listing_state();
		$uses_query_state = null === $product_type && null === $product_page;
		$filter = null === $product_type ? $state['product_type'] : sanitize_key( (string) $product_type );
		$explicit_filter_valid = isset( self::storefront_filter_options()[ $filter ] );
		if ( ! isset( self::storefront_filter_options()[ $filter ] ) ) {
			$filter = 'all';
		}
		$page = null === $product_page ? $state['product_page'] : intval( $product_page );
		$explicit_page_valid = 0 < $page;
		$page = max( 1, $page );
		$requested_page = $page;

		$all_product_ids = array_values( array_map( 'absint', self::storefront_product_ids() ) );
		$matching_ids    = array();
		foreach ( $all_product_ids as $product_id ) {
			$facets = array_values(
				array_unique(
					array_filter(
						array_map(
							'sanitize_key',
							preg_split( '/\s+/', trim( (string) get_post_meta( $product_id, '_complete99_live_catalog_facet', true ) ) ) ?: array()
						)
					)
				)
			);
			if ( 'all' === $filter || in_array( $filter, $facets, true ) ) {
				$matching_ids[] = $product_id;
			}
		}

		$total       = count( $matching_ids );
		$total_pages = max( 1, (int) ceil( $total / self::STOREFRONT_PAGE_SIZE ) );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * self::STOREFRONT_PAGE_SIZE;
		$product_ids = array_slice( $matching_ids, $offset, self::STOREFRONT_PAGE_SIZE );

		return array(
			'product_type'        => $filter,
			'product_page'        => $page,
			'per_page'            => self::STOREFRONT_PAGE_SIZE,
			'total_products'      => $total,
			'total_pages'         => $total_pages,
			'product_ids'         => $product_ids,
			'all_product_ids'     => $all_product_ids,
			'matching_product_ids' => $matching_ids,
			'first_product_number' => $total ? $offset + 1 : 0,
			'last_product_number' => $total ? min( $offset + count( $product_ids ), $total ) : 0,
			'query_is_valid'      => $uses_query_state
				? ( $state['query_is_valid'] && $requested_page <= $total_pages )
				: ( $explicit_filter_valid && $explicit_page_valid && $requested_page <= $total_pages ),
			'query_is_canonical'  => $uses_query_state
				? ( $state['query_is_canonical'] && $requested_page <= $total_pages )
				: ( $explicit_filter_valid && $explicit_page_valid && $requested_page <= $total_pages ),
		);
	}

	/**
	 * Build a stable storefront URL for one allowlisted filter and page.
	 */
	public static function storefront_url( $lang, $filter = 'all', $page = 1, $fragment = '' ) {
		$lang   = 'en' === $lang ? 'en' : 'he';
		$filter = sanitize_key( (string) $filter );
		if ( ! isset( self::storefront_filter_options()[ $filter ] ) ) {
			$filter = 'all';
		}
		$page = intval( $page );
		$page = 0 < $page ? $page : 1;
		$args = array();
		if ( 'all' !== $filter ) {
			$args['product-type'] = $filter;
		}
		if ( 1 < $page ) {
			$args['product-page'] = $page;
		}
		$url = Complete99_Content::route_url( 'store', $lang );
		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}
		$fragment = sanitize_html_class( ltrim( (string) $fragment, '#' ) );
		return '' === $fragment ? $url : $url . '#' . $fragment;
	}

	/**
	 * Resolve a product to the server-rendered page that contains its card.
	 */
	public static function storefront_product_url( $product_code, $lang, $filter = 'all' ) {
		$product_code = sanitize_key( (string) $product_code );
		$filter       = sanitize_key( (string) $filter );
		if ( '' === $product_code ) {
			return self::storefront_url( $lang );
		}

		$product_id = 0;
		foreach ( self::storefront_product_ids() as $candidate_id ) {
			$candidate_code = sanitize_key( (string) get_post_meta( $candidate_id, '_complete99_catalog_product_code', true ) );
			if ( '' !== $candidate_code && hash_equals( $candidate_code, $product_code ) ) {
				$product_id = absint( $candidate_id );
				break;
			}
		}
		if ( ! $product_id ) {
			return self::storefront_url( $lang );
		}

		$listing = self::storefront_listing( $filter, 1 );
		$position = array_search( $product_id, $listing['matching_product_ids'], true );
		if ( false === $position ) {
			$listing  = self::storefront_listing( 'all', 1 );
			$position = array_search( $product_id, $listing['matching_product_ids'], true );
		}
		$page = false === $position ? 1 : (int) floor( $position / self::STOREFRONT_PAGE_SIZE ) + 1;
		return self::storefront_url(
			$lang,
			$listing['product_type'],
			$page,
			'c99-product-code-' . sanitize_html_class( $product_code )
		);
	}

	private static function page_contains_commerce_surface( $post_id, $surface ) {
		$content = (string) get_post_field( 'post_content', absint( $post_id ) );
		if ( '' === trim( $content ) ) {
			return false;
		}
		$contracts = array(
			'cart'     => array( 'woocommerce/cart', 'woocommerce_cart' ),
			'checkout' => array( 'woocommerce/checkout', 'woocommerce_checkout' ),
			'account'  => array( 'woocommerce/my-account', 'woocommerce_my_account' ),
		);
		if ( ! isset( $contracts[ $surface ] ) ) {
			return false;
		}
		list( $block, $shortcode ) = $contracts[ $surface ];
		return ( function_exists( 'has_block' ) && has_block( $block, $content ) )
			|| ( function_exists( 'has_shortcode' ) && has_shortcode( $content, $shortcode ) );
	}

	private static function legal_page_snapshot() {
		$snapshot = array();
		foreach ( array( 'privacy', 'terms', 'accessibility' ) as $key ) {
			foreach ( array( 'he', 'en' ) as $lang ) {
				$post_id = Complete99_Content::find_translation_post_id( $key, $lang, true );
				$post    = $post_id ? get_post( $post_id ) : null;
				if ( ! $post || 'publish' !== $post->post_status ) {
					return array();
				}
				$visible_copy = wp_strip_all_tags(
					(string) $post->post_title . "\n" . (string) $post->post_excerpt . "\n" . (string) $post->post_content
				);
				if ( ! self::text_matches_language( $visible_copy, $lang, 80, 60 ) ) {
					return array();
				}
				$snapshot[ $key . ':' . $lang ] = array(
					'post_id' => (int) $post_id,
					'hash'    => hash(
						'sha256',
						(string) $post->post_title . "\n" . (string) $post->post_excerpt . "\n" . (string) $post->post_content
					),
				);
			}
		}
		return $snapshot;
	}

	private static function legal_receipt() {
		$receipt = get_option( self::OPTION_LEGAL, array() );
		if ( ! is_array( $receipt )
			|| 'complete99-commerce-legal/v1' !== ( $receipt['schema'] ?? '' )
			|| 'passed' !== ( $receipt['status'] ?? '' )
			|| empty( $receipt['accepted_at'] )
			|| 1 > absint( $receipt['accepted_by'] ?? 0 )
			|| empty( $receipt['pages'] )
			|| empty( $receipt['policy_facts'] )
			|| ! is_array( $receipt['pages'] )
			|| ! is_array( $receipt['policy_facts'] ) ) {
			return array();
		}
		$accepted_at = strtotime( (string) $receipt['accepted_at'] );
		if ( false === $accepted_at || $accepted_at > time() + 300 || $accepted_at < time() - self::LEGAL_MAX_AGE ) {
			return array();
		}
		$current = self::legal_page_snapshot();
		if ( empty( $current )
			|| ! hash_equals(
				hash( 'sha256', wp_json_encode( $receipt['pages'] ) ),
				hash( 'sha256', wp_json_encode( $current ) )
			) ) {
			return array();
		}
		return $receipt;
	}

	public static function record_legal_acceptance( WP_REST_Request $request ) {
		$facts = $request->get_param( 'policy_facts' );
		$keys  = array( 'payment_processor', 'payment_methods', 'delivery_and_pickup', 'refund_and_cancellation', 'data_retention', 'support_contact' );
		if ( ! is_array( $facts ) ) {
			return new WP_Error( 'complete99_commerce_legal_facts', 'Bilingual policy facts are required.', array( 'status' => 400 ) );
		}
		$clean = array();
		$seen  = array( 'he' => array(), 'en' => array() );
		foreach ( $keys as $key ) {
			if ( ! isset( $facts[ $key ] ) || ! is_array( $facts[ $key ] ) ) {
				return new WP_Error( 'complete99_commerce_legal_facts', 'Every required policy fact needs Hebrew and English text.', array( 'status' => 422 ) );
			}
			foreach ( array( 'he', 'en' ) as $lang ) {
				$value = sanitize_textarea_field( (string) ( $facts[ $key ][ $lang ] ?? '' ) );
				$length = function_exists( 'mb_strlen' ) ? mb_strlen( trim( $value ), 'UTF-8' ) : strlen( trim( $value ) );
				if ( 12 > $length || 500 < $length ) {
					return new WP_Error( 'complete99_commerce_legal_facts', 'A required policy fact is empty or too long.', array( 'status' => 422 ) );
				}
				if ( ! self::text_matches_language( $value, $lang, 8, 60 ) ) {
					return new WP_Error( 'complete99_commerce_legal_language', 'Each policy fact must be written primarily in its declared language.', array( 'status' => 422 ) );
				}
				$identity = function_exists( 'mb_strtolower' )
					? mb_strtolower( trim( $value ), 'UTF-8' )
					: strtolower( trim( $value ) );
				if ( in_array( $identity, $seen[ $lang ], true ) ) {
					return new WP_Error( 'complete99_commerce_legal_facts', 'Each required policy fact must be distinct.', array( 'status' => 422 ) );
				}
				$seen[ $lang ][] = $identity;
				$clean[ $key ][ $lang ] = $value;
			}
		}

		$pages = self::legal_page_snapshot();
		if ( empty( $pages ) ) {
			return new WP_Error( 'complete99_commerce_legal_pages', 'Published bilingual privacy, terms and accessibility pages are required.', array( 'status' => 422 ) );
		}
		$page_copy = array();
		foreach ( $pages as $identity => $page ) {
			list( $page_key, $lang ) = array_pad( explode( ':', $identity, 2 ), 2, '' );
			$post = get_post( absint( $page['post_id'] ) );
			if ( $post && in_array( $page_key, array( 'privacy', 'terms', 'accessibility' ), true )
				&& in_array( $lang, array( 'he', 'en' ), true ) ) {
				$page_copy[ $page_key ][ $lang ] = wp_strip_all_tags(
					(string) $post->post_title . "\n" . (string) $post->post_excerpt . "\n" . (string) $post->post_content
				);
			}
		}
		$held_markers = array(
			'he' => array(
				'האתר הציבורי אינו מבקש פרטי תשלום',
				'כפתור ההזמנה מוביל לשירות חיצוני',
				'ההזמנה מתבצעת כרגע באתר חיצוני',
				'אין באתר סל, תשלום או עסקת מכר',
				'כפתור ההזמנה פותח אתר שאינו מופעל בתוך האתר הזה',
			),
			'en' => array(
				'public site does not ask for payment details',
				'ordering button opens an external service',
				'orders are currently completed on an external website',
				'this site has no cart, payment or sale transaction',
				'ordering button opens a website that is not operated inside this site',
			),
		);
		foreach ( $held_markers as $lang => $markers ) {
			$combined = implode( "\n", array_column( $page_copy, $lang ) );
			foreach ( $markers as $marker ) {
				$found = function_exists( 'mb_stripos' )
					? false !== mb_stripos( $combined, $marker, 0, 'UTF-8' )
					: false !== stripos( $combined, $marker );
				if ( $found ) {
					return new WP_Error(
						'complete99_commerce_legal_hold',
						'The public legal copy still describes the held external-ordering state.',
						array( 'status' => 422 )
					);
				}
			}
		}
		$fact_pages = array(
			'payment_processor'        => 'privacy',
			'payment_methods'          => 'terms',
			'delivery_and_pickup'      => 'terms',
			'refund_and_cancellation'  => 'terms',
			'data_retention'           => 'privacy',
			'support_contact'          => 'accessibility',
		);
		foreach ( $clean as $key => $fact ) {
			$page_key = $fact_pages[ $key ];
			foreach ( array( 'he', 'en' ) as $lang ) {
				$found = function_exists( 'mb_stripos' )
					? false !== mb_stripos( (string) ( $page_copy[ $page_key ][ $lang ] ?? '' ), $fact[ $lang ], 0, 'UTF-8' )
					: false !== stripos( (string) ( $page_copy[ $page_key ][ $lang ] ?? '' ), $fact[ $lang ] );
				if ( ! $found ) {
					return new WP_Error( 'complete99_commerce_legal_copy', 'Every accepted policy fact must appear on its matching public policy page.', array( 'status' => 422 ) );
				}
			}
		}

		$receipt = array(
			'schema'       => 'complete99-commerce-legal/v1',
			'status'       => 'passed',
			'accepted_at'  => gmdate( 'c' ),
			'accepted_by'  => function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0,
			'pages'        => $pages,
			'policy_facts' => $clean,
		);
		update_option( self::OPTION_LEGAL, $receipt, false );
		self::invalidate_commerce_state_cache();
		$stored = self::legal_receipt();
		if ( empty( $stored ) ) {
			return new WP_Error( 'complete99_commerce_legal_readback', 'The legal acceptance receipt could not be verified.', array( 'status' => 500 ) );
		}
		return rest_ensure_response( $stored );
	}

	private static function shopper_list_feature_is_enabled( $feature_id ) {
		$feature_id = sanitize_key( (string) $feature_id );
		if ( ! in_array( $feature_id, array( 'cart_save_for_later', 'product_wishlist' ), true ) ) {
			return true;
		}
		$features_util = '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil';
		if ( class_exists( $features_util ) && is_callable( array( $features_util, 'feature_is_enabled' ) ) ) {
			try {
				return (bool) $features_util::feature_is_enabled( $feature_id );
			} catch ( \Throwable $error ) {
				return true;
			}
		}
		$fallback_options = array(
			'cart_save_for_later' => 'woocommerce_cart_save_for_later_enabled',
			'product_wishlist'    => 'woocommerce_product_wishlist_enabled',
		);
		return 'yes' === (string) get_option( $fallback_options[ $feature_id ], 'no' );
	}

	private static function readiness( $assume_enabled = false, $assume_store_index_contract = false, $assume_ever_launched = false ) {
		$use_cache = ! $assume_enabled && ! $assume_store_index_contract && ! $assume_ever_launched;
		if ( $use_cache && is_array( self::$readiness_cache ) ) {
			return self::$readiness_cache;
		}
		$missing       = array();
		$woo_active    = class_exists( 'WooCommerce' ) && function_exists( 'WC' );
		$product_count = 0;
		$currency      = 'ILS';
		$policy_pages  = array();

		if ( ! $woo_active ) {
			$missing[] = 'woocommerce_dependency';
		} else {
			$products      = self::approved_products();
			$product_count = count( $products['valid_ids'] );
			$currency      = function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : '';
			if ( 1 > count( $products['reviewed_ids'] ) ) {
				$missing[] = 'approved_products';
			}
			if ( 1 > $product_count ) {
				$missing[] = 'product_readiness';
			}
			if ( ! empty( $products['invalid_ids'] ) ) {
				$missing[] = 'invalid_approved_products';
			}
			if ( 'ILS' !== $currency ) {
				$missing[] = 'store_currency';
			}
			if ( self::shopper_list_feature_is_enabled( 'cart_save_for_later' )
				|| self::shopper_list_feature_is_enabled( 'product_wishlist' ) ) {
				$missing[] = 'shopper_lists_disabled';
			}
			if ( '' === trim( (string) get_option( 'woocommerce_store_address', '' ) )
				|| '' === trim( (string) get_option( 'woocommerce_default_country', '' ) ) ) {
				$missing[] = 'merchant_address';
			}
			$required_pages = array(
				'shop_page'     => absint( get_option( 'woocommerce_shop_page_id', 0 ) ),
				'terms_page'    => absint( get_option( 'woocommerce_terms_page_id', 0 ) ),
				'privacy_page'  => absint( get_option( 'wp_page_for_privacy_policy', 0 ) ),
				'cart_page'     => absint( get_option( 'woocommerce_cart_page_id', 0 ) ),
				'checkout_page' => absint( get_option( 'woocommerce_checkout_page_id', 0 ) ),
				'account_page'  => absint( get_option( 'woocommerce_myaccount_page_id', 0 ) ),
			);
			foreach ( $required_pages as $requirement => $post_id ) {
				if ( 1 > $post_id || 'publish' !== get_post_status( $post_id ) ) {
					$missing[] = $requirement;
				}
			}
			foreach ( array( 'he', 'en' ) as $policy_lang ) {
				foreach ( array( 'terms', 'privacy' ) as $policy_key ) {
					$policy_id = Complete99_Content::find_translation_post_id( $policy_key, $policy_lang, true );
					$policy_pages[ $policy_lang ][ $policy_key ] = $policy_id;
					if ( 1 > $policy_id || 'publish' !== get_post_status( $policy_id ) ) {
						$missing[] = 'localized_policy_pages';
					}
				}
			}
			foreach ( array( 'he', 'en' ) as $store_lang ) {
				$store_id = Complete99_Content::find_translation_post_id( 'store', $store_lang, true );
				if ( 1 > $store_id
					|| 'publish' !== get_post_status( $store_id )
					|| ( ! $assume_store_index_contract
						&& ( ! rest_sanitize_boolean( get_post_meta( $store_id, '_complete99_index_eligible', true ) )
							|| 'launch_ready' !== (string) get_post_meta( $store_id, '_complete99_verification_state', true ) ) ) ) {
					$missing[] = 'store_index_contract';
				}
			}
			if ( 0 < $required_pages['cart_page']
				&& ! self::page_contains_commerce_surface( $required_pages['cart_page'], 'cart' ) ) {
				$missing[] = 'cart_surface';
			}
			if ( 0 < $required_pages['checkout_page']
				&& ! self::page_contains_commerce_surface( $required_pages['checkout_page'], 'checkout' ) ) {
				$missing[] = 'checkout_surface';
			}
			if ( 0 < $required_pages['account_page']
				&& ! self::page_contains_commerce_surface( $required_pages['account_page'], 'account' ) ) {
				$missing[] = 'account_surface';
			}

			$gateways = WC()->payment_gateways();
			$enabled  = array();
			if ( $gateways && method_exists( $gateways, 'payment_gateways' ) ) {
				foreach ( $gateways->payment_gateways() as $gateway ) {
					if ( is_object( $gateway )
						&& 'yes' === (string) $gateway->enabled
						&& method_exists( $gateway, 'supports' )
						&& $gateway->supports( 'refunds' )
						&& self::gateway_is_live_mode( $gateway ) ) {
						$enabled[] = $gateway;
					}
				}
			}
			if ( empty( $enabled ) ) {
				$missing[] = 'payment_gateway';
			}

			$shipping_methods = array();
			if ( class_exists( 'WC_Shipping_Zones' ) ) {
				foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
					if ( isset( $zone['shipping_methods'] ) && is_array( $zone['shipping_methods'] ) ) {
						$shipping_methods = array_merge( $shipping_methods, $zone['shipping_methods'] );
					}
				}
				if ( class_exists( 'WC_Shipping_Zone' ) ) {
					$rest_of_world   = new WC_Shipping_Zone( 0 );
					$shipping_methods = array_merge( $shipping_methods, $rest_of_world->get_shipping_methods( true ) );
				}
			}
			$shipping_methods = array_filter(
				$shipping_methods,
				static function ( $method ) {
					if ( ! is_object( $method ) ) {
						return false;
					}
					if ( method_exists( $method, 'is_enabled' ) ) {
						return $method->is_enabled();
					}
					return isset( $method->enabled ) && 'yes' === (string) $method->enabled;
				}
			);
			if ( empty( $shipping_methods ) ) {
				$missing[] = 'fulfilment_method';
			}
			if ( ! function_exists( 'is_ssl' ) || ! is_ssl() ) {
				$missing[] = 'secure_checkout';
			}
			if ( empty( self::acceptance_receipt() ) ) {
				$missing[] = 'checkout_acceptance';
			}
			if ( empty( self::legal_receipt() ) ) {
				$missing[] = 'consumer_legal_acceptance';
			}
		}

		if ( ! $assume_enabled ) {
			if ( 'yes' !== (string) get_option( self::OPTION_ENABLED, 'no' ) ) {
				$missing[] = 'explicit_live_enablement';
			} elseif ( ! self::launch_audit_is_valid() ) {
				$missing[] = 'launch_audit_integrity';
			}
		}
		if ( ! $assume_ever_launched && 'yes' !== (string) get_option( self::OPTION_EVER_LAUNCHED, 'no' ) ) {
			$missing[] = 'customer_continuity_state';
		}
		if ( 'yes' === (string) get_option( self::OPTION_PREVIEW, 'no' ) ) {
			$missing[] = 'acceptance_preview_enabled';
		}
		$raw_outbox   = get_option( self::OPTION_OUTBOX, array() );
		$outbox_count = count( self::outbox() );
		$outbox_failures = self::outbox_failures();
		$outbox_errors = self::outbox_errors();
		if ( ! is_array( $raw_outbox )
			|| count( $raw_outbox ) !== $outbox_count
			|| 450 <= $outbox_count
			|| ! empty( $outbox_failures )
			|| ! empty( $outbox_errors )
			|| self::$outbox_corruption_detected ) {
			$missing[] = 'operations_outbox_backpressure';
		}

		$missing = array_values( array_unique( $missing ) );
		$result = array(
			'ready'                  => empty( $missing ),
			'woocommerce_active'     => $woo_active,
			'product_count'          => $product_count,
			'currency'               => $currency,
			'policy_page_ids'        => $policy_pages,
			'missing_requirements'   => $missing,
			'outbox_event_count'     => $outbox_count,
			'outbox_failure_count'   => count( $outbox_failures ),
			'worker_assignment_mode' => 'unassigned_infrastructure',
			'inventory_authority'    => $woo_active ? 'woocommerce' : 'not_configured',
			'order_handoff'          => self::OUTBOX_SCHEMA,
		);
		if ( $use_cache ) {
			self::$readiness_cache = $result;
		}
		return $result;
	}

	private static function approved_products() {
		if ( is_array( self::$approved_products_cache ) ) {
			return self::$approved_products_cache;
		}
		$result = array(
			'reviewed_ids' => array(),
			'valid_ids'    => array(),
			'invalid_ids'  => array(),
		);
		if ( ! function_exists( 'get_posts' ) || ! function_exists( 'wc_get_product' ) ) {
			self::$approved_products_cache = $result;
			return self::$approved_products_cache;
		}
		$ids = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'meta_key'               => self::PRODUCT_APPROVED,
				'meta_value'             => 'yes',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);
		if ( ! is_array( $ids ) ) {
			self::$approved_products_cache = $result;
			return self::$approved_products_cache;
		}
		$previous_evaluation         = self::$evaluating_readiness;
		self::$evaluating_readiness = true;
		try {
			foreach ( array_map( 'absint', $ids ) as $product_id ) {
				$result['reviewed_ids'][] = $product_id;
				$product = wc_get_product( $product_id );
				$stock = is_object( $product ) ? $product->get_stock_quantity() : null;
				$valid = is_object( $product )
					&& method_exists( $product, 'is_visible' )
					&& self::product_passes_static_acceptance_contract( $product_id )
					&& $product->is_visible()
					&& $product->is_purchasable()
					&& is_numeric( $stock )
					&& 0 < (float) $stock
					&& $product->is_in_stock();
				$result[ $valid ? 'valid_ids' : 'invalid_ids' ][] = $product_id;
			}
		} finally {
			self::$evaluating_readiness = $previous_evaluation;
		}
		self::$approved_products_cache = $result;
		return self::$approved_products_cache;
	}

	private static function allowed_product_ids() {
		if ( self::catalog_is_ready() ) {
			return Complete99_Live_Catalog::product_ids();
		}
		return self::approved_products()['valid_ids'];
	}

	private static function is_allowed_product_id( $product_id ) {
		$product_id = absint( $product_id );
		if ( 1 > $product_id || ( ! self::catalog_is_ready() && ! self::is_ready() && ! self::can_preview_commerce() ) ) {
			return false;
		}
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		$parent  = $product && method_exists( $product, 'get_parent_id' ) ? absint( $product->get_parent_id() ) : 0;
		$root_id = $parent ? $parent : $product_id;
		return in_array( $root_id, self::allowed_product_ids(), true );
	}

	public static function gate_product_visibility( $visible, $product_id ) {
		if ( self::$evaluating_readiness ) {
			return $visible;
		}
		return (bool) $visible && self::is_allowed_product_id( $product_id );
	}

	public static function gate_product_purchasability( $purchasable, $product ) {
		if ( self::$evaluating_readiness ) {
			return $purchasable;
		}
		$product_id = is_object( $product ) && method_exists( $product, 'get_id' ) ? $product->get_id() : 0;
		return (bool) $purchasable && self::cart_is_ready() && self::is_allowed_product_id( $product_id );
	}

	public static function disable_woocommerce_auto_update( $update, $item ) {
		$plugin = is_object( $item ) && isset( $item->plugin ) ? (string) $item->plugin : '';
		$slug   = is_object( $item ) && isset( $item->slug ) ? (string) $item->slug : '';
		if ( 'woocommerce/woocommerce.php' === $plugin || 'woocommerce' === $slug ) {
			return false;
		}
		return $update;
	}

	public static function noindex_native_woocommerce_routes( $robots ) {
		if ( self::is_public_woocommerce_route() ) {
			unset( $robots['index'] );
			$robots['noindex']  = true;
			$robots['nofollow'] = false;
		}
		return $robots;
	}

	public static function enforce_commerce_no_cache() {
		$post_id = function_exists( 'get_queried_object_id' ) ? absint( get_queried_object_id() ) : 0;
		$page_key = 0 < $post_id
			? (string) get_post_meta( $post_id, '_complete99_translation_key', true )
			: '';
		if ( ! in_array( $page_key, array( 'home', 'store' ), true ) && ! self::is_public_woocommerce_route() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}
		if ( ! headers_sent() ) {
			header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
			header( 'Pragma: no-cache', true );
		}
	}

	public static function gate_store_api( $result, $server, $request ) {
		if ( null !== $result ) {
			return $result;
		}
		if ( ! $request instanceof WP_REST_Request ) {
			return $result;
		}
		$route             = (string) $request->get_route();
		$is_store          = 1 === preg_match( '#^/wc/store(?:/v\d+)?(?:/|$)#', $route );
		$is_store_products = 1 === preg_match( '#^/wc/store(?:/v\d+)?/(?:batch|products|shopper-lists)(?:/|$)#', $route );
		$is_core_product   = 1 === preg_match(
			'#^/wp/v2/(?:product|product_variation|product_cat|product_tag|product_brand|product_shipping_class|pa_[^/]+)(?:/|$)#',
			$route
		);
		$is_core_media     = 1 === preg_match( '#^/wp/v2/media(?:/|$)#', $route );
		$is_core_search    = 1 === preg_match( '#^/wp/v2/search(?:/|$)#', $route );
		$search_subtypes   = $is_core_search ? $request->get_param( 'subtype' ) : array();
		$search_subtypes   = is_array( $search_subtypes ) ? $search_subtypes : array( $search_subtypes );
		$search_subtypes   = array_values( array_filter( array_map( 'sanitize_key', $search_subtypes ) ) );
		$product_subtypes  = array( 'product', 'product_variation' );
		$is_product_search = $is_core_search
			&& ! empty( $search_subtypes )
			&& empty( array_diff( $search_subtypes, $product_subtypes ) );
		$is_product_oembed = false;
		if ( 1 === preg_match( '#^/oembed/1\.0/embed(?:/|$)#', $route )
			&& function_exists( 'url_to_postid' )
			&& function_exists( 'get_post_type' ) ) {
			$embed_url = esc_url_raw( (string) $request->get_param( 'url' ) );
			$embed_id  = $embed_url ? absint( url_to_postid( $embed_url ) ) : 0;
			$is_product_oembed = 0 < $embed_id
				&& ( in_array( get_post_type( $embed_id ), array( 'product', 'product_variation' ), true )
					|| ( 'attachment' === get_post_type( $embed_id ) && self::attachment_is_approved_product_image( $embed_id ) ) );
		}
		if ( ! $is_store && ! $is_core_product && ! $is_core_media && ! $is_product_search && ! $is_product_oembed ) {
			return $result;
		}
		if ( ( $is_core_product || $is_core_media || $is_product_search || $is_product_oembed ) && self::can_manage_commerce() ) {
			return $result;
		}
		if ( ! self::is_ready() && ! self::can_preview_commerce() ) {
			return new WP_Error(
				'complete99_commerce_held',
				'The Complete99 on-site store is not open.',
				array( 'status' => 503 )
			);
		}
		if ( $is_store || $is_store_products || $is_core_product || $is_core_media || $is_product_search || $is_product_oembed ) {
			return new WP_Error( 'complete99_commerce_route_held', 'This public commerce route is not exposed.', array( 'status' => 404 ) );
		}
		return $result;
	}

	public static function exclude_products_from_rest_search( $query_args, $request ) {
		if ( self::can_manage_commerce() ) {
			return $query_args;
		}
		$post_types = isset( $query_args['post_type'] ) ? (array) $query_args['post_type'] : array();
		if ( empty( $post_types ) || in_array( 'any', $post_types, true ) ) {
			$post_types = function_exists( 'get_post_types' )
				? array_values( get_post_types( array( 'exclude_from_search' => false ), 'names' ) )
				: array( 'post', 'page', 'c99_dish' );
		}
		$post_types = array_values( array_diff( $post_types, array( 'product', 'product_variation' ) ) );
		$query_args['post_type'] = empty( $post_types ) ? array( 'complete99_no_public_product_results' ) : $post_types;
		return $query_args;
	}

	private static function is_public_woocommerce_route() {
		$attachment_id = function_exists( 'is_attachment' ) && is_attachment() && function_exists( 'get_queried_object_id' )
			? absint( get_queried_object_id() )
			: 0;
		if ( 0 < $attachment_id && self::attachment_is_approved_product_image( $attachment_id ) ) {
			return true;
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			return true;
		}
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			return true;
		}
		return self::is_transaction_page();
	}

	public static function is_transaction_page() {
		return ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() );
	}

	public static function transaction_page_type() {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return 'checkout';
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return 'account';
		}
		return 'cart';
	}

	private static function requested_transaction_language() {
		if ( ! isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		$lang = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $lang, array( 'he', 'en' ), true ) ? $lang : '';
	}

	public static function remember_transaction_language() {
		$lang = self::requested_transaction_language();
		if ( '' === $lang ) {
			return;
		}
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			WC()->session->set( 'complete99_transaction_language', $lang );
		}
		if ( function_exists( 'is_user_logged_in' )
			&& is_user_logged_in()
			&& function_exists( 'get_current_user_id' ) ) {
			self::store_customer_language( get_current_user_id(), $lang );
		}
	}

	private static function store_customer_language( $customer_id, $lang ) {
		$customer_id = absint( $customer_id );
		$lang        = 'en' === $lang ? 'en' : 'he';
		if ( 1 > $customer_id || ! function_exists( 'update_user_meta' ) ) {
			return false;
		}
		update_user_meta( $customer_id, self::CUSTOMER_LANGUAGE, $lang );
		update_user_meta( $customer_id, 'locale', 'en' === $lang ? 'en_US' : 'he_IL' );
		return true;
	}

	public static function remember_customer_language( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		return self::store_customer_language( $customer_id, self::transaction_language() );
	}

	private static function customer_language_for_user( $user_id ) {
		$user_id = absint( $user_id );
		if ( 1 > $user_id || ! function_exists( 'get_user_meta' ) ) {
			return '';
		}
		$lang = sanitize_key( (string) get_user_meta( $user_id, self::CUSTOMER_LANGUAGE, true ) );
		if ( in_array( $lang, array( 'he', 'en' ), true ) ) {
			return $lang;
		}
		$locale = (string) get_user_meta( $user_id, 'locale', true );
		return 0 === strpos( $locale, 'en_' ) ? 'en' : '';
	}

	public static function prepare_new_account_email_language( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		$lang = self::customer_language_for_user( $customer_id );
		self::$pending_customer_email_languages['customer_new_account'] = '' !== $lang
			? $lang
			: self::transaction_language();
	}

	public static function prepare_reset_password_email_language( $user_login, $reset_key = '' ) {
		$user = function_exists( 'get_user_by' ) ? get_user_by( 'login', (string) $user_login ) : false;
		$lang = $user && isset( $user->ID ) ? self::customer_language_for_user( $user->ID ) : '';
		self::$pending_customer_email_languages['customer_reset_password'] = '' !== $lang
			? $lang
			: self::transaction_language();
	}

	public static function prepare_customer_order_email_language( $first, $second = false, $third = false, $fourth = false ) {
		$args  = array( $first, $second, $third, $fourth );
		$order = false;
		foreach ( $args as $candidate ) {
			if ( is_object( $candidate )
				&& is_a( $candidate, 'WC_Order' )
				&& ! is_a( $candidate, 'WC_Order_Refund' ) ) {
				$order = $candidate;
				break;
			}
		}
		$order_id = is_array( $first ) ? absint( $first['order_id'] ?? 0 ) : absint( $first );
		if ( ! $order && 0 < $order_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}
		$lang = self::order_language( $order );
		if ( ! in_array( $lang, array( 'he', 'en' ), true ) ) {
			return false;
		}
		$action = function_exists( 'current_action' ) ? (string) current_action() : '';
		$email_id = '';
		if ( false !== strpos( $action, '_to_processing_notification' ) ) {
			$email_id = 'customer_processing_order';
		} elseif ( 'woocommerce_order_status_completed_notification' === $action ) {
			$email_id = 'customer_completed_order';
		} elseif ( false !== strpos( $action, '_to_on-hold_notification' ) ) {
			$email_id = 'customer_on_hold_order';
		} elseif ( false !== strpos( $action, '_to_cancelled_notification' ) ) {
			$email_id = 'customer_cancelled_order';
		} elseif ( 'woocommerce_order_status_failed_notification' === $action ) {
			$email_id = 'customer_failed_order';
		} elseif ( 'woocommerce_order_fully_refunded_notification' === $action ) {
			$email_id = 'customer_refunded_order';
		} elseif ( 'woocommerce_order_partially_refunded_notification' === $action ) {
			$email_id = 'customer_partially_refunded_order';
		} elseif ( 'woocommerce_send_review_request_notification' === $action ) {
			$email_id = 'customer_review_request';
		} elseif ( 'woocommerce_new_customer_note_notification' === $action ) {
			$email_id = 'customer_note';
		} elseif ( 'woocommerce_fulfillment_created_notification' === $action ) {
			$email_id = 'customer_fulfillment_created';
		} elseif ( 'woocommerce_fulfillment_updated_notification' === $action ) {
			$email_id = 'customer_fulfillment_updated';
		} elseif ( 'woocommerce_fulfillment_deleted_notification' === $action ) {
			$email_id = 'customer_fulfillment_deleted';
		} elseif ( 'woocommerce_before_resend_order_emails' === $action ) {
			$email_id = sanitize_key( (string) $second );
		}
		if ( '' === $email_id ) {
			return false;
		}
		self::$pending_customer_email_languages[ $email_id ] = $lang;
		if ( in_array( $email_id, array( 'customer_refunded_order', 'customer_partially_refunded_order' ), true ) ) {
			self::$pending_customer_email_languages['customer_refunded_order'] = $lang;
			self::$pending_customer_email_languages['customer_partially_refunded_order'] = $lang;
		}
		return true;
	}

	private static function order_language( $order ) {
		if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( absint( $order ) );
		}
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return '';
		}
		$lang = sanitize_key( (string) $order->get_meta( self::ORDER_LANGUAGE, true ) );
		return in_array( $lang, array( 'he', 'en' ), true ) ? $lang : '';
	}

	private static function current_transaction_order() {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$order_id = 0;
		if ( function_exists( 'get_query_var' ) ) {
			foreach ( array( 'order-received', 'order-pay', 'view-order' ) as $query_var ) {
				$value = absint( get_query_var( $query_var, 0 ) );
				if ( 0 < $value ) {
					$order_id = $value;
					break;
				}
			}
		}
		if ( 1 > $order_id ) {
			return false;
		}
		$order = wc_get_order( $order_id );
		return self::customer_can_access_order( $order ) ? $order : false;
	}

	private static function customer_can_access_order( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_order_key' ) ) {
			return false;
		}
		if ( self::can_manage_commerce() ) {
			return true;
		}
		$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $key && hash_equals( (string) $order->get_order_key(), $key ) ) {
			return true;
		}
		$customer_id = method_exists( $order, 'get_customer_id' ) ? absint( $order->get_customer_id() ) : 0;
		return 0 < $customer_id
			&& function_exists( 'get_current_user_id' )
			&& $customer_id === absint( get_current_user_id() );
	}

	public static function transaction_language( $order = null ) {
		$order_lang = self::order_language( $order );
		if ( '' === $order_lang && null === $order ) {
			$order_lang = self::order_language( self::current_transaction_order() );
		}
		if ( '' !== $order_lang ) {
			return $order_lang;
		}
		$requested = self::requested_transaction_language();
		if ( '' !== $requested ) {
			return $requested;
		}
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			$session_lang = sanitize_key( (string) WC()->session->get( 'complete99_transaction_language', '' ) );
			if ( in_array( $session_lang, array( 'he', 'en' ), true ) ) {
				return $session_lang;
			}
		}
		return 'he';
	}

	public static function retain_transaction_language_in_url( $url ) {
		if ( '' === (string) $url ) {
			return $url;
		}
		return add_query_arg( 'lang', self::transaction_language(), $url );
	}

	public static function retain_order_language_in_url( $url, $order ) {
		if ( '' === (string) $url ) {
			return $url;
		}
		return add_query_arg( 'lang', self::transaction_language( $order ), $url );
	}

	private static function localized_policy_page_id( $page_key, $fallback_id ) {
		$lang = self::transaction_language();
		$page_id = Complete99_Content::find_translation_post_id( $page_key, $lang, true );
		return 0 < $page_id && 'publish' === get_post_status( $page_id )
			? $page_id
			: absint( $fallback_id );
	}

	public static function localized_terms_page_id( $page_id ) {
		return self::localized_policy_page_id( 'terms', $page_id );
	}

	public static function localized_privacy_page_id( $page_id ) {
		return self::localized_policy_page_id( 'privacy', $page_id );
	}

	private static function localized_checkout_policy_copy( $lang ) {
		if ( 'en' === $lang ) {
			return array(
				'terms'        => 'I have read and agree to the website [terms].',
				'checkout'     => 'We use your personal data to process and support your order, as explained in our [privacy_policy].',
				'registration' => 'We use your personal data to manage your account and support your experience, as explained in our [privacy_policy].',
			);
		}
		return array(
			'terms'        => 'קראתי ואני מסכים/ה ל[terms] של האתר.',
			'checkout'     => 'הפרטים האישיים שלך ישמשו לטיפול בהזמנה ולתמיכה בה, כמפורט ב[privacy_policy].',
			'registration' => 'הפרטים האישיים שלך ישמשו לניהול החשבון ולתמיכה בחוויה באתר, כמפורט ב[privacy_policy].',
		);
	}

	public static function localized_terms_checkbox_text( $text ) {
		$copy = self::localized_checkout_policy_copy( self::transaction_language() );
		return $copy['terms'];
	}

	public static function localized_privacy_policy_text( $text, $type = '' ) {
		$copy = self::localized_checkout_policy_copy( self::transaction_language() );
		return 'registration' === $type ? $copy['registration'] : $copy['checkout'];
	}

	public static function can_access_customer_continuity() {
		if ( self::can_preview_commerce() ) {
			return true;
		}
		if ( 'yes' !== (string) get_option( self::OPTION_EVER_LAUNCHED, 'no' ) ) {
			return false;
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return false !== self::current_transaction_order();
		}
		return false;
	}

	public static function transaction_url( $surface, $lang ) {
		$url = '';
		if ( 'checkout' === $surface && function_exists( 'wc_get_checkout_url' ) ) {
			$url = wc_get_checkout_url();
		} elseif ( 'account' === $surface && function_exists( 'wc_get_page_permalink' ) ) {
			$url = wc_get_page_permalink( 'myaccount' );
		} elseif ( function_exists( 'wc_get_cart_url' ) ) {
			$url = wc_get_cart_url();
		}
		return add_query_arg( 'lang', 'en' === $lang ? 'en' : 'he', $url );
	}

	private static function is_customer_commerce_context() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		return ! function_exists( 'is_admin' ) || ! is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() );
	}

	private static function localized_product_name_value( $product, $lang, $fallback = '' ) {
		if ( is_numeric( $product ) && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( absint( $product ) );
		}
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return $fallback;
		}
		$product_id = absint( $product->get_id() );
		$parent_id  = method_exists( $product, 'get_parent_id' ) ? absint( $product->get_parent_id() ) : 0;
		$source_id  = $parent_id ? $parent_id : $product_id;
		$meta_key   = 'en' === $lang ? self::NAME_EN : self::NAME_HE;
		$name       = trim( (string) get_post_meta( $source_id, $meta_key, true ) );
		return '' !== $name ? $name : $fallback;
	}

	public static function localize_product_name( $name, $product ) {
		if ( ! self::is_customer_commerce_context() ) {
			return $name;
		}
		return self::localized_product_name_value( $product, self::transaction_language(), $name );
	}

	public static function localize_cart_item_name( $name, $cart_item, $cart_item_key = '' ) {
		$product = is_array( $cart_item ) && isset( $cart_item['data'] ) ? $cart_item['data'] : false;
		return self::localized_product_name_value( $product, self::transaction_language(), $name );
	}

	private static function apply_bilingual_line_item_names( $item, $product, $lang ) {
		if ( ! is_object( $item ) || ! method_exists( $item, 'update_meta_data' ) ) {
			return;
		}
		$fallback = method_exists( $item, 'get_name' ) ? (string) $item->get_name() : '';
		$name_he  = self::localized_product_name_value( $product, 'he', $fallback );
		$name_en  = self::localized_product_name_value( $product, 'en', $fallback );
		$item->update_meta_data( self::ITEM_NAME_HE, $name_he );
		$item->update_meta_data( self::ITEM_NAME_EN, $name_en );
		if ( method_exists( $item, 'set_name' ) ) {
			$item->set_name( 'en' === $lang ? $name_en : $name_he );
		}
	}

	public static function localize_checkout_line_item( $item, $cart_item_key, $values, $order ) {
		$product = is_array( $values ) && isset( $values['data'] ) ? $values['data'] : false;
		self::apply_bilingual_line_item_names( $item, $product, self::transaction_language( $order ) );
	}

	private static function remember_order_language( $order, $persist_items = false ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return;
		}
		$lang = self::transaction_language( $order );
		$order->update_meta_data( self::ORDER_LANGUAGE, $lang );
		if ( $persist_items && method_exists( $order, 'get_items' ) ) {
			foreach ( $order->get_items() as $item ) {
				$product = is_object( $item ) && method_exists( $item, 'get_product' ) ? $item->get_product() : false;
				self::apply_bilingual_line_item_names( $item, $product, $lang );
				if ( is_object( $item ) && method_exists( $item, 'save' ) ) {
					$item->save();
				}
			}
		}
		if ( $persist_items && method_exists( $order, 'save_meta_data' ) ) {
			$order->save_meta_data();
		}
	}

	public static function remember_checkout_order_language( $order, $data = array() ) {
		self::remember_order_language( $order, false );
	}

	public static function remember_store_api_order_language( $order ) {
		self::remember_order_language( $order, true );
	}

	public static function localize_order_item_name( $name, $item, $is_visible = true ) {
		if ( ! is_object( $item ) || ! method_exists( $item, 'get_meta' ) ) {
			return $name;
		}
		$order = method_exists( $item, 'get_order' ) ? $item->get_order() : false;
		$lang  = self::transaction_language( $order );
		$key   = 'en' === $lang ? self::ITEM_NAME_EN : self::ITEM_NAME_HE;
		$value = trim( (string) $item->get_meta( $key, true ) );
		return '' !== $value ? $value : $name;
	}

	public static function hide_bilingual_order_item_meta( $hidden ) {
		$hidden = is_array( $hidden ) ? $hidden : array();
		$hidden[] = self::ITEM_NAME_HE;
		$hidden[] = self::ITEM_NAME_EN;
		return array_values( array_unique( $hidden ) );
	}

	private static function customer_email_order( $email ) {
		if ( ! is_object( $email )
			|| ! method_exists( $email, 'is_customer_email' )
			|| ! $email->is_customer_email()
			|| ! isset( $email->object )
			|| ! is_object( $email->object )
			|| ! is_a( $email->object, 'WC_Order' ) ) {
			return false;
		}
		return $email->object;
	}

	private static function customer_email_language( $email ) {
		if ( ! is_object( $email )
			|| ! method_exists( $email, 'is_customer_email' )
			|| ! $email->is_customer_email() ) {
			return '';
		}
		$email_id = sanitize_key( (string) ( $email->id ?? '' ) );
		if ( isset( self::$pending_customer_email_languages[ $email_id ] ) ) {
			return self::$pending_customer_email_languages[ $email_id ];
		}
		if ( ! isset( $email->object ) || ! is_object( $email->object ) ) {
			return in_array( $email_id, array( 'customer_new_account', 'customer_reset_password' ), true )
				? self::transaction_language()
				: '';
		}
		if ( is_a( $email->object, 'WC_Order' ) ) {
			return self::transaction_language( $email->object );
		}
		$user_id = isset( $email->object->ID ) ? absint( $email->object->ID ) : 0;
		if ( 1 > $user_id || ! function_exists( 'get_user_meta' ) ) {
			return '';
		}
		$lang = sanitize_key( (string) get_user_meta( $user_id, self::CUSTOMER_LANGUAGE, true ) );
		if ( in_array( $lang, array( 'he', 'en' ), true ) ) {
			return $lang;
		}
		$locale = (string) get_user_meta( $user_id, 'locale', true );
		return 0 === strpos( $locale, 'en_' ) ? 'en' : 'he';
	}

	public static function switch_customer_email_locale( $allowed, $email ) {
		$lang = self::customer_email_language( $email );
		if ( '' === $lang || ! function_exists( 'switch_to_locale' ) ) {
			return $allowed;
		}
		$hash   = spl_object_hash( $email );
		$locale = 'en' === $lang ? 'en_US' : 'he_IL';
		$active = function_exists( 'get_locale' ) ? (string) get_locale() : '';
		if ( $locale === $active ) {
			self::$email_locale_switches[ $hash ] = false;
			return false;
		}
		if ( array_key_exists( $hash, self::$email_locale_switches ) ) {
			return false;
		}
		$switched = (bool) switch_to_locale( $locale );
		$active   = function_exists( 'get_locale' ) ? (string) get_locale() : '';
		if ( ! $switched || $locale !== $active ) {
			if ( $switched && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
			unset( self::$email_locale_switches[ $hash ] );
			return $allowed;
		}
		self::$email_locale_switches[ $hash ] = true;
		return false;
	}

	public static function restore_customer_email_locale( $allowed, $email ) {
		if ( ! is_object( $email ) ) {
			return $allowed;
		}
		$hash = spl_object_hash( $email );
		if ( ! array_key_exists( $hash, self::$email_locale_switches ) ) {
			$email_id = sanitize_key( (string) ( $email->id ?? '' ) );
			self::clear_pending_customer_email_language( $email_id );
			return $allowed;
		}
		if ( self::$email_locale_switches[ $hash ] && function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		}
		unset( self::$email_locale_switches[ $hash ] );
		$email_id = sanitize_key( (string) ( $email->id ?? '' ) );
		self::clear_pending_customer_email_language( $email_id );
		return false;
	}

	private static function clear_pending_customer_email_language( $email_id ) {
		$email_id = sanitize_key( (string) $email_id );
		unset( self::$pending_customer_email_languages[ $email_id ] );
		if ( in_array( $email_id, array( 'customer_refunded_order', 'customer_partially_refunded_order' ), true ) ) {
			unset(
				self::$pending_customer_email_languages['customer_refunded_order'],
				self::$pending_customer_email_languages['customer_partially_refunded_order']
			);
		}
	}

	public static function restore_stranded_email_locales() {
		if ( empty( self::$email_locale_switches ) || ! function_exists( 'restore_previous_locale' ) ) {
			self::$email_locale_switches = array();
			self::$pending_customer_email_languages = array();
			self::$pending_customer_email_content = array();
			return;
		}
		foreach ( array_reverse( self::$email_locale_switches ) as $switched ) {
			if ( $switched ) {
				restore_previous_locale();
			}
		}
		self::$email_locale_switches = array();
		self::$pending_customer_email_languages = array();
		self::$pending_customer_email_content = array();
	}

	private static function email_script_counts_match_language( $email, $lang ) {
		if ( ! is_array( $email ) ) {
			return false;
		}
		$subject_hebrew = absint( $email['subject_hebrew_chars'] ?? 0 );
		$body_hebrew    = absint( $email['body_hebrew_chars'] ?? 0 );
		$subject_latin  = absint( $email['subject_latin_chars'] ?? 0 );
		$body_latin     = absint( $email['body_latin_chars'] ?? 0 );
		if ( 'he' === $lang ) {
			return 8 <= $subject_hebrew
				&& 80 <= $body_hebrew
				&& 100 * $subject_hebrew >= 55 * ( $subject_hebrew + $subject_latin )
				&& 100 * $body_hebrew >= 60 * ( $body_hebrew + $body_latin );
		}
		if ( 'en' === $lang ) {
			return 8 <= $subject_latin
				&& 100 <= $body_latin
				&& 100 * $subject_latin >= 55 * ( $subject_hebrew + $subject_latin )
				&& 100 * $body_latin >= 60 * ( $body_hebrew + $body_latin );
		}
		return false;
	}

	public static function inspect_customer_email_content( $params, $email ) {
		if ( ! is_array( $params ) || ! isset( $params[1], $params[2] ) ) {
			return $params;
		}
		$order    = self::customer_email_order( $email );
		$email_id = sanitize_key( (string) ( $email->id ?? '' ) );
		if ( ! $order
			|| ! in_array( $email_id, array( 'customer_processing_order', 'customer_completed_order' ), true ) ) {
			return $params;
		}
		$lang    = self::order_language( $order );
		$subject = function_exists( 'wp_strip_all_tags' )
			? wp_strip_all_tags( (string) $params[1], true )
			: strip_tags( (string) $params[1] );
		$body = function_exists( 'wp_strip_all_tags' )
			? wp_strip_all_tags( (string) $params[2], true )
			: strip_tags( (string) $params[2] );
		$subject_counts = self::text_script_counts( $subject );
		$body_counts    = self::text_script_counts( $body );
		$subject_hebrew = $subject_counts['hebrew'];
		$body_hebrew    = $body_counts['hebrew'];
		$subject_latin  = $subject_counts['latin'];
		$body_latin     = $body_counts['latin'];
		$verified = 'he' === $lang
			? self::text_matches_language( $subject, 'he', 8, 55 )
				&& self::text_matches_language( $body, 'he', 80, 60 )
			: ( 'en' === $lang
				&& self::text_matches_language( $subject, 'en', 8, 55 )
				&& self::text_matches_language( $body, 'en', 100, 60 ) );
		self::$pending_customer_email_content[ spl_object_hash( $email ) ] = array(
			'order_id'              => absint( $order->get_id() ),
			'order_language'        => $lang,
			'email_id'              => $email_id,
			'locale'                => function_exists( 'get_locale' ) ? (string) get_locale() : '',
			'subject_digest'        => hash( 'sha256', $subject ),
			'body_digest'           => hash( 'sha256', $body ),
			'subject_hebrew_chars'  => absint( $subject_hebrew ),
			'body_hebrew_chars'     => absint( $body_hebrew ),
			'subject_latin_chars'   => absint( $subject_latin ),
			'body_latin_chars'      => absint( $body_latin ),
			'language_verified'     => $verified,
		);
		return $params;
	}

	public static function record_order_received_seen( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$order = wc_get_order( absint( $order_id ) );
		if ( ! self::customer_can_access_order( $order ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return false;
		}
		$order->update_meta_data(
			self::ORDER_RECEIVED_SEEN,
			array(
				'schema'      => 'complete99-order-received-evidence/v1',
				'observed_at' => gmdate( 'c' ),
			)
		);
		$order->save_meta_data();
		return true;
	}

	public static function record_customer_order_email_sent( $sent, $email_id, $email ) {
		$order = self::customer_email_order( $email );
		$order_language = self::order_language( $order );
		$locale         = function_exists( 'get_locale' ) ? (string) get_locale() : '';
		$expected_locale = 'en' === $order_language ? 'en_US' : 'he_IL';
		$content_key     = is_object( $email ) ? spl_object_hash( $email ) : '';
		$content         = '' !== $content_key && isset( self::$pending_customer_email_content[ $content_key ] )
			? self::$pending_customer_email_content[ $content_key ]
			: array();
		if ( '' !== $content_key ) {
			unset( self::$pending_customer_email_content[ $content_key ] );
		}
		if ( true !== $sent
			|| ! $order
			|| ! in_array( $order_language, array( 'he', 'en' ), true )
			|| $expected_locale !== $locale
			|| ! is_array( $content )
			|| empty( $content['language_verified'] )
			|| absint( $content['order_id'] ?? 0 ) !== absint( $order->get_id() )
			|| $order_language !== (string) ( $content['order_language'] ?? '' )
			|| $expected_locale !== (string) ( $content['locale'] ?? '' )
			|| ! in_array( (string) $email_id, array( 'customer_processing_order', 'customer_completed_order' ), true )
			|| sanitize_key( (string) $email_id ) !== (string) ( $content['email_id'] ?? '' )
			|| ! method_exists( $order, 'update_meta_data' ) ) {
			return false;
		}
		$order->update_meta_data(
			self::ORDER_EMAIL_SENT,
			array(
				'schema'         => 'complete99-order-email-evidence/v4',
				'email_id'       => sanitize_key( (string) $email_id ),
				'order_language' => $order_language,
				'locale'         => $locale,
				'subject_digest' => (string) $content['subject_digest'],
				'body_digest'    => (string) $content['body_digest'],
				'subject_hebrew_chars' => absint( $content['subject_hebrew_chars'] ),
				'body_hebrew_chars'    => absint( $content['body_hebrew_chars'] ),
				'subject_latin_chars'  => absint( $content['subject_latin_chars'] ),
				'body_latin_chars'     => absint( $content['body_latin_chars'] ),
				'script_dominance_verified' => true,
				'language_verified'    => true,
				'accepted_at'    => gmdate( 'c' ),
			)
		);
		$order->save_meta_data();
		return true;
	}

	public static function capture_order_item_stock_reduction( $item, $change, $order ) {
		if ( ! is_object( $item )
			|| ! is_object( $order )
			|| ! method_exists( $item, 'get_id' )
			|| ! method_exists( $item, 'get_product_id' )
			|| ! method_exists( $order, 'get_id' )
			|| ! is_array( $change )
			|| ! isset( $change['product'], $change['from'], $change['to'] )
			|| ! is_object( $change['product'] )
			|| ! method_exists( $change['product'], 'get_id' )
			|| ! is_numeric( $change['from'] )
			|| ! is_numeric( $change['to'] ) ) {
			return false;
		}
		$order_id     = absint( $order->get_id() );
		$line_item_id = absint( $item->get_id() );
		$product_id   = absint( $item->get_product_id() );
		$variation_id = method_exists( $item, 'get_variation_id' ) ? absint( $item->get_variation_id() ) : 0;
		$quantity     = method_exists( $item, 'get_quantity' ) ? (float) $item->get_quantity() : 0.0;
		$stock_from   = (float) $change['from'];
		$stock_to     = (float) $change['to'];
		$reduced      = $stock_from - $stock_to;
		if ( 1 > $order_id
			|| 1 > $line_item_id
			|| 1 > $product_id
			|| 0 >= $quantity
			|| abs( $reduced - $quantity ) > 0.00001 ) {
			return false;
		}
		$stock_product_id = absint( $change['product']->get_id() );
		$sku = method_exists( $change['product'], 'get_sku' )
			? sanitize_text_field( (string) $change['product']->get_sku() )
			: '';
		$event_version = self::stock_reduction_event_version( $line_item_id, $stock_from, $stock_to );
		$event_payload = self::stock_reduction_event_payload(
			$order_id,
			$line_item_id,
			$product_id,
			$variation_id,
			$stock_product_id,
			$sku,
			$quantity,
			$stock_from,
			$stock_to
		);
		$event_id = '';
		$captured = self::enqueue_event(
			'inventory.order_stock_reduced',
			'order',
			(string) $order_id,
			$event_version,
			$event_payload,
			$event_id
		);
		if ( ! $captured || ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) ) {
			return false;
		}
		if ( ! isset( self::$stock_reduction_lines[ $order_id ] ) ) {
			self::$stock_reduction_lines[ $order_id ] = array();
		}
		self::$stock_reduction_lines[ $order_id ][ $line_item_id ] = array(
			'line_item_id'    => $line_item_id,
			'product_id'      => $product_id,
			'variation_id'    => $variation_id,
			'stock_product_id'=> $stock_product_id,
			'sku'             => $sku,
			'quantity'        => $quantity,
			'stock_from'      => $stock_from,
			'stock_to'        => $stock_to,
			'event_version'   => $event_version,
			'payload_digest'  => self::digest_value( $event_payload ),
			'event_id'        => $event_id,
		);
		return true;
	}

	private static function stock_reduction_event_version( $line_item_id, $stock_from, $stock_to ) {
		return absint( $line_item_id ) . '|' . (float) $stock_from . '|' . (float) $stock_to;
	}

	private static function stock_reduction_event_payload( $order_id, $line_item_id, $product_id, $variation_id, $stock_product_id, $sku, $quantity, $stock_from, $stock_to ) {
		return array(
			'order_id'         => absint( $order_id ),
			'line_item_id'     => absint( $line_item_id ),
			'product_id'       => absint( $product_id ),
			'variation_id'     => absint( $variation_id ),
			'stock_product_id' => absint( $stock_product_id ),
			'sku'              => sanitize_text_field( (string) $sku ),
			'quantity'         => (float) $quantity,
			'stock_from'       => (float) $stock_from,
			'stock_to'         => (float) $stock_to,
		);
	}

	public static function record_order_stock_reduction( $order ) {
		if ( ! is_object( $order )
			|| ! method_exists( $order, 'get_id' )
			|| ! method_exists( $order, 'get_items' )
			|| ! method_exists( $order, 'update_meta_data' ) ) {
			return false;
		}
		$order_id = absint( $order->get_id() );
		$captured = self::$stock_reduction_lines[ $order_id ] ?? array();
		if ( empty( $captured ) ) {
			return false;
		}
		$lines = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item )
				|| ! method_exists( $item, 'get_id' )
				|| ! method_exists( $item, 'get_product_id' ) ) {
				return false;
			}
			$line_item_id = absint( $item->get_id() );
			$quantity     = method_exists( $item, 'get_quantity' ) ? (float) $item->get_quantity() : 0.0;
			if ( ! isset( $captured[ $line_item_id ] )
				|| abs( (float) $captured[ $line_item_id ]['quantity'] - $quantity ) > 0.00001 ) {
				unset( self::$stock_reduction_lines[ $order_id ] );
				return false;
			}
			$lines[] = $captured[ $line_item_id ];
		}
		unset( self::$stock_reduction_lines[ $order_id ] );
		$order->update_meta_data(
			self::ORDER_STOCK_RECEIPT,
			array(
				'schema'      => 'complete99-stock-reduction-evidence/v2',
				'order_id'    => $order_id,
				'recorded_at' => gmdate( 'c' ),
				'lines'       => $lines,
			)
		);
		$order->save_meta_data();
		return true;
	}

	private static function order_payment_gateway( $order ) {
		if ( ! is_object( $order )
			|| ! method_exists( $order, 'get_payment_method' )
			|| ! function_exists( 'WC' )
			|| ! WC()
			|| ! WC()->payment_gateways() ) {
			return false;
		}
		$gateway_id = (string) $order->get_payment_method();
		$gateways   = WC()->payment_gateways()->payment_gateways();
		return isset( $gateways[ $gateway_id ] ) && is_object( $gateways[ $gateway_id ] )
			? $gateways[ $gateway_id ]
			: false;
	}

	private static function gateway_is_live_mode( $gateway, $order = null ) {
		if ( ! is_object( $gateway ) ) {
			return false;
		}
		$filtered = apply_filters( 'complete99_commerce_gateway_live_mode', null, $gateway, $order );
		if ( is_bool( $filtered ) ) {
			return $filtered;
		}
		$settings   = isset( $gateway->settings ) && is_array( $gateway->settings ) ? $gateway->settings : array();
		$test_keys  = array( 'testmode', 'test_mode', 'sandbox', 'sandbox_mode' );
		$mode_keys  = array( 'environment', 'mode' );
		$recognized = false;
		$live_indicator = false;
		foreach ( $test_keys as $key ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}
			$recognized = true;
			$value      = strtolower( trim( (string) $settings[ $key ] ) );
			if ( in_array( $value, array( 'yes', 'true', '1', 'on', 'test', 'sandbox' ), true ) ) {
				return false;
			}
			if ( in_array( $value, array( 'no', 'false', '0', 'off' ), true ) ) {
				$live_indicator = true;
			}
		}
		foreach ( $mode_keys as $key ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}
			$recognized = true;
			$value      = strtolower( trim( (string) $settings[ $key ] ) );
			if ( false !== strpos( $value, 'test' ) || false !== strpos( $value, 'sandbox' ) || false !== strpos( $value, 'develop' ) ) {
				return false;
			}
			if ( false !== strpos( $value, 'live' ) || false !== strpos( $value, 'production' ) ) {
				return true;
			}
		}
		return $recognized && $live_indicator;
	}

	public static function record_gateway_payment_receipt( $order_id, $transaction_id = '' ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order || ! is_a( $order, 'WC_Order' ) || ! method_exists( $order, 'update_meta_data' ) ) {
			return false;
		}
		$gateway       = self::order_payment_gateway( $order );
		$transaction_id = '' !== trim( (string) $transaction_id )
			? (string) $transaction_id
			: (string) $order->get_transaction_id();
		if ( ! $gateway
			|| 'yes' !== (string) $gateway->enabled
			|| ! method_exists( $gateway, 'supports' )
			|| ! $gateway->supports( 'refunds' )
			|| ! self::gateway_is_live_mode( $gateway, $order )
			|| '' === trim( $transaction_id ) ) {
			$order->delete_meta_data( self::ORDER_GATEWAY_RECEIPT );
			$order->save_meta_data();
			return false;
		}
		$order->update_meta_data(
			self::ORDER_GATEWAY_RECEIPT,
			array(
				'schema'              => 'complete99-gateway-payment-evidence/v1',
				'gateway_id'          => sanitize_key( (string) $order->get_payment_method() ),
				'transaction_id_hash' => hash( 'sha256', $transaction_id ),
				'live_mode'           => true,
				'observed_at'         => gmdate( 'c' ),
			)
		);
		$order->save_meta_data();
		return true;
	}

	/**
	 * Authorize classic checkout only after the full launch gate, during an
	 * administrator acceptance preview, or for an authenticated existing-order
	 * continuity request. Catalog and cart readiness alone are never enough.
	 */
	private static function classic_checkout_is_authorized() {
		return self::is_ready()
			|| self::can_preview_commerce()
			|| self::can_access_customer_continuity();
	}

	private static function is_non_ajax_admin_request() {
		return function_exists( 'is_admin' )
			&& is_admin()
			&& ( ! function_exists( 'wp_doing_ajax' ) || ! wp_doing_ajax() )
			&& self::can_manage_commerce();
	}

	private static function classic_checkout_hold_message() {
		return 'en' === self::transaction_language()
			? 'Electronic checkout is not available for this cart. Please call Complete99 to confirm the order.'
			: 'התשלום האלקטרוני אינו זמין לסל הזה. יש להתקשר לקומפלט 99 כדי לאשר את ההזמנה.';
	}

	/**
	 * Hide every classic payment gateway while checkout is held. This filter is
	 * also applied to wc-ajax=checkout, which bypasses template redirects.
	 */
	public static function gate_classic_payment_gateways( $gateways ) {
		if ( self::is_non_ajax_admin_request() || self::classic_checkout_is_authorized() ) {
			return is_array( $gateways ) ? $gateways : array();
		}
		return array();
	}

	/**
	 * Reject a held classic checkout before customer or order creation.
	 */
	public static function guard_classic_checkout_validation( $data, $errors ) {
		if ( self::is_non_ajax_admin_request() || self::classic_checkout_is_authorized() ) {
			return;
		}
		if ( is_object( $errors ) && method_exists( $errors, 'add' ) ) {
			$errors->add( 'complete99_classic_checkout_held', self::classic_checkout_hold_message() );
		}
	}

	/**
	 * Final server-side guard for direct WC_Checkout::create_order() calls.
	 */
	public static function guard_classic_order_creation( $order_id, $checkout ) {
		if ( self::is_non_ajax_admin_request() || self::classic_checkout_is_authorized() ) {
			return $order_id;
		}
		return new WP_Error(
			'complete99_classic_checkout_held',
			self::classic_checkout_hold_message(),
			array( 'status' => 409 )
		);
	}

	public static function gate_public_woocommerce_routes() {
		if ( is_admin() || wp_doing_ajax() || ! self::is_public_woocommerce_route() ) {
			return;
		}
		$ready_or_preview = self::is_ready() || self::can_preview_commerce();
		$cart_or_preview = self::cart_is_ready() || $ready_or_preview;
		if ( self::can_preview_commerce() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			if ( function_exists( 'nocache_headers' ) ) {
				nocache_headers();
			}
			if ( ! headers_sent() ) {
				header( 'X-Robots-Tag: noindex, nofollow', true );
			}
		}
		$lang             = self::transaction_language();
		$store_url        = Complete99_Content::route_url( 'store', $lang );
		$is_cart = function_exists( 'is_cart' ) && is_cart();
		if ( $is_cart && $cart_or_preview ) {
			return;
		}
		if ( ! $ready_or_preview && ! self::can_access_customer_continuity() ) {
			if ( 'GET' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
				wp_die( esc_html__( 'The Complete99 on-site store is not open.', 'complete99-platform' ), '', array( 'response' => 503 ) );
			}
			wp_safe_redirect( $store_url, 302 );
			exit;
		}
		if ( ( function_exists( 'is_product' ) && is_product() )
			|| ( function_exists( 'is_shop' ) && is_shop() )
			|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
			|| ( function_exists( 'is_attachment' )
				&& is_attachment()
				&& function_exists( 'get_queried_object_id' )
				&& self::attachment_is_approved_product_image( get_queried_object_id() ) ) ) {
			wp_safe_redirect( $store_url . '#c99-live-store-products', 302 );
			exit;
		}
	}

	/**
	 * Keep cart editing public while checkout is held, with a real order path.
	 */
	public static function configure_catalog_cart_continuation() {
		if ( ! self::cart_is_ready()
			|| self::is_ready()
			|| ! function_exists( 'is_cart' )
			|| ! is_cart() ) {
			return;
		}
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'render_catalog_cart_continuation' ), 20 );
	}

	/**
	 * Render the non-electronic continuation for a catalog-ready cart.
	 */
	public static function render_catalog_cart_continuation() {
		$is_he     = 'he' === self::transaction_language();
		$store_url = Complete99_Content::route_url( 'store', $is_he ? 'he' : 'en' );
		?>
		<div class="c99-cart-continuation" role="group" aria-label="<?php echo esc_attr( 'המשך הזמנה / Order continuation' ); ?>">
			<p><?php echo esc_html( $is_he ? 'אפשר לעדכן או להסיר מוצרים מהסל. לבדיקת זמינות ולאישור ההזמנה מתקשרים אלינו.' : 'You can update or remove products from the cart. Call us to check availability and confirm the order.' ); ?></p>
			<a class="checkout-button button alt wc-forward c99-cart-phone-order" href="tel:035231810" aria-label="<?php echo esc_attr( 'לשאלות ולהזמנה בטלפון / Questions and orders by phone: 03-523-1810' ); ?>"><?php echo esc_html( $is_he ? 'לשאלות ולהזמנה: 03-523-1810' : 'Questions and orders: 03-523-1810' ); ?></a>
			<a class="button wc-backward c99-cart-return-store" href="<?php echo esc_url( $store_url ); ?>"><?php echo esc_html( $is_he ? 'חזרה למזווה' : 'Return to the pantry' ); ?></a>
		</div>
		<?php
	}

	public static function exclude_products_from_public_search( $query ) {
		if ( is_admin() || ! is_object( $query ) || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		$post_type = $query->get( 'post_type' );
		if ( empty( $post_type ) || 'any' === $post_type ) {
			$query->set( 'post_type', array( 'post', 'page', 'c99_dish' ) );
			return;
		}
		$types = is_array( $post_type ) ? $post_type : array( $post_type );
		$query->set( 'post_type', array_values( array_diff( $types, array( 'product', 'product_variation' ) ) ) );
	}

	private static function acceptance_outbox_evidence_is_valid( $order, $evidence ) {
		if ( ! is_object( $order )
			|| ! method_exists( $order, 'get_id' )
			|| ! is_array( $evidence )
			|| 1 > absint( $evidence['refund_id'] ?? 0 )
			|| ! isset( $evidence['observed'], $evidence['outbox'] )
			|| ! is_array( $evidence['observed'] )
			|| ! is_array( $evidence['outbox'] ) ) {
			return false;
		}
		$order_id = (string) absint( $order->get_id() );
		$refund_id = (string) absint( $evidence['refund_id'] );
		$outbox = $evidence['outbox'];
		$order_event_id = strtolower( sanitize_text_field( (string) ( $outbox['order_snapshot_event_id'] ?? '' ) ) );
		$refund_event_id = strtolower( sanitize_text_field( (string) ( $outbox['refund_event_id'] ?? '' ) ) );
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $order_event_id )
			|| ! preg_match( '/^[a-f0-9]{64}$/', $refund_event_id )
			|| ! self::outbox_has_event_id( $order_event_id, 'order_snapshot', $order_id )
			|| ! self::outbox_has_event_id( $refund_event_id, 'refund_created', $refund_id ) ) {
			return false;
		}

		$expected_stock_ids = array();
		foreach ( (array) ( $evidence['observed']['stock_reduction']['lines'] ?? array() ) as $line ) {
			$expected_stock_ids[] = strtolower( sanitize_text_field( (string) ( $line['event_id'] ?? '' ) ) );
		}
		$stored_stock_ids = array_map(
			static function ( $value ) {
				return strtolower( sanitize_text_field( (string) $value ) );
			},
			(array) ( $outbox['stock_event_ids'] ?? array() )
		);
		sort( $expected_stock_ids, SORT_STRING );
		sort( $stored_stock_ids, SORT_STRING );
		if ( empty( $expected_stock_ids )
			|| count( $expected_stock_ids ) !== count( array_unique( $expected_stock_ids ) )
			|| ! hash_equals( hash( 'sha256', serialize( $expected_stock_ids ) ), hash( 'sha256', serialize( $stored_stock_ids ) ) ) ) {
			return false;
		}
		foreach ( $stored_stock_ids as $event_id ) {
			if ( ! preg_match( '/^[a-f0-9]{64}$/', $event_id )
				|| ! self::outbox_has_event_id( $event_id, 'inventory_order_stock_reduced', $order_id ) ) {
				return false;
			}
		}

		$expected_fulfilment_ids = array();
		foreach ( (array) ( $evidence['observed']['fulfilment']['fulfilments'] ?? array() ) as $fulfilment ) {
			$expected_fulfilment_ids[] = strtolower( sanitize_text_field( (string) ( $fulfilment['event_id'] ?? '' ) ) );
		}
		$stored_fulfilment_ids = array_map(
			static function ( $value ) {
				return strtolower( sanitize_text_field( (string) $value ) );
			},
			(array) ( $outbox['fulfilment_event_ids'] ?? array() )
		);
		sort( $expected_fulfilment_ids, SORT_STRING );
		sort( $stored_fulfilment_ids, SORT_STRING );
		if ( count( $expected_fulfilment_ids ) !== count( array_unique( $expected_fulfilment_ids ) )
			|| ! hash_equals( hash( 'sha256', serialize( $expected_fulfilment_ids ) ), hash( 'sha256', serialize( $stored_fulfilment_ids ) ) ) ) {
			return false;
		}
		foreach ( $stored_fulfilment_ids as $event_id ) {
			if ( ! preg_match( '/^[a-f0-9]{64}$/', $event_id )
				|| ! self::outbox_has_event_id( $event_id, 'fulfilment_changed', $order_id ) ) {
				return false;
			}
		}
		return true;
	}

	private static function acceptance_language_entry_is_valid( $entry, $lang ) {
		if ( ! is_array( $entry )
			|| ! in_array( $lang, array( 'he', 'en' ), true )
			|| 1 > absint( $entry['order_id'] ?? 0 )
			|| 1 > absint( $entry['accepted_by'] ?? 0 )
			|| $lang !== (string) ( $entry['order_language'] ?? '' )
			|| empty( $entry['tested_at'] )
			|| empty( $entry['evidence'] )
			|| ! is_array( $entry['evidence'] )
			|| empty( $entry['evidence_digest'] )
			|| empty( $entry['configuration_digest'] )
			|| ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$tested_at = strtotime( (string) $entry['tested_at'] );
		if ( false === $tested_at
			|| $tested_at > time() + 300
			|| $tested_at < time() - self::ACCEPTANCE_MAX_AGE ) {
			return false;
		}
		$order    = wc_get_order( absint( $entry['order_id'] ) );
		$evidence = self::order_passes_acceptance_contract( $order, false, false );
		return false !== $evidence
			&& $lang === self::order_language( $order )
			&& self::acceptance_outbox_evidence_is_valid( $order, $entry['evidence'] )
			&& hash_equals(
				(string) $entry['evidence_digest'],
				hash( 'sha256', wp_json_encode( $entry['evidence'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
			)
			&& hash_equals(
				hash( 'sha256', wp_json_encode( $evidence['observed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
				hash( 'sha256', wp_json_encode( $entry['evidence']['observed'] ?? array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
			)
			&& hash_equals(
				(string) $entry['configuration_digest'],
				self::commerce_configuration_digest( $order )
			);
	}

	private static function acceptance_receipt() {
		$receipt = get_option( self::OPTION_ACCEPTANCE, array() );
		if ( ! is_array( $receipt )
			|| 'complete99-commerce-acceptance/v3' !== ( $receipt['schema'] ?? '' )
			|| 'passed' !== ( $receipt['status'] ?? '' )
			|| 1 > absint( $receipt['accepted_by'] ?? 0 )
			|| ! isset( $receipt['languages'] )
			|| ! is_array( $receipt['languages'] )
			|| ! isset( $receipt['languages']['he'], $receipt['languages']['en'] )
			|| absint( $receipt['languages']['he']['order_id'] ?? 0 ) === absint( $receipt['languages']['en']['order_id'] ?? 0 )
			|| ! self::acceptance_language_entry_is_valid( $receipt['languages']['he'], 'he' )
			|| ! self::acceptance_language_entry_is_valid( $receipt['languages']['en'], 'en' ) ) {
			return array();
		}
		return $receipt;
	}

	public static function record_checkout_acceptance( WP_REST_Request $request ) {
		if ( ! self::acquire_store_launch_lock() ) {
			return new WP_Error( 'complete99_commerce_launch_lock', 'Another store-state change is in progress.', array( 'status' => 503 ) );
		}
		try {
			return self::record_checkout_acceptance_locked( $request );
		} finally {
			self::release_store_launch_lock();
		}
	}

	private static function record_checkout_acceptance_locked( WP_REST_Request $request ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return new WP_Error( 'complete99_commerce_dependency', 'WooCommerce is not available.', array( 'status' => 409 ) );
		}
		if ( ! self::can_preview_commerce() ) {
			return new WP_Error(
				'complete99_commerce_preview_required',
				'Enable the capability-gated acceptance preview before running a checkout test.',
				array( 'status' => 409 )
			);
		}
		$order_id = absint( $request->get_param( 'order_id' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		$evidence = self::order_passes_acceptance_contract( $order, true, true );
		if ( false === $evidence ) {
			return new WP_Error(
				'complete99_commerce_acceptance',
				'A recent completed storefront order with gateway payment, gateway partial refund, exact stock evidence, customer email evidence and an observed receipt is required.',
				array( 'status' => 422 )
			);
		}
		$lang = self::order_language( $order );
		if ( ! in_array( $lang, array( 'he', 'en' ), true ) ) {
			return new WP_Error(
				'complete99_commerce_order_language',
				'The acceptance order must carry an explicit Hebrew or English storefront language.',
				array( 'status' => 422 )
			);
		}
		$accepted_by = function_exists( 'get_current_user_id' ) ? absint( get_current_user_id() ) : 0;
		$tested_at   = gmdate( 'c' );
		$entry       = array(
			'tested_at'            => $tested_at,
			'accepted_by'          => $accepted_by,
			'order_id'             => $order_id,
			'refund_id'            => absint( $evidence['refund_id'] ),
			'order_status'         => sanitize_key( (string) $order->get_status() ),
			'created_via'          => sanitize_key( (string) $order->get_created_via() ),
			'payment_method'       => sanitize_key( (string) $order->get_payment_method() ),
			'transaction_id_hash'  => hash( 'sha256', (string) $order->get_transaction_id() ),
			'currency'             => sanitize_text_field( (string) $order->get_currency() ),
			'total'                => (string) $order->get_total(),
			'item_count'           => count( $order->get_items() ),
			'order_language'       => $lang,
			'evidence'             => $evidence,
			'evidence_digest'      => hash( 'sha256', wp_json_encode( $evidence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
			'configuration_digest' => self::commerce_configuration_digest( $order ),
		);
		$stored_receipt = get_option( self::OPTION_ACCEPTANCE, array() );
		$languages      = is_array( $stored_receipt )
			&& 'complete99-commerce-acceptance/v3' === ( $stored_receipt['schema'] ?? '' )
			&& isset( $stored_receipt['languages'] )
			&& is_array( $stored_receipt['languages'] )
				? $stored_receipt['languages']
				: array();
		foreach ( array( 'he', 'en' ) as $existing_lang ) {
			if ( $existing_lang !== $lang
				&& isset( $languages[ $existing_lang ] )
				&& ! self::acceptance_language_entry_is_valid( $languages[ $existing_lang ], $existing_lang ) ) {
				unset( $languages[ $existing_lang ] );
			}
		}
		$languages[ $lang ] = $entry;
		$passed = isset( $languages['he'], $languages['en'] )
			&& absint( $languages['he']['order_id'] ?? 0 ) !== absint( $languages['en']['order_id'] ?? 0 )
			&& self::acceptance_language_entry_is_valid( $languages['he'], 'he' )
			&& self::acceptance_language_entry_is_valid( $languages['en'], 'en' );
		$receipt = array(
			'schema'             => 'complete99-commerce-acceptance/v3',
			'status'             => $passed ? 'passed' : 'pending_second_language',
			'tested_at'          => $tested_at,
			'accepted_by'        => $accepted_by,
			'required_languages' => array( 'he', 'en' ),
			'languages'          => $languages,
		);
		update_option( self::OPTION_ACCEPTANCE, $receipt, false );
		$store_ids = self::published_store_page_ids();
		$hold = 2 === count( $store_ids )
			? self::commit_store_hold_state( $store_ids, ! $passed )
			: array(
				'held'                    => false,
				'store_enabled'           => 'yes' === (string) get_option( self::OPTION_ENABLED, 'no' ),
				'preview_enabled'         => 'yes' === (string) get_option( self::OPTION_PREVIEW, 'no' ),
				'store_pages_closed'      => false,
				'hold_audit_verified'     => false,
				'cache_purge_verified'    => false,
			);
		$stored = get_option( self::OPTION_ACCEPTANCE, array() );
		if ( empty( $hold['held'] )
			|| ( $passed ? 'no' : 'yes' ) !== (string) get_option( self::OPTION_PREVIEW, 'no' )
			|| 'no' !== (string) get_option( self::OPTION_ENABLED, '__missing__' )
			|| ! is_array( $stored )
			|| hash( 'sha256', serialize( $receipt ) ) !== hash( 'sha256', serialize( $stored ) )
			|| ( $passed && empty( self::acceptance_receipt() ) ) ) {
			return new WP_Error(
				'complete99_commerce_acceptance_readback',
				'The checkout acceptance receipt and held store state could not be verified.',
				array_merge( array( 'status' => 500 ), $hold )
			);
		}
		$receipt['store_requires_explicit_launch'] = true;
		return rest_ensure_response( $receipt );
	}

	private static function evidence_time_is_valid( $value, $require_recent ) {
		$timestamp = strtotime( (string) $value );
		if ( false === $timestamp || $timestamp > time() + 300 ) {
			return false;
		}
		$max_age = $require_recent ? self::ACCEPTANCE_EVIDENCE_MAX_AGE : self::ACCEPTANCE_MAX_AGE;
		return $timestamp >= time() - $max_age;
	}

	private static function fulfilment_receipt_covers_order( $order, $receipt, $require_recent, $require_pending_outbox ) {
		if ( ! is_object( $order )
			|| ! method_exists( $order, 'get_id' )
			|| ! method_exists( $order, 'get_items' )
			|| ! is_array( $receipt )
			|| 'complete99-fulfilment-evidence/v2' !== ( $receipt['schema'] ?? '' )
			|| absint( $receipt['order_id'] ?? 0 ) !== absint( $order->get_id() )
			|| ! self::evidence_time_is_valid( $receipt['updated_at'] ?? '', $require_recent )
			|| empty( $receipt['fulfilments'] )
			|| ! is_array( $receipt['fulfilments'] ) ) {
			return false;
		}
		$expected = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_id' ) || ! method_exists( $item, 'get_quantity' ) ) {
				return false;
			}
			$line_item_id = absint( $item->get_id() );
			$quantity     = (float) $item->get_quantity();
			if ( 1 > $line_item_id || 0 >= $quantity ) {
				return false;
			}
			$expected[ $line_item_id ] = $quantity;
		}
		$covered    = array_fill_keys( array_keys( $expected ), 0.0 );
		$normalized = array();
		foreach ( $receipt['fulfilments'] as $entry ) {
			if ( ! is_array( $entry ) ) {
				return false;
			}
			$event_id = strtolower( sanitize_text_field( (string) ( $entry['event_id'] ?? '' ) ) );
			if ( empty( $entry['fulfilled'] )
				|| 1 > absint( $entry['fulfilment_id'] ?? 0 )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $event_id )
				|| ! self::evidence_time_is_valid( $entry['observed_at'] ?? '', $require_recent )
				|| empty( $entry['items'] )
				|| ! is_array( $entry['items'] )
				|| ( $require_pending_outbox
					&& ! self::pending_outbox_has_event_id(
						$event_id,
						'fulfilment_changed',
						(string) $order->get_id()
					) ) ) {
				return false;
			}
			$items = array();
			foreach ( $entry['items'] as $item ) {
				$line_item_id = absint( $item['item_id'] ?? 0 );
				$quantity     = (float) ( $item['qty'] ?? 0 );
				if ( ! isset( $expected[ $line_item_id ] ) || 0 >= $quantity ) {
					return false;
				}
				$covered[ $line_item_id ] += $quantity;
				if ( $covered[ $line_item_id ] - $expected[ $line_item_id ] > 0.00001 ) {
					return false;
				}
				$items[] = array(
					'line_item_id' => $line_item_id,
					'quantity'     => $quantity,
				);
			}
			$normalized[] = array(
				'fulfilment_id' => absint( $entry['fulfilment_id'] ),
				'status'         => sanitize_key( (string) ( $entry['status'] ?? '' ) ),
				'fulfilled'      => true,
				'observed_at'    => (string) $entry['observed_at'],
				'event_id'       => $event_id,
				'items'          => $items,
			);
		}
		foreach ( $expected as $line_item_id => $quantity ) {
			if ( abs( $covered[ $line_item_id ] - $quantity ) > 0.00001 ) {
				return false;
			}
		}
		usort(
			$normalized,
			static function ( $left, $right ) {
				return $left['fulfilment_id'] <=> $right['fulfilment_id'];
			}
		);
		return array(
			'schema'       => 'complete99-fulfilment-evidence/v2',
			'order_id'     => absint( $order->get_id() ),
			'updated_at'   => (string) $receipt['updated_at'],
			'fulfilments' => $normalized,
		);
	}

	private static function order_passes_acceptance_contract( $order, $require_recent = false, $require_pending_outbox = false ) {
		if ( ! is_object( $order )
			|| ! method_exists( $order, 'get_items' )
			|| empty( $order->get_items() )
			|| empty( $order->get_items( 'shipping' ) )
			|| '' === trim( (string) $order->get_payment_method() )
			|| '' === trim( (string) $order->get_transaction_id() )
			|| 'checkout' !== (string) $order->get_created_via()
			|| 'ILS' !== (string) $order->get_currency()
			|| 0 >= (float) $order->get_total()
			|| '' === trim( (string) $order->get_billing_email() )
			|| '' === trim( (string) $order->get_billing_address_1() )
			|| '' === trim( (string) $order->get_billing_city() )
			|| '' === trim( (string) $order->get_billing_country() )
			|| ! self::order_uses_approved_products( $order )
			|| 'completed' !== (string) $order->get_status() ) {
			return false;
		}
		$gateway         = self::order_payment_gateway( $order );
		$gateway_receipt = $order->get_meta( self::ORDER_GATEWAY_RECEIPT, true );
		if ( ! $gateway
			|| 'yes' !== (string) $gateway->enabled
			|| ! method_exists( $gateway, 'supports' )
			|| ! $gateway->supports( 'refunds' )
			|| ! self::gateway_is_live_mode( $gateway, $order )
			|| ! is_array( $gateway_receipt )
			|| 'complete99-gateway-payment-evidence/v1' !== ( $gateway_receipt['schema'] ?? '' )
			|| sanitize_key( (string) ( $gateway_receipt['gateway_id'] ?? '' ) ) !== sanitize_key( (string) $order->get_payment_method() )
			|| empty( $gateway_receipt['live_mode'] )
			|| ! hash_equals(
				hash( 'sha256', (string) $order->get_transaction_id() ),
				(string) ( $gateway_receipt['transaction_id_hash'] ?? '' )
			)
			|| ! self::evidence_time_is_valid( $gateway_receipt['observed_at'] ?? '', $require_recent ) ) {
			return false;
		}
		$paid = method_exists( $order, 'get_date_paid' ) ? $order->get_date_paid() : false;
		if ( ! $paid
			|| ( $require_recent && $paid->getTimestamp() < time() - self::ACCEPTANCE_EVIDENCE_MAX_AGE )
			|| $paid->getTimestamp() > time() + 300 ) {
			return false;
		}
		$refund_total = 0.0;
		$gateway_refund = false;
		foreach ( $order->get_refunds() as $candidate_refund ) {
			if ( ! is_object( $candidate_refund ) || ! method_exists( $candidate_refund, 'get_amount' ) ) {
				continue;
			}
			$amount = abs( (float) $candidate_refund->get_amount() );
			if ( 0 < $amount ) {
				$refund_total += $amount;
				if ( false === $gateway_refund
					&& method_exists( $candidate_refund, 'get_refunded_payment' )
					&& true === $candidate_refund->get_refunded_payment() ) {
					$gateway_refund = $candidate_refund;
				}
			}
		}
		if ( 0 >= $refund_total || $refund_total >= (float) $order->get_total() || false === $gateway_refund ) {
			return false;
		}

		$received = $order->get_meta( self::ORDER_RECEIVED_SEEN, true );
		$email    = $order->get_meta( self::ORDER_EMAIL_SENT, true );
		$stock    = $order->get_meta( self::ORDER_STOCK_RECEIPT, true );
		$order_language = self::order_language( $order );
		$expected_email_locale = 'en' === $order_language ? 'en_US' : 'he_IL';
		$fulfilment = $order->get_meta( self::ORDER_FULFILMENT_RECEIPT, true );
		$fulfilment_feature = 'yes' === (string) get_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		$fulfilment_evidence = $fulfilment_feature
			? self::fulfilment_receipt_covers_order( $order, $fulfilment, $require_recent, $require_pending_outbox )
			: false;
		if ( ! is_array( $received )
			|| 'complete99-order-received-evidence/v1' !== ( $received['schema'] ?? '' )
			|| ! self::evidence_time_is_valid( $received['observed_at'] ?? '', $require_recent )
			|| ! is_array( $email )
			|| 'complete99-order-email-evidence/v4' !== ( $email['schema'] ?? '' )
			|| ! in_array( (string) ( $email['email_id'] ?? '' ), array( 'customer_processing_order', 'customer_completed_order' ), true )
			|| $order_language !== (string) ( $email['order_language'] ?? '' )
			|| $expected_email_locale !== (string) ( $email['locale'] ?? '' )
			|| empty( $email['language_verified'] )
			|| empty( $email['script_dominance_verified'] )
			|| ! self::email_script_counts_match_language( $email, $order_language )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $email['subject_digest'] ?? '' ) )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) ( $email['body_digest'] ?? '' ) )
			|| ! self::evidence_time_is_valid( $email['accepted_at'] ?? '', $require_recent )
			|| ! is_array( $stock )
			|| 'complete99-stock-reduction-evidence/v2' !== ( $stock['schema'] ?? '' )
			|| absint( $stock['order_id'] ?? 0 ) !== absint( $order->get_id() )
			|| ! self::evidence_time_is_valid( $stock['recorded_at'] ?? '', $require_recent )
			|| empty( $stock['lines'] )
			|| ! is_array( $stock['lines'] )
			|| ( $fulfilment_feature && false === $fulfilment_evidence ) ) {
			return false;
		}

		$expected_stock = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item )
				|| ! method_exists( $item, 'get_id' )
				|| ! method_exists( $item, 'get_product_id' ) ) {
				return false;
			}
			$line_item_id = absint( $item->get_id() );
			$product_id   = absint( $item->get_product_id() );
			$variation_id = method_exists( $item, 'get_variation_id' ) ? absint( $item->get_variation_id() ) : 0;
			$expected_stock[ $line_item_id ] = array(
				'line_item_id' => $line_item_id,
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'quantity'     => method_exists( $item, 'get_quantity' ) ? (float) $item->get_quantity() : 0,
			);
		}
		$normalized_stock_lines = array();
		foreach ( $stock['lines'] as $line ) {
			$line_item_id = absint( $line['line_item_id'] ?? 0 );
			$event_id     = strtolower( sanitize_text_field( (string) ( $line['event_id'] ?? '' ) ) );
			$stock_from   = (float) ( $line['stock_from'] ?? 0 );
			$stock_to     = (float) ( $line['stock_to'] ?? 0 );
			if ( ! isset( $expected_stock[ $line_item_id ] )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $event_id )
				|| abs( (float) ( $line['quantity'] ?? 0 ) - (float) $expected_stock[ $line_item_id ]['quantity'] ) > 0.00001
				|| abs( ( $stock_from - $stock_to ) - (float) $expected_stock[ $line_item_id ]['quantity'] ) > 0.00001
				|| ( $require_pending_outbox
					&& ! self::pending_outbox_has_event_id(
						$event_id,
						'inventory_order_stock_reduced',
						(string) $order->get_id()
					) ) ) {
				return false;
			}
			$normalized_stock_lines[] = array(
				'line_item_id'    => $line_item_id,
				'product_id'      => $expected_stock[ $line_item_id ]['product_id'],
				'variation_id'    => $expected_stock[ $line_item_id ]['variation_id'],
				'stock_product_id'=> absint( $line['stock_product_id'] ?? 0 ),
				'quantity'        => $expected_stock[ $line_item_id ]['quantity'],
				'stock_from'      => $stock_from,
				'stock_to'        => $stock_to,
				'event_id'        => $event_id,
			);
			unset( $expected_stock[ $line_item_id ] );
		}
		if ( ! empty( $expected_stock ) ) {
			return false;
		}
		usort(
			$normalized_stock_lines,
			static function ( $left, $right ) {
				return $left['line_item_id'] <=> $right['line_item_id'];
			}
		);

		$outbox_evidence = array();
		if ( $require_pending_outbox ) {
			$order_event  = self::latest_pending_outbox_event( 'order_snapshot', (string) $order->get_id() );
			$refund_event = self::latest_pending_outbox_event( 'refund_created', (string) $gateway_refund->get_id() );
			if ( empty( $order_event['id'] )
				|| empty( $refund_event['id'] )
				|| ! self::evidence_time_is_valid( $order_event['occurred_at'] ?? '', true )
				|| ! self::evidence_time_is_valid( $refund_event['occurred_at'] ?? '', true ) ) {
				return false;
			}
			$outbox_evidence = array(
				'order_snapshot_event_id' => (string) $order_event['id'],
				'refund_event_id'         => (string) $refund_event['id'],
				'stock_event_ids'         => array_values( array_column( $normalized_stock_lines, 'event_id' ) ),
				'fulfilment_event_ids'    => $fulfilment_feature
					? array_values( array_column( $fulfilment_evidence['fulfilments'], 'event_id' ) )
					: array(),
			);
		}

		return array(
			'refund_id' => absint( $gateway_refund->get_id() ),
			'observed'  => array(
				'order_received'  => array(
					'schema'      => 'complete99-order-received-evidence/v1',
					'observed_at' => (string) $received['observed_at'],
				),
				'customer_email'  => array(
					'schema'         => 'complete99-order-email-evidence/v4',
					'email_id'       => sanitize_key( (string) $email['email_id'] ),
					'order_language' => $order_language,
					'locale'         => $expected_email_locale,
					'subject_digest' => (string) $email['subject_digest'],
					'body_digest'    => (string) $email['body_digest'],
					'subject_hebrew_chars' => absint( $email['subject_hebrew_chars'] ),
					'body_hebrew_chars'    => absint( $email['body_hebrew_chars'] ),
					'subject_latin_chars'  => absint( $email['subject_latin_chars'] ),
					'body_latin_chars'     => absint( $email['body_latin_chars'] ),
					'script_dominance_verified' => true,
					'language_verified'    => true,
					'accepted_at'    => (string) $email['accepted_at'],
				),
				'gateway_payment' => array(
					'schema'              => 'complete99-gateway-payment-evidence/v1',
					'gateway_id'          => sanitize_key( (string) $gateway_receipt['gateway_id'] ),
					'transaction_id_hash' => (string) $gateway_receipt['transaction_id_hash'],
					'live_mode'           => true,
					'observed_at'         => (string) $gateway_receipt['observed_at'],
				),
				'stock_reduction' => array(
					'schema'      => 'complete99-stock-reduction-evidence/v2',
					'order_id'    => absint( $order->get_id() ),
					'recorded_at' => (string) $stock['recorded_at'],
					'lines'       => $normalized_stock_lines,
				),
				'fulfilment'     => $fulfilment_feature
					? $fulfilment_evidence
					: array(
						'mode'   => 'completed_order_status',
						'status' => 'completed',
					),
			),
			'outbox'    => $outbox_evidence,
		);
	}

	private static function order_uses_approved_products( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
			return false;
		}
		$count = 0;
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item )
				|| ! method_exists( $item, 'get_product_id' )
				|| ( method_exists( $item, 'get_variation_id' ) && 0 < absint( $item->get_variation_id() ) )
				|| ! self::product_passes_static_acceptance_contract( absint( $item->get_product_id() ) ) ) {
				return false;
			}
			$count++;
		}
		return 0 < $count;
	}

	private static function canonicalize_digest_value( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$keys    = array_keys( $value );
		$is_list = empty( $value ) || $keys === range( 0, count( $value ) - 1 );
		if ( ! $is_list ) {
			ksort( $value, SORT_STRING );
		}
		foreach ( $value as $key => $item ) {
			$value[ $key ] = self::canonicalize_digest_value( $item );
		}
		return $value;
	}

	private static function digest_value( $value ) {
		$encoded = wp_json_encode(
			self::canonicalize_digest_value( $value ),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		return is_string( $encoded ) ? hash( 'sha256', $encoded ) : '';
	}

	private static function commerce_attachment_identity( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$file          = $attachment_id && function_exists( 'get_attached_file' )
			? (string) get_attached_file( $attachment_id, true )
			: '';
		$file_digest   = '';
		$file_size     = 0;
		if ( '' !== $file && is_readable( $file ) && is_file( $file ) ) {
			$calculated = hash_file( 'sha256', $file );
			$file_digest = is_string( $calculated ) ? $calculated : '';
			$size        = filesize( $file );
			$file_size   = false === $size ? 0 : absint( $size );
		}
		$metadata = $attachment_id && function_exists( 'wp_get_attachment_metadata' )
			? wp_get_attachment_metadata( $attachment_id )
			: array();
		$public_safe = $attachment_id
			? 'yes' === (string) get_post_meta( $attachment_id, self::MEDIA_PUBLIC_SAFE, true )
			: false;
		return array(
			'id'              => $attachment_id,
			'valid'           => 0 < $attachment_id
				&& function_exists( 'wp_attachment_is_image' )
				&& wp_attachment_is_image( $attachment_id )
				&& 0 < $file_size
				&& preg_match( '/^[a-f0-9]{64}$/', $file_digest )
				&& $public_safe,
			'post_status'     => $attachment_id ? (string) get_post_status( $attachment_id ) : '',
			'post_title'      => $attachment_id ? (string) get_post_field( 'post_title', $attachment_id ) : '',
			'post_excerpt'    => $attachment_id ? (string) get_post_field( 'post_excerpt', $attachment_id ) : '',
			'mime_type'       => $attachment_id ? (string) get_post_mime_type( $attachment_id ) : '',
			'relative_file'   => $attachment_id ? (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) : '',
			'alt_text'        => $attachment_id ? (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) : '',
			'public_safe'     => $public_safe,
			'file_size'       => $file_size,
			'file_sha256'     => $file_digest,
			'metadata_digest' => self::digest_value( is_array( $metadata ) ? $metadata : array() ),
		);
	}

	private static function commerce_material_options_snapshot() {
		global $wpdb;
		$option_names = self::commerce_configuration_option_names();
		$enumeration_complete = false;
		if ( is_object( $wpdb )
			&& isset( $wpdb->options )
			&& method_exists( $wpdb, 'prepare' )
			&& method_exists( $wpdb, 'esc_like' )
			&& method_exists( $wpdb, 'get_col' ) ) {
			$query = $wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( 'woocommerce_' ) . '%' . $wpdb->esc_like( '_settings' ),
				$wpdb->esc_like( 'woocommerce_checkout_' ) . '%' . $wpdb->esc_like( '_endpoint' ),
				$wpdb->esc_like( 'woocommerce_myaccount_' ) . '%' . $wpdb->esc_like( '_endpoint' )
			);
			$dynamic_names = $wpdb->get_col( $query );
			if ( is_array( $dynamic_names ) ) {
				$enumeration_complete = true;
				foreach ( $dynamic_names as $option_name ) {
					if ( self::commerce_configuration_option_is_material( $option_name ) ) {
						$option_names[] = (string) $option_name;
					}
				}
			}
		}
		$values = array();
		foreach ( array_values( array_unique( $option_names ) ) as $option_name ) {
			$values[ $option_name ] = self::digest_value( get_option( $option_name, null ) );
		}
		ksort( $values, SORT_STRING );
		return array(
			'enumeration_complete' => $enumeration_complete,
			'values'               => $values,
		);
	}

	private static function commerce_page_identity( $page_id ) {
		$page_id = absint( $page_id );
		return array(
			'id'              => $page_id,
			'post_status'     => $page_id ? (string) get_post_status( $page_id ) : '',
			'post_name'       => $page_id ? (string) get_post_field( 'post_name', $page_id ) : '',
			'post_parent'     => $page_id ? absint( get_post_field( 'post_parent', $page_id ) ) : 0,
			'template'        => $page_id ? (string) get_post_meta( $page_id, '_wp_page_template', true ) : '',
			'language'        => $page_id ? (string) get_post_meta( $page_id, '_complete99_language', true ) : '',
			'translation_key' => $page_id ? (string) get_post_meta( $page_id, '_complete99_translation_key', true ) : '',
			'content_digest'  => $page_id
				? self::digest_value(
					array(
						'title'   => (string) get_post_field( 'post_title', $page_id ),
						'excerpt' => (string) get_post_field( 'post_excerpt', $page_id ),
						'content' => (string) get_post_field( 'post_content', $page_id ),
					)
				)
				: '',
		);
	}

	private static function product_passes_static_acceptance_contract( $product_id ) {
		$product_id = absint( $product_id );
		$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		$image      = self::commerce_attachment_identity( $product ? $product->get_image_id() : 0 );
		$gallery_images_valid = true;
		if ( $product ) {
			foreach ( (array) $product->get_gallery_image_ids() as $gallery_image_id ) {
				if ( empty( self::commerce_attachment_identity( $gallery_image_id )['valid'] ) ) {
					$gallery_images_valid = false;
					break;
				}
			}
		}
		if ( ! $product
			|| 'publish' !== get_post_status( $product_id )
			|| 'yes' !== (string) get_post_meta( $product_id, self::PRODUCT_APPROVED, true )
			|| ! $product->is_type( 'simple' )
			|| $product->get_virtual()
			|| ! $product->needs_shipping()
			|| '' === trim( (string) $product->get_sku() )
			|| ! is_numeric( $product->get_price() )
			|| 0 >= (float) $product->get_price()
			|| ! is_numeric( $product->get_weight() )
			|| 0 >= (float) $product->get_weight()
			|| empty( $image['valid'] )
			|| ! $gallery_images_valid
			|| 1 > absint( $product->get_shipping_class_id() )
			|| ! in_array( (string) $product->get_tax_status(), array( 'taxable', 'shipping', 'none' ), true )
			|| ! $product->managing_stock()
			|| $product->backorders_allowed()
			|| 'woocommerce' !== (string) get_post_meta( $product_id, self::STOCK_AUTHORITY, true )
			|| 'yes' !== (string) get_post_meta( $product_id, self::LABEL_REVIEWED, true )
			|| 'yes' !== (string) get_post_meta( $product_id, self::ORIGIN_REVIEWED, true )
			|| 'yes' !== (string) get_post_meta( $product_id, self::CHECKOUT_ELIGIBLE, true )
			|| 'yes' !== (string) get_post_meta( $product_id, self::RIGHTS_REVIEWED, true )
			|| 'yes' !== (string) get_post_meta( $product_id, self::TAX_REVIEWED, true )
			|| 'yes' !== (string) get_post_meta( $product_id, self::MEDIA_PUBLIC_SAFE, true ) ) {
			return false;
		}
		return self::product_copy_matches_declared_languages( $product_id );
	}

	private static function commerce_tax_configuration() {
		$configuration = array(
			'readback_complete'   => true,
			'calculation_enabled' => (string) get_option( 'woocommerce_calc_taxes', 'no' ),
			'prices_include_tax'  => (string) get_option( 'woocommerce_prices_include_tax', 'no' ),
			'based_on'            => (string) get_option( 'woocommerce_tax_based_on', 'shipping' ),
			'round_at_subtotal'   => (string) get_option( 'woocommerce_tax_round_at_subtotal', 'no' ),
			'display_shop'        => (string) get_option( 'woocommerce_tax_display_shop', 'excl' ),
			'display_cart'        => (string) get_option( 'woocommerce_tax_display_cart', 'excl' ),
			'shipping_tax_class'  => (string) get_option( 'woocommerce_shipping_tax_class', '' ),
			'rates'               => array(),
		);
		if ( ! class_exists( 'WC_Tax' )
			|| ! is_callable( array( 'WC_Tax', 'get_tax_class_slugs' ) )
			|| ! is_callable( array( 'WC_Tax', 'get_rates_for_tax_class' ) ) ) {
			return array();
		}
		try {
			$class_slugs = WC_Tax::get_tax_class_slugs();
		} catch ( \Throwable $error ) {
			return array();
		}
		if ( ! is_array( $class_slugs ) ) {
			return array();
		}
		$classes = array_values( array_unique( array_merge( array( '' ), $class_slugs ) ) );
		sort( $classes, SORT_STRING );
		foreach ( $classes as $tax_class ) {
			try {
				$rates = WC_Tax::get_rates_for_tax_class( $tax_class );
			} catch ( \Throwable $error ) {
				return array();
			}
			if ( ! is_array( $rates ) ) {
				return array();
			}
			foreach ( (array) $rates as $rate_key => $rate ) {
				$rate = is_object( $rate ) ? get_object_vars( $rate ) : (array) $rate;
				foreach ( array( 'postcode', 'city' ) as $location_key ) {
					if ( isset( $rate[ $location_key ] ) && is_array( $rate[ $location_key ] ) ) {
						$rate[ $location_key ] = array_values( array_map( 'strval', $rate[ $location_key ] ) );
						sort( $rate[ $location_key ], SORT_STRING );
					}
				}
				$rates[ $rate_key ] = $rate;
			}
			$configuration['rates'][ (string) $tax_class ] = self::canonicalize_digest_value( $rates );
		}
		return self::canonicalize_digest_value( $configuration );
	}

	private static function commerce_configuration_digest( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
			return '';
		}
		$product_meta_keys = array(
			self::PRODUCT_APPROVED,
			self::PRODUCT_KIND,
			self::STOCK_AUTHORITY,
			self::NAME_HE,
			self::NAME_EN,
			self::DESCRIPTION_HE,
			self::DESCRIPTION_EN,
			self::INGREDIENTS_HE,
			self::INGREDIENTS_EN,
			self::ALLERGENS_HE,
			self::ALLERGENS_EN,
			self::STORAGE_HE,
			self::STORAGE_EN,
			self::FULFILMENT_HE,
			self::FULFILMENT_EN,
			self::ORIGIN_HE,
			self::ORIGIN_EN,
			self::MODEL_HE,
			self::MODEL_EN,
			self::MATERIAL_HE,
			self::MATERIAL_EN,
			self::DIMENSIONS_HE,
			self::DIMENSIONS_EN,
			self::CARE_HE,
			self::CARE_EN,
			self::SAFETY_HE,
			self::SAFETY_EN,
			self::LABEL_REVIEWED,
			self::ORIGIN_REVIEWED,
			self::CHECKOUT_ELIGIBLE,
			self::RIGHTS_REVIEWED,
			self::TAX_REVIEWED,
			self::MEDIA_PUBLIC_SAFE,
		);
		$order_product_ids = array();
		foreach ( $order->get_items() as $item ) {
			$product_id = is_object( $item ) && method_exists( $item, 'get_product_id' ) ? absint( $item->get_product_id() ) : 0;
			if ( 1 > $product_id ) {
				return '';
			}
			$order_product_ids[] = $product_id;
		}
		$catalog = self::approved_products();
		$catalog_ids = array_values( array_unique( array_map( 'absint', (array) $catalog['reviewed_ids'] ) ) );
		sort( $catalog_ids, SORT_NUMERIC );
		if ( empty( $catalog_ids ) || ! empty( array_diff( $order_product_ids, $catalog_ids ) ) ) {
			return '';
		}
		$products = array();
		foreach ( $catalog_ids as $product_id ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
			if ( ! $product ) {
				return '';
			}
			$meta = array();
			foreach ( $product_meta_keys as $key ) {
				$meta[ $key ] = (string) get_post_meta( $product_id, $key, true );
			}
			$attributes = array();
			foreach ( (array) $product->get_attributes() as $attribute_key => $attribute ) {
				if ( is_object( $attribute ) && method_exists( $attribute, 'get_name' ) ) {
					$options = method_exists( $attribute, 'get_options' ) ? (array) $attribute->get_options() : array();
					$attributes[ (string) $attribute_key ] = array(
						'id'        => method_exists( $attribute, 'get_id' ) ? absint( $attribute->get_id() ) : 0,
						'name'      => (string) $attribute->get_name(),
						'options'   => array_values( $options ),
						'position'  => method_exists( $attribute, 'get_position' ) ? absint( $attribute->get_position() ) : 0,
						'visible'   => method_exists( $attribute, 'get_visible' ) ? (bool) $attribute->get_visible() : false,
						'variation' => method_exists( $attribute, 'get_variation' ) ? (bool) $attribute->get_variation() : false,
					);
				} else {
					$attributes[ (string) $attribute_key ] = self::canonicalize_digest_value( $attribute );
				}
			}
			ksort( $attributes, SORT_STRING );
			$category_ids = array_values( array_map( 'absint', (array) $product->get_category_ids() ) );
			$tag_ids      = array_values( array_map( 'absint', (array) $product->get_tag_ids() ) );
			sort( $category_ids, SORT_NUMERIC );
			sort( $tag_ids, SORT_NUMERIC );
			$gallery = array();
			foreach ( (array) $product->get_gallery_image_ids() as $gallery_id ) {
				$gallery[] = self::commerce_attachment_identity( $gallery_id );
			}
			$products[ $product_id ] = array(
				'id'                => $product_id,
				'post_status'       => (string) get_post_status( $product_id ),
				'post_title'        => (string) get_post_field( 'post_title', $product_id ),
				'post_name'         => (string) get_post_field( 'post_name', $product_id ),
				'post_content'      => (string) get_post_field( 'post_content', $product_id ),
				'post_excerpt'      => (string) get_post_field( 'post_excerpt', $product_id ),
				'type'              => (string) $product->get_type(),
				'sku'               => (string) $product->get_sku(),
				'price'             => (string) $product->get_price(),
				'regular_price'     => (string) $product->get_regular_price(),
				'sale_price'        => (string) $product->get_sale_price(),
				'weight'            => (string) $product->get_weight(),
				'length'            => (string) $product->get_length(),
				'width'             => (string) $product->get_width(),
				'height'            => (string) $product->get_height(),
				'image'             => self::commerce_attachment_identity( $product->get_image_id() ),
				'gallery'           => $gallery,
				'shipping_class_id' => absint( $product->get_shipping_class_id() ),
				'tax_status'        => (string) $product->get_tax_status(),
				'tax_class'         => (string) $product->get_tax_class(),
				'catalog_visibility'=> (string) $product->get_catalog_visibility(),
				'purchase_note'     => (string) $product->get_purchase_note(),
				'category_ids'      => $category_ids,
				'tag_ids'           => $tag_ids,
				'attributes'        => $attributes,
				'virtual'           => (bool) $product->get_virtual(),
				'downloadable'      => (bool) $product->get_downloadable(),
				'manage_stock'      => (bool) $product->managing_stock(),
				'backorders'        => (string) $product->get_backorders(),
				'sold_individually' => (bool) $product->is_sold_individually(),
				'meta'              => $meta,
			);
		}
		ksort( $products, SORT_NUMERIC );
		$shipping = array();
		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$shipping[] = array(
				'method_id'   => is_object( $item ) && method_exists( $item, 'get_method_id' ) ? sanitize_key( (string) $item->get_method_id() ) : '',
				'instance_id' => is_object( $item ) && method_exists( $item, 'get_instance_id' ) ? absint( $item->get_instance_id() ) : 0,
			);
		}
		usort(
			$shipping,
			static function ( $left, $right ) {
				return strcmp(
					$left['method_id'] . ':' . $left['instance_id'],
					$right['method_id'] . ':' . $right['instance_id']
				);
			}
		);
		$pages = array(
			'shop'     => absint( get_option( 'woocommerce_shop_page_id', 0 ) ),
			'terms'    => absint( get_option( 'woocommerce_terms_page_id', 0 ) ),
			'privacy'  => absint( get_option( 'wp_page_for_privacy_policy', 0 ) ),
			'cart'     => absint( get_option( 'woocommerce_cart_page_id', 0 ) ),
			'checkout' => absint( get_option( 'woocommerce_checkout_page_id', 0 ) ),
			'account'  => absint( get_option( 'woocommerce_myaccount_page_id', 0 ) ),
		);
		foreach ( $pages as $page_key => $page_id ) {
			$pages[ $page_key ] = self::commerce_page_identity( $page_id );
		}
		$localized_policy_pages = array();
		foreach ( array( 'he', 'en' ) as $policy_lang ) {
			foreach ( array( 'terms', 'privacy' ) as $policy_key ) {
				$policy_id = Complete99_Content::find_translation_post_id( $policy_key, $policy_lang, true );
				$localized_policy_pages[ $policy_lang ][ $policy_key ] = self::commerce_page_identity( $policy_id );
			}
		}
		$localized_store_pages = array();
		foreach ( array( 'he', 'en' ) as $store_lang ) {
			$store_id = Complete99_Content::find_translation_post_id( 'store', $store_lang, true );
			$localized_store_pages[ $store_lang ] = self::commerce_page_identity( $store_id );
		}
		$gateway_configuration = array();
		if ( function_exists( 'WC' ) && WC() && WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( ! is_object( $gateway ) || empty( $gateway->id ) ) {
					continue;
				}
				$gateway_configuration[ (string) $gateway->id ] = array(
					'class'           => get_class( $gateway ),
					'enabled'         => 'yes' === (string) $gateway->enabled,
					'refunds'         => method_exists( $gateway, 'supports' ) && $gateway->supports( 'refunds' ),
					'live_mode'       => self::gateway_is_live_mode( $gateway, $order ),
					'settings_digest' => self::digest_value(
						isset( $gateway->settings ) && is_array( $gateway->settings ) ? $gateway->settings : array()
					),
				);
			}
			ksort( $gateway_configuration );
		}
		$shipping_configuration = array();
		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			$zones = WC_Shipping_Zones::get_zones();
			if ( class_exists( 'WC_Shipping_Zone' ) ) {
				$rest_of_world = new WC_Shipping_Zone( 0 );
				$zones[] = array(
					'zone_id'          => 0,
					'zone_name'        => method_exists( $rest_of_world, 'get_zone_name' ) ? $rest_of_world->get_zone_name() : 'Rest of the world',
					'zone_order'       => 0,
					'zone_locations'   => method_exists( $rest_of_world, 'get_zone_locations' ) ? $rest_of_world->get_zone_locations() : array(),
					'shipping_methods' => $rest_of_world->get_shipping_methods( true ),
				);
			}
			foreach ( $zones as $zone ) {
				$zone_id   = absint( $zone['zone_id'] ?? 0 );
				$locations = array();
				foreach ( (array) ( $zone['zone_locations'] ?? array() ) as $location ) {
					$locations[] = array(
						'type' => sanitize_key( (string) ( is_object( $location ) ? ( $location->type ?? '' ) : ( $location['type'] ?? '' ) ) ),
						'code' => sanitize_text_field( (string) ( is_object( $location ) ? ( $location->code ?? '' ) : ( $location['code'] ?? '' ) ) ),
					);
				}
				usort(
					$locations,
					static function ( $left, $right ) {
						return strcmp( $left['type'] . '|' . $left['code'], $right['type'] . '|' . $right['code'] );
					}
				);
				$zone_configuration = array(
					'zone_id'    => $zone_id,
					'zone_name'  => sanitize_text_field( (string) ( $zone['zone_name'] ?? '' ) ),
					'zone_order' => absint( $zone['zone_order'] ?? 0 ),
					'locations'  => $locations,
					'methods'    => array(),
				);
				foreach ( (array) ( $zone['shipping_methods'] ?? array() ) as $method ) {
					if ( ! is_object( $method ) ) {
						continue;
					}
					$instance_id = isset( $method->instance_id ) ? absint( $method->instance_id ) : 0;
					$method_id   = sanitize_key( (string) ( $method->id ?? '' ) );
					$key         = $method_id . ':' . $instance_id;
					$zone_configuration['methods'][ $key ] = array(
						'method_id'   => $method_id,
						'instance_id' => $instance_id,
						'order'       => absint( $method->method_order ?? 0 ),
						'enabled' => method_exists( $method, 'is_enabled' )
							? (bool) $method->is_enabled()
							: 'yes' === (string) ( $method->enabled ?? '' ),
						'global_settings_digest' => self::digest_value( (array) ( $method->settings ?? array() ) ),
						'instance_settings_digest' => self::digest_value( (array) ( $method->instance_settings ?? array() ) ),
					);
				}
				ksort( $zone_configuration['methods'] );
				$shipping_configuration[ (string) $zone_id ] = $zone_configuration;
			}
			ksort( $shipping_configuration, SORT_NUMERIC );
		}
		foreach ( $shipping as $selected_method ) {
			$found = false;
			foreach ( $shipping_configuration as $zone_configuration ) {
				$key = $selected_method['method_id'] . ':' . $selected_method['instance_id'];
				if ( isset( $zone_configuration['methods'][ $key ] )
					&& ! empty( $zone_configuration['methods'][ $key ]['enabled'] ) ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				return '';
			}
		}
		$material_options = self::commerce_material_options_snapshot();
		if ( empty( $material_options['enumeration_complete'] ) ) {
			return '';
		}
		$tax_configuration = self::commerce_tax_configuration();
		if ( empty( $tax_configuration ) || empty( $tax_configuration['readback_complete'] ) ) {
			return '';
		}
		$configuration = array(
			'plugin_version' => defined( 'COMPLETE99_PLATFORM_VERSION' ) ? COMPLETE99_PLATFORM_VERSION : '',
			'woo_version'    => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'currency'       => (string) $order->get_currency(),
			'payment_method' => sanitize_key( (string) $order->get_payment_method() ),
			'order_language' => self::transaction_language( $order ),
			'products'       => $products,
			'shipping'       => $shipping,
			'gateways'       => $gateway_configuration,
			'shipping_zones' => $shipping_configuration,
			'shopper_lists'  => array(
				'cart_save_for_later' => self::shopper_list_feature_is_enabled( 'cart_save_for_later' ),
				'product_wishlist'    => self::shopper_list_feature_is_enabled( 'product_wishlist' ),
			),
			'taxes'                  => $tax_configuration,
			'material_options'       => $material_options,
			'pages'                  => $pages,
			'localized_policy_pages' => $localized_policy_pages,
			'localized_store_pages'  => $localized_store_pages,
			'checkout_policy_copy'   => array(
				'he' => self::localized_checkout_policy_copy( 'he' ),
				'en' => self::localized_checkout_policy_copy( 'en' ),
			),
		);
		return self::digest_value( $configuration );
	}

	public static function capture_order_snapshot( $order, $data_store = null ) {
		if ( ! is_object( $order )
			|| ! is_a( $order, 'WC_Order' )
			|| empty( $order->get_items() )
			|| in_array( (string) $order->get_status(), array( 'checkout-draft', 'auto-draft', 'draft' ), true ) ) {
			return false;
		}
		return self::capture_order_event( 'order.snapshot', $order->get_id(), array(), $order );
	}

	public static function capture_order_status( $order_id, $from, $to, $order = null ) {
		if ( in_array( (string) $to, array( 'checkout-draft', 'auto-draft', 'draft' ), true ) ) {
			return false;
		}
		self::capture_order_event(
			'order.status_changed',
			$order_id,
			array(
				'from' => sanitize_key( (string) $from ),
				'to'   => sanitize_key( (string) $to ),
			),
			$order
		);
		return true;
	}

	public static function capture_refund_created( $refund_id, $args = array() ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$refund = wc_get_order( absint( $refund_id ) );
		if ( ! $refund || ! is_a( $refund, 'WC_Order_Refund' ) ) {
			return false;
		}
		$created = $refund->get_date_created();
		$restock_items = isset( $args['restock_items'] ) ? (bool) $args['restock_items'] : null;
		return self::enqueue_event(
			'refund.created',
			'refund',
			(string) $refund->get_id(),
			( $created ? $created->getTimestamp() : time() ) . '|' . (string) $refund->get_amount(),
			self::refund_created_event_payload( $refund, $restock_items )
		);
	}

	private static function refund_created_event_payload( $refund, $restock_items ) {
		if ( ! is_object( $refund )
			|| ! method_exists( $refund, 'get_id' )
			|| ! method_exists( $refund, 'get_parent_id' )
			|| ! method_exists( $refund, 'get_amount' )
			|| ! method_exists( $refund, 'get_currency' ) ) {
			return array();
		}
		return array(
			'refund_id'      => absint( $refund->get_id() ),
			'order_id'       => absint( $refund->get_parent_id() ),
			'amount'         => (string) abs( (float) $refund->get_amount() ),
			'currency'       => sanitize_text_field( (string) $refund->get_currency() ),
			'refunded_lines' => self::refund_lines( $refund ),
			'restock_items'  => is_bool( $restock_items ) ? $restock_items : null,
		);
	}

	public static function capture_refund_updated( $refund_id, $refund = null ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$refund = $refund && is_a( $refund, 'WC_Order_Refund' ) ? $refund : wc_get_order( absint( $refund_id ) );
		if ( ! $refund || ! is_a( $refund, 'WC_Order_Refund' ) ) {
			return false;
		}
		$modified = $refund->get_date_modified();
		return self::enqueue_event(
			'refund.updated',
			'refund',
			(string) $refund->get_id(),
			( $modified ? $modified->getTimestamp() : time() ) . '|' . (string) $refund->get_amount(),
			array(
				'refund_id'       => absint( $refund->get_id() ),
				'order_id'        => absint( $refund->get_parent_id() ),
				'amount'          => (string) abs( (float) $refund->get_amount() ),
				'currency'        => sanitize_text_field( (string) $refund->get_currency() ),
				'refunded_payment'=> method_exists( $refund, 'get_refunded_payment' ) ? (bool) $refund->get_refunded_payment() : false,
				'refunded_lines'  => self::refund_lines( $refund ),
			)
		);
	}

	public static function capture_refund_pre_delete( $check, $refund, $force_delete = false ) {
		if ( null !== $check ) {
			return $check;
		}
		if ( ! is_object( $refund )
			|| ! is_a( $refund, 'WC_Order_Refund' )
			|| ! method_exists( $refund, 'get_id' )
			|| ! method_exists( $refund, 'get_parent_id' )
			|| ! self::capture_refund_before_delete( $refund->get_id(), $refund ) ) {
			return false;
		}
		return null;
	}

	public static function capture_refund_before_delete( $order_id, $order = null ) {
		if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order_Refund' ) ) {
			return false;
		}
		$refund_id = absint( $order_id );
		$parent_id = absint( $order->get_parent_id() );
		self::$refund_delete_context[ $refund_id ] = $parent_id;
		return self::enqueue_event(
			'refund.delete_requested',
			'refund',
			(string) $refund_id,
			(string) $parent_id,
			array(
				'refund_id' => $refund_id,
				'order_id'  => $parent_id,
			)
		);
	}

	public static function capture_refund_before_post_delete( $post_id, $post = null ) {
		$post_type = is_object( $post ) && isset( $post->post_type ) ? (string) $post->post_type : '';
		if ( 'shop_order_refund' !== $post_type || ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$refund = wc_get_order( absint( $post_id ) );
		return self::capture_refund_before_delete( $post_id, $refund );
	}

	public static function capture_refund_deleted( $refund_id, $order_id = 0 ) {
		$refund_id = absint( $refund_id );
		$order_id  = absint( $order_id );
		if ( 1 > $order_id && isset( self::$refund_delete_context[ $refund_id ] ) ) {
			$order_id = absint( self::$refund_delete_context[ $refund_id ] );
		}
		unset( self::$refund_delete_context[ $refund_id ] );
		return self::enqueue_event(
			'refund.deleted',
			'refund',
			(string) $refund_id,
			(string) $order_id,
			array(
				'refund_id' => $refund_id,
				'order_id'  => $order_id,
			)
		);
	}

	public static function capture_fulfilment_change( $fulfilment, $changes = array(), $previous_status = '' ) {
		if ( ! is_object( $fulfilment )
			|| ! method_exists( $fulfilment, 'get_entity_type' )
			|| ! method_exists( $fulfilment, 'get_entity_id' )
			|| ! is_a( (string) $fulfilment->get_entity_type(), 'WC_Order', true ) ) {
			return false;
		}
		$order_id      = absint( $fulfilment->get_entity_id() );
		$fulfilment_id = method_exists( $fulfilment, 'get_id' ) ? absint( $fulfilment->get_id() ) : 0;
		$status        = method_exists( $fulfilment, 'get_status' ) ? sanitize_key( (string) $fulfilment->get_status() ) : '';
		$fulfilled     = method_exists( $fulfilment, 'get_is_fulfilled' ) && $fulfilment->get_is_fulfilled();
		$action        = function_exists( 'current_action' ) ? sanitize_key( (string) current_action() ) : 'fulfilment_changed';
		$deleted       = 'woocommerce_fulfillment_after_delete' === $action;
		$items         = array();
		if ( method_exists( $fulfilment, 'get_items' ) ) {
			foreach ( $fulfilment->get_items() as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$items[] = array(
					'item_id' => absint( $item['item_id'] ?? 0 ),
					'qty'     => (float) ( $item['qty'] ?? 0 ),
				);
			}
		}
		$observed_at = gmdate( 'c' );
		$event_version = self::fulfilment_event_version( $fulfilment_id, $status, $fulfilled, $action );
		$event_payload = self::fulfilment_event_payload(
			$order_id,
			$fulfilment_id,
			$status,
			(bool) $fulfilled && ! $deleted,
			$action,
			$previous_status,
			$items
		);
		$event_id    = '';
		$captured    = self::enqueue_event(
			'fulfilment.changed',
			'order',
			(string) $order_id,
			$event_version,
			$event_payload,
			$event_id
		);
		if ( ! $captured || ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) || ! function_exists( 'wc_get_order' ) ) {
			return $captured;
		}
		$order = wc_get_order( $order_id );
		if ( $order && method_exists( $order, 'update_meta_data' ) ) {
			$receipt = $order->get_meta( self::ORDER_FULFILMENT_RECEIPT, true );
			if ( ! is_array( $receipt )
				|| 'complete99-fulfilment-evidence/v2' !== ( $receipt['schema'] ?? '' )
				|| absint( $receipt['order_id'] ?? 0 ) !== $order_id
				|| ! isset( $receipt['fulfilments'] )
				|| ! is_array( $receipt['fulfilments'] ) ) {
				$receipt = array(
					'schema'       => 'complete99-fulfilment-evidence/v2',
					'order_id'     => $order_id,
					'updated_at'   => $observed_at,
					'fulfilments' => array(),
				);
			}
			if ( $deleted ) {
				unset( $receipt['fulfilments'][ $fulfilment_id ] );
			} else {
				$receipt['fulfilments'][ $fulfilment_id ] = array(
					'fulfilment_id' => $fulfilment_id,
					'status'         => $status,
					'fulfilled'      => (bool) $fulfilled,
					'observed_at'    => $observed_at,
					'event'          => $action,
					'previous_status'=> sanitize_key( (string) $previous_status ),
					'event_version'  => $event_version,
					'payload_digest' => self::digest_value( $event_payload ),
					'event_id'       => $event_id,
					'items'          => $items,
				);
			}
			$receipt['updated_at'] = $observed_at;
			$order->update_meta_data(
				self::ORDER_FULFILMENT_RECEIPT,
				$receipt
			);
			$order->save_meta_data();
		}
		return $captured;
	}

	private static function fulfilment_event_version( $fulfilment_id, $status, $fulfilled, $action ) {
		return absint( $fulfilment_id )
			. '|' . sanitize_key( (string) $status )
			. '|' . ( $fulfilled ? '1' : '0' )
			. '|' . sanitize_key( (string) $action );
	}

	private static function fulfilment_event_payload( $order_id, $fulfilment_id, $status, $fulfilled, $action, $previous_status, $items ) {
		$normalized_items = array();
		foreach ( (array) $items as $item ) {
			$normalized_items[] = array(
				'item_id' => absint( $item['item_id'] ?? 0 ),
				'qty'     => (float) ( $item['qty'] ?? 0 ),
			);
		}
		return array(
			'order_id'        => absint( $order_id ),
			'fulfilment_id'   => absint( $fulfilment_id ),
			'status'          => sanitize_key( (string) $status ),
			'fulfilled'       => (bool) $fulfilled,
			'event'           => sanitize_key( (string) $action ),
			'previous_status' => sanitize_key( (string) $previous_status ),
			'items'           => $normalized_items,
		);
	}

	private static function refund_lines( $refund ) {
		$lines = array();
		foreach ( $refund->get_items() as $item_id => $item ) {
			$lines[] = array(
				'line_item_id' => absint( $item_id ),
				'product_id'   => absint( $item->get_product_id() ),
				'variation_id' => absint( $item->get_variation_id() ),
				'quantity'     => abs( (float) $item->get_quantity() ),
				'total'        => (string) abs( (float) $item->get_total() ),
			);
		}
		return $lines;
	}

	private static function order_event_payload( $order, $change = array() ) {
		if ( ! is_object( $order )
			|| ! method_exists( $order, 'get_id' )
			|| ! method_exists( $order, 'get_items' ) ) {
			return array();
		}
		$lines = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$lines[] = array(
				'line_item_id' => absint( $item_id ),
				'product_id'   => absint( $item->get_product_id() ),
				'variation_id' => absint( $item->get_variation_id() ),
				'sku'          => (string) ( $item->get_product() ? $item->get_product()->get_sku() : '' ),
				'quantity'     => (float) $item->get_quantity(),
				'subtotal'     => (string) $item->get_subtotal(),
				'total'        => (string) $item->get_total(),
			);
		}
		$shipping = array();
		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$shipping[] = array(
				'method_id'   => is_object( $item ) && method_exists( $item, 'get_method_id' ) ? sanitize_key( (string) $item->get_method_id() ) : '',
				'instance_id' => is_object( $item ) && method_exists( $item, 'get_instance_id' ) ? absint( $item->get_instance_id() ) : 0,
			);
		}
		$fulfilment = method_exists( $order, 'get_meta' ) ? $order->get_meta( self::ORDER_FULFILMENT_RECEIPT, true ) : array();
		return array(
			'order_id'       => absint( $order->get_id() ),
			'order_number'   => sanitize_text_field( (string) $order->get_order_number() ),
			'status'         => sanitize_key( (string) $order->get_status() ),
			'currency'       => sanitize_text_field( (string) $order->get_currency() ),
			'total'          => (string) $order->get_total(),
			'payment_method' => sanitize_key( (string) $order->get_payment_method() ),
			'lines'          => $lines,
			'shipping'       => $shipping,
			'fulfilment'     => is_array( $fulfilment )
				? array(
					'status'    => sanitize_key( (string) ( $fulfilment['status'] ?? '' ) ),
					'fulfilled' => ! empty( $fulfilment['fulfilled'] ),
				)
				: array(),
			'change'         => self::canonicalize_digest_value( is_array( $change ) ? $change : array() ),
		);
	}

	private static function order_event_version( $order ) {
		$modified = is_object( $order ) && method_exists( $order, 'get_date_modified' )
			? $order->get_date_modified()
			: false;
		$status = is_object( $order ) && method_exists( $order, 'get_status' )
			? (string) $order->get_status()
			: '';
		return ( $modified ? $modified->getTimestamp() : time() ) . '|' . $status;
	}

	private static function capture_order_event( $type, $order_id, $change = array(), $order = null ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$order = $order && is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		$payload = self::order_event_payload( $order, $change );
		if ( empty( $payload ) ) {
			return false;
		}
		return self::enqueue_event(
			$type,
			'order',
			(string) $order->get_id(),
			self::order_event_version( $order ),
			$payload
		);
	}

	public static function capture_product_stock( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return false;
		}
		self::invalidate_commerce_state_cache();
		$modified = method_exists( $product, 'get_date_modified' ) ? $product->get_date_modified() : null;
		$version  = $modified ? $modified->getTimestamp() : time();
		$captured = self::enqueue_event(
			'inventory.stock_changed',
			'product',
			(string) $product->get_id(),
			$version . '|' . (string) $product->get_stock_quantity(),
			array(
				'product_id'     => absint( $product->get_id() ),
				'sku'            => sanitize_text_field( (string) $product->get_sku() ),
				'stock_quantity' => null === $product->get_stock_quantity() ? null : (float) $product->get_stock_quantity(),
				'stock_status'   => sanitize_key( (string) $product->get_stock_status() ),
			)
		);
		if ( ! $captured ) {
			return false;
		}
		if ( ! self::purge_commerce_caches() ) {
			self::record_outbox_error( 'cache' );
			return false;
		}
		return true;
	}

	private static function build_outbox_event( $type, $entity, $entity_id, $version, $payload ) {
		$type           = sanitize_key( str_replace( '.', '_', (string) $type ) );
		$entity         = sanitize_key( (string) $entity );
		$entity_id      = sanitize_text_field( (string) $entity_id );
		$version        = sanitize_text_field( (string) $version );
		$payload        = self::canonicalize_digest_value( is_array( $payload ) ? $payload : array() );
		$payload_digest = self::digest_value( $payload );
		$occurred_at    = gmdate( 'c' );
		$event_id       = self::outbox_event_id( $type, $entity, $entity_id, $version, $occurred_at, $payload_digest );
		return array(
			'id'             => $event_id,
			'id_version'     => self::OUTBOX_ID_VERSION,
			'schema'         => self::OUTBOX_SCHEMA,
			'type'           => $type,
			'entity'         => $entity,
			'entity_id'      => $entity_id,
			'event_version'  => $version,
			'occurred_at'    => $occurred_at,
			'payload_digest' => $payload_digest,
			'payload'        => $payload,
		);
	}

	private static function outbox_event_id( $type, $entity, $entity_id, $version, $occurred_at, $payload_digest ) {
		return self::digest_value(
			array(
				'id_version'     => self::OUTBOX_ID_VERSION,
				'type'           => (string) $type,
				'entity'         => (string) $entity,
				'entity_id'      => (string) $entity_id,
				'event_version'  => (string) $version,
				'occurred_at'    => (string) $occurred_at,
				'payload_digest' => (string) $payload_digest,
			)
		);
	}

	private static function is_valid_outbox_event( $event ) {
		if ( ! is_array( $event )
			|| self::OUTBOX_SCHEMA !== ( $event['schema'] ?? '' )
			|| self::OUTBOX_ID_VERSION !== absint( $event['id_version'] ?? 0 )
			|| ! isset( $event['id'], $event['type'], $event['entity'], $event['entity_id'], $event['event_version'], $event['occurred_at'], $event['payload_digest'], $event['payload'] )
			|| ! is_array( $event['payload'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $event['id'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $event['payload_digest'] )
			|| '' === (string) $event['type']
			|| '' === (string) $event['entity']
			|| '' === (string) $event['entity_id']
			|| '' === (string) $event['event_version']
			|| sanitize_key( (string) $event['type'] ) !== (string) $event['type']
			|| sanitize_key( (string) $event['entity'] ) !== (string) $event['entity']
			|| sanitize_text_field( (string) $event['entity_id'] ) !== (string) $event['entity_id']
			|| sanitize_text_field( (string) $event['event_version'] ) !== (string) $event['event_version']
			|| sanitize_text_field( (string) $event['occurred_at'] ) !== (string) $event['occurred_at'] ) {
			return false;
		}
		$occurred_at = strtotime( (string) $event['occurred_at'] );
		if ( false === $occurred_at
			|| $occurred_at > time() + 300
			|| gmdate( 'c', $occurred_at ) !== (string) $event['occurred_at'] ) {
			return false;
		}
		$payload_digest = self::digest_value( $event['payload'] );
		if ( '' === $payload_digest || ! hash_equals( (string) $event['payload_digest'], $payload_digest ) ) {
			return false;
		}
		$expected_id = self::outbox_event_id(
			(string) $event['type'],
			(string) $event['entity'],
			(string) $event['entity_id'],
			(string) $event['event_version'],
			(string) $event['occurred_at'],
			$payload_digest
		);
		return hash_equals( $expected_id, (string) $event['id'] );
	}

	private static function enqueue_event( $type, $entity, $entity_id, $version, $payload, &$stored_event_id = null ) {
		self::mark_public_commerce_state_dirty();
		$stored_event_id = '';
		$event           = self::build_outbox_event( $type, $entity, $entity_id, $version, $payload );
		$event_id        = (string) $event['id'];
		if ( ! self::acquire_outbox_lock() ) {
			self::record_outbox_error( 'lock' );
			self::record_outbox_failure( 'lock', $event );
			do_action( 'complete99_commerce_outbox_error', 'lock', $type, $entity_id );
			return false;
		}
		try {
			self::clear_outbox_error( 'lock' );
			wp_cache_delete( self::OPTION_OUTBOX, 'options' );
			if ( ! self::outbox_durable_state_is_valid() ) {
				self::record_outbox_failure( 'event_corrupt', $event );
				do_action( 'complete99_commerce_outbox_error', 'event_corrupt', $type, $entity_id );
				return false;
			}
			$outbox = self::outbox();
			if ( count( $outbox ) >= self::MAX_OUTBOX_EVENTS ) {
				self::record_outbox_error( 'capacity' );
				self::record_outbox_failure( 'capacity', $event );
				do_action( 'complete99_commerce_outbox_error', 'capacity', $type, $entity_id );
				return false;
			}
			self::clear_outbox_error( 'capacity' );

			foreach ( $outbox as $stored_event ) {
				if ( isset( $stored_event['id'] ) && hash_equals( (string) $stored_event['id'], $event_id ) ) {
					self::clear_outbox_failure( $event_id );
					self::clear_outbox_error( 'readback' );
					$stored_event_id = $event_id;
					return true;
				}
			}

			$outbox[] = $event;
			update_option( self::OPTION_OUTBOX, $outbox, false );
			wp_cache_delete( self::OPTION_OUTBOX, 'options' );

			$stored = self::outbox();
			foreach ( $stored as $stored_event ) {
				if ( isset( $stored_event['id'] ) && hash_equals( (string) $stored_event['id'], $event_id ) ) {
					self::clear_outbox_failure( $event_id );
					self::clear_outbox_error( 'readback' );
					$stored_event_id = $event_id;
					return true;
				}
			}
			self::record_outbox_error( 'readback' );
			self::record_outbox_failure( 'readback', $event );
			do_action( 'complete99_commerce_outbox_error', 'readback', $type, $entity_id );
			return false;
		} finally {
			self::release_outbox_lock();
		}
	}

	private static function acquire_outbox_lock() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return false;
		}
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::outbox_lock_name(), 3 ) );
		return '1' === (string) $locked;
	}

	private static function outbox_lock_name() {
		$site_id = function_exists( 'get_current_blog_id' ) ? absint( get_current_blog_id() ) : 0;
		$site    = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
		return 'complete99-commerce-' . substr( hash( 'sha256', $site_id . '|' . $site ), 0, 32 );
	}

	private static function release_outbox_lock() {
		global $wpdb;
		if ( is_object( $wpdb ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'get_var' ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::outbox_lock_name() ) );
		}
	}

	private static function record_outbox_error( $code ) {
		self::mark_public_commerce_state_dirty();
		$code = sanitize_key( (string) $code );
		if ( ! in_array( $code, self::outbox_error_codes(), true ) ) {
			$code = 'unknown';
		}
		update_option(
			self::OPTION_OUTBOX_ERROR_PREFIX . $code,
			array(
				'schema'      => 'complete99-commerce-outbox-error/v2',
				'status'      => sanitize_key( (string) $code ),
				'occurred_at' => gmdate( 'c' ),
			),
			false
		);
		wp_cache_delete( self::OPTION_OUTBOX_ERROR_PREFIX . $code, 'options' );
	}

	private static function outbox_error_codes() {
		return array(
			'cache',
			'lock',
			'capacity',
			'readback',
			'event_corrupt',
			'failure_lock',
			'failure_capacity',
			'failure_readback',
			'failure_emergency_readback',
			'failure_corrupt',
			'failure_unrecoverable',
			'audit_capacity',
			'audit_readback',
			'audit_corrupt',
			'error_corrupt',
			'configuration_state',
			'launch_state',
			'unknown',
		);
	}

	private static function outbox_errors() {
		$errors = array();
		$missing = new \stdClass();
		$corrupt = false;
		$legacy = get_option( self::OPTION_OUTBOX_ERROR, $missing );
		if ( $missing !== $legacy ) {
			if ( is_array( $legacy ) && ! empty( $legacy['status'] ) ) {
				$errors[ sanitize_key( (string) $legacy['status'] ) ] = $legacy;
			} else {
				$corrupt = true;
			}
		}
		foreach ( self::outbox_error_codes() as $code ) {
			$error = get_option( self::OPTION_OUTBOX_ERROR_PREFIX . $code, $missing );
			if ( $missing === $error ) {
				continue;
			}
			if ( is_array( $error )
				&& 'complete99-commerce-outbox-error/v2' === ( $error['schema'] ?? '' )
				&& $code === (string) ( $error['status'] ?? '' ) ) {
				$errors[ $code ] = $error;
			} else {
				$corrupt = true;
			}
		}
		if ( $corrupt ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'error_corrupt' );
			$errors['error_corrupt'] = array(
				'schema'      => 'complete99-commerce-outbox-error/v2',
				'status'      => 'error_corrupt',
				'occurred_at' => gmdate( 'c' ),
			);
		}
		return $errors;
	}

	private static function clear_outbox_error( $code ) {
		self::mark_public_commerce_state_dirty();
		$code = sanitize_key( (string) $code );
		delete_option( self::OPTION_OUTBOX_ERROR_PREFIX . $code );
		wp_cache_delete( self::OPTION_OUTBOX_ERROR_PREFIX . $code, 'options' );
		$legacy = get_option( self::OPTION_OUTBOX_ERROR, array() );
		if ( is_array( $legacy ) && $code === (string) ( $legacy['status'] ?? '' ) ) {
			delete_option( self::OPTION_OUTBOX_ERROR );
			wp_cache_delete( self::OPTION_OUTBOX_ERROR, 'options' );
		}
	}

	private static function acquire_outbox_failures_lock() {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return false;
		}
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::outbox_lock_name() . '-failures', 3 ) );
		return '1' === (string) $locked;
	}

	private static function release_outbox_failures_lock() {
		global $wpdb;
		if ( is_object( $wpdb ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'get_var' ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::outbox_lock_name() . '-failures' ) );
		}
	}

	private static function outbox_failures() {
		$failures = self::main_outbox_failures();
		foreach ( self::emergency_outbox_failures() as $failure ) {
			$event_id = (string) $failure['event']['id'];
			$found    = false;
			foreach ( $failures as $stored ) {
				if ( hash_equals( (string) $stored['event']['id'], $event_id ) ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$failures[] = $failure;
			}
		}
		return $failures;
	}

	private static function main_outbox_failures() {
		$raw = get_option( self::OPTION_OUTBOX_FAILURES, array() );
		if ( ! is_array( $raw ) ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'failure_readback' );
			self::record_outbox_error( 'failure_corrupt' );
			return array();
		}
		$failures = array_values(
			array_filter(
				$raw,
				static function ( $failure ) {
					return self::is_valid_outbox_failure( $failure );
				}
			)
		);
		if ( count( $raw ) !== count( $failures ) ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'failure_readback' );
			self::record_outbox_error( 'failure_corrupt' );
		}
		return $failures;
	}

	private static function is_valid_outbox_failure( $failure ) {
		if ( ! is_array( $failure )
			|| self::OUTBOX_FAILURE_SCHEMA !== ( $failure['schema'] ?? '' )
			|| ! isset( $failure['code'], $failure['failed_at'], $failure['failure_digest'], $failure['event'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $failure['failure_digest'] )
			|| '' === (string) $failure['code']
			|| sanitize_key( (string) $failure['code'] ) !== (string) $failure['code']
			|| ! self::is_valid_outbox_event( $failure['event'] ) ) {
			return false;
		}
		$failed_at = strtotime( (string) $failure['failed_at'] );
		if ( false === $failed_at
			|| $failed_at > time() + 300
			|| gmdate( 'c', $failed_at ) !== (string) $failure['failed_at'] ) {
			return false;
		}
		$expected_digest = self::outbox_failure_digest(
			(string) $failure['code'],
			(string) $failure['failed_at'],
			(string) $failure['event']['id']
		);
		return hash_equals( $expected_digest, (string) $failure['failure_digest'] );
	}

	private static function outbox_failure_digest( $code, $failed_at, $event_id ) {
		return self::digest_value(
			array(
				'schema'    => self::OUTBOX_FAILURE_SCHEMA,
				'code'      => (string) $code,
				'failed_at' => (string) $failed_at,
				'event_id'  => (string) $event_id,
			)
		);
	}

	private static function emergency_outbox_failures() {
		global $wpdb;
		self::$emergency_outbox_readback_failed = false;
		if ( ! is_object( $wpdb )
			|| ! isset( $wpdb->options )
			|| ! method_exists( $wpdb, 'prepare' )
			|| ! method_exists( $wpdb, 'esc_like' )
			|| ! method_exists( $wpdb, 'get_results' ) ) {
			self::$emergency_outbox_readback_failed = true;
			if ( function_exists( 'sanitize_key' ) && function_exists( 'update_option' ) ) {
				self::record_outbox_error( 'failure_readback' );
				self::record_outbox_error( 'failure_emergency_readback' );
			}
			return array();
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( self::OPTION_OUTBOX_FAILURE_EVENT_PREFIX ) . '%'
			)
		);
		if ( ! is_array( $rows ) ) {
			self::$emergency_outbox_readback_failed = true;
			self::record_outbox_error( 'failure_readback' );
			self::record_outbox_error( 'failure_emergency_readback' );
			return array();
		}
		self::clear_outbox_error( 'failure_emergency_readback' );
		$failures = array();
		$invalid_rows = 0;
		foreach ( $rows as $row ) {
			$value = isset( $row->option_value ) ? maybe_unserialize( $row->option_value ) : array();
			if ( self::is_valid_outbox_failure( $value ) ) {
				$failures[] = $value;
			} else {
				$invalid_rows++;
			}
		}
		if ( 0 < $invalid_rows ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'failure_readback' );
			self::record_outbox_error( 'failure_corrupt' );
		}
		return $failures;
	}

	private static function record_emergency_outbox_failure( $code, $event ) {
		if ( ! self::is_valid_outbox_event( $event ) ) {
			self::record_outbox_error( 'failure_unrecoverable' );
			return false;
		}
		$code = sanitize_key( (string) $code );
		if ( ! in_array( $code, self::outbox_error_codes(), true ) ) {
			$code = 'unknown';
		}
		$failed_at = gmdate( 'c' );
		$failure = array(
			'schema'         => self::OUTBOX_FAILURE_SCHEMA,
			'code'           => $code,
			'failed_at'      => $failed_at,
			'failure_digest' => self::outbox_failure_digest( $code, $failed_at, (string) $event['id'] ),
			'event'          => $event,
		);
		$name = self::OPTION_OUTBOX_FAILURE_EVENT_PREFIX . (string) $event['id'];
		if ( function_exists( 'add_option' ) ) {
			add_option( $name, $failure, '', 'no' );
		} else {
			update_option( $name, $failure, false );
		}
		wp_cache_delete( $name, 'options' );
		$stored = get_option( $name, array() );
		if ( ! self::is_valid_outbox_failure( $stored )
			|| ! hash_equals( (string) $stored['event']['id'], (string) $event['id'] ) ) {
			self::record_outbox_error( 'failure_unrecoverable' );
			return false;
		}
		return true;
	}

	private static function record_outbox_failure( $code, $event ) {
		if ( ! self::is_valid_outbox_event( $event ) ) {
			return false;
		}
		$code = sanitize_key( (string) $code );
		if ( ! in_array( $code, self::outbox_error_codes(), true ) ) {
			$code = 'unknown';
		}
		if ( ! self::acquire_outbox_failures_lock() ) {
			self::record_outbox_error( 'failure_lock' );
			return self::record_emergency_outbox_failure( $code, $event );
		}
		try {
			self::clear_outbox_error( 'failure_lock' );
			wp_cache_delete( self::OPTION_OUTBOX_FAILURES, 'options' );
			$failures = self::main_outbox_failures();
			if ( self::$outbox_corruption_detected ) {
				self::record_outbox_error( 'failure_corrupt' );
				return self::record_emergency_outbox_failure( $code, $event );
			}
			$failures = array_values(
				array_filter(
					$failures,
					static function ( $failure ) use ( $event ) {
						return ! hash_equals( (string) $failure['event']['id'], (string) $event['id'] );
					}
				)
			);
			if ( count( $failures ) >= self::MAX_OUTBOX_FAILURES ) {
				self::record_outbox_error( 'failure_capacity' );
				return self::record_emergency_outbox_failure( $code, $event );
			}
			self::clear_outbox_error( 'failure_capacity' );
			$failed_at = gmdate( 'c' );
			$failures[] = array(
				'schema'         => self::OUTBOX_FAILURE_SCHEMA,
				'code'           => $code,
				'failed_at'      => $failed_at,
				'failure_digest' => self::outbox_failure_digest( $code, $failed_at, (string) $event['id'] ),
				'event'          => $event,
			);
			update_option( self::OPTION_OUTBOX_FAILURES, $failures, false );
			wp_cache_delete( self::OPTION_OUTBOX_FAILURES, 'options' );
			if ( hash( 'sha256', serialize( $failures ) ) !== hash( 'sha256', serialize( self::main_outbox_failures() ) ) ) {
				self::record_outbox_error( 'failure_readback' );
				return self::record_emergency_outbox_failure( $code, $event );
			}
			self::clear_outbox_error( 'failure_readback' );
			return true;
		} finally {
			self::release_outbox_failures_lock();
		}
	}

	private static function clear_outbox_failure( $event_id ) {
		self::mark_public_commerce_state_dirty();
		$event_id = strtolower( sanitize_text_field( (string) $event_id ) );
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $event_id ) ) {
			return false;
		}
		if ( ! self::acquire_outbox_failures_lock() ) {
			self::record_outbox_error( 'failure_lock' );
			return false;
		}
		try {
			self::clear_outbox_error( 'failure_lock' );
			wp_cache_delete( self::OPTION_OUTBOX_FAILURES, 'options' );
			$before = self::main_outbox_failures();
			if ( self::$outbox_corruption_detected ) {
				self::record_outbox_error( 'failure_corrupt' );
				return false;
			}
			$emergency_name = self::OPTION_OUTBOX_FAILURE_EVENT_PREFIX . $event_id;
			$emergency      = get_option( $emergency_name, array() );
			$has_emergency  = self::is_valid_outbox_failure( $emergency )
				&& hash_equals( (string) $emergency['event']['id'], (string) $event_id );
			$removed_codes = array();
			if ( $has_emergency ) {
				$removed_codes[] = sanitize_key( (string) $emergency['code'] );
			}
			$after  = array_values(
				array_filter(
					$before,
					static function ( $failure ) use ( $event_id, &$removed_codes ) {
						$keep = ! hash_equals( (string) $failure['event']['id'], (string) $event_id );
						if ( ! $keep ) {
							$removed_codes[] = sanitize_key( (string) $failure['code'] );
						}
						return $keep;
					}
				)
			);
			if ( count( $before ) === count( $after ) && ! $has_emergency ) {
				return false;
			}
			update_option( self::OPTION_OUTBOX_FAILURES, $after, false );
			delete_option( $emergency_name );
			wp_cache_delete( $emergency_name, 'options' );
			wp_cache_delete( self::OPTION_OUTBOX_FAILURES, 'options' );
			$stored_main = self::main_outbox_failures();
			if ( hash( 'sha256', serialize( $after ) ) !== hash( 'sha256', serialize( $stored_main ) )
				|| self::is_valid_outbox_failure( get_option( $emergency_name, array() ) ) ) {
				self::record_outbox_error( 'failure_readback' );
				return false;
			}
			$stored = self::outbox_failures();
			if ( ! self::$emergency_outbox_readback_failed ) {
				self::clear_outbox_error( 'failure_readback' );
			}
			if ( count( self::main_outbox_failures() ) < self::MAX_OUTBOX_FAILURES ) {
				self::clear_outbox_error( 'failure_capacity' );
			}
			foreach ( array_unique( $removed_codes ) as $removed_code ) {
				$still_present = false;
				foreach ( $stored as $failure ) {
					if ( $removed_code === sanitize_key( (string) $failure['code'] ) ) {
						$still_present = true;
						break;
					}
				}
				if ( ! $still_present ) {
					self::clear_outbox_error( $removed_code );
				}
			}
			return true;
		} finally {
			self::release_outbox_failures_lock();
		}
	}

	private static function is_valid_outbox_ack( $entry ) {
		if ( ! is_array( $entry )
			|| self::OUTBOX_ACK_SCHEMA !== ( $entry['schema'] ?? '' )
			|| self::OUTBOX_ID_VERSION !== absint( $entry['id_version'] ?? 0 )
			|| ! isset( $entry['id'], $entry['type'], $entry['entity'], $entry['entity_id'], $entry['event_version'], $entry['occurred_at'], $entry['acknowledged_at'], $entry['ack_digest'], $entry['payload_digest'], $entry['payload'] )
			|| ! is_array( $entry['payload'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $entry['id'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $entry['payload_digest'] )
			|| ! preg_match( '/^[a-f0-9]{64}$/', (string) $entry['ack_digest'] )
			|| '' === (string) $entry['type']
			|| '' === (string) $entry['entity']
			|| '' === (string) $entry['entity_id']
			|| '' === (string) $entry['event_version']
			|| sanitize_key( (string) $entry['type'] ) !== (string) $entry['type']
			|| sanitize_key( (string) $entry['entity'] ) !== (string) $entry['entity']
			|| sanitize_text_field( (string) $entry['entity_id'] ) !== (string) $entry['entity_id']
			|| sanitize_text_field( (string) $entry['event_version'] ) !== (string) $entry['event_version']
			|| sanitize_text_field( (string) $entry['occurred_at'] ) !== (string) $entry['occurred_at']
			|| sanitize_text_field( (string) $entry['acknowledged_at'] ) !== (string) $entry['acknowledged_at'] ) {
			return false;
		}
		$occurred_at     = strtotime( (string) $entry['occurred_at'] );
		$acknowledged_at = strtotime( (string) $entry['acknowledged_at'] );
		if ( false === $occurred_at
			|| false === $acknowledged_at
			|| $occurred_at > time() + 300
			|| $acknowledged_at > time() + 300
			|| $acknowledged_at < $occurred_at
			|| gmdate( 'c', $occurred_at ) !== (string) $entry['occurred_at']
			|| gmdate( 'c', $acknowledged_at ) !== (string) $entry['acknowledged_at'] ) {
			return false;
		}
		$payload_digest = self::digest_value( $entry['payload'] );
		if ( '' === $payload_digest || ! hash_equals( (string) $entry['payload_digest'], $payload_digest ) ) {
			return false;
		}
		$expected_id = self::outbox_event_id(
			(string) $entry['type'],
			(string) $entry['entity'],
			(string) $entry['entity_id'],
			(string) $entry['event_version'],
			(string) $entry['occurred_at'],
			$payload_digest
		);
		$expected_ack_digest = self::digest_value(
			array(
				'schema'          => self::OUTBOX_ACK_SCHEMA,
				'event_id'        => (string) $entry['id'],
				'acknowledged_at' => (string) $entry['acknowledged_at'],
			)
		);
		return hash_equals( $expected_id, (string) $entry['id'] )
			&& hash_equals( $expected_ack_digest, (string) $entry['ack_digest'] );
	}

	private static function outbox_ack_audit() {
		$raw = get_option( self::OPTION_OUTBOX_AUDIT, array() );
		if ( ! is_array( $raw ) ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'audit_readback' );
			self::record_outbox_error( 'audit_corrupt' );
			return array();
		}
		$entries  = array();
		$seen     = array();
		$corrupt  = false;
		foreach ( $raw as $entry ) {
			$event_id = is_array( $entry ) ? (string) ( $entry['id'] ?? '' ) : '';
			if ( ! self::is_valid_outbox_ack( $entry ) || isset( $seen[ $event_id ] ) ) {
				$corrupt = true;
				continue;
			}
			$seen[ $event_id ] = true;
			$entries[]         = $entry;
		}
		if ( $corrupt || count( $raw ) !== count( $entries ) ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'audit_readback' );
			self::record_outbox_error( 'audit_corrupt' );
		}
		return $entries;
	}

	private static function outbox_durable_state_is_valid() {
		self::outbox();
		self::main_outbox_failures();
		self::emergency_outbox_failures();
		self::outbox_ack_audit();
		self::outbox_errors();
		return ! self::$outbox_corruption_detected && ! self::$emergency_outbox_readback_failed;
	}

	private static function latest_outbox_event( $type, $entity_id ) {
		$matches = array();
		foreach ( array_merge( self::outbox(), self::outbox_ack_audit() ) as $event ) {
			if ( (string) $event['type'] === (string) $type
				&& (string) $event['entity_id'] === (string) $entity_id ) {
				$matches[] = $event;
			}
		}
		if ( empty( $matches ) ) {
			return array();
		}
		usort(
			$matches,
			static function ( $left, $right ) {
				return strcmp( (string) $left['occurred_at'], (string) $right['occurred_at'] );
			}
		);
		return end( $matches );
	}

	private static function latest_pending_outbox_event( $type, $entity_id ) {
		$matches = array();
		foreach ( self::outbox() as $event ) {
			if ( (string) $event['type'] === (string) $type
				&& (string) $event['entity_id'] === (string) $entity_id ) {
				$matches[] = $event;
			}
		}
		if ( empty( $matches ) ) {
			return array();
		}
		usort(
			$matches,
			static function ( $left, $right ) {
				return strcmp( (string) $left['occurred_at'], (string) $right['occurred_at'] );
			}
		);
		return end( $matches );
	}

	private static function outbox_has_event_id( $event_id, $type, $entity_id ) {
		foreach ( array_merge( self::outbox(), self::outbox_ack_audit() ) as $event ) {
			if ( hash_equals( (string) $event['id'], (string) $event_id )
				&& (string) $event['type'] === (string) $type
				&& (string) $event['entity_id'] === (string) $entity_id ) {
				return true;
			}
		}
		return false;
	}

	private static function pending_outbox_has_event_id( $event_id, $type, $entity_id ) {
		foreach ( self::outbox() as $event ) {
			if ( hash_equals( (string) $event['id'], (string) $event_id )
				&& (string) $event['type'] === (string) $type
				&& (string) $event['entity_id'] === (string) $entity_id ) {
				return true;
			}
		}
		return false;
	}

	private static function outbox() {
		$raw = get_option( self::OPTION_OUTBOX, array() );
		if ( ! is_array( $raw ) ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'readback' );
			self::record_outbox_error( 'event_corrupt' );
			return array();
		}
		$events  = array();
		$seen    = array();
		$corrupt = false;
		foreach ( $raw as $event ) {
			$event_id = is_array( $event ) ? (string) ( $event['id'] ?? '' ) : '';
			if ( ! self::is_valid_outbox_event( $event ) || isset( $seen[ $event_id ] ) ) {
				$corrupt = true;
				continue;
			}
			$seen[ $event_id ] = true;
			$events[]          = $event;
		}
		if ( $corrupt ) {
			self::$outbox_corruption_detected = true;
			self::record_outbox_error( 'readback' );
			self::record_outbox_error( 'event_corrupt' );
		} elseif ( false !== get_option( self::OPTION_OUTBOX_ERROR_PREFIX . 'event_corrupt', false ) ) {
			self::clear_outbox_error( 'event_corrupt' );
		}
		return $events;
	}

	public static function read_order_operation_details( WP_REST_Request $request ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return new WP_Error( 'complete99_commerce_dependency', 'WooCommerce is not available.', array( 'status' => 409 ) );
		}
		$order = wc_get_order( absint( $request->get_param( 'id' ) ) );
		if ( ! $order || ! is_a( $order, 'WC_Order' ) || is_a( $order, 'WC_Order_Refund' ) ) {
			return new WP_Error( 'complete99_commerce_order_not_found', 'Order not found.', array( 'status' => 404 ) );
		}
		$lines = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$lines[] = array(
				'line_item_id' => absint( $item_id ),
				'product_id'   => absint( $item->get_product_id() ),
				'variation_id' => absint( $item->get_variation_id() ),
				'name_he'      => sanitize_text_field( (string) $item->get_meta( self::ITEM_NAME_HE, true ) ),
				'name_en'      => sanitize_text_field( (string) $item->get_meta( self::ITEM_NAME_EN, true ) ),
				'quantity'     => (float) $item->get_quantity(),
				'total'        => (string) $item->get_total(),
			);
		}
		$shipping_methods = array();
		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$shipping_methods[] = array(
				'method_id'    => sanitize_key( (string) $item->get_method_id() ),
				'instance_id'  => absint( $item->get_instance_id() ),
				'method_title' => sanitize_text_field( (string) $item->get_method_title() ),
				'total'        => (string) $item->get_total(),
			);
		}
		$refunds = array();
		foreach ( $order->get_refunds() as $refund ) {
			$refunds[] = array(
				'refund_id'       => absint( $refund->get_id() ),
				'amount'          => (string) abs( (float) $refund->get_amount() ),
				'refunded_payment'=> method_exists( $refund, 'get_refunded_payment' ) ? (bool) $refund->get_refunded_payment() : false,
				'created_at'      => $refund->get_date_created() ? $refund->get_date_created()->date( 'c' ) : '',
			);
		}
		$response = rest_ensure_response(
			array(
				'schema'          => 'complete99-commerce-order-operations/v1',
				'order_id'        => absint( $order->get_id() ),
				'order_number'    => sanitize_text_field( (string) $order->get_order_number() ),
				'status'          => sanitize_key( (string) $order->get_status() ),
				'created_at'      => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
				'paid_at'         => $order->get_date_paid() ? $order->get_date_paid()->date( 'c' ) : '',
				'currency'        => sanitize_text_field( (string) $order->get_currency() ),
				'total'           => (string) $order->get_total(),
				'payment_method'  => sanitize_key( (string) $order->get_payment_method() ),
				'order_language'  => self::transaction_language( $order ),
				'billing'         => array(
					'first_name' => sanitize_text_field( (string) $order->get_billing_first_name() ),
					'last_name'  => sanitize_text_field( (string) $order->get_billing_last_name() ),
					'company'    => sanitize_text_field( (string) $order->get_billing_company() ),
					'address_1'  => sanitize_text_field( (string) $order->get_billing_address_1() ),
					'address_2'  => sanitize_text_field( (string) $order->get_billing_address_2() ),
					'city'       => sanitize_text_field( (string) $order->get_billing_city() ),
					'state'      => sanitize_text_field( (string) $order->get_billing_state() ),
					'postcode'   => sanitize_text_field( (string) $order->get_billing_postcode() ),
					'country'    => sanitize_text_field( (string) $order->get_billing_country() ),
					'email'      => sanitize_email( (string) $order->get_billing_email() ),
					'phone'      => sanitize_text_field( (string) $order->get_billing_phone() ),
				),
				'shipping'        => array(
					'first_name' => sanitize_text_field( (string) $order->get_shipping_first_name() ),
					'last_name'  => sanitize_text_field( (string) $order->get_shipping_last_name() ),
					'company'    => sanitize_text_field( (string) $order->get_shipping_company() ),
					'address_1'  => sanitize_text_field( (string) $order->get_shipping_address_1() ),
					'address_2'  => sanitize_text_field( (string) $order->get_shipping_address_2() ),
					'city'       => sanitize_text_field( (string) $order->get_shipping_city() ),
					'state'      => sanitize_text_field( (string) $order->get_shipping_state() ),
					'postcode'   => sanitize_text_field( (string) $order->get_shipping_postcode() ),
					'country'    => sanitize_text_field( (string) $order->get_shipping_country() ),
				),
				'lines'           => $lines,
				'shipping_methods'=> $shipping_methods,
				'refunds'         => $refunds,
				'fulfilment'      => $order->get_meta( self::ORDER_FULFILMENT_RECEIPT, true ),
			)
		);
		if ( is_object( $response ) && method_exists( $response, 'header' ) ) {
			$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		}
		return $response;
	}

	public static function read_outbox() {
		return rest_ensure_response(
			array(
				'schema'          => self::OUTBOX_SCHEMA,
				'events'          => self::outbox(),
				'failed_events'   => self::outbox_failures(),
				'errors'          => self::outbox_errors(),
				'recovery_status' => empty( self::outbox_failures() ) && empty( self::outbox_errors() )
					? 'clear'
					: 'recovery_required',
				'acknowledged_audit_count' => count( self::outbox_ack_audit() ),
			)
		);
	}

	public static function replay_outbox_failures( WP_REST_Request $request ) {
		self::mark_public_commerce_state_dirty();
		$ids = $request->get_param( 'failure_ids' );
		if ( ! is_array( $ids ) || empty( $ids ) || 100 < count( $ids ) ) {
			return new WP_Error( 'complete99_outbox_replay', 'A bounded failure_ids array is required.', array( 'status' => 400 ) );
		}
		$ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $value ) {
							$value = strtolower( sanitize_text_field( (string) $value ) );
							return preg_match( '/^[a-f0-9]{64}$/', $value ) ? $value : '';
						},
						$ids
					)
				)
			)
		);
		if ( empty( $ids ) ) {
			return new WP_Error( 'complete99_outbox_replay', 'No valid failure identifier was supplied.', array( 'status' => 400 ) );
		}
		if ( ! self::outbox_durable_state_is_valid() ) {
			return new WP_Error( 'complete99_outbox_corrupt', 'The outbox recovery journal is corrupt and requires operator repair.', array( 'status' => 503 ) );
		}
		$failures = self::outbox_failures();
		$selected = array();
		foreach ( $failures as $failure ) {
			$event_id = (string) $failure['event']['id'];
			if ( in_array( $event_id, $ids, true ) ) {
				$selected[ $event_id ] = $failure['event'];
			}
		}
		if ( count( $selected ) !== count( $ids ) ) {
			return new WP_Error( 'complete99_outbox_unknown_failure', 'Every failure identifier must match a pending failed event.', array( 'status' => 409 ) );
		}
		if ( ! self::acquire_outbox_lock() ) {
			self::record_outbox_error( 'lock' );
			return new WP_Error( 'complete99_outbox_lock', 'The outbox is busy. No failed event was replayed.', array( 'status' => 503 ) );
		}
		try {
			self::clear_outbox_error( 'lock' );
			wp_cache_delete( self::OPTION_OUTBOX, 'options' );
			if ( ! self::outbox_durable_state_is_valid() ) {
				return new WP_Error( 'complete99_outbox_corrupt', 'The outbox is corrupt and no failed event was replayed.', array( 'status' => 503 ) );
			}
			$outbox   = self::outbox();
			$existing = array_values( array_column( $outbox, 'id' ) );
			foreach ( $selected as $event_id => $event ) {
				if ( in_array( $event_id, $existing, true ) ) {
					continue;
				}
				if ( count( $outbox ) >= self::MAX_OUTBOX_EVENTS ) {
					self::record_outbox_error( 'capacity' );
					return new WP_Error( 'complete99_outbox_capacity', 'The outbox has no capacity for replay.', array( 'status' => 503 ) );
				}
				$outbox[]   = $event;
				$existing[] = $event_id;
			}
			self::clear_outbox_error( 'capacity' );
			update_option( self::OPTION_OUTBOX, $outbox, false );
			wp_cache_delete( self::OPTION_OUTBOX, 'options' );
			$stored_ids = array_values( array_column( self::outbox(), 'id' ) );
			foreach ( $ids as $event_id ) {
				if ( ! in_array( $event_id, $stored_ids, true ) ) {
					self::record_outbox_error( 'readback' );
					return new WP_Error( 'complete99_outbox_readback', 'A replayed event could not be verified.', array( 'status' => 500 ) );
				}
			}
			self::clear_outbox_error( 'readback' );
			$uncleared = array();
			foreach ( $ids as $event_id ) {
				if ( ! self::clear_outbox_failure( $event_id ) ) {
					$uncleared[] = $event_id;
				}
			}
			if ( ! empty( $uncleared ) ) {
				self::record_outbox_error( 'failure_readback' );
				return new WP_Error(
					'complete99_outbox_failure_readback',
					'Replayed events were preserved, but one or more failure rows could not be cleared.',
					array(
						'status'        => 503,
						'event_ids'     => $uncleared,
						'retry_is_safe' => true,
					)
				);
			}
			return rest_ensure_response(
				array(
					'replayed'           => count( $ids ),
					'pending_events'     => count( self::outbox() ),
					'remaining_failures' => count( self::outbox_failures() ),
				)
			);
		} finally {
			self::release_outbox_lock();
		}
	}

	private static function protected_acceptance_event_ids() {
		$receipt = get_option( self::OPTION_ACCEPTANCE, array() );
		if ( ! is_array( $receipt ) ) {
			return array();
		}
		$protected = array();
		array_walk_recursive(
			$receipt,
			static function ( $value ) use ( &$protected ) {
				$value = strtolower( (string) $value );
				if ( preg_match( '/^[a-f0-9]{64}$/', $value ) ) {
					$protected[ $value ] = true;
				}
			}
		);
		return $protected;
	}

	public static function acknowledge_outbox( WP_REST_Request $request ) {
		self::mark_public_commerce_state_dirty();
		$ids = $request->get_param( 'event_ids' );
		if ( ! is_array( $ids ) || empty( $ids ) || 200 < count( $ids ) ) {
			return new WP_Error( 'complete99_outbox_ack', 'A bounded event_ids array is required.', array( 'status' => 400 ) );
		}
		$ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $value ) {
							$value = strtolower( sanitize_text_field( (string) $value ) );
							return preg_match( '/^[a-f0-9]{64}$/', $value ) ? $value : '';
						},
						$ids
					)
				)
			)
		);
		if ( empty( $ids ) ) {
			return new WP_Error( 'complete99_outbox_ack', 'No valid event identifier was supplied.', array( 'status' => 400 ) );
		}
		if ( ! self::acquire_outbox_lock() ) {
			self::record_outbox_error( 'lock' );
			return new WP_Error( 'complete99_outbox_lock', 'The outbox is busy. No event was acknowledged.', array( 'status' => 503 ) );
		}
		try {
			self::clear_outbox_error( 'lock' );
			wp_cache_delete( self::OPTION_OUTBOX, 'options' );
			if ( ! self::outbox_durable_state_is_valid() ) {
				return new WP_Error( 'complete99_outbox_corrupt', 'The outbox is corrupt and no event was acknowledged.', array( 'status' => 503 ) );
			}
			$before = self::outbox();
			$known_ids = array_values( array_column( $before, 'id' ) );
			$unknown   = array_values( array_diff( $ids, $known_ids ) );
			if ( ! empty( $unknown ) ) {
				return new WP_Error(
					'complete99_outbox_unknown_event',
					'Every event identifier must match a currently pending event.',
					array( 'status' => 409 )
				);
			}
			$audit = array_values(
				array_filter(
					self::outbox_ack_audit(),
					static function ( $entry ) {
						$acknowledged_at = strtotime( (string) $entry['acknowledged_at'] );
						return false !== $acknowledged_at && $acknowledged_at >= time() - self::ACCEPTANCE_MAX_AGE;
					}
				)
			);
			if ( self::$outbox_corruption_detected ) {
				return new WP_Error( 'complete99_outbox_audit_corrupt', 'The acknowledgement audit is corrupt and no event was acknowledged.', array( 'status' => 503 ) );
			}
			$audit = array_values(
				array_filter(
					$audit,
					static function ( $entry ) use ( $ids ) {
						return ! in_array( (string) $entry['id'], $ids, true );
					}
				)
			);
			foreach ( $before as $event ) {
				if ( ! in_array( (string) $event['id'], $ids, true ) ) {
					continue;
				}
				$acknowledged_at = gmdate( 'c' );
				$audit[] = array(
					'schema'          => self::OUTBOX_ACK_SCHEMA,
					'id'              => (string) $event['id'],
					'id_version'      => self::OUTBOX_ID_VERSION,
					'type'            => (string) $event['type'],
					'entity'          => (string) $event['entity'],
					'entity_id'       => (string) $event['entity_id'],
					'event_version'   => (string) $event['event_version'],
					'occurred_at'      => (string) $event['occurred_at'],
					'acknowledged_at'  => $acknowledged_at,
					'ack_digest'       => self::digest_value(
						array(
							'schema'          => self::OUTBOX_ACK_SCHEMA,
							'event_id'        => (string) $event['id'],
							'acknowledged_at' => $acknowledged_at,
						)
					),
					'payload_digest'   => (string) $event['payload_digest'],
					'payload'          => $event['payload'],
				);
			}
			if ( count( $audit ) > self::MAX_OUTBOX_AUDIT ) {
				$protected_ids = self::protected_acceptance_event_ids();
				usort(
					$audit,
					static function ( $left, $right ) {
						return strcmp( (string) $left['acknowledged_at'], (string) $right['acknowledged_at'] );
					}
				);
				foreach ( $audit as $index => $entry ) {
					if ( count( $audit ) <= self::MAX_OUTBOX_AUDIT ) {
						break;
					}
					if ( ! isset( $protected_ids[ (string) $entry['id'] ] ) ) {
						unset( $audit[ $index ] );
					}
				}
				$audit = array_values( $audit );
				if ( count( $audit ) > self::MAX_OUTBOX_AUDIT ) {
					self::record_outbox_error( 'audit_capacity' );
					return new WP_Error( 'complete99_outbox_audit_capacity', 'The acknowledgement audit cannot compact without removing active acceptance evidence.', array( 'status' => 503 ) );
				}
			}
			self::clear_outbox_error( 'audit_capacity' );
			update_option( self::OPTION_OUTBOX_AUDIT, $audit, false );
			wp_cache_delete( self::OPTION_OUTBOX_AUDIT, 'options' );
			if ( hash( 'sha256', serialize( $audit ) ) !== hash( 'sha256', serialize( self::outbox_ack_audit() ) ) ) {
				self::record_outbox_error( 'audit_readback' );
				return new WP_Error( 'complete99_outbox_audit_readback', 'The acknowledgement audit could not be verified.', array( 'status' => 500 ) );
			}
			self::clear_outbox_error( 'audit_readback' );
			$remaining = array_values(
				array_filter(
					$before,
					static function ( $event ) use ( $ids ) {
						return ! in_array( (string) $event['id'], $ids, true );
					}
				)
			);
			update_option( self::OPTION_OUTBOX, $remaining, false );
			wp_cache_delete( self::OPTION_OUTBOX, 'options' );
			$stored = self::outbox();
			if ( hash( 'sha256', serialize( $remaining ) ) !== hash( 'sha256', serialize( $stored ) ) ) {
				self::record_outbox_error( 'readback' );
				return new WP_Error( 'complete99_outbox_readback', 'The outbox acknowledgement could not be verified.', array( 'status' => 500 ) );
			}
			self::clear_outbox_error( 'readback' );
			if ( count( $stored ) < self::MAX_OUTBOX_EVENTS ) {
				self::clear_outbox_error( 'capacity' );
			}
			return rest_ensure_response(
				array(
					'acknowledged' => count( $before ) - count( $stored ),
					'remaining'    => count( $stored ),
				)
			);
		} finally {
			self::release_outbox_lock();
		}
	}
}
