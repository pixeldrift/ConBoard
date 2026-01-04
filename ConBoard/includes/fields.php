<?php
// includes/fields.php

acf_add_local_field_group([
    'key' => 'group_conboard_session_fields',
    'title' => 'Session Details',
    'location' => [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'session',
            ],
        ],
    ],
    'fields' => [
        [
            'key' => 'field_start_time',
            'label' => 'Start Time',
            'name' => 'start_time',
            'type' => 'date_time_picker',
            'return_format' => 'Y-m-d H:i:s',
        ],
        [
            'key' => 'field_end_time',
            'label' => 'End Time',
            'name' => 'end_time',
            'type' => 'date_time_picker',
            'return_format' => 'Y-m-d H:i:s',
        ],
        [
            'key' => 'field_room',
            'label' => 'Room',
            'name' => 'room',
            'type' => 'post_object',
            'post_type' => ['room'],
            'return_format' => 'id',
        ],
        [
            'key' => 'field_participants',
            'label' => 'Participants',
            'name' => 'participants',
            'type' => 'relationship',
            'post_type' => ['presenter'],
        ],
        [
            'key' => 'field_status_override',
            'label' => 'Status Override',
            'name' => 'status_override',
            'type' => 'select',
            'choices' => [
                'normal' => 'Normal',
                'delayed' => 'Delayed',
                'canceled' => 'Canceled',
                'moved' => 'Moved',
            ],
            'default_value' => 'normal',
        ],
    ],
]);


acf_add_local_field_group([
    'key' => 'group_conboard_presenter_fields',
    'title' => 'Presenter Details',
    'fields' => [
        [
            'key' => 'field_presenter_title',
            'label' => 'Title',
            'name' => 'presenter_title',
            'type' => 'text',
        ],
        [
            'key' => 'field_presenter_org',
            'label' => 'Organization',
            'name' => 'presenter_org',
            'type' => 'text',
        ],
        [
            'key' => 'field_presenter_email',
            'label' => 'Email',
            'name' => 'presenter_email',
            'type' => 'email',
        ],
        [
            'key' => 'field_presenter_socials',
            'label' => 'Socials',
            'name' => 'presenter_socials',
            'type' => 'repeater',
            'button_label' => 'Add Social',
            'sub_fields' => [
                [
                    'key' => 'field_presenter_social_url',
                    'label' => 'URL',
                    'name' => 'url',
                    'type' => 'url',
                    'required' => 1,
                ],
            ],
        ],
        [
            'key' => 'field_presenter_websites',
            'label' => 'Websites',
            'name' => 'presenter_websites',
            'type' => 'repeater',
            'button_label' => 'Add Website',
            'sub_fields' => [
                [
                    'key' => 'field_presenter_website_url',
                    'label' => 'URL',
                    'name' => 'url',
                    'type' => 'url',
                    'required' => 1,
                ],
                [
                    'key' => 'field_presenter_website_label',
                    'label' => 'Label',
                    'name' => 'label',
                    'type' => 'text',
                    'instructions' => 'Optional (e.g. Personal Site, Company)',
                ],
            ],
        ],
    ],
    'location' => [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'conboard_presenter',
            ],
        ],
    ],
]);

