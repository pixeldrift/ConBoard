<?php
// includes/post-types/cpt_sponsor.php

add_action('init', function () {
    register_post_type('sponsor', [
        'labels' => [
            'name' => 'Sponsors',
            'singular_name' => 'Sponsor',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'editor'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'sponsors'],
        'show_in_rest' => true,
    ]);
});
