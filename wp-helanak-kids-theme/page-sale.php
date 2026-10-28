<?php
/**
 * Template Name: Sale / Campaign
 *
 * Standalone campaign landing page — assign to any page via Page Attributes
 * (there's no fixed "sale" slug, since campaigns get renamed/retired). Reuses
 * the same real-data approach as template-parts/section-flash-sale.php: the
 * countdown only renders when a real on-sale product has a scheduled
 * _sale_price_dates_to, and "Deal of the day" is the single on-sale product
 * with the largest real regular/sale price gap — never a fabricated deadline
 * or placeholder discount.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$helanak_sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();

$helanak_sale_end = 0;
$helanak_deal_id  = 0;
$helanak_deal_pct = 0;

foreach ( $helanak_sale_ids as $helanak_sale_id ) {
	$helanak_sale_to = get_post_meta( $helanak_sale_id, '_sale_price_dates_to', true );
	if ( $helanak_sale_to && ( ! $helanak_sale_end || $helanak_sale_to < $helanak_sale_end ) ) {
		$helanak_sale_end = (int) $helanak_sale_to;
	}

	$helanak_sale_product = wc_get_product( $helanak_sale_id );
	if ( ! $helanak_sale_product ) {
		continue;
	}
	$helanak_regular = (float) $helanak_sale_product->get_regular_price();
	$helanak_sale    = (float) $helanak_sale_product->get_sale_price();
	if ( $helanak_regular <= 0 || $helanak_sale <= 0 ) {
		continue;
	}
	$helanak_pct = (int) round( ( 1 - ( $helanak_sale / $helanak_regular ) ) * 100 );
	if ( $helanak_pct > $helanak_deal_pct ) {
		$helanak_deal_pct = $helanak_pct;
		$helanak_deal_id  = $helanak_sale_id;
	}
}

$helanak_paged    = max( 1, (int) get_query_var( 'paged' ) );
$helanak_grid_ids = array_values( array_diff( $helanak_sale_ids, $helanak_deal_id ? array( $helanak_deal_id ) : array() ) );

$helanak_sale_query = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'paged'          => $helanak_paged,
		'post__in'       => $helanak_grid_ids ? $helanak_grid_ids : array( 0 ),
	)
);
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">

		<div class="helanak-sale-hero">
			<div class="helanak-sale-hero__grid">
				<div class="helanak-sale-hero__copy">
					<span class="helanak-flash-sale__kicker">⚡ <?php esc_html_e( 'Flash sale', 'wp-helanak-kids-theme' ); ?></span>
					<h1><?php esc_html_e( 'Deals little ones (and you) will love', 'wp-helanak-kids-theme' ); ?></h1>
					<p><?php esc_html_e( 'Limited-time prices on favorites across the shop — while stock lasts.', 'wp-helanak-kids-theme' ); ?></p>
				</div>
				<div class="helanak-sale-hero__aside">
					<?php if ( $helanak_sale_end > time() ) : ?>
						<div class="helanak-flash-sale__timer" data-helanak-countdown="<?php echo esc_attr( $helanak_sale_end ); ?>">
							<span class="helanak-countdown__chip"><b data-unit="hours">00</b><i><?php esc_html_e( 'hrs', 'wp-helanak-kids-theme' ); ?></i></span>
							<span class="helanak-countdown__chip"><b data-unit="minutes">00</b><i><?php esc_html_e( 'min', 'wp-helanak-kids-theme' ); ?></i></span>
							<span class="helanak-countdown__chip"><b data-unit="seconds">00</b><i><?php esc_html_e( 'sec', 'wp-helanak-kids-theme' ); ?></i></span>
						</div>
					<?php endif; ?>
					<?php
					// The prototype's celebrateDog: a continuously hopping dog beside the timer.
					helanak_character(
						'dog',
						array(
							'hop'   => true,
							'scale' => .8,
						)
					);
					?>
				</div>
			</div>
		</div>

		<?php
		$helanak_deal_product = $helanak_deal_id ? wc_get_product( $helanak_deal_id ) : null;
		if ( $helanak_deal_product ) :
			$helanak_deal_sold  = $helanak_deal_product->get_total_sales();
			$helanak_deal_stock = is_numeric( $helanak_deal_product->get_stock_quantity() ) ? (int) $helanak_deal_product->get_stock_quantity() : 0;
			$helanak_deal_total = max( $helanak_deal_sold + $helanak_deal_stock, $helanak_deal_sold, 1 );
			$helanak_deal_bar   = min( 100, (int) round( ( $helanak_deal_sold / $helanak_deal_total ) * 100 ) );
			$helanak_deal_stars = str_repeat( '★', (int) round( $helanak_deal_product->get_average_rating() ) ) . str_repeat( '☆', 5 - (int) round( $helanak_deal_product->get_average_rating() ) );
			?>
			<div class="helanak-deal-day">
				<a class="helanak-deal-day__media" href="<?php echo esc_url( $helanak_deal_product->get_permalink() ); ?>" aria-hidden="true" tabindex="-1">
					<?php echo $helanak_deal_product->get_image( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput -- core-generated <img>. ?>
					<span class="helanak-deal-day__pct">-<?php echo esc_html( $helanak_deal_pct ); ?>%</span>
				</a>
				<div class="helanak-deal-day__body">
					<span class="helanak-deal-day__chip">🎁 <?php esc_html_e( 'Deal of the day', 'wp-helanak-kids-theme' ); ?></span>
					<h3><a href="<?php echo esc_url( $helanak_deal_product->get_permalink() ); ?>"><?php echo esc_html( $helanak_deal_product->get_name() ); ?></a></h3>
					<?php if ( $helanak_deal_product->get_rating_count() > 0 ) : ?>
						<div class="helanak-deal-day__rating">
							<?php echo esc_html( $helanak_deal_stars ); ?>
							<span>(<?php echo esc_html( number_format_i18n( $helanak_deal_product->get_rating_count() ) ); ?>)</span>
						</div>
					<?php endif; ?>
					<div class="helanak-deal-day__price"><?php echo $helanak_deal_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce price HTML. ?></div>
					<span class="helanak-deal-day__progress"><span style="width:<?php echo esc_attr( $helanak_deal_bar ); ?>%"></span></span>
					<span class="helanak-deal-day__sold">
						<?php
						printf(
							/* translators: 1: units sold, 2: total units. */
							esc_html__( 'Sold %1$s of %2$s', 'wp-helanak-kids-theme' ),
							esc_html( number_format_i18n( $helanak_deal_sold ) ),
							esc_html( number_format_i18n( $helanak_deal_total ) )
						);
						?>
					</span>
					<div>
						<a class="helanak-btn helanak-btn--primary" href="<?php echo esc_url( $helanak_deal_product->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( $helanak_deal_product->get_id() ); ?>">
							<?php esc_html_e( 'Add to cart', 'wp-helanak-kids-theme' ); ?>
						</a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'More on sale', 'wp-helanak-kids-theme' ); ?></h2>

		<?php if ( $helanak_sale_query->have_posts() ) : ?>
			<ul class="helanak-products">
				<?php
				// Tells content-product.php to add the solid red %-off pill (real prices only).
				set_query_var( 'helanak_percent_badge', true );
				while ( $helanak_sale_query->have_posts() ) :
					$helanak_sale_query->the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				set_query_var( 'helanak_percent_badge', false );
				?>
			</ul>
			<?php
			$helanak_original_query      = $GLOBALS['wp_query'];
			$GLOBALS['wp_query']         = $helanak_sale_query;
			woocommerce_pagination();
			$GLOBALS['wp_query']         = $helanak_original_query;
			wp_reset_postdata();
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'No active sales right now — check back soon!', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
