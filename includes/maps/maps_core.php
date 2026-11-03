<?php
// includes/maps/maps_core.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Path helpers
define( 'CONBOARD_MAPS_DIR', __DIR__ );
define( 'CONBOARD_MAPS_URL', CONBOARD_PLUGIN_URL . '/includes/maps' );

// ACF field names
define( 'CONBOARD_LOCATION_MAP_FIELD', 'location_map' );
define( 'CONBOARD_LOCATION_MAP_LAYER_FIELD', 'location_map_layer' );

// Load submodules
require_once CONBOARD_MAPS_DIR . '/maps_helpers.php';
require_once CONBOARD_MAPS_DIR . '/maps_acf.php';
require_once CONBOARD_MAPS_DIR . '/maps_admin.php';

/**
 * Is this attachment id a usable map? (the attachment still exists)
 */
function conboard_map_attachment_ok( $attachment_id ): bool {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) return false;
	return get_post_type( $attachment_id ) === 'attachment';
}

/**
 * Map attachment id stored on a location itself (0 when none or the file was deleted)
 */
function conboard_get_location_own_map_id( int $location_id ): int {
	$id = (int) get_post_meta( $location_id, CONBOARD_LOCATION_MAP_FIELD, true );
	return conboard_map_attachment_ok( $id ) ? $id : 0;
}

/**
 * Find the location that provides the map (using raw meta to avoid ACF recursion).
 * A map whose file was deleted is skipped, so the location falls back to its parent's map.
 */
function conboard_get_location_map_source( int $location_id ): ?int {

	while ( $location_id ) {

		if ( conboard_get_location_own_map_id( $location_id ) ) {
			return $location_id;
		}

		$post = get_post( $location_id );
		if ( ! $post || ! $post->post_parent ) {
			break;
		}

		$location_id = (int) $post->post_parent;
	}

	return null;
}

/**
 * Is this location's map a transparent highlight laid over its parent's map?
 */
function conboard_location_map_is_overlay( int $location_id ): bool {
	return get_post_meta( $location_id, 'map_overlay', true ) === '1'
		&& conboard_get_location_own_map_id( $location_id ) > 0;
}

/**
 * Map visibility toggle (on unless explicitly turned off)
 */
function conboard_location_shows_map( int $location_id ): bool {
	return get_post_meta( $location_id, 'show_map', true ) !== '0';
}

/**
 * Work out what to draw for a location, bottom to top:
 *   base      attachment id of the base map
 *   highlight attachment id of a transparent room image over the base (or 0)
 *   labels    attachment id of a labels image drawn above everything (or 0)
 *   source    location id that supplied the map the visitor sees
 *   base_loc  location id that supplied the base
 */
function conboard_get_location_map_stack( int $location_id ): ?array {

	$source = conboard_get_location_map_source( $location_id );
	if ( ! $source ) return null;

	$source_map = conboard_get_location_own_map_id( $source );
	$base_loc   = $source;
	$highlight  = 0;

	// A highlight only works over a base image, so look further up the tree for one
	if ( conboard_location_map_is_overlay( $source ) ) {
		$cursor = (int) wp_get_post_parent_id( $source );
		$found  = 0;
		while ( $cursor ) {
			if ( conboard_get_location_own_map_id( $cursor ) && ! conboard_location_map_is_overlay( $cursor ) ) {
				$found = $cursor;
				break;
			}
			$cursor = (int) wp_get_post_parent_id( $cursor );
		}
		if ( $found ) {
			$base_loc  = $found;
			$highlight = $source_map;
		}
	}

	$base = conboard_get_location_own_map_id( $base_loc );

	// Labels: from the base map's location, otherwise nearest in the chain above the source
	$labels = 0;
	$cursor = $base_loc;
	while ( $cursor && ! $labels ) {
		$labels = conboard_get_location_labels_id( $cursor );
		$cursor = (int) wp_get_post_parent_id( $cursor );
	}
	if ( ! $labels && $source !== $base_loc ) {
		$labels = conboard_get_location_labels_id( $source );
	}

	return [
		'base'      => $base,
		'highlight' => $highlight,
		'labels'    => $labels,
		'source'    => $source,
		'base_loc'  => $base_loc,
	];
}

/**
 * Labels overlay image stored on a location (0 when none or deleted)
 */
function conboard_get_location_labels_id( int $location_id ): int {
	$id = (int) get_post_meta( $location_id, 'map_labels', true );
	return conboard_map_attachment_ok( $id ) ? $id : 0;
}

/**
 * Get inherited layer selections
 */
function conboard_get_inherited_map_layers( int $location_id ): array {

	while ( $location_id ) {

		$layers = get_post_meta( $location_id, CONBOARD_LOCATION_MAP_LAYER_FIELD, true );
		if ( is_array( $layers ) && ! empty( $layers ) ) {
			return $layers;
		}

		$post = get_post( $location_id );
		if ( ! $post || ! $post->post_parent ) {
			break;
		}

		$location_id = (int) $post->post_parent;
	}

	return [];
}

/**
 * Does a location have its own explicitly assigned map?
 * (Safe to use get_field here)
 */
function conboard_location_has_own_map( int $location_id ): bool {
	return ! empty( get_field( CONBOARD_LOCATION_MAP_FIELD, $location_id ) );
}

/**
 * Get selected layers for a location (non-inherited)
 */
function conboard_get_location_map_layers_selected( int $location_id ): array {
	$layers = get_field( CONBOARD_LOCATION_MAP_LAYER_FIELD, $location_id );
	return is_array( $layers ) ? $layers : [];
}
