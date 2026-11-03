<?php
// includes/admin/location_metaboxes_actions.php

if ( ! defined('ABSPATH') ) exit;

add_action('add_meta_boxes', function () {
	// Sessions in this Location (main content)
	add_meta_box(
		'location_sessions',
		'Sessions in this location',
		'conboard_render_location_sessions',
		'location',
		'normal',
		'default'
	);
});

function conboard_render_location_sessions( $post ) {
	$sessions = get_posts([
		'post_type'		=> 'session',
		'numberposts'	=> -1,
		'meta_query'	=> [
			[
				'key'		=> 'location',
				'value'		=> $post->ID,
				'compare'	=> '=',
			],
		],
		'orderby'		=> 'title',
		'order'			=> 'ASC',
	]);

	if ( empty( $sessions ) ) {
		echo '<p>No sessions assigned to this location.</p>';
		return;
	}

	echo '<ul>';
	foreach ( $sessions as $session ) {
		echo '<li><a href="' . esc_url( get_edit_post_link( $session->ID ) ) . '">';
		echo esc_html( $session->post_title );
		echo '</a></li>';
	}
	echo '</ul>';
}
