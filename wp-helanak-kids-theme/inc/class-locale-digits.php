<?php
/**
 * Persian digit shaping: when the storefront is in Persian, numbers rendered
 * through WordPress/WooCommerce formatting (prices, counts, sold labels,
 * rating counts) display as ۰۱۲۳۴۵۶۷۸۹ — matching the prototype, which used
 * Persian numerals throughout its fa strings. Neither WP core's fa_IR locale
 * nor WooCommerce transliterate digit glyphs on their own, so this hooks the
 * two formatting funnels everything already flows through:
 *
 *  - number_format_i18n (core filter: every number_format_i18n() call —
 *    sold labels, rating counts, "%d items")
 *  - wc_price (every formatted WooCommerce price)
 *
 * The JS countdown localizes its own digits (see countdown.js) since it
 * ticks client-side. Latin digits in URLs, attributes, and form values are
 * untouched — only display output passes through these filters.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Locale_Digits {

	public static function init(): void {
		add_filter( 'number_format_i18n', array( __CLASS__, 'filter_number_format' ) );
		add_filter( 'wc_price', array( __CLASS__, 'filter_price' ) );
	}

	private static function is_fa(): bool {
		return function_exists( 'helanak_current_lang' ) && 'fa' === helanak_current_lang();
	}

	/** 0-9 (and the , . separators) to Persian equivalents. */
	public static function to_persian( string $text ): string {
		return strtr(
			$text,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		);
	}

	public static function filter_number_format( $formatted ) {
		return self::is_fa() ? self::to_persian( (string) $formatted ) : $formatted;
	}

	public static function filter_price( $price_html ) {
		return self::is_fa() ? self::to_persian( (string) $price_html ) : $price_html;
	}
}
