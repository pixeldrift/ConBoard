<?php
// includes/admin/location_metaboxes_actions.php

if ( ! defined('ABSPATH') ) exit;

add_action('add_meta_boxes', function () {
    // Display Screen (side)
    add_meta_box(
        'location_display_board',
        'Display Screen',
        'conboard_location_display_metabox',
        'location',
        'side'
    );

    // Sessions in this Location (main content)
    add_meta_box(
        'location_sessions',
        'Sessions in this location',
        'conboard_render_location_sessions',
        'location',
        'normal',
        'default'
    );
});

function conboard_render_location_sessions( $post ) {
    $sessions = get_posts([
        'post_type'   => 'session',
        'numberposts' => -1,
        'meta_query'  => [
            [
                'key'   => 'location',
                'value' => $post->ID,
                'compare' => '=',
            ],
        ],
        'orderby' => 'title',
        'order'   => 'ASC',
    ]);

    if ( empty( $sessions ) ) {
        echo '<p>No sessions assigned to this location.</p>';
        return;
    }

    echo '<ul>';
    foreach ( $sessions as $session ) {
        echo '<li><a href="' . esc_url( get_edit_post_link( $session->ID ) ) . '">';
        echo esc_html( $session->post_title );
        echo '</a></li>';
    }
    echo '</ul>';
}


function conboard_location_display_metabox($post) {

    $existing = get_posts([
        'post_type' => 'display_board',
        'meta_key' => 'display_location',
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
        admin_url("admin-post.php?action=conboard_create_location_display&location={$post->ID}"),
        'conboard_create_location_display'
    );

    echo '<a class="button button-primary" href="' . esc_url($url) . '">Create display screen</a>';
}
