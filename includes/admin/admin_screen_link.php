<?php
// includes/admin/admin_screen_link.php
//
// "Get Display Screen": the Screen Mode link for a post (a box on every ConBoard post) and
// for the list pages (a section on the ConBoard Dashboard), with options for orientation,
// rotation and flip. The link is built in the browser by assets/js/admin_screen_link.js.

if ( ! defined( 'ABSPATH' ) ) exit;


// ---------------------------------------------------------------------------
// Scripts
// ---------------------------------------------------------------------------

add_action( 'admin_enqueue_scripts', function ( $hook ) {

	$screen     = get_current_screen();
	$on_post    = $screen && in_array( $screen->post_type, conboard_screen_post_types(), true ) && in_array( $hook, [ 'post.php', 'post-new.php' ], true );
	$on_dashboard = strpos( (string) $hook, 'conboard_dashboard' ) !== false;

	if ( ! $on_post && ! $on_dashboard ) {
		return;
	}

	wp_enqueue_script(
		'conboard-admin-screen-link',
		CONBOARD_PLUGIN_URL . 'assets/js/admin_screen_link.js',
		[],
		'0.1.0',
		true
	);
});


// ---------------------------------------------------------------------------
// Building blocks
// ---------------------------------------------------------------------------

// Orientation / rotate / flip choices. One set applies to every link in the same box.
function conboard_get_screen_link_options_html(): string {

	ob_start();
	?>
	<div class="conboard-screen-options">

		<p>
			<label>Orientation<br>
				<select data-param="orientation" style="width:100%">
					<option value="landscape">Landscape</option>
					<option value="portrait">Portrait</option>
				</select>
			</label>
		</p>

		<p>
			<label>Rotate<br>
				<select data-param="rotate" style="width:100%">
					<option value="0">None</option>
					<option value="90">90° right</option>
					<option value="180">180°</option>
					<option value="270">90° left</option>
				</select>
			</label>
		</p>

		<p>
			<label>Flip<br>
				<select data-param="flip" style="width:100%">
					<option value="none">None</option>
					<option value="horizontal">Horizontal</option>
					<option value="vertical">Vertical</option>
				</select>
			</label>
		</p>

	</div>
	<?php

	return (string) ob_get_clean();
}

// One link: optional title and description, the address, Copy and Open buttons
function conboard_get_screen_link_row_html( string $base_url, string $label = '', string $description = '' ): string {

	ob_start();
	?>
	<div class="conboard-screen-row" data-base="<?php echo esc_attr( $base_url ); ?>">

		<?php if ( $label ) : ?>
			<strong><?php echo esc_html( $label ); ?></strong>
		<?php endif; ?>

		<?php if ( $description ) : ?>
			<span class="description"><?php echo esc_html( $description ); ?></span>
		<?php endif; ?>

		<p>
			<input type="text" class="conboard-screen-url widefat" readonly onfocus="this.select()">
		</p>

		<p>
			<button type="button" class="button button-primary conboard-screen-copy">Copy link</button>
			<a class="button conboard-screen-open" href="#" target="_blank" rel="noopener">Open</a>
		</p>

	</div>
	<?php

	return (string) ob_get_clean();
}


// ---------------------------------------------------------------------------
// Box on every ConBoard post
// ---------------------------------------------------------------------------

add_action( 'add_meta_boxes', function () {

	foreach ( conboard_screen_post_types() as $post_type ) {
		add_meta_box(
			'conboard_screen_link',
			'Display Screen',
			'conboard_render_screen_link_metabox',
			$post_type,
			'side',
			'default'
		);
	}
});

function conboard_render_screen_link_metabox( WP_Post $post ): void {

	$base = get_permalink( $post );

	if ( ! $base ) {
		echo '<p>Save this first to get its display link.</p>';
		return;
	}

	echo '<div class="conboard-screen-link">';
	echo '<p class="description">Show this page full screen on a TV or projector. Anyone with the link can use it, no login needed.</p>';
	echo conboard_get_screen_link_options_html(); // Static markup
	echo conboard_get_screen_link_row_html( $base ); // Escaped inside

	if ( $post->post_status !== 'publish' ) {
		echo '<p class="description">This is not published yet. Publish it to get its final link.</p>';
	}

	echo '</div>';
}


// ---------------------------------------------------------------------------
// Dashboard section: the list pages
// ---------------------------------------------------------------------------

// The list pages that can be shown on a screen
function conboard_get_screen_list_pages(): array {

	$pages = [
		'session'		=> [ 'Schedule',		'What is on now and what is next, a few sessions per page.' ],
		'presenter'		=> [ 'Presenters',		'Presenter photos and titles, a page at a time.' ],
		'sponsor'		=> [ 'Sponsors',		'A looping sponsor display, a page for each sponsor level.' ],
		'staff'			=> [ 'Staff',			'Staff photos and roles.' ],
		'announcement'	=> [ 'Announcements',	'Current announcements, one per page, urgent ones first.' ],
		'location'		=> [ 'Locations',		'A simple list of locations to help people find their way.' ],
	];

	$out = [];

	foreach ( $pages as $post_type => $info ) {
		$url = get_post_type_archive_link( $post_type );

		if ( $url ) {
			$out[] = [ 'label' => $info[0], 'description' => $info[1], 'url' => $url ];
		}
	}

	return $out;
}

function conboard_render_screen_links_section(): void {

	$pages = conboard_get_screen_list_pages();

	if ( empty( $pages ) ) {
		return;
	}

	?>
	<div class="conboard-screen-link conboard-screen-links-section" style="text-align:left;max-width:900px;margin:32px auto;">

		<h2>Screen links for list pages</h2>

		<p class="description">
			Open a link on any TV or projector, or copy it for whoever is setting up the screens.
			The options below apply to every link. Individual pages (a room, a speaker, a session) have
			their own link in the Display Screen box when you edit them.
		</p>

		<?php echo conboard_get_screen_link_options_html(); // Static markup ?>

		<?php foreach ( $pages as $page ) : ?>
			<?php echo conboard_get_screen_link_row_html( $page['url'], $page['label'], $page['description'] ); // Escaped inside ?>
		<?php endforeach; ?>

	</div>
	<?php
}
