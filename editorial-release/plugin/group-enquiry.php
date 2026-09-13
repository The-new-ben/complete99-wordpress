<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The existing public proposal page, not a new privileged endpoint. */
function c99_editorial_enquiry_url() {
	if ( is_admin() || is_404() || ! is_singular() || ! class_exists( 'Complete99_Content' ) || ! class_exists( 'Complete99_Consumer' ) || ! is_callable( array( 'Complete99_Leads', 'handle' ) ) ) { return ''; }
	if ( ! defined( 'COMPLETE99_PLATFORM_VERSION' ) || version_compare( COMPLETE99_PLATFORM_VERSION, '1.22.1', '<' ) ) { return ''; }
	$id = get_queried_object_id();
	$lang = Complete99_Content::language_for_post( $id );
	if ( 'publish' !== get_post_status( $id ) || 'proposal' !== Complete99_Content::translation_group_for_post( $id ) || ! in_array( $lang, array( 'he', 'en' ), true ) ) { return ''; }
	$url = get_permalink( $id );
	$expected = home_url( 'en' === $lang ? '/en/request-proposal/' : '/request-proposal/' );
	return is_string( $url ) && $url === $expected ? $url : '';
}

/** Keep all native fields, nonce and consent; only change the known form action. */
function c99_editorial_enquiry_form( $html, $url ) {
	if ( ! $url ) { return $html; }
	$pattern = '~<form class="c99-lead-form" method="post" action="' . preg_quote( esc_url( admin_url( 'admin-post.php' ) ), '~' ) . '">.*?</form>~s';
	if ( 1 !== preg_match_all( $pattern, $html, $matches ) ) { return $html; }
	$form = $matches[0][0];
	foreach ( array( 'action' => 'complete99_submit_lead', 'interest' => 'group-order' ) as $name => $value ) {
		if ( ! preg_match( '~<input\b[^>]*name="' . $name . '"\s+value="' . $value . '"[^>]*>~', $form ) ) { return $html; }
	}
	$opening = '<form class="c99-lead-form" method="post" action="' . esc_url( $url ) . '" data-c99-public-enquiry="1">';
	$changed = preg_replace( '~^<form\b[^>]*>~', $opening, $form, 1 );
	return str_replace( $form, $changed, $html );
}

/** Native handler still owns validation, rate limits, storage and redirects. */
function c99_editorial_submit_enquiry() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! c99_editorial_enquiry_url() ) { return; }
	if ( 'complete99_submit_lead' !== ( $_POST['action'] ?? '' ) || 'group-order' !== ( $_POST['interest'] ?? '' ) ) { return; }
	Complete99_Leads::handle();
}
