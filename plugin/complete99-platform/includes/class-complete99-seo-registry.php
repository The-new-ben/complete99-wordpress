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

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$records    = self::records();
		$duplicates = self::duplicate_intents( $records );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Complete99 keyword ownership', 'complete99-platform' ); ?></h1>
			<p><?php echo esc_html__( 'This registry is the launch contract: one canonical owner per primary intent. “Prohibited competing pages” must not target the same primary query.', 'complete99-platform' ); ?></p>
			<?php if ( $duplicates ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( sprintf( 'Duplicate primary intents detected: %s', implode( ', ', $duplicates ) ) ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( '%d ownership rows loaded; no duplicate primary intents.', count( $records ) ) ); ?></p></div>
			<?php endif; ?>
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
