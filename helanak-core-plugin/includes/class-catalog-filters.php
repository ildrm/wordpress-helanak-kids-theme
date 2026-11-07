<?php
/** Catalogue facets and public query rules; storefront markup belongs to the theme. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Catalog_Filters {

	private static $groups = null;

	public static function init(): void {
		add_action( 'pre_get_posts', array( __CLASS__, 'normalize_request' ), 1 );
		// WooCommerce sets its native query restrictions before this action.
		add_action( 'woocommerce_product_query', array( __CLASS__, 'apply' ), 20 );
		add_filter( 'woocommerce_redirect_single_search_result', array( __CLASS__, 'keep_search_results' ) );
	}

	/** Keep the search/filter form available even when only one product matches. */
	public static function keep_search_results( $redirect ) {
		return ! is_admin() && is_search() && 'product' === get_query_var( 'post_type' ) ? false : $redirect;
	}

	private static function definitions(): array {
		$definitions = array(
			array( 'key' => 'helanak_category', 'taxonomy' => 'product_cat', 'label' => __( 'Category', 'helanak-core' ), 'attribute' => false ),
			array( 'key' => 'helanak_brand', 'taxonomy' => 'product_brand', 'label' => __( 'Brand', 'helanak-core' ), 'attribute' => false ),
			array( 'key' => 'helanak_age', 'taxonomy' => 'age_range', 'label' => __( 'Age', 'helanak-core' ), 'attribute' => false ),
			array( 'key' => 'helanak_badge', 'taxonomy' => 'helanak_badge', 'label' => __( 'Product highlights', 'helanak-core' ), 'attribute' => false ),
		);
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$name = $attribute->attribute_name;
			$definitions[] = array(
				'key'        => 'helanak_attr_' . $name,
				'taxonomy'   => wc_attribute_taxonomy_name( $name ),
				'label'      => $attribute->attribute_label ?: $name,
				'attribute'  => true,
				'native_key' => 'filter_' . $name,
				'query_key'  => 'query_type_' . $name,
			);
		}
		return $definitions;
	}

	/** Return only real, populated facets, including non-public global attributes. */
	public static function groups(): array {
		if ( null !== self::$groups ) {
			return self::$groups;
		}
		self::$groups = array();
		foreach ( self::definitions() as $definition ) {
			if ( ! taxonomy_exists( $definition['taxonomy'] ) ) {
				continue;
			}
			$terms = get_terms( array( 'taxonomy' => $definition['taxonomy'], 'hide_empty' => true, 'orderby' => 'name', 'number' => 100 ) );
			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}
			$definition['terms'] = $terms;
			self::$groups[] = $definition;
		}
		return self::$groups;
	}

	private static function slugs( $raw ): array {
		$values = is_array( $raw ) ? $raw : ( is_string( $raw ) ? explode( ',', $raw ) : array() );
		$values = array_slice( array_filter( $values, 'is_scalar' ), 0, 100 );
		$slugs = array_map( 'sanitize_title', $values );
		return array_values( array_unique( array_filter( $slugs, static function ( $slug ) { return '' !== $slug; } ) ) );
	}

	public static function selected( string $key ): array {
		if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return self::slugs( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( 0 === strpos( $key, 'helanak_attr_' ) ) {
			$name = substr( $key, strlen( 'helanak_attr_' ) );
			$native = 'filter_' . $name;
			if ( isset( $_GET[ $native ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return self::slugs( wp_unslash( $_GET[ $native ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			if ( in_array( $name, array( 'color', 'colour', 'رنگ' ), true ) && isset( $_GET['helanak_color'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return self::slugs( wp_unslash( $_GET['helanak_color'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
		return array();
	}

	/** Scalar form state. Numeric price URLs use the canonical decimal point. */
	public static function value( string $key ): string {
		$raw = isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? wp_unslash( $_GET[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $key, array( 'min_price', 'max_price' ), true ) ) {
			$raw = strtr( trim( $raw ), array_combine( preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩٫', -1, PREG_SPLIT_NO_EMPTY ), str_split( '01234567890123456789.' ) ) );
			return strlen( $raw ) <= 24 && preg_match( '/^[0-9]+(?:\.[0-9]+)?$/D', $raw ) ? wc_format_decimal( $raw ) : '';
		}
		if ( 'helanak_stock' === $key ) {
			return 'instock' === $raw ? 'instock' : '';
		}
		if ( 'helanak_sale' === $key ) {
			return '1' === $raw ? '1' : '';
		}
		return mb_substr( sanitize_text_field( $raw ), 0, 100 );
	}

	/** Exclude these controls from hidden query fields and reset links. */
	public static function request_keys(): array {
		$keys = array( 's', 'min_price', 'max_price', 'helanak_stock', 'helanak_sale', 'helanak_color', 'rating_filter' );
		foreach ( self::definitions() as $definition ) {
			$keys[] = $definition['key'];
			if ( $definition['attribute'] ) {
				$keys[] = $definition['native_key'];
				$keys[] = $definition['query_key'];
			}
		}
		return array_values( array_unique( $keys ) );
	}

	private static function product_query( WP_Query $query ): bool {
		if ( is_admin() || ! $query->is_main_query() ) {
			return false;
		}
		return $query->is_post_type_archive( 'product' )
			|| $query->is_tax( get_object_taxonomies( 'product' ) )
			|| ( 'product' === $query->get( 'post_type' ) && $query->is_search() )
			|| ( wc_get_page_id( 'shop' ) > 0 && absint( $query->get( 'page_id' ) ) === wc_get_page_id( 'shop' ) );
	}

	public static function normalize_request( WP_Query $query ): void {
		if ( ! self::product_query( $query ) ) {
			return;
		}
		if ( isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_GET['s'] = self::value( 's' );
			$query->set( 's', $_GET['s'] );
			if ( '' === $_GET['s'] ) {
				// An empty form field is a catalogue visit, not an empty search.
				unset( $_GET['s'] );
				$query->is_search = false;
			}
		}
		foreach ( array( 'min_price', 'max_price' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$price = self::value( $key );
				if ( '' === $price ) {
					unset( $_GET[ $key ] );
				} else {
					$_GET[ $key ] = $price;
				}
			}
		}
		foreach ( self::definitions() as $definition ) {
			if ( ! $definition['attribute'] ) {
				continue;
			}
			$own = $definition['key'];
			$native = $definition['native_key'];
			$legacy = in_array( substr( $own, strlen( 'helanak_attr_' ) ), array( 'color', 'colour', 'رنگ' ), true ) && isset( $_GET['helanak_color'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET[ $own ] ) || $legacy ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// wc_clean() strips percent octets; Woo expects decoded UTF-8 here.
				$_GET[ $native ] = implode( ',', array_map( 'rawurldecode', self::selected( $own ) ) );
				$_GET[ $definition['query_key'] ] = 'or';
			} elseif ( isset( $_GET[ $native ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( is_string( $_GET[ $native ] ) ) {
					$_GET[ $native ] = implode( ',', array_map( 'rawurldecode', self::slugs( wp_unslash( $_GET[ $native ] ) ) ) );
				} else {
					unset( $_GET[ $native ] );
				}
			}
		}
		if ( is_callable( array( 'WC_Query', 'reset_chosen_attributes' ) ) ) {
			WC_Query::reset_chosen_attributes();
		}
	}

	private static function restrict_ids( WP_Query $query, array $ids ): void {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		$existing = (array) $query->get( 'post__in' );
		if ( $existing ) {
			$ids = array_values( array_intersect( $existing, $ids ) );
		}
		$query->set( 'post__in', $ids ?: array( 0 ) );
	}

	public static function apply( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$clauses = array();
		$existing = $query->get( 'tax_query' );
		if ( $existing ) {
			$clauses[] = $existing;
		}
		foreach ( self::definitions() as $definition ) {
			if ( $definition['attribute'] || ! taxonomy_exists( $definition['taxonomy'] ) ) {
				continue;
			}
			$selected = self::selected( $definition['key'] );
			if ( $selected ) {
				$clauses[] = array( 'taxonomy' => $definition['taxonomy'], 'field' => 'slug', 'terms' => $selected, 'operator' => 'IN', 'include_children' => is_taxonomy_hierarchical( $definition['taxonomy'] ) );
			}
		}
		if ( $clauses ) {
			$query->set( 'tax_query', array_merge( array( 'relation' => 'AND' ), $clauses ) );
		}
		if ( 'instock' === self::value( 'helanak_stock' ) ) {
			$meta = $query->get( 'meta_query' );
			$query->set( 'meta_query', array( 'relation' => 'AND', $meta ?: array(), array( 'key' => '_stock_status', 'value' => 'instock', 'compare' => '=' ) ) );
		}
		if ( '' !== $query->get( 's' ) ) {
			$query->set( 'search_columns', array( 'post_title' ) );
		}
		if ( isset( $_GET['helanak_wishlist'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw = isset( $_GET['helanak_wishlist'] ) && is_string( $_GET['helanak_wishlist'] ) ? wp_unslash( $_GET['helanak_wishlist'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::restrict_ids( $query, array_slice( explode( ',', $raw ), 0, 100 ) );
		}
		if ( '1' === self::value( 'helanak_sale' ) ) {
			self::restrict_ids( $query, wc_get_product_ids_on_sale() );
		}
	}
}
