<?php
/** Remove only explicitly tracked disposable QA records in the local clone. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_base = 'D:/prj/wordpress-helanak-kids-theme/.local/audit/';
foreach ( array( 'qa-products.json', 'qa-posts.json' ) as $helanak_name ) {
	$helanak_file = $helanak_base . $helanak_name;
	if ( ! is_file( $helanak_file ) ) { continue; }
	$helanak_data = json_decode( file_get_contents( $helanak_file ), true );
	$helanak_ids = isset( $helanak_data['created'] ) ? $helanak_data['created'] : $helanak_data;
	$helanak_ids = array_map( 'absint', $helanak_ids );
	rsort( $helanak_ids );
	foreach ( $helanak_ids as $helanak_id ) {
		$helanak_post = get_post( $helanak_id );
		if ( ! $helanak_post ) { continue; }
		$helanak_owned = '1' === get_post_meta( $helanak_id, '_helanak_qa', true );
		$helanak_owned_variation = 'product_variation' === $helanak_post->post_type && in_array( (int) $helanak_post->post_parent, $helanak_ids, true ) && '1' === get_post_meta( $helanak_post->post_parent, '_helanak_qa', true );
		if ( ! $helanak_owned && ! $helanak_owned_variation ) { throw new RuntimeException( 'Refusing to remove a record not owned by these QA scripts.' ); }
		wp_delete_post( $helanak_id, true );
	}
	rename( $helanak_file, $helanak_base . str_replace( '.json', '-completed-' . time() . '.json', $helanak_name ) );
}
echo 'Tracked disposable QA products and posts removed.' . PHP_EOL;
