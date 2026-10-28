<?php
/** Feature real priced products in the local design preview; retain rollback data. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_originals = get_option( 'helanak_local_preview_originals', array() );
$helanak_count = 0;
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $helanak_product ) {
	if ( get_post_meta( $helanak_product->get_id(), '_helanak_qa', true ) || ! $helanak_product->is_purchasable() || ! $helanak_product->is_visible() ) { continue; }
	if ( ! isset( $helanak_originals['featured'][ $helanak_product->get_id() ] ) ) {
		$helanak_originals['featured'][ $helanak_product->get_id() ] = $helanak_product->get_featured();
	}
	$helanak_product->set_featured( true );
	$helanak_product->save();
	$helanak_count++;
}
update_option( 'helanak_local_preview_originals', $helanak_originals, false );
echo 'Real priced products featured in local preview: ' . $helanak_count . PHP_EOL;
