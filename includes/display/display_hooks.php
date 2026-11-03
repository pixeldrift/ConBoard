<?php
// includes/display/display_hooks.php

if ( ! defined( 'ABSPATH' ) ) exit;

//
// Screen Mode pages are standalone (no theme header or footer), so assets are registered
// and printed directly by the screen template rather than through wp_head().
//
function conboard_register_screen_assets(): void {

	wp_register_style(
		'conboard-screen',
		CONBOARD_PLUGIN_URL . 'assets/css/screen.css',
		[],
		'0.1.0'
	);

	wp_register_script(
		'conboard-qrcode',
		CONBOARD_PLUGIN_URL . 'assets/js/vendor/qrcode.js',
		[],
		'2.0.4',
		true
	);

	wp_register_script(
		'conboard-html-to-image',
		CONBOARD_PLUGIN_URL . 'assets/js/vendor/html-to-image.js',
		[],
		'1.11.13',
		true
	);

	wp_register_script(
		'conboard-zip',
		CONBOARD_PLUGIN_URL . 'assets/js/zip_store.js',
		[],
		'0.1.0',
		true
	);

	wp_register_script(
		'conboard-signage',
		CONBOARD_PLUGIN_URL . 'assets/js/signage.js',
		[ 'conboard-qrcode', 'conboard-html-to-image', 'conboard-zip' ],
		'0.1.0',
		true
	);
}


/**
 * Sync check for screens: a tiny public endpoint a screen calls every so often. If the answers stop
 * arriving, the screen shows its offline indicator. Also returns the server time in milliseconds.
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'conboard/v1', '/ping', [
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			nocache_headers();
			return rest_ensure_response( [ 'ok' => true, 'now' => (int) ( microtime( true ) * 1000 ) ] );
		},
	] );
} );
