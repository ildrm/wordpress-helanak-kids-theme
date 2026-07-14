<?php
/**
 * Static trust-signal strip (shipping/safety/returns/payment). Content is
 * intentionally plain, translatable strings — every kids' store needs these
 * four, and they don't need per-site configuration to be true.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Icon / title / description triplets, copy taken from the prototype's trust
// strip (frontend/front-page.dc.html).
$helanak_trust_items = array(
	array( '↩️', __( '30-day returns', 'wp-helanak-kids-theme' ), __( 'Hassle-free and free of charge', 'wp-helanak-kids-theme' ) ),
	array( '🛡️', __( 'Safety certified', 'wp-helanak-kids-theme' ), __( 'Tested, non-toxic materials', 'wp-helanak-kids-theme' ) ),
	array( '🚚', __( 'Fast delivery', 'wp-helanak-kids-theme' ), __( 'Ships in 2–4 days', 'wp-helanak-kids-theme' ) ),
	array( '🔒', __( 'Secure checkout', 'wp-helanak-kids-theme' ), __( 'Encrypted, local gateways', 'wp-helanak-kids-theme' ) ),
);
?>
<section class="helanak-section" data-helanak-section="trust_badges"<?php helanak_section_hidden_attr( 'trust_badges' ); ?>>
	<div class="helanak-container helanak-trust">
		<?php foreach ( $helanak_trust_items as $helanak_item ) : ?>
			<div class="helanak-trust__item">
				<div class="helanak-trust__icon" aria-hidden="true"><?php echo esc_html( $helanak_item[0] ); ?></div>
				<div>
					<span class="helanak-trust__title"><?php echo esc_html( $helanak_item[1] ); ?></span>
					<span class="helanak-trust__desc"><?php echo esc_html( $helanak_item[2] ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
