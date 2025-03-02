<?php

/**
 * Main application class for Search Query Tracker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main class for Search Query Tracker functionality
 */
class SQT_App {
    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Register hooks
        register_activation_hook(SQT_PLUGIN_FILE, array($this, 'activate'));
        
        // Add action hooks
        add_action('wp', array($this, 'track_search_query'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_sqt_get_data', array($this, 'get_data'));
        add_action('wp_ajax_sqt_reset_data', array($this, 'reset_data'));
    }

    /**
     * Plugin activation hook
     */
    public function activate() {
        // Nothing to do for now, but we can add initialization code here if needed
    }

    /**
     * Track search queries
     */
    public function track_search_query() {
        // Check if we're on a search page and have a query before proceeding
        if (!is_search() || empty(get_search_query())) {
            return;
        }
        
        // Use get_search_query() only once and store the result
        $query = sanitize_text_field(get_search_query());
        
        // Use autoloading to reduce memory usage
        $search_queries = get_option('sqt_search_queries', [], true);
        
        if (isset($search_queries[$query])) {
            $search_queries[$query]++;
        } else {
            $search_queries[$query] = 1;
        }
        
        // Use autoload and consider using a transient for frequently updated data
        update_option('sqt_search_queries', $search_queries, true);
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (is_search()) {
            // Enqueue the compiled frontend script
            $asset_file = include(SQT_PLUGIN_DIR . 'build/frontend.asset.php');
            
            wp_enqueue_script(
                'sqt-tracker', 
                SQT_PLUGIN_URL . 'build/frontend.js', 
                $asset_file['dependencies'],
                $asset_file['version'],
                true
            );
            
            // Make sure ajaxurl is available in the frontend
            wp_localize_script('sqt-tracker', 'sqtData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sqt_nonce'),
                'pluginUrl' => SQT_PLUGIN_URL
            ));
            
            // Add inline script to define ajaxurl globally
            wp_add_inline_script('sqt-tracker', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
        }
    }

    /**
     * Track clicks on search results via AJAX
     */
    public function track_click() {
        if (!isset($_POST['query']) || !isset($_POST['url'])) {
            wp_send_json_error('Missing required parameters');
        }
        
        $query = sanitize_text_field($_POST['query']);
        $url = esc_url_raw($_POST['url']);
        
        // Use autoloading parameter
        $search_clicks = get_option('sqt_search_clicks', [], true);
        
        if (!isset($search_clicks[$query])) {
            $search_clicks[$query] = [];
        }
        
        if (isset($search_clicks[$query][$url])) {
            $search_clicks[$query][$url]++;
        } else {
            $search_clicks[$query][$url] = 1;
        }
        
        // Use autoload parameter
        update_option('sqt_search_clicks', $search_clicks, true);
        wp_send_json_success();
    }
    
    /**
     * Get search data for the React app
     */
    public function get_data() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');
        
        // Only allow administrators to access this data
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }
        
        $search_queries = get_option('sqt_search_queries', [], true);
        $search_clicks = get_option('sqt_search_clicks', [], true);
        
        wp_send_json_success([
            'searchQueries' => $search_queries,
            'searchClicks' => $search_clicks
        ]);
    }
    
    /**
     * Reset all search data
     */
    public function reset_data() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');
        
        // Only allow administrators to reset data
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }
        
        delete_option('sqt_search_queries');
        delete_option('sqt_search_clicks');
        
        wp_send_json_success('All search data has been cleared successfully.');
    }
}

// Include dashboard functionality
require_once 'dashboard.php';

// Initialize the app
$sqt_app = new SQT_App();
