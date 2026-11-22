<?php
// includes/templates.php

add_filter('template_include', function ($template) {

	if (!is_singular() && !is_post_type_archive()) {
		return $template;
	}

	$post_type = get_post_type();

	// Keys are the registered post type slugs (see includes/post-types/).
	// Only templates that are ready for use are mapped here. Display Boards are not
	// mapped yet (they belong to Screen Mode), so the theme's own templates handle them.
	$templates = [
		'presenter' => [
			'single'	=> 'single-conboard_presenter.php',
			'archive'	=> 'archive-conboard_presenter.php',
		],
		'announcement' => [
			'single'	=> 'single-conboard_announcement.php',
			'archive'	=> 'archive-conboard_announcement.php',
		],
		'location' => [
			'single'	=> 'single-conboard_location.php',
			'archive'	=> 'archive-conboard_location.php',
		],
		'staff' => [
			'single'	=> 'single-conboard_staff.php',
			'archive'	=> 'archive-conboard_staff.php',
		],
		'sponsor' => [
			'single'	=> 'single-conboard_sponsor.php',
			'archive'	=> 'archive-conboard_sponsor.php',
		],
		'session' => [
			'single'	=> 'single-conboard_session.php',
			'archive'	=> 'archive-conboard_session.php',
		],
	];

	if (!isset($templates[$post_type])) {
		return $template;
	}

	$file = '';

	if (is_singular() && !empty($templates[$post_type]['single'])) {
		$file = $templates[$post_type]['single'];
	} elseif (is_post_type_archive() && !empty($templates[$post_type]['archive'])) {
		$file = $templates[$post_type]['archive'];
	}

	if ($file) {
		$located = conboard_locate_template($file);
		if ($located) {
			return $located;
		}
	}

	return $template;
});


// ---------------------------------------------------------------------------
// Page frame: classic themes and block themes
// ---------------------------------------------------------------------------
//
// Classic themes have header.php and footer.php, so get_header() and get_footer() do the job.
// Block themes (Twenty Twenty-Five and most new sites) have neither: their header and footer are
// "template parts". So on a block theme the page is built here instead: the document head, the
// theme's own header part, a wrapper with the theme's page padding and width, and the footer part.
// The ConBoard templates only fill in what goes between them.

function conboard_is_block_theme(): bool {
	return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() && function_exists( 'block_template_part' );
}

function conboard_template_header(): void {

	if ( ! conboard_is_block_theme() ) {
		get_header();
		return;
	}

	?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<?php block_template_part( 'header' ); ?>
<div class="conboard-block-page wp-block-group has-global-padding is-layout-constrained">
<?php
}

function conboard_template_footer(): void {

	if ( ! conboard_is_block_theme() ) {
		get_footer();
		return;
	}

	?>
</div>
<?php block_template_part( 'footer' ); ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php
}
