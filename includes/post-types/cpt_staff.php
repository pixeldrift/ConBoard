<?php
// includes/post-types/cpt_staff.php

add_action('init', function () {
	register_post_type('staff', [
		'labels' => [
			'name'					=> 'Staff',
			'singular_name'			=> 'Staff Member',
			'menu_name'				=> 'Staff',
			'add_new'				=> 'Staff',
			'add_new_item'			=> 'Add New Staff Member',
			'edit_item'				=> 'Edit Staff Member',
			'new_item'				=> 'New Staff Member',
			'view_item'				=> 'View Staff',
			'search_items'			=> 'Search Staff',
			'not_found'				=> 'No Staff member found',
			'not_found_in_trash'	=> 'No Staff member found in Trash',
			'all_items'				=> 'Staff',
			'name_admin_bar'		=> 'Staff',
		],
		'public'		=> true,
		'show_ui'		=> true,
		'show_in_menu'	=> 'conboard',
		'icon_code'		=> 'f307',  // dashicons-groups
		'supports'		=> ['title', 'editor', 'thumbnail'],
		'has_archive'	=> true,
		'rewrite'		=> ['slug' => 'staff'],
		'show_in_rest'	=> true,
		'description'	=> 'The event officers and other team members',
	]);
});
