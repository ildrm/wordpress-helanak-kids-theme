<?php
/**
 * "Shop by world" category slider: iterates real product_cat terms and pulls
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
		'number'     => 10,
	)
);
if ( is_wp_error( $helanak_cats ) ) {
	$helanak_cats = array();
}
$helanak_shop_permalink = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
?>
<section class="helanak-section" id="helanak-category-slider" data-helanak-section="category_slider"<?php helanak_section_hidden_attr( 'category_slider' ); ?>>
	<div class="helanak-container">
		<div class="helanak-section__head">
			<h2><?php esc_html_e( 'Shop by world', 'wp-helanak-kids-theme' ); ?></h2>
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
			<div class="helanak-worlds__track" data-helanak-worlds-track>
				<?php
				// Prototype: each icon bobs/wiggles/hops on its own rhythm.
				$helanak_icon_anims = array( 'scbob', 'scwiggle', 'schop' );
				foreach ( array_values( $helanak_cats ) as $helanak_cat_index => $helanak_cat ) :
					$helanak_skin       = class_exists( 'Helanak_Core_Category_Theme' ) ? Helanak_Core_Category_Theme::get_skin_for_term( $helanak_cat ) : null;
					$helanak_icon       = $helanak_skin[2] ?? '🧸';
					// The emoji itself animates (not the gradient header), like the prototype.
					$helanak_emoji_style = sprintf(
						'display:inline-block;animation:%1$s %2$ss ease-in-out %3$ss infinite',
						$helanak_icon_anims[ $helanak_cat_index % 3 ],
						number_format( 2.8 + ( $helanak_cat_index % 4 ) * 0.6, 1, '.', '' ),
						number_format( $helanak_cat_index * 0.25, 2, '.', '' )
					);
					// Skin accent pair recolors the tile header per category, like the prototype's per-hue tile gradients.
					$helanak_icon_style = isset( $helanak_skin[3], $helanak_skin[4] )
						? sprintf( '--world-bg:linear-gradient(135deg,%1$s,%2$s)', $helanak_skin[3], $helanak_skin[4] )
						: '';
					?>
					<a class="helanak-world-card" href="<?php echo esc_url( get_term_link( $helanak_cat ) ); ?>">
						<span class="helanak-world-card__icon" aria-hidden="true"<?php echo $helanak_icon_style ? ' style="' . esc_attr( $helanak_icon_style ) . '"' : ''; ?>><span style="<?php echo esc_attr( $helanak_emoji_style ); ?>"><?php echo esc_html( $helanak_icon ); ?></span></span>
						<span class="helanak-world-card__name"><?php echo esc_html( $helanak_cat->name ); ?></span>
						<span class="helanak-world-card__count">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of products in this category */
									_n( '%d item', '%d items', $helanak_cat->count, 'wp-helanak-kids-theme' ),
									$helanak_cat->count
								)
							);
							?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Categories will appear here once products are added.', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>
	</div>
</section>
