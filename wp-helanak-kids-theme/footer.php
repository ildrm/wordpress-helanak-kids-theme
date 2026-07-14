<?php
/**
 * Site footer: footer nav + copyright, sleeping cat mascot peek, floating
 * mini-cart button + drawer (data-helanak-section="mini_cart"), wp_footer().
 *
 * The drawer wraps woocommerce_mini_cart() in .widget_shopping_cart_content
 * so WooCommerce's own wc-cart-fragments script (enqueued automatically on
 * the frontend) keeps its contents in sync after add-to-cart, with no extra
 * AJAX wiring needed here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

	<footer class="helanak-site-footer">
		<?php
		// Footer cat pair: awake and asleep are two different server-rendered
		// art structures (blinking eyes vs closed-eye arcs + z glyphs), so
		// both render at the same spot and theme.js's idle timer swaps them —
		// awake while the visitor interacts, asleep after 30s of inactivity
		// (both carry data-behavior="sleep" so the existing sleeper query
		// toggles .is-sleeping on the pair together).
		helanak_character(
			'cat',
			array(
				'scale'    => .55,
				'behavior' => 'sleep',
				'class'    => 'helanak-character--footer-peek helanak-footer-cat--awake',
			)
		);
		helanak_character(
			'cat',
			array(
				'scale'    => .55,
				'awake'    => false,
				'behavior' => 'sleep',
				'class'    => 'helanak-character--footer-peek helanak-footer-cat--asleep',
			)
		);

		// Dedicated flyby bird: hidden/fixed until theme.js adds .is-flying on
		// scroll (parade/static birds render inline and never fly).
		helanak_character(
			'bird',
			array(
				'scale'    => 1.4,
				'behavior' => 'fly',
			)
		);
		?>
		<div class="helanak-container">
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav class="helanak-nav" aria-label="<?php esc_attr_e( 'Footer', 'wp-helanak-kids-theme' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'items_wrap'     => '%3$s',
							'fallback_cb'    => false,
						)
					);
					?>
				</nav>
			<?php endif; ?>
			<p>
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.
				<?php esc_html_e( 'All rights reserved.', 'wp-helanak-kids-theme' ); ?>
			</p>
		</div>
	</footer>

	<?php if ( function_exists( 'WC' ) ) : ?>
		<button
			type="button"
			class="helanak-mini-cart-button"
			data-helanak-section="mini_cart"
			data-helanak-cart-toggle
			aria-haspopup="true"
			aria-expanded="false"
			aria-controls="helanak-mini-cart-drawer"
			aria-label="<?php esc_attr_e( 'Open cart', 'wp-helanak-kids-theme' ); ?>"
			<?php helanak_section_hidden_attr( 'mini_cart' ); ?>
		>
			<span aria-hidden="true">🛍️</span>
			<?php echo helanak_cart_count_badge(); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped markup. ?>
		</button>

		<div
			id="helanak-mini-cart-drawer"
			class="helanak-mini-cart-drawer"
			data-helanak-cart-drawer
			hidden
			role="dialog"
			aria-modal="true"
			aria-label="<?php esc_attr_e( 'Shopping cart', 'wp-helanak-kids-theme' ); ?>"
		>
			<div class="helanak-mini-cart-drawer__head">
				<span class="helanak-mini-cart-drawer__title">🛒 <?php esc_html_e( 'Your cart', 'wp-helanak-kids-theme' ); ?></span>
				<?php
				// The prototype's cartRabbit: a little waving rabbit greeting
				// the shopper from the drawer header.
				helanak_character(
					'rabbit',
					array(
						'wave'  => true,
						'scale' => .3,
					)
				);
				?>
				<button
					type="button"
					class="helanak-mini-cart-drawer__close"
					data-helanak-cart-close
					aria-label="<?php esc_attr_e( 'Close', 'wp-helanak-kids-theme' ); ?>"
				>
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<?php
			// Shown by CSS only while the drawer contains WooCommerce's
			// empty-cart message (:has()), so it stays in sync with the
			// cart-fragments AJAX refresh with no JS of its own.
			?>
			<div class="helanak-mini-cart-drawer__empty">
				<?php helanak_character( 'cat', array( 'awake' => false, 'scale' => .5 ) ); ?>
				<p><?php esc_html_e( 'Your cart is napping — nothing in it yet.', 'wp-helanak-kids-theme' ); ?></p>
				<a class="helanak-btn helanak-btn--primary" href="<?php echo esc_url( function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Start shopping', 'wp-helanak-kids-theme' ); ?>
				</a>
			</div>
			<div class="widget_shopping_cart_content">
				<?php woocommerce_mini_cart(); ?>
			</div>
		</div>
	<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
