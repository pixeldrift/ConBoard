<?php
// includes/presenter_roles.php

// Defining presenter role definitions
function conboard_get_presenter_roles(): array {
    return apply_filters( 'conboard_presenter_roles', [

        'presenter' => [
            'singular' => 'Presenter',
            'plural'   => 'Presenters',
            'display'  => 'Presented by',
            'default'  => true,
        ],

        'speaker' => [
            'singular' => 'Speaker',
            'plural'   => 'Speakers',
            'display'  => 'Speaker',
        ],

        'panelist' => [
            'singular' => 'Panelist',
            'plural'   => 'Panelists',
            'display'  => 'Panelists',
        ],

        'moderator' => [
            'singular' => 'Moderator',
            'plural'   => 'Moderators',
            'display'  => 'Moderated by',
        ],

        'host' => [
            'singular' => 'Host',
            'plural'   => 'Hosts',
            'display'  => 'Hosted by',
        ],

        'mc' => [
            'singular' => 'MC',
            'plural'   => 'MCs',
            'display'  => 'MC',
        ],

        'instructor' => [
            'singular' => 'Instructor',
            'plural'   => 'Instructors',
            'display'  => 'Instructed by',
        ],

        'facilitator' => [
            'singular' => 'Facilitator',
            'plural'   => 'Facilitators',
            'display'  => 'Facilitated by',
        ],

        'performer' => [
            'singular' => 'Performer',
            'plural'   => 'Performers',
            'display'  => 'Performance by',
        ],

        'introducer' => [
            'singular' => 'Introducer',
            'plural'   => 'Introducers',
            'display'  => 'Introduction by',
        ],

    ]);
}



