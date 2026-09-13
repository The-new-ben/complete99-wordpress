<?php
/**
 * Plugin Name: Complete99 Editorial Home
 * Description: Shared public food-site presentation and bilingual editorial homepage. No content migrations.
 * Version: 1.2.2
 * Requires PHP: 8.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'C99_EDITORIAL_VERSION', '1.2.2' );
define( 'C99_EDITORIAL_URL', plugin_dir_url( __FILE__ ) );
require_once __DIR__ . '/editorial-content.php';

function c99_editorial_home_request() {
	return ! is_admin() && ! is_404() && is_singular()
		&& defined( 'COMPLETE99_PLATFORM_VERSION' )
		&& version_compare( COMPLETE99_PLATFORM_VERSION, '1.22.1', '>=' )
		&& version_compare( COMPLETE99_PLATFORM_VERSION, '1.24.0', '<' )
		&& class_exists( 'Complete99_Content' ) && class_exists( 'Complete99_Consumer' )
		&& 'home' === Complete99_Content::translation_group_for_post( get_queried_object_id() )
		&& in_array( Complete99_Content::language_for_post( get_queried_object_id() ), array( 'he', 'en' ), true );
}

add_filter( 'template_include', static function ( $template ) {
	if ( c99_editorial_page_key() ) { return __DIR__ . '/editorial-shell.php'; }
	if ( ! c99_editorial_home_request() ) { return $template; }
	require_once __DIR__ . '/includes/class-complete99-editorial-consumer.php';
	return __DIR__ . '/public-shell.php';
}, 100 );

add_action( 'wp_enqueue_scripts', static function () {
	if ( ! c99_editorial_home_request() ) { return; }
	wp_dequeue_style( 'complete99-consumer' );
	wp_deregister_style( 'complete99-consumer' );
	wp_enqueue_style( 'complete99-consumer', C99_EDITORIAL_URL . 'assets/consumer.css', array( 'complete99-public' ), C99_EDITORIAL_VERSION );
	wp_dequeue_script( 'complete99-public' );
	wp_deregister_script( 'complete99-public' );
	wp_enqueue_script( 'complete99-public', C99_EDITORIAL_URL . 'assets/public.js', array(), C99_EDITORIAL_VERSION, true );
}, 20 );

// The installed 1.22.1 menu script predates local filter state. Use its exact
// archived bytes with only the two local-state guards, not a newer core script.
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_admin() || is_404() || ! is_singular() || ! class_exists( 'Complete99_Content' ) || ! defined( 'COMPLETE99_PLATFORM_VERSION' ) || '1.22.1' !== COMPLETE99_PLATFORM_VERSION ) { return; }
	if ( 'dishes' !== Complete99_Content::translation_group_for_post( get_queried_object_id() ) || ! in_array( Complete99_Content::language_for_post( get_queried_object_id() ), array( 'he', 'en' ), true ) ) { return; }
	wp_dequeue_script( 'complete99-public' );
	wp_deregister_script( 'complete99-public' );
	wp_enqueue_script( 'complete99-public', C99_EDITORIAL_URL . 'assets/legacy-public.js', array(), C99_EDITORIAL_VERSION, true );
}, 25 );

add_action( 'wp_enqueue_scripts', static function () {
	if ( is_admin() || ! class_exists( 'Complete99_Consumer' ) ) { return; }
	wp_enqueue_style( 'complete99-site-editorial', C99_EDITORIAL_URL . 'assets/site-editorial.css', array( 'complete99-consumer' ), C99_EDITORIAL_VERSION );
	wp_add_inline_script( 'complete99-public', file_get_contents( __DIR__ . '/assets/discovery-local.js' ), 'before' );
}, 30 );
