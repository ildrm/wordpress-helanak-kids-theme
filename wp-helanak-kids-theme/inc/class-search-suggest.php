<?php
/**
 * Live search suggestions: the header search pill fetches up to 5 matching
 * products (thumbnail, name, price, age chip) as the shopper types —
 * assets/js/search-suggest.js renders them in a dropdown under the pill.
 * Returns server-rendered list items so markup/escaping stays in PHP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Search_Suggest {

	const MAX_RESULTS = 5;

	public static function init(): void {
		add_action( 'wp_ajax_helanak_search_suggest', array( __CLASS__, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_helanak_search_suggest', array( __CLASS__, 'handle_request' ) );
	}

	public static function handle_request(): void {
		check_ajax_referer( 'helanak_search_suggest', 'nonce' );

		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'html' => '' ) );
		}

		$products = wc_get_products(
			array(
				'status'     => 'publish',
				'limit'      => self::MAX_RESULTS,
				's'          => $term,
				'visibility' => 'search',
			)
		);

		if ( ! $products ) {
			wp_send_json_success(
				array(
					'html' => '<li class="helanak-search-suggest__empty" role="option" aria-disabled="true">'
						. esc_html__( 'No products found', 'wp-helanak-kids-theme' )
						. '</li>',
				)
			);
		}

		ob_start();
		foreach ( $products as $product ) {
			self::render_item( $product );
		}
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	private static function render_item( WC_Product $product ): void {
		$age_terms = get_the_terms( $product->get_id(), 'age_range' );
		$age_term  = ( is_array( $age_terms ) && $age_terms ) ? $age_terms[0] : null;
		?>
		<li role="option">
			<a class="helanak-search-suggest__item" href="<?php echo esc_url( $product->get_permalink() ); ?>">
				<?php echo $product->get_image( 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput -- core-generated <img>. ?>
				<span class="helanak-search-suggest__text">
					<span class="helanak-search-suggest__name"><?php echo esc_html( $product->get_name() ); ?></span>
					<span class="helanak-search-suggest__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				</span>
				<?php if ( $age_term ) : ?>
					<span class="helanak-search-suggest__age"><?php echo esc_html( helanak_age_label( $age_term ) ); ?></span>
				<?php endif; ?>
			</a>
		</li>
		<?php
	}
}
