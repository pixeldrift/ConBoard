<?php
// templates/archive-conboard_session.php

/**
 * Archive: Sessions
 */

get_header();

$now = current_time('timestamp');

// Query all sessions, sorted by start time
$query = new WP_Query([
	'post_type'			=> 'session',
	'posts_per_page'	=> -1,
	'meta_key'			=> 'start_time',
	'orderby'			=> 'meta_value',
	'order'				=> 'ASC',
]);

?>

<main class="conboard conboard-sessions">

	<h1>Conference Schedule</h1>

	<?php if ($query->have_posts()) : ?>

		<ul class="conboard-session-list">

			<?php while ($query->have_posts()) : $query->the_post(); ?>

				<?php
				$start = get_post_meta(get_the_ID(), 'start_time', true);
				$end   = get_post_meta(get_the_ID(), 'end_time', true);
				$room  = get_post_meta(get_the_ID(), 'location', true);

				$state = conboard_get_session_state(get_the_ID(), $now);
				?>

				<li class="session session-<?php echo esc_attr($state); ?>">

					<time>
						<?php echo esc_html(date('g:i A', strtotime($start))); ?>
						–
						<?php echo esc_html(date('g:i A', strtotime($end))); ?>
					</time>

					<h2>
						<a href="<?php the_permalink(); ?>">
							<?php the_title(); ?>
						</a>
					</h2>

					<?php if ($room) : ?>
						<div class="session-room">
							<?php echo esc_html(get_the_title($room)); ?>
						</div>
					<?php endif; ?>

				</li>

			<?php endwhile; ?>

		</ul>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>

		<p>No sessions found.</p>

	<?php endif; ?>

</main>

<?php get_footer(); ?>
