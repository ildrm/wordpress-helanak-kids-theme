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
	 * Lowest min_amount among enabled free_shipping methods across all zones
	 * (incl. the "rest of the world" zone 0), or 0.0 when none is configured.
	 */
	public static function threshold(): float {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return 0.0;
		}

		$zones   = WC_Shipping_Zones::get_zones();
		$zones[] = array( 'zone_id' => 0 );

		$min = 0.0;
		foreach ( $zones as $zone_data ) {
			$zone = WC_Shipping_Zones::get_zone( (int) $zone_data['zone_id'] );
			if ( ! $zone ) {
				continue;
			}
			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( 'free_shipping' !== $method->id ) {
					continue;
				}
				$amount = isset( $method->min_amount ) ? (float) $method->min_amount : 0.0;
				if ( $amount > 0 && ( 0.0 === $min || $amount < $min ) ) {
					$min = $amount;
				}
			}
		}
		return $min;
	}

	public static function render(): void {
		$threshold = self::threshold();
		if ( $threshold <= 0 || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$subtotal  = (float) WC()->cart->get_displayed_subtotal();
		$remaining = $threshold - $subtotal;
		$pct       = min( 100, (int) round( ( $subtotal / $threshold ) * 100 ) );
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
			<span class="helanak-shipping-goal__bar"><span style="width:<?php echo esc_attr( $pct ); ?>%"></span></span>
		</div>
		<?php
	}
}
