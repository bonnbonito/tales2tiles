<?php
/**
 * Fallback template for posts, pages and archives (incl. the Order page).
 *
 * @package Tales2Tiles
 */

get_header();
?>

<main id="main" class="wrap">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<?php if ( is_singular() ) : ?>
					<?php the_title( '<h1>', '</h1>' ); ?>
					<?php the_content(); ?>
				<?php else : ?>
					<?php the_title( '<h2><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
					<?php the_excerpt(); ?>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p class="entry"><?php esc_html_e( 'Nothing found.', 'tales2tiles' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
