<?php
// templates/archive-conboard_staff.php

/**
 * Archive: Staff (by Display Order)
 */

conboard_template_header();

$staff_html = conboard_render_staff([
	'layout'	=> 'grid',
	'columns'	=> 4,
]);

?>

<main class="conboard conboard-staff-page">

	<h1>Staff</h1>

	<?php if ($staff_html) : ?>
		<?php echo $staff_html; // Escaped in conboard_render_staff() ?>
	<?php else : ?>
		<p>No staff listed yet.</p>
	<?php endif; ?>

</main>

<?php conboard_template_footer(); ?>
