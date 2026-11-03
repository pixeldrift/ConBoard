<?php
// includes/schedule.php
//
// The schedule layer: one place that finds sessions, works out where each one stands in time,
// and builds the "live" session markup used by Screen Mode and Display Boards.
//
// Times are stored on sessions as site-local text (Y-m-d H:i:s). Everything here converts them to
// real timestamps (seconds since 1970), so the browser can keep states, progress and countdowns
// up to date by itself between page refreshes (see assets/js/signage.js).

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Time
// ---------------------------------------------------------------------------

// The current moment as a real timestamp. Filterable ('conboard_now') so previews and tests can pretend it is another time.
function conboard_now(): int {
	return (int) apply_filters( 'conboard_now', time() );
}

// Site-local "Y-m-d H:i:s" text to a real timestamp (0 when empty or invalid)
function conboard_local_to_timestamp( $text ): int {

	$text = trim( (string) $text );

	if ( $text === '' ) {
		return 0;
	}

	try {
		return (int) ( new DateTimeImmutable( $text, wp_timezone() ) )->getTimestamp();
	} catch ( Exception $e ) {
		return 0;
	}
}

// How long a session is assumed to last when it has no usable end time (minutes)
function conboard_default_session_minutes(): int {
	return max( 1, (int) apply_filters( 'conboard_default_session_minutes', 60 ) );
}

// Where a session stands at $now: unscheduled | upcoming | soon | current | past
function conboard_schedule_state( int $start, int $end, int $now, int $soon_seconds = 900 ): string {

	if ( $start <= 0 ) {
		return 'unscheduled';
	}

	if ( $now >= $end ) {
		return 'past';
	}

	if ( $now >= $start ) {
		return 'current';
	}

	return ( $start - $now ) <= $soon_seconds ? 'soon' : 'upcoming';
}


// A date setting made usable: 'today' | 'all' | 'Y-m-d'. Accepts 'day2' (event day 2, see Settings).
function conboard_schedule_resolve_date( $date ): string {

	$date = strtolower( trim( (string) $date ) );

	if ( $date === '' ) {
		return 'today';
	}

	if ( preg_match( '/^day\s*(\d+)$/', $date, $m ) ) {
		$resolved = function_exists( 'conboard_get_event_day_date' ) ? conboard_get_event_day_date( (int) $m[1] ) : '';
		return $resolved !== '' ? $resolved : 'today';
	}

	return ( $date === 'all' || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) ? $date : 'today';
}


// ---------------------------------------------------------------------------
// Finding sessions
// ---------------------------------------------------------------------------

// One session as a plain row (see conboard_schedule_query for the fields)
function conboard_schedule_row( $session, int $now = 0, int $soon_seconds = 900 ): array {

	$session = get_post( $session );
	$now     = $now ?: time();

	$start = conboard_local_to_timestamp( get_post_meta( $session->ID, 'start_time', true ) );
	$end   = conboard_local_to_timestamp( get_post_meta( $session->ID, 'end_time', true ) );

	if ( $start > 0 && $end <= $start ) {
		$end = $start + conboard_default_session_minutes() * 60;
	}

	$location_id = (int) get_post_meta( $session->ID, 'location', true );
	$presenters  = [];

	foreach ( conboard_get_session_presenters( $session->ID ) as $entry ) {
		$presenters[] = get_the_title( $entry['presenter_id'] );
	}

	$tracks = wp_get_post_terms( $session->ID, 'session_type', [ 'fields' => 'names' ] );

	return [
		'id'			=> (int) $session->ID,
		'title'			=> get_the_title( $session ),
		'url'			=> (string) get_permalink( $session ),
		'start'			=> $start,
		'end'			=> $end,
		'location_id'	=> $location_id,
		'location'		=> $location_id ? get_the_title( $location_id ) : '',
		'tracks'		=> is_array( $tracks ) ? $tracks : [],
		'presenters'	=> array_values( array_unique( $presenters ) ),
		'cancelled'		=> (bool) get_post_meta( $session->ID, 'cancelled', true ),
		'state'			=> conboard_schedule_state( $start, $end, $now, $soon_seconds ),
	];
}

/**
 * Find sessions, with their state worked out at a given moment.
 *
 * @param array $args {
 *     @type string $date      'today' (default), 'all', or a date like 2026-11-14 (site time zone)
 *     @type int[]  $locations Only these locations (their nested locations are included). Empty = all.
 *     @type string[] $tracks  Only sessions with one of these Session Type slugs. Empty = all.
 *     @type int    $window    Minutes from now: only sessions that start in that window (0 = off)
 *     @type string $completed 'show' (default) | 'dim' | 'hide'. 'hide' drops finished sessions.
 *     @type string $cancelled 'show' (default) | 'hide'
 *     @type int    $limit     Maximum rows (0 = all)
 *     @type int    $now       Pretend it is this moment (for previews and tests)
 *     @type int    $soon      Minutes before the start that count as "starting soon" (default 15)
 * }
 * @return array[] Rows sorted by start time: id, title, url, start, end, location_id, location,
 *                 tracks, presenters, cancelled, state. Sessions without a start time are left out.
 */
function conboard_schedule_query( array $args = [] ): array {

	$a = array_merge([
		'date'		=> 'today',
		'locations'	=> [],
		'tracks'	=> [],
		'window'	=> 0,
		'completed'	=> 'show',
		'cancelled'	=> 'show',
		'limit'		=> 0,
		'now'		=> 0,
		'soon'		=> 15,
	], $args );

	$now  = $a['now'] ? (int) $a['now'] : conboard_now();

	$a['date'] = conboard_schedule_resolve_date( $a['date'] );
	$soon = max( 0, (int) $a['soon'] ) * 60;

	$query = [
		'post_type'			=> 'session',
		'post_status'		=> 'publish',
		'posts_per_page'	=> -1,
	];

	$location_ids = [];
	foreach ( conboard_normalize_ids( $a['locations'] ) as $location_id ) {
		$location_ids[] = $location_id;
		$location_ids   = array_merge( $location_ids, conboard_get_descendant_location_ids( $location_id ) );
	}

	if ( $location_ids ) {
		$query['meta_query'] = [[
			'key'		=> 'location',
			'value'		=> array_map( 'strval', array_unique( $location_ids ) ),
			'compare'	=> 'IN',
		]];
	}

	$tracks = array_values( array_filter( array_map( 'sanitize_title', (array) $a['tracks'] ) ) );
	if ( $tracks ) {
		$query['tax_query'] = [[ 'taxonomy' => 'session_type', 'field' => 'slug', 'terms' => $tracks ]];
	}

	// The day being asked about, in the site's time zone
	$day_start = 0;
	$day_end   = 0;

	if ( $a['date'] !== 'all' ) {
		try {
			$zone = wp_timezone();
			$day  = $a['date'] === 'today' || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['date'] )
				? ( new DateTimeImmutable( '@' . $now ) )->setTimezone( $zone )
				: new DateTimeImmutable( (string) $a['date'], $zone );
			$day       = $day->setTime( 0, 0 );
			$day_start = $day->getTimestamp();
			$day_end   = $day->modify( '+1 day' )->getTimestamp();
		} catch ( Exception $e ) {
			$day_start = $day_end = 0;
		}
	}

	$rows = [];

	foreach ( get_posts( $query ) as $session ) {

		$row = conboard_schedule_row( $session, $now, $soon );

		if ( $row['state'] === 'unscheduled' ) {
			continue;
		}

		if ( $day_end && ! ( $row['start'] < $day_end && $row['end'] > $day_start ) ) {
			continue;
		}

		if ( $a['window'] > 0 && ! ( $row['start'] > $now && $row['start'] <= $now + (int) $a['window'] * 60 ) ) {
			continue;
		}

		if ( $a['completed'] === 'hide' && $row['state'] === 'past' ) {
			continue;
		}

		if ( $a['cancelled'] === 'hide' && $row['cancelled'] ) {
			continue;
		}

		$rows[] = $row;
	}

	usort( $rows, function ( $x, $y ) {
		return [ $x['start'], $x['end'], $x['location'], $x['title'] ] <=> [ $y['start'], $y['end'], $y['location'], $y['title'] ];
	});

	return $a['limit'] > 0 ? array_slice( $rows, 0, (int) $a['limit'] ) : $rows;
}


// ---------------------------------------------------------------------------
// Live markup
// ---------------------------------------------------------------------------

// "9:00 AM – 10:00 AM", with the weekday in front when it is not today
function conboard_schedule_time_label( int $start, int $end, int $now = 0, bool $with_day = true ): string {

	if ( $start <= 0 ) {
		return '';
	}

	$now   = $now ?: conboard_now();
	$label = wp_date( 'g:i A', $start );

	if ( $end > $start ) {
		$label .= ' – ' . wp_date( 'g:i A', $end );
	}

	if ( $with_day && wp_date( 'Y-m-d', $start ) !== wp_date( 'Y-m-d', $now ) ) {
		$label = wp_date( 'l', $start ) . ' ' . $label;
	}

	return $label;
}

/**
 * A session as a card that keeps itself up to date in the browser: its state (upcoming, starting
 * soon, in progress, finished), a progress bar and a countdown change without a page refresh.
 *
 * @param array $options show_presenters (bool), hide_location (int location ID to leave out), compact (bool)
 */
function conboard_schedule_item_html( array $row, array $options = [] ): string {

	$options = array_merge([ 'show_presenters' => true, 'hide_location' => 0, 'compact' => false, 'now' => 0, 'link' => false, 'with_day' => true ], $options );
	$state   = $row['cancelled'] ? 'cancelled' : $row['state'];

	$html  = '<article class="cb-session cb-session-' . esc_attr( $state ) . ( $options['compact'] ? ' is-compact' : '' ) . '"';
	$html .= ' data-start="' . (int) $row['start'] . '" data-end="' . (int) $row['end'] . '" data-state="' . esc_attr( $state ) . '">';

	$html .= '<div class="cb-session-time">' . esc_html( conboard_schedule_time_label( $row['start'], $row['end'], (int) $options['now'], (bool) $options['with_day'] ) );
	$html .= $row['cancelled'] ? ' <span class="cb-badge cb-badge-cancelled">Cancelled</span>' : '';
	$html .= '</div>';

	$title = esc_html( $row['title'] );
	if ( $options['link'] && $row['url'] ) {
		$title = '<a href="' . esc_url( $row['url'] ) . '">' . $title . '</a>';
	}
	$html .= '<h3 class="cb-session-title">' . $title . '</h3>';

	if ( $options['show_presenters'] && $row['presenters'] ) {
		$html .= '<div class="cb-session-presenters">' . esc_html( implode( ', ', $row['presenters'] ) ) . '</div>';
	}

	if ( $row['location'] && (int) $row['location_id'] !== (int) $options['hide_location'] ) {
		$html .= '<div class="cb-session-room">' . esc_html( $row['location'] ) . '</div>';
	}

	if ( ! $row['cancelled'] ) {
		$html .= '<div class="cb-session-status"><span class="cb-countdown"></span></div>';
		$html .= '<div class="cb-progress"><span></span></div>';
	}

	return $html . '</article>';
}

// Wrap a list of items. $completed ('show' | 'dim' | 'hide') says how finished sessions look once
// the browser sees them end.
function conboard_schedule_list_html( array $items, string $completed = 'show', string $class = '' ): string {
	return '<div class="cb-sched ' . esc_attr( $class ) . '" data-completed="' . esc_attr( $completed ) . '">' . implode( '', $items ) . '</div>';
}
