<?php
/**
 * Free-shipping goal bar in the mini-cart drawer: "Add $X more for free
 * shipping" with a real progress bar, or a celebration line once the
 * threshold is met. Reads the actual min_amount of an enabled free_shipping
 * method from the store's shipping zones — renders nothing when no such
 * method is configured, per the theme's no-fabricated-data rule.
 *
 * Hooked to woocommerce_before_mini_cart, which fires inside the
 * .widget_shopping_cart_content wrapper (footer.php), so WooCommerce's own
 * cart-fragments refresh keeps the bar in sync after AJAX add-to-cart.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Shipping_Goal {

	public static function init(): void {
		add_action( 'woocommerce_before_mini_cart', array( __CLASS__, 'render' ) );
	}

	/**
	 * Current package's applicable monetary threshold, or zero when unavailable.
	 * Retained as a compatibility helper; all eligibility rules live in Core.
	 */
	public static function threshold(): float {
		$goal = class_exists( 'Helanak_Core_Shipping_Goal' ) ? Helanak_Core_Shipping_Goal::get_goal() : null;
		return $goal ? (float) $goal['threshold'] : 0.0;
	}

	public static function render(): void {
		$goal = class_exists( 'Helanak_Core_Shipping_Goal' ) ? Helanak_Core_Shipping_Goal::get_goal() : null;
		if ( ! $goal ) {
			return;
		}

		$remaining = $goal['remaining'];
		$pct       = $goal['percent'];
		?>
		<div class="helanak-shipping-goal">
			<?php if ( $remaining > 0 ) : ?>
				<p class="helanak-shipping-goal__label">
					<?php
					printf(
						/* translators: %s: formatted price still needed for free shipping. */
						wp_kses_post( __( 'Add %s more for free shipping 🚚', 'wp-helanak-kids-theme' ) ),
						wp_kses_post( wc_price( $remaining ) )
					);
					?>
				</p>
			<?php else : ?>
				<p class="helanak-shipping-goal__label helanak-shipping-goal__label--reached">
					<?php esc_html_e( "You've unlocked free shipping! 🎉", 'wp-helanak-kids-theme' ); ?>
				</p>
			<?php endif; ?>
			<span class="helanak-shipping-goal__bar" role="progressbar" aria-label="<?php esc_attr_e( 'Free shipping progress', 'wp-helanak-kids-theme' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $pct ); ?>"><span style="width:<?php echo esc_attr( $pct ); ?>%"></span></span>
		</div>
		<?php
	}
}
