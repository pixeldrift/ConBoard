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
			'key'			=> 'field_conboard_presenter_pronouns',
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
			'key'			=> 'field_conboard_presenter_keynote',
			'label'			=> 'Keynote Speaker',
			'name'			=> 'keynote_speaker',
			'type'			=> 'true_false',
			'ui'			=> 1,
			'instructions'	=> 'Highlight as a keynote speaker.',
		],

		[
			'key'			=> 'field_conboard_presenter_featured',
			'label'			=> 'Featured Presenter',
			'name'			=> 'featured_presenter',
			'type'			=> 'true_false',
			'ui'			=> 1,
			'instructions'	=> 'Feature on the home page and at the top of the Presenters page.',
		],
	],
]);
