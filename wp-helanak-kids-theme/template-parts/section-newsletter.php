<?php
/**
 * Newsletter signup: posts to admin-post.php, handled by Helanak_Newsletter
 * (inc/class-newsletter.php). Real validation + persistence, no ESP
 * integration baked in — that's a per-site choice left for later.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_newsletter_status = isset( $_GET['helanak_newsletter'] ) ? sanitize_key( wp_unslash( $_GET['helanak_newsletter'] ) ) : '';
?>
<section class="helanak-section" id="helanak-newsletter" data-helanak-section="newsletter"<?php helanak_section_hidden_attr( 'newsletter' ); ?>>
	<div class="helanak-container">
		<div class="helanak-newsletter">
			<h2><?php esc_html_e( 'Join the helanak club', 'wp-helanak-kids-theme' ); ?></h2>
			<p><?php esc_html_e( 'Seasonal guides, birthday gifts, and 10% off your first order.', 'wp-helanak-kids-theme' ); ?></p>

			<?php if ( 'success' === $helanak_newsletter_status ) : ?>
				<p role="status"><?php esc_html_e( "You're subscribed! Thanks for joining us.", 'wp-helanak-kids-theme' ); ?></p>
			<?php elseif ( 'invalid' === $helanak_newsletter_status ) : ?>
				<p role="alert"><?php esc_html_e( 'Please enter a valid email address.', 'wp-helanak-kids-theme' ); ?></p>
			<?php endif; ?>

			<form class="helanak-newsletter__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="helanak_newsletter_subscribe" />
				<?php wp_nonce_field( 'helanak_newsletter_subscribe' ); ?>
				<label class="screen-reader-text" for="helanak-newsletter-email"><?php esc_html_e( 'Email address', 'wp-helanak-kids-theme' ); ?></label>
				<input
					id="helanak-newsletter-email"
					type="email"
					name="helanak_email"
					required
					placeholder="<?php esc_attr_e( 'you@example.com', 'wp-helanak-kids-theme' ); ?>"
				/>
				<button type="submit" class="helanak-btn helanak-btn--primary"><?php esc_html_e( 'Subscribe', 'wp-helanak-kids-theme' ); ?></button>
			</form>
		</div>
	</div>
</section>
