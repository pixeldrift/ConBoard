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
// (presenters live in the session's "presenters" repeater, so use the shared lookup)
//
function conboard_recalculate_session_count_for_presenter( int $presenter_id ) {

	update_post_meta(
		$presenter_id,
		'_conboard_session_count_presenter',
		count( conboard_get_session_ids_for_presenter( $presenter_id ) )
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
// Recount Locations and Presenters affected by a Session.
// Runs on acf/save_post (priority 20) rather than save_post_session, because ACF
// writes its field values during save_post, after save_post_{type} has already fired.
// Reading earlier would give the previous values.
//
add_action( 'acf/save_post', function ( $post_id ) {

	if ( ! is_numeric( $post_id ) || get_post_type( $post_id ) !== 'session' ) {
		return;
	}

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$post_id = (int) $post_id;

	// ---- Locations ----
	$old_locations = conboard_normalize_ids( get_post_meta( $post_id, '_conboard_cached_location', true ) );
	$new_locations = conboard_normalize_ids( get_field( 'location', $post_id ) );

	update_post_meta( $post_id, '_conboard_cached_location', $new_locations );

	foreach ( array_unique( array_merge( $old_locations, $new_locations ) ) as $location_id ) {
		conboard_recalculate_session_count_for_location( (int) $location_id );
	}

	// ---- Presenters ----
	$old_presenters = conboard_normalize_ids( get_post_meta( $post_id, '_conboard_cached_presenters', true ) );
	$new_presenters = array_column( conboard_get_session_presenters( $post_id ), 'presenter_id' );

	update_post_meta( $post_id, '_conboard_cached_presenters', $new_presenters );

	foreach ( array_unique( array_merge( $old_presenters, $new_presenters ) ) as $presenter_id ) {
		conboard_recalculate_session_count_for_presenter( (int) $presenter_id );
	}

}, 20 );

//
// Recount the Locations and Presenters a Session was cached against
//
function conboard_recount_cached_for_session( int $session_id ) {

	$locations  = conboard_normalize_ids( get_post_meta( $session_id, '_conboard_cached_location', true ) );
	$presenters = conboard_normalize_ids( get_post_meta( $session_id, '_conboard_cached_presenters', true ) );

	foreach ( $locations as $location_id ) {
		conboard_recalculate_session_count_for_location( $location_id );
	}

	foreach ( $presenters as $presenter_id ) {
		conboard_recalculate_session_count_for_presenter( $presenter_id );
	}
}

//
// When a Session is deleted, recount after it is actually gone from the database
//
add_action( 'before_delete_post', function ( $post_id ) {

	if ( get_post_type( $post_id ) !== 'session' ) {
		return;
	}

	$post_id = (int) $post_id;

	// Remember the cached IDs now, recount once the post has been removed
	$locations  = conboard_normalize_ids( get_post_meta( $post_id, '_conboard_cached_location', true ) );
	$presenters = conboard_normalize_ids( get_post_meta( $post_id, '_conboard_cached_presenters', true ) );

	add_action( 'deleted_post', function ( $deleted_id ) use ( $post_id, $locations, $presenters ) {

		if ( (int) $deleted_id !== $post_id ) {
			return;
		}

		foreach ( $locations as $location_id ) {
			conboard_recalculate_session_count_for_location( $location_id );
		}

		foreach ( $presenters as $presenter_id ) {
			conboard_recalculate_session_count_for_presenter( $presenter_id );
		}
	});
});

//
// Recount when a Session is published, unpublished or trashed (counts only include published sessions)
//
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {

	if ( $post->post_type !== 'session' || $new_status === $old_status ) {
		return;
	}

	conboard_recount_cached_for_session( (int) $post->ID );

}, 10, 3 );
