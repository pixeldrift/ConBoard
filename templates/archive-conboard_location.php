<?php
// templates/archive-conboard_location.php

/**
 * Archive: Locations, nested (campus > building > floor > room)
 */

conboard_template_header();

$tree_html = conboard_render_location_tree();

?>

<main class="conboard conboard-locations-page">

	<h1>Locations</h1>

	<?php if ($tree_html) : ?>
		<?php echo $tree_html; // Escaped in conboard_render_location_tree() ?>
	<?php else : ?>
		<p>No locations yet.</p>
	<?php endif; ?>

</main>

<?php conboard_template_footer(); ?>
