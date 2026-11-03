<?php
// includes/acf/acf_announcement.php

if ( ! defined('ABSPATH') ) { exit; }

add_action( 'acf/init', function () {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group([
		'key'		=> 'group_conboard_announcement',
		'title'		=> 'Announcement Options',
		'fields'	=> [

			[
				'key'			=> 'field_conboard_announcement_urgent',
				'label'			=> 'Urgent',
				'name'			=> 'urgent',
				'type'			=> 'true_false',
				'ui'			=> 1,
				'instructions'	=> 'Show first and mark with an Urgent label (and highlight it on screens).',
			],

			[
				'key'			=> 'field_conboard_announcement_screens',
				'label'			=> 'Show on Screens',
				'name'			=> 'show_on_screens',
				'type'			=> 'true_false',
				'ui'			=> 1,
				'default_value'	=> 1,
				'instructions'	=> 'Include this announcement on info screens and Display Boards.',
			],

			[
				'key'			=> 'field_conboard_announcement_expires',
				'label'			=> 'Expires',
				'name'			=> 'expires',
				'type'			=> 'date_time_picker',
				'display_format'	=> 'F j, Y g:i a',
				'return_format'	=> 'Y-m-d H:i:s',
				'instructions'	=> 'Optional. After this time the announcement is hidden from lists and screens. Its own page stays available.',
			],

		],
		'location'	=> [
			[
				[
					'param'		=> 'post_type',
					'operator'	=> '==',
					'value'		=> 'announcement',
				],
			],
		],
	]);
});
