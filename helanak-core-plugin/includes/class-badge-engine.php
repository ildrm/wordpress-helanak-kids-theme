<?php
/**
 * Badge engine (spec 10.5): evaluates which merchandising badges a product
 * should show. "Sale" and "New" are computed live from real product state
 * (on-sale flag / publish date) rather than stored meta, so they can never go
 * stale; the rest are explicit admin-set flags stored as post meta. Badge
 * *evaluation* is business logic and lives here; badge *rendering* (colors,
 * markup, how many to show) is left to the theme via the ordered array this
 * returns.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Badge_Engine {

	const META_PREFIX = '_helanak_badge_';

	/** Manual (admin-toggled) badge keys, in priority order (highest first). */
	public static function manual_badge_keys(): array {
		return array(
			'limited'      => __( 'Limited', 'helanak-core' ),
			'hot'          => __( 'Hot', 'helanak-core' ),
			'trending'     => __( 'Trending', 'helanak-core' ),
			'best_seller'  => __( 'Best Seller', 'helanak-core' ),
			'recommended'  => __( 'Recommended', 'helanak-core' ),
			'gift'         => __( 'Gift', 'helanak-core' ),
			'educational'  => __( 'Educational', 'helanak-core' ),
			'organic'      => __( 'Organic', 'helanak-core' ),
			'safe_material' => __( 'Safe Material', 'helanak-core' ),
		);
	}

	/** slug => [bg, fg] — a sensible default palette; themes may override via the `helanak_badge_colors` filter. */
	public static function default_colors(): array {
		$colors = array(
			'sale'          => array( '#E86752', '#FFFFFF' ),
			'limited'       => array( '#2B2B33', '#FFFFFF' ),
			'hot'           => array( '#FF7E55', '#FFFFFF' ),
			'trending'      => array( '#73C8FF', '#173247' ),
			'new'           => array( '#8AE67E', '#1D3B18' ),
			'best_seller'   => array( '#FFD84D', '#4A3B00' ),
			'recommended'   => array( '#B8DFFF', '#1B3550' ),
			'gift'          => array( '#FFB7D5', '#5B1F3A' ),
			'educational'   => array( '#A8E4FF', '#1B4358' ),
			'organic'       => array( '#A8C86A', '#233A0E' ),
			'safe_material' => array( '#DDD2FF', '#372766' ),
			'age_specific'  => array( '#F3E8CF', '#5B4A2A' ),
		);
		return apply_filters( 'helanak_badge_colors', $colors );
	}

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
	}

	public static function add_tab( array $tabs ): array {
		$tabs['helanak_badges'] = array(
			'label'    => __( 'Badges', 'helanak-core' ),
			'target'   => 'helanak_badges_data',
			'class'    => array(),
			'priority' => 22,
		);
		return $tabs;
	}

	public static function render_panel(): void {
		global $post;
		echo '<div id="helanak_badges_data" class="panel woocommerce_options_panel"><div class="options_group">';
		echo '<p style="padding:0 12px;color:#666">' . esc_html__( '"Sale" and "New" badges are automatic (based on the product\'s sale price and publish date) and don\'t need a checkbox here.', 'helanak-core' ) . '</p>';
		foreach ( self::manual_badge_keys() as $key => $label ) {
			woocommerce_wp_checkbox(
				array(
					'id'    => self::META_PREFIX . $key,
					'label' => $label,
					'value' => get_post_meta( $post->ID, self::META_PREFIX . $key, true ),
				)
			);
		}
		echo '</div></div>';
	}

	public static function save_fields( int $post_id ): void {
		foreach ( array_keys( self::manual_badge_keys() ) as $key ) {
			$meta_key = self::META_PREFIX . $key;
			update_post_meta( $post_id, $meta_key, isset( $_POST[ $meta_key ] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/** How many days after publish a product counts as "New". */
	public static function new_window_days(): int {
		return (int) apply_filters( 'helanak_badge_new_window_days', 14 );
	}

	/**
	 * Ordered list of active badges for a product: [ ['key' => ..., 'label' => ..., 'bg' => ..., 'fg' => ...], ... ]
	 * Highest-priority badge first; the theme decides how many to actually display.
	 */
	public static function get_badges( \WC_Product $product ): array {
		$colors = self::default_colors();
		$badges = array();

		$add = function ( string $key, string $label ) use ( &$badges, $colors ) {
			$badges[] = array(
				'key'   => $key,
				'label' => $label,
				'bg'    => $colors[ $key ][0] ?? '#EEEEEE',
				'fg'    => $colors[ $key ][1] ?? '#222222',
			);
		};

		if ( $product->is_on_sale() ) {
			$add( 'sale', __( 'Sale', 'helanak-core' ) );
		}

		foreach ( self::manual_badge_keys() as $key => $label ) {
			if ( 'yes' === get_post_meta( $product->get_id(), self::META_PREFIX . $key, true ) ) {
				$add( $key, $label );
			}
		}

		$published = get_post_field( 'post_date_gmt', $product->get_id() );
		if ( $published ) {
			$age_days = ( time() - strtotime( $published . ' GMT' ) ) / DAY_IN_SECONDS;
			if ( $age_days <= self::new_window_days() ) {
				$add( 'new', __( 'New', 'helanak-core' ) );
			}
		}

		if ( class_exists( 'Helanak_Core_Age_Taxonomy' ) ) {
			$terms = get_the_terms( $product->get_id(), Helanak_Core_Age_Taxonomy::TAXONOMY );
			if ( is_array( $terms ) && 1 === count( $terms ) ) {
				$is_fa = 'fa' === substr( get_locale(), 0, 2 );
				$label = Helanak_Core_Age_Taxonomy::localized_label( $terms[0], $is_fa );
				$add( 'age_specific', $label );
			}
		}

		return $badges;
	}
}
