<?php
// includes/display/display_slides.php
//
// Display Boards: the slide type registry, how a board's slides and settings are stored,
// and how a board is turned into screen pages.
//
// A Display Board is a playlist of slides. Each slide is
//   [ 'type' => 'welcome', 'enabled' => true, 'duration' => 0 (0 = board default), 'settings' => [ ... ] ]
// stored as a list in the board's _conboard_slides meta. Each slide type is registered with
// conboard_register_slide_type(), which gives its settings fields (for the editor) and a
// render callback, so new types can be added without touching the playback code.

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Slide type registry
// ---------------------------------------------------------------------------

/**
 * Register a slide type.
 *
 * @param string $key  Short unique key, e.g. 'welcome'
 * @param array  $def  [
 *     'label'  => 'Welcome',
 *     'fields' => [ 'heading' => [ 'label' => 'Heading', 'type' => 'text' ], ... ]  (row editor field definitions;
 *                  or a function returning them, for fields that need WordPress data such as the list of locations),
 *     'render' => callable( array $settings, array $slide, int $board_id )
 *                 Returns the slide's HTML, or '' to skip it. A slide that needs several screens (for example a
 *                 page of sponsors per level) returns a list of pages; each page is HTML or
 *                 [ 'html' => ..., 'url' => address for the QR code while this page shows ].
 * ]
 */
function conboard_register_slide_type( string $key, array $def ): void {
	add_filter( 'conboard_slide_types', function ( array $types ) use ( $key, $def ) {
		$types[ $key ] = $def;
		return $types;
	});
}

function conboard_get_slide_types(): array {
	return apply_filters( 'conboard_slide_types', [] );
}

// A slide type's settings fields (resolved at the moment they are needed)
function conboard_get_slide_type_fields( array $def ): array {
	$fields = $def['fields'] ?? [];
	return is_callable( $fields ) ? (array) $fields() : (array) $fields;
}


// ---------------------------------------------------------------------------
// Board settings
// ---------------------------------------------------------------------------

function conboard_get_board_setting_defaults(): array {
	return [
		'orientation'	=> 'landscape',
		'rotate'		=> '0',
		'flip'			=> 'none',
		'interval'		=> 10,
		'refresh'		=> 60,
		'clock'			=> true,
		'qr'			=> true,
		'transition'	=> 'fade',
	];
}

// A board's settings with defaults filled in
function conboard_get_board_settings( int $board_id ): array {

	$saved = get_post_meta( $board_id, '_conboard_board', true );
	$saved = is_array( $saved ) ? $saved : [];

	return array_merge( conboard_get_board_setting_defaults(), $saved );
}

// Clean submitted board settings (anything invalid falls back to the default)
function conboard_sanitize_board_settings( array $raw ): array {

	$defaults = conboard_get_board_setting_defaults();
	$out      = [];

	$orientation = conboard_normalize_display_param( 'orientation', $raw['orientation'] ?? null );
	$rotate      = conboard_normalize_display_param( 'rotate', $raw['rotate'] ?? null );
	$flip        = conboard_normalize_display_param( 'flip', $raw['flip'] ?? null );
	$interval    = conboard_clamp_display_int( $raw['interval'] ?? null, 3, 300 );
	$refresh     = conboard_clamp_display_int( $raw['refresh'] ?? null, 15, 3600 );

	$out['orientation'] = $orientation ?? $defaults['orientation'];
	$out['rotate']      = $rotate ?? $defaults['rotate'];
	$out['flip']        = $flip ?? $defaults['flip'];
	$out['interval']    = $interval ?: $defaults['interval'];
	$out['refresh']     = $refresh ?: $defaults['refresh'];
	$out['clock']       = ( $raw['clock'] ?? '' ) === '1';
	$out['qr']          = ( $raw['qr'] ?? '' ) === '1';
	$out['transition']  = in_array( $raw['transition'] ?? '', [ 'fade', 'none' ], true ) ? $raw['transition'] : $defaults['transition'];

	return $out;
}


// ---------------------------------------------------------------------------
// Slides: storage and editor conversion
// ---------------------------------------------------------------------------

// A board's slides, cleaned up. Unknown types are kept (so nothing is lost) but never shown.
function conboard_get_board_slides( int $board_id ): array {

	$stored = get_post_meta( $board_id, '_conboard_slides', true );

	if ( ! is_array( $stored ) ) {
		return [];
	}

	$out = [];

	foreach ( $stored as $slide ) {
		if ( ! is_array( $slide ) || empty( $slide['type'] ) ) {
			continue;
		}

		$out[] = [
			'type'		=> (string) $slide['type'],
			'enabled'	=> ! isset( $slide['enabled'] ) || (bool) $slide['enabled'],
			'duration'	=> max( 0, (int) ( $slide['duration'] ?? 0 ) ),
			'settings'	=> is_array( $slide['settings'] ?? null ) ? $slide['settings'] : [],
		];
	}

	return $out;
}

function conboard_save_board_slides( int $board_id, array $slides ): void {
	update_post_meta( $board_id, '_conboard_slides', array_values( $slides ) );
}

// Row editor fields: the common ones, then every slide type's own fields (shown only for that type)
function conboard_get_slide_editor_fields(): array {

	$types   = conboard_get_slide_types();
	$choices = [];

	foreach ( $types as $key => $def ) {
		$choices[ $key ] = $def['label'] ?? $key;
	}

	$fields = [
		'type'		=> [ 'label' => 'Slide type', 'type' => 'select', 'choices' => $choices, 'default' => (string) array_key_first( $choices ), 'required' => true, 'strict' => true ],
		'duration'	=> [ 'label' => 'Seconds (0 = board default)', 'type' => 'number', 'min' => 0, 'max' => 600 ],
		'enabled'	=> [ 'label' => 'Show', 'type' => 'checkbox', 'default' => '1' ],
	];

	foreach ( $types as $key => $def ) {
		foreach ( conboard_get_slide_type_fields( $def ) as $name => $field ) {
			$field['show_when'] = [ 'field' => 'type', 'in' => [ $key ] ];
			$field['required']  = false;
			$fields[ $key . '__' . $name ] = $field;
		}
	}

	return $fields;
}

// Saved slides -> rows for the editor
function conboard_slides_to_rows( array $slides ): array {

	$rows = [];

	foreach ( $slides as $slide ) {
		$row = [
			'type'		=> $slide['type'],
			'duration'	=> (string) $slide['duration'],
			'enabled'	=> $slide['enabled'] ? '1' : '0',
		];

		foreach ( $slide['settings'] as $name => $value ) {
			$row[ $slide['type'] . '__' . $name ] = is_scalar( $value ) ? (string) $value : '';
		}

		$rows[] = $row;
	}

	return $rows;
}

// Cleaned editor rows -> slides
function conboard_rows_to_slides( array $rows ): array {

	$types  = conboard_get_slide_types();
	$slides = [];

	foreach ( $rows as $row ) {
		$type = (string) ( $row['type'] ?? '' );

		if ( ! isset( $types[ $type ] ) ) {
			continue;
		}

		$settings = [];

		foreach ( array_keys( conboard_get_slide_type_fields( $types[ $type ] ) ) as $name ) {
			$settings[ $name ] = (string) ( $row[ $type . '__' . $name ] ?? '' );
		}

		$slides[] = [
			'type'		=> $type,
			'enabled'	=> ( $row['enabled'] ?? '1' ) === '1',
			'duration'	=> max( 0, (int) ( $row['duration'] ?? 0 ) ),
			'settings'	=> $settings,
		];
	}

	return $slides;
}


// ---------------------------------------------------------------------------
// Slide types
// ---------------------------------------------------------------------------

conboard_register_slide_type( 'welcome', [
	'label'		=> 'Welcome',
	'fields'	=> [
		'heading'	=> [ 'label' => 'Heading (blank = site name)', 'type' => 'text' ],
		'message'	=> [ 'label' => 'Message', 'type' => 'textarea' ],
		'logo'		=> [ 'label' => 'Logo or image', 'type' => 'image' ],
	],
	'render'	=> function ( array $s ): string {

		$heading = ( $s['heading'] ?? '' ) !== '' ? $s['heading'] : get_bloginfo( 'name' );
		$image   = ! empty( $s['logo'] ) ? wp_get_attachment_image( (int) $s['logo'], 'large', false, [ 'class' => 'cb-slide-image' ] ) : '';

		$html  = '<div class="cb-welcome">';
		$html .= $image;
		$html .= '<h2 class="cb-welcome-heading">' . esc_html( $heading ) . '</h2>';
		$html .= ( $s['message'] ?? '' ) !== '' ? '<p class="cb-welcome-message">' . nl2br( esc_html( $s['message'] ) ) . '</p>' : '';

		return $html . '</div>';
	},
]);

conboard_register_slide_type( 'custom', [
	'label'		=> 'Custom Content',
	'fields'	=> [
		'heading'	=> [ 'label' => 'Heading', 'type' => 'text' ],
		'text'		=> [ 'label' => 'Text', 'type' => 'textarea', 'rows' => 4 ],
		'image'		=> [ 'label' => 'Image', 'type' => 'image' ],
		'qr_url'	=> [ 'label' => 'QR code address (optional)', 'type' => 'url' ],
	],
	'render'	=> function ( array $s ) {

		$image   = ! empty( $s['image'] ) ? wp_get_attachment_image( (int) $s['image'], 'large', false, [ 'class' => 'cb-slide-image' ] ) : '';
		$heading = (string) ( $s['heading'] ?? '' );
		$text    = (string) ( $s['text'] ?? '' );

		if ( $image === '' && $heading === '' && $text === '' ) {
			return '';
		}

		$html  = '<div class="cb-custom' . ( $image !== '' ? ' has-image' : '' ) . '">';
		$html .= $image !== '' ? '<div class="cb-custom-image">' . $image . '</div>' : '';
		$html .= '<div class="cb-custom-body">';
		$html .= $heading !== '' ? '<h2 class="cb-custom-heading">' . esc_html( $heading ) . '</h2>' : '';
		$html .= $text !== '' ? '<div class="cb-custom-text">' . wpautop( esc_html( $text ) ) . '</div>' : '';

		return [ 'html' => $html . '</div></div>', 'url' => (string) ( $s['qr_url'] ?? '' ) ];
	},
]);

conboard_register_slide_type( 'announcements', [
	'label'		=> 'Announcements',
	'fields'	=> [
		'count'	=> [ 'label' => 'How many to show', 'type' => 'number', 'min' => 1, 'max' => 10 ],
	],
	'render'	=> function ( array $s ): string {

		$count = max( 1, min( 10, (int) ( $s['count'] ?? 3 ) ?: 3 ) );

		$announcements = conboard_get_announcements([
			'status'		=> 'active',
			'screens_only'	=> true,
			'urgent_first'	=> true,
			'limit'			=> $count,
		]);

		// Nothing to announce: the slide is skipped rather than shown empty
		if ( empty( $announcements ) ) {
			return '';
		}

		$html = '<div class="cb-announcement-list"><h2 class="cb-page-heading">Announcements</h2>';

		foreach ( $announcements as $announcement ) {
			$html .= '<div class="cb-announcement-item"><h3>' . esc_html( get_the_title( $announcement ) ) . '</h3>' . conboard_screen_announcement( $announcement ) . '</div>';
		}

		return $html . '</div>';
	},
]);


// ---------------------------------------------------------------------------
// Rendering a board
// ---------------------------------------------------------------------------

function conboard_screen_board( WP_Post $board ): string {

	$ctx      = conboard_get_display_context();
	$settings = conboard_get_board_settings( $board->ID );
	$default  = max( 3, (int) $ctx['interval'] );
	$types    = conboard_get_slide_types();
	$pages    = [];

	foreach ( conboard_get_board_slides( $board->ID ) as $slide ) {

		if ( ! $slide['enabled'] || ! isset( $types[ $slide['type'] ]['render'] ) ) {
			continue;
		}

		$result = call_user_func( $types[ $slide['type'] ]['render'], $slide['settings'], $slide, $board->ID );

		// One page as a string or as [ 'html' => ... ], or a list of pages
		if ( ! is_array( $result ) || isset( $result['html'] ) ) {
			$result = [ $result ];
		}

		foreach ( $result as $page ) {
			$page_html = is_array( $page ) ? (string) ( $page['html'] ?? '' ) : (string) $page;

			if ( $page_html === '' ) {
				continue;
			}

			$pages[] = [
				'html'		=> $page_html,
				'url'		=> is_array( $page ) ? (string) ( $page['url'] ?? '' ) : '',
				'duration'	=> $slide['duration'] > 0 ? $slide['duration'] : $default,
				'type'		=> $slide['type'],
			];
		}
	}

	if ( empty( $pages ) ) {
		return conboard_screen_empty( 'Nothing to show yet. Add slides to this Display Board.' );
	}

	$html = '<div class="cb-pages cb-board" data-pages="' . count( $pages ) . '" data-transition="' . esc_attr( $settings['transition'] ) . '">';

	foreach ( $pages as $index => $page ) {
		$html .= '<section class="cb-page cb-slide-' . esc_attr( $page['type'] ) . ( $index === 0 ? ' is-active' : '' ) . '" data-duration="' . (int) $page['duration'] . '"' . ( $page['url'] !== '' ? ' data-url="' . esc_attr( $page['url'] ) . '"' : '' ) . '>' . $page['html'] . '</section>';
	}

	return $html . '</div>';
}
