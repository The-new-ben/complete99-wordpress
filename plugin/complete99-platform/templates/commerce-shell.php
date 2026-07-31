<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$complete99_lang       = Complete99_Commerce::transaction_language();
$complete99_dir        = 'he' === $complete99_lang ? 'rtl' : 'ltr';
$complete99_locale     = 'he' === $complete99_lang ? 'he_IL' : 'en_US';
$complete99_switched   = function_exists( 'switch_to_locale' ) ? switch_to_locale( $complete99_locale ) : false;
$complete99_deployment = (string) get_option( 'complete99_last_deployment_id', COMPLETE99_PLATFORM_DEPLOYMENT_ID );
$complete99_store_id   = Complete99_Content::find_translation_post_id( 'store', $complete99_lang, true );
?>
<!doctype html>
<html lang="<?php echo esc_attr( $complete99_lang ); ?>" dir="<?php echo esc_attr( $complete99_dir ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<script>document.documentElement.classList.add('c99-js');</script>
	<?php Complete99_Frontend::render_document_title_tag(); ?>
	<?php wp_head(); ?>
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
<?php Complete99_Consumer::render_header( $complete99_store_id, $complete99_lang ); ?>
<main id="c99-main" tabindex="-1">
	<?php Complete99_Consumer::render_transaction_page( Complete99_Commerce::transaction_page_type(), $complete99_lang ); ?>
</main>
<?php Complete99_Consumer::render_footer( $complete99_lang ); ?>
<?php wp_footer(); ?>
</body>
</html>
<?php
if ( $complete99_switched && function_exists( 'restore_previous_locale' ) ) {
	restore_previous_locale();
}
