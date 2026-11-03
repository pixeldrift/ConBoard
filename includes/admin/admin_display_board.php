<?php
// includes/admin/admin_display_board.php
//
// The Display Board editor: a Settings box (physical defaults and playback) and a Slides box
// (the row editor). Storage and slide types are in includes/display/display_slides.php.
// The "Display Screen" box with the board's link is added by admin_screen_link.php.

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes_display_board', function () {

	add_meta_box( 'conboard_board_slides', 'Slides', 'conboard_render_board_slides_metabox', 'display_board', 'normal', 'high' );
	add_meta_box( 'conboard_board_settings', 'Board Settings', 'conboard_render_board_settings_metabox', 'display_board', 'side', 'default' );
});

function conboard_render_board_slides_metabox( WP_Post $post ): void {

	wp_nonce_field( 'conboard_board', 'conboard_board_nonce' );

	echo '<p class="description">Slides play in this order and then start over. A slide with 0 seconds uses the board default. Slides that have nothing to show (for example, Announcements when there are none) are skipped. Save, then use the Display Screen box to open the board.</p>';

	conboard_render_row_editor(
		'conboard_slides',
		conboard_slides_to_rows( conboard_get_board_slides( $post->ID ) ),
		conboard_get_slide_editor_fields(),
		[
			'add_label'	=> 'Add Slide',
			'empty'		=> 'No slides yet.',
			'stacked'	=> true,
		]
	);
}

function conboard_render_board_settings_metabox( WP_Post $post ): void {

	$s = conboard_get_board_settings( $post->ID );

	$select = function ( string $name, array $choices, string $current ) {
		echo '<select name="conboard_board[' . esc_attr( $name ) . ']" style="width:100%">';
		foreach ( $choices as $value => $label ) {
			echo '<option value="' . esc_attr( (string) $value ) . '"' . selected( (string) $value, $current, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	};

	echo '<p class="description">These are the defaults for every screen showing this board. A screen can still be corrected from its own link or menu without changing them.</p>';

	echo '<p><label>Orientation<br>';
	$select( 'orientation', [ 'landscape' => 'Landscape', 'portrait' => 'Portrait' ], (string) $s['orientation'] );
	echo '</label></p>';

	echo '<p><label>Rotate<br>';
	$select( 'rotate', [ '0' => 'None', '90' => '90° right', '180' => '180°', '270' => '90° left' ], (string) $s['rotate'] );
	echo '</label></p>';

	echo '<p><label>Flip<br>';
	$select( 'flip', [ 'none' => 'None', 'horizontal' => 'Horizontal', 'vertical' => 'Vertical' ], (string) $s['flip'] );
	echo '</label></p>';

	echo '<p><label>Default seconds per slide<br><input type="number" name="conboard_board[interval]" min="3" max="300" value="' . esc_attr( (string) $s['interval'] ) . '" class="small-text"></label></p>';

	echo '<p><label>Transition<br>';
	$select( 'transition', [ 'fade' => 'Fade', 'none' => 'None' ], (string) $s['transition'] );
	echo '</label></p>';

	echo '<p><label>Refresh data every (seconds)<br><input type="number" name="conboard_board[refresh]" min="15" max="3600" value="' . esc_attr( (string) $s['refresh'] ) . '" class="small-text"></label></p>';

	foreach ( [ 'clock' => 'Show the clock', 'qr' => 'Show the QR code' ] as $key => $label ) {
		echo '<p><input type="hidden" name="conboard_board[' . esc_attr( $key ) . ']" value="0"><label><input type="checkbox" name="conboard_board[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '> ' . esc_html( $label ) . '</label></p>';
	}
}

add_action( 'save_post_display_board', function ( $post_id ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['conboard_board_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conboard_board_nonce'] ) ), 'conboard_board' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$rows = conboard_sanitize_rows( wp_unslash( $_POST['conboard_slides'] ?? [] ), conboard_get_slide_editor_fields() );
	conboard_save_board_slides( (int) $post_id, conboard_rows_to_slides( $rows ) );

	$settings = wp_unslash( $_POST['conboard_board'] ?? [] );
	update_post_meta( $post_id, '_conboard_board', conboard_sanitize_board_settings( is_array( $settings ) ? $settings : [] ) );
});
