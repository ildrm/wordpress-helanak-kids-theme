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

		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_mascot' ), 4 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_safety_panel' ), 25 );
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

	public static function render_mascot(): void {
		helanak_character(
			'monkey',
			array(
				'scale'    => 1,
				'behavior' => 'idle',
				'class'    => 'helanak-character--buybox-peek',
			)
		);
	}
}
