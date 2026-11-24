<?php
/** Show every direct child, including empty categories, above product results. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$helanak_parent = $args['parent'] ?? null;
if ( ! $helanak_parent instanceof WP_Term || 'product_cat' !== $helanak_parent->taxonomy ) {
	return;
}
$helanak_children = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'parent'     => $helanak_parent->term_id,
		'hide_empty' => false,
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
	)
);
if ( is_wp_error( $helanak_children ) || ! $helanak_children ) {
	return;
}
?>
<section class="helanak-subcategories" aria-labelledby="helanak-subcategories-heading" data-helanak-category-carousel data-helanak-parent-category="<?php echo esc_attr( $helanak_parent->term_id ); ?>">
	<div class="helanak-section__head">
		<h2 id="helanak-subcategories-heading"><?php esc_html_e( 'Subcategories', 'wp-helanak-kids-theme' ); ?></h2>
		<div class="helanak-worlds__nav">
			<button type="button" data-helanak-worlds-prev aria-label="<?php esc_attr_e( 'Previous', 'wp-helanak-kids-theme' ); ?>">&lsaquo;</button>
			<button type="button" data-helanak-worlds-next aria-label="<?php esc_attr_e( 'Next', 'wp-helanak-kids-theme' ); ?>">&rsaquo;</button>
		</div>
	</div>
	<?php get_template_part( 'template-parts/category-rail', null, array( 'categories' => $helanak_children ) ); ?>
</section>
