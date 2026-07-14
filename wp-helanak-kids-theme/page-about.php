<?php
/**
 * Template Name: About
 *
 * Auto-selected for a page with the slug "about" via WordPress's
 * page-{slug}.php template hierarchy; the Template Name header also lets it
 * be assigned manually to any page from Page Attributes. Reuses
 * template-parts/section-friends-parade.php for the "Meet our friends"
 * mascot section instead of re-deriving mascot markup here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">

		<div class="helanak-page-hero">
			<h1><?php esc_html_e( 'Made with care, for little ones', 'wp-helanak-kids-theme' ); ?></h1>
			<p><?php esc_html_e( 'Helanak Kids started as a small family shop and grew into a place parents trust for safe, joyful clothing and toys.', 'wp-helanak-kids-theme' ); ?></p>
		</div>

		<div class="helanak-values">
			<div class="helanak-value-card">
				<span class="helanak-stat-number"><?php esc_html_e( '6+', 'wp-helanak-kids-theme' ); ?></span>
				<p><?php esc_html_e( 'Years in business', 'wp-helanak-kids-theme' ); ?></p>
			</div>
			<div class="helanak-value-card">
				<span class="helanak-stat-number"><?php esc_html_e( '40k', 'wp-helanak-kids-theme' ); ?></span>
				<p><?php esc_html_e( 'Happy families', 'wp-helanak-kids-theme' ); ?></p>
			</div>
			<div class="helanak-value-card">
				<span class="helanak-stat-number"><?php esc_html_e( '1200+', 'wp-helanak-kids-theme' ); ?></span>
				<p><?php esc_html_e( 'Products', 'wp-helanak-kids-theme' ); ?></p>
			</div>
		</div>

		<h2><?php esc_html_e( 'Why families trust us', 'wp-helanak-kids-theme' ); ?></h2>
		<div class="helanak-values">
			<div class="helanak-value-card">
				<h3><?php esc_html_e( '🛡️ Safety first', 'wp-helanak-kids-theme' ); ?></h3>
				<p><?php esc_html_e( 'Every product is checked against safety standards before it reaches our shelves.', 'wp-helanak-kids-theme' ); ?></p>
			</div>
			<div class="helanak-value-card">
				<h3><?php esc_html_e( '✨ Honest quality', 'wp-helanak-kids-theme' ); ?></h3>
				<p><?php esc_html_e( 'We choose materials that last through play, wash, and grow spurts alike.', 'wp-helanak-kids-theme' ); ?></p>
			</div>
			<div class="helanak-value-card">
				<h3><?php esc_html_e( '🎈 Everyday joy', 'wp-helanak-kids-theme' ); ?></h3>
				<p><?php esc_html_e( 'From packaging to product, we want every unboxing to feel like a little celebration.', 'wp-helanak-kids-theme' ); ?></p>
			</div>
		</div>

	</div>

	<?php get_template_part( 'template-parts/section', 'friends-parade' ); ?>
</main>

<?php
get_footer();
