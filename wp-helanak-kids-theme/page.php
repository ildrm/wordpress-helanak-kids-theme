<?php
/**
 * Generic page template. Critical for the WooCommerce Blocks cart/checkout
 * pages: without a page.php the template hierarchy fell through to
 * index.php, which renders a blog-card grid and never calls the_content() —
 * so the cart/checkout block markup (and its asset enqueues) never reached
 * the browser and the cart appeared permanently empty. Any page with a more
 * specific template (page-about.php, page-contact.php, the Sale template)
 * still wins over this file per the normal hierarchy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">

		<?php
		// The cart→checkout flow gets a mascot greeter + warmer copy so the
		// purchase pages carry the same voice as the rest of the storefront.
		// The order-received endpoint is excluded: it already celebrates via
		// Helanak_Celebration (confetti + waving rabbit).
		$helanak_flow = '';
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			$helanak_flow = 'cart';
		} elseif ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
			$helanak_flow = 'checkout';
		}

		while ( have_posts() ) :
			the_post();
			?>
			<div class="helanak-page-hero helanak-page-hero--compact">
				<?php if ( 'cart' === $helanak_flow ) : ?>
					<div class="helanak-page-hero__mascot">
						<?php helanak_character( 'rabbit', array( 'wave' => true, 'scale' => .5 ) ); ?>
					</div>
				<?php elseif ( 'checkout' === $helanak_flow ) : ?>
					<div class="helanak-page-hero__mascot">
						<?php helanak_character( 'bear', array( 'wave' => true, 'scale' => .5 ) ); ?>
					</div>
				<?php endif; ?>
				<h1><?php the_title(); ?></h1>
				<?php if ( 'cart' === $helanak_flow ) : ?>
					<p><?php esc_html_e( 'Your little treasures are waiting — almost home!', 'wp-helanak-kids-theme' ); ?></p>
				<?php elseif ( 'checkout' === $helanak_flow ) : ?>
					<p><?php esc_html_e( 'Almost there! Just a few details and the fun is on its way.', 'wp-helanak-kids-theme' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="helanak-page-content">
				<?php the_content(); ?>
			</div>
		<?php endwhile; ?>

	</div>
</main>

<?php
get_footer();
