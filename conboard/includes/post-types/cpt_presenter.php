<?php
// includes/post-types/cpt_presenter.php

add_action('init', function () {
    register_post_type('presenter', [
        'labels' => [
            'name'               => 'Presenters',
            'singular_name'      => 'Presenter',
            'menu_name'          => 'Presenters',
            'add_new'            => 'Add Presenter',
            'add_new_item'       => 'Add New Presenter',
            'edit_item'          => 'Edit Presenter',
            'new_item'           => 'New Presenter',
            'view_item'          => 'View Presenter',
            'search_items'       => 'Search Presenters',
            'not_found'          => 'No Presenters found',
            'not_found_in_trash' => 'No Presenters found in Trash',
            'all_items'          => 'Presenters',
            'name_admin_bar'     => 'Presenters',
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => 'conboard',
        'icon_code'    => 'f482',  // dashicons-microphone
        'supports'     => ['title', 'editor', 'thumbnail'],
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'presenters'],
        'show_in_rest' => true,
        'description'  => 'Speakers, panelists, moderators, and hosts',
    ]);
});
