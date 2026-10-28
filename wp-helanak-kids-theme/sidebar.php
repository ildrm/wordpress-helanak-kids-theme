<?php
/** Avoid WordPress's generic fallback widgets in the storefront. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
	get_template_part( 'sidebar', 'shop' );
}
