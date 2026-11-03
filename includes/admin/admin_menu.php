<?php
// includes/admin/admin_menu.php

if ( ! defined('ABSPATH') ) exit;

// List of ConBoard CPTs in the order we want
define('CONBOARD_CPTS', [
	'presenter',
	'session',
	'location',
	'sponsor',
	'staff',
	'display_board',
	'venue_screen',
	'announcement',
]);

/*
icon_code conventions:
- WP Dashicons glyph hex without leading slash
- Example: 'f488' // dashicons-megaphone
- Used for:
  - CPT submenu icons
  - Manual submenu icons
  - Dashboard cards
*/

add_action('admin_menu', function () {

	// Main menu
	add_menu_page(
		'ConBoard',					// Page title
		'ConBoard',					// Menu title
		'manage_options',			// Access requirement
		'conboard',					// Menu slug
		null,						// Callback (submenu handles display)
		'dashicons-calendar-alt',	// Menu icon
		25							// Menu position
	);

	// Dashboard (redirect target)
	add_submenu_page(
		'conboard',					// Parent slug
		'Dashboard',				// Page title
		'Dashboard',				// Menu title
		'manage_options',			// Access requirement
		'conboard_dashboard',		// Menu slug
		'conboard_admin_dashboard'	// Callback 
	);

	// Settings
	add_submenu_page(
		'conboard',					// Parent slug
		'ConBoard Settings',		// Page title
		'Settings',					// Menu title
		'manage_options',			// Access requirement
		'conboard_settings',		// Menu slug
		'conboard_admin_settings'	// Callback 
	);

	// About
	add_submenu_page(
		'conboard',					// Parent slug
		'About ConBoard',			// Page title
		'About ConBoard',			// Menu title
		'manage_options',			// Access requirement
		'conboard_about',			// Menu slug
		'conboard_admin_about'		// Callback 
	);


	add_action('admin_menu', function() {
		global $submenu;

		if ( isset( $submenu['conboard'] ) ) {
			foreach ( $submenu['conboard'] as $i => $item ) {
				if ( $item[2] === 'conboard_dashboard' ) {
					// Add a custom class
					$submenu['conboard'][$i][4] = 'conboard-dashboard-submenu'; // $item[4] is the class
				}
			}
		}
	}, 100);


});



// Enable custom menu ordering
add_filter('custom_menu_order', '__return_true');

// Sort only ConBoard submenu items
add_filter('menu_order', function ( $menu_order ) {

	global $submenu;

	if ( empty( $submenu['conboard'] ) ) {
		return $menu_order;
	}

	// Desired submenu order (USE MENU SLUGS)
	$desired = array_merge(
		[
			'conboard_dashboard', // Dashboard
		],
		array_map(
			fn ( $cpt ) => 'edit.php?post_type=' . $cpt,
			CONBOARD_CPTS
		),
		[
			'conboard_planner',
			'conboard_screens',
			'conboard_settings',
			'conboard_about',
		]
	);

	// Index existing submenu items by slug
	$lookup = [];
	foreach ( $submenu['conboard'] as $item ) {
		$lookup[ $item[2] ] = $item;
	}

	// Build reordered submenu
	$new = [];
	foreach ( $desired as $slug ) {
		if ( isset( $lookup[ $slug ] ) ) {
			$new[] = $lookup[ $slug ];
			unset( $lookup[ $slug ] );
		}
	}

	// Append leftovers (future items, dividers, etc.)
	foreach ( $lookup as $item ) {
		$new[] = $item;
	}

	$submenu['conboard'] = $new;

	return $menu_order;

}, 999);


// Submenu Icons via icon_code
add_action('admin_head', function () {

	$icons = [];

	// CPT submenu icons
	foreach ( CONBOARD_CPTS as $slug ) {
		$obj = get_post_type_object( $slug );
		if ( ! $obj || empty( $obj->icon_code ) ) {
			continue;
		}

		$icons[ 'edit.php?post_type=' . $slug ] = $obj->icon_code;
	}

	// Admin page icons (guarded)
	if ( defined('CONBOARD_ADMIN_PAGES') && is_array(CONBOARD_ADMIN_PAGES) ) {
		foreach ( CONBOARD_ADMIN_PAGES as $slug => $page ) {
			if ( empty( $page['icon_code'] ) ) {
				continue;
			}

			echo "<style>
				#adminmenu .wp-submenu a[href*='page={$slug}']::before {
					font-family: dashicons;
					content: '\\{$page['icon_code']}';
					margin-right: 6px;
					font-size: 16px;
					vertical-align: middle;
					opacity: 0.9;
					display: inline-block;
					width: 16px;
					line-height: 1;
				}
			</style>";
		}
	}


	if ( empty( $icons ) ) {
		return;
	}

	echo '<style>';

	foreach ( $icons as $url => $glyph ) {
		echo "
		#adminmenu .wp-submenu a[href='{$url}']::before {
			font-family: dashicons;
			content: '\\{$glyph}';
			margin-right: 6px;
			font-size: 16px;
			vertical-align: middle;
			opacity: 0.9;
		}";
	}

	echo '</style>';
});

// Hide the Dashboard item from the submenu
// since Conboard main already redirects there
// MUST be injected into head, wont' wor in linked CSS
add_action('admin_head', function () {
	echo '<style>
		#adminmenu .wp-submenu li:has(> a[href*="page=conboard_dashboard"]) {
			display: none !important;
		}
	</style>';
});


// Gray out future Settings page
/*
add_action('admin_head', function () {
	echo '<style>
		#adminmenu a[href="admin.php?page=conboard_settings"] {
			opacity: 0.5;
			pointer-events: none;
		}
		#adminmenu a[href="admin.php?page=conboard_settings"]::after {
			content: " (soon)";
			font-size: 11px;
			opacity: 0.7;
		}
	</style>';
});
*/
