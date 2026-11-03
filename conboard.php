<?php
/**
 * Plugin Name: ConBoard – Conference Event Manager
 * Description: ConBoard is a WordPress plugin for managing conference content and powering live event info from the same data source. It connects sessions, presenters, locations, and schedules into a time-aware system that works both on the website and on screens around the venue. This plugin is not just a website, but also a digital signage system!
 * Version: 0.1.0
 * Author: Nathan D. B. Pizar
 * Text Domain: conboard
 * Requires PHP: 8.2
 */

// Requires PHP 8.2+ (uses str_starts_with / str_contains). Tested for syntax on 8.3.


if (!defined('ABSPATH')) exit;

add_action( 'admin_enqueue_scripts', function ( $hook ) {

	// Only load our CSS on ConBoard pages
	// Helps avoid conflicts
	if ( ! str_starts_with( $hook, 'conboard' ) && ! str_contains( $hook, 'conboard' ) ) {
		return;
	}

	wp_enqueue_style(
		'conboard-admin',
		CONBOARD_PLUGIN_URL . 'assets/css/style.css',
		[],
	);
});

define('CONBOARD_PLUGIN_FILE', __FILE__);
define('CONBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CONBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Core - Order matters
require_once CONBOARD_PLUGIN_PATH . 'includes/helpers.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/presenter_roles.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/sponsor_levels.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/links.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/schedule.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/event_days.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/activation.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/defaults.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/shortcodes.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/taxonomies.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/templates.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/frontend/frontend_assets.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/frontend/frontend_render.php';

// Map handling code
require_once CONBOARD_PLUGIN_PATH . 'includes/maps/maps_core.php';

require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_shared.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_presenter.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_location.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_session.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_sponsor.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_staff.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf/acf_announcement.php';

// Post Types - Order matters
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_announcement.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_display_board.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_presenter.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_location.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_session.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_sponsor.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_staff.php';

// Display System - Order matters
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_bootstrap.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_context.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_hooks.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_params.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_qr_code.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_screens.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_slides.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_slides_schedule.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_slides_content.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_routing.php';

// Admin - Order matters
if (is_admin()) {
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_helpers.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_pages.php'; 
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_menu.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_dashboard.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_columns.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_settings.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_markdown.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_list_icons.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_notices.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_about.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_display_board.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_location_metaboxes.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_screen_link.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_row_editor.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_session_presenters.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_links_metabox.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_sessions_caching.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_session_conflicts.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_planner.php';
	require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_export_boards.php';

}


// Hooks
// Alert user if dependancy (Advanced Custom Fields plugin) is not installed
add_action('admin_notices', 'conboard_acf_missing_notice');