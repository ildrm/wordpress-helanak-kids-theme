<?php
/**
 * Quick View: a lightweight AJAX modal so a shopper can preview a product
 * (image, price, short description, safety/age info, add-to-cart) without
 * leaving the grid they're browsing — gated by the "Quick View" product-card
 * detail toggle in the Customizer (helanak_card_show_quick_view).
 *
 * The trigger button markup lives in woocommerce/content-product.php; this
 * class only serves the modal's inner HTML over admin-ajax.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Quick_View {

	public static function init(): void {
		add_action( 'wp_ajax_helanak_quick_view', array( __CLASS__, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_helanak_quick_view', array( __CLASS__, 'handle_request' ) );
	}

	public static function handle_request(): void {
		check_ajax_referer( 'helanak_quick_view', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) && is_scalar( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'wp-helanak-kids-theme' ) ), 404 );
		}
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || post_password_required( $product_id ) || ! $product->is_visible() ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'wp-helanak-kids-theme' ) ), 404 );
		}

		ob_start();
		self::render_modal_content( $product );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	private static function render_modal_content( WC_Product $product ): void {
		// woocommerce_template_single_add_to_cart() and its type-specific
		// partials read the product from $GLOBALS['product'], not a param.
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

		$badges    = class_exists( 'Helanak_Core_Badge_Engine' ) ? Helanak_Core_Badge_Engine::get_badges( $product ) : array();
		$safety    = class_exists( 'Helanak_Core_Product_Safety_Fields' ) ? Helanak_Core_Product_Safety_Fields::get_safety_data( $product->get_id() ) : null;
		?>
		<div class="helanak-quick-view">
			<div class="helanak-quick-view__media">
				<?php echo $product->get_image( 'helanak-card', array( 'alt' => $product->get_name() ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce generates escaped responsive image markup. ?>
				<?php if ( $badges ) : ?>
					<div class="helanak-quick-view__badges">
						<?php foreach ( $badges as $badge ) : ?>
							<span class="helanak-badge" style="--badge-bg:<?php echo esc_attr( $badge['bg'] ); ?>;--badge-fg:<?php echo esc_attr( $badge['fg'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="helanak-quick-view__details">
				<h2 class="helanak-quick-view__title"><?php echo esc_html( $product->get_name() ); ?></h2>
				<div class="helanak-quick-view__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<div class="helanak-quick-view__excerpt"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php if ( $safety && ( $safety['age_suitability'] || $safety['choking_hazard'] ) ) : ?>
					<div class="helanak-quick-view__safety">
						<?php if ( $safety['age_suitability'] ) : ?>
							<p class="helanak-quick-view__age"><?php echo esc_html( $safety['age_suitability'] ); ?></p>
						<?php endif; ?>
						<?php if ( $safety['choking_hazard'] ) : ?>
							<p class="helanak-quick-view__choking-hazard"><?php echo esc_html( $safety['choking_text'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="helanak-quick-view__cart">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>
				<a class="helanak-quick-view__link" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
					<?php esc_html_e( 'View full details', 'wp-helanak-kids-theme' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
