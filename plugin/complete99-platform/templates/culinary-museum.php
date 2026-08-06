<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$complete99_museum_bundle = Complete99_Culinary_Museum_Frontend::current_bundle();
if ( empty( $complete99_museum_bundle ) ) {
	return;
}

$complete99_museum_lang       = $complete99_museum_bundle['language'];
$complete99_museum_dir        = 'he' === $complete99_museum_lang ? 'rtl' : 'ltr';
$complete99_museum_other_lang = 'he' === $complete99_museum_lang ? 'en' : 'he';
$complete99_museum_post_id    = class_exists( 'Complete99_Content', false )
	? Complete99_Content::find_translation_post_id( 'knowledge', $complete99_museum_lang, true )
	: 0;
$complete99_museum_deployment = (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID );
?>
<!doctype html>
<html lang="<?php echo esc_attr( $complete99_museum_lang ); ?>" dir="<?php echo esc_attr( $complete99_museum_dir ); ?>">
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
	data-c99-deployment="<?php echo esc_attr( $complete99_museum_deployment ); ?>"
></span>
<?php
Complete99_Consumer::render_header(
	$complete99_museum_post_id,
	$complete99_museum_lang,
	'',
	$complete99_museum_bundle['alternates'][ $complete99_museum_other_lang ],
	'museum'
);
?>
<main id="c99-main" tabindex="-1">
	<?php Complete99_Culinary_Museum_Frontend::render_page( $complete99_museum_bundle ); ?>
</main>
<?php Complete99_Consumer::render_footer( $complete99_museum_lang ); ?>
<?php wp_footer(); ?>
</body>
</html>
