<?php
/**
 * Homepage product rail: WooCommerce-featured products if any exist,
 * otherwise the most recent published products. Rendered through
 * woocommerce/content-product.php so it stays in sync with the archive grid.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_template_part' ) ) {
	return;
}
$helanak_featured_ids = function_exists( 'wc_get_featured_product_ids' ) ? wc_get_featured_product_ids() : array();

$helanak_rail_args = array(
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'posts_per_page' => 8,
	'orderby'        => 'date',
	'order'          => 'DESC',
);
if ( $helanak_featured_ids ) {
	$helanak_rail_args['post__in'] = $helanak_featured_ids;
	$helanak_rail_args['orderby']  = 'post__in';
}
$helanak_rail_query     = new WP_Query( $helanak_rail_args );
$helanak_shop_permalink = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
?>
<section class="helanak-section" data-helanak-section="product_rail"<?php helanak_section_hidden_attr( 'product_rail' ); ?>>
	<div class="helanak-container">
		<div class="helanak-section__head">
			<h2>
				<?php
				echo esc_html( $helanak_featured_ids ? __( 'Featured picks', 'wp-helanak-kids-theme' ) : __( 'New arrivals', 'wp-helanak-kids-theme' ) );
				?>
			</h2>
			<a class="helanak-section__see-all" href="<?php echo esc_url( $helanak_shop_permalink ); ?>"><?php esc_html_e( 'See all →', 'wp-helanak-kids-theme' ); ?></a>
		</div>
		<?php if ( $helanak_rail_query->have_posts() ) : ?>
			<ul class="helanak-products">
				<?php
				while ( $helanak_rail_query->have_posts() ) :
					$helanak_rail_query->the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Products will appear here once your catalog is set up.', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php wp_reset_postdata(); ?>
