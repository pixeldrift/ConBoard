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
			'key'			=> 'field_staff_socials',
			'label'			=> 'Socials',
			'name'			=> 'socials',
			'type'			=> 'repeater',
			'layout'		=> 'row',
			'button_label'	=> 'Add Social',
			'sub_fields'	=> [
				[
					'key'	=> 'field_staff_social_label',
					'label'	=> 'Label',
					'name'	=> 'label',
					'type'	=> 'text',
				],
				[
					'key'	=> 'field_staff_social_url',
					'label'	=> 'URL',
					'name'	=> 'url',
					'type'	=> 'url',
				],
			],
		],

		[
			'key' => 'field_staff_links',
			'label' => 'Web Links',
			'name' => 'links',
			'type'			=> 'repeater',
			'layout'		=> 'row',
			'button_label'	=> 'Add Link',
			'sub_fields'	=> [
				[
					'key' => 'field_staff_link_label',
					'label' => 'Label',
					'name' => 'label',
					'type' => 'text',
				],
				[
					'key' => 'field_staff_link_url',
					'label' => 'URL',
					'name' => 'url',
					'type' => 'url',
				],
			],
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
