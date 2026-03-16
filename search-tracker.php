<?php
/**
 * Plugin Name:       Search Query Tracker
 * Description:       Search Query Tracker's plugin description
 * Requires at least: 6.3.0
 * Requires PHP:      7.4
 * Version:           1.1.0
 * Author:            devusrmk
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       search_query_tracker
 * Website:           
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$plugin_data = get_plugin_data(__FILE__);
define('SQT_VERSION', $plugin_data['Version']);
define('SQT_PLUGIN_FILE', __FILE__);
define('SQT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SQT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the main application file
require_once SQT_PLUGIN_DIR . 'app/app.php';

// Register activation hook
register_activation_hook(__FILE__, array('SQT_App', 'activate_plugin'));


// ------------------------------------------------------------------------------------------------
// Self Hosted Plugin Updater
// ------------------------------------------------------------------------------------------------

$plugin_prefix = 'SEARCHQUERYTRACKER';

// Extract the version number
$plugin_data = get_file_data(__FILE__, ['Version' => 'Version']);

// Plugin Constants
define($plugin_prefix . '_DIR', plugin_basename(__DIR__));
define($plugin_prefix . '_BASE', plugin_basename(__FILE__));
define($plugin_prefix . '_PATH', plugin_dir_path(__FILE__));
define($plugin_prefix . '_VER', $plugin_data['Version']);
define($plugin_prefix . '_CACHE_KEY', 'search_query_tracker-cache-key-for-plugin');
define($plugin_prefix . '_REMOTE_URL', 'https://selfhost.dplugins.com/wp-content/uploads/downloads/7/info.json');

require constant($plugin_prefix . '_PATH') . 'inc/update.php';

new DPUpdateChecker(
	constant($plugin_prefix . '_BASE'),
	constant($plugin_prefix . '_VER'),
	constant($plugin_prefix . '_CACHE_KEY'),
	constant($plugin_prefix . '_REMOTE_URL'),
);