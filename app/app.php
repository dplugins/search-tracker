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
        add_action('wp_ajax_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sqt_track_click', array($this, 'track_click'));
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
            wp_enqueue_script('sqt-tracker', plugin_dir_url(SQT_PLUGIN_FILE) . 'assets/js/tracker.js', ['jquery'], null, true);
            wp_localize_script('sqt-tracker', 'sqtAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
        }
    }

    /**
     * Track clicks on search results via AJAX
     */
    public function track_click() {
        if (!isset($_POST['query']) || !isset($_POST['url'])) {
            wp_die();
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
        wp_die();
    }
}

// Include dashboard functionality
require_once 'dashboard.php';

// Initialize the app
$sqt_app = new SQT_App();
