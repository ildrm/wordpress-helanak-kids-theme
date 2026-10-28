<?php
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_file = 'D:/prj/wordpress-helanak-kids-theme/.local/audit/qa-posts.json';
$helanak_ids = is_file( $helanak_file ) ? json_decode( file_get_contents( $helanak_file ), true ) : array();
for ( $helanak_i = count( $helanak_ids ); $helanak_i < 19; $helanak_i++ ) {
	$helanak_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_author' => 2, 'post_title' => 'Helanak disposable pagination QA ' . $helanak_i, 'post_name' => 'helanak-pagination-qa-' . $helanak_i, 'post_content' => 'Disposable local pagination check.', 'post_date' => wp_date( 'Y-m-d H:i:s', time() - $helanak_i * MINUTE_IN_SECONDS ) ), true );
	if ( is_wp_error( $helanak_id ) ) { throw new RuntimeException( $helanak_id->get_error_message() ); }
	update_post_meta( $helanak_id, '_helanak_qa', '1' );
	$helanak_ids[] = $helanak_id;
}
file_put_contents( $helanak_file, wp_json_encode( $helanak_ids ) );
echo wp_json_encode( $helanak_ids ) . PHP_EOL;
