<?php
// includes/sponsor_levels.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// Sponsor level definitions
//
function conboard_get_sponsor_levels() {
    return [
        'platinum' => [
            'singular' => 'Platinum Sponsor',
            'plural'   => 'Platinum Sponsors',
            'label'    => 'Platinum',
            'order'    => 1,
        ],
        'gold' => [
            'singular' => 'Gold Sponsor',
            'plural'   => 'Gold Sponsors',
            'label'    => 'Gold',
            'order'    => 2,
        ],
        'silver' => [
            'singular' => 'Silver Sponsor',
            'plural'   => 'Silver Sponsors',
            'label'    => 'Silver',
            'order'    => 3,
        ],
        'bronze' => [
            'singular' => 'Bronze Sponsor',
            'plural'   => 'Bronze Sponsors',
            'label'    => 'Bronze',
            'order'    => 4,
        ],
        'community' => [
            'singular' => 'Community Sponsor',
            'plural'   => 'Community Sponsors',
            'label'    => 'Community',
            'order'    => 5,
        ],
    ];
}



// Persist sponsor level order as numeric meta for sorting

add_action('acf/save_post', function ( $post_id ) {

    // Only sponsors
    if ( get_post_type($post_id) !== 'sponsor' ) {
        return;
    }

    // Get selected sponsor level (ACF field)
    $level = get_field('sponsor<?php
// includes/sponsor_levels.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// Sponsor level definitions
//
function conboard_get_sponsor_levels() {
    return [
        'platinum' => [
            'singular' => 'Platinum Sponsor',
            'plural'   => 'Platinum Sponsors',
            'label'    => 'Platinum',
            'order'    => 1,
        ],
        'gold' => [
            'singular' => 'Gold Sponsor',
            'plural'   => 'Gold Sponsors',
            'label'    => 'Gold',
            'order'    => 2,
        ],
        'silver' => [
            'singular' => 'Silver Sponsor',
            'plural'   => 'Silver Sponsors',
            'label'    => 'Silver',
            'order'    => 3,
        ],
        'bronze' => [
            'singular' => 'Bronze Sponsor',
            'plural'   => 'Bronze Sponsors',
            'label'    => 'Bronze',
            'order'    => 4,
        ],
        'community' => [
            'singular' => 'Community Sponsor',
            'plural'   => 'Community Sponsors',
            'label'    => 'Community',
            'order'    => 5,
        ],
    ];
}



// Persist sponsor level order as numeric meta for sorting

add_action('acf/save_post', function ( $post_id ) {

    // Only sponsors
    if ( get_post_type($post_id) !== 'sponsor' ) {
        return;
    }

    // Get selected sponsor level (ACF field)
    $level = get_field('sponsor_level', $post_id);
    if ( ! $level ) {
        return;
    }

    // Get level definitions
    $levels = conboard_get_sponsor_levels();

    if ( empty( $levels[ $level ]['order'] ) ) {
        return;
    }

    // Store numeric order for sorting
    update_post_meta(
        $post_id,
        'sponsor_level_order',
        (int) $levels[ $level ]['order']
    );

}, 20); // after ACF saves fields
_level', $post_id);
    if ( ! $level ) {
        return;
    }

    // Get level definitions
    $levels = conboard_get_sponsor_levels();

    if ( empty( $levels[ $level ]['order'] ) ) {
        return;
    }

    // Store numeric order for sorting
    update_post_meta(
        $post_id,
        'sponsor_level_order',
        (int) $levels[ $level ]['order']
    );

}, 20); // after ACF saves fields
