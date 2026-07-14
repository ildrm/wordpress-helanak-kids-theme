<?php
/**
 * Core theme supports, nav menus, and image sizes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Theme_Setup {

	public static function init(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'setup' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_sidebars' ) );
	}

	public static function setup(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'custom-logo', array(
			'height'      => 60,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
		) );
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		) );

		// WooCommerce.
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		load_theme_textdomain( 'wp-helanak-kids-theme', HELANAK_THEME_DIR . '/languages' );

		register_nav_menus( array(
			'primary' => __( 'Primary Menu', 'wp-helanak-kids-theme' ),
			'footer'  => __( 'Footer Menu', 'wp-helanak-kids-theme' ),
		) );

		set_post_thumbnail_size( 600, 600, true );
		add_image_size( 'helanak-card', 480, 480, true );
		add_image_size( 'helanak-hero', 1400, 900, true );

		global $content_width;
		if ( ! isset( $content_width ) ) {
			$content_width = 1180; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}

	public static function register_sidebars(): void {
		register_sidebar( array(
			'name'          => __( 'Shop Sidebar', 'wp-helanak-kids-theme' ),
			'id'            => 'shop-sidebar',
			'description'   => __( 'Shown on the shop archive alongside the filter panel.', 'wp-helanak-kids-theme' ),
			'before_widget' => '<div class="helanak-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="helanak-widget-title">',
			'after_title'   => '</h3>',
		) );
	}
}
