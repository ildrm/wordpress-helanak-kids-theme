<?php
/** Local-only preview data setup, after the live clone has been backed up. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME || 'localhost' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'This script only targets the dedicated localhost clone.' );
}

$helanak_originals = get_option( 'helanak_local_preview_originals', array() );
if ( ! $helanak_originals ) {
	$helanak_originals = array( 'products' => array(), 'options' => array() );
	foreach ( array( 'show_on_front', 'page_on_front', 'page_for_posts', 'woocommerce_coming_soon' ) as $helanak_key ) {
		$helanak_originals['options'][ $helanak_key ] = get_option( $helanak_key );
	}
	$helanak_drafts = get_posts( array( 'post_type' => 'product', 'post_status' => 'draft', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $helanak_drafts as $helanak_id ) {
		$helanak_originals['products'][ $helanak_id ] = 'draft';
	}
	update_option( 'helanak_local_preview_originals', $helanak_originals, false );
}
foreach ( $helanak_originals['products'] as $helanak_id => $helanak_status ) {
	wp_update_post( array( 'ID' => $helanak_id, 'post_status' => 'publish' ) );
	wc_delete_product_transients( $helanak_id );
}

$helanak_pages = array(
	'home'    => array( 'title' => 'هلانک', 'content' => '', 'template' => '' ),
	'about'   => array( 'title' => 'درباره هلانک', 'content' => '<p>هلانک فروشگاه لباس و اسباب‌بازی کودکان است. محصولات را بر اساس دسته‌بندی، سن و مشخصات هر محصول بررسی کنید.</p>', 'template' => 'page-about.php' ),
	'contact' => array( 'title' => 'تماس با هلانک', 'content' => '<p>برای پرسش درباره محصولات یا سفارش، از فرم زیر استفاده کنید.</p>', 'template' => 'page-contact.php' ),
	'sale'    => array( 'title' => 'پیشنهادهای هلانک', 'content' => '', 'template' => 'page-sale.php' ),
	'journal' => array( 'title' => 'مجله هلانک', 'content' => '', 'template' => '' ),
);
$helanak_ids = array();
foreach ( $helanak_pages as $helanak_slug => $helanak_page ) {
	$helanak_existing = get_page_by_path( $helanak_slug );
	$helanak_id = $helanak_existing ? $helanak_existing->ID : wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $helanak_slug,
		'post_title'   => $helanak_page['title'],
		'post_content' => $helanak_page['content'],
	), true );
	if ( is_wp_error( $helanak_id ) ) { throw new RuntimeException( $helanak_id->get_error_message() ); }
	$helanak_ids[ $helanak_slug ] = (int) $helanak_id;
	if ( $helanak_page['template'] && ! $helanak_existing ) {
		update_post_meta( $helanak_id, '_wp_page_template', $helanak_page['template'] );
	}
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $helanak_ids['home'] );
update_option( 'page_for_posts', $helanak_ids['journal'] );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'blog_public', '0' );

$helanak_menu = wp_get_nav_menu_object( 'Helanak local preview' );
$helanak_menu_id = $helanak_menu ? $helanak_menu->term_id : wp_create_nav_menu( 'Helanak local preview' );
if ( ! $helanak_menu ) {
	$helanak_items = array(
		array( 'title' => 'خانه', 'url' => home_url( '/' ) ),
		array( 'title' => 'فروشگاه', 'url' => wc_get_page_permalink( 'shop' ) ),
		array( 'title' => 'پیشنهادها', 'url' => get_permalink( $helanak_ids['sale'] ) ),
		array( 'title' => 'درباره هلانک', 'url' => get_permalink( $helanak_ids['about'] ) ),
		array( 'title' => 'تماس', 'url' => get_permalink( $helanak_ids['contact'] ) ),
	);
	foreach ( $helanak_items as $helanak_item ) {
		wp_update_nav_menu_item( $helanak_menu_id, 0, array( 'menu-item-title' => $helanak_item['title'], 'menu-item-url' => $helanak_item['url'], 'menu-item-status' => 'publish' ) );
	}
}
$helanak_locations = get_theme_mod( 'nav_menu_locations', array() );
$helanak_locations['primary'] = $helanak_menu_id;
$helanak_locations['footer'] = $helanak_menu_id;
set_theme_mod( 'nav_menu_locations', $helanak_locations );
flush_rewrite_rules( false );
echo wp_json_encode( array( 'local_preview_products' => count( $helanak_originals['products'] ), 'pages' => $helanak_ids, 'menu_id' => $helanak_menu_id ) ) . PHP_EOL;
