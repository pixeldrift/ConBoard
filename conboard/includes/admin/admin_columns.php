<?php
// includes/admin/admin_columns.php

if ( ! defined('ABSPATH') ) exit;

/*
|--------------------------------------------------------------------------
| Rename Post Title Columns
|--------------------------------------------------------------------------
*/
conboard_rename_title_column('presenter', 'Presenter Name');
conboard_rename_title_column('session', 'Session Name');
conboard_rename_title_column('location', 'Location Name');
conboard_rename_title_column('sponsor', 'Sponsor Name');
conboard_rename_title_column('staff', 'Staff Name');
conboard_rename_title_column('display_board', 'Display Board Name');

/*
|--------------------------------------------------------------------------
| Default Sorting (Title + Session Count)
|--------------------------------------------------------------------------
*/
add_action('pre_get_posts', function ( WP_Query $query ) {

    if ( ! is_admin() || ! $query->is_main_query() ) return;

    $post_type = $query->get('post_type');
    if ( ! $post_type ) return;

    $post_types = (array) $post_type;

    // Only ConBoard CPTs
    if ( ! array_intersect( $post_types, CONBOARD_CPTS ) ) return;


    // Handle session_count sorting
    if ( $query->get('orderby') === 'session_count' ) {

        $type = is_array($post_type) ? reset($post_type) : $post_type;
        $meta_key = conboard_get_session_count_meta_key( $type );

        if ( $meta_key ) {
            $query->set('meta_key', $meta_key);
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        }
        return;
    }

    // Respect user-selected sorting
    if ( $query->get('orderby') ) return;

    // Default: sort by title ASC
    $query->set('orderby', 'title');
    $query->set('order', 'ASC');
});

/*
|--------------------------------------------------------------------------
| Admin Column Helpers
|--------------------------------------------------------------------------
*/

function conboard_render_meta_label( int $post_id, string $field ) {
    $value = function_exists('get_field') ? get_field( $field, $post_id ) : null;
    echo $value ? esc_html( ucwords(str_replace('_',' ', $value)) ) : '—';
}

function conboard_render_admin_link( string $url, string $label ) {
    echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
}

function conboard_insert_column( array $columns, string $key, string $label, $position ): array {

    $new = [ $key => $label ];

    if ( $position === 'first' ) return $new + $columns;
    if ( $position === 'last' ) return $columns + $new;

    if ( is_string($position) && preg_match('/^(before|after):(.+)$/', $position, $m) ) {
        [ , $where, $target ] = $m;
        $out = [];
        foreach ($columns as $k => $v) {
            if ($where === 'before' && $k === $target) $out += $new;
            $out[$k] = $v;
            if ($where === 'after' && $k === $target) $out += $new;
        }
        return $out;
    }

    // Default: after title
    $out = [];
    foreach ($columns as $k => $v) {
        $out[$k] = $v;
        if ($k === 'title') $out += $new;
    }
    return $out;
}

/*
|--------------------------------------------------------------------------
| Core Column Registration
|--------------------------------------------------------------------------
*/

function conboard_register_admin_column( string $post_type, array $column ): void {

    $key = $column['key'];

    add_filter(
        "manage_{$post_type}_posts_columns",
        fn( $cols ) => conboard_insert_column(
            $cols,
            $key,
            $column['label'],
            $column['position'] ?? 'after:title'
        )
    );

    if ( ! empty($column['sortable']) ) {
        $sort_key = $column['sortable'] ?? $key;

        add_filter(
            "manage_edit-{$post_type}_sortable_columns",
            fn( $cols ) => $cols + [ $key => $sort_key ]
        );
    }

    add_action(
        "manage_{$post_type}_posts_custom_column",
        function ( $col, $post_id ) use ( $key, $column ) {
            if ( $col === $key ) {
                call_user_func( $column['render'], $post_id );
            }
        },
        10,
        2
    );
}

/*
|--------------------------------------------------------------------------
| Add Columns to Admin Lists
|--------------------------------------------------------------------------
*/

// Add in reverse order you want them to appear!

function conboard_get_admin_columns_manifest(): array {
    return [

        // Presenters Page
        'presenter' => [
            [
                'key'       => 'sessions',
                'label'     => 'Sessions',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    // with link to sessions page, filtered by presenter ID
                    echo '—';
                }
            
            ],
            [
                'key'       => 'presenter_email',
                'label'     => 'Email',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $email = get_field('presenter_email', $post_id);
                    if ( ! $email ) { echo '—'; return; }
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                },
            ],
                        [
                'key'       => 'presenter_title',
                'label'     => 'Title',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $title = get_field('presenter_title', $post_id);
                    if (! $title) { echo '—'; return; }
                    $id = is_object($title) ? $title->ID : $title;
                       echo $id;
                },
            ],
        ],


        // Sessions Page
        'session' => [
            [
                'key'       => 'session_time',
                'label'     => 'Time',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $time = get_field('session_time', $post_id);
                    if (! $time) { echo '—'; return; }
                    $id = is_object($time) ? $time->ID : $time;
                },
            ],
            [
                'key'       => 'session_day',
                'label'     => 'Day',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $day = get_field('session_day', $post_id);
                    if (! $day) { echo '—'; return; }
                    $id = is_object($day) ? $day->ID : $day;
                },
            ],
            [
                'key'       => 'session_location',
                'label'     => 'Location',
                'sortable' => true,
                'render'    => function ( $post_id ) {
                    $location = get_field('location', $post_id);
                    if (! $location) { echo '—'; return; }
                    $id = is_object($location) ? $location->ID : $location;
                    conboard_render_admin_link(
                        get_edit_post_link($id),
                        get_the_title($id)
                    );
                },
            ],
            [
                'key'       => 'presenters',
                'label'     => 'Presenters',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $presenter = get_field('presenter', $post_id);
                    if (! $presenter) { echo '—'; return; }
                    $id = is_object($presenter) ? $pres->ID : $presenter;
                    conboard_render_admin_link(
                        get_edit_post_link($id),
                        get_the_title($id)
                    );
                },
            ],

        ],

        // Locations page
        'location' => [
            [
                'key'      => 'location_type',
                'label'    => 'Location Type',
                'sortable' => true,
                'render'   => fn($id)=>conboard_render_meta_label($id,'location_type'),
            ],
            [
                'key'      => 'room_type',
                'label'    => 'Room Type',
                'sortable' => true,
                'render'   => fn($id)=>conboard_render_meta_label($id,'room_type'),
            ],
            [
                'key'      => 'default_room_setup',
                'label'    => 'Room Setup',
                'sortable' => true,
                'render'   => fn($id)=>conboard_render_meta_label($id,'default_room_setup'),
            ],
            [
                'key'      => 'session_count',
                'label'    => 'Sessions',
                'position' => 2,
                'sortable' => true,
                'render'   => function ( $post_id ) {
                    $meta_key = conboard_get_session_count_meta_key('location');
                    $count = get_post_meta($post_id, $meta_key, true);
                    if ($count === '' || $count === false) { echo '—'; return; }
                    conboard_render_admin_link(
                        admin_url('edit.php?post_type=session&location_filter='.$post_id),
                        (string)(int)$count
                    );
                },
            ],
        ],

        // Sponsors Page
        'sponsor' => [
            [
                'key'      => 'website',
                'label'    => 'Website',
                'sortable' => false, // important
                'render'   => function ( $post_id ) {
                    $website = get_field( 'website', $post_id );
                    if ( ! $website ) { echo '—'; return; }
                    echo '<a href="' . esc_url($website) . '" target="_blank" rel="noopener">';
                    echo esc_html($website);
                    echo '</a>';
                },
            ],
            
            [
                'key'       => 'sponsor_level',
                'label'     => 'Sponsorship Level',
                'sortable' => 'sponsor_level_sort',
                'render'    => function ( $post_id ) {
                    $level = get_field('sponsor_level', $post_id);
                    if (! $level) { echo '—'; return; }
                    $id = is_object($level) ? $level->ID : $level;
                    $levels = conboard_get_sponsor_levels();
                    $label = $levels[ $id ]['label'] ?? $id;
                    $order = $levels[ $id ]['order'] ?? 999;
                    conboard_render_admin_link(
                        admin_url(
                            'edit.php?post_type=sponsor&sponsor_level='
                            . urlencode($id)
                        ),
                        esc_html((string)$order . ' - ' . $label)
                    );
                }
            ]
        ],


        // Staff Page
        'staff' => [
            [
                'key'       => 'email',
                'label'     => 'Email',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $email = get_field('email', $post_id);
                    if ( ! $email ) { echo '—'; return; }
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                },
            ],
            [
                'key'       => 'role',
                'label'     => 'Staff Role',
                'sortable'  => true,
                'render'    => function ( $post_id ) {
                    $role = get_field('role', $post_id);
                    echo $role ? esc_html($role) : '—';
                },
            ],
        ],

    ];
}

/*
|--------------------------------------------------------------------------
| Bootstrap Columns
|--------------------------------------------------------------------------
*/

add_action('admin_init', function () {

    foreach ( conboard_get_admin_columns_manifest() as $post_type => $columns ) {
        foreach ( $columns as $column ) {
            conboard_register_admin_column( $post_type, $column );
        }
    }
});

/*
|--------------------------------------------------------------------------
| Thumbnails (Presenter / Staff / Sponsor)
|--------------------------------------------------------------------------
*/

foreach ( ['presenter','staff','sponsor'] as $type ) {
    conboard_register_admin_column( $type, [
        'key'      => 'thumbnail',
        'label'    => '',
        'position' => 'before:title',
        'render'   => fn($id)=>conboard_render_admin_thumbnail($id),
    ]);
}

function conboard_render_admin_thumbnail( int $post_id, int $size = 40 ) {
    if ( ! has_post_thumbnail($post_id) ) { echo '—'; return; }
    echo get_the_post_thumbnail($post_id, [$size,$size], [
        'style'=>'object-fit:cover;border-radius:4px;border:1px solid #ccc;'
    ]);
}

add_action('admin_head', function () {
    echo '<style>
        .wp-list-table .column-thumbnail { width:60px;text-align:center; }
        .wp-list-table td, .wp-list-table th { vertical-align:middle; }
    </style>';
});



add_filter('posts_clauses', function ( $clauses, WP_Query $query ) {

    if ( ! is_admin() || ! $query->is_main_query() ) {
        return $clauses;
    }

    global $wpdb;

    // Whitelist of the fields we do our own manual sorting of
    $orderby = $query->get('orderby');
    if ( ! in_array( $orderby, [
        'sessions',
        'presenter_title',
        'role',
        'email',
        'location_type',
        'room_type',
        'sponsor_level_sorting',
        'default_room_setup',
    ], true ) ) {
        return $clauses;
    }

    $meta_key = $orderby;

    // Join meta table if not already joined
    if ( strpos( $clauses['join'], $wpdb->postmeta ) === false ) {
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS cbmeta
            ON ({$wpdb->posts}.ID = cbmeta.post_id AND cbmeta.meta_key = '{$meta_key}')";
    }

    /*
     * Ordering logic:
     * 1. Assigned values first
     * 2. Alphabetical meta value
     * 3. Post title as tiebreaker
     */
    $clauses['orderby'] = "
        CASE
            WHEN cbmeta.meta_value IS NULL OR cbmeta.meta_value = '' THEN 1
            ELSE 0
        END ASC,
        cbmeta.meta_value ASC,
        {$wpdb->posts}.post_title ASC
    ";

    return $clauses;
}, 10, 2);


add_filter('posts_clauses', function ( $clauses, WP_Query $query ) {

    if ( ! is_admin() || ! $query->is_main_query() ) {
        return $clauses;
    }

    if ( $query->get('orderby') !== 'sponsor_level_sort' ) {
        return $clauses;
    }

    global $wpdb;

    $order = strtoupper( $query->get('order') );
    if ( ! in_array( $order, ['ASC','DESC'], true ) ) {
        $order = 'ASC';
    }

    // LEFT JOIN sponsor_level meta
    if ( strpos( $clauses['join'], 'cbmeta_level' ) === false ) {
        $clauses['join'] .= "
            LEFT JOIN {$wpdb->postmeta} AS cbmeta_level
            ON ({$wpdb->posts}.ID = cbmeta_level.post_id
            AND cbmeta_level.meta_key = 'sponsor_level')
        ";
    }

    /*
     * Ordering rules:
     * 1. Assigned sponsor levels first
     * 2. Lower order first
     * 3. Unassigned last
     * 4. Title tiebreaker
     */
    $clauses['orderby'] = "
        CASE
            WHEN cbmeta_level.meta_value IS NULL OR cbmeta_level.meta_value = '' THEN 1
            ELSE 0
        END ASC,
        CAST(cbmeta_level.meta_value AS UNSIGNED) {$order},
        {$wpdb->posts}.post_title {$order}
    ";

    return $clauses;
}, 20, 2);

