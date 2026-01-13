<?php
// includes/taxonomies.php

add_action('init', function () {

	register_taxonomy('session_type', 'session', [
		'label'			=> 'Session Type',
		'public'		=> true,
		'hierarchical'	=> false,
		'show_in_rest'	=> true,
	]);

	register_taxonomy('sponsor_level', 'sponsor', [
		'label'			=> 'Sponsor Level',
		'public'		=> true,
		'hierarchical'	=> true,
		'show_in_rest'	=> true,
	]);

	register_taxonomy('conference_day', 'session', [
		'label'			=> 'Conference Day',
		'public'		=> true,
		'hierarchical'	=> false,
		'show_in_rest'	=> true,
	]);
});
