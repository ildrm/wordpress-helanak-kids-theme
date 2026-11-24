<?php
/**
 * Category slider: iterates real product_cat terms and pulls
 * each term's visual skin (icon/label/accent) from Helanak_Core_Category_Theme
 * when the Core plugin is active. Falls back to a plain icon if a category
 * has no skin assigned — skins are purely decorative, never gate products.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_cats = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'number'     => 0,
	)
);
if ( is_wp_error( $helanak_cats ) ) {
	$helanak_cats = array();
}
$helanak_shop_permalink = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
?>
<section class="helanak-section" id="helanak-category-slider" data-helanak-category-carousel data-helanak-section="category_slider"<?php helanak_section_hidden_attr( 'category_slider' ); ?>>
	<div class="helanak-container">
		<div class="helanak-section__head">
			<h2><?php esc_html_e( 'Shop by category', 'wp-helanak-kids-theme' ); ?></h2>
			<?php if ( $helanak_cats ) : ?>
				<div class="helanak-section__head-actions">
					<a class="helanak-section__see-all" href="<?php echo esc_url( $helanak_shop_permalink ); ?>"><?php esc_html_e( 'See all →', 'wp-helanak-kids-theme' ); ?></a>
					<div class="helanak-worlds__nav">
						<button type="button" data-helanak-worlds-prev aria-label="<?php esc_attr_e( 'Previous', 'wp-helanak-kids-theme' ); ?>">&lsaquo;</button>
						<button type="button" data-helanak-worlds-next aria-label="<?php esc_attr_e( 'Next', 'wp-helanak-kids-theme' ); ?>">&rsaquo;</button>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $helanak_cats ) : ?>
			<?php get_template_part( 'template-parts/category-rail', null, array( 'categories' => $helanak_cats, 'image_size' => 'medium' ) ); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Categories will appear here once products are added.', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>
	</div>
</section>
