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
	// A registered screen's own address is always a screen
	if ( did_action( 'wp' ) && is_singular( 'venue_screen' ) ) {
		return true;
	}

	return isset( $_GET['display'] ) && conboard_normalize_display_param( 'display', $_GET['display'] ) === 'screen';
}

// The post types that can be shown in Screen Mode
function conboard_screen_post_types(): array {
	return [ 'location', 'session', 'presenter', 'sponsor', 'staff', 'announcement', 'display_board' ];
}

/**
 * Settings saved on a Display Board (see includes/display/display_slides.php).
 */
function conboard_context_from_display_board( int $board_id ): array {

	$board = conboard_get_board_settings( $board_id );

	return [
		'orientation'	=> (string) $board['orientation'],
		'rotate'		=> (string) $board['rotate'],
		'flip'			=> (string) $board['flip'],
		'interval'		=> (int) $board['interval'],
		'refresh'		=> (int) $board['refresh'],
		'clock'			=> (bool) $board['clock'],
		'qr'			=> (bool) $board['qr'],
	];
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

	// A Display Board has no ordinary version; its QR code points to the home page
	if ( is_singular( [ 'display_board', 'venue_screen' ] ) ) {
		return home_url( '/' );
	}

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

		if ( is_singular( 'venue_screen' ) ) {
			$context['source'] = 'device';
			$context = array_merge( $context, conboard_context_from_screen( $context['post_id'] ) );
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
