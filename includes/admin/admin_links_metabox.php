<?php
// includes/admin/admin_links_metabox.php
//
// "Social & Web Links" box on presenters, sponsors and staff, built on the row editor.
// Storage and reading are in includes/links.php.

if ( ! defined( 'ABSPATH' ) ) exit;

function conboard_get_social_editor_fields(): array {
	return [
		'platform'	=> [ 'label' => 'Platform', 'type' => 'select', 'choices' => [ '' => '— Other —' ] + conboard_get_social_platforms() ],
		'url'		=> [ 'label' => 'URL', 'type' => 'url', 'required' => true ],
		'label'		=> [ 'label' => 'Label (optional)', 'type' => 'text' ],
	];
}

function conboard_get_link_editor_fields(): array {
	return [
		'url'	=> [ 'label' => 'URL', 'type' => 'url', 'required' => true ],
		'label'	=> [ 'label' => 'Label (optional)', 'type' => 'text' ],
	];
}

add_action( 'add_meta_boxes', function () {

	foreach ( conboard_link_post_types() as $post_type ) {
		add_meta_box(
			'conboard_links',
			'Social & Web Links',
			function ( WP_Post $post ) {

				wp_nonce_field( 'conboard_links', 'conboard_links_nonce' );

				echo '<h4>Social media</h4>';
				conboard_render_row_editor( 'conboard_socials', conboard_get_socials( $post->ID ), conboard_get_social_editor_fields(), [
					'add_label'	=> 'Add Social',
					'empty'		=> 'No social links added yet.',
				] );

				echo '<h4>Web links</h4>';
				conboard_render_row_editor( 'conboard_links', conboard_get_links( $post->ID ), conboard_get_link_editor_fields(), [
					'add_label'	=> 'Add Link',
					'empty'		=> 'No web links added yet.',
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
		conboard_sanitize_rows( wp_unslash( $_POST['conboard_socials'] ?? [] ), conboard_get_social_editor_fields() ),
		conboard_sanitize_rows( wp_unslash( $_POST['conboard_links'] ?? [] ), conboard_get_link_editor_fields() )
	);

}, 5, 2 );
