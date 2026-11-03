<?php
// templates/archive-conboard_announcement.php

/**
 * Archive: Announcements. Current ones first (urgent on top), then expired ones.
 */

get_header();

$current_html = conboard_render_announcements([ 'status' => 'active' ]);
$expired_html = conboard_render_announcements([ 'status' => 'expired' ]);

?>

<main class="conboard conboard-announcements-page">

	<h1>Announcements</h1>

	<?php if ($current_html) : ?>
		<?php echo $current_html; // Escaped in conboard_render_announcements() ?>
	<?php elseif (!$expired_html) : ?>
		<p>No announcements yet.</p>
	<?php else : ?>
		<p>No current announcements.</p>
	<?php endif; ?>

	<?php if ($expired_html) : ?>
		<section class="conboard-announcements-earlier">
			<h2>Earlier announcements</h2>
			<?php echo $expired_html; // Escaped in conboard_render_announcements() ?>
		</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
