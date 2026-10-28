<?php
/** All enabled storybook friends remain visible, even without JavaScript. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! Helanak_Character_Engine::enabled_characters() ) { return; }
$helanak_parade_scale = array( 'rabbit' => .46, 'dog' => .56, 'cat' => .6, 'monkey' => .95, 'bear' => .46, 'fox' => .48, 'penguin' => .5, 'bird' => 1.6, 'turtle' => .56, 'elephant' => .5 );
?>
<section class="helanak-section" data-helanak-section="friends_parade"<?php helanak_section_hidden_attr( 'friends_parade' ); ?>>
	<div class="helanak-container"><div class="helanak-friends-card">
		<h2><?php esc_html_e( 'Meet our friends', 'wp-helanak-kids-theme' ); ?></h2><p class="helanak-friends-card__sub"><?php esc_html_e( 'The little companions who play along while you shop', 'wp-helanak-kids-theme' ); ?></p>
		<div class="helanak-parade" dir="ltr">
			<?php foreach ( Helanak_Theme_Customizer::character_labels() as $helanak_key => $helanak_label ) : ?><?php if ( Helanak_Character_Engine::is_enabled( $helanak_key ) ) : ?>
				<button type="button" class="helanak-parade__item" data-helanak-greet data-friend-name="<?php echo esc_attr( $helanak_label ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Say hello to %s', 'wp-helanak-kids-theme' ), $helanak_label ) ); ?>">
					<?php helanak_character( $helanak_key, array( 'scale' => $helanak_parade_scale[ $helanak_key ] ?? .6, 'wave' => 'bear' === $helanak_key ) ); ?>
					<span class="helanak-parade__name" dir="auto"><?php echo esc_html( $helanak_label ); ?></span>
				</button>
			<?php endif; ?><?php endforeach; ?>
		</div>
		<p class="helanak-friends-greeting" data-helanak-friend-greeting aria-live="polite"><?php esc_html_e( 'Choose a friend for a little hello.', 'wp-helanak-kids-theme' ); ?></p><button type="button" class="helanak-btn helanak-btn--outline helanak-surprise-button" data-helanak-surprise-friend><?php esc_html_e( 'Surprise me', 'wp-helanak-kids-theme' ); ?></button>
	</div></div>
</section>
