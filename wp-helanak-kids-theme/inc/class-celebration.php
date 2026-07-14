<?php
/**
 * Order-received celebration: confetti + a waving rabbit on the order
 * "thank you" page, porting the prototype's place-order celebration
 * (confettiLayer + celebrateRabbit in frontend/page-cart-checkout.dc.html).
 * Checkout is WooCommerce Blocks on this site, and the Blocks order
 * confirmation never fires the classic woocommerce_thankyou hook — so this
 * hangs off wp_footer gated by is_order_received_page(), which is true for
 * the order-received endpoint under both the Blocks and classic checkouts.
 * Both pieces are fixed-position overlays, so footer placement is fine.
 *
 * The 26 confetti pieces mirror the prototype's generator: seasonal palette
 * (--primary/--secondary/--accent-color + white), alternating circles and
 * rounded squares, staggered scconf falls. Pure CSS animation — runs once on
 * load, killed by the global prefers-reduced-motion / data-motion switches.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Celebration {

	public static function init(): void {
		add_action( 'wp_footer', array( __CLASS__, 'maybe_render' ), 5 );
	}

	public static function maybe_render(): void {
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
			return;
		}
		self::render();
	}

	public static function render(): void {
		$colors = array( 'var(--primary-color)', 'var(--secondary-color)', 'var(--accent-color)', '#fff' );

		echo '<div class="helanak-confetti" aria-hidden="true">';
		for ( $i = 0; $i < 26; $i++ ) {
			$style = sprintf(
				'left:%1$s%%;background:%2$s;border-radius:%3$s;width:%4$dpx;height:%5$dpx;animation:scconf %6$ss ease-in %7$ss both',
				number_format( ( $i * 3.9 ) + ( ( $i % 3 ) * 1.3 ), 1, '.', '' ),
				$colors[ $i % 4 ],
				( 0 === $i % 2 ) ? '50%' : '3px',
				7 + ( $i % 3 ) * 2,
				9 + ( $i % 4 ) * 2,
				number_format( 2.4 + ( $i % 5 ) * 0.4, 1, '.', '' ),
				number_format( ( $i % 7 ) * 0.15, 2, '.', '' )
			);
			printf( '<span style="%s"></span>', esc_attr( $style ) );
		}
		echo '</div>';

		echo '<div class="helanak-celebrate-mascot">';
		helanak_character(
			'rabbit',
			array(
				'wave'  => true,
				'scale' => .5,
			)
		);
		echo '</div>';
	}
}
