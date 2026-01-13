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
			'key'			=> 'field_conboard_session_room',
			'label'			=> 'Room',
			'name'			=> 'room',
			'type'			=> 'post_object',
			'post_type'		=> [ 'room' ],
			'return_format'	=> 'id',
			'ui'			=> 1,
			'required'		=> 1,
			'instructions'	=> 'Select the room where this session takes place.',
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


//
// Session presenters
//
acf_add_local_field_group([
	'key'		=> 'group_conboard_session_presenters',
	'title'		=> 'Session Presenters',
	'fields'	=> [

		[
			'key'			=> 'field_conboard_session_presenters',
			'label'			=> 'Presenters',
			'name'			=> 'presenters',
			'type'			=> 'repeater',
			'layout'		=> 'row',
			'button_label'	=> 'Add Presenter',
			'instructions'	=> 'Add presenters and define their role for this session.',
			'sub_fields'	=> [

				[
					'key'			=> 'field_conboard_presenter_person',
					'label'			=> 'Presenter',
					'name'			=> 'presenter',
					'type'			=> 'relationship',
					'post_type'		=> [ 'presenter' ],
					'filters'		=> [ 'search' ],
					'return_format'	=> 'id',
				],

				[
					'key'			=> 'field_conboard_presenter_role',
					'label'			=> 'Role',
					'name'			=> 'role',
					'type'			=> 'select',
					'choices'		=> conboard_get_presenter_role_choices(),
					'default_value'	=> conboard_get_default_presenter_role(),
					'ui'			=> 1,
					'return_format'	=> 'value',
				],

			],
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