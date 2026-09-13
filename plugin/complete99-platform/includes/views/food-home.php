<?php
/** Food-led homepage. Included only by Complete99_Consumer. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$order_url = Complete99_Commerce::order_url( $lang );
?>
<div class="c99-food-portal c99-editorial-home" data-c99-home-experience="food-editorial-v2">
 <section class="c99-ed-intro c99-container" aria-labelledby="c99-home-title">
  <p class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'קומפלט 99 · אוכל, אנשים, תל אביב' : 'Complete99 · Food, people, Tel Aviv' ); ?></p>
  <div class="c99-ed-title-row">
   <h1 id="c99-home-title"><?php echo esc_html( $is_he ? 'מה אוכלים היום?' : 'What are you hungry for?' ); ?></h1>
   <p><?php echo esc_html( $is_he ? 'מהסיר לצלחת. מהמטבח לשולחן של כולם. אוכל שאוהבים, ורעיונות לארוחה הבאה.' : 'From the pot to your plate. From the kitchen to a shared table. Food you love, and ideas for your next meal.' ); ?></p>
  </div>
 </section>
 <section class="c99-ed-cover c99-container" aria-label="<?php echo esc_attr( $is_he ? 'סביב השולחן' : 'Around the table' ); ?>">
  <figure class="c99-ed-cover-photo">
   <picture><source srcset="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/editorial/c99-shared-table-v01-768.webp' ); ?>" media="(max-width: 760px)" />
   <img src="<?php echo esc_url( COMPLETE99_PLATFORM_URL . 'assets/images/editorial/c99-shared-table-v01.webp' ); ?>" width="1536" height="1024" fetchpriority="high" decoding="async" alt="<?php echo esc_attr( $is_he ? 'שולחן עם קוסקוס, מרק סלק, חצילים וסלט' : 'A table with couscous, beet soup, aubergines and salad' ); ?>" /></picture>
  </figure>
  <div class="c99-ed-cover-note">
   <span class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'יש מקום לכולם' : 'Room for everyone' ); ?></span>
   <h2><?php echo esc_html( $is_he ? 'פותחים שולחן.' : 'Set the table.' ); ?></h2>
   <p><?php echo esc_html( $is_he ? 'לצהריים במשרד, למפגש עם חברים או לארוחה משפחתית.' : 'For lunch at work, friends coming over or a family meal.' ); ?></p>
   <a class="c99-ed-button" href="<?php echo esc_url( self::route( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'מתכננים ארוחה לקבוצה' : 'Plan a group meal' ); ?><span aria-hidden="true">↗</span></a>
  </div>
  <a class="c99-ed-cover-caption" href="#c99-home-menu"><?php echo esc_html( $is_he ? 'או שמתחילים במשהו קטן ↓' : 'Or start with a little something ↓' ); ?></a>
 </section>
 <nav class="c99-home-choices c99-container" aria-label="<?php echo esc_attr( $is_he ? 'מה מחפשים היום' : 'Find your next meal' ); ?>">
  <a href="#c99-home-menu"><span class="c99-ed-choice-number" aria-hidden="true">01</span><strong><?php echo esc_html( $is_he ? 'בא לי לאכול' : 'Something to eat' ); ?></strong><span><?php echo esc_html( $is_he ? 'בפיתה, בצלחת או ישר מהסיר' : 'In a pita, on a plate or from the pot' ); ?></span><i aria-hidden="true">↗</i></a>
  <a href="<?php echo esc_url( self::route( 'proposal', $lang ) ); ?>"><span class="c99-ed-choice-number" aria-hidden="true">02</span><strong><?php echo esc_html( $is_he ? 'מזמינים לכולם' : 'Feed the whole team' ); ?></strong><span><?php echo esc_html( $is_he ? 'לצוות, למשפחה ולאורחים' : 'For colleagues, family and friends' ); ?></span><i aria-hidden="true">↗</i></a>
  <a href="<?php echo esc_url( self::route( 'ingredients', $lang ) ); ?>"><span class="c99-ed-choice-number" aria-hidden="true">03</span><strong><?php echo esc_html( $is_he ? 'סקרנים לגבי אוכל' : 'Curious about food' ); ?></strong><span><?php echo esc_html( $is_he ? 'מרכיבים, מטבחים וסיפורים' : 'Ingredients, kitchens and stories' ); ?></span><i aria-hidden="true">↗</i></a>
 </nav>
 <section class="c99-ed-menu c99-container" id="c99-home-menu" aria-labelledby="c99-menu-preview-title">
  <div class="c99-ed-section-title"><div><p class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'מהמטבח שלנו' : 'From our kitchen' ); ?></p><h2 id="c99-menu-preview-title"><?php echo esc_html( $is_he ? 'קודם כול, משהו טעים.' : 'First, something delicious.' ); ?></h2></div><a class="c99-text-link" href="<?php echo esc_url( self::route( 'dishes', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לתפריט המלא' : 'The full menu' ); ?> ↗</a></div>
  <div class="c99-ed-menu-tools" data-c99-dish-filter data-c99-local-search>
   <label class="c99-ed-search"><span><?php echo esc_html( $is_he ? 'מה מחפשים בתפריט?' : 'What are you looking for on the menu?' ); ?></span><input type="search" data-c99-menu-search placeholder="<?php echo esc_attr( $is_he ? 'למשל קובה, קוסקוס או חציל' : 'Try kubbeh, couscous or aubergine' ); ?>" aria-controls="c99-home-menu-results" /></label>
   <div class="c99-ed-filter-row" role="group" aria-label="<?php echo esc_attr( $is_he ? 'סגנון ארוחה' : 'Meal style' ); ?>">
   <?php foreach ( array( 'all', 'pita', 'plate', 'pots', 'vegetarian' ) as $filter ) : $labels = self::culinary_facets()['filters'][ $filter ]; ?>
    <button type="button" class="c99-dish-filter-button<?php echo 'all' === $filter ? ' is-active' : ''; ?>" data-c99-filter="<?php echo esc_attr( $filter ); ?>" aria-pressed="<?php echo 'all' === $filter ? 'true' : 'false'; ?>"><?php echo esc_html( $labels[ $lang ] ); ?></button>
   <?php endforeach; ?>
   </div><p class="c99-ed-result-count" data-c99-filter-count aria-live="polite"></p>
  </div>
  <div id="c99-home-menu-results"><?php self::render_menu_grid( $lang, 0, true ); ?></div>
  <p data-c99-filter-empty hidden><?php echo esc_html( $is_he ? 'לא מצאנו מנה מתאימה. נסו שם אחר או חזרו לכל המנות.' : 'No matching dish. Try another name or return to all dishes.' ); ?></p>
 </section>
 <section class="c99-ed-gathering" aria-labelledby="c99-ed-group-title">
  <div class="c99-container c99-ed-gathering-grid">
   <figure><?php self::brand_picture( 'c99-food-house-spread-hero-2021-wp-v01', $is_he ? 'מבחר מנות קומפלט 99' : 'A selection of Complete99 dishes', 1400, 788 ); ?></figure>
   <div><p class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'ארוחה אחת. כולם יחד.' : 'One meal. Everyone together.' ); ?></p><h2 id="c99-ed-group-title"><?php echo esc_html( $is_he ? 'אתם מביאים את האנשים.' : 'You bring the people.' ); ?><br /><em><?php echo esc_html( $is_he ? 'נדבר על האוכל.' : 'Let’s talk food.' ); ?></em></h2>
   <p><?php echo esc_html( $is_he ? 'צהריים לצוות או מפגש גדול? ספרו לנו לכמה אנשים, לאיזה יום ומה אוהבים לאכול. משם נתכנן יחד את המנות והכמויות.' : 'Team lunch or a larger gathering? Tell us how many people, which day and what you like to eat. We’ll plan the dishes and quantities together.' ); ?></p>
   <a class="c99-ed-button" href="<?php echo esc_url( self::route( 'proposal', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'לבקשת הצעה לארוחה' : 'Request a meal proposal' ); ?> <span aria-hidden="true">↗</span></a>
   </div>
  </div>
 </section>
 <section class="c99-ed-journal c99-container" aria-labelledby="c99-ed-journal-title">
  <div class="c99-ed-section-title"><div><p class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'בין ביס לביס' : 'Between bites' ); ?></p><h2 id="c99-ed-journal-title"><?php echo esc_html( $is_he ? 'יש עוד הרבה לטעום.' : 'There’s more to discover.' ); ?></h2></div><a class="c99-text-link" href="<?php echo esc_url( self::route( 'knowledge', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'למדריכים' : 'Read the guides' ); ?> ↗</a></div>
  <div class="c99-ed-journal-grid">
   <a href="<?php echo esc_url( self::route( 'ingredients', $lang ) ); ?>"><figure><?php self::brand_picture( 'c99-food-sabich-pita-gallery-2021-wp-v01', $is_he ? 'חציל, טחינה וירקות בפיתה' : 'Aubergine, tahini and vegetables in a pita' ); ?></figure><span class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'חומרי גלם' : 'Ingredients' ); ?></span><h3><?php echo esc_html( $is_he ? 'מה עושה את הביס?' : 'What makes the bite?' ); ?></h3><p><?php echo esc_html( $is_he ? 'מהחציל ועד הטחינה, מכירים את מה שנכנס לצלחת.' : 'From aubergine to tahini, meet what goes onto your plate.' ); ?></p></a>
   <a href="<?php echo esc_url( self::route( 'traditions', $lang ) ); ?>"><figure><?php self::brand_picture( 'c99-food-kubeh-beet-soup-gallery-2021-wp-v01', $is_he ? 'קובה במרק סלק' : 'Kubbeh in beet soup' ); ?></figure><span class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'סיפורי אוכל' : 'Food stories' ); ?></span><h3><?php echo esc_html( $is_he ? 'לכל סיר יש סיפור.' : 'Every pot has a story.' ); ?></h3><p><?php echo esc_html( $is_he ? 'מנות שעוברות בין בתים, דורות ומטבחים.' : 'Dishes shared across homes, generations and kitchens.' ); ?></p></a>
   <a href="<?php echo esc_url( self::route( 'about', $lang ) ); ?>"><figure><?php self::brand_picture( 'c99-food-couscous-beef-gallery-2021-wp-v01', $is_he ? 'קוסקוס, ירקות ובשר' : 'Couscous, vegetables and beef' ); ?></figure><span class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'המטבח של 99' : 'The kitchen at 99' ); ?></span><h3><?php echo esc_html( $is_he ? 'תל אביב, שעת צהריים.' : 'Tel Aviv. Time for lunch.' ); ?></h3><p><?php echo esc_html( $is_he ? 'הסיפור שלנו מתחיל באבן גבירול ונמשך סביב השולחן.' : 'Our story starts on Ibn Gabirol and continues around the table.' ); ?></p></a>
  </div>
 </section>
 <section class="c99-ed-visit c99-container" aria-labelledby="c99-ed-visit-title"><div><p class="c99-ed-kicker"><?php echo esc_html( $is_he ? 'נפגשים בעיר' : 'Meet us in the city' ); ?></p><h2 id="c99-ed-visit-title"><?php echo esc_html( $is_he ? 'אבן גבירול 99, תל אביב.' : '99 Ibn Gabirol, Tel Aviv.' ); ?></h2></div><div class="c99-ed-visit-actions"><a class="c99-ed-button" href="<?php echo esc_url( self::route( 'contact', $lang ) ); ?>"><?php echo esc_html( $is_he ? 'איך מגיעים?' : 'Find us' ); ?> ↗</a><a class="c99-text-link" href="<?php echo esc_url( $order_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $is_he ? 'מזמינים ב-Wolt' : 'Order on Wolt' ); ?> ↗</a></div></section>
 <?php self::render_pantry_teaser( $lang ); ?>
</div>
