<?php
// includes/post-types/cpt_display_board.php

add_action('init', function () {
    register_post_type('display_board', [
        'labels' => [
            'name'               => 'Display Boards',
            'singular_name'      => 'Display Board',
            'menu_name'          => 'Display Boards',
            'add_new'            => 'Display Board',
            'add_new_item'       => 'Add New Display Board',
            'edit_item'          => 'Edit Display Board',
            'new_item'           => 'New Display Board',
            'view_item'          => 'View Display Board',
            'search_items'       => 'Search Display Boards',
            'not_found'          => 'No Display Board found',
            'not_found_in_trash' => 'No Display Boards found in Trash',
            'all_items'          => 'Display Boards',
            'name_admin_bar'     => 'Display Boards',
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => 'conboard',
        'icon_code'    => 'f115',  // dashicons-welcome-view-site
        'supports'     => ['title'],
        'has_archive'  => false,
        'rewrite'      => ['slug' => 'display'],
        'show_in_rest' => true,
        'description'  => 'Custom, re-usable info-screens for digital signage',
    ]);
});
