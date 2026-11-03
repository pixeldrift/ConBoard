<?php
// templates/single-conboard_session.php

conboard_template_header();

the_post();

$session_id  = get_the_ID();
$start       = get_post_meta($session_id, 'start_time', true);
$end         = get_post_meta($session_id, 'end_time', true);
$location_id = (int) get_post_meta($session_id, 'location', true);
$presenters  = conboard_get_session_presenters($session_id);

?>

<main class="conboard conboard-session">

	<h1><?php the_title(); ?></h1>

	<?php if ($start) : ?>
		<p class="session-time">
			<?php echo esc_html(date_i18n('l, F j, g:i A', strtotime($start))); ?>
			<?php if ($end) : ?>
				&ndash; <?php echo esc_html(date_i18n('g:i A', strtotime($end))); ?>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ($location_id) : ?>
		<p class="session-location">
			<a href="<?php echo esc_url(get_permalink($location_id)); ?>">
				<?php echo esc_html(get_the_title($location_id)); ?>
			</a>
		</p>
	<?php endif; ?>

	<?php
	$setup_slug = conboard_get_session_room_setup($session_id);
	$setup_all  = conboard_room_setup_choices();
	if ($setup_slug && isset($setup_all[$setup_slug])) : ?>
		<p class="session-room-setup">Room setup: <?php echo esc_html($setup_all[$setup_slug]); ?></p>
	<?php endif; ?>

	<?php if (!empty($presenters)) : ?>
		<ul class="session-presenters">
			<?php foreach ($presenters as $entry) : ?>
				<li class="session-presenter">
					<span class="presenter-role">
						<?php echo esc_html(conboard_get_presenter_role_label($entry['role'])); ?>:
					</span>
					<a href="<?php echo esc_url(get_permalink($entry['presenter_id'])); ?>">
						<?php echo esc_html(get_the_title($entry['presenter_id'])); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="session-content">
		<?php the_content(); ?>
	</div>

	<?php $related = conboard_get_related_sessions($session_id, 3); ?>
	<?php if (!empty($related)) : ?>
		<section class="conboard-related">
			<h2>You might also like</h2>
			<div class="conboard-related-cards">
				<?php foreach ($related as $row) : ?>
					<a class="conboard-related-card" href="<?php echo esc_url($row['url']); ?>">
						<span class="conboard-related-reason"><?php echo esc_html($row['reason']); ?></span>
						<strong><?php echo esc_html($row['title']); ?></strong>
						<?php if ($row['start']) : ?>
							<span class="conboard-related-when"><?php echo esc_html(conboard_schedule_time_label($row['start'], $row['end'])); ?></span>
						<?php endif; ?>
						<?php if ($row['location']) : ?>
							<span class="conboard-related-where"><?php echo esc_html($row['location']); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

</main>

<?php conboard_template_footer(); ?>
