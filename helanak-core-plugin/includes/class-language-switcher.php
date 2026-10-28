<?php
/**
 * Storefront English/Persian language selection.
 *
 * An explicit query-string or cookie choice may override the storefront
 * locale. Without one, WordPress's configured site locale is preserved.
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
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'localize_menu_items' ) );
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'menu_label_fields' ), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_menu_labels' ), 10, 2 );
		self::persist_cookie();
	}

	/** Optional explicit labels keep custom navigation editable in both languages. */
	public static function localize_menu_items( array $items ): array {
		if ( is_admin() ) {
			return $items;
		}
		foreach ( $items as $item ) {
			$label = get_post_meta( $item->ID, 'helanak_menu_label_' . self::current_lang(), true );
			if ( is_string( $label ) && '' !== $label ) {
				$item->title = $label;
			}
		}
		return $items;
	}

	public static function menu_label_fields( $item_id, $item ): void {
		static $nonce_printed = false;
		if ( ! $nonce_printed ) {
			wp_nonce_field( 'helanak_menu_labels', 'helanak_menu_labels_nonce' );
			$nonce_printed = true;
		}
		foreach ( array( 'en' => __( 'English navigation label', 'helanak-core' ), 'fa' => __( 'Persian navigation label', 'helanak-core' ) ) as $language => $label ) {
			printf(
				'<p class="description description-wide"><label for="helanak-menu-%1$s-%2$d">%3$s<br><input type="text" class="widefat" id="helanak-menu-%1$s-%2$d" name="helanak_menu_label_%1$s[%2$d]" value="%4$s"></label></p>',
				esc_attr( $language ),
				absint( $item_id ),
				esc_html( $label ),
				esc_attr( get_post_meta( $item_id, 'helanak_menu_label_' . $language, true ) )
			);
		}
	}

	public static function save_menu_labels( $menu_id, $item_id ): void {
		$nonce = isset( $_POST['helanak_menu_labels_nonce'] ) && is_string( $_POST['helanak_menu_labels_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['helanak_menu_labels_nonce'] ) ) : '';
		if ( ! current_user_can( 'edit_theme_options' ) || ! wp_verify_nonce( $nonce, 'helanak_menu_labels' ) ) {
			return;
		}
		foreach ( array( 'en', 'fa' ) as $language ) {
			$key = 'helanak_menu_label_' . $language;
			if ( ! isset( $_POST[ $key ][ $item_id ] ) || ! is_string( $_POST[ $key ][ $item_id ] ) ) {
				continue;
			}
			update_post_meta( $item_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ][ $item_id ] ) ) );
		}
	}

	/** The active storefront language ('en' or 'fa'). */
	public static function current_lang(): string {
		if ( null !== self::$lang ) {
			return self::$lang;
		}

		$selected = self::selected_lang();
		if ( null !== $selected ) {
			self::$lang = $selected;
			return self::$lang;
		}

		$site_locale = (string) get_option( 'WPLANG', '' );
		self::$lang  = 'fa' === substr( $site_locale, 0, 2 ) ? 'fa' : 'en';
		return self::$lang;
	}

	public static function is_rtl(): bool {
		return 'fa' === self::current_lang();
	}

	/**
	 * Preserve WordPress's locale unless this storefront request contains a
	 * valid, explicit visitor selection.
	 *
	 * @param string $locale Locale selected by WordPress.
	 */
	public static function filter_locale( $locale ) {
		if ( ! self::should_override_locale() ) {
			return $locale;
		}

		$selected = self::selected_lang();
		return null === $selected ? $locale : self::LOCALES[ $selected ];
	}

	/** Builds a link that switches the current or supplied path to $lang. */
	public static function switch_url( string $lang, string $path = '' ): string {
		if ( ! isset( self::LOCALES[ $lang ] ) ) {
			return home_url( '/' );
		}
		if ( '' !== $path ) {
			return add_query_arg( 'lang', $lang, home_url( $path ) );
		}
		return add_query_arg( 'lang', $lang );
	}

	private static function requested_lang(): ?string {
		$raw       = isset( $_GET['lang'] ) ? wp_unslash( $_GET['lang'] ) : '';
		$requested = is_scalar( $raw ) ? sanitize_key( (string) $raw ) : '';
		return isset( self::LOCALES[ $requested ] ) ? $requested : null;
	}

	/** Explicit query/cookie selection, or null when WordPress should decide. */
	private static function selected_lang(): ?string {
		$requested = self::requested_lang();
		if ( null !== $requested ) {
			return $requested;
		}

		$raw    = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) : '';
		$cookie = is_scalar( $raw ) ? sanitize_key( (string) $raw ) : '';
		return isset( self::LOCALES[ $cookie ] ) ? $cookie : null;
	}

	/** Storefront requests and storefront AJAX may opt in; system screens may not. */
	private static function should_override_locale(): bool {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}
		if ( wp_doing_cron() ) {
			return false;
		}
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			return false;
		}
		if ( is_admin() ) {
			$raw_action = isset( $_REQUEST['action'] ) ? wp_unslash( $_REQUEST['action'] ) : '';
			$action     = is_scalar( $raw_action ) ? sanitize_key( (string) $raw_action ) : '';
			if ( ! wp_doing_ajax() ) {
				return isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] && in_array( $action, array( 'helanak_contact_submit', 'helanak_newsletter_subscribe' ), true );
			}
			if ( ! in_array( $action, array( 'helanak_quick_view', 'helanak_search_suggest', 'helanak_compare' ), true ) ) {
				return false;
			}
		}
		return true;
	}

	/** Persist an explicit visitor choice for later storefront requests. */
	private static function persist_cookie(): void {
		$requested = self::requested_lang();
		if ( null === $requested || headers_sent() ) {
			return;
		}

		$raw_cookie     = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) : '';
		$current_cookie = is_scalar( $raw_cookie ) ? sanitize_key( (string) $raw_cookie ) : '';
		if ( $requested === $current_cookie ) {
			return;
		}

		setcookie(
			self::COOKIE_NAME,
			$requested,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN ?: '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}
}
