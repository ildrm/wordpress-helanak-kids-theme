<?php
/**
 * Declares compatibility with WooCommerce's High-Performance Order Storage
 * (custom order tables) and the Cart/Checkout Blocks, per the platform's
 * "fully HPOS compatible" foundational decision.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_HPOS_Compat {

	public static function init(): void {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_compatibility' ) );
	}

	public static function declare_compatibility(): void {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			HELANAK_CORE_FILE,
			true
		);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			HELANAK_CORE_FILE,
			true
		);
	}
}
