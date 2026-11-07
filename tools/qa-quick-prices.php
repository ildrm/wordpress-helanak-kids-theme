<?php
/** Disposable local-only regression checks for variation Quick Edit prices. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME || 'localhost' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'The dedicated local clone is required.' );
}
$helanak_file = 'D:/prj/wordpress-helanak-kids-theme/.local/audit/quick-edit-prices/fixture.json';
$helanak_mode = $args[0] ?? 'create';
$helanak_data = is_file( $helanak_file ) ? json_decode( file_get_contents( $helanak_file ), true ) : array();
if ( 'cleanup' === $helanak_mode ) {
	foreach ( array_reverse( $helanak_data['created'] ?? array() ) as $helanak_id ) {
		if ( '1' !== get_post_meta( $helanak_id, '_helanak_qa', true ) ) {
			throw new RuntimeException( 'The fixture ownership marker is missing.' );
		}
		wp_delete_post( $helanak_id, true );
	}
	if ( is_file( $helanak_file ) ) {
		rename( $helanak_file, dirname( $helanak_file ) . '/fixture-completed-' . time() . '.json' );
	}
	echo 'Disposable Quick Edit products removed.' . PHP_EOL;
	return;
}
if ( ! $helanak_data ) {
	$helanak_data = array( 'created' => array() );
	foreach ( array( 'parent', 'other' ) as $helanak_key ) {
		$helanak_product = new WC_Product_Variable();
		$helanak_product->set_name( 'HELANAK_QUICK_PRICE_QA_' . $helanak_key );
		$helanak_product->set_status( 'draft' );
		$helanak_attribute = new WC_Product_Attribute();
		$helanak_attribute->set_name( 'Size' );
		$helanak_attribute->set_options( array( 'Small', 'Large' ) );
		$helanak_attribute->set_visible( true );
		$helanak_attribute->set_variation( true );
		$helanak_product->set_attributes( array( $helanak_attribute ) );
		$helanak_id = $helanak_product->save();
		update_post_meta( $helanak_id, '_helanak_qa', '1' );
		$helanak_data[ $helanak_key ] = $helanak_id;
		$helanak_data['created'][] = $helanak_id;
	}
	foreach ( array( 'a' => 'parent', 'b' => 'parent', 'foreign' => 'other' ) as $helanak_key => $helanak_parent ) {
		$helanak_product = new WC_Product_Variation();
		$helanak_product->set_parent_id( $helanak_data[ $helanak_parent ] );
		$helanak_product->set_attributes( array( 'size' => 'b' === $helanak_key ? 'Large' : 'Small' ) );
		$helanak_product->set_regular_price( 'b' === $helanak_key ? '200' : '100' );
		$helanak_product->set_price( 'b' === $helanak_key ? '200' : '100' );
		if ( 'a' === $helanak_key ) {
			$helanak_product->set_sale_price( '80' );
			$helanak_product->set_date_on_sale_from( time() + DAY_IN_SECONDS );
			$helanak_product->set_date_on_sale_to( time() + 2 * DAY_IN_SECONDS );
		}
		$helanak_id = $helanak_product->save();
		update_post_meta( $helanak_id, '_helanak_qa', '1' );
		$helanak_data[ $helanak_key ] = $helanak_id;
		$helanak_data['created'][] = $helanak_id;
	}
	$helanak_product = new WC_Product_Simple();
	$helanak_product->set_name( 'HELANAK_QUICK_PRICE_QA_simple' );
	$helanak_product->set_status( 'draft' );
	$helanak_product->set_regular_price( '50' );
	$helanak_product->set_sale_price( '40' );
	$helanak_id = $helanak_product->save();
	update_post_meta( $helanak_id, '_helanak_qa', '1' );
	$helanak_data['simple'] = $helanak_id;
	$helanak_data['created'][] = $helanak_id;
	WC_Product_Variable::sync( $helanak_data['parent'] );
	WC_Product_Variable::sync( $helanak_data['other'] );
	file_put_contents( $helanak_file, wp_json_encode( $helanak_data, JSON_PRETTY_PRINT ) );
}
if ( 'create' === $helanak_mode ) {
	echo wp_json_encode( $helanak_data ) . PHP_EOL;
	return;
}
if ( 'verify' !== $helanak_mode ) {
	throw new RuntimeException( 'Unknown local QA action.' );
}

function helanak_price_assert( $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	echo 'PASS ' . $message . PHP_EOL;
}

wp_set_current_user( get_user_by( 'login', 'admin' )->ID );
$helanak_a = wc_get_product( $helanak_data['a'] );
$helanak_original = Helanak_Core_Quick_Edit_Prices::fingerprint( $helanak_a );
$helanak_dates = array( $helanak_a->get_date_on_sale_from()->getTimestamp(), $helanak_a->get_date_on_sale_to()->getTimestamp() );
$helanak_payload = array( $helanak_data['a'] => array( 'regular' => '۱۱۰', 'sale' => '۷۰', 'original' => $helanak_original ) );
helanak_price_assert( '6800000' === Helanak_Core_Quick_Edit_Prices::price( '۶٬۸۰۰٬۰۰۰' ), 'Persian digits and grouped price normalize correctly.' );
helanak_price_assert( '6800000' === Helanak_Core_Quick_Edit_Prices::price( '٦٨٠٠٠٠٠' ), 'Arabic digits normalize correctly.' );
helanak_price_assert( '0' === Helanak_Core_Quick_Edit_Prices::price( '۰' ), 'Zero is a valid regular price.' );
helanak_price_assert( '12.5' === Helanak_Core_Quick_Edit_Prices::price( '12' . wc_get_price_decimal_separator() . '5' ), 'The configured decimal separator is supported.' );
foreach ( array( '-1', '100abc', '1,,2', array() ) as $helanak_invalid ) {
	helanak_price_assert( is_wp_error( Helanak_Core_Quick_Edit_Prices::price( $helanak_invalid ) ), 'Invalid price input is rejected.' );
}
$helanak_bad = $helanak_payload;
$helanak_bad[ $helanak_data['a'] ]['sale'] = '۱۱۰';
helanak_price_assert( is_wp_error( Helanak_Core_Quick_Edit_Prices::changes( $helanak_data['parent'], $helanak_bad ) ), 'A sale price at or above regular price is rejected.' );
$helanak_bad = $helanak_payload;
$helanak_bad[ $helanak_data['foreign'] ] = array( 'regular' => '100', 'sale' => '', 'original' => Helanak_Core_Quick_Edit_Prices::fingerprint( wc_get_product( $helanak_data['foreign'] ) ) );
helanak_price_assert( is_wp_error( Helanak_Core_Quick_Edit_Prices::changes( $helanak_data['parent'], $helanak_bad ) ), 'A variation belonging to another parent rejects the batch.' );
helanak_price_assert( '100' === wc_get_product( $helanak_data['a'] )->get_regular_price(), 'Rejected batches do not modify otherwise valid rows.' );
$helanak_bad = $helanak_payload;
$helanak_bad[ $helanak_data['a'] ]['original'] = str_repeat( '0', 64 );
helanak_price_assert( is_wp_error( Helanak_Core_Quick_Edit_Prices::changes( $helanak_data['parent'], $helanak_bad ) ), 'Stale price snapshots are rejected.' );
wp_set_current_user( 0 );
helanak_price_assert( is_wp_error( Helanak_Core_Quick_Edit_Prices::changes( $helanak_data['parent'], $helanak_payload ) ), 'An unauthorized user cannot change variation prices.' );
wp_set_current_user( get_user_by( 'login', 'admin' )->ID );
$helanak_post_before = $_POST;
$helanak_request_before = $_REQUEST;
try {
	$_POST = array(
		'post_ID' => $helanak_data['parent'],
		'helanak_variation_prices' => wp_slash( wp_json_encode( $helanak_payload ) ),
		'helanak_quick_price_nonce' => wp_create_nonce( Helanak_Core_Quick_Edit_Prices::NONCE_ACTION ),
		'_inline_edit' => wp_create_nonce( 'inlineeditnonce' ),
	);
	$_REQUEST = $_POST;
	Helanak_Core_Quick_Edit_Prices::validate_request();
	Helanak_Core_Quick_Edit_Prices::save( wc_get_product( $helanak_data['parent'] ) );
} finally {
	$_POST = $helanak_post_before;
	$_REQUEST = $helanak_request_before;
}
$helanak_a = wc_get_product( $helanak_data['a'] );
helanak_price_assert( '110' === $helanak_a->get_regular_price() && '70' === $helanak_a->get_sale_price(), 'The edited row saves regular and sale prices through WooCommerce CRUD.' );
helanak_price_assert( '200' === wc_get_product( $helanak_data['b'] )->get_regular_price(), 'An unedited variation retains its own price.' );
helanak_price_assert( $helanak_dates === array( $helanak_a->get_date_on_sale_from()->getTimestamp(), $helanak_a->get_date_on_sale_to()->getTimestamp() ), 'Existing sale dates are retained.' );
helanak_price_assert( '110' === $helanak_a->get_price(), 'A future sale keeps the regular price active.' );
echo 'Local Quick Edit pricing regression checks passed.' . PHP_EOL;
