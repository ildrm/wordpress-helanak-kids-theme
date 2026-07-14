<?php
/**
 * Adds the children's-store-specific "Safety & Age" Product Data tab (spec
 * 10.2 / 10.6): age suitability, choking-hazard warning, and material/safety
 * certification. These are required fields for a kids' store, not optional
 * flavor text, so they get real meta fields + admin UI rather than living in
 * the free-text product description.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Product_Safety_Fields {

	const META_AGE_SUITABILITY = '_helanak_age_suitability';
	const META_CHOKING_HAZARD  = '_helanak_choking_hazard';
	const META_CHOKING_TEXT    = '_helanak_choking_hazard_text';
	const META_CERTIFICATION   = '_helanak_safety_certification';

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_action( 'woocommerce_product_meta_end', array( __CLASS__, 'render_frontend_notice' ) );
	}

	public static function add_tab( array $tabs ): array {
		$tabs['helanak_safety'] = array(
			'label'    => __( 'Safety & Age', 'helanak-core' ),
			'target'   => 'helanak_safety_data',
			'class'    => array(),
			'priority' => 21,
		);
		return $tabs;
	}

	public static function render_panel(): void {
		global $post;
		?>
		<div id="helanak_safety_data" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php
				woocommerce_wp_textarea_input(
					array(
						'id'          => self::META_AGE_SUITABILITY,
						'label'       => __( 'Age suitability note', 'helanak-core' ),
						'desc_tip'    => true,
						'description' => __( 'Free-text guidance shown on the product page in addition to the Age Range taxonomy, e.g. "Not suitable for children under 3 due to small parts."', 'helanak-core' ),
						'value'       => get_post_meta( $post->ID, self::META_AGE_SUITABILITY, true ),
					)
				);
				woocommerce_wp_checkbox(
					array(
						'id'          => self::META_CHOKING_HAZARD,
						'label'       => __( 'Choking hazard warning', 'helanak-core' ),
						'description' => __( 'Show a choking-hazard warning on the product page.', 'helanak-core' ),
						'value'       => get_post_meta( $post->ID, self::META_CHOKING_HAZARD, true ),
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'          => self::META_CHOKING_TEXT,
						'label'       => __( 'Choking hazard text', 'helanak-core' ),
						'desc_tip'    => true,
						'description' => __( 'Defaults to a standard small-parts warning if left blank.', 'helanak-core' ),
						'value'       => get_post_meta( $post->ID, self::META_CHOKING_TEXT, true ),
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'          => self::META_CERTIFICATION,
						'label'       => __( 'Material / safety certification', 'helanak-core' ),
						'desc_tip'    => true,
						'description' => __( 'e.g. "OEKO-TEX Standard 100" or "EN 71-3 tested". Shown as a trust line on the product page.', 'helanak-core' ),
						'value'       => get_post_meta( $post->ID, self::META_CERTIFICATION, true ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	public static function save_fields( int $post_id ): void {
		if ( isset( $_POST[ self::META_AGE_SUITABILITY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, self::META_AGE_SUITABILITY, sanitize_textarea_field( wp_unslash( $_POST[ self::META_AGE_SUITABILITY ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		update_post_meta( $post_id, self::META_CHOKING_HAZARD, isset( $_POST[ self::META_CHOKING_HAZARD ] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_POST[ self::META_CHOKING_TEXT ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, self::META_CHOKING_TEXT, sanitize_text_field( wp_unslash( $_POST[ self::META_CHOKING_TEXT ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( isset( $_POST[ self::META_CERTIFICATION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, self::META_CERTIFICATION, sanitize_text_field( wp_unslash( $_POST[ self::META_CERTIFICATION ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * Structured data for the theme to render however it likes (the theme
	 * owns presentation — this plugin only supplies the values).
	 *
	 * @return array{age_suitability:string,choking_hazard:bool,choking_text:string,certification:string}
	 */
	public static function get_safety_data( int $product_id ): array {
		$choking_hazard = 'yes' === get_post_meta( $product_id, self::META_CHOKING_HAZARD, true );
		$choking_text   = get_post_meta( $product_id, self::META_CHOKING_TEXT, true );

		return array(
			'age_suitability' => (string) get_post_meta( $product_id, self::META_AGE_SUITABILITY, true ),
			'choking_hazard'  => $choking_hazard,
			'choking_text'    => $choking_text ?: __( 'WARNING: Choking hazard — small parts. Not suitable for children under 3 years.', 'helanak-core' ),
			'certification'   => (string) get_post_meta( $product_id, self::META_CERTIFICATION, true ),
		);
	}

	/**
	 * Fallback frontend output so the safety data is never silently lost if
	 * the active theme doesn't call get_safety_data() itself. Themes that do
	 * render it in a nicer spot (e.g. wp-helanak-kids-theme) should unhook
	 * this via `remove_action( 'woocommerce_product_meta_end', [ Helanak_Core_Product_Safety_Fields::class, 'render_frontend_notice' ] )`.
	 */
	public static function render_frontend_notice(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$data = self::get_safety_data( $product->get_id() );
		if ( ! $data['age_suitability'] && ! $data['choking_hazard'] && ! $data['certification'] ) {
			return;
		}
		echo '<div class="helanak-safety-meta">';
		if ( $data['age_suitability'] ) {
			echo '<p class="helanak-age-suitability">' . esc_html( $data['age_suitability'] ) . '</p>';
		}
		if ( $data['choking_hazard'] ) {
			echo '<p class="helanak-choking-hazard">⚠ ' . esc_html( $data['choking_text'] ) . '</p>';
		}
		if ( $data['certification'] ) {
			echo '<p class="helanak-certification">🛡 ' . esc_html( $data['certification'] ) . '</p>';
		}
		echo '</div>';
	}
}
