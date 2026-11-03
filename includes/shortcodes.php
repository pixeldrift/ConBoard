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
				'key		=> 'location',
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
