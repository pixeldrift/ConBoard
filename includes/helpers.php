<?php
// includes/helpers.php

// Sponsorship level helpers
function conboard_get_sponsor_level_choices() {
	$choices = [];

	foreach ( conboard_get_sponsor_levels() as $key => $level ) {
		if ( empty( $level['label'] ) ) {
			continue;
		}
		$choices[ $key ] = $level['label'];
	}

	return $choices;
}

function conboard_get_default_sponsor_level(): string {
	return 'community';
}

function conboard_get_sponsor_level_label( string $level ): string {
	$levels = conboard_get_sponsor_levels();

	return $levels[ $level ]['label'] ?? ucfirst( $level );
}

// Give us the list of presenter roles
function conboard_get_presenter_role_choices(): array {
	$choices = [];

	foreach ( conboard_get_presenter_roles() as $key => $role ) {
		if ( ! is_array( $role ) ) {
			continue;
		}

		$choices[ $key ] = $role['singular'] ?? ucfirst( $key );
	}

	return $choices;
}

function conboard_get_default_presenter_role(): string {
	foreach ( conboard_get_presenter_roles() as $key => $role ) {
		if ( ! empty( $role['default'] ) ) {
			return $key;
		}
	}
	return 'presenter';
}
function conboard_get_presenter_role_label( string $role, string $form = 'singular' ): string {
	$roles = conboard_get_presenter_roles();
	return $roles[ $role ][ $form ] ?? ucfirst( $role );
}

// Normalize a post ID, WP_Post, or an array of either (e.g. an ACF relationship value) into unique int IDs
function conboard_normalize_ids( $value ): array {

	if ( empty( $value ) ) {
		return [];
	}

	if ( ! is_array( $value ) ) {
		$value = [ $value ];
	}

	$ids = [];

	foreach ( $value as $item ) {
		if ( $item instanceof WP_Post ) {
			$item = $item->ID;
		}

		if ( is_numeric( $item ) && (int) $item > 0 ) {
			$ids[] = (int) $item;
		}
	}

	return array_values( array_unique( $ids ) );
}

// Get structured presenters for a session
// (the "presenter" sub-field is a relationship, so it can hold more than one ID)
function conboard_get_session_presenters( int $session_id ): array {
	$rows = get_field( 'presenters', $session_id );
	if ( ! is_array( $rows ) ) {
		return [];
	}

	$out = [];

	foreach ( $rows as $row ) {
		$role = ! empty( $row['role'] ) ? $row['role'] : conboard_get_default_presenter_role();

		foreach ( conboard_normalize_ids( $row['presenter'] ?? null ) as $presenter_id ) {
			$out[] = [
				'presenter_id'	=> $presenter_id,
				'role'			=> $role,
			];
		}
	}

	return $out;
}

// IDs of published sessions that list a given presenter
// ACF stores repeater rows as presenters_0_presenter, presenters_1_presenter, ...
function conboard_get_session_ids_for_presenter( int $presenter_id ): array {
	global $wpdb;

	$key_like   = $wpdb->esc_like( 'presenters_' ) . '%' . $wpdb->esc_like( '_presenter' );
	$value_like = '%' . $wpdb->esc_like( '"' . $presenter_id . '"' ) . '%';

	$sql = $wpdb->prepare(
		"SELECT DISTINCT pm.post_id
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE p.post_type = 'session'
		   AND p.post_status = 'publish'
		   AND pm.meta_key LIKE %s
		   AND ( pm.meta_value = %s OR pm.meta_value LIKE %s )",
		$key_like,
		(string) $presenter_id,
		$value_like
	);

	return array_map( 'intval', $wpdb->get_col( $sql ) );
}

// Published sessions for a given presenter, earliest first (sessions without a start time go last)
function conboard_get_sessions_for_presenter( int $presenter_id ): array {
	$ids = conboard_get_session_ids_for_presenter( $presenter_id );

	if ( empty( $ids ) ) {
		return [];
	}

	$sessions = get_posts([
		'post_type'			=> 'session',
		'post__in'			=> $ids,
		'posts_per_page'	=> -1,
		'orderby'			=> 'post__in',
	]);

	return conboard_sort_sessions_by_start( $sessions );
}

// Sort session posts by start time, earliest first (sessions without a start time go last)
function conboard_sort_sessions_by_start( array $sessions ): array {
	usort( $sessions, function ( $a, $b ) {
		$a_start = (string) get_post_meta( $a->ID, 'start_time', true );
		$b_start = (string) get_post_meta( $b->ID, 'start_time', true );

		if ( $a_start === $b_start ) return 0;
		if ( $a_start === '' ) return 1;
		if ( $b_start === '' ) return -1;

		return strcmp( $a_start, $b_start );
	});

	return $sessions;
}

// IDs of every location nested inside a location (children, grandchildren, ...)
function conboard_get_descendant_location_ids( int $location_id, int $max_depth = 10 ): array {
	if ( $max_depth <= 0 ) {
		return [];
	}

	$children = get_posts([
		'post_type'			=> 'location',
		'post_parent'		=> $location_id,
		'post_status'		=> 'publish',
		'posts_per_page'	=> -1,
		'fields'			=> 'ids',
	]);

	$ids = [];

	foreach ( $children as $child_id ) {
		$ids[] = (int) $child_id;
		$ids   = array_merge( $ids, conboard_get_descendant_location_ids( (int) $child_id, $max_depth - 1 ) );
	}

	return array_values( array_unique( $ids ) );
}

// Published sessions held at a location, earliest first.
// With $include_children, sessions in rooms/floors nested inside it are included too.
function conboard_get_sessions_for_location( int $location_id, bool $include_children = true ): array {
	$location_ids = [ $location_id ];

	if ( $include_children ) {
		$location_ids = array_merge( $location_ids, conboard_get_descendant_location_ids( $location_id ) );
	}

	$sessions = get_posts([
		'post_type'			=> 'session',
		'post_status'		=> 'publish',
		'posts_per_page'	=> -1,
		'meta_query'		=> [
			[
				'key'		=> 'location',
				'value'		=> array_map( 'strval', $location_ids ),
				'compare'	=> 'IN',
			],
		],
	]);

	return conboard_sort_sessions_by_start( $sessions );
}

// The role a presenter has in a particular session (e.g. "speaker", "moderator")
function conboard_get_presenter_role_in_session( int $session_id, int $presenter_id ): string {
	foreach ( conboard_get_session_presenters( $session_id ) as $entry ) {
		if ( $entry['presenter_id'] === $presenter_id ) {
			return (string) $entry['role'];
		}
	}

	return conboard_get_default_presenter_role();
}


// Compare session time with current time: 'past', 'current', 'upcoming' or 'unscheduled'
function conboard_get_session_state($session_id, $now = null) {
	$now = $now ?: current_time('timestamp');

	$start = strtotime( (string) get_field('start_time', $session_id) );
	$end   = strtotime( (string) get_field('end_time', $session_id) );

	if (!$start) return 'unscheduled';
	if (!$end)   $end = $start;

	if ($end < $now) return 'past';
	if ($start <= $now && $end >= $now) return 'current';
	return 'upcoming';
}


// Template Helper
function conboard_locate_template($template_name) {

	// 1. Theme override
	$theme_template = locate_template('conboard/' . $template_name);
	if ($theme_template) {
		return $theme_template;
	}

	// 2. Plugin fallback (only if the file exists)
	$plugin_template = CONBOARD_PLUGIN_PATH . 'templates/' . $template_name;
	return file_exists($plugin_template) ? $plugin_template : '';
}
