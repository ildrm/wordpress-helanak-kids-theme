<?php
/** Temporary local products for HTTP security and cart regression checks. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) {
	throw new RuntimeException( 'Local clone required.' );
}
$helanak_file = 'D:/prj/wordpress-helanak-kids-theme/.local/audit/qa-products.json';
if ( is_file( $helanak_file ) ) {
	echo file_get_contents( $helanak_file );
	return;
}
$helanak_data = array( 'created' => array(), 'public' => array(), 'variable' => null );
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $helanak_product ) {
	if ( $helanak_product->is_visible() ) { $helanak_data['public'][] = $helanak_product->get_id(); }
	if ( ! $helanak_data['variable'] && $helanak_product->is_type( 'variable' ) && $helanak_product->is_in_stock() ) {
		$helanak_data['variable'] = $helanak_product->get_id();
	}
}
foreach ( array( 'protected', 'hidden', 'simple' ) as $helanak_type ) {
	$helanak_product = new WC_Product_Simple();
	$helanak_product->set_name( 'Helanak temporary QA ' . $helanak_type );
	$helanak_product->set_status( 'publish' );
	$helanak_product->set_regular_price( '100' );
	$helanak_product->set_price( '100' );
	$helanak_product->set_short_description( 'HELANAK_QA_RESTRICTED_MARKER_' . $helanak_type );
	if ( 'hidden' === $helanak_type ) { $helanak_product->set_catalog_visibility( 'hidden' ); }
	$helanak_id = $helanak_product->save();
	update_post_meta( $helanak_id, '_helanak_qa', '1' );
	if ( 'protected' === $helanak_type ) { wp_update_post( array( 'ID' => $helanak_id, 'post_password' => 'helanak-local-qa' ) ); }
	$helanak_data[ $helanak_type ] = $helanak_id;
	$helanak_data['created'][] = $helanak_id;
}
file_put_contents( $helanak_file, wp_json_encode( $helanak_data, JSON_PRETTY_PRINT ) );
echo wp_json_encode( $helanak_data ) . PHP_EOL;
