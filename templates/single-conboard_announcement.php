<?php
// templates/single-conboard_announcement.php

/**
 * Single Announcement
 */

get_header();

the_post();

$announcement_id = get_the_ID();
$urgent          = conboard_announcement_is_urgent($announcement_id);
$expired         = conboard_announcement_is_expired($announcement_id);

?>

<main class="conboard conboard-announcement-page">

	<article class="conboard-announcement<?php echo $urgent ? ' conboard-announcement-urgent' : ''; ?>">

		<?php if ($urgent) : ?>
			<span class="conboard-card-badge conboard-announcement-badge">Urgent</span>
		<?php endif; ?>

		<h1 class="conboard-announcement-title"><?php the_title(); ?></h1>

		<time class="conboard-announcement-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
			<?php echo esc_html(get_the_date('F j, Y g:i A')); ?>
		</time>

		<?php if ($expired) : ?>
			<p class="conboard-announcement-expired"><em>This announcement has expired.</em></p>
		<?php endif; ?>

		<div class="conboard-announcement-text">
			<?php the_content(); ?>
		</div>

	</article>

	<p class="announcement-back">
		<a href="<?php echo esc_url(get_post_type_archive_link('announcement')); ?>">&larr; All announcements</a>
	</p>

</main>

<?php get_footer(); ?>
