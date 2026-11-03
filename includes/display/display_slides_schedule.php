<?php
// includes/display/display_slides_schedule.php
//
// Live schedule slide types for Display Boards: Now / Next, Daily Schedule, Room Schedule,
// Coming Up and Schedule Grid. They all use conboard_schedule_query() (includes/schedule.php),
// and the session cards they print keep their own state, progress and countdown up to date in
// the browser (assets/js/signage.js), so a slide stays correct between refreshes.

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

// Location choices for the editor: ID => title
function conboard_get_location_choices(): array {

	$out = [];

	foreach ( get_posts([ 'post_type' => 'location', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ]) as $location ) {
		$out[ $location->ID ] = get_the_title( $location );
	}

	return $out;
}

// Track (Session Type) choices for the editor: slug => name
function conboard_get_track_choices(): array {

	$terms = get_terms([ 'taxonomy' => 'session_type', 'hide_empty' => false ]);
	$out   = [];

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			$out[ $term->slug ] = $term->name;
		}
	}

	return $out;
}

// A comma separated setting as a list
function conboard_setting_list( array $settings, string $key ): array {
	$value = (string) ( $settings[ $key ] ?? '' );
	return $value === '' ? [] : array_values( array_filter( explode( ',', $value ), 'strlen' ) );
}

function conboard_setting_flag( array $settings, string $key, bool $default = true ): bool {
	return isset( $settings[ $key ] ) && $settings[ $key ] !== '' ? $settings[ $key ] === '1' : $default;
}

// Settings shared by the schedule slides, turned into conboard_schedule_query() arguments
function conboard_schedule_args_from_settings( array $s, array $extra = [] ): array {

	return array_merge([
		'date'		=> conboard_schedule_resolve_date( $s['date'] ?? '' ),
		'locations'	=> conboard_setting_list( $s, 'locations' ),
		'tracks'	=> conboard_setting_list( $s, 'tracks' ),
		'completed'	=> in_array( $s['completed'] ?? '', [ 'show', 'dim', 'hide' ], true ) ? $s['completed'] : 'hide',
	], $extra );
}

// Editor fields reused by several slide types
function conboard_schedule_field( string $name ): array {

	$fields = [
		'locations'			=> [ 'label' => 'Locations', 'type' => 'multiselect', 'choices' => conboard_get_location_choices() ],
		'tracks'			=> [ 'label' => 'Tracks (session types)', 'type' => 'multiselect', 'choices' => conboard_get_track_choices() ],
		'date'				=> [ 'label' => 'Date (blank = today; or day2, 2026-11-14, all)', 'type' => 'text' ],
		'completed'			=> [ 'label' => 'Finished sessions', 'type' => 'select', 'choices' => [ 'hide' => 'Hide', 'dim' => 'Dim', 'show' => 'Show' ] ],
		'show_presenters'	=> [ 'label' => 'Show presenters', 'type' => 'checkbox', 'default' => '1' ],
		'limit'				=> [ 'label' => 'How many to show', 'type' => 'number', 'min' => 1, 'max' => 20 ],
	];

	return $fields[ $name ];
}

function conboard_schedule_card_options( array $s, array $extra = [] ): array {
	return array_merge([ 'show_presenters' => conboard_setting_flag( $s, 'show_presenters', true ) ], $extra );
}

function conboard_schedule_heading( string $text ): string {
	return '<h2 class="cb-page-heading">' . esc_html( $text ) . '</h2>';
}


// ---------------------------------------------------------------------------
// Now / Next
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'now_next', [
	'label'		=> 'Now / Next',
	'fields'	=> fn() => [
		'locations'			=> conboard_schedule_field( 'locations' ),
		'limit'				=> [ 'label' => 'Upcoming per location', 'type' => 'number', 'min' => 1, 'max' => 5 ],
		'show_presenters'	=> conboard_schedule_field( 'show_presenters' ),
	],
	'render'	=> function ( array $s ): string {

		$next_count = max( 1, min( 5, (int) ( $s['limit'] ?? 2 ) ?: 2 ) );
		$groups     = [];

		$locations = conboard_normalize_ids( conboard_setting_list( $s, 'locations' ) );

		if ( $locations ) {
			foreach ( $locations as $location_id ) {
				$groups[ $location_id ] = conboard_schedule_query([ 'locations' => [ $location_id ], 'completed' => 'hide', 'cancelled' => 'hide' ]);
			}
		} else {
			foreach ( conboard_schedule_query([ 'completed' => 'hide', 'cancelled' => 'hide' ]) as $row ) {
				$groups[ $row['location_id'] ][] = $row;
			}
		}

		$blocks = [];

		foreach ( $groups as $location_id => $rows ) {

			$current  = array_values( array_filter( $rows, fn( $r ) => $r['state'] === 'current' ) );
			$upcoming = array_slice( array_values( array_filter( $rows, fn( $r ) => in_array( $r['state'], [ 'soon', 'upcoming' ], true ) ) ), 0, $next_count );

			if ( ! $current && ! $upcoming ) {
				continue;
			}

			$options = conboard_schedule_card_options( $s, [ 'hide_location' => $location_id, 'compact' => true ] );

			$html  = '<section class="cb-nn"><h2 class="cb-nn-room">' . esc_html( $location_id ? get_the_title( $location_id ) : 'Elsewhere' ) . '</h2>';
			$html .= $current ? '<div class="cb-label">Now</div>' . implode( '', array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), $current ) ) : '';
			$html .= $upcoming ? '<div class="cb-label">' . ( $current ? 'Next' : 'Up next' ) . '</div>' . implode( '', array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), $upcoming ) ) : '';
			$blocks[] = $html . '</section>';
		}

		if ( ! $blocks ) {
			return '';
		}

		$blocks = array_slice( $blocks, 0, 6 );

		return conboard_schedule_heading( 'Now & Next' ) . conboard_schedule_list_html( $blocks, 'hide', 'cb-nownext cols-' . min( 3, count( $blocks ) ) );
	},
]);


// ---------------------------------------------------------------------------
// Daily Schedule
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'daily_schedule', [
	'label'		=> 'Daily Schedule',
	'fields'	=> fn() => [
		'date'				=> conboard_schedule_field( 'date' ),
		'locations'			=> conboard_schedule_field( 'locations' ),
		'tracks'			=> conboard_schedule_field( 'tracks' ),
		'completed'			=> conboard_schedule_field( 'completed' ),
		'limit'				=> [ 'label' => 'Sessions per screen', 'type' => 'number', 'min' => 1, 'max' => 12 ],
		'show_presenters'	=> conboard_schedule_field( 'show_presenters' ),
	],
	'render'	=> function ( array $s ): string {

		$per_page = max( 1, min( 12, (int) ( $s['limit'] ?? 6 ) ?: 6 ) );
		$args     = conboard_schedule_args_from_settings( $s );
		$rows     = conboard_schedule_query( $args );

		if ( ! $rows ) {
			return '';
		}

		// More sessions than fit: start from what is happening now (keeping one finished session for context)
		if ( count( $rows ) > $per_page ) {
			$first = 0;
			foreach ( $rows as $i => $row ) {
				if ( $row['state'] !== 'past' ) { $first = $i; break; }
			}
			$first = max( 0, min( $first - ( $args['completed'] === 'hide' ? 0 : 1 ), count( $rows ) - $per_page ) );
			$rows  = array_slice( $rows, $first, $per_page );
		}

		$options = conboard_schedule_card_options( $s, [ 'compact' => true ] );
		$items   = array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), $rows );
		$date    = $args['date'] === 'all' ? 'Schedule' : conboard_format_event_day( $args['date'] === 'today' ? conboard_now() : $args['date'] );

		return conboard_schedule_heading( $date ) . conboard_schedule_list_html( $items, $args['completed'], 'cb-daily' );
	},
]);


// ---------------------------------------------------------------------------
// Room Schedule: one column per room, the rest of the day
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'room_schedule', [
	'label'		=> 'Room Schedule',
	'fields'	=> fn() => [
		'locations'			=> conboard_schedule_field( 'locations' ),
		'date'				=> conboard_schedule_field( 'date' ),
		'completed'			=> conboard_schedule_field( 'completed' ),
		'limit'				=> [ 'label' => 'Sessions per room', 'type' => 'number', 'min' => 1, 'max' => 8 ],
		'show_presenters'	=> conboard_schedule_field( 'show_presenters' ),
	],
	'render'	=> function ( array $s ): string {

		$per_room = max( 1, min( 8, (int) ( $s['limit'] ?? 4 ) ?: 4 ) );
		$args     = conboard_schedule_args_from_settings( $s );
		$columns  = [];

		$locations = conboard_normalize_ids( $args['locations'] );

		if ( $locations ) {
			foreach ( $locations as $location_id ) {
				$columns[ $location_id ] = conboard_schedule_query( array_merge( $args, [ 'locations' => [ $location_id ] ] ) );
			}
		} else {
			foreach ( conboard_schedule_query( $args ) as $row ) {
				$columns[ $row['location_id'] ][] = $row;
			}
		}

		$blocks  = [];
		$options = conboard_schedule_card_options( $s, [ 'compact' => true, 'hide_location' => -1 ] );

		foreach ( array_slice( $columns, 0, 4, true ) as $location_id => $rows ) {
			if ( ! $rows ) {
				continue;
			}

			$options['hide_location'] = $location_id;
			$items = array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), array_slice( $rows, 0, $per_room ) );

			$blocks[] = '<section class="cb-nn"><h2 class="cb-nn-room">' . esc_html( $location_id ? get_the_title( $location_id ) : 'Elsewhere' ) . '</h2>' . implode( '', $items ) . '</section>';
		}

		if ( ! $blocks ) {
			return '';
		}

		return conboard_schedule_heading( 'Room Schedule' ) . conboard_schedule_list_html( $blocks, $args['completed'], 'cb-nownext cols-' . min( 3, count( $blocks ) ) );
	},
]);


// ---------------------------------------------------------------------------
// Coming Up: starting within a time window
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'coming_up', [
	'label'		=> 'Coming Up',
	'fields'	=> fn() => [
		'window'			=> [ 'label' => 'Starting within', 'type' => 'select', 'choices' => [ '30' => '30 minutes', '60' => '1 hour', '120' => '2 hours', '180' => '3 hours' ], 'default' => '60' ],
		'locations'			=> conboard_schedule_field( 'locations' ),
		'tracks'			=> conboard_schedule_field( 'tracks' ),
		'limit'				=> conboard_schedule_field( 'limit' ),
		'show_presenters'	=> conboard_schedule_field( 'show_presenters' ),
	],
	'render'	=> function ( array $s ): string {

		$window = max( 5, (int) ( $s['window'] ?? 60 ) ?: 60 );
		$limit  = max( 1, min( 20, (int) ( $s['limit'] ?? 6 ) ?: 6 ) );

		$rows = conboard_schedule_query( conboard_schedule_args_from_settings( $s, [
			'date'		=> 'all',
			'window'	=> $window,
			'cancelled'	=> 'hide',
			'limit'		=> $limit,
		] ) );

		if ( ! $rows ) {
			return '';
		}

		$options = conboard_schedule_card_options( $s, [ 'compact' => true ] );
		$items   = array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), $rows );
		$label   = $window >= 60 && $window % 60 === 0 ? ( $window / 60 ) . ' hour' . ( $window > 60 ? 's' : '' ) : $window . ' minutes';

		return conboard_schedule_heading( 'Coming up in the next ' . $label ) . conboard_schedule_list_html( $items, 'hide', 'cb-daily' );
	},
]);


// ---------------------------------------------------------------------------
// Schedule Grid: rooms across, time down
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'schedule_grid', [
	'label'		=> 'Schedule Grid',
	'fields'	=> fn() => [
		'date'		=> conboard_schedule_field( 'date' ),
		'locations'	=> conboard_schedule_field( 'locations' ),
		'tracks'	=> conboard_schedule_field( 'tracks' ),
	],
	'render'	=> function ( array $s ): string {

		$args = conboard_schedule_args_from_settings( $s, [ 'completed' => 'show' ] );
		$rows = conboard_schedule_query( $args );

		if ( ! $rows ) {
			return '';
		}

		// Columns: the chosen locations, or every location that has sessions (at most 8)
		$column_ids = conboard_normalize_ids( $args['locations'] );

		if ( ! $column_ids ) {
			$names = [];
			foreach ( $rows as $row ) {
				$names[ $row['location_id'] ] = $row['location'];
			}
			asort( $names );
			$column_ids = array_keys( $names );
		}

		$column_ids = array_slice( $column_ids, 0, 8 );
		$column_of  = array_flip( $column_ids );

		// A selected location also shows sessions in rooms nested inside it
		foreach ( $rows as $i => $row ) {
			if ( isset( $column_of[ $row['location_id'] ] ) ) {
				continue;
			}
			foreach ( $column_ids as $column_id ) {
				if ( in_array( $row['location_id'], conboard_get_descendant_location_ids( $column_id ), true ) ) {
					$rows[ $i ]['location_id'] = $column_id;
					break;
				}
			}
		}

		$rows = array_values( array_filter( $rows, fn( $r ) => isset( $column_of[ $r['location_id'] ] ) ) );

		if ( ! $rows ) {
			return '';
		}

		// Time axis: whole hours, 15-minute rows
		$min    = min( array_column( $rows, 'start' ) );
		$max    = max( array_column( $rows, 'end' ) );
		$offset = wp_timezone()->getOffset( new DateTime( '@' . $min ) );
		$top    = (int) ( floor( ( $min + $offset ) / 3600 ) * 3600 - $offset );
		$bottom = (int) ( ceil( ( $max + $offset ) / 3600 ) * 3600 - $offset );
		$units  = max( 4, (int) ( ( $bottom - $top ) / 900 ) );

		$html  = '<div class="cb-grid" style="grid-template-columns:120px repeat(' . count( $column_ids ) . ',minmax(0,1fr));grid-template-rows:auto repeat(' . $units . ',minmax(0,1fr));">';
		$html .= '<div class="cb-grid-corner" style="grid-row:1;grid-column:1"></div>';

		foreach ( $column_ids as $i => $column_id ) {
			$html .= '<div class="cb-grid-head" style="grid-row:1;grid-column:' . ( $i + 2 ) . '">' . esc_html( $column_id ? get_the_title( $column_id ) : 'Elsewhere' ) . '</div>';
		}

		for ( $t = $top; $t < $bottom; $t += 3600 ) {
			$row_start = (int) ( ( $t - $top ) / 900 ) + 2;
			$html .= '<div class="cb-grid-hour" style="grid-row:' . $row_start . ' / span 4;grid-column:1">' . esc_html( wp_date( 'g A', $t ) ) . '</div>';
			$html .= '<div class="cb-grid-line" style="grid-row:' . $row_start . ';grid-column:1 / -1"></div>';
		}

		foreach ( $rows as $row ) {
			$from = max( 0, (int) floor( ( $row['start'] - $top ) / 900 ) ) + 2;
			$span = max( 2, (int) ceil( ( $row['end'] - $row['start'] ) / 900 ) );
			$col  = $column_of[ $row['location_id'] ] + 2;

			$html .= '<div class="cb-grid-cell" style="grid-row:' . $from . ' / span ' . $span . ';grid-column:' . $col . '">'
				. conboard_schedule_item_html( $row, [ 'show_presenters' => false, 'hide_location' => $row['location_id'], 'compact' => true ] )
				. '</div>';
		}

		$html .= '</div>';

		return conboard_schedule_heading( $args['date'] === 'all' ? 'Schedule' : conboard_format_event_day( $args['date'] === 'today' ? conboard_now() : $args['date'] ) ) . '<div class="cb-sched" data-completed="dim">' . $html . '</div>';
	},
]);
