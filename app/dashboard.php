<?php

/**
 * Dashboard page for Search Query Tracker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class to handle the Search Query Tracker dashboard
 */
class SQT_Dashboard {
    /**
     * Initialize the dashboard
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register the admin menu page
     */
    public function register_admin_menu() {
        add_menu_page(
            'Search Tracker', 
            'Search Tracker', 
            'manage_options', 
            'search-tracker', 
            array($this, 'render_app'), 
            'dashicons-search'
        );
    }

    /**
     * Enqueue admin scripts for the plugin page
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_search-tracker' !== $hook) {
            return;
        }
        
        // Enqueue the compiled React app
        $asset_file = include(SQT_PLUGIN_DIR . 'build/index.asset.php');
        
        wp_enqueue_script(
            'sqt-admin', 
            SQT_PLUGIN_URL . 'build/index.js', 
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );
        
        // Pass data to the React app
        wp_localize_script('sqt-admin', 'sqtData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sqt_nonce'),
            'pluginUrl' => SQT_PLUGIN_URL
        ));
        
        // Enqueue main stylesheet
        wp_enqueue_style(
            'sqt-styles',
            SQT_PLUGIN_URL . 'build/index.css',
            array(),
            $asset_file['version']
        );

        wp_enqueue_style(
            'sqt-tailwind',
            SQT_PLUGIN_URL . 'build/output.css',
            array(),
            $asset_file['version']
        );
        
        // wp_enqueue_style('wp-edit-blocks'); // Loads core Gutenberg styles
        // wp_enqueue_style('wp-components');  // Loads WP component styles
        wp_enqueue_style(
            'wp-editor',
            includes_url('css/dist/editor/style.css'),
            [],
            file_exists(ABSPATH . 'wp-includes/css/dist/editor/style.css') ? filemtime(ABSPATH . 'wp-includes/css/dist/editor/style.css') : '6.0' // Default version fallback
        );
    }
    
    /**
     * Render the React app container
     */
    public function render_app() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<div id="sqt-app"></div>';
        echo '</div>';
    }
}

// Initialize the dashboard
$sqt_dashboard = new SQT_Dashboard();
