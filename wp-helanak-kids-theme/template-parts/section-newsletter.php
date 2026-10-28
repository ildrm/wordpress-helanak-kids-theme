<?php
/** Newsletter presentation; capture and abuse protection belong to Core. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$helanak_newsletter_status = isset( $_GET['helanak_newsletter'] ) ? sanitize_key( wp_unslash( $_GET['helanak_newsletter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<section class="helanak-section" id="helanak-newsletter" data-helanak-section="newsletter"<?php helanak_section_hidden_attr( 'newsletter' ); ?>>
	<div class="helanak-container"><div class="helanak-newsletter">
		<h2><?php esc_html_e( 'Join the helanak club', 'wp-helanak-kids-theme' ); ?></h2><p><?php esc_html_e( 'New arrivals and little discoveries, delivered to your inbox.', 'wp-helanak-kids-theme' ); ?></p>
		<?php if ( 'success' === $helanak_newsletter_status ) : ?><p class="helanak-form-feedback helanak-form-feedback--success" role="status"><?php esc_html_e( "You're subscribed! Thanks for joining us.", 'wp-helanak-kids-theme' ); ?></p>
		<?php elseif ( 'invalid' === $helanak_newsletter_status ) : ?><p class="helanak-form-feedback" role="alert"><?php esc_html_e( 'Please enter a valid email address.', 'wp-helanak-kids-theme' ); ?></p>
		<?php elseif ( 'limited' === $helanak_newsletter_status ) : ?><p class="helanak-form-feedback" role="alert"><?php esc_html_e( 'Please wait a few minutes before trying again.', 'wp-helanak-kids-theme' ); ?></p>
		<?php elseif ( in_array( $helanak_newsletter_status, array( 'failed', 'unavailable' ), true ) ) : ?><p class="helanak-form-feedback" role="alert"><?php esc_html_e( 'We could not save your subscription. Please try again later.', 'wp-helanak-kids-theme' ); ?></p><?php endif; ?>
		<form class="helanak-newsletter__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="helanak_newsletter_subscribe"><?php wp_nonce_field( 'helanak_newsletter_subscribe' ); ?>
			<div class="helanak-honeypot" aria-hidden="true"><label for="helanak-newsletter-website">Website</label><input id="helanak-newsletter-website" type="text" name="helanak_website" tabindex="-1" autocomplete="off"></div>
			<label class="screen-reader-text" for="helanak-newsletter-email"><?php esc_html_e( 'Email address', 'wp-helanak-kids-theme' ); ?></label><input id="helanak-newsletter-email" type="email" name="helanak_email" autocomplete="email" inputmode="email" spellcheck="false" dir="ltr" maxlength="254" required placeholder="<?php esc_attr_e( 'you@example.com', 'wp-helanak-kids-theme' ); ?>">
			<button type="submit" class="helanak-btn helanak-btn--primary"><?php esc_html_e( 'Subscribe', 'wp-helanak-kids-theme' ); ?></button>
		</form>
	</div></div>
</section>
