<?php
// includes/admin/admin_export_boards.php
//
// "Export all boards as ZIP" on the Display Boards list. Each board is opened in a hidden frame,
// saved as 1920x1080 JPGs there (see assets/js/signage.js, ?export=data), and the pictures are
// packed into one ZIP in the browser: a folder per board, or everything in the root for players
// that read a flat thumb drive.

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_enqueue_scripts', function ( $hook ) {

	if ( $hook !== 'edit.php' ) return;

	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'display_board' ) return;

	$boards = [];
	foreach ( get_posts( [ 'post_type' => 'display_board', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] ) as $b ) {
		$boards[] = [
			'id'    => (int) $b->ID,
			'title' => get_the_title( $b ),
			'url'   => add_query_arg( [ 'display' => 'screen', 'export' => 'data' ], get_permalink( $b ) ),
		];
	}

	wp_enqueue_script( 'conboard-zip', CONBOARD_PLUGIN_URL . 'assets/js/zip_store.js', [], '0.1.0', true );
	wp_enqueue_script( 'conboard-admin-export-boards', CONBOARD_PLUGIN_URL . 'assets/js/admin_export_boards.js', [ 'conboard-zip' ], '0.1.0', true );
	wp_localize_script( 'conboard-admin-export-boards', 'CONBOARD_EXPORT', [ 'boards' => $boards ] );
} );

add_action( 'all_admin_notices', function () {

	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== 'display_board' ) return;

	?>
	<div class="notice notice-info conboard-export-boards" style="padding:10px 14px">
		<strong>Export all boards as pictures</strong>
		<p style="margin:6px 0">Saves every published board as 1920x1080 JPG pictures in one ZIP file, ready for a digital signage player or a thumb drive.</p>
		<p style="margin:6px 0">
			<label style="margin-right:16px"><input type="radio" name="conboard-export-layout" value="folders" checked> A folder for each board</label>
			<label><input type="radio" name="conboard-export-layout" value="flat"> All pictures in the root (file names start with the board name)</label>
		</p>
		<p style="margin:6px 0">
			<button type="button" class="button button-primary" id="conboard-export-boards">Export all boards (ZIP)</button>
			<span id="conboard-export-status" style="margin-left:10px"></span>
		</p>
	</div>
	<?php
} );
