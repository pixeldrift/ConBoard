<?php
// includes/display/display_bootstrap.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// Screens always need live data, and are not for search engines or the admin bar
//
add_action( 'template_redirect', function () {

	if ( ! conboard_is_display() ) {
		return;
	}

	// Page caches must not store screens
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );

	add_filter( 'show_admin_bar', '__return_false' );
});
