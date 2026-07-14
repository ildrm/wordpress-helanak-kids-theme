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
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
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

	public static function enqueue(): void {
		wp_enqueue_style(
			'wp-helanak-kids-theme-style',
			get_stylesheet_uri(),
			array(),
			HELANAK_THEME_VERSION
		);

		wp_enqueue_style(
			'helanak-theme',
			HELANAK_THEME_URI . '/assets/css/theme.css',
			array( 'wp-helanak-kids-theme-style' ),
			HELANAK_THEME_VERSION
		);

		if ( is_rtl() ) {
			wp_enqueue_style(
				'helanak-theme-rtl',
				HELANAK_THEME_URI . '/assets/css/rtl.css',
				array( 'helanak-theme' ),
				HELANAK_THEME_VERSION
			);
		}

		if ( self::is_fa_locale() ) {
			wp_enqueue_style(
				'helanak-font-vazirmatn',
				'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
				array(),
				null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL is itself versioned/cached.
			);
		} else {
			wp_enqueue_style(
				'helanak-font-poppins',
				'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap',
				array(),
				null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			);
		}

		wp_enqueue_script(
			'helanak-theme',
			HELANAK_THEME_URI . '/assets/js/theme.js',
			array(),
			HELANAK_THEME_VERSION,
			true
		);

		wp_localize_script( 'helanak-theme', 'helanakSettings', array(
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'quickViewNonce'     => wp_create_nonce( 'helanak_quick_view' ),
			'compareNonce'       => wp_create_nonce( 'helanak_compare' ),
			'compareLabel'       => __( 'Compare', 'wp-helanak-kids-theme' ),
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
		wp_enqueue_script(
			'helanak-quick-view',
			HELANAK_THEME_URI . '/assets/js/quick-view.js',
			array( 'helanak-theme' ),
			HELANAK_THEME_VERSION,
			true
		);

		// No-ops itself (via an early `return`) when the flash-sale countdown
		// markup isn't present on the page, so it's cheap to enqueue globally.
		wp_enqueue_script(
			'helanak-countdown',
			HELANAK_THEME_URI . '/assets/js/countdown.js',
			array(),
			HELANAK_THEME_VERSION,
			true
		);

		// Wishlist toggle/count sync can appear in the header on every page
		// and on any product-card grid, so it's enqueued globally too.
		wp_enqueue_script(
			'helanak-wishlist',
			HELANAK_THEME_URI . '/assets/js/wishlist.js',
			array(),
			HELANAK_THEME_VERSION,
			true
		);

		// Compare reuses quick-view's modal shell (window.helanakOpenModal),
		// so it depends on that script having run first.
		wp_enqueue_script(
			'helanak-compare',
			HELANAK_THEME_URI . '/assets/js/compare.js',
			array( 'helanak-quick-view' ),
			HELANAK_THEME_VERSION,
			true
		);

		// No-ops itself when the header search input isn't on the page.
		wp_enqueue_script(
			'helanak-search-suggest',
			HELANAK_THEME_URI . '/assets/js/search-suggest.js',
			array( 'helanak-theme' ),
			HELANAK_THEME_VERSION,
			true
		);
	}
}
