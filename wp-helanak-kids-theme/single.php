<?php
/**
 * Single blog post. Not part of the original 9-page prototype set (which
 * only mocked the blog listing, not a post-detail view) — kept intentionally
 * simple: title, real date/reading-time meta, featured image, post content,
 * and native WP comments if enabled.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="helanak-main" class="helanak-main">
		<div class="helanak-container helanak-single-post">
			<div class="helanak-page-hero">
				<h1><?php the_title(); ?></h1>
				<p class="helanak-blog-card__meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: post date, 2: estimated reading time */
							__( '%1$s · %2$s', 'wp-helanak-kids-theme' ),
							get_the_date(),
							helanak_reading_time( get_post() )
						)
					);
					?>
				</p>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="helanak-single-post__media"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>

			<div class="helanak-single-post__content">
				<?php the_content(); ?>
			</div>

			<?php if ( comments_open() || get_comments_number() ) : ?>
				<?php comments_template(); ?>
			<?php endif; ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
