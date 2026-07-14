<?php
/**
 * Registers the "Age Range" taxonomy on products (spec 10.2) as a real
 * filterable taxonomy rather than free-text, so it can drive archive filters,
 * the homepage age selector, and future recommendation logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Age_Taxonomy {

	const TAXONOMY = 'age_range';

	/** Slug => [en label, fa label], in display order. */
	public static function default_terms(): array {
		return array(
			'0-1y'  => array( 'en' => '0–1 years', 'fa' => '۰ تا ۱ سال' ),
			'1-3y'  => array( 'en' => '1–3 years', 'fa' => '۱ تا ۳ سال' ),
			'3-5y'  => array( 'en' => '3–5 years', 'fa' => '۳ تا ۵ سال' ),
			'5-8y'  => array( 'en' => '5–8 years', 'fa' => '۵ تا ۸ سال' ),
			'8-12y' => array( 'en' => '8–12 years', 'fa' => '۸ تا ۱۲ سال' ),
			'12p'   => array( 'en' => '12+ years', 'fa' => '۱۲ سال به بالا' ),
		);
	}

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register(): void {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			array(
				'label'             => __( 'Age Range', 'helanak-core' ),
				'labels'            => array(
					'name'          => __( 'Age Ranges', 'helanak-core' ),
					'singular_name' => __( 'Age Range', 'helanak-core' ),
					'menu_name'     => __( 'Age Ranges', 'helanak-core' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_quick_edit' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'age-range' ),
			)
		);
	}

	/**
	 * Seeds the fixed set of age-range terms once. Safe to call repeatedly —
	 * `term_exists` guards against duplicates on re-activation.
	 */
	public static function seed_terms(): void {
		foreach ( self::default_terms() as $slug => $labels ) {
			if ( term_exists( $slug, self::TAXONOMY ) ) {
				continue;
			}
			$term = wp_insert_term( $labels['en'], self::TAXONOMY, array( 'slug' => $slug ) );
			if ( ! is_wp_error( $term ) ) {
				update_term_meta( $term['term_id'], 'name_fa', $labels['fa'] );
			}
		}
	}

	/**
	 * Localized label for a term: falls back to the English term name if no
	 * Persian term-meta translation has been entered yet.
	 */
	public static function localized_label( \WP_Term $term, bool $is_fa ): string {
		if ( ! $is_fa ) {
			return $term->name;
		}
		$fa = get_term_meta( $term->term_id, 'name_fa', true );
		return $fa ?: $term->name;
	}
}
