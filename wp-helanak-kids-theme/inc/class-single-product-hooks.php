<?php
/**
 * Hooked additions to WooCommerce's default single-product template — not a
 * single-product.php override, so gallery/variations/upsells/reviews stay
 * fully stock. Adds a richer "Safety & Age" panel (age_range term pills +
 * Helanak_Core_Product_Safety_Fields data) in place of the plugin's own
 * fallback notice, plus a decorative peeking monkey mascot near the buy box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Single_Product_Hooks {

	public static function init(): void {
		if ( class_exists( 'Helanak_Core_Product_Safety_Fields' ) ) {
			remove_action( 'woocommerce_product_meta_end', array( 'Helanak_Core_Product_Safety_Fields', 'render_frontend_notice' ) );
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_price' ), 10 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_safety_panel' ), 25 );
		add_action( 'woocommerce_before_variations_form', array( __CLASS__, 'render_variation_images' ) );
	}

	/** Image-only data also supports products whose variations load over AJAX. */
	public static function render_variation_images(): void {
		global $product;
		if ( ! is_product() || ! $product instanceof WC_Product_Variable ) {
			return;
		}

		$children = $product->get_children();
		_prime_post_caches( $children );
		$choices = array();
		$images  = array();
		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation || ! $variation->variation_is_visible() ) {
				continue;
			}
			$image_id = (int) $variation->get_image_id( 'edit' );
			if ( ! $image_id ) {
				$gallery_ids = $variation->get_gallery_image_ids( 'edit' );
				$image_id    = (int) ( $gallery_ids[0] ?? 0 );
			}
			if ( $image_id && ! wp_attachment_is_image( $image_id ) ) {
				$image_id = 0;
			}
			$choices[] = array(
				'attributes' => $variation->get_variation_attributes(),
				'image_id'   => $image_id,
			);
			if ( $image_id && ! isset( $images[ $image_id ] ) ) {
				$images[ $image_id ] = wc_get_product_attachment_props( $image_id );
			}
		}
		if ( ! $images ) {
			return;
		}
		echo '<script type="application/json" class="helanak-variation-images">';
		echo wp_json_encode( array( 'variations' => $choices, 'images' => $images ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is encoded for a script text node.
		echo '</script>';
	}

	public static function render_safety_panel(): void {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$age_terms = get_the_terms( $product->get_id(), 'age_range' );
		$has_ages  = is_array( $age_terms ) && ! empty( $age_terms );

		$data = class_exists( 'Helanak_Core_Product_Safety_Fields' )
			? Helanak_Core_Product_Safety_Fields::get_safety_data( $product->get_id() )
			: array(
				'age_suitability' => '',
				'choking_hazard'  => false,
				'choking_text'    => '',
				'certification'   => '',
			);

		if ( ! $has_ages && ! $data['age_suitability'] && ! $data['choking_hazard'] && ! $data['certification'] ) {
			return;
		}
		?>
		<div class="helanak-trust-panel helanak-safety-panel">
			<h3><?php esc_html_e( 'Safety & Age', 'wp-helanak-kids-theme' ); ?></h3>

			<?php if ( $has_ages ) : ?>
				<div class="helanak-age-pills">
					<?php foreach ( $age_terms as $helanak_age_term ) : ?>
						<span><?php echo esc_html( helanak_age_label( $helanak_age_term ) ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $data['age_suitability'] ) : ?>
				<p class="helanak-age-suitability"><?php echo esc_html( $data['age_suitability'] ); ?></p>
			<?php endif; ?>

			<?php if ( $data['choking_hazard'] ) : ?>
				<p class="helanak-choking-hazard">⚠ <?php echo esc_html( $data['choking_text'] ); ?></p>
			<?php endif; ?>

			<?php if ( $data['certification'] ) : ?>
				<p class="helanak-certification">🛡 <?php echo esc_html( $data['certification'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function render_price(): void {
		// Keep WooCommerce's price output and reserve a separate mobile mascot column.
		echo '<div class="helanak-product-price-row">';
		woocommerce_template_single_price();
		helanak_character(
			'monkey',
			array(
				'scale'    => 1,
				'behavior' => 'idle',
				'class'    => 'helanak-character--buybox-peek',
			)
		);
		echo '</div>';
	}
}
