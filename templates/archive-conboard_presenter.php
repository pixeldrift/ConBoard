<?php
// templates/archive-conboard_session.php

/**
 * Archive: Presenters
 */

get_header();

$query = new WP_Query([
	'post_type'			=> 'presenter',
	'posts_per_page'	=> -1,
	'orderby'			=> 'title',
	'order'				=> 'ASC',
]);

?>

<main class="conboard conboard-presenters">

	<h1>Presenters</h1>

	<?php if ($query->have_posts()) : ?>

		<ul class="conboard-presenter-grid">

			<?php while ($query->have_posts()) : $query->the_post(); ?>

				<li class="presenter">

					<a href="<?php the_permalink(); ?>">

						<?php if (has_post_thumbnail()) : ?>
							<div class="presenter-photo">
								<?php the_post_thumbnail('medium'); ?>
							</div>
						<?php endif; ?>

						<h2><?php the_title(); ?></h2>

						<?php
						$title = get_post_meta(get_the_ID(), 'presenter_title', true);
						if ($title) :
						?>
							<div class="presenter-title">
								<?php echo esc_html($title); ?>
							</div>
						<?php endif; ?>

					</a>

				</li>

			<?php endwhile; ?>

		</ul>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>

		<p>No presenters found.</p>

	<?php endif; ?>

</main>

<?php get_footer(); ?>
