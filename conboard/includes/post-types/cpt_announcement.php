<?php
// includes/post-types/cpt_announcement.php

add_action('init', function () {
    register_post_type('announcement', [
        'labels' => [
            'name'               => 'Announcements',
            'singular_name'      => 'Announcement',
            'menu_name'          => 'Announcements',
            'add_new'            => 'Add Announcement',
            'add_new_item'       => 'Add New Announcement',
            'edit_item'          => 'Edit Announcement',
            'new_item'           => 'New Announcement',
            'view_item'          => 'View Announcement',
            'search_items'       => 'Search Announcements',
            'not_found'          => 'No Announcements found',
            'not_found_in_trash' => 'No Announcements found in Trash',
            'all_items'          => 'Announcements',
            'name_admin_bar'     => 'Announcements',
        ],
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => 'conboard',
        'icon_code'    => 'f488',  // dashicons-megaphone
        'supports'     => ['title', 'editor'],
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'announcements'],
        'show_in_rest' => true,
        'description'  => 'Important alerts or news for attendees',
    ]);
});
