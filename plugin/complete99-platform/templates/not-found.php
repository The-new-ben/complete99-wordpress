<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$complete99_not_found_lang = Complete99_Frontend::not_found_language();
$complete99_not_found_dir  = 'he' === $complete99_not_found_lang ? 'rtl' : 'ltr';
$complete99_deployment     = (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID );
?>
<!doctype html>
<html lang="<?php echo esc_attr( $complete99_not_found_lang ); ?>" dir="<?php echo esc_attr( $complete99_not_found_dir ); ?>">
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
<?php Complete99_Frontend::render_not_found_page( $complete99_not_found_lang ); ?>
<?php wp_footer(); ?>
</body>
</html>
