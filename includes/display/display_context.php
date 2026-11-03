<?php
// includes/display/display_context.php


function conboard_context_from_display_board($post_id) {

    return [
        'source'        => 'display_board',
        'board_id'      => $post_id,

        // Content
        'display_mode'  => get_field('display_mode', $post_id),
        'room'          => get_field('display_room', $post_id),

        // Physical defaults
        'orientation'   => get_field('orientation', $post_id) ?: 'landscape',
        'rotation'      => intval(get_field('rotation', $post_id)),
        'flip'          => get_field('flip', $post_id) ?: 'none',

        // Behavior
        'slide_interval'   => intval(get_field('slide_interval', $post_id)),
        'refresh_interval' => intval(get_field('refresh_interval', $post_id)),
        'show_clock'       => (bool) get_field('show_clock', $post_id),
    ];
}


function conboard_context_from_url() {
    if (!isset($_GET['display'])) return [];

    return [
        'orientation' => $_GET['orientation'] ?? null,
        'rotation'    => isset($_GET['rotate']) ? intval($_GET['rotate']) : null,
        'flip'        => $_GET['flip'] ?? null,
    ];
}

function conboard_merge_display_context($base, $override) {
    foreach ($override as $key => $value) {
        if ($value !== null) {
            $base[$key] = $value;
        }
    }
    return $base;
}

function conboard_get_display_context() {
    static $context = null;
    if ($context !== null) {
        return $context;
    }

    $schema = conboard_display_param_schema();

    $context = [
        'is_display' => isset($_GET['display']),
        'params'     => [],
    ];

    if (!$context['is_display']) {
        return $context;
    }

    foreach ($schema as $key => $config) {
        $context['params'][$key] = $_GET[$key] ?? $config['default'];
    }

    return $context;
}


function conboard_is_display() {
    return conboard_get_display_context()['is_display'];
}