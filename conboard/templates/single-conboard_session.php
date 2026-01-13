<?php
// single-conboard_session.php

get_header();

the_post();
?>

<main class="conboard-session">

	<h1><?php the_title(); ?></h1>

	<?php if ($time = get_post_meta(get_the_ID(), 'start_time', true)) : ?>
		<p class="session-time">
			<?php echo esc_html($time); ?>
		</p>
	<?php endif; ?>

	<div class="session-content">
		<?php the_content(); ?>
	</div>

</main>

<?php get_footer(); ?>

