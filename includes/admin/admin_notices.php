<?php
// includes/admin/admin_notices.php


// Alert to install the Advanced Custom Fields plugin
function conboard_acf_missing_notice() {
	if ( function_exists('acf_add_local_field_group') ) {
		return;
	}

	echo '<div class="notice notice-error"><p>
		<strong>ConBoard requires <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields</a>.</strong>
		Please install and activate the ACF plugin.
	</p></div>';
}

// Show ConBoard post type descriptions at top of list tables
add_action('all_admin_notices', function () {

	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}

	// Only post list screens
	if ( $screen->base !== 'edit' ) {
		return;
	}

	// Only ConBoard CPTs
	$desc = conboard_get_post_type_description( $screen->post_type );

	if ( ! $desc ) {
		return;
	}

	echo '<div class="notice notice-info conboard-description">';
	echo '<p>' . esc_html( $desc ) . '</p>';
	echo '</div>';
});

// Style post-type description to not look like an error alert
add_action('admin_head', function () {
	echo '<style>
		.conboard-description {
			border-left-color: #2271b1;
			background: #f0f6fc;
		}
		.conboard-description p {
			font-size: 14px;
			margin: 0.5em 0;
		}
	</style>';
});



/**
 * Titles on Add New pages.
 *
 * The block editor has no "Add New Session" heading, so the title field says what is being
 * added (it feeds both editors), and the classic editor also gets the heading above the form.
 */
function conboard_get_post_types(): array {
	return [ 'session', 'presenter', 'location', 'sponsor', 'staff', 'announcement', 'display_board' ];
}

add_filter( 'enter_title_here', function ( $text, $post ) {

	if ( ! $post || ! in_array( $post->post_type, conboard_get_post_types(), true ) ) {
		return $text;
	}

	$type = get_post_type_object( $post->post_type );
	if ( ! $type ) return $text;

	$singular = strtolower( $type->labels->singular_name );

	return sprintf( 'Add %s name', $singular );
}, 10, 2 );

add_action( 'edit_form_top', function ( $post ) {

	global $pagenow;
	if ( $pagenow !== 'post-new.php' || ! $post || ! in_array( $post->post_type, conboard_get_post_types(), true ) ) return;

	$type = get_post_type_object( $post->post_type );
	if ( ! $type ) return;

	echo '<h1 class="wp-heading-inline conboard-add-new-title">' . esc_html( $type->labels->add_new_item ) . '</h1>';
} );
