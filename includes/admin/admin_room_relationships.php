<?php
// includes/admin/admin_location_relationships.php

if ( ! defined('ABSPATH') ) exit;

//
// Exclude the current location from its own "contained_locations" relationship field
//
add_filter(
	'acf/fields/relationship/query/name=contained_locations',
	function ($args, $field, $post_id) {

		// $post_id can be "new_post" when creating
		if (!is_numeric($post_id)) {
			return $args;
		}

		$args['post__not_in'] = [$post_id];

		return $args;
	},
	10,
	3
);
