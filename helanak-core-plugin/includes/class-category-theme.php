<?php
/**
 * Adds a "Category Theme" (visual skin) field to WooCommerce product
 * categories (spec section 9 — Category Template Engine). Deliberately
 * modeled as a content theme (space / animals / princesses / ...), not a
 * gender-locked section: a parent browses INTO a themed skin by subject,
 * the underlying catalog is never gender-gated.
 *
 * All 10 skins share one archive template (woocommerce/archive-product.php)
 * and one product-card partial — only hero art, accent color tokens, and
 * decorative motif vary per skin, per the spec's "10 templates, not 10
 * forked codebases" rule.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Category_Theme {

	const META_KEY = 'helanak_category_theme';
	const TAXONOMY = 'product_cat';

	/** slug => [label_en, label_fa, icon (emoji), accent_from, accent_to] */
	public static function skins(): array {
		return array(
			'sky-adventure'    => array( 'Sky Adventure', 'ماجراجویی آسمان', '✈️', '#8AE67E', '#A8E4FF' ),
			'space-hero'       => array( 'Space Hero', 'قهرمان فضایی', '🚀', '#2B2B6B', '#7C7CE0' ),
			'dinosaur-world'   => array( 'Dinosaur World', 'دنیای دایناسورها', '🦕', '#4E7A3D', '#A8C86A' ),
			'race-world'       => array( 'Race World', 'دنیای مسابقه', '🏎️', '#E63946', '#FFB703' ),
			'robot-world'      => array( 'Robot World', 'دنیای ربات‌ها', '🤖', '#457B9D', '#A8DADC' ),
			'candy-land'       => array( 'Candy Land', 'سرزمین شیرینی', '🍬', '#FF6FB5', '#FFD6E8' ),
			'princess-garden'  => array( 'Princess Garden', 'باغ پرنسسی', '👑', '#B76BC7', '#FFD6F5' ),
			'rainbow-world'    => array( 'Rainbow World', 'دنیای رنگین‌کمان', '🌈', '#FF9F3D', '#7EC8FF' ),
			'magic-unicorn'    => array( 'Magic Unicorn', 'تک‌شاخ جادویی', '🦄', '#C58AFF', '#FFE9A8' ),
			'flower-dream'     => array( 'Flower Dream', 'رویای گل‌ها', '🌸', '#FFB7D5', '#B4F0A8' ),
		);
	}

	public static function init(): void {
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_field' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_field' ) );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_field' ) );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_field' ) );
	}

	private static function field_options_html( string $selected ): string {
		$html = '<option value="">' . esc_html__( '— None —', 'helanak-core' ) . '</option>';
		foreach ( self::skins() as $slug => $skin ) {
			$html .= sprintf(
				'<option value="%s" %s>%s %s</option>',
				esc_attr( $slug ),
				selected( $selected, $slug, false ),
				esc_html( $skin[2] ),
				esc_html( $skin[0] )
			);
		}
		return $html;
	}

	public static function render_add_field(): void {
		?>
		<div class="form-field">
			<label for="helanak_category_theme"><?php esc_html_e( 'Category Theme (visual skin)', 'helanak-core' ); ?></label>
			<select name="helanak_category_theme" id="helanak_category_theme">
				<?php echo self::field_options_html( '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</select>
			<p><?php esc_html_e( 'Picks the hero art, accent colors, and motif this category uses on the shop archive. Purely a visual skin — never gates or hides products.', 'helanak-core' ); ?></p>
		</div>
		<?php
	}

	public static function render_edit_field( \WP_Term $term ): void {
		$selected = get_term_meta( $term->term_id, self::META_KEY, true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="helanak_category_theme"><?php esc_html_e( 'Category Theme (visual skin)', 'helanak-core' ); ?></label></th>
			<td>
				<select name="helanak_category_theme" id="helanak_category_theme">
					<?php echo self::field_options_html( (string) $selected ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</select>
				<p class="description"><?php esc_html_e( 'Picks the hero art, accent colors, and motif this category uses on the shop archive. Purely a visual skin — never gates or hides products.', 'helanak-core' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public static function save_field( int $term_id ): void {
		if ( ! isset( $_POST['helanak_category_theme'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$value = sanitize_key( wp_unslash( $_POST['helanak_category_theme'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $value ) {
			delete_term_meta( $term_id, self::META_KEY );
			return;
		}
		if ( array_key_exists( $value, self::skins() ) ) {
			update_term_meta( $term_id, self::META_KEY, $value );
		}
	}

	/**
	 * Returns the skin data array for a term, or null if it has none assigned.
	 *
	 * @return array{0:string,1:string,2:string,3:string,4:string}|null
	 */
	public static function get_skin_for_term( \WP_Term $term ): ?array {
		$slug  = get_term_meta( $term->term_id, self::META_KEY, true );
		$skins = self::skins();
		return $slug && isset( $skins[ $slug ] ) ? $skins[ $slug ] : null;
	}
}
