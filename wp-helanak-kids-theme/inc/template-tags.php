<?php
/**
 * Template-tag helpers that read the Customizer's visibility toggles
 * (registered in class-customizer.php) so every template gates its output
 * through one function instead of repeating get_theme_mod() calls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether a homepage/site section (helanak_show_{key}) is enabled. Defaults true unless the section is unknown. */
function helanak_show( string $section_key ): bool {
	$toggles = Helanak_Theme_Customizer::section_toggles();
	if ( ! array_key_exists( $section_key, $toggles ) ) {
		return true;
	}
	list( , $default ) = $toggles[ $section_key ];
	return (bool) get_theme_mod( "helanak_show_{$section_key}", $default );
}

/** Whether a product-card detail (helanak_card_show_{key}) is enabled. Defaults true unless the detail is unknown. */
function helanak_card_shows( string $detail_key ): bool {
	$toggles = Helanak_Theme_Customizer::card_toggles();
	if ( ! array_key_exists( $detail_key, $toggles ) ) {
		return true;
	}
	list( , $default ) = $toggles[ $detail_key ];
	return (bool) get_theme_mod( "helanak_card_show_{$detail_key}", $default );
}

/**
 * Echoes the `hidden` attribute when a section is toggled off. The wrapper
 * markup carrying data-helanak-section="{key}" must always render — never
 * behind an if/endif — because customizer-preview.js's toggleByAttribute()
 * only ever flips an existing element's `hidden` property; it has nothing
 * to un-hide if PHP omitted the markup entirely on page load.
 */
function helanak_section_hidden_attr( string $section_key ): void {
	echo helanak_show( $section_key ) ? '' : ' hidden';
}

/** Same live-preview contract as helanak_section_hidden_attr(), for data-helanak-card-part="{key}" wrappers. */
function helanak_card_hidden_attr( string $detail_key ): void {
	echo helanak_card_shows( $detail_key ) ? '' : ' hidden';
}

/** Echoes a mascot if enabled — thin wrapper so templates don't need to know the Character Engine class name. */
function helanak_character( string $key, array $args = array() ): void {
	echo Helanak_Character_Engine::render_character( $key, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- render_character() returns pre-escaped markup.
}

/** True when the current request should render right-to-left (Persian) chrome. */
function helanak_is_rtl_request(): bool {
	if ( class_exists( 'Helanak_Core_Language_Switcher' ) ) {
		return Helanak_Core_Language_Switcher::is_rtl();
	}
	return is_rtl();
}

/** Current storefront language ('en'|'fa'), falling back to 'en' if Helanak Core isn't active. */
function helanak_current_lang(): string {
	if ( class_exists( 'Helanak_Core_Language_Switcher' ) ) {
		return Helanak_Core_Language_Switcher::current_lang();
	}
	return 'en';
}

/** URL that switches the storefront to $lang, falling back to a plain ?lang= link if Helanak Core isn't active. */
function helanak_lang_switch_url( string $lang ): string {
	if ( class_exists( 'Helanak_Core_Language_Switcher' ) ) {
		return Helanak_Core_Language_Switcher::switch_url( $lang );
	}
	return add_query_arg( 'lang', $lang );
}

/** Localized age-range term label, falling back to the term's own name if Helanak Core isn't active. */
function helanak_age_label( WP_Term $term ): string {
	if ( class_exists( 'Helanak_Core_Age_Taxonomy' ) ) {
		return Helanak_Core_Age_Taxonomy::localized_label( $term, helanak_is_rtl_request() );
	}
	return $term->name;
}

/**
 * Cart-count badge markup — shared by the header cart icon, the floating
 * mini-cart button, and the woocommerce_add_to_cart_fragments filter so
 * every copy refreshes together after AJAX add-to-cart. Always rendered
 * (hidden at zero) so the fragment replace has an element to target even
 * when the visitor's first page view had an empty cart.
 */
function helanak_cart_count_badge(): string {
	$count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
	return sprintf(
		'<span class="helanak-count" data-helanak-cart-count%s>%s</span>',
		$count ? '' : ' hidden',
		esc_html( number_format_i18n( $count ) )
	);
}

/** Real word-count-based reading time estimate (~200 words/minute), never a placeholder number. */
function helanak_reading_time( $post ): string {
	$text    = wp_strip_all_tags( get_post_field( 'post_content', $post ) );
	$words   = count( array_filter( preg_split( '/\s+/u', trim( $text ) ) ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );
	return sprintf(
		/* translators: %d: number of minutes */
		_n( '%d min read', '%d min read', $minutes, 'wp-helanak-kids-theme' ),
		$minutes
	);
}
