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

		[
			'key'			=> 'field_conboard_session_cancelled',
			'label'			=> 'Cancelled',
			'name'			=> 'cancelled',
			'type'			=> 'true_false',
			'ui'			=> 1,
			'instructions'	=> 'Keep the session listed on schedules and screens, marked as cancelled.',
		],

		[
			'key'			=> 'field_conboard_session_room_setup',
			'label'			=> 'Room Setup',
			'name'			=> 'room_setup',
			'type'			=> 'select',
			'choices'		=> [ '' => '-Use location default-' ] + conboard_room_setup_choices(),
			'allow_null'	=> 1,
			'ui'			=> 1,
			'return_format'	=> 'value',
			'instructions'	=> 'Overrides the location\'s Default Room Setup for this session. Leave on the default to use the location\'s.',
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
