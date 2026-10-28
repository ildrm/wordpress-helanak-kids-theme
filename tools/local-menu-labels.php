<?php
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_menu = wp_get_nav_menu_object( 'Helanak local preview' );
$helanak_labels = array( 'خانه' => 'Home', 'فروشگاه' => 'Shop', 'پیشنهادها' => 'Offers', 'درباره هلانک' => 'About', 'تماس' => 'Contact' );
foreach ( wp_get_nav_menu_items( $helanak_menu->term_id ) as $helanak_item ) {
	if ( isset( $helanak_labels[ $helanak_item->title ] ) ) {
		update_post_meta( $helanak_item->ID, 'helanak_menu_label_fa', $helanak_item->title );
		update_post_meta( $helanak_item->ID, 'helanak_menu_label_en', $helanak_labels[ $helanak_item->title ] );
	}
}
echo 'Local navigation labels saved for both interface languages.' . PHP_EOL;
