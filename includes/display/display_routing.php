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

	if ( is_singular( $types ) || is_post_type_archive( $types ) ) {
		return CONBOARD_PLUGIN_PATH . 'templates/display/screen.php';
	}

	return $template;
}, 20 );
