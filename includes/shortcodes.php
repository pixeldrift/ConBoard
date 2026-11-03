<?php
// includes/shortcodes.php

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('current_session', function ($atts) {
	$atts = shortcode_atts([
		'location' => null,
	], $atts);

	if (!$atts['location']) return '';

	$now = current_time('timestamp');

	$query = new WP_Query([
		'post_type'			=> 'session',
		'meta_query'		=> [
			[
				'key'		=> 'location',
				'value'		=> $atts['location'],
			],
			[
				'key'		=> 'start_time',
				'value'		=> date('Y-m-d H:i:s', $now),
				'compare'	=> '<=',
				'type'		=> 'DATETIME',
			],
			[
				'key'		=> 'end_time',
				'value'		=> date('Y-m-d H:i:s', $now),
				'compare'	=> '>=',
				'type'		=> 'DATETIME',
			],
		],
		'posts_per_page'	=> 1,
	]);

	if (!$query->have_posts()) return '<div class="no-session">No current session</div>';

	$query->the_post();
	ob_start();
	?>
	<div class="current-session" data-session-id="<?php the_ID(); ?>">
		<h2><?php the_title(); ?></h2>
		<div class="description"><?php the_content(); ?></div>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
});


//
// [conboard_sponsors] - sponsor logos anywhere
//
//   layout="levels|grid|carousel"  (default levels: a heading per sponsor level)
//   level="gold,silver"            only these levels ("other" = sponsors without a valid level)
//   columns="4"   limit="12"
//   link="page|website|none"       where a logo goes when clicked (default: the sponsor's page)
//   names="yes"                    show the name under the logo
//   autoplay="yes"  speed="4"      carousel only (seconds per step)
//
add_shortcode( 'conboard_sponsors', function ( $atts ) {

	$atts = shortcode_atts([
		'layout'	=> 'levels',
		'level'		=> '',
		'columns'	=> 4,
		'limit'		=> 0,
		'link'		=> 'page',
		'names'		=> 'no',
		'autoplay'	=> 'yes',
		'speed'		=> 4,
	], $atts, 'conboard_sponsors' );

	return conboard_render_sponsors([
		'layout'		=> in_array( $atts['layout'], [ 'levels', 'grid', 'carousel' ], true ) ? $atts['layout'] : 'levels',
		'levels'		=> conboard_parse_key_list( $atts['level'] ),
		'columns'		=> (int) $atts['columns'],
		'limit'			=> (int) $atts['limit'],
		'link'			=> in_array( $atts['link'], [ 'page', 'website', 'none' ], true ) ? $atts['link'] : 'page',
		'show_names'	=> conboard_is_truthy( $atts['names'] ),
		'autoplay'		=> conboard_is_truthy( $atts['autoplay'] ),
		'speed'			=> (int) $atts['speed'],
	]);
});


//
// [conboard_presenters] - presenters anywhere
//
//   featured="yes"   only presenters marked Featured
//   keynote="yes"    only presenters marked Keynote Speaker (with both, either one matches)
//   layout="grid|carousel"  columns="3"  limit="6"  titles="yes|no"
//   autoplay="yes"  speed="5"   carousel only
//
add_shortcode( 'conboard_presenters', function ( $atts ) {

	$atts = shortcode_atts([
		'featured'	=> 'no',
		'keynote'	=> 'no',
		'layout'	=> 'grid',
		'columns'	=> 3,
		'limit'		=> 0,
		'titles'	=> 'yes',
		'autoplay'	=> 'yes',
		'speed'		=> 5,
	], $atts, 'conboard_presenters' );

	return conboard_render_presenters([
		'featured'		=> conboard_is_truthy( $atts['featured'] ),
		'keynote'		=> conboard_is_truthy( $atts['keynote'] ),
		'layout'		=> $atts['layout'] === 'carousel' ? 'carousel' : 'grid',
		'columns'		=> (int) $atts['columns'],
		'limit'			=> (int) $atts['limit'],
		'show_title'	=> conboard_is_truthy( $atts['titles'] ),
		'autoplay'		=> conboard_is_truthy( $atts['autoplay'] ),
		'speed'			=> (int) $atts['speed'],
	]);
});


//
// [conboard_featured_speaker] - spotlight one presenter (great for the home page)
//
//   id="123"         a specific presenter
//   random="yes"     otherwise: pick randomly from the keynote speakers (or featured presenters)
//
add_shortcode( 'conboard_featured_speaker', function ( $atts ) {

	$atts = shortcode_atts([
		'id'		=> 0,
		'random'	=> 'no',
	], $atts, 'conboard_featured_speaker' );

	$presenter = conboard_get_featured_presenter( (int) $atts['id'], conboard_is_truthy( $atts['random'] ) );

	return $presenter ? conboard_render_featured_speaker( $presenter ) : '';
});
