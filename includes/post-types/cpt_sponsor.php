<?php
// includes/post-types/cpt_sponsor.php

add_action('init', function () {
    register_post_type('sponsor', [
        'labels' => [
            'name'               => 'Sponsors',
            'singular_name'      => 'Sponsor',
            'menu_name'          => 'Sponsors',
            'add_new'            => 'Sponsor',
            'add_new_item'       => 'Add New Sponsor',
            'edit_item'          => 'Edit Sponsor',
            'new_item'           => 'New Sponsor',
            'view_item'          => 'View Sponsor',
            'search_items'       => 'Search Sponsors',
            'not_found'          => 'No Sponsor found',
            'not_found_in_trash' => 'No Sponsors found in Trash',
            'all_items'          => 'Sponsors',
            'name_admin_bar'     => 'Sponsors',
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => 'conboard',
        'icon_code'    => 'f155',  // dashicons-star-filled
        'supports'     => ['title', 'editor', 'thumbnail'],
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'sponsors'],
        'show_in_rest' => true,
        'description'  => 'Contributing companies, organizations, and individuals supporting the event',
    ]);
});
