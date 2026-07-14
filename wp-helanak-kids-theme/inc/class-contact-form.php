<?php
/**
 * Contact form handler: posts to admin-post.php, validated server-side, then
 * relayed via wp_mail() to the site's admin email. No CRM/ESP integration in
 * this phase — same "not meant to replace a real integration at scale" scope
 * as inc/class-newsletter.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Contact_Form {

	public static function init(): void {
		add_action( 'admin_post_helanak_contact_submit', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_helanak_contact_submit', array( __CLASS__, 'handle_submit' ) );
	}

	public static function handle_submit(): void {
		check_admin_referer( 'helanak_contact_submit' );

		$redirect = wp_get_referer() ?: home_url( '/' );

		$name    = isset( $_POST['helanak_name'] ) ? sanitize_text_field( wp_unslash( $_POST['helanak_name'] ) ) : '';
		$email   = isset( $_POST['helanak_email'] ) ? sanitize_email( wp_unslash( $_POST['helanak_email'] ) ) : '';
		$subject = isset( $_POST['helanak_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['helanak_subject'] ) ) : '';
		$message = isset( $_POST['helanak_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['helanak_message'] ) ) : '';

		if ( ! $name || ! is_email( $email ) || ! $message ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'helanak_contact', 'invalid', $redirect ) . '#helanak-contact-form' ) );
			exit;
		}

		$to      = get_option( 'admin_email' );
		$title   = $subject
			? sprintf( '[%1$s] %2$s', get_bloginfo( 'name' ), $subject )
			: sprintf( '[%s] New contact form message', get_bloginfo( 'name' ) );
		$body    = "Name: {$name}\nEmail: {$email}\n\n{$message}";
		$headers = array( "Reply-To: {$name} <{$email}>" );

		wp_mail( $to, $title, $body, $headers );

		wp_safe_redirect( esc_url_raw( add_query_arg( 'helanak_contact', 'success', $redirect ) . '#helanak-contact-form' ) );
		exit;
	}
}
