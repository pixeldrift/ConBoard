<?php
// includes/display/display_screens.php
//
// The content of each Screen Mode page. Everything here is built from live data on every
// request, and the screen refreshes itself, so schedule changes show up automatically.
//
// Layout is designed on a 1920x1080 stage (1080x1920 in portrait); signage.js scales it to fit
// whatever display it is on.

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Building blocks
// ---------------------------------------------------------------------------

/**
 * Wrap pages of content so signage.js can loop through them.
 * The first page is visible straight away; with a single page nothing loops.
 *
 * @param string[] $pages HTML for each page (empty pages are skipped)
 */
function conboard_screen_pages( array $pages, string $class = '' ): string {

	$pages = array_values( array_filter( $pages ) );

	if ( empty( $pages ) ) {
		return '';
	}

	$html = '<div class="cb-pages ' . esc_attr( $class ) . '" data-pages="' . count( $pages ) . '">';

	foreach ( $pages as $index => $page ) {
		$html .= '<section class="cb-page' . ( $index === 0 ? ' is-active' : '' ) . '">' . $page . '</section>';
	}

	return $html . '</div>';
}

// Message shown when there is nothing to display
function conboard_screen_empty( string $message ): string {
	return '<div class="cb-empty">' . esc_html( $message ) . '</div>';
}

// "9:00 AM – 10:00 AM", with the weekday added when it is not today
function conboard_screen_time_range( $start, $end = '' ): string {

	$start_ts = strtotime( (string) $start );

	if ( ! $start_ts ) {
		return '';
	}

	$label = date_i18n( 'g:i A', $start_ts );

	if ( $end && ( $end_ts = strtotime( (string) $end ) ) ) {
		$label .= ' – ' . date_i18n( 'g:i A', $end_ts );
	}

	if ( date( 'Y-m-d', $start_ts ) !== date( 'Y-m-d', current_time( 'timestamp' ) ) ) {
		$label = date_i18n( 'l', $start_ts ) . ' ' . $label;
	}

	return $label;
}

// Names of the people presenting a session, e.g. "Ann Lee, Bo Chen"
function conboard_screen_presenter_names( int $session_id ): string {

	$names = [];

	foreach ( conboard_get_session_presenters( $session_id ) as $entry ) {
		$names[] = get_the_title( $entry['presenter_id'] );
	}

	return implode( ', ', array_unique( $names ) );
}

// A session as a large card that keeps its own state and countdown up to date (see includes/schedule.php)
function conboard_screen_session_card( WP_Post $session, int $page_location_id = 0 ): string {
	return conboard_schedule_item_html( conboard_schedule_row( $session ), [ 'hide_location' => $page_location_id ] );
}

// A photo or logo tile with a name (not clickable: screens have no one to click)
function conboard_screen_tile( string $image_html, string $name, string $sub = '', string $class = '' ): string {

	$html  = '<div class="cb-tile ' . esc_attr( $class ) . '">';
	$html .= $image_html ? '<div class="cb-tile-image">' . $image_html . '</div>' : '';
	$html .= '<div class="cb-tile-name">' . esc_html( $name ) . '</div>';
	$html .= $sub ? '<div class="cb-tile-sub">' . esc_html( $sub ) . '</div>' : '';

	return $html . '</div>';
}

// Several tiles per page, as a grid
function conboard_screen_tile_pages( array $tiles, int $per_page = 8, string $heading = '' ): array {

	$pages = [];

	foreach ( array_chunk( $tiles, max( 1, $per_page ) ) as $chunk ) {
		$pages[] = ( $heading ? '<h2 class="cb-page-heading">' . esc_html( $heading ) . '</h2>' : '' )
			. '<div class="cb-tile-grid">' . implode( '', $chunk ) . '</div>';
	}

	return $pages;
}


// ---------------------------------------------------------------------------
// Title shown in the header
// ---------------------------------------------------------------------------

function conboard_screen_title(): string {

	if ( is_singular() ) {
		return get_the_title( get_queried_object_id() );
	}

	if ( is_post_type_archive( 'session' ) )      return 'Schedule';
	if ( is_post_type_archive( 'announcement' ) ) return 'Announcements';

	return post_type_archive_title( '', false );
}


// ---------------------------------------------------------------------------
// Single pages
// ---------------------------------------------------------------------------

// A location: what is happening now, what is next, and the map
function conboard_screen_location( WP_Post $location ): string {

	// Sessions still to come (or in progress) here, nested rooms included
	$rows     = conboard_schedule_query([ 'date' => 'all', 'locations' => [ $location->ID ], 'completed' => 'hide', 'cancelled' => 'hide' ]);
	$current  = array_values( array_filter( $rows, fn( $r ) => $r['state'] === 'current' ) );
	$upcoming = array_values( array_filter( $rows, fn( $r ) => in_array( $r['state'], [ 'soon', 'upcoming' ], true ) ) );
	$options  = [ 'hide_location' => $location->ID ];

	$main = '';

	if ( $current ) {
		$main .= '<section class="cb-block cb-now"><h2 class="cb-label">Happening now</h2>';
		$main .= conboard_schedule_list_html( array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), array_slice( $current, 0, 3 ) ), 'hide' );
		$main .= '</section>';
	}

	if ( $upcoming ) {
		$main .= '<section class="cb-block cb-next"><h2 class="cb-label">' . ( $current ? 'Next' : 'Up next' ) . '</h2>';
		$main .= conboard_schedule_list_html( array_map( fn( $r ) => conboard_schedule_item_html( $r, $options ), array_slice( $upcoming, 0, $current ? 2 : 3 ) ), 'hide' );
		$main .= '</section>';
	}

	if ( ! $main ) {
		$any  = conboard_schedule_query([ 'date' => 'all', 'locations' => [ $location->ID ] ]);
		$main = conboard_screen_empty( $any ? 'No more sessions here' : 'Nothing scheduled here' );
	}

	$map  = conboard_get_location_map_html( $location->ID );
	$side = $map ? '<div class="cb-col cb-col-side">' . $map . '</div>' : '';

	return '<div class="cb-location' . ( $side ? ' has-side' : '' ) . '"><div class="cb-col cb-col-main">' . $main . '</div>' . $side . '</div>';
}

// A session: when, where, who, and a short description
function conboard_screen_session( WP_Post $session ): string {

	$start    = get_post_meta( $session->ID, 'start_time', true );
	$end      = get_post_meta( $session->ID, 'end_time', true );
	$location = (int) get_post_meta( $session->ID, 'location', true );
	$summary  = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $session->post_content ) ), 60 );

	$html  = '<div class="cb-detail">';
	$html .= '<div class="cb-detail-meta">';
	$html .= $start ? '<div class="cb-session-time">' . esc_html( conboard_screen_time_range( $start, $end ) ) . '</div>' : '';
	$html .= $location ? '<div class="cb-session-room">' . esc_html( get_the_title( $location ) ) . '</div>' : '';
	$html .= '</div>';
	$html .= $summary ? '<p class="cb-summary">' . esc_html( $summary ) . '</p>' : '';

	$people = [];

	foreach ( conboard_get_session_presenters( $session->ID ) as $entry ) {
		$people[] = conboard_screen_tile(
			get_the_post_thumbnail( $entry['presenter_id'], 'medium' ),
			get_the_title( $entry['presenter_id'] ),
			conboard_get_presenter_role_label( $entry['role'] ),
			'cb-tile-person'
		);
	}

	$html .= $people ? '<div class="cb-tile-grid cb-people">' . implode( '', $people ) . '</div>' : '';

	return $html . '</div>';
}

// A presenter: photo, bio and their next sessions
function conboard_screen_presenter( WP_Post $presenter ): string {

	$badge   = conboard_get_presenter_badge( $presenter->ID );
	$line    = conboard_get_presenter_title_line( $presenter->ID );
	$bio     = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $presenter->post_content ) ), 70 );
	$photo   = get_the_post_thumbnail( $presenter->ID, 'large' );
	$now     = current_time( 'timestamp' );
	$sessions = [];

	foreach ( conboard_get_sessions_for_presenter( $presenter->ID ) as $session ) {
		if ( in_array( conboard_get_session_state( $session->ID, $now ), [ 'current', 'upcoming' ], true ) ) {
			$sessions[] = $session;
		}
	}

	$html  = '<div class="cb-detail cb-person">';
	$html .= $photo ? '<div class="cb-person-photo">' . $photo . '</div>' : '';
	$html .= '<div class="cb-person-body">';
	$html .= $badge ? '<div class="cb-badge">' . esc_html( $badge ) . '</div>' : '';
	$html .= $line ? '<div class="cb-person-line">' . esc_html( $line ) . '</div>' : '';
	$html .= $bio ? '<p class="cb-summary">' . esc_html( $bio ) . '</p>' : '';

	if ( $sessions ) {
		$html .= '<h2 class="cb-label">Sessions</h2>';
		foreach ( array_slice( $sessions, 0, 2 ) as $session ) {
			$html .= conboard_screen_session_card( $session );
		}
	}

	return $html . '</div></div>';
}

// A sponsor: the logo, large
function conboard_screen_sponsor( WP_Post $sponsor ): string {

	$logo  = get_the_post_thumbnail( $sponsor->ID, 'large' );
	$level = (string) get_post_meta( $sponsor->ID, 'sponsor_level', true );

	$html  = '<div class="cb-sponsor">';
	$html .= $logo ? '<div class="cb-sponsor-logo">' . $logo . '</div>' : '<div class="cb-sponsor-name">' . esc_html( get_the_title( $sponsor ) ) . '</div>';
	$html .= $level ? '<div class="cb-badge">' . esc_html( conboard_get_sponsor_level_label( $level ) ) . '</div>' : '';

	return $html . '</div>';
}

// A staff member: photo and role
function conboard_screen_staff( WP_Post $member ): string {

	$photo = get_the_post_thumbnail( $member->ID, 'large' );
	$role  = (string) get_post_meta( $member->ID, 'role', true );
	$bio   = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $member->post_content ) ), 60 );

	$html  = '<div class="cb-detail cb-person">';
	$html .= $photo ? '<div class="cb-person-photo">' . $photo . '</div>' : '';
	$html .= '<div class="cb-person-body">';
	$html .= $role ? '<div class="cb-person-line">' . esc_html( $role ) . '</div>' : '';
	$html .= $bio ? '<p class="cb-summary">' . esc_html( $bio ) . '</p>' : '';

	return $html . '</div></div>';
}

// An announcement: big text, urgent ones flagged
function conboard_screen_announcement( WP_Post $announcement ): string {

	$urgent = conboard_announcement_is_urgent( $announcement );

	$html  = '<article class="cb-announcement' . ( $urgent ? ' cb-announcement-urgent' : '' ) . '">';
	$html .= $urgent ? '<div class="cb-badge cb-badge-urgent">Urgent</div>' : '';
	$html .= '<div class="cb-announcement-text">' . wpautop( wp_kses_post( $announcement->post_content ) ) . '</div>';

	return $html . '</article>';
}


// ---------------------------------------------------------------------------
// Archives
// ---------------------------------------------------------------------------

// Schedule: what is on now and next, a few sessions per page
function conboard_screen_sessions_archive(): string {

	$cards = [];

	foreach ( conboard_schedule_query([ 'date' => 'all', 'completed' => 'hide', 'cancelled' => 'hide' ]) as $row ) {
		$cards[] = conboard_schedule_item_html( $row );
	}

	if ( empty( $cards ) ) {
		return conboard_screen_empty( 'No upcoming sessions' );
	}

	$pages = [];

	foreach ( array_chunk( $cards, 4 ) as $chunk ) {
		$pages[] = conboard_schedule_list_html( $chunk, 'hide', 'cb-session-list' );
	}

	return conboard_screen_pages( $pages );
}

// Pages of presenter tiles (also used by Display Board slides)
function conboard_screen_presenter_pages( array $presenters, int $per_page = 8, string $heading = '' ): array {

	$tiles = [];

	foreach ( $presenters as $presenter ) {
		$tiles[] = conboard_screen_tile(
			get_the_post_thumbnail( $presenter->ID, 'medium' ),
			get_the_title( $presenter ),
			conboard_get_presenter_title_line( $presenter->ID ),
			'cb-tile-person'
		);
	}

	return conboard_screen_tile_pages( $tiles, $per_page, $heading );
}

function conboard_screen_presenters_archive(): string {

	$pages = conboard_screen_presenter_pages( conboard_get_presenters() );

	return $pages ? conboard_screen_pages( $pages ) : conboard_screen_empty( 'No presenters yet' );
}

// Pages of sponsor logos: a page per level (split further when a level has many sponsors)
function conboard_screen_sponsor_pages( array $levels = [], int $per_page = 8 ): array {

	$pages = [];

	foreach ( conboard_get_sponsor_groups([ 'levels' => $levels ]) as $group ) {

		$tiles = [];

		foreach ( $group['sponsors'] as $sponsor ) {
			$tiles[] = conboard_screen_tile( get_the_post_thumbnail( $sponsor->ID, 'medium' ), get_the_title( $sponsor ), '', 'cb-tile-logo' );
		}

		$pages = array_merge( $pages, conboard_screen_tile_pages( $tiles, $per_page, $group['plural'] ) );
	}

	return $pages;
}

function conboard_screen_sponsors_archive(): string {

	$pages = conboard_screen_sponsor_pages();

	return $pages ? conboard_screen_pages( $pages, 'cb-sponsor-loop' ) : conboard_screen_empty( 'No sponsors yet' );
}

// Pages of staff tiles
function conboard_screen_staff_pages( array $staff, int $per_page = 8, string $heading = '' ): array {

	$tiles = [];

	foreach ( $staff as $member ) {
		$tiles[] = conboard_screen_tile(
			get_the_post_thumbnail( $member->ID, 'medium' ),
			get_the_title( $member ),
			(string) get_post_meta( $member->ID, 'role', true ),
			'cb-tile-person'
		);
	}

	return conboard_screen_tile_pages( $tiles, $per_page, $heading );
}

function conboard_screen_staff_archive(): string {

	$pages = conboard_screen_staff_pages( conboard_get_staff() );

	return $pages ? conboard_screen_pages( $pages ) : conboard_screen_empty( 'No staff listed yet' );
}

// Announcements: one per page, urgent first. Only current ones that are allowed on screens.
function conboard_screen_announcements_archive(): string {

	$pages = [];

	foreach ( conboard_get_announcements([ 'status' => 'active', 'screens_only' => true ]) as $announcement ) {
		$pages[] = '<h2 class="cb-page-heading">' . esc_html( get_the_title( $announcement ) ) . '</h2>' . conboard_screen_announcement( $announcement );
	}

	return $pages ? conboard_screen_pages( $pages ) : conboard_screen_empty( 'No announcements right now' );
}

// Locations: a simple list to help people find their way
function conboard_screen_locations_archive(): string {

	$tree = conboard_render_location_tree();

	return $tree ? '<div class="cb-locations">' . $tree . '</div>' : conboard_screen_empty( 'No locations yet' );
}


// ---------------------------------------------------------------------------
// Pick the right content for this request
// ---------------------------------------------------------------------------

function conboard_screen_render_content(): string {

	if ( is_singular() ) {

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		switch ( $post->post_type ) {
			case 'location':     return conboard_screen_location( $post );
			case 'session':      return conboard_screen_session( $post );
			case 'presenter':    return conboard_screen_presenter( $post );
			case 'sponsor':      return conboard_screen_sponsor( $post );
			case 'staff':        return conboard_screen_staff( $post );
			case 'announcement': return conboard_screen_announcement( $post );
			case 'display_board': return conboard_screen_board( $post );
		}

		return '';
	}

	$type = get_queried_object();

	if ( $type instanceof WP_Post_Type ) {
		switch ( $type->name ) {
			case 'session':      return conboard_screen_sessions_archive();
			case 'presenter':    return conboard_screen_presenters_archive();
			case 'sponsor':      return conboard_screen_sponsors_archive();
			case 'staff':        return conboard_screen_staff_archive();
			case 'announcement': return conboard_screen_announcements_archive();
			case 'location':     return conboard_screen_locations_archive();
		}
	}

	return '';
}
