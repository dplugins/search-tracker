<?php
/**
 * Plugin Name: Search Query Tracker
 * Description: Tracks search queries and user clicks on search results.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('SQT_PLUGIN_FILE', __FILE__);
define('SQT_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load the main application file
require_once SQT_PLUGIN_DIR . 'app/app.php';