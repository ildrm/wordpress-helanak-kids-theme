<?php
/**
 * Theme bootstrap. Presentation only — all business logic (age taxonomy,
 * product safety fields, badge evaluation, category-theme meta) lives in the
 * companion "Helanak Core" plugin and is consumed here only through its
 * documented helper functions/classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HELANAK_THEME_VERSION', '0.7.0' );
define( 'HELANAK_THEME_DIR', get_template_directory() );
define( 'HELANAK_THEME_URI', get_template_directory_uri() );

require_once HELANAK_THEME_DIR . '/inc/class-theme-setup.php';
require_once HELANAK_THEME_DIR . '/inc/class-assets.php';
require_once HELANAK_THEME_DIR . '/inc/class-blog-query.php';
require_once HELANAK_THEME_DIR . '/inc/class-customizer.php';
require_once HELANAK_THEME_DIR . '/inc/class-season-engine.php';
require_once HELANAK_THEME_DIR . '/inc/class-character-engine.php';
require_once HELANAK_THEME_DIR . '/inc/class-quick-view.php';
require_once HELANAK_THEME_DIR . '/inc/class-newsletter.php';
require_once HELANAK_THEME_DIR . '/inc/class-contact-form.php';
require_once HELANAK_THEME_DIR . '/inc/class-archive-filters.php';
require_once HELANAK_THEME_DIR . '/inc/class-single-product-hooks.php';
require_once HELANAK_THEME_DIR . '/inc/class-compare.php';
require_once HELANAK_THEME_DIR . '/inc/class-search-suggest.php';
require_once HELANAK_THEME_DIR . '/inc/class-recently-viewed.php';
require_once HELANAK_THEME_DIR . '/inc/class-shipping-goal.php';
require_once HELANAK_THEME_DIR . '/inc/class-celebration.php';
require_once HELANAK_THEME_DIR . '/inc/class-locale-digits.php';
require_once HELANAK_THEME_DIR . '/inc/template-tags.php';

Helanak_Theme_Setup::init();
Helanak_Theme_Assets::init();
Helanak_Theme_Blog_Query::init();
Helanak_Theme_Customizer::init();
Helanak_Season_Engine::init();
Helanak_Character_Engine::init();
Helanak_Quick_View::init();
Helanak_Newsletter::init();
Helanak_Contact_Form::init();
Helanak_Archive_Filters::init();
Helanak_Single_Product_Hooks::init();
Helanak_Compare::init();
Helanak_Search_Suggest::init();
Helanak_Recently_Viewed::init();
Helanak_Shipping_Goal::init();
Helanak_Celebration::init();
Helanak_Locale_Digits::init();

/**
 * Keeps every cart-count badge (header icon + floating mini-cart button) in
 * sync after AJAX add-to-cart via WooCommerce's own cart-fragments refresh —
 * without this the badges stay stale until the next full page load.
 */
function helanak_theme_cart_count_fragment( array $fragments ): array {
	$fragments['span[data-helanak-cart-count]'] = helanak_cart_count_badge();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'helanak_theme_cart_count_fragment' );

/**
 * The theme is designed around Helanak Core for the age taxonomy, product
 * safety fields, and badge engine. It still renders without it (all template
 * tags fall back to sensible defaults), but nag the admin so the gap doesn't
 * go unnoticed.
 */
function helanak_theme_missing_plugin_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) || ( class_exists( 'Helanak_Core_Age_Taxonomy' ) && class_exists( 'Helanak_Core_Store_Forms' ) ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<?php
			esc_html_e(
				'Activate Helanak Core 0.3.0 or later for store forms, shipping progress, product safety, badges, and age filters. Storefront pages remain available when the companion plugin is missing.',
				'wp-helanak-kids-theme'
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'helanak_theme_missing_plugin_notice' );

/**
 * WooCommerce itself is a hard dependency (this is a WooCommerce theme, not
 * just WooCommerce-aware) — templates under woocommerce/ and the header/
 * footer cart chrome call wc_*() functions directly rather than guarding
 * every call, matching the convention of other WooCommerce themes (e.g.
 * Storefront). Only nag if it's missing; don't fatal.
 */
function helanak_theme_missing_woocommerce_notice(): void {
	if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'WP Helanak Kids Theme requires WooCommerce to be installed and active.', 'wp-helanak-kids-theme' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'helanak_theme_missing_woocommerce_notice' );
