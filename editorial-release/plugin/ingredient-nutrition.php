<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Enrich exact existing ingredient identities. No product values or certifications. */
function c99_editorial_ingredient_nutrition( $html, $lang ) {
	if ( ! in_array( $lang, array( 'he', 'en' ), true ) ) { return $html; }
	static $data = null;
	if ( null === $data ) { $data = json_decode( file_get_contents( __DIR__ . '/ingredient-nutrition.json' ), true ); }
	if ( ! is_array( $data ) || empty( $data['ingredients'] ) || empty( $data['sources'] ) ) { return $html; }
	return preg_replace_callback( '~<article\b(?=[^>]*\bclass="c99-ingredient-index-card")(?=[^>]*\bid="([a-z0-9-]+)")[^>]*>.*?</article>~s', static function ( $match ) use ( $lang, $data ) {
		$id = $match[1];
		$card = $match[0];
		$entry = $data['ingredients'][ $id ] ?? null;
		$source = $data['sources'][ $entry['source'] ?? '' ] ?? null;
		$marker = '<div class="c99-ingredient-index-links">';
		if ( ! $entry || ! $source || empty( $entry[ $lang ] ) || empty( $source[ $lang ] ) || empty( $source['url'] ) || 1 !== substr_count( $card, $marker ) || false !== strpos( $card, 'data-c99-ingredient-nutrition=' ) ) { return $card; }
		$label = 'he' === $lang ? 'מבט תזונתי' : 'Nutrition notes';
		$note = '<div class="c99-ingredient-nutrition" data-c99-ingredient-nutrition="' . esc_attr( $id ) . '"><h4>' . esc_html( $label ) . '</h4><p>' . esc_html( $entry[ $lang ] ) . '</p><a href="' . esc_url( $source['url'] ) . '">' . esc_html( $source[ $lang ] ) . '</a></div>';
		return str_replace( $marker, $note . $marker, $card );
	}, $html );
}
