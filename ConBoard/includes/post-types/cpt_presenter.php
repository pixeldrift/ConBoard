<?php
// includes/post-types/cpt_presenter.php

add_action('init', function () {
    register_post_type('presenter', [
        'labels' => [
            'name' => 'Presenters',
            'singular_name' => 'Presenter',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon' => 'dashicons-microphone',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'presenters'],
        'show_in_rest' => true,
    ]);
});
