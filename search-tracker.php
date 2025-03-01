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

// Register activation hook to create necessary database tables
register_activation_hook(__FILE__, 'sqt_activate');

function sqt_activate() {
    // Nothing to do for now, but we can add initialization code here if needed
}

function sqt_track_search_query() {
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
add_action('wp', 'sqt_track_search_query');

function sqt_enqueue_scripts() {
    if (is_search()) {
        wp_enqueue_script('sqt-tracker', plugin_dir_url(__FILE__) . 'tracker.js', ['jquery'], null, true);
        wp_localize_script('sqt-tracker', 'sqtAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }
}
add_action('wp_enqueue_scripts', 'sqt_enqueue_scripts');

function sqt_track_click() {
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
add_action('wp_ajax_sqt_track_click', 'sqt_track_click');
add_action('wp_ajax_nopriv_sqt_track_click', 'sqt_track_click');

// Include dashboard functionality
require_once SQT_PLUGIN_DIR . 'dashboard.php';
