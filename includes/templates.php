<?php
// includes/templates.php

add_filter('template_include', function ($template) {

	if (!is_singular() && !is_post_type_archive()) {
		return $template;
	}

	$post_type = get_post_type();

	// Keys are the registered post type slugs (see includes/post-types/).
	// Only templates that are ready for use are mapped here. Display Boards are not
	// mapped yet (they belong to Screen Mode), so the theme's own templates handle them.
	$templates = [
		'presenter' => [
			'single'	=> 'single-conboard_presenter.php',
			'archive'	=> 'archive-conboard_presenter.php',
		],
		'announcement' => [
			'single'	=> 'single-conboard_announcement.php',
			'archive'	=> 'archive-conboard_announcement.php',
		],
		'location' => [
			'single'	=> 'single-conboard_location.php',
			'archive'	=> 'archive-conboard_location.php',
		],
		'staff' => [
			'single'	=> 'single-conboard_staff.php',
			'archive'	=> 'archive-conboard_staff.php',
		],
		'sponsor' => [
			'single'	=> 'single-conboard_sponsor.php',
			'archive'	=> 'archive-conboard_sponsor.php',
		],
		'session' => [
			'single'	=> 'single-conboard_session.php',
			'archive'	=> 'archive-conboard_session.php',
		],
	];

	if (!isset($templates[$post_type])) {
		return $template;
	}

	$file = '';

	if (is_singular() && !empty($templates[$post_type]['single'])) {
		$file = $templates[$post_type]['single'];
	} elseif (is_post_type_archive() && !empty($templates[$post_type]['archive'])) {
		$file = $templates[$post_type]['archive'];
	}

	if ($file) {
		$located = conboard_locate_template($file);
		if ($located) {
			return $located;
		}
	}

	return $template;
});
