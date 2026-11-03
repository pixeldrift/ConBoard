<?php
// includes/acf/acf_sponsor.php

if ( ! defined('ABSPATH') ) exit;

acf_add_local_field_group([
	'key' => 'group_conboard_sponsor',
	'title' => 'Sponsor Details',
	'fields' => [

		// Sponsor Level
		[
			'key'			=> 'field_sponsor_level',
			'label'			=> 'Sponsor Level',
			'name'			=> 'sponsor_level',
			'type'			=> 'select',
			'choices'		=> conboard_get_sponsor_level_choices(),
			'default_value'	=> conboard_get_default_sponsor_level(),
			'ui'			=> 1,
			'return_format'	=> 'value',
		],

		// Website
		[
			'key'			=> 'field_sponsor_website',
			'label'			=> 'Website',
			'name'			=> 'website',
			'type'			=> 'url',
		],

		// Display Order
		[
			'key'			=> 'field_sponsor_display_order',
			'label'			=> 'Display Order',
			'name'			=> 'display_order',
			'type'			=> 'number',
			'default_value'	=> 0,
			'instructions'	=> 'Lower numbers appear first',
		],

	],
	'location' => [
		[
			[
				'param'		=> 'post_type',
				'operator'	=> '==',
				'value'		=> 'sponsor',
			],
		],
	],
]);
