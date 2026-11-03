<?php
// templates/single-conboard_announcement.php

/**
 * Single Announcement
 */

get_header();

the_post();

?>

<main class="conboard conboard-announcement-page">

	<article class="conboard-announcement">

		<h1 class="conboard-announcement-title"><?php the_title(); ?></h1>

		<time class="conboard-announcement-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
			<?php echo esc_html(get_the_date('F j, Y g:i A')); ?>
		</time>

		<div class="conboard-announcement-text">
			<?php the_content(); ?>
		</div>

	</article>

	<p class="announcement-back">
		<a href="<?php echo esc_url(get_post_type_archive_link('announcement')); ?>">&larr; All announcements</a>
	</p>

</main>

<?php get_footer(); ?>
