<?php
/**
 * Product compare: serves a side-by-side comparison table (image, price,
 * rating, age range, safety fields, stock, add-to-cart) for up to 3 products
 * over admin-ajax.php, displayed in the same modal shell as Quick View.
 *
 * The per-card ⇄ trigger buttons already existed in
 * woocommerce/content-product.php ([data-helanak-compare-toggle]) but had no
 * behavior at all; assets/js/compare.js now maintains the localStorage
 * selection (hln-compare, matching the wishlist's hln-* convention) and
 * fetches this endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Compare {

	const MAX_PRODUCTS = 3;

	public static function init(): void {
		add_action( 'wp_ajax_helanak_compare', array( __CLASS__, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_helanak_compare', array( __CLASS__, 'handle_request' ) );
	}

	public static function handle_request(): void {
		check_ajax_referer( 'helanak_compare', 'nonce' );

		if ( ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'wp-helanak-kids-theme' ) ), 404 );
		}
		$raw_ids = isset( $_POST['product_ids'] ) && is_string( $_POST['product_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['product_ids'] ) ) : '';
		$ids     = array_slice( array_unique( array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) ) ), 0, self::MAX_PRODUCTS );

		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product instanceof WC_Product && 'publish' === $product->get_status() && ! post_password_required( $id ) && $product->is_visible() ) {
				$products[] = $product;
			}
		}

		ob_start();
		self::render_table( $products );
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	/** @param WC_Product[] $products */
	private static function render_table( array $products ): void {
		$rows = array(
			'price'     => __( 'Price', 'wp-helanak-kids-theme' ),
			'rating'    => __( 'Rating', 'wp-helanak-kids-theme' ),
			'age'       => __( 'Age range', 'wp-helanak-kids-theme' ),
			'safety'    => __( 'Safety', 'wp-helanak-kids-theme' ),
			'stock'     => __( 'Availability', 'wp-helanak-kids-theme' ),
			'cart'      => __( 'Shopping options', 'wp-helanak-kids-theme' ),
		);
		?>
		<div class="helanak-compare" data-helanak-compare-ids="<?php echo esc_attr( implode( ',', array_map( static function ( $product ) { return $product->get_id(); }, $products ) ) ); ?>">
			<h2 class="helanak-compare__title"><?php esc_html_e( 'Compare products', 'wp-helanak-kids-theme' ); ?></h2>
			<?php if ( ! $products ) : ?>
				<p role="status"><?php esc_html_e( 'Your comparison list is empty.', 'wp-helanak-kids-theme' ); ?></p>
				<button type="button" class="helanak-btn helanak-btn--primary" data-helanak-compare-close><?php esc_html_e( 'Continue shopping', 'wp-helanak-kids-theme' ); ?></button>
			<?php else : ?>
				<?php if ( 1 === count( $products ) ) : ?>
					<p class="helanak-compare__hint" role="status"><?php esc_html_e( 'Add another product to compare its details.', 'wp-helanak-kids-theme' ); ?></p>
				<?php endif; ?>
			<div class="helanak-compare__scroll">
				<table class="helanak-compare__table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Details', 'wp-helanak-kids-theme' ); ?></th>
							<?php foreach ( $products as $product ) : ?>
								<th scope="col">
									<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
										<?php echo $product->get_image( 'helanak-card' ); // phpcs:ignore WordPress.Security.EscapeOutput -- core-generated <img>. ?>
										<span><?php echo esc_html( $product->get_name() ); ?></span>
									</a>
									<button type="button" class="helanak-compare__remove" data-helanak-compare-remove data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from comparison', 'wp-helanak-kids-theme' ), $product->get_name() ) ); ?>"><?php esc_html_e( 'Remove from comparison', 'wp-helanak-kids-theme' ); ?></button>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row_key => $row_label ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $row_label ); ?></th>
								<?php foreach ( $products as $product ) : ?>
									<td><?php self::render_cell( $row_key, $product ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_cell( string $row_key, WC_Product $product ): void {
		switch ( $row_key ) {
			case 'price':
				echo wp_kses_post( $product->get_price_html() );
				break;

			case 'rating':
				$rating = (float) $product->get_average_rating();
				if ( $rating > 0 ) {
					echo esc_html( str_repeat( '★', (int) round( $rating ) ) . str_repeat( '☆', 5 - (int) round( $rating ) ) );
					echo ' <span class="helanak-compare__muted">(' . esc_html( number_format_i18n( $product->get_rating_count() ) ) . ')</span>';
				} else {
					echo '<span class="helanak-compare__muted">—</span>';
				}
				break;

			case 'age':
				$terms = get_the_terms( $product->get_id(), 'age_range' );
				if ( is_array( $terms ) && $terms ) {
					echo esc_html( implode( ', ', array_map( 'helanak_age_label', $terms ) ) );
				} else {
					echo '<span class="helanak-compare__muted">—</span>';
				}
				break;

			case 'safety':
				$safety = class_exists( 'Helanak_Core_Product_Safety_Fields' )
					? Helanak_Core_Product_Safety_Fields::get_safety_data( $product->get_id() )
					: null;
				$bits   = array();
				if ( $safety ) {
					if ( ! empty( $safety['certification'] ) ) {
						$bits[] = '<span class="helanak-certification">' . esc_html( $safety['certification'] ) . '</span>';
					}
					if ( ! empty( $safety['choking_hazard'] ) ) {
						$bits[] = '<span class="helanak-choking-hazard">⚠ ' . esc_html__( 'Choking hazard', 'wp-helanak-kids-theme' ) . '</span>';
					}
				}
				echo $bits ? wp_kses_post( implode( '<br />', $bits ) ) : '<span class="helanak-compare__muted">—</span>';
				break;

			case 'stock':
				if ( 'instock' === $product->get_stock_status() ) {
					esc_html_e( 'In stock', 'wp-helanak-kids-theme' );
				} elseif ( 'onbackorder' === $product->get_stock_status() ) {
					esc_html_e( 'Available on backorder', 'wp-helanak-kids-theme' );
				} else {
					esc_html_e( 'Out of stock', 'wp-helanak-kids-theme' );
				}
				break;

			case 'cart':
				$ajax = $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock();
				// The compare table lives in a modal; a plain add-to-cart link
				// with the AJAX classes behaves like the card button.
				printf(
					'<a href="%1$s" data-product_id="%2$d" data-quantity="1" class="helanak-btn helanak-btn--primary%4$s" rel="nofollow">%3$s</a>',
					esc_url( $product->add_to_cart_url() ),
					absint( $product->get_id() ),
					esc_html( $product->add_to_cart_text() ),
					$ajax ? ' add_to_cart_button ajax_add_to_cart' : ''
				);
				break;
		}
	}
}
