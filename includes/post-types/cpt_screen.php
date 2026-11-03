<?php
// includes/post-types/cpt_screen.php
//
// Screens: the physical displays at the venue (name, place, resolution, rotation, kind of player).
// Each one has its own permanent address that shows whatever Display Board is assigned to it.

add_action('init', function () {
	register_post_type('venue_screen', [
		'labels' => [
			'name'					=> 'Screens',
			'singular_name'			=> 'Screen',
			'menu_name'				=> 'Screens',
			'add_new'				=> 'Screen',
			'add_new_item'			=> 'Add New Screen',
			'edit_item'				=> 'Edit Screen',
			'new_item'				=> 'New Screen',
			'view_item'				=> 'Open Screen',
			'search_items'			=> 'Search Screens',
			'not_found'				=> 'No Screens found',
			'not_found_in_trash'	=> 'No Screens found in Trash',
			'all_items'				=> 'Screens',
			'name_admin_bar'		=> 'Screen',
			'featured_image'		=> 'Reference photo',
			'set_featured_image'	=> 'Add a reference photo',
			'remove_featured_image'	=> 'Remove reference photo',
			'use_featured_image'	=> 'Use as reference photo',
		],
		'public'				=> true,
		'exclude_from_search'	=> true,
		'show_ui'				=> true,
		'show_in_menu'			=> 'conboard',
		'icon_code'				=> 'f472',  // dashicons-desktop
		'supports'				=> ['title', 'thumbnail'],
		'has_archive'			=> false,
		'rewrite'				=> ['slug' => 'screen'],
		'show_in_rest'			=> false,
		'description'			=> 'The physical displays at your venue and which Display Board each one shows',
	]);
});
