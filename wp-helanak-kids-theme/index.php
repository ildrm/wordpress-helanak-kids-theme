<?php
/**
 * Universal WordPress template-hierarchy fallback (search results, author/
 * date archives, and any request no more specific template claims). Every
 * WordPress theme is required to ship an index.php — see
 * https://developer.wordpress.org/themes/basics/main-index-file/. The blog
 * "posts page" itself is served by home.php, not this file, when one is set
 * (Settings > Reading) — this stays intentionally generic underneath it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">

		<div class="helanak-page-hero">
			<h1>
				<?php
				if ( is_search() ) {
					printf(
						/* translators: %s: search query */
						esc_html__( 'Search results for "%s"', 'wp-helanak-kids-theme' ),
						esc_html( get_search_query() )
					);
				} else {
					esc_html_e( 'Latest posts', 'wp-helanak-kids-theme' );
				}
				?>
			</h1>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="helanak-blog-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<a class="helanak-blog-card" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<span class="helanak-blog-card__media"><?php the_post_thumbnail( 'medium' ); ?></span>
						<?php endif; ?>
						<span class="helanak-blog-card__body">
							<h3><?php the_title(); ?></h3>
							<span class="helanak-blog-card__meta"><?php echo esc_html( get_the_date() ); ?></span>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found.', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
