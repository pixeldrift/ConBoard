<?php
// templates/single-conboard_staff.php

/**
 * Single Staff Member
 */

get_header();

the_post();

$staff_id = get_the_ID();

$pronouns = (string) get_post_meta($staff_id, 'pronouns', true);
$role     = (string) get_post_meta($staff_id, 'role', true);
$email    = (string) get_post_meta($staff_id, 'email', true);

// Socials and web links share the same shape: label + url
$links = [];

foreach (['socials', 'links'] as $field) {
	$rows = get_field($field, $staff_id);

	if (!is_array($rows)) {
		continue;
	}

	foreach ($rows as $row) {
		if (!empty($row['url'])) {
			$links[] = [
				'url'   => $row['url'],
				'label' => !empty($row['label']) ? $row['label'] : (parse_url($row['url'], PHP_URL_HOST) ?: $row['url']),
			];
		}
	}
}

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

			<?php if (!empty($links)) : ?>
				<ul class="staff-links">
					<?php foreach ($links as $link) : ?>
						<li>
							<a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener">
								<?php echo esc_html($link['label']); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</div>

	</header>

	<section class="staff-bio">
		<?php the_content(); ?>
	</section>

	<p class="staff-back">
		<a href="<?php echo esc_url(get_post_type_archive_link('staff')); ?>">&larr; All staff</a>
	</p>

</main>

<?php get_footer(); ?>
