<?php
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_report = array();
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $helanak_product ) {
	if ( get_post_meta( $helanak_product->get_id(), '_helanak_qa', true ) ) { continue; }
	$helanak_report[] = array(
		'id' => $helanak_product->get_id(),
		'name' => $helanak_product->get_name(),
		'type' => $helanak_product->get_type(),
		'price' => $helanak_product->get_price(),
		'stock' => $helanak_product->get_stock_status(),
		'purchasable' => $helanak_product->is_purchasable(),
		'available_variations' => $helanak_product->is_type( 'variable' ) ? count( $helanak_product->get_available_variations() ) : 0,
		'variation_attributes' => $helanak_product->is_type( 'variable' ) ? $helanak_product->get_variation_attributes() : array(),
		'first_child_attributes' => $helanak_product->is_type( 'variable' ) && $helanak_product->get_children() ? wc_get_product( $helanak_product->get_children()[0] )->get_attributes() : array(),
	);
}
file_put_contents( 'D:/prj/wordpress-helanak-kids-theme/.local/audit/catalog-report.json', wp_json_encode( $helanak_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
echo wp_json_encode( $helanak_report, JSON_UNESCAPED_UNICODE ) . PHP_EOL;
