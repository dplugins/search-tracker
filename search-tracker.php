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
    
    // Enqueue our custom admin script
    wp_enqueue_script('sqt-admin', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], '1.0', true);
    
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
    
    // Get top 20 queries for visualization
    $counter = 0;
    $max_count = 0;
    $top_queries = [];
    
    foreach ($search_queries as $query => $count) {
        if ($counter >= 20) break; // Limit to top 20 for visualization
        $top_queries[$query] = $count;
        if ($count > $max_count) {
            $max_count = $count;
        }
        $counter++;
    }
    
    // Get active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'search_queries';
    
    // Display dashboard
    ?>
    <div class="wrap sqt-dashboard">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=search-tracker&tab=search_queries" class="nav-tab <?php echo $active_tab == 'search_queries' ? 'nav-tab-active' : ''; ?>">Search Queries</a>
            <a href="?page=search-tracker&tab=clicked_urls" class="nav-tab <?php echo $active_tab == 'clicked_urls' ? 'nav-tab-active' : ''; ?>">Search Queries with Clicked URLs</a>
        </h2>
        
        <div class="sqt-tab-content">
            <?php if ($active_tab == 'search_queries') : ?>
                <div class="sqt-integrated-view">
                    <h2>Search Queries Overview</h2>
                    
                    <?php if (empty($search_queries)) : ?>
                        <p>No search queries data available yet.</p>
                    <?php else : ?>
                        <div class="sqt-plausible-style" style="min-height: 320px;">
                            <?php foreach ($top_queries as $query => $count) : 
                                $percentage = ($max_count > 0) ? ($count / $max_count) * 100 : 0;
                            ?>
                                <div style="min-height: 32px;">
                                    <div class="flex w-full" style="margin-top: 4px;">
                                        <div class="flex-grow w-full overflow-hidden">
                                            <div class="w-full h-full relative">
                                                <div class="sqt-bar-bg" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                                <div class="sqt-bar-content">
                                                    <span class="sqt-query-text"><?php echo esc_html($query); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sqt-count-cell">
                                            <span class="sqt-count-value"><?php echo esc_html($count); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($search_queries) > count($top_queries)) : ?>
                            <div class="sqt-view-all">
                                <h3>All Search Queries</h3>
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
                        <?php endif; ?>
                    <?php endif; ?>
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
