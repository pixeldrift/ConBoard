<?php
// includes/post-types/cpt_location.php

add_action('init', function () {
	register_post_type('location', [
		'labels' => [
			'name'					=> 'Locations',
			'singular_name'			=> 'Location',
			'menu_name'				=> 'Locations',
			'add_new'				=> 'Location',
			'add_new_item'			=> 'Add New Location',
			'edit_item'				=> 'Edit Location',
			'new_item'				=> 'New Location',
			'view_item'				=> 'View Location',
			'search_items'			=> 'Search Locations',
			'not_found'				=> 'No Location found',
			'not_found_in_trash'	=> 'No Locations found in Trash',
			'all_items'				=> 'Locations',
			'name_admin_bar'		=> 'Locations',
		],
		'public'		=> true,
		'show_ui'		=> true,
		'show_in_menu'	=> 'conboard',
		'icon_code'		=> 'f231',  // dashicons-location-alt
		'supports'		=> ['title', 'editor', 'thumbnail', 'page-attributes'],
		'has_archive'	=> false,
		'rewrite'		=> ['slug' => 'locations'],
		'hierarchical'	=> true, //
		'show_in_rest'	=> true,
		'description'	=> 'Physical spaces where conference sessions and events take place (buildings, rooms, etc)',
	]);
});
