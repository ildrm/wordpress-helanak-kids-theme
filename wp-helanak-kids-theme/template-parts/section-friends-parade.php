<?php
/**
 * "Meet our friends" mascot parade: all 8 characters, gated individually by
 * Helanak_Character_Engine::is_enabled() (per-character Customizer toggle),
 * not just the section-level toggle. Forced dir="ltr" so the parade order
 * stays stable regardless of site language — mascots aren't text and
 * shouldn't reverse with RTL.
 *
 * Per-species scale + hop/wave are ported verbatim from the prototype's
 * `parade` array (frontend/front-page.dc.html): rabbit(.46,{hop:'4.5s'}),
 * dog(.56), cat(.6), monkey(.95), bear(.46,{wave:true}), fox(.48),
 * penguin(.5), bird(1.6) — hand-tuned per species, not a single shared
 * pixel size, so `scale` (not `size`) is passed to helanak_character().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helanak_characters    = Helanak_Theme_Customizer::character_labels();
$helanak_parade_scale  = array(
	'rabbit'  => .46,
	'dog'     => .56,
	'cat'     => .6,
	'monkey'  => .95,
	'bear'    => .46,
	'fox'     => .48,
	'penguin' => .5,
	'bird'    => 1.6,
);
$helanak_parade_extra  = array(
	'rabbit' => array( 'hop' => '4.5s' ),
	'bear'   => array( 'wave' => true ),
);
?>
<section class="helanak-section" data-helanak-section="friends_parade"<?php helanak_section_hidden_attr( 'friends_parade' ); ?>>
	<div class="helanak-container">
		<div class="helanak-friends-card">
			<h2><?php esc_html_e( 'Meet our friends', 'wp-helanak-kids-theme' ); ?></h2>
			<p class="helanak-friends-card__sub"><?php esc_html_e( 'The little companions who play along while you shop', 'wp-helanak-kids-theme' ); ?></p>
			<div class="helanak-parade" dir="ltr">
				<?php foreach ( $helanak_characters as $helanak_key => $helanak_label ) : ?>
					<?php if ( Helanak_Character_Engine::is_enabled( $helanak_key ) ) : ?>
						<div class="helanak-parade__item">
							<?php
							helanak_character(
								$helanak_key,
								array_merge(
									array( 'scale' => $helanak_parade_scale[ $helanak_key ] ?? .6 ),
									$helanak_parade_extra[ $helanak_key ] ?? array()
								)
							);
							?>
							<span class="helanak-parade__name"><?php echo esc_html( $helanak_label ); ?></span>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
