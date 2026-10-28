<?php
/** Summarize actual generated image bytes in the dedicated local clone. */
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) {
	throw new RuntimeException( 'Local clone required.' );
}
$helanak_uploads = wp_get_upload_dir();
$helanak_rows = array();
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids' ) ) as $helanak_id ) {
	$helanak_meta = wp_get_attachment_metadata( $helanak_id );
	if ( empty( $helanak_meta['file'] ) ) { continue; }
	foreach ( $helanak_meta['sizes'] ?? array() as $helanak_size => $helanak_data ) {
		$helanak_relative = dirname( $helanak_meta['file'] ) . '/' . $helanak_data['file'];
		$helanak_file = $helanak_uploads['basedir'] . '/' . $helanak_relative;
		if ( is_file( $helanak_file ) ) {
			$helanak_rows[] = array( 'id' => $helanak_id, 'size' => $helanak_size, 'file' => $helanak_relative, 'bytes' => filesize( $helanak_file ), 'mime' => $helanak_data['mime-type'] );
		}
	}
}
$helanak_report = array( 'sizes' => count( $helanak_rows ), 'bytes' => array_sum( array_column( $helanak_rows, 'bytes' ) ), 'rows' => $helanak_rows );
file_put_contents( 'D:/prj/wordpress-helanak-kids-theme/.local/audit/media-report.json', wp_json_encode( $helanak_report, JSON_PRETTY_PRINT ) );
echo wp_json_encode( array( 'sizes' => $helanak_report['sizes'], 'bytes' => $helanak_report['bytes'] ) ) . PHP_EOL;
