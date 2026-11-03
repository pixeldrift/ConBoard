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

// Clears the level from every sponsor that uses it (used when a level is deleted in Settings)
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


//
// When the levels are saved in Settings: sponsors on a level that was deleted lose it (and so fall
// back to the lowest remaining level), and the stored sort order of the rest is brought up to date.
//
function conboard_sync_sponsor_levels( $old, $new ): void {

	$old = ( is_array( $old ) && ! empty( $old ) ) ? $old : conboard_get_default_sponsor_levels();
	$new = is_array( $new ) ? $new : [];

	foreach ( array_diff( array_keys( $old ), array_keys( $new ) ) as $removed ) {
		conboard_unlink_sponsor_level( (string) $removed );
	}

	foreach ( $new as $slug => $level ) {

		$sponsors = get_posts([
			'post_type'			=> 'sponsor',
			'post_status'		=> 'any',
			'posts_per_page'	=> -1,
			'fields'			=> 'ids',
			'meta_key'			=> 'sponsor_level',
			'meta_value'		=> (string) $slug,
		]);

		foreach ( $sponsors as $sponsor_id ) {
			update_post_meta( $sponsor_id, 'sponsor_level_order', (int) ( $level['order'] ?? 999 ) );
		}
	}
}

add_action( 'update_option_conboard_sponsor_levels', 'conboard_sync_sponsor_levels', 10, 2 );
add_action( 'add_option_conboard_sponsor_levels', function ( $option, $value ) {
	conboard_sync_sponsor_levels( [], $value );
}, 10, 2 );
