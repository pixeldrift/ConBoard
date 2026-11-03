<?php
// includes/frontend/frontend_assets.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// Register front-end styles and scripts. They are only printed when used.
//
add_action( 'wp_enqueue_scripts', function () {

	wp_register_style(
		'conboard-frontend',
		CONBOARD_PLUGIN_URL . 'assets/css/frontend.css',
		[],
		'0.1.0'
	);

	wp_register_script(
		'conboard-carousel',
		CONBOARD_PLUGIN_URL . 'assets/js/carousel.js',
		[],
		'0.1.0',
		true
	);

	// ConBoard's own pages always get the base styles
	$types = [ 'presenter', 'session', 'location', 'sponsor', 'staff', 'announcement', 'display_board' ];

	if ( is_singular( $types ) || is_post_type_archive( $types ) ) {
		wp_enqueue_style( 'conboard-frontend' );
	}
} );

//
// Called by shortcodes and templates that need the styles (and optionally the carousel script)
//
function conboard_enqueue_frontend( bool $carousel = false ): void {
	wp_enqueue_style( 'conboard-frontend' );

	if ( $carousel ) {
		wp_enqueue_script( 'conboard-carousel' );
	}
}
