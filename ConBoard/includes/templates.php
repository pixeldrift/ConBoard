<?php
// includes/templates.php

add_filter('template_include', function ($template) {

    if (!is_singular() && !is_post_type_archive()) {
        return $template;
    }

    $post_type = get_post_type();

    $templates = [
        'conboard_announcement'  => [
            'single'  => 'single-conboard_announcement.php',
            'archive' => 'archive-conboard_announcement.php',
        ],
        'conboard_display_board' => [
            'single'  => 'single-conboard_display-board.php',
        ],
        'conboard_presenter'     => [
            'single'  => 'single-conboard_presenter.php',
            'archive' => 'archive-conboard_presenter.php',
        ],
        'conboard_room'          => [
            'single'  => 'single-conboard_room.php',
        ],
        'conboard_session'       => [
            'single'  => 'single-conboard_session.php',
            'archive' => 'archive-conboard_session.php',
        ],
        'conboard_sponsor'       => [
            'single'  => 'single-conboard_sponsor.php',
        ],
        'conboard_staff'         => [
            'single'  => 'single-conboard_staff.php',
        ],
    ];

    if (!isset($templates[$post_type])) {
        return $template;
    }

    // Singular
    if (is_singular() && !empty($templates[$post_type]['single'])) {
        return conboard_locate_template($templates[$post_type]['single']);
    }

    // Archive
    if (is_post_type_archive() && !empty($templates[$post_type]['archive'])) {
        return conboard_locate_template($templates[$post_type]['archive']);
    }

    return $template;
});
