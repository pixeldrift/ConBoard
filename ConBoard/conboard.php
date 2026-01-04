<?php
/**
 * Plugin Name: ConBoard – Conference Core
 * Description: Core data layer for conference events (sessions, speakers, rooms, signage).
 * Version: 0.1.0
 * Author: Nathan D. B. Pizar
 * Text Domain: conboard
 */


if (!defined('ABSPATH')) exit;

define('CONBOARD_PLUGIN_FILE', __FILE__);
define('CONBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CONBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Core
require_once CONBOARD_PLUGIN_PATH . 'includes/activation.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/defaults.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/fields.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/helpers.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/shortcodes.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/taxonomies.php';

// Post Types
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_announcement.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_display_board.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_presenter.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_room.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_session.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_sponsor.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/post-types/cpt_staff.php';

// Display System
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_bootstrap.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_context.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_hooks.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_params.php';
require_once CONBOARD_PLUGIN_PATH . 'includes/display/display_qr_code.php';

// Admin
if (is_admin()) {
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_menu.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_room_metaboxes.php';
    require_once CONBOARD_PLUGIN_PATH . 'includes/admin/admin_display_board_actions.php';
}
