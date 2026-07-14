<?php
/**
 * Resolves the storefront's active language (English or Persian) from a
 * `?lang=` query var or a persisted cookie, and forces every `get_locale()`
 * call — core, WooCommerce, and the theme alike — to that language. This
 * overrides the site's `WPLANG` option outright: the storefront defaults to
 * English for new visitors and only switches to Persian when a visitor picks
 * it, rather than the whole site being locked to whatever `WPLANG` happens to
 * be set to. Registering the `locale` filter here (top-level plugin load,
 * before `plugins_loaded`) — not in the theme's functions.php — matters
 * because WordPress core reads `get_locale()` for `load_default_textdomain()`
 * and builds `$wp_locale` (which drives `is_rtl()`) before any theme file is
 * ever included.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Language_Switcher {

	const COOKIE_NAME = 'helanak_lang';

	/** Supported UI language => full WordPress locale. */
	const LOCALES = array(
		'en' => 'en_US',
		'fa' => 'fa_IR',
	);

	/** @var string|null Resolved once per request. */
	private static $lang = null;

	public static function init(): void {
		add_filter( 'locale', array( __CLASS__, 'filter_locale' ) );
		self::persist_cookie();
	}

	/** The visitor's chosen language ('en' or 'fa'), resolved from ?lang= then the cookie, defaulting to 'en'. */
	public static function current_lang(): string {
		if ( null !== self::$lang ) {
			return self::$lang;
		}

		$requested = self::requested_lang();
		if ( null !== $requested ) {
			self::$lang = $requested;
			return self::$lang;
		}

		$cookie = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
		if ( isset( self::LOCALES[ $cookie ] ) ) {
			self::$lang = $cookie;
			return self::$lang;
		}

		self::$lang = 'en';
		return self::$lang;
	}

	/** True when the storefront should render right-to-left (Persian) chrome. */
	public static function is_rtl(): bool {
		return 'fa' === self::current_lang();
	}

	public static function filter_locale( $locale ) {
		return self::LOCALES[ self::current_lang() ];
	}

	/** Builds a link that switches the given path (defaults to the current request) to $lang. */
	public static function switch_url( string $lang, string $path = '' ): string {
		if ( ! isset( self::LOCALES[ $lang ] ) ) {
			return home_url( '/' );
		}
		if ( '' !== $path ) {
			return add_query_arg( 'lang', $lang, home_url( $path ) );
		}
		// No explicit path: $_SERVER['REQUEST_URI'] already includes the site's own subdirectory
		// (if any), so running it through home_url() like the branch above would double that
		// subdirectory. add_query_arg() with no $url argument reads REQUEST_URI itself instead.
		return add_query_arg( 'lang', $lang );
	}

	/** ?lang= value from the request, only if it names a supported language. */
	private static function requested_lang(): ?string {
		$requested = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';
		return isset( self::LOCALES[ $requested ] ) ? $requested : null;
	}

	/** Persists an explicit ?lang= choice as a cookie so it survives past the current request. */
	private static function persist_cookie(): void {
		$requested = self::requested_lang();
		if ( null === $requested || headers_sent() ) {
			return;
		}

		$current_cookie = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
		if ( $requested === $current_cookie ) {
			return;
		}

		setcookie( self::COOKIE_NAME, $requested, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
	}
}
