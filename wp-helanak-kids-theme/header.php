<?php
/**
 * Site header: doctype/head, announcement ticker, sticky header row
 * (logo, primary nav, search, wishlist/cart icons, mobile nav toggle).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="helanak-ticker" data-helanak-section="announcement"<?php helanak_section_hidden_attr( 'announcement' ); ?>>
	<div class="helanak-ticker__track" aria-hidden="true">
		<?php
		$helanak_announcement = get_theme_mod(
			'helanak_announcement_text',
			__( 'Free shipping over $50 · New seasonal arrivals · Safe, tested materials', 'wp-helanak-kids-theme' )
		);
		for ( $helanak_ticker_i = 0; $helanak_ticker_i < 2; $helanak_ticker_i++ ) :
			?>
			<span><?php echo esc_html( $helanak_announcement ); ?></span>
		<?php endfor; ?>
	</div>
</div>

<header class="helanak-header">
	<div class="helanak-container helanak-header__row">
		<a class="helanak-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="helanak-logo__mark" aria-hidden="true">H</span>
				<span><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="helanak-nav" aria-label="<?php esc_attr_e( 'Primary', 'wp-helanak-kids-theme' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>

		<form class="helanak-header__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="product" />
			<label class="screen-reader-text" for="helanak-search-input"><?php esc_html_e( 'Search products', 'wp-helanak-kids-theme' ); ?></label>
			<input
				id="helanak-search-input"
				type="search"
				name="s"
				placeholder="<?php esc_attr_e( 'Search for toys, clothes…', 'wp-helanak-kids-theme' ); ?>"
				value="<?php echo esc_attr( get_search_query() ); ?>"
			/>
		</form>

		<div class="helanak-header__icons">
			<div class="helanak-lang-switch" role="group" aria-label="<?php esc_attr_e( 'Language', 'wp-helanak-kids-theme' ); ?>">
				<?php foreach ( array( 'en' => 'EN', 'fa' => 'فارسی' ) as $helanak_lang_code => $helanak_lang_label ) : ?>
					<?php if ( helanak_current_lang() === $helanak_lang_code ) : ?>
						<span class="helanak-lang-switch__item is-active" aria-current="true"><?php echo esc_html( $helanak_lang_label ); ?></span>
					<?php else : ?>
						<a class="helanak-lang-switch__item" href="<?php echo esc_url( helanak_lang_switch_url( $helanak_lang_code ) ); ?>"><?php echo esc_html( $helanak_lang_label ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<a
				class="helanak-header__icon"
				data-helanak-wishlist-nav
				href="<?php echo esc_url( function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Wishlist', 'wp-helanak-kids-theme' ); ?>"
			>
				<span aria-hidden="true">♡</span>
				<span class="helanak-count" data-helanak-wishlist-count hidden>0</span>
			</a>
			<a
				class="helanak-header__icon"
				data-helanak-cart-toggle
				aria-haspopup="true"
				aria-expanded="false"
				aria-controls="helanak-mini-cart-drawer"
				href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Cart', 'wp-helanak-kids-theme' ); ?>"
			>
				<span aria-hidden="true">🛍️</span>
				<?php echo helanak_cart_count_badge(); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped markup. ?>
			</a>
			<button
				type="button"
				class="helanak-nav-toggle"
				data-helanak-nav-toggle
				aria-expanded="false"
				aria-controls="helanak-mobile-nav"
				aria-label="<?php esc_attr_e( 'Menu', 'wp-helanak-kids-theme' ); ?>"
			>
				<span aria-hidden="true">&#9776;</span>
			</button>
		</div>
	</div>

	<nav
		id="helanak-mobile-nav"
		class="helanak-nav--mobile"
		data-helanak-mobile-nav
		hidden
		aria-label="<?php esc_attr_e( 'Primary', 'wp-helanak-kids-theme' ); ?>"
	>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>
</header>
