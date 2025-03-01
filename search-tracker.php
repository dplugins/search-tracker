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

// Register activation hook to create necessary database tables
register_activation_hook(__FILE__, 'sqt_activate');

function sqt_activate() {
    // Nothing to do for now, but we can add initialization code here if needed
}

function sqt_track_search_query() {
    if (!is_search() || empty(get_search_query())) {
        return;
    }
    
    $query = sanitize_text_field(get_search_query());
    $search_queries = get_option('sqt_search_queries', []);
    
    if (isset($search_queries[$query])) {
        $search_queries[$query]++;
    } else {
        $search_queries[$query] = 1;
    }
    
    update_option('sqt_search_queries', $search_queries);
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
    $search_clicks = get_option('sqt_search_clicks', []);
    
    if (!isset($search_clicks[$query])) {
        $search_clicks[$query] = [];
    }
    
    if (isset($search_clicks[$query][$url])) {
        $search_clicks[$query][$url]++;
    } else {
        $search_clicks[$query][$url] = 1;
    }
    
    update_option('sqt_search_clicks', $search_clicks);
    wp_die();
}
add_action('wp_ajax_sqt_track_click', 'sqt_track_click');
add_action('wp_ajax_nopriv_sqt_track_click', 'sqt_track_click');

// Enqueue admin scripts for the plugin page
function sqt_enqueue_admin_scripts($hook) {
    if ('toplevel_page_search-tracker' !== $hook) {
        return;
    }
    
    // Enqueue Chart.js
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
    
    // Enqueue our custom admin script
    wp_enqueue_script('sqt-admin', plugin_dir_url(__FILE__) . 'admin.js', ['jquery', 'chartjs'], '1.0', true);
    
    // Add some basic styling
    wp_enqueue_style('sqt-admin-style', plugin_dir_url(__FILE__) . 'admin.css', [], '1.0');
}
add_action('admin_enqueue_scripts', 'sqt_enqueue_admin_scripts');

function sqt_display_stats() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $search_queries = get_option('sqt_search_queries', []);
    arsort($search_queries);
    $search_clicks = get_option('sqt_search_clicks', []);
    
    // Prepare data for charts
    $chart_data = [
        'labels' => [],
        'counts' => []
    ];
    
    // Get top 20 queries for the chart
    $counter = 0;
    foreach ($search_queries as $query => $count) {
        if ($counter >= 20) break; // Limit to top 20 for better visualization
        $chart_data['labels'][] = $query;
        $chart_data['counts'][] = $count;
        $counter++;
    }
    
    // Pass data to JavaScript
    wp_localize_script('sqt-admin', 'sqtChartData', $chart_data);
    
    // Get active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'top_queries';
    
    // Display dashboard
    ?>
    <div class="wrap sqt-dashboard">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=search-tracker&tab=top_queries" class="nav-tab <?php echo $active_tab == 'top_queries' ? 'nav-tab-active' : ''; ?>">Top Search Queries</a>
            <a href="?page=search-tracker&tab=all_queries" class="nav-tab <?php echo $active_tab == 'all_queries' ? 'nav-tab-active' : ''; ?>">All Search Queries</a>
            <a href="?page=search-tracker&tab=clicked_urls" class="nav-tab <?php echo $active_tab == 'clicked_urls' ? 'nav-tab-active' : ''; ?>">Search Queries with Clicked URLs</a>
        </h2>
        
        <div class="sqt-tab-content">
            <?php if ($active_tab == 'top_queries') : ?>
                <div class="sqt-chart-container">
                    <div class="sqt-chart-wrapper">
                        <canvas id="sqtQueriesChart"></canvas>
                    </div>
                </div>
            <?php elseif ($active_tab == 'all_queries') : ?>
                <div class="sqt-data-container">
                    <div class="sqt-table-wrapper">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_queries as $query => $count) : ?>
                                    <tr>
                                        <td><?php echo esc_html($query); ?></td>
                                        <td><?php echo esc_html($count); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($active_tab == 'clicked_urls') : ?>
                <div class="sqt-data-container">
                    <?php if (empty($search_clicks)) : ?>
                        <p>No clicked URLs data available yet.</p>
                    <?php else : ?>
                        <?php foreach ($search_clicks as $query => $urls) : ?>
                            <h3><?php echo esc_html($query); ?></h3>
                            <div class="sqt-table-wrapper">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>URL</th>
                                            <th>Clicks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        arsort($urls);
                                        foreach ($urls as $url => $count) : 
                                        ?>
                                            <tr>
                                                <td><a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_html($url); ?></a></td>
                                                <td><?php echo esc_html($count); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function sqt_admin_menu() {
    add_menu_page('Search Tracker', 'Search Tracker', 'manage_options', 'search-tracker', 'sqt_display_stats', 'dashicons-search');
}
add_action('admin_menu', 'sqt_admin_menu');
