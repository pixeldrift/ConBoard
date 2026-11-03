<?php
// templates/archive-conboard_sponsor.php

/**
 * Archive: Sponsors, grouped by level
 */

conboard_template_header();

$sponsors_html = conboard_render_sponsors([
	'layout'	=> 'levels',
	'columns'	=> 4,
	'link'		=> 'page',
]);

?>

<main class="conboard conboard-sponsors-page">

	<h1>Sponsors</h1>

	<?php if ($sponsors_html) : ?>
		<?php echo $sponsors_html; // Escaped in conboard_render_sponsors() ?>
	<?php else : ?>
		<p>No sponsors yet.</p>
	<?php endif; ?>

</main>

<?php conboard_template_footer(); ?>
