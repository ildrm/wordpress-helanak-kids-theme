<?php
/**
 * Contact request/redirect presentation with short-lived private form state.
 * Helanak Core validates submissions, limits abuse, and sends store email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Contact_Form {

	public static function form_state(): array {
		$state = array(
			'values' => array_fill_keys( array( 'name', 'email', 'subject', 'message' ), '' ),
			'errors' => array(),
			'status' => isset( $_GET['helanak_contact'] ) && is_string( $_GET['helanak_contact'] ) ? sanitize_key( wp_unslash( $_GET['helanak_contact'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
		);
		$token = isset( $_GET['helanak_contact_token'] ) && is_string( $_GET['helanak_contact_token'] ) ? sanitize_key( wp_unslash( $_GET['helanak_contact_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( preg_match( '/^[a-z0-9]{32}$/', $token ) ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
			header( 'Referrer-Policy: same-origin' );
			$saved = get_transient( 'helanak_contact_' . $token );
			if ( is_array( $saved ) ) {
				$state = $saved;
			}
		}
		return $state;
	}

	public static function init(): void {
		add_action( 'admin_post_helanak_contact_submit', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_helanak_contact_submit', array( __CLASS__, 'handle_submit' ) );
	}

	public static function handle_submit(): void {
		check_admin_referer( 'helanak_contact_submit' );

		$redirect = remove_query_arg( array( 'helanak_contact', 'helanak_contact_token' ), wp_get_referer() ?: home_url( '/' ) );
		$values   = array();
		foreach ( array( 'name', 'email', 'subject', 'message' ) as $field ) {
			$key = 'helanak_' . $field;
			$raw = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$values[ $field ] = 'message' === $field ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		}
		$errors = array();
		$status = 'unavailable';
		if ( class_exists( 'Helanak_Core_Store_Forms' ) ) {
			$honeypot = isset( $_POST['helanak_website'] ) && is_string( $_POST['helanak_website'] ) ? wp_unslash( $_POST['helanak_website'] ) : '';
			$guard    = Helanak_Core_Store_Forms::submission_guard( 'contact', $honeypot );
			$errors   = Helanak_Core_Store_Forms::validate_contact( $values );
			if ( 'spam' === $guard ) {
				$status = 'success';
				$errors = array();
			} elseif ( 'limited' === $guard ) {
				$status = 'limited';
				$errors['form'] = __( 'Please wait a few minutes before sending another message.', 'wp-helanak-kids-theme' );
			} elseif ( $errors ) {
				$status = 'invalid';
			} else {
				$status = Helanak_Core_Store_Forms::send_contact( $values ) ? 'success' : 'failed';
			}
		}
		if ( 'failed' === $status || 'unavailable' === $status ) {
			$errors['form'] = __( 'Your message could not be sent. Your details are saved below; please try again later.', 'wp-helanak-kids-theme' );
		}
		$args = array( 'helanak_contact' => $status );
		if ( 'success' !== $status ) {
			$token = strtolower( wp_generate_password( 32, false, false ) );
			// Bound retained input even if a client bypasses HTML maxlength.
			foreach ( array( 'name' => 120, 'email' => 254, 'subject' => 200, 'message' => 10000 ) as $field => $limit ) {
				$values[ $field ] = mb_substr( $values[ $field ], 0, $limit );
			}
			set_transient( 'helanak_contact_' . $token, array( 'values' => $values, 'errors' => $errors, 'status' => $status ), 10 * MINUTE_IN_SECONDS );
			$args['helanak_contact_token'] = $token;
		}
		wp_safe_redirect( add_query_arg( $args, $redirect ) . '#helanak-contact-form' );
		exit;
	}
}
