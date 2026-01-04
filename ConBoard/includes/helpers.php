<?php
// includes/helpers.php


// Query helper
function conboard_get_sessions_for_speaker($presenter_id) {
    return new WP_Query([
        'post_type' => 'session',
        'meta_query' => [
            [
                'key' => 'participants',
                'value' => '"' . $presenter_id . '"',
                'compare' => 'LIKE',
            ],
        ],
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'start_time',
        'order' => 'ASC',
    ]);
}


// Time helper
function conboard_get_session_state($session_id, $now = null) {
    $now = $now ?: current_time('timestamp');

    $start = strtotime(get_field('start_time', $session_id));
    $end   = strtotime(get_field('end_time', $session_id));

    if ($end < $now) return 'past';
    if ($start <= $now && $end >= $now) return 'current';
    return 'upcoming';
}


// Template Helper
function conboard_locate_template($template_name) {

    // 1. Theme override
    $theme_template = locate_template('conboard/' . $template_name);
    if ($theme_template) {
        return $theme_template;
    }

    // 2. Plugin fallback
    return CONBOARD_PLUGIN_PATH . 'templates/' . $template_name;
}


