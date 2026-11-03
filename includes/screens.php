<?php
// includes/screens.php
//
// The screen registry: physical displays at the venue. Settings live in one post meta array
// (_conboard_screen) on a venue_screen post. A screen's own address (/screen/<name>/) acts as a
// proxy: it shows whatever Display Board is assigned to it right now, so the device never has to
// be touched again after setup.

if ( ! defined( 'ABSPATH' ) ) exit;

// New post type: make sure its address works without a manual "Save Permalinks"
add_action( 'init', function () {
	if ( get_option( 'conboard_screens_rewrite' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'conboard_screens_rewrite', '1' );
	}
}, 99 );

// What kind of player drives the screen (for reference when planning)
function conboard_screen_unit_types(): array {
	return [
		''				=> '— Not set —',
		'tv_browser'	=> 'TV built-in player / browser',
		'roku'			=> 'Roku',
		'chromecast'	=> 'Chromecast / Google TV',
		'fire_tv'		=> 'Fire TV Stick',
		'usb'			=> 'USB stick (exported images)',
		'computer'		=> 'Laptop or computer',
		'pi'			=> 'Raspberry Pi / mini PC',
		'player'		=> 'Signage player (BrightSign, Yodeck, ...)',
		'tablet'		=> 'Tablet',
		'other'			=> 'Other',
	];
}

function conboard_screen_resolution_presets(): array {
	return [ '1920x1080', '3840x2160', '1366x768', '1280x720', '2560x1440', '1080x1920' ];
}

function conboard_screen_defaults(): array {
	return [
		'location'		=> 0,
		'resolution'	=> '',
		'unit'			=> '',
		'board'			=> 0,
		'orientation'	=> '',	// '' = same as the board
		'rotate'		=> '',
		'flip'			=> '',
		'clock'			=> '',	// '' = same as the board, else yes / no
		'qr'			=> '',
		'notes'			=> '',
	];
}

function conboard_get_screen_settings( int $screen_id ): array {

	$saved = get_post_meta( $screen_id, '_conboard_screen', true );

	return array_merge( conboard_screen_defaults(), is_array( $saved ) ? $saved : [] );
}

function conboard_sanitize_screen_settings( array $raw ): array {

	$out      = conboard_screen_defaults();
	$location = (int) ( $raw['location'] ?? 0 );
	$board    = (int) ( $raw['board'] ?? 0 );

	$out['location'] = ( $location && get_post_type( $location ) === 'location' ) ? $location : 0;
	$out['board']    = ( $board && get_post_type( $board ) === 'display_board' ) ? $board : 0;

	if ( preg_match( '/^(\d{3,5})\s*(?:x|×)\s*(\d{3,5})$/iu', trim( (string) ( $raw['resolution'] ?? '' ) ), $m ) ) {
		$out['resolution'] = $m[1] . 'x' . $m[2];
	}

	$unit        = (string) ( $raw['unit'] ?? '' );
	$out['unit'] = isset( conboard_screen_unit_types()[ $unit ] ) ? $unit : '';

	foreach ( [ 'orientation', 'rotate', 'flip' ] as $key ) {
		$out[ $key ] = conboard_normalize_display_param( $key, $raw[ $key ] ?? null ) ?? '';
	}

	foreach ( [ 'clock', 'qr' ] as $key ) {
		$out[ $key ] = in_array( $raw[ $key ] ?? '', [ 'yes', 'no' ], true ) ? $raw[ $key ] : '';
	}

	$out['notes'] = sanitize_textarea_field( (string) ( $raw['notes'] ?? '' ) );

	return $out;
}

// The board a screen currently shows (0 when none, or when it was deleted or unpublished)
function conboard_get_screen_board_id( int $screen_id ): int {

	$board_id = (int) conboard_get_screen_settings( $screen_id )['board'];

	return ( $board_id && get_post_status( $board_id ) === 'publish' && get_post_type( $board_id ) === 'display_board' ) ? $board_id : 0;
}

function conboard_assign_screen_board( int $screen_id, int $board_id ): void {

	$settings          = conboard_get_screen_settings( $screen_id );
	$settings['board'] = ( $board_id && get_post_type( $board_id ) === 'display_board' ) ? $board_id : 0;

	update_post_meta( $screen_id, '_conboard_screen', $settings );
}

// Display context for a screen: the assigned board's settings, then the screen's own overrides
function conboard_context_from_screen( int $screen_id ): array {

	$settings = conboard_get_screen_settings( $screen_id );
	$board_id = conboard_get_screen_board_id( $screen_id );
	$context  = $board_id ? conboard_context_from_display_board( $board_id ) : [];

	foreach ( [ 'orientation', 'rotate', 'flip' ] as $key ) {
		if ( $settings[ $key ] !== '' ) {
			$context[ $key ] = $settings[ $key ];
		}
	}

	foreach ( [ 'clock', 'qr' ] as $key ) {
		if ( $settings[ $key ] !== '' ) {
			$context[ $key ] = $settings[ $key ] === 'yes';
		}
	}

	return $context;
}

// What a screen's address shows: its board, or a friendly note when none is assigned
function conboard_screen_device_page( WP_Post $screen ): string {

	$board_id = conboard_get_screen_board_id( $screen->ID );
	$board    = $board_id ? get_post( $board_id ) : null;

	if ( $board ) {
		return conboard_screen_board( $board );
	}

	return conboard_screen_empty( 'Nothing is assigned to this screen yet.' );
}

// Screen cards for the admin: one array per screen
function conboard_get_screen_cards(): array {

	$cards = [];

	foreach ( get_posts([ 'post_type' => 'venue_screen', 'post_status' => [ 'publish', 'draft', 'private' ], 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ]) as $post ) {

		$s        = conboard_get_screen_settings( $post->ID );
		$location = $s['location'] ? get_the_title( $s['location'] ) : '';
		$board_id = conboard_get_screen_board_id( $post->ID );

		$cards[] = [
			'id'		=> $post->ID,
			'title'		=> get_the_title( $post ) ?: '(no name)',
			'edit'		=> get_edit_post_link( $post->ID, 'raw' ),
			'address'	=> (string) get_permalink( $post ),
			'photo'		=> (string) get_the_post_thumbnail_url( $post->ID, 'medium' ),
			'location'	=> $location,
			'resolution'=> $s['resolution'],
			'rotate'	=> $s['rotate'],
			'orientation'=> $s['orientation'],
			'unit'		=> conboard_screen_unit_types()[ $s['unit'] ] ?? '',
			'board'		=> $board_id,
			'board_title'=> $board_id ? get_the_title( $board_id ) : '',
		];
	}

	return $cards;
}


// Assign (or clear) a screen's board. Registered here, not in the admin files, because
// REST requests are not admin requests.
add_action( 'rest_api_init', function () {

	register_rest_route( 'conboard/v1', '/screens/(?P<id>\d+)/board', [
		'methods'             => 'POST',
		'permission_callback' => fn( WP_REST_Request $r ) => current_user_can( 'edit_post', (int) $r['id'] ),
		'callback'            => function ( WP_REST_Request $r ) {

			$id    = (int) $r['id'];
			$board = (int) ( $r->get_json_params()['board'] ?? 0 );

			if ( get_post_type( $id ) !== 'venue_screen' ) {
				return new WP_Error( 'conboard_not_screen', 'Not a screen.', [ 'status' => 404 ] );
			}

			if ( $board && get_post_type( $board ) !== 'display_board' ) {
				return new WP_Error( 'conboard_not_board', 'Not a Display Board.', [ 'status' => 400 ] );
			}

			conboard_assign_screen_board( $id, $board );

			return rest_ensure_response([ 'screen' => $id, 'board' => $board ? $board : 0, 'board_title' => $board ? get_the_title( $board ) : '' ]);
		},
	]);
});
