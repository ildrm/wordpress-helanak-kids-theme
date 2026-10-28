<?php
/**
 * Flash sale rail: real WooCommerce on-sale products. The countdown timer
 * only appears when at least one on-sale product has a scheduled sale
 * end-date (_sale_price_dates_to) — we never fabricate a fake deadline.
 * "Sold" progress bars use the product's real total_sales / stock_quantity,
 * not placeholder numbers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();

$helanak_sale_query = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 4,
		'post__in'       => $helanak_sale_ids ? $helanak_sale_ids : array( 0 ),
	)
);

$helanak_sale_end = 0;
if ( $helanak_sale_query->have_posts() ) {
	foreach ( $helanak_sale_query->posts as $helanak_sale_post ) {
		$helanak_sale_to = get_post_meta( $helanak_sale_post->ID, '_sale_price_dates_to', true );
		if ( $helanak_sale_to && ( ! $helanak_sale_end || $helanak_sale_to < $helanak_sale_end ) ) {
			$helanak_sale_end = (int) $helanak_sale_to;
		}
	}
}
?>
<section class="helanak-flash-sale-wrap helanak-section" data-helanak-section="flash_sale"<?php helanak_section_hidden_attr( 'flash_sale' ); ?>>
	<div class="helanak-container">
		<div class="helanak-flash-sale">
			<span class="helanak-flash-sale__kicker">⚡ <?php esc_html_e( 'Flash sale', 'wp-helanak-kids-theme' ); ?></span>

			<?php if ( $helanak_sale_end > time() ) : ?>
				<?php // Prototype countdown is H:M:S only — countdown.js rolls remaining days into the hours chip. ?>
				<div class="helanak-flash-sale__timer" data-helanak-countdown="<?php echo esc_attr( $helanak_sale_end ); ?>">
					<span class="helanak-countdown__chip"><b data-unit="hours">00</b><i><?php esc_html_e( 'hrs', 'wp-helanak-kids-theme' ); ?></i></span>
					<span class="helanak-countdown__chip"><b data-unit="minutes">00</b><i><?php esc_html_e( 'min', 'wp-helanak-kids-theme' ); ?></i></span>
					<span class="helanak-countdown__chip"><b data-unit="seconds">00</b><i><?php esc_html_e( 'sec', 'wp-helanak-kids-theme' ); ?></i></span>
				</div>
			<?php endif; ?>

			<?php if ( $helanak_sale_query->have_posts() ) : ?>
				<div class="helanak-flash-sale__deals">
					<?php
					while ( $helanak_sale_query->have_posts() ) :
						$helanak_sale_query->the_post();
						global $product;
						if ( ! $product instanceof WC_Product ) {
							continue;
						}
						$helanak_sold  = $product->get_total_sales();
						$helanak_stock = is_numeric( $product->get_stock_quantity() ) ? (int) $product->get_stock_quantity() : 0;
						$helanak_total = max( $helanak_sold + $helanak_stock, $helanak_sold, 1 );
						$helanak_pct   = min( 100, (int) round( ( $helanak_sold / $helanak_total ) * 100 ) );
						?>
						<div class="helanak-flash-sale__deal">
							<a class="helanak-flash-sale__deal-media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php echo $product->get_image( 'helanak-card' ); // phpcs:ignore WordPress.Security.EscapeOutput -- core-generated <img>. ?>
							</a>
							<a href="<?php the_permalink(); ?>">
								<span class="helanak-flash-sale__deal-name"><?php the_title(); ?></span>
							</a>
							<span class="helanak-flash-sale__deal-price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce price HTML. ?></span>
							<span class="helanak-flash-sale__deal-progress">
								<span style="width:<?php echo esc_attr( $helanak_pct ); ?>%"></span>
							</span>
							<span class="helanak-flash-sale__deal-sold">
								<?php
								printf(
									/* translators: 1: units sold, 2: total units. */
									esc_html__( 'Sold %1$s of %2$s', 'wp-helanak-kids-theme' ),
									esc_html( number_format_i18n( $helanak_sold ) ),
									esc_html( number_format_i18n( $helanak_total ) )
								);
								?>
							</span>
							<?php woocommerce_template_loop_add_to_cart(); ?>
						</div>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No active sales right now — check back soon!', 'wp-helanak-kids-theme' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php wp_reset_postdata(); ?>
