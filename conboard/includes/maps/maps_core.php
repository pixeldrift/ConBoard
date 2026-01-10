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
 * Find the location that provides the map (using raw meta to avoid ACF recursion)
 */
function conboard_get_location_map_source( int $location_id ): ?int {

    while ( $location_id ) {

        $map = get_post_meta( $location_id, CONBOARD_LOCATION_MAP_FIELD, true );
        if ( ! empty( $map ) ) {
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
