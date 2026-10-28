<?php
/** Store form validation, delivery, abuse controls, and subscriber persistence. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Store_Forms {

	const DB_VERSION = '1';
	const DB_OPTION  = 'helanak_forms_db_version';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
	}

	/** A unique email hash makes simultaneous subscriptions safe and idempotent. */
	public static function maybe_install(): bool {
		if ( self::DB_VERSION === get_option( self::DB_OPTION ) ) {
			return true;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = $wpdb->prefix . 'helanak_subscribers';
		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(254) NOT NULL,
			email_hash char(64) NOT NULL,
			subscribed_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email_hash (email_hash)
		) " . $wpdb->get_charset_collate() . ';' );
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			return false;
		}
		$legacy = get_option( 'helanak_newsletter_subscribers', array() );
		foreach ( is_array( $legacy ) ? $legacy : array() as $email => $timestamp ) {
			if ( is_email( $email ) && false === self::insert_subscriber( strtolower( $email ), is_numeric( $timestamp ) ? (int) $timestamp : time() ) ) {
				return false;
			}
		}
		// Retain the legacy option for rollback; never silently discard subscribers.
		update_option( self::DB_OPTION, self::DB_VERSION, false );
		return true;
	}

	/** Returns a status code, without storing raw IP addresses. */
	public static function submission_guard( string $action, string $honeypot ): string {
		if ( '' !== trim( $honeypot ) ) {
			return 'spam';
		}
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key   = 'helanak_form_' . hash_hmac( 'sha256', $action . '|' . $ip, wp_salt() );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return 'limited';
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
		return '';
	}

	public static function validate_contact( array $values ): array {
		$errors = array();
		if ( '' === $values['name'] || mb_strlen( $values['name'] ) > 120 ) {
			$errors['name'] = __( 'Enter your name using no more than 120 characters.', 'helanak-core' );
		}
		if ( ! is_email( $values['email'] ) || strlen( $values['email'] ) > 254 ) {
			$errors['email'] = __( 'Enter a valid email address.', 'helanak-core' );
		}
		if ( mb_strlen( $values['subject'] ) > 200 ) {
			$errors['subject'] = __( 'Keep the subject within 200 characters.', 'helanak-core' );
		}
		if ( '' === $values['message'] || mb_strlen( $values['message'] ) > 10000 ) {
			$errors['message'] = __( 'Enter a message using no more than 10,000 characters.', 'helanak-core' );
		}
		return $errors;
	}

	public static function send_contact( array $values ): bool {
		if ( self::validate_contact( $values ) ) {
			return false;
		}
		$recipient = sanitize_email( get_option( 'admin_email' ) );
		if ( ! is_email( $recipient ) ) {
			return false;
		}
		$subject = $values['subject'] ?: __( 'New contact form message', 'helanak-core' );
		return wp_mail(
			$recipient,
			sprintf( '[%1$s] %2$s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $subject ),
			"Name: {$values['name']}\nEmail: {$values['email']}\n\n{$values['message']}",
			array( 'Reply-To: ' . $values['email'] )
		);
	}

	public static function subscribe( string $email ): string {
		if ( ! is_email( $email ) || strlen( $email ) > 254 ) {
			return 'invalid';
		}
		if ( ! self::maybe_install() ) {
			return 'unavailable';
		}
		return false === self::insert_subscriber( strtolower( $email ), time() ) ? 'failed' : 'success';
	}

	private static function insert_subscriber( string $email, int $timestamp ) {
		global $wpdb;
		return $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->prefix}helanak_subscribers (email, email_hash, subscribed_at) VALUES (%s, %s, %s)",
			$email,
			hash( 'sha256', $email ),
			gmdate( 'Y-m-d H:i:s', $timestamp )
		) );
	}
}
