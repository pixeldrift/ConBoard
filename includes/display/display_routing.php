<?php
// includes/display/display_routing.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// In Screen Mode, ConBoard pages (and their archives) use the standalone screen template.
// Anything else, including regular WordPress pages, is left alone.
//
add_filter( 'template_include', function ( $template ) {

	if ( ! conboard_is_display() ) {
		return $template;
	}

	$types = conboard_screen_post_types();

	if ( is_singular( $types ) || is_singular( 'venue_screen' ) || is_post_type_archive( $types ) ) {
		return CONBOARD_PLUGIN_PATH . 'templates/display/screen.php';
	}

	return $template;
}, 20 );

//
// A Display Board only exists as a screen, so its plain link goes to screen mode.
// Any other options in the address (rotate, flip, ...) are kept.
//
add_action( 'template_redirect', function () {

	if ( is_singular( 'display_board' ) && ! conboard_is_display() ) {
		wp_safe_redirect( add_query_arg( 'display', 'screen' ), 302 );
		exit;
	}
});
