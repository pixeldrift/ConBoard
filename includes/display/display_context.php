<?php
// includes/display/display_context.php
//
// Works out how the current request should be displayed on a screen.
//
// Order of precedence (later wins):
//   1. built-in defaults
//   2. a Display Board's saved settings (when the page is a Display Board)
//   3. URL parameters, for one-off fixes at the venue. These are never saved.

if ( ! defined( 'ABSPATH' ) ) exit;

function conboard_is_display(): bool {
	return isset( $_GET['display'] ) && conboard_normalize_display_param( 'display', $_GET['display'] ) === 'screen';
}

// The post types that can be shown in Screen Mode
function conboard_screen_post_types(): array {
	return [ 'location', 'session', 'presenter', 'sponsor', 'staff', 'announcement' ];
}

/**
 * Settings saved on a Display Board (all optional).
 * Display Boards are mapped to Screen Mode in a later step; this is ready for them.
 */
function conboard_context_from_display_board( int $board_id ): array {

	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	$settings = [];

	$orientation = conboard_normalize_display_param( 'orientation', get_field( 'orientation', $board_id ) );
	if ( $orientation !== null ) $settings['orientation'] = $orientation;

	$rotate = conboard_normalize_display_param( 'rotate', get_field( 'rotation', $board_id ) );
	if ( $rotate !== null ) $settings['rotate'] = $rotate;

	$flip = conboard_normalize_display_param( 'flip', get_field( 'flip', $board_id ) );
	if ( $flip !== null ) $settings['flip'] = $flip;

	$interval = conboard_clamp_display_int( get_field( 'slide_interval', $board_id ), 3, 300 );
	if ( $interval !== null && $interval > 0 ) $settings['interval'] = $interval;

	$refresh = conboard_clamp_display_int( get_field( 'refresh_interval', $board_id ), 15, 3600 );
	if ( $refresh !== null && $refresh > 0 ) $settings['refresh'] = $refresh;

	$clock = get_field( 'show_clock', $board_id );
	if ( $clock !== null && $clock !== '' ) $settings['clock'] = (bool) $clock;

	return $settings;
}

// Overrides present in the URL (only the ones that are valid)
function conboard_context_from_url(): array {

	$overrides = [];

	foreach ( array_keys( conboard_display_param_schema() ) as $key ) {
		if ( $key === 'display' || ! isset( $_GET[ $key ] ) ) {
			continue;
		}

		$value = conboard_normalize_display_param( $key, wp_unslash( $_GET[ $key ] ) );

		if ( $value !== null ) {
			$overrides[ $key ] = $value;
		}
	}

	if ( isset( $_GET['interval'] ) ) {
		$interval = conboard_clamp_display_int( wp_unslash( $_GET['interval'] ), 3, 300 );
		if ( $interval !== null ) $overrides['interval'] = $interval;
	}

	if ( isset( $_GET['refresh'] ) ) {
		$refresh = conboard_clamp_display_int( wp_unslash( $_GET['refresh'] ), 15, 3600 );
		if ( $refresh !== null ) $overrides['refresh'] = $refresh;
	}

	foreach ( [ 'clock', 'qr' ] as $flag ) {
		if ( isset( $_GET[ $flag ] ) ) {
			$value = conboard_parse_display_bool( wp_unslash( $_GET[ $flag ] ) );
			if ( $value !== null ) $overrides[ $flag ] = $value;
		}
	}

	return $overrides;
}

// URL of the normal (non-screen) version of the current page; the QR code points here
function conboard_get_screen_standard_url(): string {

	if ( is_singular() ) {
		return (string) get_permalink( get_queried_object_id() );
	}

	if ( is_post_type_archive() ) {
		$link = get_post_type_archive_link( get_query_var( 'post_type' ) );
		if ( $link ) {
			return $link;
		}
	}

	return home_url( '/' );
}

/**
 * The display context for this request. When it is not a screen request, only
 * [ 'is_display' => false ] is returned.
 */
function conboard_get_display_context(): array {

	static $cached = null;

	if ( $cached !== null ) {
		return $cached;
	}

	if ( ! conboard_is_display() ) {
		return [ 'is_display' => false ];
	}

	$context = [
		'is_display'	=> true,
		'orientation'	=> 'landscape',
		'rotate'		=> '0',
		'flip'			=> 'none',
		'interval'		=> 10,
		'refresh'		=> 60,
		'clock'			=> true,
		'qr'			=> true,
		'source'		=> 'screen',
		'post_id'		=> 0,
	];

	if ( did_action( 'wp' ) ) {

		$context['post_id'] = is_singular() ? get_queried_object_id() : 0;

		if ( is_singular( 'display_board' ) ) {
			$context['source'] = 'board';
			$context = array_merge( $context, conboard_context_from_display_board( $context['post_id'] ) );
		}
	}

	$context = array_merge( $context, conboard_context_from_url() );

	$context['standard_url'] = did_action( 'wp' ) ? conboard_get_screen_standard_url() : home_url( '/' );

	// Only remember the result once the main query is known
	if ( did_action( 'wp' ) ) {
		$cached = $context;
	}

	return $context;
}
