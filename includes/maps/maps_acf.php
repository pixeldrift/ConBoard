<?php
// includes/maps/maps_acf.php

if ( ! defined('ABSPATH') ) exit;

add_action( 'acf/init', 'conboard_register_location_map_fields' );

/**
 * Map settings for locations. The layer checkboxes are our own metabox
 * (see maps_admin.php) so they can show a live preview.
 */
function conboard_register_location_map_fields() {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

	acf_add_local_field_group([
		'key'		=> 'group_location_maps',
		'title'		=> 'Location Map',
		'fields'	=> [

			[
				'key'			=> 'field_location_show_map',
				'label'			=> 'Show Map',
				'name'			=> 'show_map',
				'type'			=> 'true_false',
				'ui'			=> 1,
				'default_value'	=> 1,
				'message'		=> '',
				'instructions'	=> 'Turn off to hide the map for this location (it will not be inherited-and-shown on its page or screens).',
			],

			[
				'key'			=> 'field_location_map',
				'label'			=> 'Map File',
				'name'			=> 'location_map',
				'type'			=> 'file',
				'return_format'	=> 'id',
				'mime_types'	=> 'svg,png,jpg,jpeg',
				'instructions'	=> 'SVG, PNG or JPG. If empty, the nearest parent location\'s map is used. If the file is deleted from the Media Library the parent\'s map is used instead.',
			],

			[
				'key'			=> 'field_location_map_overlay',
				'label'			=> 'Overlay on Parent Map',
				'name'			=> 'map_overlay',
				'type'			=> 'true_false',
				'ui'			=> 1,
				'default_value'	=> 0,
				'instructions'	=> 'For a transparent PNG (for example one highlighted room) drawn on top of the parent location\'s map. Needs a parent with a map.',
			],

			[
				'key'			=> 'field_location_map_labels',
				'label'			=> 'Labels Overlay',
				'name'			=> 'map_labels',
				'type'			=> 'file',
				'return_format'	=> 'id',
				'mime_types'	=> 'png,svg',
				'instructions'	=> 'Optional transparent image (room names etc.) drawn above the map and above highlights. Child locations use the one from their base map.',
			],
		],

		'location'		=> [[[
			'param'		=> 'post_type',
			'operator'	=> '==',
			'value'		=> 'location',
		]]],
	]);
}
