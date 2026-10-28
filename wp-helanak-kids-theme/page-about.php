<?php
/** Template Name: About */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">
		<?php while ( have_posts() ) : the_post(); ?>
			<div class="helanak-page-hero helanak-page-hero--about"><div><span class="helanak-page-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span><h1><?php the_title(); ?></h1><p><?php esc_html_e( 'A little world of discovery', 'wp-helanak-kids-theme' ); ?></p></div><?php helanak_character( 'bear', array( 'scale' => .7, 'wave' => true ) ); ?></div>
			<div class="helanak-page-content entry-content">
				<?php if ( trim( get_the_content() ) ) : ?><?php the_content(); ?>
				<?php else : ?><p><?php esc_html_e( 'Explore clothes, toys, and everyday finds for little ones.', 'wp-helanak-kids-theme' ); ?></p><?php endif; ?>
			</div>
		<?php endwhile; ?>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?><a class="helanak-btn helanak-btn--dark" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Explore the shop', 'wp-helanak-kids-theme' ); ?></a><?php endif; ?>
	</div>
	<?php get_template_part( 'template-parts/section', 'friends-parade' ); ?>
</main>
<?php get_footer(); ?>
