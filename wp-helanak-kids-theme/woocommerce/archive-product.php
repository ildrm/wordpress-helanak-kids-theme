<?php
/**
 * Shop / category / tag / attribute / age-range archive. Shows a
 * category-skin-aware hero when browsing a product_cat term
 * (Helanak_Core_Category_Theme), a simpler hero for age_range terms, and
 * falls back to a plain (visually hidden) title otherwise. The filter
 * sidebar renders the populated filter registry owned by Helanak Core;
 * WooCommerce handles native price and global-attribute filtering.
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

$helanak_has_catalog = class_exists( 'Helanak_Core_Catalog_Filters' );
$helanak_groups      = $helanak_has_catalog ? Helanak_Core_Catalog_Filters::groups() : array();
$helanak_managed     = $helanak_has_catalog ? Helanak_Core_Catalog_Filters::request_keys() : array( 's', 'helanak_age', 'helanak_color', 'min_price', 'max_price' );
$helanak_values      = array();
foreach ( array( 's', 'min_price', 'max_price', 'helanak_stock', 'helanak_sale' ) as $helanak_key ) {
	$helanak_values[ $helanak_key ] = $helanak_has_catalog
		? Helanak_Core_Catalog_Filters::value( $helanak_key )
		: ( isset( $_GET[ $helanak_key ] ) && is_scalar( $_GET[ $helanak_key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $helanak_key ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
}
if ( ! $helanak_has_catalog ) {
	foreach ( array( 'helanak_age' => 'age_range', 'helanak_color' => 'pa_color' ) as $helanak_key => $helanak_taxonomy ) {
		$helanak_terms = taxonomy_exists( $helanak_taxonomy ) ? get_terms( array( 'taxonomy' => $helanak_taxonomy, 'hide_empty' => true ) ) : array();
		if ( $helanak_terms && ! is_wp_error( $helanak_terms ) ) {
			$helanak_groups[] = array( 'key' => $helanak_key, 'taxonomy' => $helanak_taxonomy, 'label' => 'age_range' === $helanak_taxonomy ? __( 'Age', 'wp-helanak-kids-theme' ) : __( 'Color', 'wp-helanak-kids-theme' ), 'terms' => $helanak_terms );
		}
	}
}
$helanak_excluded        = array_unique( array_merge( $helanak_managed, array( 'paged', 'page', 'post_type' ) ) );
$helanak_filter_url      = remove_query_arg( $helanak_excluded, get_pagenum_link( 1, false ) );
$helanak_clear_url       = add_query_arg( 'post_type', 'product', $helanak_filter_url );
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

		<div class="helanak-shop-search" role="search" aria-labelledby="helanak-shop-search-label">
			<label id="helanak-shop-search-label" for="helanak-shop-search"><?php esc_html_e( 'Search product names', 'wp-helanak-kids-theme' ); ?></label>
			<div class="helanak-shop-search__controls">
				<input id="helanak-shop-search" form="helanak-filter-form" type="search" name="s" value="<?php echo esc_attr( $helanak_values['s'] ); ?>" placeholder="<?php esc_attr_e( 'Enter a product name', 'wp-helanak-kids-theme' ); ?>">
				<button type="submit" form="helanak-filter-form" class="helanak-btn helanak-btn--primary"><?php esc_html_e( 'Search', 'wp-helanak-kids-theme' ); ?></button>
			</div>
		</div>
		<div class="helanak-archive__layout">
			<aside class="helanak-filters" aria-label="<?php esc_attr_e( 'Product filters', 'wp-helanak-kids-theme' ); ?>">
				<button type="button" class="helanak-filter-toggle" data-helanak-filter-toggle aria-expanded="true" aria-controls="helanak-filter-form"><?php esc_html_e( 'Filters', 'wp-helanak-kids-theme' ); ?></button>
				<form id="helanak-filter-form" method="get" action="<?php echo esc_url( $helanak_filter_url ); ?>">
					<?php wc_query_string_form_fields( null, $helanak_excluded ); ?>
					<input type="hidden" name="post_type" value="product">
					<div class="helanak-filters__head">
						<span><?php esc_html_e( 'Filters', 'wp-helanak-kids-theme' ); ?></span>
						<a href="<?php echo esc_url( $helanak_clear_url ); ?>"><?php esc_html_e( 'Clear', 'wp-helanak-kids-theme' ); ?></a>
					</div>
					<div class="helanak-filters__scroll" role="region" aria-label="<?php esc_attr_e( 'Product filters', 'wp-helanak-kids-theme' ); ?>" tabindex="0">
						<?php foreach ( $helanak_groups as $helanak_index => $helanak_group ) : ?>
							<?php
							$helanak_selected = $helanak_has_catalog ? Helanak_Core_Catalog_Filters::selected( $helanak_group['key'] ) : ( isset( $_GET[ $helanak_group['key'] ] ) ? array_map( 'sanitize_title', array_slice( array_filter( (array) wp_unslash( $_GET[ $helanak_group['key'] ] ), 'is_scalar' ), 0, 100 ) ) : array() ); // phpcs:ignore WordPress.Security.NonceVerification
							?>
							<details class="helanak-filter-group" <?php echo ( 0 === $helanak_index || $helanak_selected ) ? 'open' : ''; ?>>
								<summary><?php echo esc_html( $helanak_group['label'] ); ?><?php if ( $helanak_selected ) : ?> <span class="helanak-filter-group__count">(<?php echo esc_html( count( $helanak_selected ) ); ?>)</span><?php endif; ?></summary>
								<fieldset>
									<legend class="screen-reader-text"><?php echo esc_html( $helanak_group['label'] ); ?></legend>
									<div class="helanak-filter-options">
										<?php foreach ( $helanak_group['terms'] as $helanak_option ) : ?>
											<label><input type="checkbox" name="<?php echo esc_attr( $helanak_group['key'] ); ?>[]" value="<?php echo esc_attr( $helanak_option->slug ); ?>" <?php checked( in_array( $helanak_option->slug, $helanak_selected, true ) ); ?>><span><?php echo esc_html( 'age_range' === $helanak_group['taxonomy'] ? helanak_age_label( $helanak_option ) : $helanak_option->name ); ?></span></label>
										<?php endforeach; ?>
									</div>
								</fieldset>
							</details>
						<?php endforeach; ?>
						<details class="helanak-filter-group helanak-filter-price" <?php echo ( '' !== $helanak_values['min_price'] || '' !== $helanak_values['max_price'] ) ? 'open' : ''; ?>>
							<summary><?php esc_html_e( 'Price', 'wp-helanak-kids-theme' ); ?> <small>(<?php echo esc_html( get_woocommerce_currency() ); ?>)</small></summary>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Price', 'wp-helanak-kids-theme' ); ?></legend>
								<div class="helanak-price-fields">
									<label for="helanak-min-price"><?php esc_html_e( 'Min', 'wp-helanak-kids-theme' ); ?><input id="helanak-min-price" type="number" name="min_price" min="0" step="any" inputmode="decimal" value="<?php echo esc_attr( $helanak_values['min_price'] ); ?>"></label>
									<label for="helanak-max-price"><?php esc_html_e( 'Max', 'wp-helanak-kids-theme' ); ?><input id="helanak-max-price" type="number" name="max_price" min="0" step="any" inputmode="decimal" value="<?php echo esc_attr( $helanak_values['max_price'] ); ?>"></label>
								</div>
							</fieldset>
						</details>
						<?php if ( $helanak_has_catalog ) : ?>
							<details class="helanak-filter-group helanak-filter-availability" <?php echo ( $helanak_values['helanak_stock'] || $helanak_values['helanak_sale'] ) ? 'open' : ''; ?>>
								<summary><?php esc_html_e( 'Availability and offers', 'wp-helanak-kids-theme' ); ?></summary>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Availability and offers', 'wp-helanak-kids-theme' ); ?></legend>
									<div class="helanak-filter-options">
										<label><input type="checkbox" name="helanak_stock" value="instock" <?php checked( 'instock', $helanak_values['helanak_stock'] ); ?>><span><?php esc_html_e( 'Only in-stock products', 'wp-helanak-kids-theme' ); ?></span></label>
										<label><input type="checkbox" name="helanak_sale" value="1" <?php checked( '1', $helanak_values['helanak_sale'] ); ?>><span><?php esc_html_e( 'Discounted products', 'wp-helanak-kids-theme' ); ?></span></label>
									</div>
								</fieldset>
							</details>
						<?php endif; ?>
					</div>
					<div class="helanak-filters__actions"><button type="submit" class="helanak-btn helanak-btn--primary helanak-btn--block"><?php esc_html_e( 'Apply filters', 'wp-helanak-kids-theme' ); ?></button></div>
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
