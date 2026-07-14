<?php
/**
 * "Shop by age" selector: real age_range taxonomy terms (from helanak-core).
 * Per the prototype, the age-range text itself sits inside a pastel-gradient
 * bubble with a life-stage sub-label beneath it. The gradient + sub-label are
 * decorative presentation keyed to the plugin's canonical term slugs — an
 * unknown slug just falls back to the generic gradient with no sub-label,
 * while the age text always comes from helanak_age_label().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_ages = get_terms(
	array(
		'taxonomy'   => 'age_range',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $helanak_ages ) ) {
	$helanak_ages = array();
}

// slug => [bubble hue (prototype: hsl(H 75% 88%) → hsl(H+35 70% 92%)), sub-label].
$helanak_age_meta = array(
	'0-1y'  => array( 205, __( 'Baby', 'wp-helanak-kids-theme' ) ),
	'1-3y'  => array( 330, __( 'Toddler', 'wp-helanak-kids-theme' ) ),
	'3-5y'  => array( 120, __( 'Preschool', 'wp-helanak-kids-theme' ) ),
	'5-8y'  => array( 45, __( 'Kids', 'wp-helanak-kids-theme' ) ),
	'8-12y' => array( 275, __( 'Big kids', 'wp-helanak-kids-theme' ) ),
	'12p'   => array( 165, __( 'Teens', 'wp-helanak-kids-theme' ) ),
);
?>
<section class="helanak-section" data-helanak-section="age_selector"<?php helanak_section_hidden_attr( 'age_selector' ); ?>>
	<div class="helanak-container">
		<div class="helanak-section__head">
			<h2><?php esc_html_e( 'Shop by age', 'wp-helanak-kids-theme' ); ?></h2>
			<span class="helanak-section__sub"><?php esc_html_e( 'Sizes that grow with them', 'wp-helanak-kids-theme' ); ?></span>
		</div>
		<?php if ( $helanak_ages ) : ?>
			<div class="helanak-ages">
				<?php
				foreach ( $helanak_ages as $helanak_age_term ) :
					$helanak_age_extra = $helanak_age_meta[ $helanak_age_term->slug ] ?? null;
					$helanak_age_style = '';
					if ( $helanak_age_extra ) {
						$helanak_age_hue   = (int) $helanak_age_extra[0];
						$helanak_age_style = sprintf(
							'--age-bg:linear-gradient(160deg,hsl(%1$d 75%% 88%%),hsl(%2$d 70%% 92%%))',
							$helanak_age_hue,
							( $helanak_age_hue + 35 ) % 360
						);
					}
					?>
					<a class="helanak-age" href="<?php echo esc_url( get_term_link( $helanak_age_term ) ); ?>"<?php echo $helanak_age_style ? ' style="' . esc_attr( $helanak_age_style ) . '"' : ''; ?>>
						<span class="helanak-age__bubble"><?php echo esc_html( helanak_age_label( $helanak_age_term ) ); ?></span>
						<?php if ( $helanak_age_extra ) : ?>
							<span class="helanak-age__label"><?php echo esc_html( $helanak_age_extra[1] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Activate the Helanak Core plugin to enable age-range browsing.', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>
	</div>
</section>
