<?php
// includes/admin/admin_links_metabox.php
//
// "Links" box on presenters, sponsors and staff, built on the row editor.
// Storage and reading are in includes/links.php.

if ( ! defined( 'ABSPATH' ) ) exit;

function conboard_get_link_editor_fields(): array {
	return [
		'platform'	=> [ 'label' => 'Type', 'type' => 'select', 'choices' => conboard_get_link_types(), 'default' => 'other' ],
		'url'		=> [ 'label' => 'URL', 'type' => 'url', 'required' => true ],
		'label'		=> [ 'label' => 'Link text (optional)', 'type' => 'text' ],
	];
}

add_action( 'add_meta_boxes', function () {

	foreach ( conboard_link_post_types() as $post_type ) {
		add_meta_box(
			'conboard_links',
			'Links',
			function ( WP_Post $post ) {

				wp_nonce_field( 'conboard_links', 'conboard_links_nonce' );

				echo '<p class="description">Fill in the ones that apply and leave the rest blank; blank links are not shown. Social sites show as @handle unless you set link text.</p>';

				conboard_render_row_editor( 'conboard_links', conboard_get_links_for_editor( $post->ID ), conboard_get_link_editor_fields(), [
					'add_label'	=> 'Add Link',
					'empty'		=> 'No links added yet.',
				] );
			},
			$post_type,
			'normal',
			'default'
		);
	}
});

add_action( 'save_post', function ( $post_id, $post ) {

	if ( ! in_array( $post->post_type, conboard_link_post_types(), true ) ) {
		return;
	}

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['conboard_links_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conboard_links_nonce'] ) ), 'conboard_links' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	conboard_save_links(
		(int) $post_id,
		conboard_sanitize_rows( wp_unslash( $_POST['conboard_links'] ?? [] ), conboard_get_link_editor_fields() )
	);

}, 5, 2 );
