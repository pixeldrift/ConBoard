<?php
// includes/maps/maps_acf.php

if ( ! defined('ABSPATH') ) exit;

add_action( 'acf/init', 'conboard_register_location_map_fields' );

function conboard_register_location_map_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    acf_add_local_field_group([
        'key'    => 'group_location_maps',
        'title'  => 'Location Map',
        'fields' => [

            [
                'key'   => 'field_location_map',
                'label' => 'Map File',
                'name'  => 'location_map',
                'type'  => 'file',
                'return_format' => 'id',
                'mime_types'    => 'svg,png,jpg,jpeg',
            ],

            [
                'key'   => 'field_location_map_layer',
                'label' => 'Map Layers',
                'name'  => 'location_map_layer',
                'type'  => 'checkbox',
                'choices' => [],     // populated dynamically
                'layout'  => 'vertical',
            ],
        ],

        'location' => [[[
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'location',
        ]]],
    ]);
}
