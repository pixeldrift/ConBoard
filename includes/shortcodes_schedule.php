<?php
// includes/shortcodes_schedule.php
//
// [conboard_schedule] - the live schedule on any page. Uses the same schedule layer as the
// screens, so sessions highlight when they are in progress or starting soon and show a progress bar.
//
//   date="all|today|2026-01-24|day2"   default all
//   group="day|room|none"              headings by event day or by location (default: day for a
//                                      multi-day list, none for a single date)
//   location="12,15"                   only these locations (and the rooms inside them)
//   track="workshops,keynotes"         only these Session Types (slugs)
//   completed="show|dim|hide"          finished sessions (default dim)    cancelled="show|hide"
//   limit="10"   presenters="yes|no"   links="yes|no" (title links to the session page)
//   live="yes|no"                      keep states and countdowns updating in the browser

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_register_script( 'conboard-schedule-live', CONBOARD_PLUGIN_URL . 'assets/js/schedule_live.js', [], '0.1.0', true );
} );

add_shortcode( 'conboard_schedule', function ( $atts ) {

	$atts = shortcode_atts([
		'date'			=> 'all',
		'group'			=> '',
		'location'		=> '',
		'track'			=> '',
		'completed'		=> 'dim',
		'cancelled'		=> 'show',
		'limit'			=> 0,
		'presenters'	=> 'yes',
		'links'			=> 'yes',
		'live'			=> 'yes',
	], $atts, 'conboard_schedule' );

	conboard_enqueue_frontend();

	$completed = in_array( $atts['completed'], [ 'show', 'dim', 'hide' ], true ) ? $atts['completed'] : 'dim';
	$single    = $atts['date'] !== 'all';
	$group     = in_array( $atts['group'], [ 'day', 'room', 'none' ], true ) ? $atts['group'] : ( $single ? 'none' : 'day' );

	$rows = conboard_schedule_query([
		'date'		=> $atts['date'],
		'locations'	=> array_filter( array_map( 'intval', explode( ',', (string) $atts['location'] ) ) ),
		'tracks'	=> array_filter( array_map( 'trim', explode( ',', (string) $atts['track'] ) ) ),
		'completed'	=> $completed === 'hide' ? 'hide' : 'show',
		'cancelled'	=> $atts['cancelled'] === 'hide' ? 'hide' : 'show',
		'limit'		=> (int) $atts['limit'],
	]);

	if ( ! $rows ) {
		return '<div class="conboard-schedule conboard-schedule-empty"><p>No sessions to show.</p></div>';
	}

	$options = [
		'show_presenters'	=> conboard_is_truthy( $atts['presenters'] ),
		'link'				=> conboard_is_truthy( $atts['links'] ),
		'with_day'			=> $group === 'none',
	];

	$groups = [];
	foreach ( $rows as $row ) {
		if ( $group === 'day' ) {
			$key = wp_date( 'Y-m-d', $row['start'] );
		} elseif ( $group === 'room' ) {
			$key = $row['location'] !== '' ? $row['location'] : 'No location';
		} else {
			$key = '';
		}
		$groups[ $key ][] = $row;
	}

	if ( $group === 'room' ) ksort( $groups );

	$live = conboard_is_truthy( $atts['live'] );
	if ( $live ) wp_enqueue_script( 'conboard-schedule-live' );

	$html = '<div class="conboard-schedule"' . ( $live ? ' data-now="' . (int) conboard_now() . '"' : '' ) . '>';

	foreach ( $groups as $key => $list ) {

		if ( $group !== 'none' ) {
			$heading = $group === 'day' ? conboard_format_event_day( $key ) : $key;
			$html   .= '<h3 class="conboard-schedule-heading">' . esc_html( $heading ) . '</h3>';
		}

		// Under a room heading the room is not repeated on every card
		$items = array_map( fn( $r ) => conboard_schedule_item_html( $r, $options + [ 'hide_location' => $group === 'room' ? (int) $r['location_id'] : 0 ] ), $list );
		$html .= conboard_schedule_list_html( $items, $completed );
	}

	return $html . '</div>';
} );
