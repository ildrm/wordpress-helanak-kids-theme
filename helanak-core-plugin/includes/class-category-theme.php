<?php
/**
 * Editable visual presentation metadata for WooCommerce product categories.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Category_Theme {

	const TAXONOMY         = 'product_cat';
	const META_LABEL_EN    = 'helanak_category_label_en';
	const META_LABEL_FA    = 'helanak_category_label_fa';
	const META_ICON        = 'helanak_category_icon';
	const META_COLOR_START = 'helanak_category_color_start';
	const META_COLOR_END   = 'helanak_category_color_end';
	const NONCE_ACTION     = 'helanak_save_category_visuals';
	const NONCE_NAME       = 'helanak_category_visuals_nonce';

	public static function init(): void {
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_fields' ) );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_fields' ) );
	}

	/**
	 * Get category presentation data with safe defaults.
	 *
	 * @return array{label_en:string,label_fa:string,icon:string,color_start:string,color_end:string,image_id:int,image_url:string}
	 */
	public static function get_visuals_for_term( \WP_Term $term ): array {
		$label_en    = (string) get_term_meta( $term->term_id, self::META_LABEL_EN, true );
		$label_fa    = (string) get_term_meta( $term->term_id, self::META_LABEL_FA, true );
		$color_start = sanitize_hex_color( get_term_meta( $term->term_id, self::META_COLOR_START, true ) );
		$color_end   = sanitize_hex_color( get_term_meta( $term->term_id, self::META_COLOR_END, true ) );
		$image_id    = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		$image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

		$label_en = $label_en ?: $term->name;
		$label_fa = $label_fa ?: $label_en;

		return array(
			'label_en'    => $label_en,
			'label_fa'    => $label_fa,
			'icon'        => (string) get_term_meta( $term->term_id, self::META_ICON, true ) ?: '🧸',
			'color_start' => $color_start ?: '#F3E8CF',
			'color_end'   => $color_end ?: '#FAF1DE',
			'image_id'    => $image_id,
			'image_url'   => $image_url ?: '',
		);
	}

	/**
	 * Backward-compatible numeric shape for integrations using the old helper.
	 *
	 * @deprecated Use get_visuals_for_term().
	 */
	public static function get_skin_for_term( \WP_Term $term ): array {
		$data = self::get_visuals_for_term( $term );
		return array(
			$data['label_en'],
			$data['label_fa'],
			$data['icon'],
			$data['color_start'],
			$data['color_end'],
		);
	}

	public static function render_add_fields(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		self::render_fields();
		?>
		<p class="description"><?php esc_html_e( 'Use the WooCommerce thumbnail above as optional category hero artwork.', 'helanak-core' ); ?></p>
		<?php
	}

	public static function render_edit_fields( \WP_Term $term ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		self::render_fields( $term );
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Hero artwork', 'helanak-core' ); ?></th>
			<td><p class="description"><?php esc_html_e( 'The standard WooCommerce category thumbnail is used as optional hero artwork.', 'helanak-core' ); ?></p></td>
		</tr>
		<?php
	}

	private static function render_fields( ?\WP_Term $term = null ): void {
		foreach ( self::meta_fields() as $key => $field ) {
			$stored  = $term ? get_term_meta( $term->term_id, $key, true ) : '';
			$value   = '' !== (string) $stored ? $stored : ( $field['default'] ?? '' );
			$is_edit = null !== $term;
			if ( $is_edit ) {
				echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			} else {
				echo '<div class="form-field"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label>';
			}
			printf(
				'<input type="%1$s" name="%2$s" id="%2$s" value="%3$s">',
				esc_attr( $field['type'] ),
				esc_attr( $key ),
				esc_attr( (string) $value )
			);
			if ( $is_edit ) {
				echo '</td></tr>';
			} else {
				echo '</div>';
			}
		}
	}

	private static function meta_fields(): array {
		return array(
			self::META_LABEL_EN    => array( 'label' => __( 'English display title', 'helanak-core' ), 'type' => 'text' ),
			self::META_LABEL_FA    => array( 'label' => __( 'Persian display title', 'helanak-core' ), 'type' => 'text' ),
			self::META_ICON        => array( 'label' => __( 'Icon or emoji', 'helanak-core' ), 'type' => 'text' ),
			self::META_COLOR_START => array( 'label' => __( 'Gradient start color', 'helanak-core' ), 'type' => 'color', 'default' => '#F3E8CF' ),
			self::META_COLOR_END   => array( 'label' => __( 'Gradient end color', 'helanak-core' ), 'type' => 'color', 'default' => '#FAF1DE' ),
		);
	}

	public static function save_fields( int $term_id ): void {
		$taxonomy = get_taxonomy( self::TAXONOMY );
		if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return;
		}
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? wp_unslash( $_POST[ self::NONCE_NAME ] ) : '';
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), self::NONCE_ACTION ) ) {
			return;
		}

		foreach ( self::meta_fields() as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw   = wp_unslash( $_POST[ $key ] );
			if ( ! is_scalar( $raw ) ) {
				continue;
			}
			$value = 'color' === $field['type'] ? sanitize_hex_color( $raw ) : sanitize_text_field( $raw );
			if ( '' === $value || null === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}
}
