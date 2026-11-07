<?php
/** Owned, disposable local catalogue fixtures; never deploy this tool. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME || 'localhost' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'The dedicated local clone is required.' );
}
$helanak_file = 'D:/prj/wordpress-helanak-kids-theme/.local/audit/seven-issues/catalog-fixtures.json';
$helanak_data = is_file( $helanak_file ) ? json_decode( file_get_contents( $helanak_file ), true ) : array();
if ( 'cleanup' === ( $args[0] ?? '' ) ) {
	foreach ( array_reverse( $helanak_data['posts'] ?? array() ) as $helanak_id ) {
		if ( '1' !== get_post_meta( $helanak_id, '_helanak_qa', true ) ) {
			throw new RuntimeException( 'Missing QA product ownership marker.' );
		}
		wp_delete_post( $helanak_id, true );
	}
	foreach ( $helanak_data['terms'] ?? array() as $helanak_term ) {
		if ( '1' !== get_term_meta( $helanak_term['id'], '_helanak_catalog_qa', true ) ) {
			throw new RuntimeException( 'Missing QA term ownership marker.' );
		}
		wp_delete_term( $helanak_term['id'], $helanak_term['taxonomy'] );
	}
	if ( is_file( $helanak_file ) ) {
		rename( $helanak_file, dirname( $helanak_file ) . '/catalog-fixtures-completed-' . time() . '.json' );
	}
	echo 'Owned local catalogue fixtures removed.' . PHP_EOL;
	return;
}
if ( $helanak_data && 'reindex' === ( $args[0] ?? '' ) ) {
	$helanak_lookup = wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore::class );
	foreach ( $helanak_data['ids'] as $helanak_id ) {
		if ( '1' !== get_post_meta( $helanak_id, '_helanak_qa', true ) ) {
			throw new RuntimeException( 'Missing fixture ownership marker.' );
		}
		$helanak_lookup->create_data_for_product( $helanak_id );
	}
	echo 'Owned fixture attribute indexes regenerated.' . PHP_EOL;
	return;
}
if ( $helanak_data ) {
	echo wp_json_encode( $helanak_data ) . PHP_EOL;
	return;
}
$helanak_data = array( 'posts' => array(), 'terms' => array(), 'ids' => array(), 'slugs' => array() );
$helanak_taxonomy = wc_attribute_taxonomy_name( 'رنگ' );
$helanak_attribute_id = wc_attribute_taxonomy_id_by_name( 'رنگ' );
if ( ! $helanak_attribute_id || ! taxonomy_exists( $helanak_taxonomy ) ) {
	throw new RuntimeException( 'The real Unicode colour attribute is required for this check.' );
}
$helanak_term_specs = array(
	'category' => array( 'product_cat', 'Helanak QA category' ),
	'brand_a'  => array( 'product_brand', 'Helanak QA brand A' ),
	'brand_b'  => array( 'product_brand', 'Helanak QA brand B' ),
	'age_a'    => array( 'age_range', 'Helanak QA age A' ),
	'age_b'    => array( 'age_range', 'Helanak QA age B' ),
	'badge'    => array( 'helanak_badge', 'Helanak QA highlight' ),
	'cyan'     => array( $helanak_taxonomy, 'Helanak QA cyan' ),
	'magenta'  => array( $helanak_taxonomy, 'Helanak QA magenta' ),
);
foreach ( $helanak_term_specs as $helanak_key => $helanak_spec ) {
	$helanak_slug = 'helanak-catalog-qa-' . str_replace( '_', '-', $helanak_key );
	// Exercise WordPress's percent-encoded Persian term slugs, not only ASCII.
	if ( in_array( $helanak_key, array( 'cyan', 'magenta' ), true ) ) {
		$helanak_slug .= '-' . ( 'cyan' === $helanak_key ? 'آبی' : 'ارغوانی' );
	}
	if ( term_exists( $helanak_slug, $helanak_spec[0] ) ) {
		throw new RuntimeException( 'An untracked fixture term already exists.' );
	}
	$helanak_term = wp_insert_term( $helanak_spec[1], $helanak_spec[0], array( 'slug' => $helanak_slug ) );
	if ( is_wp_error( $helanak_term ) ) {
		throw new RuntimeException( $helanak_term->get_error_message() );
	}
	$helanak_term_id = (int) $helanak_term['term_id'];
	update_term_meta( $helanak_term_id, '_helanak_catalog_qa', '1' );
	$helanak_data['terms'][] = array( 'id' => $helanak_term_id, 'taxonomy' => $helanak_spec[0] );
	$helanak_data['slugs'][ $helanak_key ] = get_term( $helanak_term_id, $helanak_spec[0] )->slug;
	$helanak_data['term_ids'][ $helanak_key ] = $helanak_term_id;
}
foreach ( array( 'alpha', 'beta', 'gamma', 'hidden' ) as $helanak_key ) {
	$helanak_product = 'alpha' === $helanak_key ? new WC_Product_Variable() : new WC_Product_Simple();
	$helanak_product->set_name( 'Helanak Catalog QA ' . ucfirst( $helanak_key ) );
	$helanak_product->set_status( 'publish' );
	$helanak_product->set_category_ids( array( $helanak_data['term_ids']['category'] ) );
	$helanak_product->set_stock_status( 'beta' === $helanak_key ? 'outofstock' : 'instock' );
	$helanak_color = 'gamma' === $helanak_key ? 'magenta' : 'cyan';
	$helanak_attribute = new WC_Product_Attribute();
	$helanak_attribute->set_id( $helanak_attribute_id );
	$helanak_attribute->set_name( $helanak_taxonomy );
	$helanak_attribute->set_options( array( $helanak_data['term_ids'][ $helanak_color ] ) );
	$helanak_attribute->set_visible( true );
	$helanak_attribute->set_variation( 'alpha' === $helanak_key );
	$helanak_product->set_attributes( array( $helanak_attribute ) );
	if ( 'alpha' !== $helanak_key ) {
		$helanak_product->set_regular_price( 'beta' === $helanak_key ? '150' : '300' );
		if ( 'gamma' === $helanak_key ) {
			$helanak_product->set_sale_price( '250' );
		}
	}
	if ( 'beta' === $helanak_key ) {
		$helanak_product->set_description( 'Helanak Catalog QA Alpha appears only in this description.' );
	}
	if ( 'hidden' === $helanak_key ) {
		$helanak_product->set_catalog_visibility( 'hidden' );
	}
	$helanak_id = $helanak_product->save();
	update_post_meta( $helanak_id, '_helanak_qa', '1' );
	$helanak_data['ids'][ $helanak_key ] = $helanak_id;
	$helanak_data['posts'][] = $helanak_id;
	wp_set_object_terms( $helanak_id, array( $helanak_data['term_ids'][ 'beta' === $helanak_key ? 'brand_b' : 'brand_a' ] ), 'product_brand' );
	wp_set_object_terms( $helanak_id, array( $helanak_data['term_ids'][ 'gamma' === $helanak_key ? 'age_b' : 'age_a' ] ), 'age_range' );
	if ( in_array( $helanak_key, array( 'alpha', 'gamma' ), true ) ) {
		wp_set_object_terms( $helanak_id, array( $helanak_data['term_ids']['badge'] ), 'helanak_badge' );
	}
}
$helanak_variation = new WC_Product_Variation();
$helanak_variation->set_parent_id( $helanak_data['ids']['alpha'] );
$helanak_variation->set_attributes( array( $helanak_taxonomy => $helanak_data['slugs']['cyan'] ) );
$helanak_variation->set_regular_price( '100' );
$helanak_variation->set_sale_price( '80' );
$helanak_variation->set_stock_status( 'instock' );
$helanak_id = $helanak_variation->save();
update_post_meta( $helanak_id, '_helanak_qa', '1' );
$helanak_data['posts'][] = $helanak_id;
WC_Product_Variable::sync( $helanak_data['ids']['alpha'] );
delete_transient( 'wc_products_onsale' );
$helanak_data['attribute_key'] = 'helanak_attr_رنگ';
file_put_contents( $helanak_file, wp_json_encode( $helanak_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
echo wp_json_encode( $helanak_data, JSON_UNESCAPED_UNICODE ) . PHP_EOL;
