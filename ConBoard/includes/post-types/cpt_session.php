<?php
// includes/post-types/cpt_session.php

add_action('init', function () {
    register_post_type('session', [
        'labels' => [
            'name' => 'Sessions',
            'singular_name' => 'Session',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'sessions'],
        'show_in_rest' => true,
    ]);
});
