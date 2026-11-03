<?php
// templates/single-conboard_location.php

/**
 * Single Location (campus, building, floor or room)
 */

conboard_template_header();

the_post();

$location_id = get_the_ID();

// Details
$type       = get_post_meta($location_id, 'location_type', true);
$room_type  = get_post_meta($location_id, 'room_type', true);
$room_setup = get_post_meta($location_id, 'default_room_setup', true);
$capacity   = get_post_meta($location_id, 'capacity', true);

$details = [];

if ($type) {
	$details['Type'] = conboard_get_field_choice_label('location_type', $type, $location_id);
}
if ($room_type) {
	$details['Room type'] = conboard_get_field_choice_label('room_type', $room_type, $location_id);
}
if ($room_setup) {
	$details['Setup'] = conboard_get_field_choice_label('default_room_setup', $room_setup, $location_id);
}
if ($capacity !== '' && $capacity !== false) {
	$details['Capacity'] = number_format_i18n((int) $capacity);
}

// Where this location sits (campus > building > floor)
$ancestors = array_reverse(get_post_ancestors($location_id));

// Locations inside this one
$children = get_posts([
	'post_type'			=> 'location',
	'post_parent'		=> $location_id,
	'post_status'		=> 'publish',
	'posts_per_page'	=> -1,
	'orderby'			=> 'menu_order title',
	'order'				=> 'ASC',
]);

// Map (own or inherited)
$map_html = conboard_get_location_map_html($location_id);

// Sessions here, including rooms inside this location
$now      = current_time('timestamp');
$sessions = conboard_get_sessions_for_location($location_id);
$groups   = [
	'current'     => [],
	'upcoming'    => [],
	'past'        => [],
	'unscheduled' => [],
];

foreach ($sessions as $session) {
	$groups[conboard_get_session_state($session->ID, $now)][] = $session;
}

$headings = [
	'current'     => 'Happening now',
	'upcoming'    => 'Coming up',
	'past'        => 'Earlier',
	'unscheduled' => 'Time to be announced',
];

?>

<main class="conboard conboard-location">

	<?php if (!empty($ancestors)) : ?>
		<nav class="location-breadcrumb" aria-label="Location">
			<?php foreach ($ancestors as $ancestor_id) : ?>
				<a href="<?php echo esc_url(get_permalink($ancestor_id)); ?>">
					<?php echo esc_html(get_the_title($ancestor_id)); ?>
				</a>
				<span class="separator">&rsaquo;</span>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<header class="location-header">

		<?php if (has_post_thumbnail()) : ?>
			<div class="location-photo">
				<?php the_post_thumbnail('large'); ?>
			</div>
		<?php endif; ?>

		<h1 class="location-name"><?php the_title(); ?></h1>

		<?php if (!empty($details)) : ?>
			<dl class="location-details">
				<?php foreach ($details as $label => $value) : ?>
					<dt><?php echo esc_html($label); ?></dt>
					<dd><?php echo esc_html($value); ?></dd>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>

	</header>

	<?php if ($map_html) : ?>
		<section class="location-map-section">
			<?php echo $map_html; // Sanitized in conboard_get_location_map_html() ?>
		</section>
	<?php endif; ?>

	<?php if (get_the_content()) : ?>
		<section class="location-description">
			<?php the_content(); ?>
		</section>
	<?php endif; ?>

	<?php if (!empty($children)) : ?>
		<section class="location-children">
			<h2>Inside <?php the_title(); ?></h2>
			<ul>
				<?php foreach ($children as $child) : ?>
					<li>
						<a href="<?php echo esc_url(get_permalink($child->ID)); ?>">
							<?php echo esc_html(get_the_title($child->ID)); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php foreach ($groups as $state => $items) : ?>

		<?php if (empty($items)) continue; ?>

		<section class="location-sessions location-sessions-<?php echo esc_attr($state); ?>">

			<h2><?php echo esc_html($headings[$state]); ?></h2>

			<ul class="location-session-list">

				<?php foreach ($items as $session) : ?>

					<?php
					$start     = get_post_meta($session->ID, 'start_time', true);
					$end       = get_post_meta($session->ID, 'end_time', true);
					$room_id   = (int) get_post_meta($session->ID, 'location', true);
					$presenters = conboard_get_session_presenters($session->ID);
					?>

					<li class="location-session session-<?php echo esc_attr($state); ?>">

						<?php if ($start) : ?>
							<time class="session-time">
								<?php echo esc_html(date_i18n('D g:i A', strtotime($start))); ?>
								<?php if ($end) : ?>
									&ndash; <?php echo esc_html(date_i18n('g:i A', strtotime($end))); ?>
								<?php endif; ?>
							</time>
						<?php endif; ?>

						<a class="session-title" href="<?php echo esc_url(get_permalink($session->ID)); ?>">
							<?php echo esc_html(get_the_title($session->ID)); ?>
						</a>

						<?php if ($room_id && $room_id !== $location_id) : ?>
							<span class="session-room">
								<?php echo esc_html(get_the_title($room_id)); ?>
							</span>
						<?php endif; ?>

						<?php if (!empty($presenters)) : ?>
							<span class="session-presenter-names">
								<?php
								$names = array_map(function ($entry) {
									return get_the_title($entry['presenter_id']);
								}, $presenters);
								echo esc_html(implode(', ', $names));
								?>
							</span>
						<?php endif; ?>

					</li>

				<?php endforeach; ?>

			</ul>

		</section>

	<?php endforeach; ?>

	<?php if (empty($sessions)) : ?>
		<p class="location-no-sessions">No sessions are scheduled here yet.</p>
	<?php endif; ?>

</main>

<?php conboard_template_footer(); ?>
