<?php
// includes/admin/admin_screens.php
//
// Screens (the physical displays): the editor boxes, list columns, and the Screen Assignments
// page, where a board can be dragged onto a screen card or a screen card dropped onto a board.

if ( ! defined( 'ABSPATH' ) ) exit;

conboard_rename_title_column( 'venue_screen', 'Screen Name' );


// ---------------------------------------------------------------------------
// Editor
// ---------------------------------------------------------------------------

add_action( 'add_meta_boxes_venue_screen', function () {
	add_meta_box( 'conboard_screen_details', 'Screen Details', 'conboard_render_screen_details_metabox', 'venue_screen', 'normal', 'high' );
	add_meta_box( 'conboard_screen_address', 'Permanent Address', 'conboard_render_screen_address_metabox', 'venue_screen', 'side', 'high' );
});

function conboard_screen_select( string $name, array $choices, string $current ): string {

	$html = '<select name="conboard_screen[' . esc_attr( $name ) . ']">';

	foreach ( $choices as $value => $label ) {
		$html .= '<option value="' . esc_attr( (string) $value ) . '"' . selected( (string) $value, $current, false ) . '>' . esc_html( $label ) . '</option>';
	}

	return $html . '</select>';
}

function conboard_render_screen_details_metabox( WP_Post $post ): void {

	$s = conboard_get_screen_settings( $post->ID );

	wp_nonce_field( 'conboard_screen', 'conboard_screen_nonce' );

	$boards = [ 0 => '— Nothing —' ] + conboard_get_post_choices( 'display_board' );
	$same   = [ '' => 'Same as the board' ];

	echo '<table class="form-table" role="presentation">';

	echo '<tr><th>Display Board</th><td>' . conboard_screen_select( 'board', $boards, (string) $s['board'] ) . '<p class="description">What this screen shows. Change it any time, even while the screen is on: the screen picks it up by itself.</p></td></tr>';

	echo '<tr><th>Location</th><td>' . conboard_screen_select( 'location', [ 0 => '— Not set —' ] + conboard_get_post_choices( 'location' ), (string) $s['location'] ) . '<p class="description">Where this screen is mounted.</p></td></tr>';

	echo '<tr><th>Player</th><td>' . conboard_screen_select( 'unit', conboard_screen_unit_types(), (string) $s['unit'] ) . '<p class="description">What is plugged in or built in, for reference.</p></td></tr>';

	echo '<tr><th>Resolution</th><td><input type="text" name="conboard_screen[resolution]" list="conboard-resolutions" value="' . esc_attr( $s['resolution'] ) . '" placeholder="1920x1080" class="regular-text"><datalist id="conboard-resolutions">';
	foreach ( conboard_screen_resolution_presets() as $preset ) {
		echo '<option value="' . esc_attr( $preset ) . '">';
	}
	echo '</datalist></td></tr>';

	echo '<tr><th>Orientation</th><td>' . conboard_screen_select( 'orientation', $same + [ 'landscape' => 'Landscape', 'portrait' => 'Portrait' ], (string) $s['orientation'] ) . '</td></tr>';
	echo '<tr><th>Rotate</th><td>' . conboard_screen_select( 'rotate', $same + [ '0' => 'None', '90' => '90° right', '180' => '180°', '270' => '90° left' ], (string) $s['rotate'] ) . '<p class="description">For a TV mounted sideways or upside down. Only this screen is affected.</p></td></tr>';
	echo '<tr><th>Flip</th><td>' . conboard_screen_select( 'flip', $same + [ 'none' => 'None', 'horizontal' => 'Horizontal', 'vertical' => 'Vertical' ], (string) $s['flip'] ) . '</td></tr>';

	$yesno = $same + [ 'yes' => 'Show', 'no' => 'Hide' ];
	echo '<tr><th>Clock</th><td>' . conboard_screen_select( 'clock', $yesno, (string) $s['clock'] ) . '</td></tr>';
	echo '<tr><th>QR code</th><td>' . conboard_screen_select( 'qr', $yesno, (string) $s['qr'] ) . '</td></tr>';

	echo '<tr><th>Notes</th><td><textarea name="conboard_screen[notes]" rows="3" class="large-text">' . esc_textarea( $s['notes'] ) . '</textarea><p class="description">Mounting height, remote location, who has the key to the cabinet, and so on. Use the Reference photo box to attach a picture.</p></td></tr>';

	echo '</table>';
}

function conboard_render_screen_address_metabox( WP_Post $post ): void {

	$url = get_permalink( $post );

	if ( $post->post_status !== 'publish' || ! $url ) {
		echo '<p>Publish this screen to get its address.</p>';
		return;
	}

	echo '<p class="description">Open this address on the device once. It always shows whatever board is assigned above, so the device never needs to be touched again.</p>';
	echo '<p><input type="text" class="widefat" readonly onfocus="this.select()" value="' . esc_attr( $url ) . '"></p>';
	echo '<p><a class="button" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Open</a></p>';
	echo '<p class="description">Anything added to the address (for example <code>?rotate=180</code>) still overrides this screen, for that one visit only.</p>';
}

add_action( 'save_post_venue_screen', function ( $post_id ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['conboard_screen_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conboard_screen_nonce'] ) ), 'conboard_screen' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$raw = wp_unslash( $_POST['conboard_screen'] ?? [] );
	update_post_meta( $post_id, '_conboard_screen', conboard_sanitize_screen_settings( is_array( $raw ) ? $raw : [] ) );
});


// ---------------------------------------------------------------------------
// List columns
// ---------------------------------------------------------------------------

conboard_register_admin_column( 'venue_screen', [
	'key' => 'screen_board', 'label' => 'Display Board', 'position' => 'after:title',
	'render' => function ( $id ) {
		$board = conboard_get_screen_board_id( (int) $id );
		echo $board ? esc_html( get_the_title( $board ) ) : '—';
	},
]);

conboard_register_admin_column( 'venue_screen', [
	'key' => 'screen_location', 'label' => 'Location', 'position' => 'after:title',
	'render' => function ( $id ) {
		$s = conboard_get_screen_settings( (int) $id );
		echo $s['location'] ? esc_html( get_the_title( $s['location'] ) ) : '—';
	},
]);

conboard_register_admin_column( 'venue_screen', [
	'key' => 'screen_details', 'label' => 'Resolution / Player', 'position' => 'last',
	'render' => function ( $id ) {
		$s    = conboard_get_screen_settings( (int) $id );
		$bits = array_filter([ $s['resolution'], conboard_screen_unit_types()[ $s['unit'] ] ?? '' ]);
		echo $bits ? esc_html( implode( ' · ', $bits ) ) : '—';
	},
]);


// ---------------------------------------------------------------------------
// Screen Assignments page
// ---------------------------------------------------------------------------

add_action( 'admin_menu', function () {
	add_submenu_page(
		'conboard',
		'Screen Assignments',
		'Screen Assignments',
		'edit_others_posts',
		'conboard_screens',
		'conboard_screens_page'
	);
});

add_action( 'admin_enqueue_scripts', function ( $hook ) {

	if ( strpos( (string) $hook, 'conboard_screens' ) === false ) {
		return;
	}

	wp_enqueue_script( 'conboard-screens', CONBOARD_PLUGIN_URL . 'assets/js/admin_screens.js', [], '0.1.0', true );
	wp_localize_script( 'conboard-screens', 'CONBOARD_SCREENS', [
		'root'  => esc_url_raw( rest_url( 'conboard/v1/screens/' ) ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
	]);
});

function conboard_screens_page(): void {

	$cards  = conboard_get_screen_cards();
	$boards = get_posts([ 'post_type' => 'display_board', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ]);

	$counts = [];
	foreach ( $cards as $card ) {
		if ( $card['board'] ) {
			$counts[ $card['board'] ] = ( $counts[ $card['board'] ] ?? 0 ) + 1;
		}
	}

	?>
	<div class="wrap conboard-screens-wrap">
		<h1 class="wp-heading-inline">Screen Assignments</h1>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=venue_screen' ) ); ?>" class="page-title-action">Add Screen</a>
		<hr class="wp-header-end">
		<p class="description">Drag a Display Board from the side onto a screen, or drag a screen onto a board. You can also pick a board from the list on each screen. Screens pick up the change by themselves within a minute or so.</p>

		<div class="cbs-layout">

			<div class="cbs-grid" id="cbs-grid">
				<?php if ( ! $cards ) : ?>
					<p>No screens yet. <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=venue_screen' ) ); ?>">Add the first one</a> from your venue audit.</p>
				<?php endif; ?>

				<?php foreach ( $cards as $card ) :
					$bits = array_filter([
						$card['resolution'] ? str_replace( 'x', '×', $card['resolution'] ) : '',
						$card['rotate'] !== '' && $card['rotate'] !== '0' ? 'rotated ' . $card['rotate'] . '°' : '',
						$card['orientation'] === 'portrait' ? 'portrait' : '',
						$card['unit'] !== '— Not set —' ? $card['unit'] : '',
					]);
					?>
					<div class="cbs-card" draggable="true" data-screen="<?php echo (int) $card['id']; ?>">
						<div class="cbs-photo"<?php echo $card['photo'] ? ' style="background-image:url(' . esc_url( $card['photo'] ) . ')"' : ''; ?>>
							<?php if ( ! $card['photo'] ) : ?><span class="dashicons dashicons-desktop"></span><?php endif; ?>
						</div>
						<div class="cbs-body">
							<a class="cbs-name" href="<?php echo esc_url( $card['edit'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
							<div class="cbs-meta"><?php echo $card['location'] ? esc_html( $card['location'] ) : '<em>No location</em>'; ?></div>
							<div class="cbs-meta"><?php echo $bits ? esc_html( implode( ' · ', $bits ) ) : ''; ?></div>
							<div class="cbs-assigned<?php echo $card['board'] ? '' : ' is-empty'; ?>">
								<span class="cbs-assigned-name"><?php echo $card['board'] ? esc_html( $card['board_title'] ) : 'Nothing assigned'; ?></span>
								<button type="button" class="cbs-clear" title="Unassign" aria-label="Unassign"<?php echo $card['board'] ? '' : ' hidden'; ?>>&times;</button>
							</div>
							<select class="cbs-select" aria-label="Display Board for <?php echo esc_attr( $card['title'] ); ?>">
								<option value="0">— Nothing —</option>
								<?php foreach ( $boards as $board ) : ?>
									<option value="<?php echo (int) $board->ID; ?>"<?php selected( $card['board'], $board->ID ); ?>><?php echo esc_html( get_the_title( $board ) ); ?></option>
								<?php endforeach; ?>
							</select>
							<div class="cbs-links">
								<a href="<?php echo esc_url( $card['address'] ); ?>" target="_blank" rel="noopener">Open</a>
								<a href="#" class="cbs-copy" data-address="<?php echo esc_attr( $card['address'] ); ?>">Copy address</a>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<aside class="cbs-boards" id="cbs-boards">
				<h2>Display Boards</h2>
				<?php foreach ( $boards as $board ) : ?>
					<div class="cbs-board" draggable="true" data-board="<?php echo (int) $board->ID; ?>">
						<span class="dashicons dashicons-welcome-view-site"></span>
						<span class="cbs-board-name"><?php echo esc_html( get_the_title( $board ) ); ?></span>
						<span class="cbs-count" data-count="<?php echo (int) ( $counts[ $board->ID ] ?? 0 ); ?>"></span>
					</div>
				<?php endforeach; ?>
				<?php if ( ! $boards ) : ?>
					<p class="description">No published Display Boards yet.</p>
				<?php endif; ?>
				<div class="cbs-board cbs-none" data-board="0"><span class="dashicons dashicons-dismiss"></span><span class="cbs-board-name">Unassigned</span></div>
				<p class="description">Drop a screen on <strong>Unassigned</strong> to clear it.</p>
			</aside>

		</div>
		<div id="cbs-status" class="cbs-status" aria-live="polite"></div>
	</div>
	<style>
		.cbs-layout { display:flex; gap:24px; align-items:flex-start; margin-top:16px; }
		.cbs-grid { flex:1 1 0; display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; min-width:0; }
		.cbs-card { background:#fff; border:1px solid #c3c4c7; border-radius:8px; overflow:hidden; cursor:grab; display:flex; flex-direction:column; }
		.cbs-card.is-over { outline:3px solid #2271b1; outline-offset:-3px; }
		.cbs-card.is-dragging, .cbs-board.is-dragging { opacity:.45; }
		.cbs-photo { height:130px; background:#f0f0f1 center/cover no-repeat; display:flex; align-items:center; justify-content:center; color:#a7aaad; }
		.cbs-photo .dashicons { font-size:48px; width:48px; height:48px; }
		.cbs-body { padding:10px 12px 12px; display:flex; flex-direction:column; gap:4px; }
		.cbs-name { font-weight:600; font-size:14px; text-decoration:none; }
		.cbs-meta { color:#646970; font-size:12px; min-height:1em; }
		.cbs-assigned { display:flex; align-items:center; justify-content:space-between; gap:6px; margin-top:6px; padding:6px 8px; background:#e7f1fa; border-radius:4px; font-weight:600; font-size:13px; }
		.cbs-assigned.is-empty { background:#f6f7f7; color:#8c8f94; font-weight:400; font-style:italic; }
		.cbs-clear { border:0; background:none; cursor:pointer; font-size:18px; line-height:1; color:#8c8f94; padding:0 2px; }
		.cbs-clear:hover { color:#d63638; }
		.cbs-select { width:100%; margin-top:4px; }
		.cbs-links { display:flex; gap:12px; font-size:12px; margin-top:4px; }
		.cbs-boards { flex:0 0 240px; position:sticky; top:48px; background:#fff; border:1px solid #c3c4c7; border-radius:8px; padding:4px 12px 12px; }
		.cbs-board { display:flex; align-items:center; gap:8px; padding:8px 10px; margin-bottom:6px; background:#f6f7f7; border:1px solid #dcdcde; border-radius:6px; cursor:grab; }
		.cbs-board.is-over { outline:3px solid #2271b1; outline-offset:-3px; }
		.cbs-board .cbs-board-name { flex:1 1 auto; font-weight:600; }
		.cbs-none { cursor:default; border-style:dashed; background:#fff; color:#646970; }
		.cbs-count { font-size:11px; background:#2271b1; color:#fff; border-radius:10px; padding:1px 7px; }
		.cbs-count:empty { display:none; }
		.cbs-status { position:fixed; bottom:24px; right:24px; background:#1d2327; color:#fff; padding:8px 14px; border-radius:6px; opacity:0; transition:opacity .2s; pointer-events:none; }
		.cbs-status.is-visible { opacity:1; }
		.cbs-status.is-error { background:#b32d2e; }
		@media (max-width:900px) { .cbs-layout { flex-direction:column-reverse; } .cbs-boards { position:static; flex-basis:auto; width:100%; box-sizing:border-box; } }
	</style>
	<?php
}
