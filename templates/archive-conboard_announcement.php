<?php
// templates/archive-conboard_announcement.php

/**
 * Archive: Announcements (newest first)
 */

get_header();

$announcements_html = conboard_render_announcements();

?>

<main class="conboard conboard-announcements-page">

	<h1>Announcements</h1>

	<?php if ($announcements_html) : ?>
		<?php echo $announcements_html; // Escaped in conboard_render_announcements() ?>
	<?php else : ?>
		<p>No announcements yet.</p>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
