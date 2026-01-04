<?php
// includes/post-types/cpt_room.php

add_action('init', function () {
    register_post_type('room', [
        'labels' => [
            'name' => 'Rooms',
            'singular_name' => 'Room',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'editor'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'rooms'],
        'show_in_rest' => true,
    ]);
});
