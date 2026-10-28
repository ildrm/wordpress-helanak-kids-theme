<?php
/**
 * "Recently viewed" rail on single product pages: tracks the last few
 * products a visitor opened in a plain first-party cookie (readable
 * server-side — no AJAX round-trip needed) and renders them under the
 * product via the shared content-product.php card partial. The current
 * product is excluded, and nothing renders until a second product has been
 * viewed — real browsing history only, never padded with picks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Recently_Viewed {

	const COOKIE_NAME = 'helanak_recently_viewed';
	const MAX_ITEMS   = 8;
	const RAIL_ITEMS  = 4;

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'track' ) );
		// Priority 25: after WooCommerce's own upsells (15) and related (20).
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_rail' ), 25 );
	}

	/** IDs from the cookie, most recent first. */
	private static function viewed_ids(): array {
		$raw = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
		return array_values( array_filter( array_map( 'absint', explode( '|', $raw ) ) ) );
	}

	public static function track(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() || headers_sent() ) {
			return;
		}

		$id  = get_queried_object_id();
		$ids = self::viewed_ids();

		array_unshift( $ids, $id );
		$ids = array_slice( array_unique( $ids ), 0, self::MAX_ITEMS );

		setcookie(
			self::COOKIE_NAME,
			implode( '|', $ids ),
			array(
				'expires'  => time() + MONTH_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN ?: '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	public static function render_rail(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$current = get_queried_object_id();
		$ids     = array_slice( array_diff( self::viewed_ids(), array( $current ) ), 0, self::RAIL_ITEMS );
		if ( ! $ids ) {
			return;
		}

		$rail_query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => self::RAIL_ITEMS,
				'post__in'       => $ids,
				'orderby'        => 'post__in',
			)
		);
		if ( ! $rail_query->have_posts() ) {
			return;
		}
		?>
		<section class="helanak-recently-viewed">
			<div class="helanak-section__head">
				<h2><?php esc_html_e( 'Recently viewed', 'wp-helanak-kids-theme' ); ?></h2>
			</div>
			<ul class="helanak-products">
				<?php
				while ( $rail_query->have_posts() ) :
					$rail_query->the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		</section>
		<?php
	}
}
