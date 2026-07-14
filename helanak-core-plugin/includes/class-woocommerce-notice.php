<?php
/**
 * Guards the rest of the plugin behind an active WooCommerce install, since
 * every module here (taxonomy, product fields, badges) hangs off WooCommerce
 * post types/hooks. Without this, activating the plugin on a site without
 * WooCommerce would fatal on `register_taxonomy( ..., 'product', ... )`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_WooCommerce_Notice {

	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	public static function init(): void {
		add_action( 'admin_notices', array( __CLASS__, 'render_notice' ) );
	}

	public static function render_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				esc_html_e(
					'Helanak Core requires WooCommerce to be installed and active. Its taxonomy, product-field, and badge features are disabled until then.',
					'helanak-core'
				);
				?>
			</p>
		</div>
		<?php
	}
}
