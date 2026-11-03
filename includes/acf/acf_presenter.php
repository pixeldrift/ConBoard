<?php
// includes/acf/acf_presenter.php

if ( ! defined('ABSPATH') ) { exit; }

acf_add_local_field_group([
	'key'		=> 'group_conboard_presenter',
	'title'		=> 'Presenter Details',
	'location'	=> [
		[
			[
				'param'		=> 'post_type',
				'operator'	=> '==',
				'value'		=> 'presenter',
			],
		],
	],
	'fields' => [
		[
			'key'			=> 'field_staff_pronouns',
			'label'			=> 'Pronouns',
			'name'			=> 'pronouns',
			'type'			=> 'text',
			'instructions'	=> 'e.g. she/her, they/them',
		],
		
		[
			'key'			=> 'field_conboard_presenter_title',
			'label'			=> 'Presenter',
			'name'			=> 'presenter_title',
			'type'			=> 'text',
		],

		[
			'key'			=> 'field_conboard_presenter_org',
			'label'			=> 'Organization',
			'name'			=> 'presenter_organization',
			'type'			=> 'text',
		],

		[
			'key'			=> 'field_conboard_presenter_email',
			'label'			=> 'Email',
			'name'			=> 'presenter_email',
			'type'			=> 'email',
		],
		
		[
			'key'			=> 'field_conboard_presenter_websites',
			'label'			=> 'Websites',
			'name'			=> 'presenter_websites',
			'type'			=> 'repeater',
			'button_label'	=> 'Add Website',
			'sub_fields'	=> [
				[
					'key'		=> 'field_conboard_presenter_website_url',
					'label'		=> 'URL',
					'name'		=> 'url',
					'type'		=> 'url',
					'required'	=> 1,
				],
				[
					'key'		=> 'field_conboard_presenter_website_label',
					'label'		=> 'Label',
					'name'		=> 'label',
					'type'		=> 'text',
				],
			],
		],
	],
]);
