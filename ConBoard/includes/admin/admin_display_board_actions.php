<?php
// admin/display_board_actions.php

add_action('admin_post_conboard_create_room_display', function () {

    if (!current_user_can('edit_posts')) {
        wp_die('Permission denied');
    }

    check_admin_referer('conboard_create_room_display');

    $room_id = intval($_GET['room']);

    $post_id = wp_insert_post([
        'post_type' => 'display_board',
        'post_title' => get_the_title($room_id) . ' – Room Screen',
        'post_status' => 'publish',
    ]);

    update_field('display_mode', 'room_schedule', $post_id);
    update_field('display_room', $room_id, $post_id);

    wp_redirect(get_edit_post_link($post_id, ''));
    exit;
});
