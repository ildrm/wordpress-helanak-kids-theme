<?php
/**
 * Template Name: Contact
 *
 * Auto-selected for a page with the slug "contact" via WordPress's
 * page-{slug}.php template hierarchy; the Template Name header also lets it
 * be assigned manually to any page from Page Attributes. Form posts to
 * admin-post.php, handled by Helanak_Contact_Form (inc/class-contact-form.php)
 * — real server-side validation (required fields + is_email()), relayed via
 * wp_mail() to the site's admin email. The phone/address/hours below are
 * starter copy for a new install to edit before launch; only the "to"
 * address is live data (get_option('admin_email')).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$helanak_contact_status = isset( $_GET['helanak_contact'] ) ? sanitize_key( wp_unslash( $_GET['helanak_contact'] ) ) : '';

$helanak_faqs = array(
	array(
		'q' => __( 'How long does delivery take?', 'wp-helanak-kids-theme' ),
		'a' => __( 'Most orders arrive within 2-4 business days within the city, and 4-7 days nationwide.', 'wp-helanak-kids-theme' ),
	),
	array(
		'q' => __( 'What is your return policy?', 'wp-helanak-kids-theme' ),
		'a' => __( 'We accept returns within 30 days of delivery, as long as items are unworn with tags attached.', 'wp-helanak-kids-theme' ),
	),
	array(
		'q' => __( 'How do I pick the right size?', 'wp-helanak-kids-theme' ),
		'a' => __( 'Check the size guide on each product page — it maps age and height to our sizing so you can order with confidence.', 'wp-helanak-kids-theme' ),
	),
	array(
		'q' => __( 'Can I get an order gift-wrapped?', 'wp-helanak-kids-theme' ),
		'a' => __( 'Yes — add a gift wrap note at checkout and we will wrap it with care before shipping.', 'wp-helanak-kids-theme' ),
	),
);
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">

		<div class="helanak-page-hero">
			<h1><?php esc_html_e( "We'd love to hear from you", 'wp-helanak-kids-theme' ); ?></h1>
			<p><?php esc_html_e( 'Questions about an order, sizing, or just want to say hi? Send us a message.', 'wp-helanak-kids-theme' ); ?></p>
			<?php
			// The prototype's celebrateRabbit: a waving rabbit beside the page title.
			helanak_character(
				'rabbit',
				array(
					'wave'  => true,
					'scale' => .55,
				)
			);
			?>
		</div>

		<div class="helanak-contact-layout">
			<form id="helanak-contact-form" class="helanak-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="helanak_contact_submit" />
				<?php wp_nonce_field( 'helanak_contact_submit' ); ?>

				<?php if ( 'success' === $helanak_contact_status ) : ?>
					<p role="status"><?php esc_html_e( "Thanks — your message is on its way to us. We'll reply soon.", 'wp-helanak-kids-theme' ); ?></p>
				<?php elseif ( 'invalid' === $helanak_contact_status ) : ?>
					<p role="alert"><?php esc_html_e( 'Please fill in your name, a valid email, and a message.', 'wp-helanak-kids-theme' ); ?></p>
				<?php endif; ?>

				<div>
					<label for="helanak-contact-name"><?php esc_html_e( 'Name', 'wp-helanak-kids-theme' ); ?></label>
					<input id="helanak-contact-name" type="text" name="helanak_name" required />
				</div>

				<div>
					<label for="helanak-contact-email"><?php esc_html_e( 'Email', 'wp-helanak-kids-theme' ); ?></label>
					<input id="helanak-contact-email" type="email" name="helanak_email" required />
				</div>

				<div>
					<label for="helanak-contact-subject"><?php esc_html_e( 'Subject', 'wp-helanak-kids-theme' ); ?></label>
					<input id="helanak-contact-subject" type="text" name="helanak_subject" />
				</div>

				<div>
					<label for="helanak-contact-message"><?php esc_html_e( 'Message', 'wp-helanak-kids-theme' ); ?></label>
					<textarea id="helanak-contact-message" name="helanak_message" rows="6" required></textarea>
				</div>

				<button type="submit" class="helanak-btn helanak-btn--primary"><?php esc_html_e( 'Send message', 'wp-helanak-kids-theme' ); ?></button>
			</form>

			<div class="helanak-contact-info">
				<?php
				// Prototype: one card per channel, each with a gradient icon
				// tile. Values are the same starter copy as before (only the
				// email is live data) — presentation changed, not the data.
				$helanak_contact_cards = array(
					array(
						'icon'  => '📧',
						'label' => __( 'Email', 'wp-helanak-kids-theme' ),
						'value' => '<a href="mailto:' . esc_attr( get_option( 'admin_email' ) ) . '">' . esc_html( get_option( 'admin_email' ) ) . '</a>',
					),
					array(
						'icon'  => '📞',
						'label' => __( 'Phone', 'wp-helanak-kids-theme' ),
						'value' => esc_html__( '+98 21 1234 5678', 'wp-helanak-kids-theme' ),
					),
					array(
						'icon'  => '📍',
						'label' => __( 'Address', 'wp-helanak-kids-theme' ),
						'value' => esc_html__( '12 Mehr Alley, Valiasr St, Tehran', 'wp-helanak-kids-theme' ),
					),
					array(
						'icon'  => '🕘',
						'label' => __( 'Support hours', 'wp-helanak-kids-theme' ),
						'value' => esc_html__( 'Sat–Thu, 9:00–18:00', 'wp-helanak-kids-theme' ),
					),
				);
				foreach ( $helanak_contact_cards as $helanak_contact_card ) :
					?>
					<div class="helanak-contact-card">
						<span class="helanak-contact-card__icon" aria-hidden="true"><?php echo esc_html( $helanak_contact_card['icon'] ); ?></span>
						<span>
							<span class="helanak-contact-card__label"><?php echo esc_html( $helanak_contact_card['label'] ); ?></span>
							<span class="helanak-contact-card__value"><?php echo wp_kses_post( $helanak_contact_card['value'] ); ?></span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="helanak-faq">
			<h2><?php esc_html_e( 'Frequently asked questions', 'wp-helanak-kids-theme' ); ?></h2>
			<?php foreach ( $helanak_faqs as $helanak_faq ) : ?>
				<details class="helanak-faq__item">
					<summary><?php echo esc_html( $helanak_faq['q'] ); ?></summary>
					<p><?php echo esc_html( $helanak_faq['a'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>

	</div>
</main>

<?php
get_footer();
