<?php
/**
 * Plugin Name: Helanak Core
 * Plugin URI: https://helanak.com
 * Description: Business logic for the Helanak Kids commerce platform: age-range taxonomy, product safety/compliance fields, badge engine, category theme (visual skin) assignment, and WooCommerce HPOS compatibility. Presentation lives in the wp-helanak-kids-theme theme; this plugin never renders markup directly, only exposes data/helpers.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Helanak
 * Author URI: https://helanak.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: helanak-core
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HELANAK_CORE_VERSION', '0.1.0' );
define( 'HELANAK_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'HELANAK_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'HELANAK_CORE_FILE', __FILE__ );

require_once HELANAK_CORE_DIR . 'includes/class-language-switcher.php';
require_once HELANAK_CORE_DIR . 'includes/class-hpos-compat.php';
require_once HELANAK_CORE_DIR . 'includes/class-woocommerce-notice.php';
require_once HELANAK_CORE_DIR . 'includes/class-age-taxonomy.php';
require_once HELANAK_CORE_DIR . 'includes/class-category-theme.php';
require_once HELANAK_CORE_DIR . 'includes/class-product-safety-fields.php';
require_once HELANAK_CORE_DIR . 'includes/class-badge-engine.php';

/**
 * Boot the plugin once all plugins are loaded, so the WooCommerce-active
 * check in Helanak_Core_WooCommerce_Notice is reliable.
 */
function helanak_core_init(): void {
	load_plugin_textdomain( 'helanak-core', false, dirname( plugin_basename( HELANAK_CORE_FILE ) ) . '/languages' );

	if ( ! Helanak_Core_WooCommerce_Notice::is_woocommerce_active() ) {
		Helanak_Core_WooCommerce_Notice::init();
		return;
	}

	Helanak_Core_Age_Taxonomy::init();
	Helanak_Core_Category_Theme::init();
	Helanak_Core_Product_Safety_Fields::init();
	Helanak_Core_Badge_Engine::init();
}
add_action( 'plugins_loaded', 'helanak_core_init' );

Helanak_Core_Language_Switcher::init();
Helanak_Core_HPOS_Compat::init();

/**
 * Seed default taxonomy terms on activation. Requires WooCommerce to already
 * be active so the 'product' post type / 'product_cat' taxonomy exist; if it
 * isn't, the admin notice from Helanak_Core_WooCommerce_Notice explains why
 * nothing was seeded and the seed simply runs again next time the plugin is
 * reactivated with WooCommerce present.
 */
function helanak_core_activate(): void {
	if ( Helanak_Core_WooCommerce_Notice::is_woocommerce_active() ) {
		Helanak_Core_Age_Taxonomy::register();
		Helanak_Core_Age_Taxonomy::seed_terms();
	}
	flush_rewrite_rules();
}
register_activation_hook( HELANAK_CORE_FILE, 'helanak_core_activate' );

function helanak_core_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( HELANAK_CORE_FILE, 'helanak_core_deactivate' );
