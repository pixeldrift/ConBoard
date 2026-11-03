<?php
// includes/post-types/cpt_session.php

add_action('init', function () {
	register_post_type('session', [
		'labels' => [
			'name'					=> 'Sessions',
			'singular_name'			=> 'Session',
			'menu_name'				=> 'Sessions',
			'add_new'				=> 'Session',
			'add_new_item'			=> 'Add New Session',
			'edit_item'				=> 'Edit Session',
			'new_item'				=> 'New Session',
			'view_item'				=> 'View Session',
			'search_items'			=> 'Search Sessions',
			'not_found'				=> 'No Sessions found',
			'not_found_in_trash'	=> 'No Sessions found in Trash',
			'all_items'				=> 'Sessions',
			'name_admin_bar'		=> 'Sessions',
		],
		'public'		=> true,
		'show_ui'		=> true,
		'show_in_menu'	=> 'conboard',
		'icon_code'		=> 'f163',  // dashicons-list-view
		'supports'		=> ['title', 'editor'],
		'has_archive'	=> true,
		'rewrite'		=> ['slug' => 'sessions'],
		'show_in_rest'	=> true,
		'description'	=> 'Talks, panels, workshops, and other scheduled activites',
	]);
});