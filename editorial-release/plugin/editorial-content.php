<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/ingredient-nutrition.php';

/** Only the existing ingredient/guide pages; original CMS copy and links are retained. */
function c99_editorial_page_key() {
	if ( is_admin() || is_404() || ! is_singular() || ! class_exists( 'Complete99_Content' ) || ! class_exists( 'Complete99_Consumer' ) ) { return ''; }
	if ( ! defined( 'COMPLETE99_PLATFORM_VERSION' ) || version_compare( COMPLETE99_PLATFORM_VERSION, '1.22.1', '<' ) || version_compare( COMPLETE99_PLATFORM_VERSION, '1.24.0', '>=' ) ) { return ''; }
	$key = Complete99_Content::translation_group_for_post( get_queried_object_id() );
	$lang = Complete99_Content::language_for_post( get_queried_object_id() );
	return in_array( $key, array( 'ingredients', 'knowledge' ), true ) && in_array( $lang, array( 'he', 'en' ), true ) ? $key : '';
}

function c99_editorial_enrich_html( $html, $key, $lang ) {
	if ( ! in_array( $key, array( 'ingredients', 'knowledge' ), true ) || ! in_array( $lang, array( 'he', 'en' ), true ) ) { return $html; }
	if ( 'ingredients' === $key ) { $html = c99_editorial_ingredient_nutrition( $html, $lang ); }
	$he = 'he' === $lang;
	$stem = 'ingredients' === $key ? 'ingredient-still-life-v01' : 'aubergine-pan-v01';
	$old = 'ingredients' === $key ? 'c99-food-shakshuka-plate-gallery-2021-wp-v01' : 'c99-food-kubeh-beet-soup-gallery-2021-wp-v01';
	$alt = 'ingredients' === $key ? ( $he ? 'חצילים, עגבניות, חומוס וטחינה על שולחן' : 'Aubergines, tomatoes, chickpeas and tahini on a table' ) : ( $he ? 'פרוסות חציל משחימות במחבת' : 'Aubergine slices browning in a pan' );
	$picture = '<picture data-c99-editorial-picture="' . esc_attr( $key ) . '"><img src="' . esc_url( C99_EDITORIAL_URL . 'assets/' . $stem . '-1200.webp' ) . '" srcset="' . esc_url( C99_EDITORIAL_URL . 'assets/' . $stem . '-640.webp' ) . ' 640w, ' . esc_url( C99_EDITORIAL_URL . 'assets/' . $stem . '-1200.webp' ) . ' 1200w" sizes="(max-width: 900px) 100vw, 50vw" width="1200" height="800" alt="' . esc_attr( $alt ) . '" decoding="async" fetchpriority="high"></picture>';
	// Match one known hero picture only; never replace menu/product photography.
	$pattern = '~<picture>\s*<source\b[^>]*' . preg_quote( $old, '~' ) . '\.avif[^>]*>\s*<img\b[^>]*' . preg_quote( $old, '~' ) . '\.webp[^>]*>\s*</picture>~s';
	$html = preg_replace( $pattern, $picture, $html, 1 );
	$title = 'ingredients' === $key ? ( $he ? 'מה כדאי לדעת על תזונה' : 'Nutrition, in context' ) : ( $he ? 'גם דרך הבישול משנה' : 'Cooking matters too' );
	$text = 'ingredients' === $key
		? ( $he ? 'תזונה מגוונת מתחילה בשילוב של ירקות, קטניות ודגנים מלאים. כשמשווים ערכים תזונתיים, חשוב לבדוק אם הנתון מתייחס למזון נא או מבושל, ולכמה גרמים.' : 'A varied diet includes vegetables, pulses and whole grains. When comparing nutrient values, check whether the food is raw or cooked and the weight the figures refer to.' )
		: ( $he ? 'אידוי, הקפצה ובישול במים נותנים מרקמים שונים. גם כמות השמן והמלח וגודל המנה חשובים כשבוחרים איך להכין את הארוחה.' : 'Steaming, stir-frying and boiling create different textures. The amount of oil and salt, and the serving size, also matter when planning a meal.' );
	$module = '<section class="c99-nutrition-context" aria-labelledby="c99-nutrition-title"><h2 id="c99-nutrition-title">' . esc_html( $title ) . '</h2><p>' . esc_html( $text ) . '</p><p class="c99-nutrition-sources"><a href="https://www.gov.il/he/pages/dietary-guidelines">' . esc_html( $he ? 'המלצות משרד הבריאות' : 'Israeli Ministry of Health guidance' ) . '</a> · <a href="https://fdc.nal.usda.gov/Foundation_Foods_Documentation/">USDA FoodData Central</a></p></section>';
	$marker = '<article class="c99-article">';
	$index = '<section class="c99-ingredient-index" aria-labelledby="c99-ingredient-index-title">';
	if ( false === strpos( $html, 'id="c99-nutrition-title"' ) ) {
		if ( 'ingredients' === $key && 1 === substr_count( $html, $index ) ) {
			$html = str_replace( $index, '<div class="c99-container">' . $module . '</div>' . $index, $html );
		} elseif ( 1 === substr_count( $html, $marker ) ) { $html = str_replace( $marker, $marker . $module, $html ); }
	}
	return $html;
}

function c99_editorial_render_content( $post ) {
	ob_start();
	Complete99_Consumer::render_current( $post );
	$html = ob_get_clean();
	echo c99_editorial_enrich_html( $html, c99_editorial_page_key(), Complete99_Content::language_for_post( $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
