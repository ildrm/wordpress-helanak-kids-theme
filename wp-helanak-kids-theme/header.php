<?php
/** Site header and accessible store navigation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$helanak_announcement = get_theme_mod( 'helanak_announcement_text', __( 'Little discoveries for every stage of growing up', 'wp-helanak-kids-theme' ) );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="helanak-skip-link" href="#helanak-main"><?php esc_html_e( 'Skip to content', 'wp-helanak-kids-theme' ); ?></a>
<div class="helanak-ticker" data-helanak-section="announcement"<?php helanak_section_hidden_attr( 'announcement' ); ?>>
	<span class="screen-reader-text"><?php echo esc_html( $helanak_announcement ); ?></span>
	<div class="helanak-ticker__viewport" aria-hidden="true"><div class="helanak-ticker__track"><span><?php echo esc_html( $helanak_announcement ); ?></span><span><?php echo esc_html( $helanak_announcement ); ?></span></div></div>
	<button type="button" class="helanak-motion-toggle helanak-motion-toggle--compact" data-helanak-motion-toggle aria-pressed="false"><svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true"><path d="M7 4v12M13 4v12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg><span data-helanak-motion-label><?php esc_html_e( 'Pause animations', 'wp-helanak-kids-theme' ); ?></span></button>
</div>
<header class="helanak-header">
	<div class="helanak-container helanak-header__row">
		<div class="helanak-logo">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( add_query_arg( 'lang', helanak_current_lang(), home_url( '/' ) ) ); ?>" rel="home"><span class="helanak-logo__mark" aria-hidden="true">H</span><span class="helanak-logo__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span></a>
			<?php endif; ?>
		</div>
		<nav class="helanak-nav helanak-nav--desktop" aria-label="<?php esc_attr_e( 'Primary', 'wp-helanak-kids-theme' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_id' => 'helanak-primary-menu', 'menu_class' => 'helanak-menu', 'fallback_cb' => 'helanak_primary_menu_fallback' ) ); ?></nav>
		<form class="helanak-header__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="product"><input type="hidden" name="lang" value="<?php echo esc_attr( helanak_current_lang() ); ?>">
			<label class="screen-reader-text" for="helanak-search-input"><?php esc_html_e( 'Search products', 'wp-helanak-kids-theme' ); ?></label>
			<input id="helanak-search-input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search for toys, clothes…', 'wp-helanak-kids-theme' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
			<button type="submit" class="helanak-search-submit" aria-label="<?php esc_attr_e( 'Search products', 'wp-helanak-kids-theme' ); ?>"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
		</form>
		<div class="helanak-lang-switch" role="group" aria-label="<?php esc_attr_e( 'Language', 'wp-helanak-kids-theme' ); ?>">
			<?php foreach ( array( 'en' => 'EN', 'fa' => 'فارسی' ) as $helanak_lang_code => $helanak_lang_label ) : ?>
				<?php if ( helanak_current_lang() === $helanak_lang_code ) : ?><span class="helanak-lang-switch__item is-active" aria-current="true"><?php echo esc_html( $helanak_lang_label ); ?></span>
				<?php else : ?><a class="helanak-lang-switch__item" href="<?php echo esc_url( helanak_lang_switch_url( $helanak_lang_code ) ); ?>" lang="<?php echo esc_attr( $helanak_lang_code ); ?>"><?php echo esc_html( $helanak_lang_label ); ?></a><?php endif; ?>
			<?php endforeach; ?>
		</div>
		<div class="helanak-header__icons">
			<a class="helanak-header__icon" data-helanak-wishlist-nav href="<?php echo esc_url( add_query_arg( array( 'helanak_wishlist' => '0', 'lang' => helanak_current_lang() ), function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Wishlist', 'wp-helanak-kids-theme' ); ?>"><svg viewBox="0 0 24 24" width="21" height="21" aria-hidden="true"><path d="M20 5.5a5 5 0 0 0-8 1 5 5 0 0 0-8-1C.5 9.5 5 15 12 20c7-5 11.5-10.5 8-14.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg><span class="helanak-count" data-helanak-wishlist-count hidden>0</span></a>
			<a class="helanak-header__icon" data-helanak-cart-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="helanak-mini-cart-drawer" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'wp-helanak-kids-theme' ); ?>"><svg viewBox="0 0 24 24" width="21" height="21" aria-hidden="true"><path d="M5 8h14l1 12H4L5 8Zm3 0V6a4 4 0 0 1 8 0v2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg><?php echo helanak_cart_count_badge(); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped markup. ?></a>
			<button type="button" class="helanak-nav-toggle" data-helanak-nav-toggle aria-expanded="false" aria-controls="helanak-mobile-nav" aria-label="<?php esc_attr_e( 'Menu', 'wp-helanak-kids-theme' ); ?>"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>
		</div>
	</div>
	<nav id="helanak-mobile-nav" class="helanak-nav--mobile" data-helanak-mobile-nav hidden aria-label="<?php esc_attr_e( 'Primary', 'wp-helanak-kids-theme' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_id' => 'helanak-mobile-menu', 'menu_class' => 'helanak-menu', 'fallback_cb' => 'helanak_primary_menu_fallback' ) ); ?></nav>
	<noscript><nav class="helanak-nav--no-script" aria-label="<?php esc_attr_e( 'Primary', 'wp-helanak-kids-theme' ); ?>"><?php helanak_primary_menu_fallback(); ?></nav></noscript>
</header>
