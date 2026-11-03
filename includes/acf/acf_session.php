ƒ<?php
// includes/acf/acf_session.php

if ( ! defined( 'ABSPATH' ) ) { exit; }

//
// Session core details
//
acf_add_local_field_group([
	'key'	=> 'group_conboard_session_fields',
	'title'  => 'Session Details',
	'fields' => [

		[
			'key'			=> 'field_conboard_session_location',
			'label'			=> 'Location',
			'name'			=> 'location',
			'type'			=> 'post_object',
			'post_type'		=> [ 'location' ],
			'return_format'	=> 'id',
			'ui'			=> 1,
			'required'		=> 1,
			'instructions'	=> 'Select the location where this session takes place.',
		],

		[
			'key'			=> 'field_conboard_session_start_time',
			'label'			=> 'Start Time',
			'name'			=> 'start_time',
			'type'			=> 'date_time_picker',
			'return_format'	=> 'Y-m-d H:i:s',
		],

		[
			'key'			=> 'field_conboard_session_end_time',
			'label'			=> 'End Time',
			'name'			=> 'end_time',
			'type'			=> 'date_time_picker',
			'return_format'	=> 'Y-m-d H:i:s',
		],

	],
	'location' => [
		[
			[
				'param'		=> 'post_type',
				'operator'	=> '==',
				'value'		=> 'session',
			],
		],
	],
]);

// Session presenters are edited in the Presenters box (includes/admin/admin_session_presenters.php),
// which does not need the paid ACF repeater field.
