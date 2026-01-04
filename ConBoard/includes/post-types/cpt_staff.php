<?php
// includes/post-types/cpt_staff.php

add_action('init', function () {
    register_post_type('staff', [
        'labels' => [
            'name' => 'Staff',
            'singular_name' => 'Staff',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'editor'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'staff'],
        'show_in_rest' => true,
    ]);
});
