<?php
/**
 * Versioned, idempotent data installation and legacy migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Migrator {

	const DB_VERSION = '2.0.0';
	const OPTION_KEY = 'helanak_core_db_version';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 99 );
	}

	public static function activate(): void {
		Helanak_Core_Age_Taxonomy::register();
		Helanak_Core_Badge_Engine::register_taxonomy();
		self::maybe_upgrade();
	}

	public static function maybe_upgrade(): void {
		if ( self::DB_VERSION === get_option( self::OPTION_KEY ) ) {
			return;
		}
		if ( ! taxonomy_exists( Helanak_Core_Age_Taxonomy::TAXONOMY ) || ! taxonomy_exists( Helanak_Core_Badge_Engine::TAXONOMY ) || ! taxonomy_exists( Helanak_Core_Category_Theme::TAXONOMY ) ) {
			return;
		}

		self::migrate_age_ranges();
		self::migrate_category_visuals();
		self::migrate_badges();

		if ( false === get_option( Helanak_Core_Badge_Engine::OPTION_NAME, false ) ) {
			add_option( Helanak_Core_Badge_Engine::OPTION_NAME, Helanak_Core_Badge_Engine::automatic_defaults() );
		}
		update_option( self::OPTION_KEY, self::DB_VERSION );
	}

	private static function migrate_age_ranges(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => Helanak_Core_Age_Taxonomy::TAXONOMY,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return;
		}

		// A brand-new install receives starter terms. Existing installations
		// keep exactly the ranges their administrator retained.
		if ( ! $terms ) {
			Helanak_Core_Age_Taxonomy::seed_terms();
			return;
		}

		$defaults = Helanak_Core_Age_Taxonomy::default_terms();
		foreach ( $terms as $term ) {
			if ( isset( $defaults[ $term->slug ] ) ) {
				Helanak_Core_Age_Taxonomy::seed_term_meta( $term->term_id, $defaults[ $term->slug ] );
			}
		}
	}

	private static function migrate_category_visuals(): void {
		$legacy_skins = array(
			'sky-adventure'   => array( 'Sky Adventure', 'ماجراجویی آسمان', '✈️', '#8AE67E', '#A8E4FF' ),
			'space-hero'      => array( 'Space Hero', 'قهرمان فضایی', '🚀', '#2B2B6B', '#7C7CE0' ),
			'dinosaur-world'  => array( 'Dinosaur World', 'دنیای دایناسورها', '🦕', '#4E7A3D', '#A8C86A' ),
			'race-world'      => array( 'Race World', 'دنیای مسابقه', '🏎️', '#E63946', '#FFB703' ),
			'robot-world'     => array( 'Robot World', 'دنیای ربات‌ها', '🤖', '#457B9D', '#A8DADC' ),
			'candy-land'      => array( 'Candy Land', 'سرزمین شیرینی', '🍬', '#FF6FB5', '#FFD6E8' ),
			'princess-garden' => array( 'Princess Garden', 'باغ پرنسسی', '👑', '#B76BC7', '#FFD6F5' ),
			'rainbow-world'   => array( 'Rainbow World', 'دنیای رنگین‌کمان', '🌈', '#FF9F3D', '#7EC8FF' ),
			'magic-unicorn'   => array( 'Magic Unicorn', 'تک‌شاخ جادویی', '🦄', '#C58AFF', '#FFE9A8' ),
			'flower-dream'    => array( 'Flower Dream', 'رویای گل‌ها', '🌸', '#FFB7D5', '#B4F0A8' ),
		);
		$terms = get_terms(
			array(
				'taxonomy'   => Helanak_Core_Category_Theme::TAXONOMY,
				'hide_empty' => false,
				'meta_key'   => 'helanak_category_theme',
			)
		);
		if ( is_wp_error( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$slug = (string) get_term_meta( $term->term_id, 'helanak_category_theme', true );
			if ( ! isset( $legacy_skins[ $slug ] ) ) {
				continue;
			}
			$legacy = $legacy_skins[ $slug ];
			$map    = array(
				Helanak_Core_Category_Theme::META_LABEL_EN    => $legacy[0],
				Helanak_Core_Category_Theme::META_LABEL_FA    => $legacy[1],
				Helanak_Core_Category_Theme::META_ICON        => $legacy[2],
				Helanak_Core_Category_Theme::META_COLOR_START => $legacy[3],
				Helanak_Core_Category_Theme::META_COLOR_END   => $legacy[4],
			);
			foreach ( $map as $meta_key => $value ) {
				if ( '' === (string) get_term_meta( $term->term_id, $meta_key, true ) ) {
					update_term_meta( $term->term_id, $meta_key, $value );
				}
			}
		}
	}

	private static function migrate_badges(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => Helanak_Core_Badge_Engine::TAXONOMY,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return;
		}
		if ( ! $terms ) {
			Helanak_Core_Badge_Engine::seed_default_terms();
		} else {
			$starters = Helanak_Core_Badge_Engine::starter_badges();
			foreach ( $terms as $term ) {
				if ( isset( $starters[ $term->slug ] ) ) {
					Helanak_Core_Badge_Engine::seed_term_meta( $term->term_id, $starters[ $term->slug ] );
				}
			}
		}

		foreach ( array_keys( Helanak_Core_Badge_Engine::starter_badges() ) as $slug ) {
			$term = get_term_by( 'slug', $slug, Helanak_Core_Badge_Engine::TAXONOMY );
			if ( ! $term ) {
				continue;
			}
			$product_ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_key'       => Helanak_Core_Badge_Engine::META_PREFIX . $slug,
					'meta_value'     => 'yes',
					'no_found_rows'  => true,
				)
			);
			foreach ( $product_ids as $product_id ) {
				wp_set_object_terms( $product_id, array( $term->term_id ), Helanak_Core_Badge_Engine::TAXONOMY, true );
			}
		}
	}
}
