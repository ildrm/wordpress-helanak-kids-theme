<?php
/**
 * Unified product-card partial — shared by the homepage rail, the archive
 * grid, and related products. Every optional part is wrapped in
 * data-helanak-card-part="{key}" using the always-render + hidden-attribute
 * contract (see helanak_card_hidden_attr() in inc/template-tags.php),
 * matching Helanak_Theme_Customizer::card_toggles() keys: hover_image,
 * rating, badge, wishlist, quick_view, compare, age_label, stock.
 *
 * Keep WooCommerce's <li> contract so related products and shortcode loops
 * remain valid lists. Theme-owned grids use <ul class="helanak-products">
 * with the same card partial.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$helanak_product_id = $product->get_id();
$helanak_badges      = class_exists( 'Helanak_Core_Badge_Engine' ) ? Helanak_Core_Badge_Engine::get_badges( $product ) : array();
$helanak_top_badge   = $helanak_badges[0] ?? null;

// Sale grids (page-sale.php) opt into a solid red %-off pill via query var —
// computed from the product's real prices, replacing the engine badge.
$helanak_pct_off = 0;
if ( get_query_var( 'helanak_percent_badge' ) && $product->is_on_sale() ) {
	$helanak_pct_regular = (float) $product->get_regular_price();
	$helanak_pct_sale    = (float) $product->get_sale_price();
	if ( $helanak_pct_regular > 0 && $helanak_pct_sale > 0 ) {
		$helanak_pct_off = (int) round( ( 1 - ( $helanak_pct_sale / $helanak_pct_regular ) ) * 100 );
	}
}

$helanak_age_terms = get_the_terms( $helanak_product_id, 'age_range' );
$helanak_age_term  = ( is_array( $helanak_age_terms ) && ! empty( $helanak_age_terms ) ) ? $helanak_age_terms[0] : null;

$helanak_card_sizes = '(min-width: 1180px) 280px, (min-width: 600px) 30vw, calc((100vw - 52px) / 2)';
$helanak_gallery_ids = $product->get_gallery_image_ids();
$helanak_hover_image = ! empty( $helanak_gallery_ids )
	? wp_get_attachment_image( $helanak_gallery_ids[0], 'helanak-card', false, array( 'class' => 'helanak-card__hover-image', 'sizes' => $helanak_card_sizes, 'loading' => 'lazy', 'fetchpriority' => 'low', 'decoding' => 'async', 'alt' => '' ) )
	: '';

$helanak_stock_status = $product->get_stock_status();
$helanak_rating       = $product->get_average_rating();
?>
<li class="helanak-card" data-helanak-product-id="<?php echo esc_attr( $helanak_product_id ); ?>">
	<div class="helanak-card__visual">
	<a class="helanak-card__media" href="<?php the_permalink(); ?>">
		<?php
		echo $product->get_image( 'helanak-card', array( 'sizes' => $helanak_card_sizes, 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- get_image() returns pre-escaped markup.
		if ( $helanak_hover_image ) :
			?>
			<span data-helanak-card-part="hover_image"<?php helanak_card_hidden_attr( 'hover_image' ); ?>>
				<?php echo $helanak_hover_image; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() returns pre-escaped markup. ?>
			</span>
		<?php endif; ?>

		<?php if ( $helanak_pct_off > 0 ) : ?>
			<span
				class="helanak-badge helanak-card__badge"
				data-helanak-card-part="badge"<?php helanak_card_hidden_attr( 'badge' ); ?>
				style="--badge-bg:#C4432B;--badge-fg:#fff"
			>
				-<?php echo esc_html( $helanak_pct_off ); ?>%
			</span>
		<?php elseif ( $helanak_top_badge ) : ?>
			<span
				class="helanak-badge helanak-card__badge"
				data-helanak-card-part="badge"<?php helanak_card_hidden_attr( 'badge' ); ?>
				style="--badge-bg:<?php echo esc_attr( $helanak_top_badge['bg'] ); ?>;--badge-fg:<?php echo esc_attr( $helanak_top_badge['fg'] ); ?>"
			>
				<?php echo esc_html( $helanak_top_badge['label'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $helanak_age_term ) : ?>
			<span class="helanak-card__age" data-helanak-card-part="age_label"<?php helanak_card_hidden_attr( 'age_label' ); ?>>
				<?php echo esc_html( helanak_age_label( $helanak_age_term ) ); ?>
			</span>
		<?php endif; ?>
	</a>

	<button
		type="button"
		class="helanak-card__wishlist"
		data-helanak-card-part="wishlist"<?php helanak_card_hidden_attr( 'wishlist' ); ?>
		data-helanak-wishlist-toggle
		data-product-id="<?php echo esc_attr( $helanak_product_id ); ?>"
		aria-pressed="false"
		aria-label="<?php esc_attr_e( 'Add to wishlist', 'wp-helanak-kids-theme' ); ?>"
	><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M20 5.5a5 5 0 0 0-8 1 5 5 0 0 0-8-1C.5 9.5 5 15 12 20c7-5 11.5-10.5 8-14.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></button>

	</div>
	<div class="helanak-card__tools">
	<?php if ( class_exists( 'Helanak_Quick_View' ) ) : ?>
		<button
			type="button"
			class="helanak-card__quick-view"
			data-helanak-card-part="quick_view"<?php helanak_card_hidden_attr( 'quick_view' ); ?>
			data-helanak-quick-view
			data-product-id="<?php echo esc_attr( $helanak_product_id ); ?>"
		>
			<?php esc_html_e( 'Quick view', 'wp-helanak-kids-theme' ); ?>
		</button>
	<?php endif; ?>

	<button
		type="button"
		class="helanak-card__compare"
		data-helanak-card-part="compare"<?php helanak_card_hidden_attr( 'compare' ); ?>
		data-helanak-compare-toggle
		data-product-id="<?php echo esc_attr( $helanak_product_id ); ?>"
		aria-pressed="false"
		aria-label="<?php esc_attr_e( 'Add to compare', 'wp-helanak-kids-theme' ); ?>"
	><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M4 7h15m-4-4 4 4-4 4M20 17H5m4-4-4 4 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg></button>

	</div>
	<div class="helanak-card__body">
		<div class="helanak-card__rating" data-helanak-card-part="rating"<?php helanak_card_hidden_attr( 'rating' ); ?>>
			<?php if ( $helanak_rating > 0 ) : ?>
				<?php
				echo esc_html(
					str_repeat( '★', (int) round( $helanak_rating ) ) . str_repeat( '☆', 5 - (int) round( $helanak_rating ) )
				);
				?>
				<span>(<?php echo esc_html( $product->get_rating_count() ); ?>)</span>
			<?php endif; ?>
		</div>

		<h3 class="helanak-card__title">
			<a href="<?php the_permalink(); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</h3>

		<div class="helanak-card__price-row">
			<span class="helanak-card__price">
				<?php echo wp_kses_post( $product->get_price_html() ); ?>
			</span>
			<?php woocommerce_template_loop_add_to_cart(); ?>
		</div>

		<p class="helanak-card__stock" data-helanak-card-part="stock"<?php helanak_card_hidden_attr( 'stock' ); ?>>
			<?php
			if ( 'instock' === $helanak_stock_status ) {
				esc_html_e( 'In stock', 'wp-helanak-kids-theme' );
			} elseif ( 'onbackorder' === $helanak_stock_status ) {
				esc_html_e( 'Available on backorder', 'wp-helanak-kids-theme' );
			} else {
				esc_html_e( 'Out of stock', 'wp-helanak-kids-theme' );
			}
			?>
		</p>
	</div>
</li>
