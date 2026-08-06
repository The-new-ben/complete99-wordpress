<?php
/**
 * Plugin Name: Complete99 Platform
 * Plugin URI:  https://complete99.co.il/
 * Description: Bilingual consumer food, culinary knowledge, commerce readiness and a secure bridge to Complete99 OS.
 * Version:     1.4.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author:      Complete99
 * Text Domain: complete99-platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COMPLETE99_PLATFORM_VERSION', '1.4.0' );
define( 'COMPLETE99_PLATFORM_DEPLOYMENT_ID', 'c99-wp-1.4.0' );
define( 'COMPLETE99_PLATFORM_FILE', __FILE__ );
define( 'COMPLETE99_PLATFORM_DIR', plugin_dir_path( __FILE__ ) );
define( 'COMPLETE99_PLATFORM_URL', plugin_dir_url( __FILE__ ) );
define(
	'COMPLETE99_PLATFORM_UPDATE_MANIFEST_URL',
	'https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/main/plugin-dist/complete99-platform.json'
);

require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-content.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-settings.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-leads.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-rest.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-order-connectors.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-commerce.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-catalog-graph.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-evaluation-catalog.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-live-catalog.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-inventory-bridge.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-culinary-science.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-culinary-commerce.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-review-lab.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-frontend.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-consumer.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-live-dish-sitemap-provider.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-culinary-museum-frontend.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-culinary-museum-sitemap-provider.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-seo-registry.php';
require_once COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-platform.php';

register_activation_hook( __FILE__, array( 'Complete99_Platform', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Complete99_Platform', 'deactivate' ) );

Complete99_Platform::boot();
