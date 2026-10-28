<?php
/** A disposable purchasable variable product; real draft prices stay intact. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_file = 'D:/prj/wordpress-helanak-kids-theme/.local/audit/qa-products.json';
$helanak_data = json_decode( file_get_contents( $helanak_file ), true );
if ( ! empty( $helanak_data['purchasable_variable'] ) ) { echo $helanak_data['purchasable_variable']; return; }
$helanak_attribute = new WC_Product_Attribute();
$helanak_attribute->set_name( 'Color' );
$helanak_attribute->set_options( array( 'Blue', 'Coral' ) );
$helanak_attribute->set_visible( true );
$helanak_attribute->set_variation( true );
$helanak_product = new WC_Product_Variable();
$helanak_product->set_name( 'Helanak temporary QA variable' );
$helanak_product->set_status( 'publish' );
$helanak_product->set_attributes( array( $helanak_attribute ) );
$helanak_product->set_tax_status( 'none' );
$helanak_parent = $helanak_product->save();
update_post_meta( $helanak_parent, '_helanak_qa', '1' );
$helanak_data['created'][] = $helanak_parent;
foreach ( array( 'Blue', 'Coral' ) as $helanak_color ) {
	$helanak_variation = new WC_Product_Variation();
	$helanak_variation->set_parent_id( $helanak_parent );
	$helanak_variation->set_attributes( array( 'color' => $helanak_color ) );
	$helanak_variation->set_status( 'publish' );
	$helanak_variation->set_regular_price( '100' );
	$helanak_variation->set_price( '100' );
	$helanak_variation->set_tax_status( 'none' );
	$helanak_data['created'][] = $helanak_variation->save();
}
WC_Product_Variable::sync( $helanak_parent );
$helanak_data['purchasable_variable'] = $helanak_parent;
file_put_contents( $helanak_file, wp_json_encode( $helanak_data, JSON_PRETTY_PRINT ) );
echo 'Purchasable variable fixture: ' . $helanak_parent . PHP_EOL;
