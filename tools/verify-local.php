<?php
/** Integration checks using disposable records in the dedicated local clone. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME || 'localhost' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'Refusing to run outside the dedicated localhost clone.' );
}

function helanak_qa_assert( $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	echo 'PASS ' . $message . PHP_EOL;
}

$helanak_ids     = array();
$helanak_zones   = array();
$helanak_methods = array();
$helanak_options = array();
$helanak_filters = array();
$helanak_email   = 'helanak-qa-' . time() . '@example.invalid';
$helanak_customer = WC()->customer;
$helanak_cart     = WC()->cart;
try {
	foreach ( array( 'woocommerce_ship_to_countries' => '', 'woocommerce_enable_coupons' => 'yes' ) as $key => $value ) {
		$helanak_options[ $key ] = get_option( $key );
		update_option( $key, $value );
	}
	if ( ! WC()->session ) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}
	WC()->customer = new WC_Customer();
	WC()->customer->set_shipping_country( 'AQ' );
	WC()->customer->set_billing_country( 'AQ' );
	WC()->customer->set_shipping_postcode( '00000' );
	WC()->cart = new WC_Cart();
	$product = new WC_Product_Simple();
	$product->set_name( 'Helanak disposable QA product' );
	$product->set_status( 'publish' );
	$product->set_regular_price( '100' );
	$product->set_price( '100' );
	$product->set_tax_status( 'none' );
	$helanak_ids[] = $product->save();
	WC()->cart->add_to_cart( $product->get_id() );

	foreach ( array( 'AQ' => 150, 'US' => 5 ) as $country => $minimum ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Helanak disposable QA ' . $country );
		$zone->set_zone_order( -100 );
		$zone->save();
		$zone->add_location( $country, 'country' );
		$zone->save();
		$helanak_zones[] = $zone;
		$instance = $zone->add_shipping_method( 'free_shipping' );
		$helanak_methods[ $country ] = $instance;
		update_option( 'woocommerce_free_shipping_' . $instance . '_settings', array( 'enabled' => 'yes', 'requires' => 'min_amount', 'min_amount' => $minimum, 'ignore_discounts' => 'no' ) );
	}
	WC()->cart->calculate_totals();
	$goal = Helanak_Core_Shipping_Goal::get_goal();
	helanak_qa_assert( $goal && 150.0 === $goal['threshold'] && 50.0 === $goal['remaining'], 'Shipping uses the matching zone, excluding a cheaper unrelated zone.' );

	$coupon = new WC_Coupon();
	$coupon->set_code( 'helanak-qa-discount-' . time() );
	$coupon->set_discount_type( 'fixed_cart' );
	$coupon->set_amount( 20 );
	$helanak_ids[] = $coupon->save();
	WC()->cart->apply_coupon( $coupon->get_code() );
	WC()->cart->calculate_totals();
	$goal = Helanak_Core_Shipping_Goal::get_goal();
	helanak_qa_assert( $goal && 70.0 === $goal['remaining'], 'Shipping minimum respects coupon discounts.' );

	$settings = get_option( 'woocommerce_free_shipping_' . $helanak_methods['AQ'] . '_settings' );
	$settings['requires'] = 'both';
	update_option( 'woocommerce_free_shipping_' . $helanak_methods['AQ'] . '_settings', $settings );
	helanak_qa_assert( null === Helanak_Core_Shipping_Goal::get_goal(), 'A missing required shipping coupon suppresses the monetary promise.' );
	$coupon->set_free_shipping( true );
	$coupon->save();
	WC()->cart->remove_coupons();
	WC()->cart->apply_coupon( $coupon->get_code() );
	WC()->cart->calculate_totals();
	$goal = Helanak_Core_Shipping_Goal::get_goal();
	helanak_qa_assert( $goal && 70.0 === $goal['remaining'] && ! $goal['available'], 'Coupon plus minimum still requires the full eligible amount.' );
	$settings['requires'] = 'either';
	update_option( 'woocommerce_free_shipping_' . $helanak_methods['AQ'] . '_settings', $settings );
	$goal = Helanak_Core_Shipping_Goal::get_goal();
	helanak_qa_assert( $goal && $goal['available'] && 0.0 === $goal['remaining'], 'An eligible either-rule coupon unlocks free shipping.' );

	$split = static function ( $packages ) { $packages[] = reset( $packages ); return $packages; };
	add_filter( 'woocommerce_cart_shipping_packages', $split );
	$helanak_filters[] = array( 'woocommerce_cart_shipping_packages', $split );
	helanak_qa_assert( null === Helanak_Core_Shipping_Goal::get_goal(), 'Multiple packages never receive an unsupported single shipping promise.' );
	remove_filter( 'woocommerce_cart_shipping_packages', $split );

	WC()->cart->remove_coupons();
	WC()->cart->set_quantity( array_key_first( WC()->cart->get_cart() ), 2 );
	WC()->cart->calculate_totals();
	$blocked = static function () { return false; };
	add_filter( 'woocommerce_shipping_free_shipping_is_available', $blocked );
	$helanak_filters[] = array( 'woocommerce_shipping_free_shipping_is_available', $blocked );
	helanak_qa_assert( null === Helanak_Core_Shipping_Goal::get_goal(), 'A store availability filter prevents a false unlocked-shipping claim.' );
	remove_filter( 'woocommerce_shipping_free_shipping_is_available', $blocked );

	$errors = Helanak_Core_Store_Forms::validate_contact( array( 'name' => '', 'email' => 'invalid', 'subject' => '', 'message' => '' ) );
	helanak_qa_assert( isset( $errors['name'], $errors['email'], $errors['message'] ), 'Invalid contact fields produce specific validation errors.' );
	helanak_qa_assert( false === Helanak_Core_Store_Forms::send_contact( array( 'name' => 'QA', 'email' => $helanak_email, 'subject' => 'Local QA', 'message' => 'No email should leave localhost.' ) ), 'Local mail failure propagates to the contact handler.' );
	helanak_qa_assert( 'success' === Helanak_Core_Store_Forms::subscribe( strtoupper( $helanak_email ) ) && 'success' === Helanak_Core_Store_Forms::subscribe( $helanak_email ), 'Repeated subscriptions are idempotent across email case.' );
	global $wpdb;
	$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}helanak_subscribers WHERE email = %s", $helanak_email ) );
	helanak_qa_assert( 1 === $count && 'invalid' === Helanak_Core_Store_Forms::subscribe( 'invalid' ), 'Subscriptions store one valid row and reject malformed addresses.' );
	$key = 'helanak_form_' . hash_hmac( 'sha256', 'qa-rate|unknown', wp_salt() );
	delete_transient( $key );
	$ip = $_SERVER['REMOTE_ADDR'] ?? null;
	unset( $_SERVER['REMOTE_ADDR'] );
	for ( $i = 0; $i < 5; $i++ ) {
		helanak_qa_assert( '' === Helanak_Core_Store_Forms::submission_guard( 'qa-rate', '' ), 'A permitted form attempt is accepted.' );
	}
	helanak_qa_assert( 'limited' === Helanak_Core_Store_Forms::submission_guard( 'qa-rate', '' ) && 'spam' === Helanak_Core_Store_Forms::submission_guard( 'qa-rate', 'bot' ), 'Form bursts and honeypot submissions are rejected.' );
	delete_transient( $key );
	if ( null !== $ip ) { $_SERVER['REMOTE_ADDR'] = $ip; }
	helanak_qa_assert( is_wp_error( wp_remote_get( 'https://helanak.com/' ) ), 'The local clone blocks outbound production HTTP requests.' );
	helanak_qa_assert( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ), 'The local image editor supports optimized WebP sizes.' );
	echo 'Local integration checks complete.' . PHP_EOL;
} finally {
	foreach ( $helanak_filters as $filter ) { remove_filter( $filter[0], $filter[1] ); }
	if ( WC()->cart ) { WC()->cart->empty_cart( false ); }
	WC()->cart = $helanak_cart;
	WC()->customer = $helanak_customer;
	foreach ( $helanak_ids as $id ) { wp_delete_post( $id, true ); }
	foreach ( $helanak_zones as $zone ) { $zone->delete(); }
	foreach ( $helanak_methods as $instance ) { delete_option( 'woocommerce_free_shipping_' . $instance . '_settings' ); }
	foreach ( $helanak_options as $key => $value ) { update_option( $key, $value ); }
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'helanak_subscribers', array( 'email' => $helanak_email ), array( '%s' ) );
}
