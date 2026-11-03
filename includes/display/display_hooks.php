<?php
// includes/display/display_hooks.php

add_filter('body_class', function ($classes) {
    $ctx = conboard_get_display_context();
    if (!$ctx) return $classes;

    $classes[] = 'display-mode';
    $classes[] = 'display-' . $ctx['display'];
    $classes[] = 'orientation-' . $ctx['orientation'];

    if ($ctx['rotation']) {
        $classes[] = 'rotation-' . $ctx['rotation'];
    }

    return $classes;
});

add_action('wp_enqueue_scripts', function () {
    $ctx = conboard_get_display_context();
    if (!$ctx) return;

    wp_register_script(
        'conboard-signage',
        CONBOARD_PLUGIN_URL . 'assets/js/signage.js',
        [],
        '0.1.0',
        true
    );

    wp_enqueue_script('conboard-signage');

    wp_localize_script(
        'conboard-signage',
        'ConBoardDisplay',
        $ctx
    );
});
