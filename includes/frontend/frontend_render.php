<?php
// includes/frontend/frontend_render.php
//
// Data lookups and HTML renderers shared by the page templates and the shortcodes.

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Small helpers
// ---------------------------------------------------------------------------

function conboard_is_truthy( $value ): bool {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

// "gold, silver" -> [ 'gold', 'silver' ]
function conboard_parse_key_list( $value ): array {
	$keys = array_map( 'sanitize_key', array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) );

	return array_values( array_unique( $keys ) );
}


// ---------------------------------------------------------------------------
// Sponsors: data
// ---------------------------------------------------------------------------

/**
 * Published sponsors grouped by level, in level order, then Display Order, then name.
 * Sponsors whose level no longer exists are collected under "Supporters" at the end.
 *
 * @param array $args {
 *     @type string[] $levels Only these level slugs ("other" means the Supporters group).
 *     @type int      $limit  Maximum number of sponsors in total (0 = no limit).
 * }
 * @return array[] level key => [ 'key', 'label', 'plural', 'sponsors' => WP_Post[] ]
 */
function conboard_get_sponsor_groups( array $args = [] ): array {

	$args   = wp_parse_args( $args, [ 'levels' => [], 'limit' => 0 ] );
	$levels = conboard_get_sponsor_levels();
	$only   = array_map( function ( $key ) { return $key === 'other' ? '_other' : $key; }, (array) $args['levels'] );

	$groups = [];

	foreach ( $levels as $key => $level ) {
		$label = ! empty( $level['label'] ) ? $level['label'] : ucfirst( $key );

		$groups[ $key ] = [
			'key'		=> $key,
			'label'		=> $label,
			'plural'	=> ! empty( $level['plural'] ) ? $level['plural'] : $label,
			'sponsors'	=> [],
		];
	}

	$groups['_other'] = [
		'key'		=> '_other',
		'label'		=> 'Supporters',
		'plural'	=> 'Supporters',
		'sponsors'	=> [],
	];

	$sponsors = get_posts([
		'post_type'			=> 'sponsor',
		'post_status'		=> 'publish',
		'posts_per_page'	=> -1,
	]);

	foreach ( $sponsors as $sponsor ) {
		$level = (string) get_post_meta( $sponsor->ID, 'sponsor_level', true );
		$key   = isset( $levels[ $level ] ) ? $level : '_other';

		if ( ! empty( $only ) && ! in_array( $key, $only, true ) ) {
			continue;
		}

		$groups[ $key ]['sponsors'][] = $sponsor;
	}

	$remaining = (int) $args['limit'];

	foreach ( $groups as $key => &$group ) {

		usort( $group['sponsors'], function ( $a, $b ) {
			$order = (int) get_post_meta( $a->ID, 'display_order', true ) <=> (int) get_post_meta( $b->ID, 'display_order', true );
			return $order !== 0 ? $order : strcasecmp( $a->post_title, $b->post_title );
		});

		if ( $args['limit'] > 0 ) {
			$group['sponsors'] = array_slice( $group['sponsors'], 0, max( 0, $remaining ) );
			$remaining        -= count( $group['sponsors'] );
		}
	}
	unset( $group );

	return array_filter( $groups, function ( $group ) {
		return ! empty( $group['sponsors'] );
	});
}

// All sponsors from the groups as one flat list
function conboard_flatten_sponsor_groups( array $groups ): array {
	$flat = [];

	foreach ( $groups as $group ) {
		$flat = array_merge( $flat, $group['sponsors'] );
	}

	return $flat;
}


// ---------------------------------------------------------------------------
// Presenters: data
// ---------------------------------------------------------------------------

/**
 * Published presenters. Keynote speakers come first, then featured presenters, then the rest by name.
 *
 * @param array $args {
 *     @type bool $featured Only presenters marked "Featured Presenter".
 *     @type bool $keynote  Only presenters marked "Keynote Speaker". With both set, either flag matches.
 *     @type int  $limit    Maximum number to return (0 = no limit).
 * }
 */
function conboard_get_presenters( array $args = [] ): array {

	$args = wp_parse_args( $args, [ 'featured' => false, 'keynote' => false, 'limit' => 0 ] );

	$query = [
		'post_type'			=> 'presenter',
		'post_status'		=> 'publish',
		'posts_per_page'	=> -1,
	];

	$meta = [];

	if ( $args['featured'] ) {
		$meta[] = [ 'key' => 'featured_presenter', 'value' => '1' ];
	}

	if ( $args['keynote'] ) {
		$meta[] = [ 'key' => 'keynote_speaker', 'value' => '1' ];
	}

	if ( count( $meta ) > 1 ) {
		$meta['relation'] = 'OR';
	}

	if ( ! empty( $meta ) ) {
		$query['meta_query'] = $meta;
	}

	$presenters = get_posts( $query );

	usort( $presenters, function ( $a, $b ) {
		$rank = function ( $post ) {
			if ( get_post_meta( $post->ID, 'keynote_speaker', true ) ) return 0;
			if ( get_post_meta( $post->ID, 'featured_presenter', true ) ) return 1;
			return 2;
		};

		$order = $rank( $a ) <=> $rank( $b );
		return $order !== 0 ? $order : strcasecmp( $a->post_title, $b->post_title );
	});

	if ( $args['limit'] > 0 ) {
		$presenters = array_slice( $presenters, 0, (int) $args['limit'] );
	}

	return $presenters;
}

// One presenter to spotlight: a specific ID, or a keynote speaker, or else a featured presenter.
function conboard_get_featured_presenter( int $presenter_id = 0, bool $random = false ): ?WP_Post {

	if ( $presenter_id ) {
		$post = get_post( $presenter_id );
		return ( $post && $post->post_type === 'presenter' && $post->post_status === 'publish' ) ? $post : null;
	}

	$pool = conboard_get_presenters([ 'keynote' => true ]);

	if ( empty( $pool ) ) {
		$pool = conboard_get_presenters([ 'featured' => true ]);
	}

	if ( empty( $pool ) ) {
		return null;
	}

	return $random ? $pool[ array_rand( $pool ) ] : $pool[0];
}


// ---------------------------------------------------------------------------
// Layout wrappers: grid and carousel
// ---------------------------------------------------------------------------

/**
 * Wrap a list of card HTML strings in a grid or a carousel.
 *
 * @param string[] $cards  Card HTML, already escaped.
 * @param array    $opts   layout (grid|carousel), columns (1-8), autoplay (bool), speed (seconds), label (string)
 */
function conboard_render_card_list( array $cards, array $opts = [] ): string {

	if ( empty( $cards ) ) {
		return '';
	}

	$opts = wp_parse_args( $opts, [
		'layout'	=> 'grid',
		'columns'	=> 4,
		'autoplay'	=> true,
		'speed'		=> 5,
		'label'		=> '',
	]);

	$columns = max( 1, min( 8, (int) $opts['columns'] ) );

	if ( $opts['layout'] === 'carousel' ) {

		conboard_enqueue_frontend( true );

		$html  = sprintf(
			'<div class="conboard-carousel" style="--conboard-cols:%d" data-autoplay="%d" data-speed="%d" role="region" aria-roledescription="carousel" aria-label="%s">',
			$columns,
			$opts['autoplay'] ? 1 : 0,
			max( 1, (int) $opts['speed'] ) * 1000,
			esc_attr( $opts['label'] )
		);
		$html .= '<button type="button" class="conboard-carousel-btn conboard-carousel-prev" aria-label="Previous">&lsaquo;</button>';
		$html .= '<ul class="conboard-carousel-track">';

		foreach ( $cards as $card ) {
			$html .= '<li class="conboard-carousel-item">' . $card . '</li>';
		}

		$html .= '</ul>';
		$html .= '<button type="button" class="conboard-carousel-btn conboard-carousel-next" aria-label="Next">&rsaquo;</button>';
		$html .= '</div>';

		return $html;
	}

	conboard_enqueue_frontend();

	$html = sprintf( '<ul class="conboard-grid" style="--conboard-cols:%d">', $columns );

	foreach ( $cards as $card ) {
		$html .= '<li class="conboard-grid-item">' . $card . '</li>';
	}

	return $html . '</ul>';
}


// ---------------------------------------------------------------------------
// Sponsors: rendering
// ---------------------------------------------------------------------------

/**
 * One sponsor card: logo (or name), optionally linked.
 * $link: "page" = the sponsor's page on this site, "website" = their own website, "none"
 */
function conboard_render_sponsor_card( WP_Post $sponsor, string $link = 'page', bool $show_name = false ): string {

	$name = get_the_title( $sponsor );
	$logo = get_the_post_thumbnail( $sponsor->ID, 'medium', [ 'alt' => $name, 'loading' => 'lazy' ] );

	$inner = $logo ? '<span class="conboard-card-logo">' . $logo . '</span>' : '';

	if ( ! $logo || $show_name ) {
		$inner .= '<span class="conboard-card-name">' . esc_html( $name ) . '</span>';
	}

	if ( $link === 'website' ) {
		$url = (string) get_post_meta( $sponsor->ID, 'website', true );

		if ( $url ) {
			return '<a class="conboard-card conboard-sponsor-card" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . $inner . '</a>';
		}
	} elseif ( $link === 'page' ) {
		return '<a class="conboard-card conboard-sponsor-card" href="' . esc_url( get_permalink( $sponsor ) ) . '">' . $inner . '</a>';
	}

	return '<div class="conboard-card conboard-sponsor-card">' . $inner . '</div>';
}

/**
 * Sponsors as a grid, a carousel, or grouped under a heading per level.
 *
 * @param array $args {
 *     @type string   $layout    levels (default) | grid | carousel
 *     @type string[] $levels    Only these levels
 *     @type int      $columns   1-8
 *     @type int      $limit     Maximum sponsors
 *     @type string   $link      page | website | none
 *     @type bool     $show_names Show the name under the logo
 *     @type bool     $autoplay  Carousel only
 *     @type int      $speed     Carousel seconds per step
 * }
 */
function conboard_render_sponsors( array $args = [] ): string {

	$args = wp_parse_args( $args, [
		'layout'		=> 'levels',
		'levels'		=> [],
		'columns'		=> 4,
		'limit'			=> 0,
		'link'			=> 'page',
		'show_names'	=> false,
		'autoplay'		=> true,
		'speed'			=> 4,
	]);

	$groups = conboard_get_sponsor_groups([ 'levels' => $args['levels'], 'limit' => $args['limit'] ]);

	if ( empty( $groups ) ) {
		return '';
	}

	$card = function ( WP_Post $sponsor ) use ( $args ) {
		return conboard_render_sponsor_card( $sponsor, $args['link'], (bool) $args['show_names'] );
	};

	if ( $args['layout'] === 'levels' ) {

		$html = '<div class="conboard-sponsors conboard-sponsors-levels">';

		foreach ( $groups as $group ) {
			$html .= '<section class="conboard-sponsor-level conboard-sponsor-level-' . esc_attr( $group['key'] ) . '">';
			$html .= '<h2 class="conboard-sponsor-level-title">' . esc_html( $group['plural'] ) . '</h2>';
			$html .= conboard_render_card_list( array_map( $card, $group['sponsors'] ), [ 'layout' => 'grid', 'columns' => $args['columns'] ] );
			$html .= '</section>';
		}

		return $html . '</div>';
	}

	$cards = array_map( $card, conboard_flatten_sponsor_groups( $groups ) );

	return '<div class="conboard-sponsors">' . conboard_render_card_list( $cards, [
		'layout'	=> $args['layout'] === 'carousel' ? 'carousel' : 'grid',
		'columns'	=> $args['columns'],
		'autoplay'	=> $args['autoplay'],
		'speed'		=> $args['speed'],
		'label'		=> 'Sponsors',
	]) . '</div>';
}


// ---------------------------------------------------------------------------
// Presenters: rendering
// ---------------------------------------------------------------------------

// "Keynote Speaker", "Featured" or '' for a presenter
function conboard_get_presenter_badge( int $presenter_id ): string {
	if ( get_post_meta( $presenter_id, 'keynote_speaker', true ) ) return 'Keynote Speaker';
	if ( get_post_meta( $presenter_id, 'featured_presenter', true ) ) return 'Featured';
	return '';
}

// "Title, Organization" line for a presenter
function conboard_get_presenter_title_line( int $presenter_id ): string {
	$parts = array_filter([
		(string) get_post_meta( $presenter_id, 'presenter_title', true ),
		(string) get_post_meta( $presenter_id, 'presenter_organization', true ),
	]);

	return implode( ', ', $parts );
}

// One presenter card: photo, name, title line and badge, linked to their page
function conboard_render_presenter_card( WP_Post $presenter, bool $show_title = true, bool $show_badge = true ): string {

	$photo = get_the_post_thumbnail( $presenter->ID, 'medium', [ 'alt' => get_the_title( $presenter ), 'loading' => 'lazy' ] );
	$badge = $show_badge ? conboard_get_presenter_badge( $presenter->ID ) : '';
	$line  = $show_title ? conboard_get_presenter_title_line( $presenter->ID ) : '';

	$html  = '<a class="conboard-card conboard-presenter-card" href="' . esc_url( get_permalink( $presenter ) ) . '">';
	$html .= $photo ? '<span class="conboard-card-photo">' . $photo . '</span>' : '';
	$html .= $badge ? '<span class="conboard-card-badge">' . esc_html( $badge ) . '</span>' : '';
	$html .= '<span class="conboard-card-name">' . esc_html( get_the_title( $presenter ) ) . '</span>';
	$html .= $line ? '<span class="conboard-card-subtitle">' . esc_html( $line ) . '</span>' : '';
	$html .= '</a>';

	return $html;
}

/**
 * Presenters as a grid or carousel.
 *
 * @param array $args featured, keynote, limit, columns, layout (grid|carousel), show_title, autoplay, speed
 */
function conboard_render_presenters( array $args = [] ): string {

	$args = wp_parse_args( $args, [
		'featured'		=> false,
		'keynote'		=> false,
		'limit'			=> 0,
		'columns'		=> 3,
		'layout'		=> 'grid',
		'show_title'	=> true,
		'autoplay'		=> true,
		'speed'			=> 5,
	]);

	$presenters = conboard_get_presenters([
		'featured'	=> $args['featured'],
		'keynote'	=> $args['keynote'],
		'limit'		=> $args['limit'],
	]);

	if ( empty( $presenters ) ) {
		return '';
	}

	$cards = array_map( function ( WP_Post $presenter ) use ( $args ) {
		return conboard_render_presenter_card( $presenter, (bool) $args['show_title'] );
	}, $presenters );

	return '<div class="conboard-presenters">' . conboard_render_card_list( $cards, [
		'layout'	=> $args['layout'] === 'carousel' ? 'carousel' : 'grid',
		'columns'	=> $args['columns'],
		'autoplay'	=> $args['autoplay'],
		'speed'		=> $args['speed'],
		'label'		=> 'Presenters',
	]) . '</div>';
}

/**
 * Spotlight on one presenter: photo, name, bio excerpt and their next session.
 */
function conboard_render_featured_speaker( WP_Post $presenter ): string {

	conboard_enqueue_frontend();

	$badge = conboard_get_presenter_badge( $presenter->ID );
	$line  = conboard_get_presenter_title_line( $presenter->ID );
	$bio   = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $presenter->post_content ) ), 45 );
	$url   = get_permalink( $presenter );

	// Their next (or current) session
	$next = null;
	$now  = current_time( 'timestamp' );

	foreach ( conboard_get_sessions_for_presenter( $presenter->ID ) as $session ) {
		if ( in_array( conboard_get_session_state( $session->ID, $now ), [ 'current', 'upcoming' ], true ) ) {
			$next = $session;
			break;
		}
	}

	$html  = '<div class="conboard-featured-speaker">';

	if ( has_post_thumbnail( $presenter ) ) {
		$html .= '<a class="conboard-featured-photo" href="' . esc_url( $url ) . '">'
			. get_the_post_thumbnail( $presenter->ID, 'medium_large', [ 'alt' => get_the_title( $presenter ) ] )
			. '</a>';
	}

	$html .= '<div class="conboard-featured-body">';
	$html .= $badge ? '<div class="conboard-card-badge">' . esc_html( $badge ) . '</div>' : '';
	$html .= '<h3 class="conboard-featured-name"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $presenter ) ) . '</a></h3>';
	$html .= $line ? '<div class="conboard-card-subtitle">' . esc_html( $line ) . '</div>' : '';
	$html .= $bio ? '<p class="conboard-featured-bio">' . esc_html( $bio ) . '</p>' : '';

	if ( $next ) {
		$start = get_post_meta( $next->ID, 'start_time', true );

		$html .= '<p class="conboard-featured-session">';
		$html .= $start ? '<time>' . esc_html( date_i18n( 'D g:i A', strtotime( $start ) ) ) . '</time> ' : '';
		$html .= '<a href="' . esc_url( get_permalink( $next ) ) . '">' . esc_html( get_the_title( $next ) ) . '</a>';
		$html .= '</p>';
	}

	$html .= '<p><a class="conboard-more" href="' . esc_url( $url ) . '">Meet ' . esc_html( get_the_title( $presenter ) ) . ' &rarr;</a></p>';
	$html .= '</div></div>';

	return $html;
}
