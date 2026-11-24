<?php
/** Shared category cards for the homepage and direct-child archive navigation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$helanak_cats = $args['categories'] ?? array();
if ( ! is_array( $helanak_cats ) || ! $helanak_cats ) {
	return;
}
$helanak_icon_anims = array( 'scbob', 'scwiggle', 'schop' );
$helanak_image_size = $args['image_size'] ?? 'thumbnail';
?>
<div class="helanak-worlds__track" data-helanak-worlds-track>
	<?php foreach ( array_values( $helanak_cats ) as $helanak_cat_index => $helanak_cat ) : ?>
		<?php
		if ( ! $helanak_cat instanceof WP_Term ) {
			continue;
		}
		$helanak_link = get_term_link( $helanak_cat );
		if ( is_wp_error( $helanak_link ) ) {
			continue;
		}
		$helanak_visuals = class_exists( 'Helanak_Core_Category_Theme' ) ? Helanak_Core_Category_Theme::get_visuals_for_term( $helanak_cat ) : null;
		$helanak_icon    = $helanak_visuals['icon'] ?? '🧸';
		$helanak_name    = $helanak_visuals
			? ( 'fa' === helanak_current_lang() ? $helanak_visuals['label_fa'] : $helanak_visuals['label_en'] )
			: $helanak_cat->name;
		$helanak_emoji_style = sprintf(
			'display:inline-block;animation:%1$s %2$ss ease-in-out %3$ss infinite',
			$helanak_icon_anims[ $helanak_cat_index % 3 ],
			number_format( 2.8 + ( $helanak_cat_index % 4 ) * 0.6, 1, '.', '' ),
			number_format( $helanak_cat_index * 0.25, 2, '.', '' )
		);
		$helanak_icon_style = isset( $helanak_visuals['color_start'], $helanak_visuals['color_end'] )
			? sprintf( '--world-bg:linear-gradient(135deg,%1$s,%2$s)', $helanak_visuals['color_start'], $helanak_visuals['color_end'] )
			: '';
		?>
		<a class="helanak-world-card" data-helanak-category-id="<?php echo esc_attr( $helanak_cat->term_id ); ?>" href="<?php echo esc_url( $helanak_link ); ?>">
			<span class="helanak-world-card__icon" aria-hidden="true"<?php echo $helanak_icon_style ? ' style="' . esc_attr( $helanak_icon_style ) . '"' : ''; ?>>
				<?php if ( ! empty( $helanak_visuals['image_id'] ) ) : ?>
					<?php echo wp_get_attachment_image( $helanak_visuals['image_id'], $helanak_image_size, false, array( 'class' => 'helanak-world-card__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<span style="<?php echo esc_attr( $helanak_emoji_style ); ?>"><?php echo esc_html( $helanak_icon ); ?></span>
				<?php endif; ?>
			</span>
			<span class="helanak-world-card__body">
				<span class="helanak-world-card__name"><?php echo esc_html( $helanak_name ); ?></span>
				<span class="helanak-world-card__count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of products in this category. */
							_n( '%d item', '%d items', $helanak_cat->count, 'wp-helanak-kids-theme' ),
							$helanak_cat->count
						)
					);
					?>
				</span>
			</span>
		</a>
	<?php endforeach; ?>
</div>
