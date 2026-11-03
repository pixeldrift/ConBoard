<?php
// templates/single-conboard_staff.php

/**
 * Single Staff Member
 */

conboard_template_header();

the_post();

$staff_id = get_the_ID();

$pronouns = (string) get_post_meta($staff_id, 'pronouns', true);
$role     = (string) get_post_meta($staff_id, 'role', true);
$email    = (string) get_post_meta($staff_id, 'email', true);

// Links
$links_html = conboard_render_post_links($staff_id, 'staff-links');

?>

<main class="conboard conboard-staff-member">

	<header class="staff-header">

		<?php if (has_post_thumbnail()) : ?>
			<div class="staff-photo">
				<?php the_post_thumbnail('large'); ?>
			</div>
		<?php endif; ?>

		<div class="staff-meta">

			<h1 class="staff-name"><?php the_title(); ?></h1>

			<?php if ($pronouns) : ?>
				<div class="staff-pronouns"><?php echo esc_html($pronouns); ?></div>
			<?php endif; ?>

			<?php if ($role) : ?>
				<div class="staff-role"><?php echo esc_html($role); ?></div>
			<?php endif; ?>

			<?php if ($email) : ?>
				<div class="staff-email">
					<a href="mailto:<?php echo esc_attr($email); ?>">
						<?php echo esc_html($email); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php echo $links_html; // Escaped in conboard_render_post_links() ?>

		</div>

	</header>

	<section class="staff-bio">
		<?php the_content(); ?>
	</section>

	<p class="staff-back">
		<a href="<?php echo esc_url(get_post_type_archive_link('staff')); ?>">&larr; All staff</a>
	</p>

</main>

<?php conboard_template_footer(); ?>
