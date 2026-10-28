<?php
/** Footer, preserved animal companions and shared cart dialog. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="helanak-site-footer">
	<div class="helanak-footer-companions" data-helanak-flyby-stage aria-hidden="true">
		<?php
		helanak_character( 'cat', array( 'scale' => .55, 'behavior' => 'sleep', 'class' => 'helanak-character--footer-peek helanak-footer-cat--awake' ) );
		helanak_character( 'cat', array( 'scale' => .55, 'awake' => false, 'behavior' => 'sleep', 'class' => 'helanak-character--footer-peek helanak-footer-cat--asleep' ) );
		helanak_character( 'bird', array( 'scale' => 1.4, 'behavior' => 'fly' ) );
		?>
	</div>
	<div class="helanak-container">
		<nav class="helanak-nav helanak-nav--footer" aria-label="<?php esc_attr_e( 'Footer', 'wp-helanak-kids-theme' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_id' => 'helanak-footer-menu', 'menu_class' => 'helanak-menu', 'fallback_cb' => 'helanak_primary_menu_fallback' ) ); ?></nav>
		<button type="button" class="helanak-motion-toggle" data-helanak-motion-toggle aria-pressed="false"><span aria-hidden="true">Ⅱ</span> <span data-helanak-motion-label><?php esc_html_e( 'Pause animations', 'wp-helanak-kids-theme' ); ?></span></button>
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'wp-helanak-kids-theme' ); ?></p>
	</div>
</footer>
<?php if ( function_exists( 'WC' ) ) : ?>
	<button type="button" class="helanak-mini-cart-button" data-helanak-section="mini_cart" data-helanak-cart-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="helanak-mini-cart-drawer" aria-label="<?php esc_attr_e( 'Open cart', 'wp-helanak-kids-theme' ); ?>"<?php helanak_section_hidden_attr( 'mini_cart' ); ?>><svg viewBox="0 0 24 24" width="25" height="25" aria-hidden="true"><path d="M5 8h14l1 12H4L5 8Zm3 0V6a4 4 0 0 1 8 0v2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg><?php echo helanak_cart_count_badge(); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped markup. ?></button>
	<div id="helanak-mini-cart-drawer" class="helanak-cart-modal" data-helanak-cart-drawer hidden role="dialog" aria-modal="true" aria-labelledby="helanak-cart-title" tabindex="-1">
		<div class="helanak-cart-backdrop" data-helanak-cart-close aria-hidden="true"></div>
		<div class="helanak-mini-cart-drawer">
			<div class="helanak-mini-cart-drawer__head">
				<h2 id="helanak-cart-title" class="helanak-mini-cart-drawer__title"><?php esc_html_e( 'Your cart', 'wp-helanak-kids-theme' ); ?></h2>
				<?php helanak_character( 'rabbit', array( 'wave' => true, 'scale' => .3 ) ); ?>
				<button type="button" class="helanak-mini-cart-drawer__close" data-helanak-cart-close aria-label="<?php esc_attr_e( 'Close', 'wp-helanak-kids-theme' ); ?>"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="helanak-mini-cart-drawer__empty">
				<?php helanak_character( 'cat', array( 'awake' => false, 'scale' => .5 ) ); ?>
				<p><?php esc_html_e( 'Your cart is napping — nothing in it yet.', 'wp-helanak-kids-theme' ); ?></p>
				<a class="helanak-btn helanak-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Start shopping', 'wp-helanak-kids-theme' ); ?></a>
			</div>
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		</div>
	</div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
