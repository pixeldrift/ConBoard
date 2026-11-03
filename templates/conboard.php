<?php
/**
 * Plugin Name: ConBoard – Conference Event Manager
 * Description: ConBoard is a WordPress plugin for managing conference content and powering live event info from the same data source. It connects sessions, presenters, rooms, and schedules into a time-aware system that works both on the website and on screens around the venue. This plugin is not just a website, but also a digital signage system!
 * Version: 0.1.0
 * Author: Nathan D. B. Pizar
 * Text Domain: conboard
 */

// PHP 7.4


if (!defined('ABSPATH')) exit;

define('CONBOARD_PLUGIN_FILE', __FILE__);
define('CONBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CONBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Core - Order matters
require_once CONBOARD_PLUGIN_PATH . 'includes/helpers.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/presenter_roles.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/sponsor_levels.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/acf_fields.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/activation.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/defaults.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/shortcodes.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/taxonomies.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/templates.php';

// Post Types - Order matters
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_announcement.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_display_board.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_presenter.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_room.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_session.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_sponsor.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_staff.php';

// Display System - Order matters
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_bootstrap.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_context.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_hooks.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_params.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_qr_code.php';

// Admin - Order matters
if (is_admin()) {
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_sessions_caching.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_helpers.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_columns.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_display_board_actions.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_markdown.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_menu.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_notices.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_overview.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_settings.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_about.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_room_metaboxes.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_session_conflicts.php';
}

// Hooks
add_action('admin_notices', 'conboard_acf_missing_notice');
add_action('acf/init', function () {
    error_log(print_r(acf_get_local_field_groups(), true));
});