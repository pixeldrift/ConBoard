<?php
// includes/acf/acf_location.php

if ( ! defined('ABSPATH') ) { exit; }


add_action('acf/init', function () {
    
    if ( ! function_exists('acf_add_local_field_group') ) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_conboard_location',
        'title'  => 'Location Details',
        'fields' => [


                // -------------------------
                // Location Type
                // -------------------------
                [
                    'key' => 'field_location_type',
                    'label' => 'Location Type',
                    'name' => 'location_type',
                    'type' => 'select',
                    'required' => 1,
                    'choices' => [
                        ''         => '-Select Location Type-',
                        'campus'   => 'Campus',
                        'building' => 'Building',
                        'floor'    => 'Floor',
                        'room'     => 'Room',
                    ],
                    'allow_null' => 1,
                    'ui' => 1,
                    'return_format' => 'value',
                ],

                // -------------------------
                // Room Type (Room only)
                // -------------------------
                [
                    'key' => 'field_room_type',
                    'label' => 'Room Type',
                    'name' => 'room_type',
                    'type' => 'select',
                    'choices' => [
                        ''                => '-Select Room Type-',
                        'ballroom'        => 'Ballroom',
                        'banquet_hall'    => 'Banquet Hall',
                        'breakout'        => 'Breakout Room',
                        'cabin'           => 'Cabin',
                        'chapel'          => 'Chapel',
                        'conference'      => 'Conference Room',
                        'deck'            => 'Deck',
                        'dining_hall'     => 'Dining Hall',
                        'exhibit_hall'    => 'Exhibit Hall',
                        'fellowship'      => 'Fellowship Hall',
                        'gallery'         => 'Gallery',
                        'garden'          => 'Garden',
                        'gazebo'          => 'Gazebo',
                        'grand_ballroom'  => 'Grand Ballroom',
                        'gymnasium'       => 'Gymnasium',
                        'lawn'            => 'Lawn',
                        'lounge'          => 'Lounge',
                        'meeting_hall'    => 'Meeting Hall',
                        'office'          => 'Office',
                        'outdoor'         => 'Outdoor',
                        'patio'           => 'Patio',
                        'pavilion'        => 'Pavilion',
                        'restaurant'      => 'Restaurant',
                        'showroom'        => 'Showroom',
                        'sportsfield'     => 'Sportsfield',
                        'stadium'         => 'Stadium',
                        'tent'            => 'Tent',
                        'terrace'         => 'Terrace',
                        'other'           => 'Other / Custom',
                    ],
                    'allow_null' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_location_type',
                                'operator' => '==',
                                'value' => 'room',
                            ],
                        ],
                    ],
                    'ui' => 1,
                    'return_format' => 'value',
                ],

                // -------------------------
                // Default Room Setup
                // -------------------------
                [
                    'key' => 'field_default_room_setup',
                    'label' => 'Default Room Setup',
                    'name' => 'default_room_setup',
                    'type' => 'select',
                    'choices' => [
                        ''               => '-Select Room Setup-',
                        'banquet'        => 'Banquet',
                        'boardroom'      => 'Boardroom',
                        'cabaret'        => 'Cabaret',
                        'circle'         => 'Circle',
                        'classroom'      => 'Classroom',
                        'cocktail'       => 'Cocktail',
                        'empty'          => 'Empty',
                        'hollow_square'  => 'Hollow Square',
                        'horseshoe'      => 'Horseshoe',
                        'semicircle'     => 'Semicircle',
                        'theater'        => 'Theater',
                        'workshop'       => 'Workshop',
                    ],
                    'allow_null' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_location_type',
                                'operator' => '==',
                                'value' => 'room',
                            ],
                        ],
                    ],
                    'ui' => 1,
                    'return_format' => 'value',
                ],


            [
                'key'   => 'field_location_capacity',
                'label' => 'Capacity',
                'name'  => 'capacity',
                'type'  => 'number',
                'instructions' => 'Maximum number of people this location configuration can accommodate.',
                'min'   => 0,
                'step'  => 1,
            ],

            [
                'key'   => 'field_location_notes',
                'label' => 'Notes',
                'name'  => 'notes',
                'type'  => 'textarea',
                'instructions' => 'Optional internal notes about this location configuration (AV, layout, restrictions, etc).',
                'rows' => 3,
            ],

        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'location',
                ],
            ],
        ],
    ]);
});