<?php
/** Variation prices in the native WooCommerce product Quick Edit form. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Quick_Edit_Prices {

	const NONCE_ACTION = 'helanak_quick_edit_prices';
	private static $pending = array();

	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'woocommerce_product_quick_edit_end', array( __CLASS__, 'fields' ) );
		add_action( 'wp_ajax_helanak_quick_edit_prices', array( __CLASS__, 'load' ) );
		// Validate the whole price batch before WordPress changes any product fields.
		add_action( 'wp_ajax_inline-save', array( __CLASS__, 'validate_request' ), 0 );
		add_action( 'woocommerce_product_quick_edit_save', array( __CLASS__, 'save' ) );
	}

	public static function assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-product' !== $screen->id ) {
			return;
		}
		wp_enqueue_style( 'helanak-quick-edit-prices', HELANAK_CORE_URL . 'assets/css/quick-edit-prices.css', array(), HELANAK_CORE_VERSION );
		wp_enqueue_script( 'helanak-quick-edit-prices', HELANAK_CORE_URL . 'assets/js/quick-edit-prices.js', array( 'jquery', 'inline-edit-post', 'woocommerce_quick-edit' ), HELANAK_CORE_VERSION, true );
		wp_localize_script( 'helanak-quick-edit-prices', 'helanakQuickPrices', array(
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			'loading' => __( 'Loading variation prices…', 'helanak-core' ),
			'failed'  => __( 'Prices could not be loaded. Try again.', 'helanak-core' ),
			'empty'   => __( 'This product has no variations. Add its sizes or models in the full product editor first.', 'helanak-core' ),
			'limit'   => __( 'Use the full product editor for products with more than 200 variations.', 'helanak-core' ),
			'invalid' => __( 'Enter valid prices. The sale price must be lower than the regular price.', 'helanak-core' ),
		) );
	}

	public static function fields(): void {
		?>
		<div class="helanak-quick-prices" hidden>
			<h4><?php esc_html_e( 'Variation prices', 'helanak-core' ); ?> (<?php echo esc_html( get_woocommerce_currency() ); ?>)</h4>
			<p class="description"><?php esc_html_e( 'Edit prices separately for each size or model. Only edited rows are saved. Clear the sale price to remove a discount; existing sale dates are kept.', 'helanak-core' ); ?></p>
			<p class="helanak-quick-prices-status" role="status" aria-live="polite"></p>
			<button type="button" class="button helanak-quick-prices-retry" hidden><?php esc_html_e( 'Retry', 'helanak-core' ); ?></button>
			<div class="helanak-quick-prices-scroll">
				<table class="widefat striped helanak-quick-prices-table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Variation', 'helanak-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Regular price', 'woocommerce' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Sale price', 'woocommerce' ); ?></th>
					</tr></thead>
					<tbody></tbody>
				</table>
			</div>
			<input type="hidden" name="helanak_variation_prices" value="{}">
			<input type="hidden" name="helanak_quick_price_nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>">
		</div>
		<?php
	}

	public static function fingerprint( WC_Product_Variation $variation ): string {
		return hash( 'sha256', $variation->get_regular_price( 'edit' ) . '|' . $variation->get_sale_price( 'edit' ) );
	}

	public static function load(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$id = isset( $_POST['product_id'] ) && is_scalar( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( array(), 403 );
		}
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			wp_send_json_error( array(), 400 );
		}
		$children = $product->get_children();
		if ( count( $children ) > 200 ) {
			wp_send_json_error( array( 'limit' => true ), 400 );
		}
		$rows = array();
		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation || ! current_user_can( 'edit_post', $child_id ) ) {
				continue;
			}
			$label = wc_get_formatted_variation( $variation, true, true, false );
			$rows[] = array(
				'id'       => $child_id,
				'label'    => wp_strip_all_tags( $label ?: '#' . $child_id ),
				'regular'  => wc_format_localized_price( $variation->get_regular_price( 'edit' ) ),
				'sale'     => wc_format_localized_price( $variation->get_sale_price( 'edit' ) ),
				'original' => self::fingerprint( $variation ),
			);
		}
		wp_send_json_success( array( 'rows' => $rows ) );
	}

	/** Accept localized decimal/group separators and Persian/Arabic digits. */
	public static function price( $value ) {
		if ( ! is_string( $value ) || strlen( $value ) > 96 ) {
			return new WP_Error( 'helanak_invalid_price', __( 'Enter a valid non-negative price.', 'helanak-core' ) );
		}
		$value = trim( strtr( $value, array_combine(
			preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ),
			str_split( '01234567890123456789' )
		) ) );
		if ( '' === $value ) {
			return '';
		}
		$decimal = wc_get_price_decimal_separator();
		$group   = wc_get_price_thousand_separator();
		$value   = str_replace( array( '٫', '٬' ), array( $decimal, $group ), $value );
		$integer = '[0-9]+';
		if ( '' !== $group && $decimal !== $group ) {
			$integer = '(?:[0-9]+|[0-9]{1,3}(?:' . preg_quote( $group, '/' ) . '[0-9]{3})+)';
		}
		if ( ! preg_match( '/^' . $integer . '(?:' . preg_quote( $decimal, '/' ) . '[0-9]+)?$/D', $value ) ) {
			return new WP_Error( 'helanak_invalid_price', __( 'Enter a valid non-negative price.', 'helanak-core' ) );
		}
		if ( '' !== $group && $group !== $decimal ) {
			$value = str_replace( $group, '', $value );
		}
		$value = str_replace( $decimal, '.', $value );
		if ( ! is_finite( (float) $value ) ) {
			return new WP_Error( 'helanak_invalid_price', __( 'Enter a valid non-negative price.', 'helanak-core' ) );
		}
		return wc_format_decimal( $value );
	}

	/** Validate every requested child and price before saving any of the batch. */
	public static function changes( int $parent_id, array $rows ) {
		$parent = wc_get_product( $parent_id );
		if ( ! current_user_can( 'edit_post', $parent_id ) || ! $parent || ! $parent->is_type( 'variable' ) || count( $rows ) > 200 ) {
			return new WP_Error( 'helanak_price_permission', __( 'You cannot edit prices for this product.', 'helanak-core' ) );
		}
		$changes = array();
		foreach ( $rows as $id => $row ) {
			$variation = ctype_digit( (string) $id ) ? wc_get_product( (int) $id ) : false;
			if ( ! $variation instanceof WC_Product_Variation || $variation->get_parent_id() !== $parent_id || ! current_user_can( 'edit_post', (int) $id ) || ! is_array( $row ) ) {
				return new WP_Error( 'helanak_price_permission', __( 'You cannot edit prices for this product.', 'helanak-core' ) );
			}
			if ( ! isset( $row['original'] ) || ! is_string( $row['original'] ) || ! hash_equals( self::fingerprint( $variation ), $row['original'] ) ) {
				return new WP_Error( 'helanak_stale_price', __( 'Prices changed since you opened Quick Edit. Reopen it to load the latest prices.', 'helanak-core' ) );
			}
			$regular = self::price( $row['regular'] ?? null );
			$sale    = self::price( $row['sale'] ?? null );
			if ( is_wp_error( $regular ) || is_wp_error( $sale ) ) {
				return new WP_Error( 'helanak_invalid_price', __( 'Enter a valid non-negative price.', 'helanak-core' ) );
			}
			if ( '' !== $sale && ( '' === $regular || (float) $sale >= (float) $regular ) ) {
				return new WP_Error( 'helanak_invalid_sale', __( 'The sale price must be lower than the regular price.', 'helanak-core' ) );
			}
			if ( $regular !== $variation->get_regular_price( 'edit' ) || $sale !== $variation->get_sale_price( 'edit' ) ) {
				$changes[] = array( 'variation' => $variation, 'regular' => $regular, 'sale' => $sale );
			}
		}
		return $changes;
	}

	public static function validate_request(): void {
		if ( ! isset( $_POST['helanak_variation_prices'] ) || '{}' === $_POST['helanak_variation_prices'] ) {
			return;
		}
		$nonce = isset( $_POST['helanak_quick_price_nonce'] ) && is_string( $_POST['helanak_quick_price_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['helanak_quick_price_nonce'] ) ) : '';
		if ( ! check_ajax_referer( 'inlineeditnonce', '_inline_edit', false ) || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Your session expired. Refresh the page and try again.', 'helanak-core' ) );
		}
		$raw = is_string( $_POST['helanak_variation_prices'] ) ? wp_unslash( $_POST['helanak_variation_prices'] ) : '';
		$rows = strlen( $raw ) <= 100000 ? json_decode( $raw, true ) : null;
		$id = isset( $_POST['post_ID'] ) && is_scalar( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;
		$changes = is_array( $rows ) ? self::changes( $id, $rows ) : new WP_Error( 'helanak_invalid_prices', __( 'Invalid price data. Reopen Quick Edit and try again.', 'helanak-core' ) );
		if ( is_wp_error( $changes ) ) {
			// Match WordPress inline-save's plain-text error response convention.
			wp_die( esc_html( $changes->get_error_message() ) );
		}
		self::$pending[ $id ] = $changes;
	}

	public static function save( WC_Product $product ): void {
		$id = $product->get_id();
		if ( ! $product->is_type( 'variable' ) || ! isset( self::$pending[ $id ] ) ) {
			return;
		}
		foreach ( self::$pending[ $id ] as $change ) {
			$variation = $change['variation'];
			$variation->set_regular_price( $change['regular'] );
			$variation->set_sale_price( $change['sale'] );
			$variation->set_price( $variation->is_on_sale( 'edit' ) ? $change['sale'] : $change['regular'] );
			$variation->save();
		}
		if ( self::$pending[ $id ] ) {
			WC_Product_Variable::sync( $id );
			wc_delete_product_transients( $id );
		}
		unset( self::$pending[ $id ] );
	}
}
