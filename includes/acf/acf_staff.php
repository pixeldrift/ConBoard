<?php
// includes/acf/acf_staff.php

if ( ! defined('ABSPATH') ) exit;

acf_add_local_field_group([
	'key'		=> 'group_conboard_staff',
	'title'		=> 'Staff Details',
	'fields'	=> [

		[
			'key'			=> 'field_staff_pronouns',
			'label'			=> 'Pronouns',
			'name'			=> 'pronouns',
			'type'			=> 'text',
			'instructions'	=> 'e.g. she/her, they/them',
		],
		
		[
			'key'			=> 'field_staff_role',
			'label'			=> 'Role',
			'name'			=> 'role',
			'type'			=> 'text',
			'required'		=> 0,
		],

		[
			'key'			=> 'field_staff_email',
			'label'			=> 'Email',
			'name'			=> 'email',
			'type'			=> 'email',
		],

		[
			'key' => 'field_staff_display_order',
			'label' => 'Display Order',
			'name' => 'display_order',
			'type' => 'number',
			'default_value' => 0,
			'instructions' => 'Lower numbers appear first',
		],

	],
	'location' => [
		[
			[
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'staff',
			],
		],
	],
]);
