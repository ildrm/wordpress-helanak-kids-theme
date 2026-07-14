<?php
/**
 * Applies the archive filter sidebar's age/color selections (see
 * woocommerce/archive-product.php), plus the client-side wishlist's
 * ?helanak_wishlist=1,2,3 (see assets/js/wishlist.js), to the main product
 * query. Price filtering needs no code here — WooCommerce's own WC_Query
 * already reads $_GET['min_price']/$_GET['max_price'] on any product query.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Archive_Filters {

	public static function init(): void {
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_filters' ) );
	}

	public static function apply_filters( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'is_product_taxonomy' ) ) {
			return;
		}
		if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		$tax_query = $query->get( 'tax_query' ) ?: array();

		if ( ! empty( $_GET['helanak_age'] ) && taxonomy_exists( 'age_range' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$tax_query[] = array(
				'taxonomy' => 'age_range',
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) wp_unslash( $_GET['helanak_age'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
			);
		}

		if ( ! empty( $_GET['helanak_color'] ) && taxonomy_exists( 'pa_color' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$tax_query[] = array(
				'taxonomy' => 'pa_color',
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) wp_unslash( $_GET['helanak_color'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
			);
		}

		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}

		if ( ! empty( $_GET['helanak_wishlist'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['helanak_wishlist'] ) ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		}
	}
}
