<?php
// includes/post-types/cpt_announcement.php

add_action('init', function () {
    register_post_type('announcement', [
        'labels' => [
            'name' => 'Announcements',
            'singular_name' => 'Announcement',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'editor'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'announcements'],
        'show_in_rest' => true,
    ]);
});
