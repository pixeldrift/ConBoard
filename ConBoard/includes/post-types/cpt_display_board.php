<?php
// includes/post-types/cpt_display_board.php

add_action('init', function () {
    register_post_type('display_board', [
        'labels' => [
            'name'          => 'Display Boards',
            'singular_name' => 'Display Board',
        ],
        'public'        => true,
        'show_ui' => true,
        'show_in_menu' => 'conboard',
        'menu_icon'     => 'dashicons-desktop',
        'supports'      => ['title'],
        'has_archive'   => false,
        'rewrite'       => ['slug' => 'display'],
        'show_in_rest'  => true,
    ]);
});
