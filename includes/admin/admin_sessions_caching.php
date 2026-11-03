<?php
// includes/admin/admin_session_caching.php

if ( ! defined( 'ABSPATH' ) ) exit;


function conboard_get_session_count_meta_key( string $post_type ): ?string {
	return [
		'presenter'	=> '_conboard_session_count_presenter',
		'location'	=> '_conboard_session_count_location',
	][ $post_type ] ?? null;
}

//
// Recalculate session count for a Presenter
//
function conboard_recalculate_session_count_for_presenter( int $presenter_id ) {

	$query = new WP_Query([
		'post_type'			=> 'session',
		'posts_per_page'	=> -1,
		'fields'			=> 'ids',
		'meta_query'		=> [
			[
				'key'		=> 'presenters', // ACF relationship
				'value'		=> '"' . $presenter_id . '"',
				'compare'	=> 'LIKE',
			],
		],
	]);

	update_post_meta(
		$presenter_id,
		'_conboard_session_count_presenter',
		(int) $query->found_posts
	);
}

//
// Recalculate session count for a Location
//
function conboard_recalculate_session_count_for_location( int $location_id ) {

	$query = new WP_Query([
		'post_type'			=> 'session',
		'posts_per_page'	=> -1,
		'fields'			=> 'ids',
		'meta_query'		=> [
			[
				'key'		=> 'location', // ACF field for session's location
				'value'		=> (string) $location_id,
				'compare'	=> '=',
			],
		],
	]);

	update_post_meta(
		$location_id,
		'_conboard_session_count_location',
		(int) $query->found_posts
	);
}

//
// Update affected Locations when a Session is saved
//
add_action( 'save_post_session', function ( $post_id, $post, $update ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Old cached location
	$old = get_post_meta( $post_id, '_conboard_cached_location', true );
	$old = is_array( $old ) ? $old : [];

	// New location (single or multiple)
	$new = get_field( 'location', $post_id );
	$new = $new ? (array) $new : [];

	// Cache new location
	update_post_meta( $post_id, '_conboard_cached_location', $new );

	// Locations affected
	$affected = array_unique( array_merge( $old, $new ) );

	foreach ( $affected as $location_id ) {
		conboard_recalculate_session_count_for_location( (int) $location_id );
	}

}, 10, 3 );

//
// Update affected Locations when a Session is deleted
//
add_action( 'before_delete_post', function ( $post_id ) {

	if ( get_post_type( $post_id ) !== 'session' ) {
		return;
	}

	$locations = get_post_meta( $post_id, '_conboard_cached_location', true );
	$locations = is_array( $locations ) ? $locations : [];

	foreach ( $locations as $location_id ) {
		conboard_recalculate_session_count_for_location( (int) $location_id );
	}

});

//
// Update affected Presenters when a Session is saved
//
add_action( 'save_post_session', function ( $post_id, $post, $update ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Old presenters (cached)
	$old = get_post_meta( $post_id, '_conboard_cached_presenters', true );
	$old = is_array( $old ) ? $old : [];

	// New presenters
	$new = get_field( 'presenters', $post_id );
	$new = is_array( $new ) ? $new : [];

	// Cache new presenters
	update_post_meta( $post_id, '_conboard_cached_presenters', $new );

	// All affected presenters
	$affected = array_unique( array_merge( $old, $new ) );

	foreach ( $affected as $presenter_id ) {
		conboard_recalculate_session_count_for_presenter( (int) $presenter_id );
	}

}, 10, 3 );

//
// Update affected Presenters when a Session is deleted
//
add_action( 'before_delete_post', function ( $post_id ) {

	if ( get_post_type( $post_id ) !== 'session' ) {
		return;
	}

	$presenters = get_post_meta( $post_id, '_conboard_cached_presenters', true );
	$presenters = is_array( $presenters ) ? $presenters : [];

	foreach ( $presenters as $presenter_id ) {
		conboard_recalculate_session_count_for_presenter( (int) $presenter_id );
	}

});
