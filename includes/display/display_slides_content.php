<?php
// includes/display/display_slides_content.php
//
// Content slide types for Display Boards: Sponsor Showcase, Presenter, Session, Generated List,
// General Information, Location Info and Directional Sign. They reuse the Screen Mode pages (display_screens.php), so a
// slide looks the same as the screen for that page.
//
// A slide can also set the address for the QR code in the corner while it is showing ('url').

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

// Published posts of a type as ID => title (for choosing one in the editor)
function conboard_get_post_choices( string $post_type, int $limit = 300 ): array {

	$out = [];

	foreach ( get_posts([ 'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => 'title', 'order' => 'ASC' ]) as $post ) {
		$out[ $post->ID ] = get_the_title( $post );
	}

	return $out;
}

// Upcoming and current sessions as ID => "Title (Sat 9:00 AM)"
function conboard_get_session_choices(): array {

	$out = [];

	foreach ( conboard_schedule_query([ 'date' => 'all', 'completed' => 'show' ]) as $row ) {
		$out[ $row['id'] ] = $row['title'] . ' (' . wp_date( 'D g:i A', $row['start'] ) . ')';
	}

	return $out;
}

function conboard_get_sponsor_level_slug_choices(): array {

	$out = [];

	foreach ( conboard_get_sponsor_levels() as $key => $level ) {
		$out[ $key ] = ! empty( $level['label'] ) ? $level['label'] : ucfirst( $key );
	}

	return $out;
}

// A page for one post: its name as a heading, then its Screen Mode page. $url is the QR address.
function conboard_slide_post_page( WP_Post $post, string $inner_html, string $url = '' ): array {
	return [
		'html'	=> '<h2 class="cb-page-heading">' . esc_html( get_the_title( $post ) ) . '</h2>' . $inner_html,
		'url'	=> $url !== '' ? $url : (string) get_permalink( $post ),
	];
}

// Text for a QR code that joins a Wi-Fi network when scanned
function conboard_wifi_qr_text( string $network, string $password ): string {

	$escape = fn( string $v ) => preg_replace( '/([\\\;,:"])/', '\\\\$1', $v );

	return 'WIFI:T:' . ( $password !== '' ? 'WPA' : 'nopass' ) . ';S:' . $escape( $network ) . ';' . ( $password !== '' ? 'P:' . $escape( $password ) . ';' : '' ) . ';';
}


// ---------------------------------------------------------------------------
// Sponsor Showcase
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'sponsors', [
	'label'		=> 'Sponsor Showcase',
	'fields'	=> fn() => [
		'levels'	=> [ 'label' => 'Sponsor levels', 'type' => 'multiselect', 'choices' => conboard_get_sponsor_level_slug_choices() ],
		'layout'	=> [ 'label' => 'Layout', 'type' => 'select', 'choices' => [ 'grid' => 'Logo grid (a page per level)', 'single' => 'One sponsor at a time' ] ],
		'limit'		=> [ 'label' => 'Logos per page', 'type' => 'number', 'min' => 1, 'max' => 12 ],
	],
	'render'	=> function ( array $s ) {

		$levels = conboard_setting_list( $s, 'levels' );

		if ( ( $s['layout'] ?? 'grid' ) === 'single' ) {

			$pages = [];

			foreach ( conboard_get_sponsor_groups([ 'levels' => $levels ]) as $group ) {
				foreach ( $group['sponsors'] as $sponsor ) {
					// The QR code goes to the sponsor's page on this site, which links on to their own sites
					$pages[] = [ 'html' => conboard_screen_sponsor( $sponsor ), 'url' => (string) get_permalink( $sponsor ) ];
				}
			}

			return $pages;
		}

		return conboard_screen_sponsor_pages( $levels, max( 1, min( 12, (int) ( $s['limit'] ?? 8 ) ?: 8 ) ) );
	},
]);


// ---------------------------------------------------------------------------
// Presenter
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'presenter', [
	'label'		=> 'Presenter',
	'fields'	=> fn() => [
		'presenter'	=> [ 'label' => 'Presenter (blank = keynote or featured presenters)', 'type' => 'select', 'choices' => [ '' => '— Keynote / featured —' ] + conboard_get_post_choices( 'presenter' ) ],
	],
	'render'	=> function ( array $s ) {

		$id = (int) ( $s['presenter'] ?? 0 );

		if ( $id ) {
			$presenters = array_filter( [ conboard_get_featured_presenter( $id ) ] );
		} else {
			$presenters = conboard_get_presenters([ 'keynote' => true, 'featured' => true ]);
		}

		$pages = [];

		foreach ( $presenters as $presenter ) {
			$pages[] = conboard_slide_post_page( $presenter, conboard_screen_presenter( $presenter ) );
		}

		return $pages;
	},
]);


// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'session', [
	'label'		=> 'Session',
	'fields'	=> fn() => [
		'session'	=> [ 'label' => 'Session', 'type' => 'select', 'choices' => [ '' => '— Choose a session —' ] + conboard_get_session_choices() ],
	],
	'render'	=> function ( array $s ) {

		$session = get_post( (int) ( $s['session'] ?? 0 ) );

		if ( ! $session || $session->post_type !== 'session' || $session->post_status !== 'publish' ) {
			return '';
		}

		return conboard_slide_post_page( $session, conboard_screen_session( $session ) );
	},
]);


// ---------------------------------------------------------------------------
// Generated List: slides made from a whole set of content
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'generated_list', [
	'label'		=> 'Generated List',
	'fields'	=> fn() => [
		'content'	=> [ 'label' => 'Make slides from', 'type' => 'select', 'choices' => [
			'presenters'	=> 'All presenters',
			'keynotes'		=> 'Keynote speakers',
			'staff'			=> 'Staff',
			'sponsors'		=> 'Sponsors',
			'sessions'		=> 'Upcoming sessions',
		] ],
		'style'		=> [ 'label' => 'Style', 'type' => 'select', 'choices' => [ 'tiles' => 'Photo grid', 'one_each' => 'One slide for each' ] ],
		'limit'		=> [ 'label' => 'Most to include', 'type' => 'number', 'min' => 1, 'max' => 40 ],
	],
	'render'	=> function ( array $s ) {

		$content = (string) ( $s['content'] ?? 'presenters' );
		$one     = ( $s['style'] ?? 'tiles' ) === 'one_each';
		$limit   = max( 1, min( 40, (int) ( $s['limit'] ?? 12 ) ?: 12 ) );

		switch ( $content ) {

			case 'keynotes':
			case 'presenters':
				$posts = conboard_get_presenters( $content === 'keynotes' ? [ 'keynote' => true ] : [] );
				$posts = array_slice( $posts, 0, $limit );

				if ( ! $one ) {
					return conboard_screen_presenter_pages( $posts, 8, $content === 'keynotes' ? 'Keynote speakers' : 'Presenters' );
				}

				return array_map( fn( $p ) => conboard_slide_post_page( $p, conboard_screen_presenter( $p ) ), $posts );

			case 'staff':
				$posts = conboard_get_staff([ 'limit' => $limit ]);

				if ( ! $one ) {
					return conboard_screen_staff_pages( $posts, 8, 'Our team' );
				}

				return array_map( fn( $p ) => conboard_slide_post_page( $p, conboard_screen_staff( $p ) ), $posts );

			case 'sponsors':
				if ( ! $one ) {
					return conboard_screen_sponsor_pages();
				}

				$pages = [];
				foreach ( conboard_get_sponsor_groups() as $group ) {
					foreach ( $group['sponsors'] as $sponsor ) {
						$pages[] = [ 'html' => conboard_screen_sponsor( $sponsor ), 'url' => (string) get_permalink( $sponsor ) ];
					}
				}
				return array_slice( $pages, 0, $limit );

			case 'sessions':
				$pages = [];
				foreach ( conboard_schedule_query([ 'date' => 'all', 'completed' => 'hide', 'cancelled' => 'hide', 'limit' => $limit ]) as $row ) {
					$session = get_post( $row['id'] );
					if ( $session ) {
						$pages[] = conboard_slide_post_page( $session, conboard_screen_session( $session ) );
					}
				}
				return $pages;
		}

		return '';
	},
]);


// ---------------------------------------------------------------------------
// General Information: Wi-Fi, registration, venue details
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'general_info', [
	'label'		=> 'General Information',
	'fields'	=> [
		'heading'	=> [ 'label' => 'Heading', 'type' => 'text' ],
		'text'		=> [ 'label' => 'Text', 'type' => 'textarea', 'rows' => 4 ],
		'wifi_name'	=> [ 'label' => 'Wi-Fi network (optional)', 'type' => 'text' ],
		'wifi_pass'	=> [ 'label' => 'Wi-Fi password', 'type' => 'text' ],
		'wifi_qr'	=> [ 'label' => 'QR code joins the Wi-Fi', 'type' => 'checkbox', 'default' => '1' ],
		'qr_url'	=> [ 'label' => 'Or QR code address', 'type' => 'url' ],
	],
	'render'	=> function ( array $s ) {

		$heading = (string) ( $s['heading'] ?? '' );
		$text    = (string) ( $s['text'] ?? '' );
		$network = (string) ( $s['wifi_name'] ?? '' );
		$pass    = (string) ( $s['wifi_pass'] ?? '' );

		if ( $heading === '' && $text === '' && $network === '' ) {
			return '';
		}

		$html  = '<div class="cb-info">';
		$html .= $heading !== '' ? '<h2 class="cb-info-heading">' . esc_html( $heading ) . '</h2>' : '';
		$html .= $text !== '' ? '<div class="cb-info-text">' . wpautop( esc_html( $text ) ) . '</div>' : '';

		if ( $network !== '' ) {
			$html .= '<dl class="cb-wifi"><dt>Wi-Fi network</dt><dd>' . esc_html( $network ) . '</dd>';
			$html .= $pass !== '' ? '<dt>Password</dt><dd>' . esc_html( $pass ) . '</dd>' : '';
			$html .= '</dl>';
		}

		$html .= '</div>';

		$url = (string) ( $s['qr_url'] ?? '' );

		if ( $url === '' && $network !== '' && conboard_setting_flag( $s, 'wifi_qr', true ) ) {
			$url = conboard_wifi_qr_text( $network, $pass );
		}

		return [ 'html' => $html, 'url' => $url ];
	},
]);


// ---------------------------------------------------------------------------
// Location Info: a location's directions text and map (stored as 'wayfinding' so saved boards keep working)
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'wayfinding', [
	'label'		=> 'Location Info',
	'fields'	=> fn() => [
		'location'	=> [ 'label' => 'Location', 'type' => 'select', 'choices' => [ '' => '— Choose a location —' ] + conboard_get_post_choices( 'location' ) ],
		'directions'=> [ 'label' => 'Directions (optional)', 'type' => 'textarea', 'rows' => 3 ],
		'show_map'	=> [ 'label' => 'Show the map', 'type' => 'checkbox', 'default' => '1' ],
	],
	'render'	=> function ( array $s ) {

		$location = get_post( (int) ( $s['location'] ?? 0 ) );

		if ( ! $location || $location->post_type !== 'location' || $location->post_status !== 'publish' ) {
			return '';
		}

		$directions = (string) ( $s['directions'] ?? '' );
		$summary    = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $location->post_content ) ), 40 );
		$map        = conboard_setting_flag( $s, 'show_map', true ) ? conboard_get_location_map_html( $location->ID ) : '';

		$text  = $directions !== '' ? '<div class="cb-way-directions">' . wpautop( esc_html( $directions ) ) . '</div>' : '';
		$text .= $summary !== '' ? '<p class="cb-summary">' . esc_html( $summary ) . '</p>' : '';

		$html  = '<div class="cb-way' . ( $map !== '' ? ' has-map' : '' ) . '">';
		$html .= '<div class="cb-way-text">' . $text . '</div>';
		$html .= $map !== '' ? '<div class="cb-way-map">' . $map . '</div>' : '';
		$html .= '</div>';

		return conboard_slide_post_page( $location, $html );
	},
]);


// ---------------------------------------------------------------------------
// Directional Sign: big arrows with a bold label ("Registration" with an arrow pointing the way)
// ---------------------------------------------------------------------------

// Direction => degrees to turn an upward-pointing arrow
function conboard_sign_directions(): array {
	return [
		'left'		=> [ 'label' => 'Left', 'deg' => 270 ],
		'right'		=> [ 'label' => 'Right', 'deg' => 90 ],
		'up'		=> [ 'label' => 'Straight ahead (up)', 'deg' => 0 ],
		'down'		=> [ 'label' => 'Down', 'deg' => 180 ],
		'up-left'	=> [ 'label' => 'Up and left', 'deg' => 315 ],
		'up-right'	=> [ 'label' => 'Up and right', 'deg' => 45 ],
		'down-left'	=> [ 'label' => 'Down and left', 'deg' => 225 ],
		'down-right'=> [ 'label' => 'Down and right', 'deg' => 135 ],
	];
}

// One sign row: arrow and label (arrow after the label when it points right)
function conboard_sign_row_html( string $direction, string $label ): string {

	$directions = conboard_sign_directions();

	if ( $label === '' || ! isset( $directions[ $direction ] ) ) {
		return '';
	}

	$arrow = '<svg class="cb-sign-arrow" viewBox="0 0 100 100" style="transform:rotate(' . (int) $directions[ $direction ]['deg'] . 'deg)" aria-hidden="true"><path d="M50 6 L94 52 H66 V94 H34 V52 H6 Z" fill="currentColor"/></svg>';
	$text  = '<span class="cb-sign-label">' . esc_html( $label ) . '</span>';
	$after = in_array( $direction, [ 'right', 'up-right', 'down-right' ], true );

	return '<div class="cb-sign-row">' . ( $after ? $text . $arrow : $arrow . $text ) . '</div>';
}

conboard_register_slide_type( 'directional_sign', [
	'label'		=> 'Directional Sign',
	'fields'	=> fn() => [
		'direction'		=> [ 'label' => 'Arrow', 'type' => 'select', 'choices' => wp_list_pluck( conboard_sign_directions(), 'label' ), 'default' => 'right' ],
		'label'			=> [ 'label' => 'Big label (e.g. Registration)', 'type' => 'text' ],
		'direction2'	=> [ 'label' => 'Second arrow (optional)', 'type' => 'select', 'choices' => [ '' => '— None —' ] + wp_list_pluck( conboard_sign_directions(), 'label' ) ],
		'label2'		=> [ 'label' => 'Second label', 'type' => 'text' ],
		'note'			=> [ 'label' => 'Small note (optional)', 'type' => 'text' ],
	],
	'render'	=> function ( array $s ) {

		$rows = conboard_sign_row_html( (string) ( $s['direction'] ?? 'right' ), (string) ( $s['label'] ?? '' ) )
			. conboard_sign_row_html( (string) ( $s['direction2'] ?? '' ), (string) ( $s['label2'] ?? '' ) );

		if ( $rows === '' ) {
			return '';
		}

		$note = (string) ( $s['note'] ?? '' );

		return [ 'html' => '<div class="cb-sign">' . $rows . ( $note !== '' ? '<p class="cb-sign-note">' . esc_html( $note ) . '</p>' : '' ) . '</div>', 'url' => '' ];
	},
]);
