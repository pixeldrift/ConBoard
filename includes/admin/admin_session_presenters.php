<?php
// includes/admin/admin_session_presenters.php
//
// The Presenters box on a Session: pick presenters and give each a role.
// Uses ConBoard's own row editor, so it works with the free version of ACF.
// Storage is handled by conboard_save_session_presenters() in includes/helpers.php.

if ( ! defined( 'ABSPATH' ) ) exit;

function conboard_get_session_presenter_editor_fields(): array {

	$choices = [ '' => '— Select a presenter —' ];

	$presenters = get_posts([
		'post_type'			=> 'presenter',
		'post_status'		=> [ 'publish', 'draft', 'pending', 'private', 'future' ],
		'posts_per_page'	=> -1,
		'orderby'			=> 'title',
		'order'				=> 'ASC',
	]);

	foreach ( $presenters as $presenter ) {
		$title = $presenter->post_title !== '' ? $presenter->post_title : '(no name) #' . $presenter->ID;
		$choices[ $presenter->ID ] = $title;
	}

	return [
		'presenter'	=> [ 'label' => 'Presenter', 'type' => 'select', 'choices' => $choices, 'required' => true ],
		'role'		=> [
			'label'		=> 'Role',
			'type'		=> 'select',
			'choices'	=> conboard_get_presenter_role_choices(),
			'default'	=> conboard_get_default_presenter_role(),
		],
	];
}

add_action( 'add_meta_boxes_session', function () {

	add_meta_box(
		'conboard_session_presenters',
		'Presenters',
		function ( WP_Post $post ) {

			wp_nonce_field( 'conboard_session_presenters', 'conboard_session_presenters_nonce' );

			$rows = [];
			foreach ( conboard_get_session_presenters( $post->ID ) as $entry ) {
				$rows[] = [ 'presenter' => (string) $entry['presenter_id'], 'role' => $entry['role'] ];
			}

			echo '<p class="description">Add presenters and choose their role in this session.</p>';

			conboard_render_row_editor( 'conboard_presenters', $rows, conboard_get_session_presenter_editor_fields(), [
				'add_label'	=> 'Add Presenter',
				'empty'		=> 'No presenters added yet.',
			] );
		},
		'session',
		'normal',
		'default'
	);
});

// Priority 5: must run before ACF saves (priority 10) and before the counts are
// recalculated on acf/save_post (priority 20).
add_action( 'save_post_session', function ( $post_id ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['conboard_session_presenters_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conboard_session_presenters_nonce'] ) ), 'conboard_session_presenters' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$rows = conboard_sanitize_rows( wp_unslash( $_POST['conboard_presenters'] ?? [] ), conboard_get_session_presenter_editor_fields() );

	conboard_save_session_presenters( (int) $post_id, $rows );

}, 5 );
