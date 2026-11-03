<?php
// includes/display/display_routing.php

add_filter('template_include', function ($template) {

    if (!conboard_is_display()) {
        return $template;
    }

    if (is_singular('location')) {
        return CONBOARD_PLUGIN_DIR . 'templates/display/location.php';
    }

    if (is_singular('presenter')) {
        return CONBOARD_PLUGIN_DIR . 'templates/display/presenter.php';
    }

    if (is_singular('display_board')) {
        return CONBOARD_PLUGIN_DIR . 'templates/display/board.php';
    }

    return $template;
});
