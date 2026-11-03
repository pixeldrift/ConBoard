<?php
// includes/defaults.php

if ( ! defined( 'ABSPATH' ) ) exit;

// On activation, give a new site one starter Display Board to edit (only if there are none).
// The activation hook itself is registered in includes/activation.php.
function conboard_create_default_display_boards(): void {

	$existing = get_posts([
		'post_type'			=> 'display_board',
		'post_status'		=> 'any',
		'posts_per_page'	=> 1,
		'fields'			=> 'ids',
	]);

	if ( ! empty( $existing ) ) {
		return;
	}

	$board_id = wp_insert_post([
		'post_type'		=> 'display_board',
		'post_title'	=> 'Main Display',
		'post_status'	=> 'publish',
	]);

	if ( ! $board_id || is_wp_error( $board_id ) ) {
		return;
	}

	update_post_meta( $board_id, '_conboard_board', conboard_get_board_setting_defaults() );

	conboard_save_board_slides( (int) $board_id, [
		[ 'type' => 'welcome', 'enabled' => true, 'duration' => 8, 'settings' => [ 'heading' => '', 'message' => 'Welcome! Ask any staff member if you need help.', 'logo' => '' ] ],
		[ 'type' => 'announcements', 'enabled' => true, 'duration' => 12, 'settings' => [ 'count' => '3' ] ],
	] );
}
