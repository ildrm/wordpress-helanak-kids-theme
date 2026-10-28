<?php
/** Data for a monetary shipping goal, using the customer's applicable zone. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Shipping_Goal {

	/**
	 * Multi-package carts deliberately omit a single monetary promise.
	 * WooCommerce's actual rate availability remains authoritative.
	 */
	public static function get_goal(): ?array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() || ! WC()->cart->needs_shipping() ) {
			return null;
		}
		$packages = WC()->cart->get_shipping_packages();
		if ( 1 !== count( $packages ) ) {
			return null;
		}
		$package = reset( $packages );
		$zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
		$goal    = null;
		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( 'free_shipping' !== $method->id ) {
				continue;
			}
			$requires  = (string) $method->get_option( 'requires', '' );
			$threshold = (float) $method->get_option( 'min_amount', 0 );
			$available = $method->is_available( $package );
			if ( $available ) {
				return array( 'threshold' => $threshold, 'remaining' => 0.0, 'percent' => 100, 'available' => true );
			}
			if ( $threshold <= 0 || ! in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) ) {
				continue;
			}
			$has_coupon = false;
			if ( 'both' === $requires ) {
				foreach ( WC()->cart->get_coupons() as $coupon ) {
					if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
						$has_coupon = true;
						break;
					}
				}
				if ( ! $has_coupon ) {
					continue;
				}
			}
			$total = (float) WC()->cart->get_displayed_subtotal();
			if ( 'no' === $method->get_option( 'ignore_discounts', 'no' ) ) {
				$total -= WC()->cart->get_discount_total();
				if ( WC()->cart->display_prices_including_tax() ) {
					$total -= WC()->cart->get_discount_tax();
				}
			}
			$total     = round( $total, wc_get_price_decimals() );
			if ( $total >= $threshold && ! $available ) {
				// A store filter may impose another condition; do not claim success.
				continue;
			}
			$candidate = array(
				'threshold' => $threshold,
				'remaining' => $available ? 0.0 : max( 0, $threshold - $total ),
				'percent'   => $available ? 100 : min( 100, max( 0, (int) round( $total / $threshold * 100 ) ) ),
				'available' => $available,
			);
			if ( null === $goal || $candidate['remaining'] < $goal['remaining'] ) {
				$goal = $candidate;
			}
		}
		return $goal;
	}
}
