<?php
/** Template Name: Contact */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Private retained fields must disable caching before any output is sent.
$helanak_form = Helanak_Contact_Form::form_state();
$helanak_values = $helanak_form['values'];
$helanak_errors = $helanak_form['errors'];
$helanak_status = $helanak_form['status'];
$helanak_public_email = sanitize_email( get_theme_mod( 'helanak_contact_email', '' ) );
$helanak_phone = get_theme_mod( 'helanak_contact_phone', '' );
$helanak_address = get_theme_mod( 'helanak_contact_address', '' );
$helanak_hours = get_theme_mod( 'helanak_contact_hours', '' );
get_header();
?>
<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">
		<div class="helanak-page-hero helanak-page-hero--contact"><div><h1><?php esc_html_e( "We'd love to hear from you", 'wp-helanak-kids-theme' ); ?></h1><p><?php esc_html_e( 'Questions about an order, sizing, or just want to say hi? Send us a message.', 'wp-helanak-kids-theme' ); ?></p></div><?php helanak_character( 'rabbit', array( 'wave' => true, 'scale' => .55 ) ); ?></div>
		<?php while ( have_posts() ) : the_post(); ?><?php if ( trim( get_the_content() ) ) : ?><div class="helanak-page-content entry-content"><?php the_content(); ?></div><?php endif; ?><?php endwhile; ?>
		<div class="helanak-contact-layout">
			<form id="helanak-contact-form" class="helanak-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="helanak_contact_submit">
				<?php wp_nonce_field( 'helanak_contact_submit' ); ?>
				<div class="helanak-honeypot" aria-hidden="true"><label for="helanak-contact-website">Website</label><input id="helanak-contact-website" type="text" name="helanak_website" tabindex="-1" autocomplete="off"></div>
				<?php if ( 'success' === $helanak_status ) : ?>
					<div class="helanak-form-feedback helanak-form-feedback--success" role="status" tabindex="-1" data-helanak-form-feedback><?php esc_html_e( "Thanks — your message is on its way to us. We'll reply soon.", 'wp-helanak-kids-theme' ); ?></div>
				<?php elseif ( $helanak_errors || in_array( $helanak_status, array( 'invalid', 'failed', 'limited', 'unavailable' ), true ) ) : ?>
					<div class="helanak-form-feedback" role="alert" tabindex="-1" data-helanak-form-feedback>
						<h2><?php esc_html_e( 'Please check your message', 'wp-helanak-kids-theme' ); ?></h2>
						<?php if ( $helanak_errors ) : ?><ul><?php foreach ( $helanak_errors as $helanak_field => $helanak_error ) : ?><li><?php if ( in_array( $helanak_field, array( 'name', 'email', 'subject', 'message' ), true ) ) : ?><a href="#helanak-contact-<?php echo esc_attr( $helanak_field ); ?>"><?php echo esc_html( $helanak_error ); ?></a><?php else : ?><?php echo esc_html( $helanak_error ); ?><?php endif; ?></li><?php endforeach; ?></ul>
						<?php else : ?><p><?php esc_html_e( 'Your message could not be sent. Please try again in a few minutes.', 'wp-helanak-kids-theme' ); ?></p><?php endif; ?>
					</div>
				<?php endif; ?>
				<div><label for="helanak-contact-name"><?php esc_html_e( 'Name', 'wp-helanak-kids-theme' ); ?></label><input id="helanak-contact-name" type="text" name="helanak_name" autocomplete="name" maxlength="120" value="<?php echo esc_attr( $helanak_values['name'] ?? '' ); ?>" required<?php if ( isset( $helanak_errors['name'] ) ) : ?> aria-invalid="true" aria-describedby="helanak-name-error"<?php endif; ?>><?php if ( isset( $helanak_errors['name'] ) ) : ?><p id="helanak-name-error" class="helanak-field-error"><?php echo esc_html( $helanak_errors['name'] ); ?></p><?php endif; ?></div>
				<div><label for="helanak-contact-email"><?php esc_html_e( 'Email', 'wp-helanak-kids-theme' ); ?></label><input id="helanak-contact-email" type="email" name="helanak_email" autocomplete="email" inputmode="email" spellcheck="false" dir="ltr" maxlength="254" value="<?php echo esc_attr( $helanak_values['email'] ?? '' ); ?>" required<?php if ( isset( $helanak_errors['email'] ) ) : ?> aria-invalid="true" aria-describedby="helanak-email-error"<?php endif; ?>><?php if ( isset( $helanak_errors['email'] ) ) : ?><p id="helanak-email-error" class="helanak-field-error"><?php echo esc_html( $helanak_errors['email'] ); ?></p><?php endif; ?></div>
				<div><label for="helanak-contact-subject"><?php esc_html_e( 'Subject', 'wp-helanak-kids-theme' ); ?></label><input id="helanak-contact-subject" type="text" name="helanak_subject" maxlength="200" value="<?php echo esc_attr( $helanak_values['subject'] ?? '' ); ?>"<?php if ( isset( $helanak_errors['subject'] ) ) : ?> aria-invalid="true" aria-describedby="helanak-subject-error"<?php endif; ?>><?php if ( isset( $helanak_errors['subject'] ) ) : ?><p id="helanak-subject-error" class="helanak-field-error"><?php echo esc_html( $helanak_errors['subject'] ); ?></p><?php endif; ?></div>
				<div><label for="helanak-contact-message"><?php esc_html_e( 'Message', 'wp-helanak-kids-theme' ); ?></label><textarea id="helanak-contact-message" name="helanak_message" rows="7" maxlength="10000" required<?php if ( isset( $helanak_errors['message'] ) ) : ?> aria-invalid="true" aria-describedby="helanak-message-error"<?php endif; ?>><?php echo esc_textarea( $helanak_values['message'] ?? '' ); ?></textarea><?php if ( isset( $helanak_errors['message'] ) ) : ?><p id="helanak-message-error" class="helanak-field-error"><?php echo esc_html( $helanak_errors['message'] ); ?></p><?php endif; ?></div>
				<button type="submit" class="helanak-btn helanak-btn--dark"><?php esc_html_e( 'Send message', 'wp-helanak-kids-theme' ); ?></button>
			</form>
			<aside class="helanak-contact-info">
				<h2><?php esc_html_e( 'A little help, when you need it', 'wp-helanak-kids-theme' ); ?></h2><p><?php esc_html_e( 'Include your order number if you are asking about an order. Please keep payment details out of your message.', 'wp-helanak-kids-theme' ); ?></p>
				<?php if ( $helanak_public_email && is_email( $helanak_public_email ) ) : ?><div class="helanak-contact-card"><span><span class="helanak-contact-card__label"><?php esc_html_e( 'Email', 'wp-helanak-kids-theme' ); ?></span><a class="helanak-contact-card__value" href="<?php echo esc_url( 'mailto:' . $helanak_public_email ); ?>"><?php echo esc_html( $helanak_public_email ); ?></a></span></div><?php endif; ?>
				<?php if ( $helanak_phone ) : ?><div class="helanak-contact-card"><span><span class="helanak-contact-card__label"><?php esc_html_e( 'Phone', 'wp-helanak-kids-theme' ); ?></span><a class="helanak-contact-card__value" dir="ltr" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $helanak_phone ) ); ?>"><?php echo esc_html( $helanak_phone ); ?></a></span></div><?php endif; ?>
				<?php if ( $helanak_address ) : ?><div class="helanak-contact-card"><span><span class="helanak-contact-card__label"><?php esc_html_e( 'Address', 'wp-helanak-kids-theme' ); ?></span><span class="helanak-contact-card__value"><?php echo nl2br( esc_html( $helanak_address ) ); ?></span></span></div><?php endif; ?>
				<?php if ( $helanak_hours ) : ?><div class="helanak-contact-card"><span><span class="helanak-contact-card__label"><?php esc_html_e( 'Support hours', 'wp-helanak-kids-theme' ); ?></span><span class="helanak-contact-card__value"><?php echo esc_html( $helanak_hours ); ?></span></span></div><?php endif; ?>
				<div class="helanak-contact-companion"><?php helanak_character( 'elephant', array( 'scale' => .65, 'wave' => true ) ); ?></div>
			</aside>
		</div>
	</div>
</main>
<?php get_footer(); ?>
