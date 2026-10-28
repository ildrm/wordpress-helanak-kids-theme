<?php
/** Optimize generated raster sizes while retaining original uploads. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Core_Media {
	public static function init(): void {
		add_filter( 'image_editor_output_format', array( __CLASS__, 'output_formats' ) );
	}

	public static function output_formats( array $formats ): array {
		if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formats += array( 'image/jpeg' => 'image/webp', 'image/png' => 'image/webp' );
		}
		return $formats;
	}
}
