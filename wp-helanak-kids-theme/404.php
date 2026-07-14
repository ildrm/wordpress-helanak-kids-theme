<?php
/**
 * Playful 404: a confused penguin, honest copy, and useful ways out (home,
 * shop, search). Without this file WordPress fell back to index.php's generic
 * blog listing, which read like a broken page rather than a missing one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$helanak_shop_permalink = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">
		<div class="helanak-404">
			<div class="helanak-404__mascot">
				<?php helanak_character( 'penguin', array( 'scale' => 0.9 ) ); ?>
			</div>
			<p class="helanak-404__code" aria-hidden="true">404</p>
			<h1><?php esc_html_e( 'Oops — this page wandered off', 'wp-helanak-kids-theme' ); ?></h1>
			<p class="helanak-404__sub"><?php esc_html_e( "Even our penguin can't find it. Let's get you back to the fun stuff.", 'wp-helanak-kids-theme' ); ?></p>
			<div class="helanak-404__actions">
				<a class="helanak-btn helanak-btn--dark" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Back to home', 'wp-helanak-kids-theme' ); ?>
				</a>
				<a class="helanak-btn helanak-btn--outline" href="<?php echo esc_url( $helanak_shop_permalink ); ?>">
					<?php esc_html_e( 'Browse the shop', 'wp-helanak-kids-theme' ); ?>
				</a>
			</div>
			<form class="helanak-404__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="helanak-404-search"><?php esc_html_e( 'Search', 'wp-helanak-kids-theme' ); ?></label>
				<input id="helanak-404-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Search for toys, clothes…', 'wp-helanak-kids-theme' ); ?>" />
				<input type="hidden" name="post_type" value="product" />
				<button type="submit" class="helanak-btn helanak-btn--primary"><?php esc_html_e( 'Search', 'wp-helanak-kids-theme' ); ?></button>
			</form>
		</div>
	</div>
</main>

<?php
get_footer();
