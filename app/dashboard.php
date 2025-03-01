<?php

/**
 * Dashboard page for Search Query Tracker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the dashboard renderers
require_once plugin_dir_path(SQT_PLUGIN_FILE) . 'app/dashboard-renderers.php';

/**
 * Class to handle the Search Query Tracker dashboard
 */
class SQT_Dashboard {
    /**
     * Dashboard renderers instance
     */
    private $renderers;

    /**
     * Initialize the dashboard
     */
    public function __construct() {
        global $sqt_dashboard_renderers;
        $this->renderers = $sqt_dashboard_renderers;
        
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register the admin menu page
     */
    public function register_admin_menu() {
        add_menu_page('Search Tracker', 'Search Tracker', 'manage_options', 'search-tracker', 
            array($this, 'display_stats'), 'dashicons-search');
    }

    /**
     * Enqueue admin scripts for the plugin page
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_search-tracker' !== $hook) {
            return;
        }

        // Enqueue our custom admin script
        wp_enqueue_script('sqt-admin', plugin_dir_url(SQT_PLUGIN_FILE) . 'assets/js/admin.js', ['jquery'], '1.0', true);

        // Add some basic styling
        wp_enqueue_style('sqt-admin-style', plugin_dir_url(SQT_PLUGIN_FILE) . 'assets/css/admin.css', [], '1.0');
    }

    /**
     * Display the statistics dashboard
     */
    public function display_stats() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Use autoloading for options
        $search_queries = get_option('sqt_search_queries', [], true);
        arsort($search_queries);
        $search_clicks = get_option('sqt_search_clicks', [], true);

        // Get maximum count for percentage calculation
        $max_count = 0;
        foreach ($search_queries as $count) {
            if ($count > $max_count) {
                $max_count = $count;
            }
        }

        // Display dashboard using the renderer
        $this->renderers->render_dashboard($search_queries, $search_clicks, $max_count);
    }
}

// Initialize the dashboard
$sqt_dashboard = new SQT_Dashboard();
