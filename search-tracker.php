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
    
    // Use autoloading for options
    $search_queries = get_option('sqt_search_queries', [], true);
    arsort($search_queries);
    $search_clicks = get_option('sqt_search_clicks', [], true);
    
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
    
    // Display dashboard
    ?>
    <div class="wrap sqt-dashboard">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="sqt-integrated-view">
            <h2>Search Queries Overview</h2>
            
            <?php if (empty($search_queries)) : ?>
                <p>No search queries data available yet.</p>
            <?php else : ?>
                <div class="sqt-table-container">
                    <table class="wp-list-table widefat fixed striped sqt-main-table">
                        <thead>
                            <tr>
                                <th>Search count</th>
                                <th>Clicks count</th>
                                <th>Search term</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($top_queries as $query => $count) : 
                                $percentage = ($max_count > 0) ? ($count / $max_count) * 100 : 0;
                                $has_clicks = isset($search_clicks[$query]) && !empty($search_clicks[$query]);
                                $row_class = $has_clicks ? 'sqt-row-clickable' : '';
                                
                                // Calculate total clicks for this query
                                $total_clicks = 0;
                                if ($has_clicks) {
                                    foreach ($search_clicks[$query] as $url => $click_count) {
                                        $total_clicks += $click_count;
                                    }
                                }
                            ?>
                                <tr class="<?php echo esc_attr($row_class); ?>" <?php if ($has_clicks) : ?>data-query="<?php echo esc_attr($query); ?>"<?php endif; ?>>
                                    <td class="sqt-count-cell"><?php echo esc_html($count); ?></td>
                                    <td class="sqt-clicks-cell"><?php echo esc_html($total_clicks); ?></td>
                                    <td class="sqt-term-cell">
                                        <div class="sqt-bar-container">
                                            <div class="sqt-bar-bg" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                            <div class="sqt-bar-content">
                                                <span class="sqt-query-text"><?php echo esc_html($query); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($search_queries) > count($top_queries)) : ?>
                    <div class="sqt-view-all">
                        <h3>All Search Queries</h3>
                        <div class="sqt-table-wrapper">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Search count</th>
                                        <th>Clicks count</th>
                                        <th>Search term</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($search_queries as $query => $count) : 
                                        $percentage = ($max_count > 0) ? ($count / $max_count) * 100 : 0;
                                        $has_clicks = isset($search_clicks[$query]) && !empty($search_clicks[$query]);
                                        $row_class = $has_clicks ? 'sqt-row-clickable' : '';
                                        
                                        // Calculate total clicks for this query
                                        $total_clicks = 0;
                                        if ($has_clicks) {
                                            foreach ($search_clicks[$query] as $url => $click_count) {
                                                $total_clicks += $click_count;
                                            }
                                        }
                                    ?>
                                        <tr class="<?php echo esc_attr($row_class); ?>" <?php if ($has_clicks) : ?>data-query="<?php echo esc_attr($query); ?>"<?php endif; ?>>
                                            <td><?php echo esc_html($count); ?></td>
                                            <td><?php echo esc_html($total_clicks); ?></td>
                                            <td class="sqt-term-cell">
                                                <div class="sqt-bar-container">
                                                    <div class="sqt-bar-bg" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                                    <div class="sqt-bar-content">
                                                        <span class="sqt-query-text"><?php echo esc_html($query); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overlay for displaying clicked URLs -->
    <div id="sqt-overlay" class="sqt-overlay">
        <div class="sqt-overlay-content">
            <span class="sqt-close">&times;</span>
            <h2 id="sqt-overlay-title"></h2>
            <div id="sqt-overlay-data"></div>
        </div>
    </div>

    <!-- Pass search clicks data to JavaScript -->
    <script type="text/javascript">
        var sqtSearchClicks = <?php echo json_encode($search_clicks); ?>;
    </script>
    <?php
}

function sqt_admin_menu() {
    add_menu_page('Search Tracker', 'Search Tracker', 'manage_options', 'search-tracker', 'sqt_display_stats', 'dashicons-search');
}
add_action('admin_menu', 'sqt_admin_menu');
