<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $complete99_live_dish, $complete99_live_lang ) || ! is_array( $complete99_live_dish ) ) {
	return;
}

$complete99_live_dir   = 'he' === $complete99_live_lang ? 'rtl' : 'ltr';
$complete99_deployment = (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID );
?>
<!doctype html>
<html lang="<?php echo esc_attr( $complete99_live_lang ); ?>" dir="<?php echo esc_attr( $complete99_live_dir ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<script>document.documentElement.classList.add('c99-js');</script>
	<?php Complete99_Frontend::render_document_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<span
	id="c99-release-marker"
	hidden
	aria-hidden="true"
	data-c99-version="<?php echo esc_attr( COMPLETE99_PLATFORM_VERSION ); ?>"
	data-c99-deployment="<?php echo esc_attr( $complete99_deployment ); ?>"
></span>
<?php Complete99_Frontend::render_live_dish_page( $complete99_live_dish, $complete99_live_lang ); ?>
<?php wp_footer(); ?>
</body>
</html>
