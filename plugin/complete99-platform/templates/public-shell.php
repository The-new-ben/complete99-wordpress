<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$complete99_post = get_queried_object();
if ( ! $complete99_post instanceof WP_Post ) {
	return;
}
$complete99_lang = Complete99_Content::language_for_post( $complete99_post->ID );
$complete99_dir  = 'he' === $complete99_lang ? 'rtl' : 'ltr';
$complete99_deployment = (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID );
?>
<!doctype html>
<html lang="<?php echo esc_attr( $complete99_lang ); ?>" dir="<?php echo esc_attr( $complete99_dir ); ?>">
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
<?php Complete99_Consumer::render_header( $complete99_post->ID, $complete99_lang ); ?>
<main id="c99-main" tabindex="-1">
	<?php Complete99_Consumer::render_current( $complete99_post ); ?>
</main>
<?php Complete99_Consumer::render_footer( $complete99_lang ); ?>
<?php wp_footer(); ?>
</body>
</html>
