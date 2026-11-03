<?php
// includes/event_days.php
//
// Event days: the first day of the event, how many days it runs, and how a day is written
// ("Day 1", "Day 1 – Sunday", "Day 1 – Sunday – Jan 24", ...). Set on the ConBoard Settings page.
// Dates are calendar dates in the site's time zone.

if ( ! defined( 'ABSPATH' ) ) exit;

// Ways to write a day, with the pattern used ({n} = day number)
function conboard_get_event_day_formats(): array {
	return [
		'day'				=> [ 'label' => 'Day 1',						'pattern' => 'Day {n}' ],
		'day_weekday'		=> [ 'label' => 'Day 1 – Sunday',				'pattern' => 'Day {n} – l' ],
		'day_weekday_date'	=> [ 'label' => 'Day 1 – Sunday – Jan 24',		'pattern' => 'Day {n} – l – M j' ],
		'weekday'			=> [ 'label' => 'Sunday',						'pattern' => 'l' ],
		'weekday_date'		=> [ 'label' => 'Sunday, January 24',			'pattern' => 'l, F j' ],
	];
}

// The saved settings (start 'Y-m-d' or '', days, format)
function conboard_get_event_days_settings(): array {

	$saved = get_option( 'conboard_event_days' );
	$saved = is_array( $saved ) ? $saved : [];

	return [
		'start'		=> (string) ( $saved['start'] ?? '' ),
		'days'		=> max( 1, min( 30, (int) ( $saved['days'] ?? 1 ) ) ),
		'format'	=> isset( conboard_get_event_day_formats()[ $saved['format'] ?? '' ] ) ? $saved['format'] : 'day_weekday_date',
	];
}

function conboard_sanitize_event_days( $input ): array {

	$input = is_array( $input ) ? $input : [];
	$start = (string) ( $input['start'] ?? '' );

	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $start, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		$start = '';
	}

	return [
		'start'		=> $start,
		'days'		=> max( 1, min( 30, (int) ( $input['days'] ?? 1 ) ) ),
		'format'	=> isset( conboard_get_event_day_formats()[ $input['format'] ?? '' ] ) ? $input['format'] : 'day_weekday_date',
	];
}

// The event's days: [ [ 'number' => 1, 'date' => '2026-01-24' ], ... ] (empty when no start date is set)
function conboard_get_event_days(): array {

	$settings = conboard_get_event_days_settings();

	if ( $settings['start'] === '' ) {
		return [];
	}

	$days = [];

	try {
		$date = new DateTimeImmutable( $settings['start'], wp_timezone() );
	} catch ( Exception $e ) {
		return [];
	}

	for ( $n = 1; $n <= $settings['days']; $n++ ) {
		$days[] = [ 'number' => $n, 'date' => $date->modify( '+' . ( $n - 1 ) . ' day' )->format( 'Y-m-d' ) ];
	}

	return $days;
}

// Which event day a date ('Y-m-d') is: 1, 2, ... or 0 when it is outside the event
function conboard_get_event_day_number( string $date ): int {

	foreach ( conboard_get_event_days() as $day ) {
		if ( $day['date'] === $date ) {
			return $day['number'];
		}
	}

	return 0;
}

// The date ('Y-m-d') of event day N, or '' when there is no such day
function conboard_get_event_day_date( int $number ): string {

	foreach ( conboard_get_event_days() as $day ) {
		if ( $day['number'] === $number ) {
			return $day['date'];
		}
	}

	return '';
}

/**
 * A date written the way the event is set up, e.g. "Day 2 – Monday – Jan 25".
 * Dates outside the event (or with no event days set) are written without the day number.
 *
 * @param int|string $when A timestamp, or a date like 2026-01-24
 */
function conboard_format_event_day( $when, string $format = '' ): string {

	$formats  = conboard_get_event_day_formats();
	$settings = conboard_get_event_days_settings();
	$format   = isset( $formats[ $format ] ) ? $format : $settings['format'];

	$ts = is_numeric( $when ) ? (int) $when : conboard_local_to_timestamp( $when . ' 12:00:00' );

	if ( $ts <= 0 ) {
		return '';
	}

	$number  = conboard_get_event_day_number( wp_date( 'Y-m-d', $ts ) );
	$pattern = $formats[ $format ]['pattern'];

	// Outside the event: drop the "Day N" part
	if ( $number === 0 ) {
		$pattern = trim( preg_replace( '/^Day \{n\}( – )?/', '', $pattern ) );
		$pattern = $pattern !== '' ? $pattern : 'l, F j';
	}

	$out = '';

	// Letters are date codes, except the {n} placeholder
	foreach ( preg_split( '/(\{n\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE ) as $part ) {
		if ( $part === '{n}' ) {
			$out .= $number;
		} elseif ( $part === 'Day ' ) {
			$out .= $part;
		} else {
			$out .= wp_date( $part, $ts );
		}
	}

	return $out;
}
