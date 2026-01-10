<?php
// includes/maps/maps_admin.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Populate the "Map Layer" select field dynamically for Location CPT.
 */
add_action( 'acf/init', function() {

    // Only add filter if the field exists
    if ( ! function_exists('acf_add_local_field_group') ) {
        return;
    }

    add_filter('acf/load_field/name=location_map_layer', function( $field ) {

        // Get the current post ID in admin edit screen
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'location' ) {
            return $field;
        }

        // Make sure core helper exists
        if ( ! function_exists( 'conboard_get_location_map_layers' ) ) {
            return $field;
        }

        // Get layers from effective map (handles inheritance)
        $layers = conboard_get_location_map_layers( $post_id );

        // Populate ACF choices
        $field['choices'] = $layers ?: [];

        return $field;
    });

});

// Inject our styles in the head, they don't work in exteral .css files
add_action( 'admin_head', function () {
    ?>
    <style>
        .conboard-map-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-top: 10px;
        }

        .conboard-map-preview {
            flex: 0 0 600px;
        }

        .conboard-map-preview img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ccc;
            background: #fff;
        }

        .conboard-map-layers {
            flex: 1;
            overflow-y: auto;
            padding: 6px 4px;
            border-left: 1px solid #ddd;
        }

        .conboard-map-layers label {
            display: block;
            margin-bottom: 6px;
        }
    </style>
    <?php
});

// Hide the automatically inserted ACF list, we added it manually
add_action('admin_head', function () {
    echo '<style>
        .acf-field[data-name="' . CONBOARD_LOCATION_MAP_LAYER_FIELD . '"] {
            display: none;
        }
    </style>';
});

