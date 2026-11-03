<?php
// templates/archive-conboard_presenter.php

/**
 * Archive: Presenters (keynote speakers first, then featured, then everyone else)
 */

get_header();

$presenters_html = conboard_render_presenters([
	'layout'	=> 'grid',
	'columns'	=> 3,
]);

?>

<main class="conboard conboard-presenters-page">

	<h1>Presenters</h1>

	<?php if ($presenters_html) : ?>
		<?php echo $presenters_html; // Escaped in conboard_render_presenters() ?>
	<?php else : ?>
		<p>No presenters found.</p>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
