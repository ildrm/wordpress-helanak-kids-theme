<?php
/** Show merchant-assigned shop widgets without WordPress's generic sidebar fallback. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! is_active_sidebar( 'shop-sidebar' ) || ( function_exists( 'is_product' ) && is_product() ) ) {
	return;
}
?>
<aside class="helanak-shop-widgets helanak-container" aria-label="<?php esc_attr_e( 'Shop Sidebar', 'wp-helanak-kids-theme' ); ?>">
	<?php dynamic_sidebar( 'shop-sidebar' ); ?>
</aside>
