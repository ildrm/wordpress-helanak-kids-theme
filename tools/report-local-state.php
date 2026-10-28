<?php
/** Write a non-sensitive handoff inventory of the dedicated local clone. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME || 'localhost' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'The dedicated localhost clone is required.' );
}

global $wpdb;
$helanak_state = array(
	'url'                => home_url( '/' ),
	'environment'        => wp_get_environment_type(),
	'database'           => DB_NAME,
	'wordpress'          => get_bloginfo( 'version' ),
	'php'                => PHP_VERSION,
	'woocommerce'        => defined( 'WC_VERSION' ) ? WC_VERSION : null,
	'theme'              => wp_get_theme()->get( 'Version' ),
	'core_plugin'        => defined( 'HELANAK_CORE_VERSION' ) ? HELANAK_CORE_VERSION : null,
	'active_plugins'     => get_option( 'active_plugins' ),
	'coming_soon'        => get_option( 'woocommerce_coming_soon' ),
	'public_indexing'    => get_option( 'blog_public' ),
	'cron_disabled'      => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
	'mail_guard_active'  => false !== has_filter( 'pre_wp_mail', '__return_false' ),
	'post_counts'        => $wpdb->get_results( "SELECT post_type, post_status, COUNT(*) AS count FROM {$wpdb->posts} GROUP BY post_type, post_status ORDER BY post_type, post_status", ARRAY_A ),
	'qa_records'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_helanak_qa' AND meta_value = '1'" ),
	'unpriced_products'  => array(),
);
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $helanak_product ) {
	if ( '' === $helanak_product->get_price() ) {
		$helanak_state['unpriced_products'][] = $helanak_product->get_id();
	}
}
sort( $helanak_state['unpriced_products'] );
$helanak_json = wp_json_encode( $helanak_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
file_put_contents( 'D:/prj/wordpress-helanak-kids-theme/.local/audit/local-state.json', $helanak_json );
echo $helanak_json . PHP_EOL;
