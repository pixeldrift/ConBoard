<?php
// admin/room_metaboxes_actions.php

add_action('add_meta_boxes', function () {
    add_meta_box(
        'room_display_board',
        'Display Screen',
        'conboard_room_display_metabox',
        'room',
        'side'
    );
});


function conboard_room_display_metabox($post) {

    $existing = get_posts([
        'post_type' => 'display_board',
        'meta_key' => 'display_room',
        'meta_value' => $post->ID,
        'posts_per_page' => 1,
    ]);

    if ($existing) {
        $board = $existing[0];
        echo '<p><strong>' . esc_html($board->post_title) . '</strong></p>';
        echo '<a class="button" href="' . get_permalink($board) . '" target="_blank">View Screen</a><br><br>';
        echo '<a class="button" href="' . get_edit_post_link($board->ID) . '">Edit Screen</a>';
        return;
    }

    $url = wp_nonce_url(
        admin_url("admin-post.php?action=conboard_create_room_display&room={$post->ID}"),
        'conboard_create_room_display'
    );

    echo '<a class="button button-primary" href="' . esc_url($url) . '">Create display screen</a>';
}
