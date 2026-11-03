<?php
// includes/sponsor_levels.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// Built-in sponsor level definitions (used until levels are saved on the Settings page)
//
function conboard_get_default_sponsor_levels() {
	return [
		'platinum'	=> [
			'singular'	=> 'Platinum Sponsor',
			'plural'	=> 'Platinum Sponsors',
			'label'		=> 'Platinum',
			'order'		=> 1,
		],
		'gold' => [
			'singular'	=> 'Gold Sponsor',
			'plural'	=> 'Gold Sponsors',
			'label'		=> 'Gold',
			'order'		=> 2,
		],
		'silver' => [
			'singular'	=> 'Silver Sponsor',
			'plural'	=> 'Silver Sponsors',
			'label'		=> 'Silver',
			'order'		=> 3,
		],
		'bronze' => [
			'singular'	=> 'Bronze Sponsor',
			'plural'	=> 'Bronze Sponsors',
			'label'		=> 'Bronze',
			'order'		=> 4,
		],
	];
}


//
// Active sponsor levels: the ones saved in Settings, otherwise the defaults. Sorted by order.
//
function conboard_get_sponsor_levels() {
	$stored = get_option( 'conboard_sponsor_levels' );
	$levels = ( is_array( $stored ) && ! empty( $stored ) ) ? $stored : conboard_get_default_sponsor_levels();

	uasort( $levels, function ( $a, $b ) {
		return ( $a['order'] ?? 999 ) <=> ( $b['order'] ?? 999 );
	});

	return $levels;
}


// Persist sponsor level order as numeric meta for sorting
add_action('acf/save_post', function ( $post_id ) {

	// Only sponsors
	if ( get_post_type($post_id) !== 'sponsor' ) {
		return;
	}

	// Get selected sponsor level (ACF field)
	$level = get_field('sponsor_level', $post_id);
	if ( ! $level ) {
		return;
	}

	// Get level definitions
	$levels = conboard_get_sponsor_levels();

	if ( empty( $levels[ $level ]['order'] ) ) {
		return;
	}

	// Store numeric order for sorting
	update_post_meta(
		$post_id,
		'sponsor_level_order',
		(int) $levels[ $level ]['order']
	);

}, 20); // after ACF saves fields

// Clears the level from every sponsor that uses it. Not called yet: meant for when a level is deleted
// in Settings. (This was found pasted into assets/js/sponsor_levels.js, where it broke the script.)
function conboard_unlink_sponsor_level( string $slug ): void {

	$query = new WP_Query([
		'post_type'			=> 'sponsor',
		'posts_per_page' => -1,
		'meta_query'		 => [
			[
				'key'	 => 'sponsor_level',
				'value' => $slug,
			],
		],
		'fields' => 'ids',
	]);

	foreach ( $query->posts as $post_id ) {
		delete_post_meta( $post_id, 'sponsor_level' );
		delete_post_meta( $post_id, 'sponsor_level_order' );
	}
}
