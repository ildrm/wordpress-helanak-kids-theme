<?php
/**
 * Newsletter request/redirect presentation. Validation, abuse controls and
 * subscriber persistence belong to Helanak Core; this handler stores no data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Newsletter {

	const OPTION_KEY = 'helanak_newsletter_subscribers';

	public static function init(): void {
		add_action( 'admin_post_helanak_newsletter_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_nopriv_helanak_newsletter_subscribe', array( __CLASS__, 'handle_subscribe' ) );
	}

	public static function handle_subscribe(): void {
		check_admin_referer( 'helanak_newsletter_subscribe' );

		$email    = isset( $_POST['helanak_email'] ) && is_string( $_POST['helanak_email'] ) ? sanitize_text_field( wp_unslash( $_POST['helanak_email'] ) ) : '';
		$redirect = remove_query_arg( 'helanak_newsletter', wp_get_referer() ?: home_url( '/' ) );
		$status   = 'unavailable';
		if ( class_exists( 'Helanak_Core_Store_Forms' ) ) {
			$honeypot = isset( $_POST['helanak_website'] ) && is_string( $_POST['helanak_website'] ) ? wp_unslash( $_POST['helanak_website'] ) : '';
			$guard = Helanak_Core_Store_Forms::submission_guard( 'newsletter', $honeypot );
			$status = 'spam' === $guard ? 'success' : ( $guard ?: Helanak_Core_Store_Forms::subscribe( $email ) );
		}
		wp_safe_redirect( add_query_arg( 'helanak_newsletter', $status, $redirect ) . '#helanak-newsletter' );
		exit;
	}
}
