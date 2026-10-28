<?php
/** Useful store guidance without unverified policy or certification claims. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$helanak_trust_items = array(
	array( 'M4 5h16v14H4zM8 2v6M16 2v6M4 10h16', __( 'Shop by age', 'wp-helanak-kids-theme' ), __( 'Find a new favourite for their stage', 'wp-helanak-kids-theme' ) ),
	array( 'M7 3h10v18H7zM10 8h4M10 12h4M10 16h3', __( 'The little details', 'wp-helanak-kids-theme' ), __( 'Check sizes, materials, and care', 'wp-helanak-kids-theme' ) ),
	array( 'M20 5.5a5 5 0 0 0-8 1 5 5 0 0 0-8-1C.5 9.5 5 15 12 20c7-5 11.5-10.5 8-14.5Z', __( 'Save your favourites', 'wp-helanak-kids-theme' ), __( 'Keep little discoveries for later', 'wp-helanak-kids-theme' ) ),
	array( 'M4 4h16v12H9l-5 4V4Zm4 5h8M8 12h5', __( 'A helping hand', 'wp-helanak-kids-theme' ), __( 'Ask about a product or your order', 'wp-helanak-kids-theme' ) ),
);
?>
<section class="helanak-section" data-helanak-section="trust_badges"<?php helanak_section_hidden_attr( 'trust_badges' ); ?>><div class="helanak-container helanak-trust">
	<?php foreach ( $helanak_trust_items as $helanak_item ) : ?><div class="helanak-trust__item"><div class="helanak-trust__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="23" height="23"><path d="<?php echo esc_attr( $helanak_item[0] ); ?>" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div><span class="helanak-trust__title"><?php echo esc_html( $helanak_item[1] ); ?></span><span class="helanak-trust__desc"><?php echo esc_html( $helanak_item[2] ); ?></span></div></div><?php endforeach; ?>
</div></section>
