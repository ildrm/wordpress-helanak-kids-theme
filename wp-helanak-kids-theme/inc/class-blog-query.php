<?php
/** Align the Posts page's main query with its featured story and card grid. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helanak_Theme_Blog_Query {

	public static function init(): void {
		add_action( 'pre_get_posts', array( __CLASS__, 'configure' ) );
	}

	public static function configure( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() || $query->is_feed() ) {
			return;
		}

		$featured_id = self::featured_post_id();
		$excluded    = array_filter( array_map( 'absint', (array) $query->get( 'post__not_in' ) ) );
		if ( $featured_id ) {
			$excluded[] = $featured_id;
		}

		$query->set( 'post_type', 'post' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'posts_per_page', 9 );
		$query->set( 'ignore_sticky_posts', true );
		$query->set( 'post__not_in', array_values( array_unique( $excluded ) ) );
		$query->set( 'orderby', array( 'date' => 'DESC', 'ID' => 'DESC' ) );
		$query->set( 'helanak_featured_post_id', $featured_id );
	}

	private static function featured_post_id(): int {
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'has_password'        => false,
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'orderby'             => array( 'date' => 'DESC', 'ID' => 'DESC' ),
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		);
		$sticky = array_filter( array_map( 'absint', (array) get_option( 'sticky_posts', array() ) ) );
		if ( $sticky ) {
			$featured = get_posts( array_merge( $args, array( 'post__in' => $sticky ) ) );
			if ( $featured ) {
				return (int) $featured[0];
			}
		}

		$featured = get_posts( $args );
		return $featured ? (int) $featured[0] : 0;
	}
}
