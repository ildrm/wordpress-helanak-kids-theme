<?php
/**
 * Blog index — maps to page-blog.dc.html per the prototype→template table in
 * the root CLAUDE.md. WordPress uses home.php specifically for the site's
 * "Posts page" (Settings > Reading); any other blog-adjacent request (search,
 * author/date archives) falls through to index.php instead. The featured
 * slot uses the newest sticky post if one exists, else simply the latest
 * post — never a hand-picked placeholder — and the grid below excludes
 * whichever post is already featured so nothing appears twice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$helanak_paged         = max( 1, (int) get_query_var( 'paged' ) );
$helanak_featured_id   = absint( get_query_var( 'helanak_featured_post_id' ) );
$helanak_featured_post = ( 1 === $helanak_paged && $helanak_featured_id ) ? get_post( $helanak_featured_id ) : null;
?>

<main id="helanak-main" class="helanak-main">
	<div class="helanak-container">

		<div class="helanak-page-hero">
			<h1><?php esc_html_e( 'Stories, tips & new arrivals', 'wp-helanak-kids-theme' ); ?></h1>
			<p><?php esc_html_e( 'Sizing guides, seasonal picks, and a peek behind the scenes at Helanak Kids.', 'wp-helanak-kids-theme' ); ?></p>
			<?php
			// The prototype's celebrateBird: a little bird beside the page title.
			helanak_character( 'bird', array( 'scale' => 1.4 ) );
			?>
		</div>

		<?php if ( $helanak_featured_post ) : ?>
			<?php
			$helanak_featured_cats  = get_the_category( $helanak_featured_post );
			$helanak_featured_image = get_the_post_thumbnail( $helanak_featured_post, 'large' );
			?>
			<a class="helanak-blog-featured<?php echo $helanak_featured_image ? '' : ' helanak-blog-featured--text-only'; ?>" href="<?php echo esc_url( get_permalink( $helanak_featured_post ) ); ?>">
				<?php if ( $helanak_featured_image ) : ?>
					<span class="helanak-blog-featured__media">
						<?php echo $helanak_featured_image; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-escaped markup. ?>
					</span>
				<?php endif; ?>
				<span class="helanak-blog-featured__body">
					<span class="helanak-blog-featured__tags">
						<span class="helanak-badge"><?php esc_html_e( '★ Featured', 'wp-helanak-kids-theme' ); ?></span>
						<?php if ( ! empty( $helanak_featured_cats ) ) : ?>
							<span class="helanak-blog-featured__cat"><?php echo esc_html( $helanak_featured_cats[0]->name ); ?></span>
						<?php endif; ?>
					</span>
					<h2><?php echo esc_html( get_the_title( $helanak_featured_post ) ); ?></h2>
					<span class="helanak-blog-featured__meta">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: post date, 2: estimated reading time */
								__( '%1$s · %2$s', 'wp-helanak-kids-theme' ),
								get_the_date( '', $helanak_featured_post ),
								helanak_reading_time( $helanak_featured_post )
							)
						);
						?>
					</span>
				</span>
			</a>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="helanak-blog-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<?php $helanak_card_cats = get_the_category(); ?>
					<a class="helanak-blog-card" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<span class="helanak-blog-card__media"><?php the_post_thumbnail( 'medium' ); ?></span>
						<?php endif; ?>
						<span class="helanak-blog-card__body">
							<?php if ( ! empty( $helanak_card_cats ) ) : ?>
								<span class="helanak-blog-card__cat"><?php echo esc_html( $helanak_card_cats[0]->name ); ?></span>
							<?php endif; ?>
							<h3><?php the_title(); ?></h3>
							<span class="helanak-blog-card__meta">
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
							</span>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php
			the_posts_pagination();
			?>
		<?php elseif ( ! $helanak_featured_post ) : ?>
			<p><?php esc_html_e( 'No posts yet — check back soon!', 'wp-helanak-kids-theme' ); ?></p>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
