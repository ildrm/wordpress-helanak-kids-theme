<?php
/**
 * Minimal newsletter capture: no ESP integration in this phase (Mailchimp /
 * Klaviyo / etc. is a per-site choice, deliberately left to a future
 * integration rather than hardcoding one vendor here). Subscribers are
 * stored in a single wp_options array — adequate for a Phase 1-3 foundation,
 * not meant to replace a real ESP at scale.
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

		$email    = isset( $_POST['helanak_email'] ) ? sanitize_email( wp_unslash( $_POST['helanak_email'] ) ) : '';
		$redirect = wp_get_referer() ?: home_url( '/' );

		if ( ! is_email( $email ) ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'helanak_newsletter', 'invalid', $redirect ) . '#helanak-newsletter' ) );
			exit;
		}

		$subscribers = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $subscribers ) ) {
			$subscribers = array();
		}
		if ( ! isset( $subscribers[ $email ] ) ) {
			$subscribers[ $email ] = time();
			update_option( self::OPTION_KEY, $subscribers, false );
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( 'helanak_newsletter', 'success', $redirect ) . '#helanak-newsletter' ) );
		exit;
	}
}
