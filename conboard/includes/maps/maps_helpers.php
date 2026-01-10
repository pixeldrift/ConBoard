<?php
// includes/maps/maps_helpers.php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Map helpers for ConBoard
 */

/**
 * Extract top-level SVG layers (<g id="..."> only)
 */
function conboard_get_location_map_layers( int $location_id ): array {

    $source_id = conboard_get_location_map_source( $location_id );
    if ( ! $source_id ) return [];

    $map_id = get_post_meta( $source_id, CONBOARD_LOCATION_MAP_FIELD, true );
    if ( ! $map_id ) return [];

    $svg_path = get_attached_file( (int) $map_id );
    if ( ! $svg_path || ! file_exists( $svg_path ) ) return [];

    $svg = file_get_contents( $svg_path );
    if ( ! $svg ) return [];

    libxml_use_internal_errors( true );
    $xml = simplexml_load_string( $svg );
    if ( ! $xml ) return [];

    $layers = [];

    // ONLY direct children of <svg>
    foreach ( $xml->children() as $node ) {
        if ( $node->getName() !== 'g' ) continue;

        $id = trim( (string) $node['id'] );
        if ( $id === '' ) continue;

        $layers[ $id ] = $id;
    }

    return $layers;
}

// -------------------------
// Checkbox layer logic
// -------------------------
add_filter(
    'acf/load_field/name=' . CONBOARD_LOCATION_MAP_LAYER_FIELD,
    function ( $field ) {

        $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
        if ( ! $post_id ) return $field;

        // Populate choices
        $field['choices'] = conboard_get_location_map_layers( $post_id );

        // If no value saved yet, inherit from parent
        $current = get_post_meta( $post_id, CONBOARD_LOCATION_MAP_LAYER_FIELD, true );
        if ( empty( $current ) ) {
            $field['value'] = conboard_get_inherited_map_layers( $post_id );
        }

        return $field;
    },
    100
);

/**
 * Show a preview of the assigned map (or inherited map) below the field.
 */
add_action('acf/render_field/key=field_location_map', function($field){
    $post_id = get_the_ID();
    $map_field = get_field('location_map', $post_id);

    if (!$map_field) {
        $source_id = conboard_get_location_map_source($post_id);
        $map_field = $source_id ? get_field('location_map', $source_id) : null;
    }

    if ($map_field) {
        $url = is_numeric($map_field)
            ? wp_get_attachment_url($map_field)
            : (is_array($map_field) && isset($map_field['url']) ? $map_field['url'] : $map_field);

        echo '<div class="conboard-map-layout">';

        echo '<div class="conboard-map-preview">';
        echo '<strong>Map Preview</strong><br>';
        echo '<img src="' . esc_url($url) . '" />';
        echo '</div>';

        echo '<div class="conboard-map-layers">';
        echo '<strong>Layers for this Location</strong>';
        echo '<p style="margin:6px 0 10px;color:#666;">Select which SVG layers are visible for this location.</p>';
        // DO NOT CLOSE DIV YET, must insert checkbox list first
    }
});

add_action('acf/render_field/key=field_location_map_layer', function($field){

    // Let ACF render the checkboxes normally
    // Then close the layout wrapper

    echo '</div>'; // .conboard-map-layers
    echo '</div>'; // .conboard-map-layout
});

