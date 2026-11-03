<?php
// includes/admin/admin_list_icons.php

add_action('admin_head', function () {

	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'edit' ) {
		return;
	}

	$post_type = $screen->post_type ?? null;
	if ( ! $post_type ) {
		return;
	}

	// Your CPT config lookup
	if ( empty( CONBOARD_ADMIN_PAGES[ $post_type ] ) ) {
		return;
	}

	$page = CONBOARD_ADMIN_PAGES[ $post_type ];

	if ( empty( $page['icon_code'] ) ) {
		return;
	}

	$icon = esc_attr( $page['icon_code'] );

	echo "<style>
		.wrap h1.wp-heading-inline::before {
			content: '\\{$icon}';
			margin-right: 10px;
			font-size: 30px;
			vertical-align: middle;
			display: inline-block;
			line-height: 1;
		}
	</style>";
});
