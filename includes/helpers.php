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

// Get structured presenters for a session
function conboard_get_session_presenters( int $session_id ): array {
	$rows = get_field( 'presenters', $session_id );
	if ( ! is_array( $rows ) ) {
		return [];
	}

	$out = [];

	foreach ( $rows as $row ) {
		if ( empty( $row['presenter'] ) ) {
			continue;
		}

		$out[] = [
			'presenter_id' => (int) $row['presenter'],
			'role'		 => $row['role'] ?? conboard_get_default_presenter_role(),
		];
	}

	return $out;
}

// Return a list of sessions for a given presenter
function conboard_get_sessions_for_presenter($presenter_id) {
	return new WP_Query([
		'post_type' => 'session',
		'meta_query' => [
			[
				'key' => 'participants',
				'value' => '"' . $presenter_id . '"',
				'compare' => 'LIKE',
			],
		],
		'posts_per_page' => -1,
		'orderby' => 'meta_value',
		'meta_key' => 'start_time',
		'order' => 'ASC',
	]);
}


// Compare session time with current time
function conboard_get_session_state($session_id, $now = null) {
	$now = $now ?: current_time('timestamp');

	$start = strtotime(get_field('start_time', $session_id));
	$end   = strtotime(get_field('end_time', $session_id));

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

	// 2. Plugin fallback
	return CONBOARD_PLUGIN_PATH . 'templates/' . $template_name;
}
