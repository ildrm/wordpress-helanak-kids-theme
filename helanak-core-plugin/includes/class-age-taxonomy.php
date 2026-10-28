<?php
/**
 * Editable product age ranges and their storefront presentation metadata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Age_Taxonomy {

	const TAXONOMY           = 'age_range';
	const META_LABEL_EN      = 'helanak_age_label_en';
	const META_LABEL_FA      = 'helanak_age_label_fa';
	const META_SUBTITLE_EN   = 'helanak_age_subtitle_en';
	const META_SUBTITLE_FA   = 'helanak_age_subtitle_fa';
	const META_ICON          = 'helanak_age_icon';
	const META_COLOR_START   = 'helanak_age_color_start';
	const META_COLOR_END     = 'helanak_age_color_end';
	const META_DISPLAY_ORDER = 'helanak_age_display_order';
	const NONCE_ACTION       = 'helanak_save_age_meta';
	const NONCE_NAME         = 'helanak_age_meta_nonce';

	/** Starter content used only by the versioned installer. */
	public static function default_terms(): array {
		return array(
			'0-1y' => array(
				'label_en' => '0–1 years', 'label_fa' => '۰ تا ۱ سال',
				'subtitle_en' => 'Baby', 'subtitle_fa' => 'نوزاد',
				'icon' => '🍼', 'color_start' => '#CDEBFF', 'color_end' => '#E4D8FF', 'order' => 10,
			),
			'1-3y' => array(
				'label_en' => '1–3 years', 'label_fa' => '۱ تا ۳ سال',
				'subtitle_en' => 'Toddler', 'subtitle_fa' => 'نوپا',
				'icon' => '🧸', 'color_start' => '#FFD1E7', 'color_end' => '#F4D8FF', 'order' => 20,
			),
			'3-5y' => array(
				'label_en' => '3–5 years', 'label_fa' => '۳ تا ۵ سال',
				'subtitle_en' => 'Preschool', 'subtitle_fa' => 'پیش‌دبستانی',
				'icon' => '🎨', 'color_start' => '#CFF4C9', 'color_end' => '#D6F4ED', 'order' => 30,
			),
			'5-8y' => array(
				'label_en' => '5–8 years', 'label_fa' => '۵ تا ۸ سال',
				'subtitle_en' => 'Kids', 'subtitle_fa' => 'کودک',
				'icon' => '🛴', 'color_start' => '#FFE8B8', 'color_end' => '#FFF0CF', 'order' => 40,
			),
			'8-12y' => array(
				'label_en' => '8–12 years', 'label_fa' => '۸ تا ۱۲ سال',
				'subtitle_en' => 'Big kids', 'subtitle_fa' => 'نوجوان کوچک',
				'icon' => '🚀', 'color_start' => '#E1D2FF', 'color_end' => '#EAD8FF', 'order' => 50,
			),
			'12p' => array(
				'label_en' => '12+ years', 'label_fa' => '۱۲ سال به بالا',
				'subtitle_en' => 'Teens', 'subtitle_fa' => 'نوجوان',
				'icon' => '⭐', 'color_start' => '#C9F2E4', 'color_end' => '#D9F6ED', 'order' => 60,
			),
		);
	}

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( self::TAXONOMY . '_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'save_fields' ) );
		add_action( 'edited_' . self::TAXONOMY, array( __CLASS__, 'save_fields' ) );
	}

	public static function register(): void {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			array(
				'label'              => __( 'Age Range', 'helanak-core' ),
				'labels'             => array(
					'name'          => __( 'Age Ranges', 'helanak-core' ),
					'singular_name' => __( 'Age Range', 'helanak-core' ),
					'menu_name'     => __( 'Age Ranges', 'helanak-core' ),
				),
				'hierarchical'       => false,
				'public'             => true,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_quick_edit' => true,
				'show_in_rest'       => true,
				'rewrite'            => array( 'slug' => 'age-range' ),
			)
		);
	}

	/** Seed starter terms once. Re-activation never calls this method. */
	public static function seed_terms(): void {
		foreach ( self::default_terms() as $slug => $data ) {
			$existing = term_exists( $slug, self::TAXONOMY );
			if ( $existing ) {
				$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
			} else {
				$created = wp_insert_term( $data['label_en'], self::TAXONOMY, array( 'slug' => $slug ) );
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$term_id = (int) $created['term_id'];
			}
			self::seed_term_meta( $term_id, $data );
		}
	}

	/** Add missing starter metadata without overwriting administrator changes. */
	public static function seed_term_meta( int $term_id, array $data ): void {
		$term      = get_term( $term_id, self::TAXONOMY );
		$legacy_fa = get_term_meta( $term_id, 'name_fa', true );
		if ( ! get_term_meta( $term_id, self::META_LABEL_EN, true ) && $term instanceof \WP_Term ) {
			update_term_meta( $term_id, self::META_LABEL_EN, $term->name );
		}
		if ( ! get_term_meta( $term_id, self::META_LABEL_FA, true ) && $legacy_fa ) {
			update_term_meta( $term_id, self::META_LABEL_FA, $legacy_fa );
		}

		$map = array(
			self::META_LABEL_EN      => 'label_en',
			self::META_LABEL_FA      => 'label_fa',
			self::META_SUBTITLE_EN   => 'subtitle_en',
			self::META_SUBTITLE_FA   => 'subtitle_fa',
			self::META_ICON          => 'icon',
			self::META_COLOR_START   => 'color_start',
			self::META_COLOR_END     => 'color_end',
			self::META_DISPLAY_ORDER => 'order',
		);
		foreach ( $map as $meta_key => $data_key ) {
			if ( '' === (string) get_term_meta( $term_id, $meta_key, true ) && isset( $data[ $data_key ] ) ) {
				update_term_meta( $term_id, $meta_key, $data[ $data_key ] );
			}
		}

	}

	/**
	 * Get language-aware labels and presentation values for an age term.
	 *
	 * @return array{label:string,label_en:string,label_fa:string,subtitle:string,subtitle_en:string,subtitle_fa:string,icon:string,color_start:string,color_end:string,order:int}
	 */
	public static function get_display_data( \WP_Term $term, string $lang = '' ): array {
		$lang        = in_array( $lang, array( 'en', 'fa' ), true ) ? $lang : ( 'fa' === substr( get_locale(), 0, 2 ) ? 'fa' : 'en' );
		$label_en    = (string) get_term_meta( $term->term_id, self::META_LABEL_EN, true );
		$label_fa    = (string) get_term_meta( $term->term_id, self::META_LABEL_FA, true );
		$subtitle_en = (string) get_term_meta( $term->term_id, self::META_SUBTITLE_EN, true );
		$subtitle_fa = (string) get_term_meta( $term->term_id, self::META_SUBTITLE_FA, true );
		$color_start = sanitize_hex_color( get_term_meta( $term->term_id, self::META_COLOR_START, true ) );
		$color_end   = sanitize_hex_color( get_term_meta( $term->term_id, self::META_COLOR_END, true ) );

		$label_en = $label_en ?: $term->name;
		$label_fa = $label_fa ?: ( (string) get_term_meta( $term->term_id, 'name_fa', true ) ?: $label_en );

		return array(
			'label'         => 'fa' === $lang ? $label_fa : $label_en,
			'label_en'      => $label_en,
			'label_fa'      => $label_fa,
			'subtitle'      => 'fa' === $lang ? ( $subtitle_fa ?: $subtitle_en ) : $subtitle_en,
			'subtitle_en'   => $subtitle_en,
			'subtitle_fa'   => $subtitle_fa,
			'icon'          => (string) get_term_meta( $term->term_id, self::META_ICON, true ),
			'color_start'   => $color_start ?: '',
			'color_end'     => $color_end ?: '',
			'order'         => (int) get_term_meta( $term->term_id, self::META_DISPLAY_ORDER, true ),
		);
	}

	public static function localized_label( \WP_Term $term, bool $is_fa ): string {
		return self::get_display_data( $term, $is_fa ? 'fa' : 'en' )['label'];
	}

	public static function render_add_fields(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		self::render_fields();
	}

	public static function render_edit_fields( \WP_Term $term ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		self::render_fields( $term );
	}

	private static function render_fields( ?\WP_Term $term = null ): void {
		$values = array();
		foreach ( self::meta_fields() as $key => $field ) {
			$stored         = $term ? get_term_meta( $term->term_id, $key, true ) : '';
			$values[ $key ] = '' !== (string) $stored ? $stored : ( $field['default'] ?? '' );
		}

		foreach ( self::meta_fields() as $key => $field ) {
			$is_edit = null !== $term;
			if ( $is_edit ) {
				echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			} else {
				echo '<div class="form-field"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label>';
			}
			printf(
				'<input type="%1$s" name="%2$s" id="%2$s" value="%3$s"%4$s>',
				esc_attr( $field['type'] ),
				esc_attr( $key ),
				esc_attr( (string) $values[ $key ] ),
				'number' === $field['type'] ? ' min="0" step="1"' : ''
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
			self::META_LABEL_EN      => array( 'label' => __( 'English label', 'helanak-core' ), 'type' => 'text' ),
			self::META_LABEL_FA      => array( 'label' => __( 'Persian label', 'helanak-core' ), 'type' => 'text' ),
			self::META_SUBTITLE_EN   => array( 'label' => __( 'English subtitle', 'helanak-core' ), 'type' => 'text' ),
			self::META_SUBTITLE_FA   => array( 'label' => __( 'Persian subtitle', 'helanak-core' ), 'type' => 'text' ),
			self::META_ICON          => array( 'label' => __( 'Icon or emoji', 'helanak-core' ), 'type' => 'text' ),
			self::META_COLOR_START   => array( 'label' => __( 'Gradient start color', 'helanak-core' ), 'type' => 'color', 'default' => '#F3E8CF' ),
			self::META_COLOR_END     => array( 'label' => __( 'Gradient end color', 'helanak-core' ), 'type' => 'color', 'default' => '#FAF1DE' ),
			self::META_DISPLAY_ORDER => array( 'label' => __( 'Display order', 'helanak-core' ), 'type' => 'number' ),
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
			$raw = wp_unslash( $_POST[ $key ] );
			if ( ! is_scalar( $raw ) ) {
				continue;
			}
			if ( 'color' === $field['type'] ) {
				$value = sanitize_hex_color( $raw );
			} elseif ( 'number' === $field['type'] ) {
				$value = absint( $raw );
			} else {
				$value = sanitize_text_field( $raw );
			}
			if ( '' === $value || null === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}
}
