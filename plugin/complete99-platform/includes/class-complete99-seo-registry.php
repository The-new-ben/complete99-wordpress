<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Complete99_SEO_Registry {
	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	public static function admin_menu() {
		add_management_page(
			__( 'Complete99 keyword ownership', 'complete99-platform' ),
			__( 'Complete99 SEO ownership', 'complete99-platform' ),
			'manage_options',
			'complete99-seo-ownership',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function records() {
		$science = class_exists( 'Complete99_Culinary_Science', false )
			&& is_callable( array( 'Complete99_Culinary_Science', 'seo_owner_records' ) )
			? Complete99_Culinary_Science::seo_owner_records()
			: array();
		return array_merge( self::checked_in_records(), self::live_dish_records(), $science );
	}

	private static function checked_in_records() {
		$path = COMPLETE99_PLATFORM_DIR . 'data/keyword-ownership.csv';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$handle = fopen( $path, 'rb' );
		if ( ! $handle ) {
			return array();
		}
		$header  = fgetcsv( $handle );
		$records = array();
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) !== count( $header ) ) {
				continue;
			}
			$records[] = array_combine( $header, $row );
		}
		fclose( $handle );
		return $records;
	}

	/**
	 * Build exact ownership rows for the currently indexable read-model entities.
	 *
	 * These records deliberately use the persisted entity ID and canonical slug;
	 * no wildcard path is inserted into the checked-in registry.
	 */
	public static function live_dish_records() {
		if ( ! class_exists( 'Complete99_REST' ) || ! method_exists( 'Complete99_REST', 'public_indexable_items' ) ) {
			return array();
		}

		$records = array();
		foreach ( Complete99_REST::public_indexable_items() as $item ) {
			$id   = trim( isset( $item['id'] ) ? (string) $item['id'] : '' );
			$slug = sanitize_title( isset( $item['slug'] ) ? (string) $item['slug'] : '' );
			if ( '' === $id || '' === $slug ) {
				continue;
			}
			$translation_key = 'live-dish-' . substr( hash( 'sha256', $id ), 0, 16 );
			foreach ( array( 'he', 'en' ) as $language ) {
				$is_he       = 'he' === $language;
				$name_key    = $is_he ? 'name_he' : 'name_en';
				$category_key = $is_he ? 'category_he' : 'category_en';
				$tag_key     = $is_he ? 'tag_he' : 'tag_en';
				$name        = trim( isset( $item[ $name_key ] ) ? (string) $item[ $name_key ] : '' );
				$secondary   = array_filter(
					array(
						isset( $item[ $category_key ] ) ? trim( (string) $item[ $category_key ] ) : '',
						isset( $item[ $tag_key ] ) ? trim( (string) $item[ $tag_key ] ) : '',
						$name . ( $is_he ? ' קומפלט 99' : ' Complete99' ),
					)
				);
				$records[] = array(
					'language'                   => $language,
					'translation_key'            => $translation_key,
					'primary_intent'             => $name,
					'canonical_path'             => ( $is_he ? '/menu/' : '/en/menu/' ) . $slug . '/',
					'secondary_queries'          => implode( '; ', array_values( array_unique( $secondary ) ) ),
					'prohibited_competing_pages' => $is_he
						? 'דפי מנות או תוכן מערכתי המתחרים על אותה זהות מנה'
						: 'Dish or platform pages competing for the same dish identity',
					'evidence_gate'               => $is_he
						? 'מודל פרסום חתום ורענן; דו־לשוני; verified או launch_ready'
						: 'Fresh signed publication model; bilingual; verified or launch_ready',
					'publication_status'          => 'proof-gated',
				);
			}
		}

		return $records;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$records    = self::records();
		$errors     = self::validation_errors( $records );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Complete99 keyword ownership', 'complete99-platform' ); ?></h1>
			<p><?php echo esc_html__( 'This registry is the launch contract: one canonical owner per primary intent. “Prohibited competing pages” must not target the same primary query.', 'complete99-platform' ); ?></p>
			<?php if ( $errors ) : ?>
				<div class="notice notice-error"><p><strong><?php echo esc_html__( 'The SEO ownership contract has errors:', 'complete99-platform' ); ?></strong></p><ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
				</ul></div>
			<?php else : ?>
				<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( '%d ownership rows loaded; intents, canonical paths and bilingual translation groups are unique and complete.', count( $records ) ) ); ?></p></div>
			<?php endif; ?>
			<p><?php echo esc_html__( 'The checked-in registry is extended at runtime with exact fresh live-dish entities; wildcard canonicals are not accepted.', 'complete99-platform' ); ?></p>
			<p><code><?php echo esc_html( COMPLETE99_PLATFORM_DIR . 'data/keyword-ownership.csv' ); ?></code></p>
			<div style="overflow:auto">
				<table class="widefat striped">
					<thead><tr>
						<th><?php echo esc_html__( 'Language', 'complete99-platform' ); ?></th>
						<th><?php echo esc_html__( 'Primary intent', 'complete99-platform' ); ?></th>
						<th><?php echo esc_html__( 'Canonical owner', 'complete99-platform' ); ?></th>
						<th><?php echo esc_html__( 'Secondary queries', 'complete99-platform' ); ?></th>
						<th><?php echo esc_html__( 'Prohibited competitors', 'complete99-platform' ); ?></th>
						<th><?php echo esc_html__( 'Evidence gate', 'complete99-platform' ); ?></th>
						<th><?php echo esc_html__( 'Status', 'complete99-platform' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $records as $record ) : ?>
						<tr>
							<td><code><?php echo esc_html( strtoupper( $record['language'] ) ); ?></code></td>
							<td><strong><?php echo esc_html( $record['primary_intent'] ); ?></strong></td>
							<td><a href="<?php echo esc_url( home_url( $record['canonical_path'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $record['canonical_path'] ); ?></a><br><small><?php echo esc_html( $record['translation_key'] ); ?></small></td>
							<td><?php echo esc_html( $record['secondary_queries'] ); ?></td>
							<td><?php echo esc_html( $record['prohibited_competing_pages'] ); ?></td>
							<td><?php echo esc_html( $record['evidence_gate'] ); ?></td>
							<td><?php echo esc_html( $record['publication_status'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public static function validation_errors( $records ) {
		$required_fields = array( 'language', 'translation_key', 'primary_intent', 'canonical_path', 'secondary_queries', 'prohibited_competing_pages', 'evidence_gate', 'publication_status' );
		$seen_intents    = array();
		$seen_paths      = array();
		$seen_locales    = array();
		$groups          = array();
		$errors          = array();

		foreach ( $records as $index => $record ) {
			$row_number = $index + 2;
			foreach ( $required_fields as $field ) {
				if ( ! isset( $record[ $field ] ) || '' === trim( (string) $record[ $field ] ) ) {
					$errors[] = sprintf( 'Row %d is missing %s.', $row_number, $field );
				}
			}
			$language = strtolower( trim( isset( $record['language'] ) ? (string) $record['language'] : '' ) );
			$group    = sanitize_key( isset( $record['translation_key'] ) ? (string) $record['translation_key'] : '' );
			$intent   = strtolower( trim( isset( $record['primary_intent'] ) ? (string) $record['primary_intent'] : '' ) );
			$path     = self::normalise_canonical_path( isset( $record['canonical_path'] ) ? (string) $record['canonical_path'] : '' );

			if ( ! in_array( $language, array( 'he', 'en' ), true ) ) {
				$errors[] = sprintf( 'Row %d has unsupported language %s.', $row_number, $language );
			}
			if ( '' === $group ) {
				$errors[] = sprintf( 'Row %d has an invalid translation key.', $row_number );
			}
			if ( '' === $path ) {
				$errors[] = sprintf( 'Row %d has an invalid canonical path.', $row_number );
			} elseif ( 'en' === $language && 0 !== strpos( $path, '/en/' ) ) {
				$errors[] = sprintf( 'Row %d English canonical path must live below /en/.', $row_number );
			} elseif ( 'he' === $language && 0 === strpos( $path, '/en/' ) ) {
				$errors[] = sprintf( 'Row %d Hebrew canonical path cannot live below /en/.', $row_number );
			}

			$intent_key = $language . ':' . $intent;
			if ( isset( $seen_intents[ $intent_key ] ) ) {
				$errors[] = sprintf( 'Rows %d and %d duplicate the same primary intent and locale.', $seen_intents[ $intent_key ], $row_number );
			}
			$seen_intents[ $intent_key ] = $row_number;

			if ( '' !== $path && isset( $seen_paths[ $path ] ) ) {
				$errors[] = sprintf( 'Rows %d and %d duplicate canonical path %s.', $seen_paths[ $path ], $row_number, $path );
			}
			if ( '' !== $path ) {
				$seen_paths[ $path ] = $row_number;
			}

			$locale_key = $group . ':' . $language;
			if ( isset( $seen_locales[ $locale_key ] ) ) {
				$errors[] = sprintf( 'Rows %d and %d duplicate translation group %s for %s.', $seen_locales[ $locale_key ], $row_number, $group, $language );
			}
			$seen_locales[ $locale_key ] = $row_number;
			if ( '' !== $group && in_array( $language, array( 'he', 'en' ), true ) ) {
				$groups[ $group ][ $language ] = isset( $groups[ $group ][ $language ] ) ? $groups[ $group ][ $language ] + 1 : 1;
			}
		}

		foreach ( $groups as $group => $locales ) {
			foreach ( array( 'he', 'en' ) as $language ) {
				if ( 1 !== ( isset( $locales[ $language ] ) ? (int) $locales[ $language ] : 0 ) ) {
					$errors[] = sprintf( 'Translation group %s must have exactly one %s owner.', $group, $language );
				}
			}
		}

		return array_values( array_unique( $errors ) );
	}

	private static function normalise_canonical_path( $path ) {
		$path = trim( (string) $path );
		if ( '' === $path || '/' !== substr( $path, 0, 1 ) || false !== strpos( $path, '?' ) || false !== strpos( $path, '#' ) ) {
			return '';
		}
		$path = '/' . ltrim( preg_replace( '#/+#', '/', strtolower( $path ) ), '/' );
		return '/' === $path ? '/' : trailingslashit( untrailingslashit( $path ) );
	}

	private static function duplicate_intents( $records ) {
		$seen       = array();
		$duplicates = array();
		foreach ( $records as $record ) {
			$key = strtolower( trim( $record['language'] . ':' . $record['primary_intent'] ) );
			if ( isset( $seen[ $key ] ) ) {
				$duplicates[] = $record['primary_intent'];
			}
			$seen[ $key ] = true;
		}
		return array_values( array_unique( $duplicates ) );
	}
}
