<?php
/**
 * Shop / category / tag / attribute / age-range archive. Shows a
 * category-skin-aware hero when browsing a product_cat term
 * (Helanak_Core_Category_Theme), a simpler hero for age_range terms, and
 * falls back to a plain (visually hidden) title otherwise. The filter
 * sidebar's age/color selections are applied in
 * inc/class-archive-filters.php; price filtering is handled natively by
 * WooCommerce's own WC_Query.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$helanak_term  = is_product_taxonomy() ? get_queried_object() : null;
$helanak_visuals = ( $helanak_term instanceof WP_Term && 'product_cat' === $helanak_term->taxonomy && class_exists( 'Helanak_Core_Category_Theme' ) )
	? Helanak_Core_Category_Theme::get_visuals_for_term( $helanak_term )
	: null;
$helanak_is_fa = 'fa' === helanak_current_lang();

$helanak_age_terms = taxonomy_exists( 'age_range' )
	? get_terms( array( 'taxonomy' => 'age_range', 'hide_empty' => false ) )
	: array();
$helanak_color_terms = taxonomy_exists( 'pa_color' )
	? get_terms( array( 'taxonomy' => 'pa_color', 'hide_empty' => true ) )
	: array();

$helanak_selected_ages   = isset( $_GET['helanak_age'] ) ? array_map( 'sanitize_title', array_slice( array_filter( (array) wp_unslash( $_GET['helanak_age'] ), 'is_scalar' ), 0, 100 ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
$helanak_selected_colors = isset( $_GET['helanak_color'] ) ? array_map( 'sanitize_title', array_slice( array_filter( (array) wp_unslash( $_GET['helanak_color'] ), 'is_scalar' ), 0, 100 ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
$helanak_filter_url      = remove_query_arg( array( 'helanak_age', 'helanak_color', 'min_price', 'max_price', 'paged', 'page' ), get_pagenum_link( 1, false ) );
$helanak_clear_url       = $helanak_filter_url;
$helanak_is_wishlist     = isset( $_GET['helanak_wishlist'] ); // phpcs:ignore WordPress.Security.NonceVerification
$helanak_empty_wishlist  = $helanak_is_wishlist && is_scalar( $_GET['helanak_wishlist'] ) && '0' === trim( (string) wp_unslash( $_GET['helanak_wishlist'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">
		<?php
		// Rendered once, placed INSIDE the hero panel when one exists (per the
		// prototype, where the breadcrumb sits within the category banner).
		ob_start();
		woocommerce_breadcrumb(
			array(
				'wrap_before' => '<nav class="helanak-breadcrumb woocommerce-breadcrumb">',
				'wrap_after'  => '</nav>',
			)
		);
		$helanak_breadcrumb = ob_get_clean();
		?>

		<?php if ( $helanak_visuals ) : ?>
			<div
				class="helanak-category-hero"
				style="background:linear-gradient(135deg,<?php echo esc_attr( $helanak_visuals['color_start'] ); ?>,<?php echo esc_attr( $helanak_visuals['color_end'] ); ?>)"
			>
				<span class="helanak-category-hero__icon" aria-hidden="true">
					<?php if ( $helanak_visuals['image_id'] ) : ?>
						<?php echo wp_get_attachment_image( $helanak_visuals['image_id'], 'medium', false, array( 'class' => 'helanak-category-hero__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php else : ?>
						<?php echo esc_html( $helanak_visuals['icon'] ); ?>
					<?php endif; ?>
				</span>
				<div>
					<?php echo $helanak_breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput -- woocommerce_breadcrumb() output, pre-escaped by WooCommerce. ?>
					<h1><?php echo esc_html( $helanak_is_fa ? $helanak_visuals['label_fa'] : $helanak_visuals['label_en'] ); ?></h1>
					<?php if ( $helanak_term->description ) : ?>
						<p><?php echo wp_kses_post( wpautop( $helanak_term->description ) ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php elseif ( $helanak_term instanceof WP_Term && 'age_range' === $helanak_term->taxonomy ) : ?>
			<?php
			$helanak_age_visuals = class_exists( 'Helanak_Core_Age_Taxonomy' )
				? Helanak_Core_Age_Taxonomy::get_display_data( $helanak_term, helanak_current_lang() )
				: array( 'icon' => '🍼', 'color_start' => '#F3E8CF', 'color_end' => '#FAF1DE' );
			?>
			<div class="helanak-category-hero" style="background:linear-gradient(135deg,<?php echo esc_attr( $helanak_age_visuals['color_start'] ?: '#F3E8CF' ); ?>,<?php echo esc_attr( $helanak_age_visuals['color_end'] ?: '#FAF1DE' ); ?>)">
				<span class="helanak-category-hero__icon" aria-hidden="true"><?php echo esc_html( $helanak_age_visuals['icon'] ?: '🍼' ); ?></span>
				<div>
					<?php echo $helanak_breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput -- woocommerce_breadcrumb() output, pre-escaped by WooCommerce. ?>
					<h1><?php echo esc_html( helanak_age_label( $helanak_term ) ); ?></h1>
				</div>
			</div>
		<?php else : ?>
			<?php echo $helanak_breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput -- woocommerce_breadcrumb() output, pre-escaped by WooCommerce. ?>
			<h1 class="helanak-archive__title"><?php echo esc_html( $helanak_is_wishlist ? __( 'Wishlist', 'wp-helanak-kids-theme' ) : woocommerce_page_title( false ) ); ?></h1>
		<?php endif; ?>

		<div class="helanak-archive__layout">
			<aside class="helanak-filters">
				<button type="button" class="helanak-filter-toggle" data-helanak-filter-toggle aria-expanded="true" aria-controls="helanak-filter-form"><?php esc_html_e( 'Filters', 'wp-helanak-kids-theme' ); ?></button>
				<form id="helanak-filter-form" method="get" action="<?php echo esc_url( $helanak_filter_url ); ?>">
					<?php wc_query_string_form_fields( null, array( 'helanak_age', 'helanak_color', 'min_price', 'max_price', 'paged', 'page' ) ); ?>
					<div class="helanak-filters__head">
						<span><?php esc_html_e( 'Filters', 'wp-helanak-kids-theme' ); ?></span>
						<a href="<?php echo esc_url( $helanak_clear_url ); ?>"><?php esc_html_e( 'Clear', 'wp-helanak-kids-theme' ); ?></a>
					</div>

					<?php if ( $helanak_age_terms && ! is_wp_error( $helanak_age_terms ) ) : ?>
						<fieldset>
							<legend><?php esc_html_e( 'Age', 'wp-helanak-kids-theme' ); ?></legend>
							<div class="helanak-filters__chips">
								<?php foreach ( $helanak_age_terms as $helanak_age_term ) : ?>
									<label>
										<input
											type="checkbox"
											name="helanak_age[]"
											value="<?php echo esc_attr( $helanak_age_term->slug ); ?>"
											<?php checked( in_array( $helanak_age_term->slug, $helanak_selected_ages, true ) ); ?>
										/>
										<?php echo esc_html( helanak_age_label( $helanak_age_term ) ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endif; ?>

					<fieldset>
						<legend><?php esc_html_e( 'Price', 'wp-helanak-kids-theme' ); ?></legend>
						<div class="helanak-price-fields">
							<label for="helanak-min-price"><?php esc_html_e( 'Min', 'wp-helanak-kids-theme' ); ?><input id="helanak-min-price" type="number" name="min_price" min="0" step="any" inputmode="decimal" value="<?php echo esc_attr( isset( $_GET['min_price'] ) && is_scalar( $_GET['min_price'] ) ? wc_format_decimal( wp_unslash( $_GET['min_price'] ) ) : '' ); ?>"></label>
							<label for="helanak-max-price"><?php esc_html_e( 'Max', 'wp-helanak-kids-theme' ); ?><input id="helanak-max-price" type="number" name="max_price" min="0" step="any" inputmode="decimal" value="<?php echo esc_attr( isset( $_GET['max_price'] ) && is_scalar( $_GET['max_price'] ) ? wc_format_decimal( wp_unslash( $_GET['max_price'] ) ) : '' ); ?>"></label>
						</div>
					</fieldset>

					<?php if ( $helanak_color_terms && ! is_wp_error( $helanak_color_terms ) ) : ?>
						<fieldset>
							<legend><?php esc_html_e( 'Color', 'wp-helanak-kids-theme' ); ?></legend>
							<div class="helanak-filters__swatches">
								<?php foreach ( $helanak_color_terms as $helanak_color_term ) : ?>
									<label title="<?php echo esc_attr( $helanak_color_term->name ); ?>">
										<span><?php echo esc_html( $helanak_color_term->name ); ?></span>
										<input
											type="checkbox"
											name="helanak_color[]"
											value="<?php echo esc_attr( $helanak_color_term->slug ); ?>"
											<?php checked( in_array( $helanak_color_term->slug, $helanak_selected_colors, true ) ); ?>
										/>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endif; ?>

					<button type="submit" class="helanak-btn helanak-btn--primary helanak-btn--block">
						<?php esc_html_e( 'Apply filters', 'wp-helanak-kids-theme' ); ?>
					</button>
				</form>
			</aside>

			<div class="helanak-archive__grid">
				<div class="helanak-archive__toolbar">
					<?php woocommerce_result_count(); ?>
					<?php woocommerce_catalog_ordering(); ?>
				</div>

				<?php if ( woocommerce_product_loop() ) : ?>
					<ul class="helanak-products">
						<?php
						while ( have_posts() ) :
							the_post();
							wc_get_template_part( 'content', 'product' );
						endwhile;
						?>
					</ul>
					<?php woocommerce_pagination(); ?>
				<?php else : ?>
					<div class="helanak-empty-state">
						<?php helanak_character( 'penguin', array( 'scale' => .7 ) ); ?>
						<p><?php echo esc_html( $helanak_empty_wishlist ? __( 'Your wishlist is waiting for a little inspiration.', 'wp-helanak-kids-theme' ) : __( 'No products were found matching your selection.', 'wp-helanak-kids-theme' ) ); ?></p>
						<a class="helanak-btn helanak-btn--outline" href="<?php echo esc_url( $helanak_empty_wishlist ? wc_get_page_permalink( 'shop' ) : $helanak_clear_url ); ?>">
							<?php echo esc_html( $helanak_empty_wishlist ? __( 'Explore the shop', 'wp-helanak-kids-theme' ) : __( 'Clear filters', 'wp-helanak-kids-theme' ) ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
