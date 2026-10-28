<?php
/**
 * Plugin Name: Helanak Core
 * Plugin URI: https://helanak.com
 * Description: Store data and rules for Helanak Kids: age ranges, product safety, badges, languages, shipping eligibility, store forms, image optimization, and WooCommerce HPOS compatibility. Storefront presentation belongs to the Helanak theme.
 * Version: 0.3.0
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

define( 'HELANAK_CORE_VERSION', '0.3.0' );
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
require_once HELANAK_CORE_DIR . 'includes/class-migrator.php';
require_once HELANAK_CORE_DIR . 'includes/class-store-forms.php';
require_once HELANAK_CORE_DIR . 'includes/class-shipping-goal.php';
require_once HELANAK_CORE_DIR . 'includes/class-media.php';

/**
 * Boot the plugin once all plugins are loaded, so the WooCommerce-active
 * check in Helanak_Core_WooCommerce_Notice is reliable.
 */
function helanak_core_init(): void {
	load_plugin_textdomain( 'helanak-core', false, dirname( plugin_basename( HELANAK_CORE_FILE ) ) . '/languages' );
	Helanak_Core_Store_Forms::init();
	Helanak_Core_Media::init();

	if ( ! Helanak_Core_WooCommerce_Notice::is_woocommerce_active() ) {
		Helanak_Core_WooCommerce_Notice::init();
		return;
	}

	Helanak_Core_Age_Taxonomy::init();
	Helanak_Core_Category_Theme::init();
	Helanak_Core_Product_Safety_Fields::init();
	Helanak_Core_Badge_Engine::init();
	Helanak_Core_Migrator::init();
}
add_action( 'plugins_loaded', 'helanak_core_init' );

Helanak_Core_Language_Switcher::init();
Helanak_Core_HPOS_Compat::init();

/**
 * Install or migrate dynamic taxonomy data on activation.
 */
function helanak_core_activate(): void {
	if ( Helanak_Core_WooCommerce_Notice::is_woocommerce_active() ) {
		Helanak_Core_Migrator::activate();
	}
	flush_rewrite_rules();
}
register_activation_hook( HELANAK_CORE_FILE, 'helanak_core_activate' );

function helanak_core_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( HELANAK_CORE_FILE, 'helanak_core_deactivate' );
