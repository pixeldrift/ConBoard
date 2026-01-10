<?php
// includes/admin/admin_session_conflicts.php

if ( ! defined('ABSPATH') ) exit;

//
// Detect location conflicts when saving a session
//
add_action('save_post_session', 'conboard_check_session_location_conflicts', 20, 3);

function conboard_check_session_location_conflicts( $post_id, $post, $update ) {

    // Avoid autosave / revisions
    if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) {
        return;
    }

    // Only run in admin
    if ( ! is_admin() ) {
        return;
    }

    // Get ACF values
    $location_id    = get_field('location', $post_id);
    $start_time = get_field('start_time', $post_id);
    $end_time   = get_field('end_time', $post_id);

    if ( ! $location_id || ! $start_time || ! $end_time ) {
        return;
    }

    // Normalize to timestamps
    $start_ts = strtotime($start_time);
    $end_ts   = strtotime($end_time);

    if ( ! $start_ts || ! $end_ts || $end_ts <= $start_ts ) {
        return;
    }

    // Get all conflicting location IDs (ancestors + descendants + self)
    $conflicting_locations = conboard_get_overlapping_location_ids( $location_id );

    // Query other sessions
    $sessions = get_posts([
        'post_type'      => 'session',
        'posts_per_page' => -1,
        'post__not_in'   => [$post_id],
        'meta_query'     => [
            [
                'key'     => 'location',
                'value'   => $conflicting_locations,
                'compare' => 'IN',
            ],
        ],
    ]);

    $conflicts = [];

    foreach ( $sessions as $session ) {

        $s_start = strtotime( get_field('start_time', $session->ID) );
        $s_end   = strtotime( get_field('end_time', $session->ID) );

        if ( ! $s_start || ! $s_end ) {
            continue;
        }

        // Time overlap check
        if ( $start_ts < $s_end && $end_ts > $s_start ) {
            $conflicts[] = $session;
        }
    }

    if ( empty($conflicts) ) {
        delete_post_meta($post_id, '_conboard_location_conflicts');
        return;
    }

    // Store conflicts for admin notice
    $conflict_ids = wp_list_pluck($conflicts, 'ID');
    update_post_meta($post_id, '_conboard_location_conflicts', $conflict_ids);
}


//
// Get all location IDs that spatially overlap with this location
// (self + ancestors + descendants)
//
function conboard_get_overlapping_location_ids( $location_id ) {

    $ids = [$location_id];

    // Ancestors
    $ancestors = get_post_ancestors( $location_id );
    $ids = array_merge( $ids, $ancestors );

    // Descendants
    $children = get_posts([
        'post_type'      => 'location',
        'posts_per_page' => -1,
        'post_parent'    => $location_id,
        'fields'         => 'ids',
    ]);

    foreach ( $children as $child_id ) {
        $ids = array_merge( $ids, conboard_get_overlapping_location_ids( $child_id ) );
    }

    return array_unique( $ids );
}


//
// Show admin warning if conflicts exist
//
add_action('admin_notices', function () {

    global $post;

    if ( ! $post || $post->post_type !== 'session' ) {
        return;
    }

    $conflicts = get_post_meta($post->ID, '_conboard_location_conflicts', true);

    if ( empty($conflicts) || ! is_array($conflicts) ) {
        return;
    }

    echo '<div class="notice notice-warning">';
    echo '<p><strong>Location scheduling conflict detected:</strong></p>';
    echo '<ul>';

    foreach ( $conflicts as $conflict_id ) {
        echo '<li>';
        echo '<a href="' . esc_url( get_edit_post_link($conflict_id) ) . '">';
        echo esc_html( get_the_title($conflict_id) );
        echo '</a>';
        echo '</li>';
    }

    echo '</ul>';
    echo '<p>This session overlaps in time with another session using the same physical space.</p>';
    echo '</div>';
});



