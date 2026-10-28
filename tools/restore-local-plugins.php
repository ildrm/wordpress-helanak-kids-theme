<?php
if ( 'local' !== wp_get_environment_type() || 'helanak_local' !== DB_NAME ) { throw new RuntimeException( 'Local clone required.' ); }
$helanak_plugins = json_decode( file_get_contents( 'D:/prj/wordpress-helanak-kids-theme/.local/audit/plugins-before-dependency-check.json' ), true );
if ( ! is_array( $helanak_plugins ) || ! in_array( 'woocommerce/woocommerce.php', $helanak_plugins, true ) || ! in_array( 'helanak-core-plugin/helanak-core.php', $helanak_plugins, true ) ) {
	throw new RuntimeException( 'The original local plugin snapshot is invalid.' );
}
update_option( 'active_plugins', $helanak_plugins );
echo 'Original local plugin activation state restored.' . PHP_EOL;
