<?php
// includes/acf/acf_shared.php

if ( ! defined('ABSPATH') ) exit;

/**
 * Shared Social sub-fields for repeaters
 */
function conboard_get_shared_social_fields(): array {
	return [
		[
			'key'		=> 'field_social_platform',
			'label'		=> 'Platform',
			'name'		=> 'platform',
			'type'		=> 'select',
			'choices'	=> [
				'instagram'	=> 'Instagram',
				'youtube'	=> 'YouTube',
				'tiktok'	=> 'TikTok',
				'facebook'	=> 'Facebook',
				'twitter'	=> 'Twitter / X',
				'linkedin'	=> 'LinkedIn',
				'threads'	=> 'Threads',
				'bluesky'	=> 'Bluesky',
			],
			'ui'			=> 1,
			'return_format'	=> 'value',
		],
		[
			'key'			=> 'field_social_url',
			'label'			=> 'URL',
			'name'			=> 'url',
			'type'			=> 'url',
			'placeholder'	=> 'https://',
		],
		[
			'key'			=> 'field_social_label',
			'label'			=> 'Label (optional)',
			'name'			=> 'label',
			'type'			=> 'text',
			'instructions'	=> 'Overrides the default platform name.',
		],
	];
}
