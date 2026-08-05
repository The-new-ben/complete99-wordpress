<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private, capability-gated inspection surface for the Complete99 catalog.
 *
 * This page deliberately separates evaluation data from the public consumer
 * experience. It is read-only and never publishes a product, price, stock
 * value, nutrition claim or generated image.
 */
final class Complete99_Review_Lab {
	const PAGE_SLUG = 'complete99-review-lab';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	public static function admin_menu() {
		add_management_page(
			__( 'Complete99 Review', 'complete99-platform' ),
			__( 'Complete99 Review', 'complete99-platform' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Return a private, bounded inspection snapshot.
	 *
	 * @return array
	 */
	public static function snapshot() {
		$dish_bundle = self::load_data_file( 'dish-entity-trees.php' );
		$guide_bundle = self::load_data_file( 'nutrition-guide-seeds.php' );
		$product_bundle = self::load_data_file( 'catalog-product-seeds.php' );
		$asset_bundle = self::load_data_file( 'generated-asset-manifest.php' );
		$connectors = self::load_data_file( 'order-connectors.php' );
		$science_snapshot = class_exists( 'Complete99_Culinary_Science', false )
			? Complete99_Culinary_Science::editorial_snapshot()
			: array();
		$science_registry = isset( $science_snapshot['registry'] ) && is_array( $science_snapshot['registry'] )
			? $science_snapshot['registry']
			: array();
		$culinary_commerce_snapshot = class_exists( 'Complete99_Culinary_Commerce', false )
			? Complete99_Culinary_Commerce::editorial_snapshot()
			: array();
		$culinary_commerce_registry = isset( $culinary_commerce_snapshot['registry'] ) && is_array( $culinary_commerce_snapshot['registry'] )
			? $culinary_commerce_snapshot['registry']
			: array();

		$dishes = isset( $dish_bundle['dishes'] ) && is_array( $dish_bundle['dishes'] )
			? array_slice( $dish_bundle['dishes'], 0, 100 )
			: array();
		$guides = isset( $guide_bundle['guides'] ) && is_array( $guide_bundle['guides'] )
			? array_slice( $guide_bundle['guides'], 0, 200 )
			: array();
		$products = isset( $product_bundle['products'] ) && is_array( $product_bundle['products'] )
			? array_slice( $product_bundle['products'], 0, 500 )
			: array();
		$assets = isset( $asset_bundle['assets'] ) && is_array( $asset_bundle['assets'] )
			? array_slice( $asset_bundle['assets'], 0, 500 )
			: array();

		$ingredient_codes = array();
		foreach ( $dishes as $dish ) {
			$codes = isset( $dish['relations']['ingredient_codes'] ) && is_array( $dish['relations']['ingredient_codes'] )
				? $dish['relations']['ingredient_codes']
				: array();
			foreach ( $codes as $code ) {
				$code = sanitize_key( (string) $code );
				if ( 0 === strpos( $code, 'ingredient-' ) ) {
					$ingredient_codes[ $code ] = true;
				}
			}
		}

		return array(
			'schema'           => 'complete99-review-lab/v1',
			'dishes'           => $dishes,
			'ingredient_codes' => array_keys( $ingredient_codes ),
			'guides'           => $guides,
			'products'         => $products,
			'assets'           => $assets,
			'connectors'       => is_array( $connectors ) ? array_slice( $connectors, 0, 20, true ) : array(),
			'commerce'         => self::commerce_readiness(),
			'evaluation_catalog' => self::evaluation_catalog_status(),
			'culinary_science' => array(
				'digest'   => isset( $science_snapshot['digest'] ) ? (string) $science_snapshot['digest'] : '',
				'version'  => isset( $science_registry['version'] ) ? (string) $science_registry['version'] : '',
				'sources'  => isset( $science_registry['sources'] ) && is_array( $science_registry['sources'] ) ? $science_registry['sources'] : array(),
				'entities' => isset( $science_registry['entities'] ) && is_array( $science_registry['entities'] ) ? array_slice( $science_registry['entities'], 0, 500 ) : array(),
			),
			'culinary_commerce_graph' => array(
				'digest'              => isset( $culinary_commerce_snapshot['digest'] ) ? (string) $culinary_commerce_snapshot['digest'] : '',
				'version'             => isset( $culinary_commerce_registry['version'] ) ? (string) $culinary_commerce_registry['version'] : '',
				'products'            => isset( $culinary_commerce_registry['products'] ) && is_array( $culinary_commerce_registry['products'] ) ? array_slice( $culinary_commerce_registry['products'], 0, 500 ) : array(),
				'variants'            => isset( $culinary_commerce_registry['variants'] ) && is_array( $culinary_commerce_registry['variants'] ) ? array_slice( $culinary_commerce_registry['variants'], 0, 500 ) : array(),
				'skus'                => isset( $culinary_commerce_registry['skus'] ) && is_array( $culinary_commerce_registry['skus'] ) ? array_slice( $culinary_commerce_registry['skus'], 0, 500 ) : array(),
				'observations'        => isset( $culinary_commerce_registry['market_observations'] ) && is_array( $culinary_commerce_registry['market_observations'] ) ? array_slice( $culinary_commerce_registry['market_observations'], 0, 500 ) : array(),
				'channel_offers'      => isset( $culinary_commerce_registry['channel_offers'] ) && is_array( $culinary_commerce_registry['channel_offers'] ) ? array_slice( $culinary_commerce_registry['channel_offers'], 0, 500 ) : array(),
				'bundles'             => isset( $culinary_commerce_registry['bundles'] ) && is_array( $culinary_commerce_registry['bundles'] ) ? array_slice( $culinary_commerce_registry['bundles'], 0, 200 ) : array(),
				'connector_profiles'  => isset( $culinary_commerce_registry['connector_profiles'] ) && is_array( $culinary_commerce_registry['connector_profiles'] ) ? array_slice( $culinary_commerce_registry['connector_profiles'], 0, 50 ) : array(),
				'integration_consumers' => isset( $culinary_commerce_registry['integration_consumers'] ) && is_array( $culinary_commerce_registry['integration_consumers'] ) ? array_slice( $culinary_commerce_registry['integration_consumers'], 0, 100 ) : array(),
			),
		);
	}

	private static function load_data_file( $filename ) {
		$filename = sanitize_file_name( (string) $filename );
		if ( '' === $filename || false === strpos( $filename, '.php' ) ) {
			return array();
		}

		$path = COMPLETE99_PLATFORM_DIR . 'data/' . $filename;
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$data = require $path;
		return is_array( $data ) ? $data : array();
	}

	private static function commerce_readiness() {
		if ( ! class_exists( 'Complete99_Commerce' ) ) {
			return array(
				'ready'   => false,
				'missing' => array( 'commerce_module' ),
			);
		}

		$response = Complete99_Commerce::private_readiness();
		if ( is_wp_error( $response ) ) {
			return array(
				'ready'   => false,
				'missing' => array( 'commerce_readiness_error' ),
			);
		}
		if ( is_object( $response ) && method_exists( $response, 'get_data' ) ) {
			$response = $response->get_data();
		}
		return is_array( $response ) ? $response : array(
			'ready'   => false,
			'missing' => array( 'commerce_readiness_unavailable' ),
		);
	}

	private static function evaluation_catalog_status() {
		if ( class_exists( 'Complete99_Platform', false )
			&& is_callable( array( 'Complete99_Platform', 'evaluation_catalog_status' ) ) ) {
			$status = Complete99_Platform::evaluation_catalog_status();
			if ( is_array( $status ) ) {
				return $status;
			}
		}
		return array(
			'schema'       => 'complete99-evaluation-catalog-status/v1',
			'ready'        => false,
			'reason'       => 'module_unavailable',
			'receipt'      => array(
				'present'            => false,
				'valid'              => false,
				'status'             => '',
				'mode'               => '',
				'seed_count'         => 0,
				'ingredient_count'   => 0,
				'product_plan_count' => 0,
				'woo_product_count'  => 0,
				'woo_materialized'   => false,
			),
			'materialized' => array(
				'ingredient_count'   => 0,
				'product_plan_count' => 0,
			),
		);
	}

	private static function link( $url, $label ) {
		$url = esc_url( (string) $url, array( 'https', 'http' ) );
		if ( '' === $url ) {
			return;
		}
		printf(
			'<a class="button button-secondary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	private static function local_name( $record, $key, $fallback = '' ) {
		$value = isset( $record[ $key ] ) ? $record[ $key ] : $fallback;
		if ( is_array( $value ) ) {
			$locale = 0 === strpos( strtolower( (string) get_user_locale() ), 'he' ) ? 'he' : 'en';
			$value = isset( $value[ $locale ] ) ? $value[ $locale ] : ( isset( $value['en'] ) ? $value['en'] : $fallback );
		}
		return sanitize_text_field( (string) $value );
	}

	private static function badge( $ok, $yes = 'מוכן', $no = 'מוחזק לבדיקה' ) {
		printf(
			'<span class="c99-review-badge %1$s">%2$s</span>',
			$ok ? 'is-ready' : 'is-held',
			esc_html( $ok ? $yes : $no )
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'complete99-platform' ) );
		}

		$snapshot = self::snapshot();
		$commerce = isset( $snapshot['commerce'] ) && is_array( $snapshot['commerce'] ) ? $snapshot['commerce'] : array();
		$evaluation = isset( $snapshot['evaluation_catalog'] ) && is_array( $snapshot['evaluation_catalog'] )
			? $snapshot['evaluation_catalog']
			: array();
		$evaluation_receipt = isset( $evaluation['receipt'] ) && is_array( $evaluation['receipt'] )
			? $evaluation['receipt']
			: array();
		$evaluation_materialized = isset( $evaluation['materialized'] ) && is_array( $evaluation['materialized'] )
			? $evaluation['materialized']
			: array();
		$missing = isset( $commerce['missing'] ) && is_array( $commerce['missing'] )
			? array_slice( array_map( 'sanitize_key', $commerce['missing'] ), 0, 100 )
			: array();
		$science = isset( $snapshot['culinary_science'] ) && is_array( $snapshot['culinary_science'] ) ? $snapshot['culinary_science'] : array();
		$science_entities = isset( $science['entities'] ) && is_array( $science['entities'] ) ? $science['entities'] : array();
		$science_sources = isset( $science['sources'] ) && is_array( $science['sources'] ) ? $science['sources'] : array();
		$science_price_records = array_filter(
			$science_entities,
			static function ( $entity ) {
				return in_array( isset( $entity['type'] ) ? $entity['type'] : '', array( 'retail_listing', 'market_observation' ), true );
			}
		);
		$commerce_graph = isset( $snapshot['culinary_commerce_graph'] ) && is_array( $snapshot['culinary_commerce_graph'] ) ? $snapshot['culinary_commerce_graph'] : array();
		$graph_products = isset( $commerce_graph['products'] ) && is_array( $commerce_graph['products'] ) ? $commerce_graph['products'] : array();
		$graph_variants = isset( $commerce_graph['variants'] ) && is_array( $commerce_graph['variants'] ) ? $commerce_graph['variants'] : array();
		$graph_skus = isset( $commerce_graph['skus'] ) && is_array( $commerce_graph['skus'] ) ? $commerce_graph['skus'] : array();
		$graph_observations = isset( $commerce_graph['observations'] ) && is_array( $commerce_graph['observations'] ) ? $commerce_graph['observations'] : array();
		$graph_offers = isset( $commerce_graph['channel_offers'] ) && is_array( $commerce_graph['channel_offers'] ) ? $commerce_graph['channel_offers'] : array();
		$graph_bundles = isset( $commerce_graph['bundles'] ) && is_array( $commerce_graph['bundles'] ) ? $commerce_graph['bundles'] : array();
		$graph_connector_profiles = isset( $commerce_graph['connector_profiles'] ) && is_array( $commerce_graph['connector_profiles'] ) ? $commerce_graph['connector_profiles'] : array();
		$graph_integration_consumers = isset( $commerce_graph['integration_consumers'] ) && is_array( $commerce_graph['integration_consumers'] ) ? $commerce_graph['integration_consumers'] : array();
		$graph_active_offers = array_filter(
			$graph_offers,
			static function ( $offer ) {
				return 'active' === ( isset( $offer['state'] ) ? $offer['state'] : '' );
			}
		);
		?>
		<div class="wrap c99-review-lab" dir="rtl">
			<style>
				.c99-review-lab{max-width:1320px}.c99-review-actions{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0}
				.c99-review-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin:18px 0}
				.c99-review-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
				.c99-review-number{display:block;font-size:30px;font-weight:700;line-height:1.1;margin-top:8px}
				.c99-review-badge{display:inline-block;border-radius:999px;padding:3px 9px;font-size:12px;font-weight:700}
				.c99-review-badge.is-ready{background:#dff5e5;color:#14532d}.c99-review-badge.is-held{background:#fff3cd;color:#713f12}
				.c99-review-table{background:#fff}.c99-review-table td,.c99-review-table th{vertical-align:top}
				.c99-review-image{width:96px;height:72px;object-fit:cover;border-radius:8px;background:#f0f0f1}
				.c99-review-code{direction:ltr;text-align:left;font-family:monospace;font-size:12px}
				.c99-review-section{margin-top:28px}.c99-review-note{max-width:920px;font-size:14px}
			</style>
			<h1>מרכז הבדיקה של Complete99</h1>
			<p class="c99-review-note">כאן רואים במקום אחד את המנות, המרכיבים, תכניות המוצרים, מחירי ההערכה, המלאי, התמונות, המאמרים, החיבורים ומצב החנות. הדף פרטי למנהלי האתר ואינו מפרסם דבר באופן אוטומטי.</p>

			<div class="c99-review-actions">
				<?php
				self::link( home_url( '/' ), 'האתר בעברית' );
				self::link( home_url( '/en/' ), 'האתר באנגלית' );
				self::link( home_url( '/dishes/' ), 'כל המנות' );
				self::link( home_url( '/ingredients/' ), 'כל המרכיבים' );
				self::link( home_url( '/knowledge/' ), 'מרכז הידע' );
				self::link( home_url( '/request-a-proposal/' ), 'הזמנה לקבוצה' );
				self::link( rest_url( 'complete99/v1/health' ), 'בדיקת בריאות' );
				self::link( Complete99_Settings::app_url( 'he' ), 'מערכת התפעול' );
				?>
			</div>

			<div class="c99-review-grid">
				<div class="c99-review-card"><strong>מנות מחוברות</strong><span class="c99-review-number"><?php echo esc_html( count( $snapshot['dishes'] ) ); ?></span></div>
				<div class="c99-review-card"><strong>מרכיבים ייחודיים</strong><span class="c99-review-number"><?php echo esc_html( count( $snapshot['ingredient_codes'] ) ); ?></span></div>
				<div class="c99-review-card"><strong>תכניות מוצרים</strong><span class="c99-review-number"><?php echo esc_html( count( $snapshot['products'] ) ); ?></span></div>
				<div class="c99-review-card"><strong>נכסים שנוצרו</strong><span class="c99-review-number"><?php echo esc_html( count( $snapshot['assets'] ) ); ?></span></div>
				<div class="c99-review-card"><strong>טיוטות ידע</strong><span class="c99-review-number"><?php echo esc_html( count( $snapshot['guides'] ) ); ?></span></div>
				<div class="c99-review-card"><strong>קבלת קטלוג הערכה</strong><span class="c99-review-number"><?php self::badge( ! empty( $evaluation_receipt['valid'] ), 'אומתה', 'מוחזקת' ); ?></span></div>
				<div class="c99-review-card"><strong>מרכיבים פרטיים שנשמרו</strong><span class="c99-review-number"><?php echo esc_html( (int) ( $evaluation_materialized['ingredient_count'] ?? 0 ) ); ?></span></div>
				<div class="c99-review-card"><strong>תכניות פרטיות שנשמרו</strong><span class="c99-review-number"><?php echo esc_html( (int) ( $evaluation_materialized['product_plan_count'] ?? 0 ) ); ?></span></div>
				<div class="c99-review-card"><strong>חנות ציבורית</strong><span class="c99-review-number"><?php self::badge( ! empty( $commerce['ready'] ), 'מוכנה', 'בבדיקות קבלה' ); ?></span></div>
				<div class="c99-review-card"><strong>ישויות קולינריה ומדע</strong><span class="c99-review-number"><?php echo esc_html( count( $science_entities ) ); ?></span></div>
				<div class="c99-review-card"><strong>מקורות מחקר ושוק</strong><span class="c99-review-number"><?php echo esc_html( count( $science_sources ) ); ?></span></div>
				<div class="c99-review-card"><strong>תצפיות מחיר מדויקות</strong><span class="c99-review-number"><?php echo esc_html( count( $science_price_records ) ); ?></span></div>
				<div class="c99-review-card"><strong>מוצרי קטלוג מופרדים</strong><span class="c99-review-number"><?php echo esc_html( count( $graph_products ) ); ?></span></div>
				<div class="c99-review-card"><strong>וריאנטים ו־SKU</strong><span class="c99-review-number"><?php echo esc_html( count( $graph_variants ) . ' / ' . count( $graph_skus ) ); ?></span></div>
				<div class="c99-review-card"><strong>תצפיות שוק מנורמלות</strong><span class="c99-review-number"><?php echo esc_html( count( $graph_observations ) ); ?></span></div>
				<div class="c99-review-card"><strong>חבילות מסחר מתוכננות</strong><span class="c99-review-number"><?php echo esc_html( count( $graph_bundles ) ); ?></span></div>
				<div class="c99-review-card"><strong>הצעות ערוץ פעילות</strong><span class="c99-review-number"><?php echo esc_html( count( $graph_active_offers ) ); ?></span></div>
				<div class="c99-review-card"><strong>מחברי קופות וצרכני API</strong><span class="c99-review-number"><?php echo esc_html( count( $graph_connector_profiles ) . ' / ' . count( $graph_integration_consumers ) ); ?></span></div>
			</div>

			<section class="c99-review-section">
				<h2>גרף מסחרי מודולרי</h2>
				<p class="c99-review-note">כאן יש הפרדה מלאה בין ידע, מוצר, וריאנט, SKU, תצפית מחיר והצעת מכירה. תצפית מחיר בחו״ל אינה הופכת להצעת מכירה בישראל. הצעה יכולה להפוך לפעילה רק לאחר SKU מאומת, מס, עלות נחיתה, מרווח, מלאי ואישור ערוץ.</p>
				<table class="widefat striped c99-review-table">
					<thead><tr><th>מוצר</th><th>ישות ידע</th><th>מצב</th><th>וריאנטים</th><th>SKU</th><th>תצפיות</th></tr></thead>
					<tbody>
					<?php foreach ( $graph_products as $graph_product ) : ?>
						<?php
						$product_id = isset( $graph_product['id'] ) ? (string) $graph_product['id'] : '';
						$product_variants = array_filter(
							$graph_variants,
							static function ( $variant ) use ( $product_id ) {
								return $product_id === ( isset( $variant['product_id'] ) ? $variant['product_id'] : '' );
							}
						);
						$variant_ids = array_map(
							static function ( $variant ) {
								return isset( $variant['id'] ) ? $variant['id'] : '';
							},
							$product_variants
						);
						$product_skus = array_filter(
							$graph_skus,
							static function ( $sku ) use ( $variant_ids ) {
								return in_array( isset( $sku['variant_id'] ) ? $sku['variant_id'] : '', $variant_ids, true );
							}
						);
						$sku_ids = array_map(
							static function ( $sku ) {
								return isset( $sku['id'] ) ? $sku['id'] : '';
							},
							$product_skus
						);
						$product_observations = array_filter(
							$graph_observations,
							static function ( $observation ) use ( $sku_ids ) {
								return in_array( isset( $observation['sku_id'] ) ? $observation['sku_id'] : '', $sku_ids, true );
							}
						);
						?>
						<tr>
							<td><strong><?php echo esc_html( self::local_name( $graph_product, 'name', $product_id ) ); ?></strong><div class="c99-review-code"><?php echo esc_html( $product_id ); ?></div></td>
							<td class="c99-review-code"><?php echo esc_html( isset( $graph_product['knowledge_entity_id'] ) ? $graph_product['knowledge_entity_id'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $graph_product['state'] ) ? $graph_product['state'] : '' ); ?></td>
							<td><?php echo esc_html( count( $product_variants ) ); ?></td>
							<td><?php echo esc_html( count( $product_skus ) ); ?></td>
							<td><?php echo esc_html( count( $product_observations ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<section class="c99-review-section">
				<h2>מחברי קופות וצרכני API</h2>
				<p class="c99-review-note">כל מתאם מקבל גבול ערוצים ושווקים מוגדר. הפעלה דורשת חוזה ספק מאומת, מזהה מפתח ייעודי וחתימה הקשורה לנתיב ולצרכן.</p>
				<table class="widefat striped c99-review-table">
					<thead><tr><th>צרכן</th><th>מחבר</th><th>מזהה מפתח</th><th>ערוצים</th><th>מצב</th></tr></thead>
					<tbody>
					<?php foreach ( $graph_integration_consumers as $consumer ) : ?>
						<tr>
							<td class="c99-review-code"><?php echo esc_html( isset( $consumer['id'] ) ? $consumer['id'] : '' ); ?></td>
							<td class="c99-review-code"><?php echo esc_html( isset( $consumer['connector_profile_id'] ) ? $consumer['connector_profile_id'] : '' ); ?></td>
							<td class="c99-review-code"><?php echo esc_html( isset( $consumer['key_id'] ) ? $consumer['key_id'] : '' ); ?></td>
							<td class="c99-review-code"><?php echo esc_html( implode( ', ', isset( $consumer['channel_ids'] ) && is_array( $consumer['channel_ids'] ) ? $consumer['channel_ids'] : array() ) ); ?></td>
							<td><?php echo esc_html( isset( $consumer['state'] ) ? $consumer['state'] : '' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<section class="c99-review-section">
				<h2>מוזיאון המדע, SEO ומודל עסקי</h2>
				<p class="c99-review-note">זהו גרף המחקר הפרטי. עמוד ציבורי נוצר רק לאחר אישור דו-לשוני, ראיות, זכויות ושער פרסום. המחירים כאן הם תצפיות מקור מתוארכות ולא מחיר מכירה בישראל.</p>
				<table class="widefat striped c99-review-table">
					<thead><tr><th>ישות</th><th>סוג ותפקיד</th><th>בעל SEO ונתיב</th><th>תמחור ומוניטיזציה</th><th>פרסום</th></tr></thead>
					<tbody>
					<?php foreach ( $science_entities as $entity ) : ?>
						<?php
						$seo = isset( $entity['seo'] ) && is_array( $entity['seo'] ) ? $entity['seo'] : array();
						$commerce_plan = isset( $entity['commerce'] ) && is_array( $entity['commerce'] ) ? $entity['commerce'] : array();
						$business = isset( $commerce_plan['business_model'] ) && is_array( $commerce_plan['business_model'] ) ? $commerce_plan['business_model'] : array();
						$publication = isset( $entity['publication'] ) && is_array( $entity['publication'] ) ? $entity['publication'] : array();
						?>
						<tr>
							<td><strong><?php echo esc_html( self::local_name( $entity, 'name', isset( $entity['id'] ) ? $entity['id'] : '' ) ); ?></strong><div class="c99-review-code"><?php echo esc_html( isset( $entity['id'] ) ? $entity['id'] : '' ); ?></div></td>
							<td><code><?php echo esc_html( isset( $entity['type'] ) ? $entity['type'] : '' ); ?></code><br><?php echo esc_html( isset( $seo['page_role'] ) ? $seo['page_role'] : '' ); ?> / <?php echo esc_html( isset( $seo['route_mode'] ) ? $seo['route_mode'] : '' ); ?></td>
							<td><div class="c99-review-code"><?php echo esc_html( isset( $seo['owner_entity_id'] ) ? $seo['owner_entity_id'] : '' ); ?></div><div class="c99-review-code"><?php echo esc_html( self::local_name( $seo, 'canonical_path' ) ); ?></div></td>
							<td><?php echo esc_html( isset( $business['pricing_state'] ) ? $business['pricing_state'] : '' ); ?><br><small><?php echo esc_html( implode( ', ', isset( $business['revenue_models'] ) && is_array( $business['revenue_models'] ) ? $business['revenue_models'] : array() ) ); ?></small><div class="c99-review-code"><?php echo esc_html( implode( ', ', isset( $business['observation_entity_ids'] ) && is_array( $business['observation_entity_ids'] ) ? $business['observation_entity_ids'] : array() ) ); ?></div></td>
							<td><?php self::badge( 'approved_public' === ( isset( $publication['state'] ) ? $publication['state'] : '' ), 'מאושר לציבור', 'פרטי לבדיקה' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<section class="c99-review-section">
				<h2>מנות ועץ מרכיבים</h2>
				<table class="widefat striped c99-review-table">
					<thead><tr><th>מנה</th><th>זהות קבועה</th><th>מרכיבים מקושרים</th><th>מצב מוצר</th></tr></thead>
					<tbody>
					<?php foreach ( $snapshot['dishes'] as $dish ) : ?>
						<?php
						$identity = isset( $dish['identity'] ) && is_array( $dish['identity'] ) ? $dish['identity'] : array();
						$relations = isset( $dish['relations'] ) && is_array( $dish['relations'] ) ? $dish['relations'] : array();
						$ingredient_codes = isset( $relations['ingredient_codes'] ) && is_array( $relations['ingredient_codes'] )
							? array_slice( $relations['ingredient_codes'], 0, 30 )
							: array();
						?>
						<tr>
							<td><strong><?php echo esc_html( self::local_name( $identity, 'name', self::local_name( $dish, 'dish_id' ) ) ); ?></strong></td>
							<td class="c99-review-code"><?php echo esc_html( isset( $dish['dish_id'] ) ? $dish['dish_id'] : '' ); ?></td>
							<td class="c99-review-code"><?php echo esc_html( implode( ', ', array_map( 'sanitize_key', $ingredient_codes ) ) ); ?></td>
							<td><?php self::badge( ! empty( $relations['product_codes'] ), 'מקושר', 'ממתין למוצר מאומת' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<section class="c99-review-section">
				<h2>מוצרי הערכה, מחיר ומלאי</h2>
				<?php if ( empty( $snapshot['products'] ) ) : ?>
					<p>קובץ מחירי השוק עדיין נאסף ומאומת. כאשר הוא ייטען, כל מוצר יוצג כאן עם מקור, גודל אריזה, מחיר ליחידה ומלאי הערכה.</p>
				<?php else : ?>
					<table class="widefat striped c99-review-table">
						<thead><tr><th>תמונה</th><th>מוצר</th><th>קוד</th><th>אריזה וסיווג</th><th>מחיר הערכה</th><th>מלאי הערכה</th><th>מקור</th><th>מצב מכירה</th></tr></thead>
						<tbody>
						<?php foreach ( $snapshot['products'] as $product ) : ?>
							<?php
							$product_image = isset( $product['image_asset'] ) ? sanitize_file_name( $product['image_asset'] ) : '';
							$product_image_url = '' !== $product_image
								? COMPLETE99_PLATFORM_URL . 'assets/images/generated/' . rawurlencode( $product_image )
								: '';
							$observation = isset( $product['market_observation'] ) && is_array( $product['market_observation'] )
								? $product['market_observation']
								: array();
							?>
							<tr>
								<td><?php if ( $product_image_url ) : ?><img class="c99-review-image" src="<?php echo esc_url( $product_image_url ); ?>" alt="" loading="lazy" /><?php endif; ?></td>
								<td><strong><?php echo esc_html( self::local_name( $product, 'name' ) ); ?></strong></td>
								<td class="c99-review-code"><?php echo esc_html( isset( $product['product_code'] ) ? sanitize_key( $product['product_code'] ) : '' ); ?></td>
								<td><?php echo esc_html( self::local_name( $product, 'package_label' ) ); ?><div class="c99-review-code"><?php echo esc_html( isset( $product['classification'] ) ? sanitize_key( $product['classification'] ) : '' ); ?></div></td>
								<td><?php echo esc_html( isset( $product['evaluation_price_ils'] ) ? number_format_i18n( (float) $product['evaluation_price_ils'], 2 ) . ' ₪' : '' ); ?></td>
								<td><?php echo esc_html( isset( $product['evaluation_stock'] ) ? absint( $product['evaluation_stock'] ) : 0 ); ?></td>
								<td>
									<?php
									if ( ! empty( $observation['source_url'] ) ) {
										self::link( $observation['source_url'], 'בדיקת מחיר' );
									}
									?>
									<div class="c99-review-code"><?php echo esc_html( isset( $observation['checked_at'] ) ? $observation['checked_at'] : '' ); ?></div>
								</td>
								<td><?php self::badge( ! empty( $product['public_sale_eligible'] ), 'עבר קבלה', 'פרטי לבדיקה' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="c99-review-section">
				<h2>נכסים חזותיים</h2>
				<?php if ( empty( $snapshot['assets'] ) ) : ?>
					<p>סדרת הנכסים נוצרת כעת. הנכסים יוצגו כאן לפני בחירה לפרסום.</p>
				<?php else : ?>
					<table class="widefat striped c99-review-table">
						<thead><tr><th>תמונה</th><th>נכס</th><th>שימוש</th><th>בדיקה</th></tr></thead>
						<tbody>
						<?php foreach ( $snapshot['assets'] as $asset ) : ?>
							<?php
							$filename = isset( $asset['filename'] ) ? sanitize_file_name( $asset['filename'] ) : '';
							$image_url = '' !== $filename ? COMPLETE99_PLATFORM_URL . 'assets/images/generated/' . rawurlencode( $filename ) : '';
							?>
							<tr>
								<td><?php if ( $image_url ) : ?><img class="c99-review-image" src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" /><?php endif; ?></td>
								<td><strong><?php echo esc_html( self::local_name( $asset, 'label', isset( $asset['slug'] ) ? $asset['slug'] : '' ) ); ?></strong><div class="c99-review-code"><?php echo esc_html( isset( $asset['slug'] ) ? sanitize_key( $asset['slug'] ) : '' ); ?></div></td>
								<td><?php echo esc_html( self::local_name( $asset, 'usage_state', 'held' ) ); ?></td>
								<td><?php self::badge( 'approved' === ( isset( $asset['review_state'] ) ? $asset['review_state'] : '' ), 'מאושר', 'הערכה' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="c99-review-section">
				<h2>חיבורי הזמנה</h2>
				<table class="widefat striped c99-review-table">
					<thead><tr><th>שירות</th><th>כתובת ציבורית</th><th>אימות בית עסק</th><th>קבלת חיבור</th></tr></thead>
					<tbody>
					<?php foreach ( $snapshot['connectors'] as $code => $connector ) : ?>
						<tr>
							<td><strong><?php echo esc_html( isset( $connector['label'] ) ? $connector['label'] : $code ); ?></strong></td>
							<td><?php self::badge( ! empty( $connector['public_enabled'] ), 'מוצג', 'מוחזק' ); ?></td>
							<td><?php self::badge( ! empty( $connector['merchant_verified'] ), 'אומת', 'דורש אימות' ); ?></td>
							<td><?php self::badge( ! empty( $connector['acceptance_receipt'] ), 'נבדק', 'דורש קבלה' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>

			<section class="c99-review-section">
				<h2>בדיקות חנות שנותרו</h2>
				<?php if ( empty( $missing ) ) : ?>
					<p><?php self::badge( true, 'כל בדיקות הקבלה עברו', '' ); ?></p>
				<?php else : ?>
					<p>המערכת שומרת את החנות סגורה לציבור עד שהבדיקות הבאות יושלמו:</p>
					<ul>
						<?php foreach ( $missing as $item ) : ?>
							<li class="c99-review-code"><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		</div>
		<?php
	}
}
