<?php
/**
 * Styles/scripts. Fonts are loaded per-locale only (spec 5: "never ship all
 * four simultaneously") — Vazirmatn for Persian/Arabic-ish RTL locales,
 * Poppins for everything else. (Cairo/Nunito for Arabic/Turkish are listed
 * in the spec's font table for a future full i18n rollout — see the
 * Multi-language section of README.md — and are intentionally not wired up
 * yet since only English/Persian are live in this phase.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Theme_Assets {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_filter( 'body_class', array( __CLASS__, 'filter_body_class' ) );
	}

	/**
	 * Lets theme.css gate continuous ambient CSS keyframe animations (the
	 * characters' idle bob/sway) on the Customizer toggles server-side, so
	 * there's no JS-added-class flash of motion before scripts run.
	 */
	public static function filter_body_class( array $classes ): array {
		if ( ! Helanak_Character_Engine::master_enabled() ) {
			$classes[] = 'helanak-characters-off';
		}
		if ( ! get_theme_mod( 'helanak_character_ambient', true ) ) {
			$classes[] = 'helanak-ambient-off';
		}
		if ( ! get_theme_mod( 'helanak_character_idle', true ) ) {
			$classes[] = 'helanak-idle-off';
		}
		return $classes;
	}

	private static function is_fa_locale(): bool {
		return 'fa' === substr( get_locale(), 0, 2 );
	}

	/** Local edits should not be masked by the browser's release-version cache. */
	private static function asset_version( string $relative_file ): string {
		$file = HELANAK_THEME_DIR . $relative_file;
		if ( 'local' === wp_get_environment_type() && is_file( $file ) ) {
			return HELANAK_THEME_VERSION . '.' . (string) filemtime( $file );
		}
		return HELANAK_THEME_VERSION;
	}

	public static function enqueue(): void {
		wp_enqueue_style(
			'wp-helanak-kids-theme-style',
			get_stylesheet_uri(),
			array(),
			self::asset_version( '/style.css' )
		);

		wp_enqueue_style(
			'helanak-theme',
			HELANAK_THEME_URI . '/assets/css/theme.css',
			array( 'wp-helanak-kids-theme-style' ),
			self::asset_version( '/assets/css/theme.css' )
		);

		if ( is_rtl() ) {
			wp_enqueue_style(
				'helanak-theme-rtl',
				HELANAK_THEME_URI . '/assets/css/rtl.css',
				array( 'helanak-theme' ),
				self::asset_version( '/assets/css/rtl.css' )
			);
		}

		if ( self::is_fa_locale() ) {
			wp_enqueue_style(
				'helanak-font-vazirmatn',
				HELANAK_THEME_URI . '/assets/css/font-vazirmatn.css',
				array(),
				self::asset_version( '/assets/css/font-vazirmatn.css' )
			);
		} else {
			wp_enqueue_style(
				'helanak-font-poppins',
				HELANAK_THEME_URI . '/assets/css/font-poppins.css',
				array(),
				self::asset_version( '/assets/css/font-poppins.css' )
			);
		}

		$helanak_script_dependencies = array();
		if ( function_exists( 'WC' ) ) {
			wp_enqueue_script( 'wc-cart-fragments' );
			$helanak_script_dependencies[] = 'jquery';
			$helanak_script_dependencies[] = 'wc-cart-fragments';
		}
		wp_enqueue_script(
			'helanak-theme',
			HELANAK_THEME_URI . '/assets/js/theme.js',
			$helanak_script_dependencies,
			self::asset_version( '/assets/js/theme.js' ),
			true
		);

		wp_localize_script( 'helanak-theme', 'helanakSettings', array(
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'quickViewNonce'     => wp_create_nonce( 'helanak_quick_view' ),
			'compareNonce'       => wp_create_nonce( 'helanak_compare' ),
			'compareLabel'       => __( 'Compare', 'wp-helanak-kids-theme' ),
			'quickViewLabel'     => __( 'Quick view', 'wp-helanak-kids-theme' ),
			'loadingLabel'       => __( 'Loading…', 'wp-helanak-kids-theme' ),
			'loadErrorLabel'     => __( 'Unable to load this product. Please try again.', 'wp-helanak-kids-theme' ),
			'suggestionsLabel'   => __( 'Search suggestions', 'wp-helanak-kids-theme' ),
			'pauseMotionLabel'   => __( 'Pause animations', 'wp-helanak-kids-theme' ),
			'resumeMotionLabel'  => __( 'Resume animations', 'wp-helanak-kids-theme' ),
			'motionReducedLabel' => __( 'Reduced motion', 'wp-helanak-kids-theme' ),
			'friendHelloLabel'   => __( '%s says hello!', 'wp-helanak-kids-theme' ),
			'searchSuggestNonce' => wp_create_nonce( 'helanak_search_suggest' ),
			'wishlistLabel'    => __( 'Wishlist', 'wp-helanak-kids-theme' ),
			'closeLabel'       => __( 'Close', 'wp-helanak-kids-theme' ),
			'addedToCartLabel' => __( 'Added to cart', 'wp-helanak-kids-theme' ),
			'characters'       => Helanak_Character_Engine::enabled_characters(),
			'ambientEffects'   => (bool) get_theme_mod( 'helanak_character_ambient', true ),
			'idleBehaviors'    => (bool) get_theme_mod( 'helanak_character_idle', true ),
			// Server-rendered dog for the add-to-cart toast (the prototype's
			// dogLayer). Empty string when the dog/master toggle is off, which
			// theme.js treats as "no dog".
			'toastDogHtml'     => Helanak_Character_Engine::render_character( 'dog', array( 'scale' => 0.32 ) ),
			'reduceMotionNote' => __( 'Reduced motion is respected automatically via prefers-reduced-motion.', 'wp-helanak-kids-theme' ),
		) );

		// Quick View can be triggered from any product-card grid (shop, home,
		// related products), not just the single product page, so it's
		// enqueued globally rather than gated to is_product()/is_shop().
		$helanak_quick_dependencies = array( 'helanak-theme' );
		if ( wp_script_is( 'wc-add-to-cart-variation', 'registered' ) ) {
			$helanak_quick_dependencies[] = 'wc-add-to-cart-variation';
		}
		wp_enqueue_script(
			'helanak-quick-view',
			HELANAK_THEME_URI . '/assets/js/quick-view.js',
			$helanak_quick_dependencies,
			self::asset_version( '/assets/js/quick-view.js' ),
			true
		);

		// No-ops itself (via an early `return`) when the flash-sale countdown
		// markup isn't present on the page, so it's cheap to enqueue globally.
		wp_enqueue_script(
			'helanak-countdown',
			HELANAK_THEME_URI . '/assets/js/countdown.js',
			array(),
			self::asset_version( '/assets/js/countdown.js' ),
			true
		);

		// Wishlist toggle/count sync can appear in the header on every page
		// and on any product-card grid, so it's enqueued globally too.
		wp_enqueue_script(
			'helanak-wishlist',
			HELANAK_THEME_URI . '/assets/js/wishlist.js',
			array(),
			self::asset_version( '/assets/js/wishlist.js' ),
			true
		);

		// Compare reuses quick-view's modal shell (window.helanakOpenModal),
		// so it depends on that script having run first.
		wp_enqueue_script(
			'helanak-compare',
			HELANAK_THEME_URI . '/assets/js/compare.js',
			array( 'helanak-quick-view' ),
			self::asset_version( '/assets/js/compare.js' ),
			true
		);

		// No-ops itself when the header search input isn't on the page.
		wp_enqueue_script(
			'helanak-search-suggest',
			HELANAK_THEME_URI . '/assets/js/search-suggest.js',
			array( 'helanak-theme' ),
			self::asset_version( '/assets/js/search-suggest.js' ),
			true
		);
	}
}
