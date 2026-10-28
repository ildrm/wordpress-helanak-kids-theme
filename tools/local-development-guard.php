<?php
/**
 * Local-only safeguards for the cloned store. Install as an MU plugin in XAMPP.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
add_filter( 'woocommerce_webhook_should_deliver', '__return_false', PHP_INT_MAX );
add_filter( 'pre_http_request', function ( $response, $args, $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
		return $response;
	}
	return new WP_Error( 'helanak_local_external_request', 'External requests are disabled in this local development clone.' );
}, PHP_INT_MAX, 3 );
add_filter( 'woocommerce_available_payment_gateways', function ( $gateways ) {
	return array_intersect_key( $gateways, array_flip( array( 'cod', 'bacs', 'cheque' ) ) );
}, PHP_INT_MAX );
