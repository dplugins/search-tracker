<?php

/**
 * Dashboard page for Search Query Tracker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Display the statistics dashboard
 */
function sqt_display_stats()
{
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
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>



        <?php if (empty($search_queries)) : ?>
            <p>No search queries data available yet.</p>
        <?php else : ?>
            <div class="sqt-flex-container">
                <div class="sqt-flex-header">
                    <div>Search count</div>
                    <div>Clicks count</div>
                    <div>Search term</div>
                </div>

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
                    <div class="sqt-flex-row <?php echo esc_attr($row_class); ?>" <?php if ($has_clicks) : ?>data-query="<?php echo esc_attr($query); ?>" <?php endif; ?>>
                        <div><?php echo esc_html($count); ?></div>
                        <div><?php echo esc_html($total_clicks); ?></div>
                        <div>
                            <div>
                                <div style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                <div>
                                    <span><?php echo esc_html($query); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($search_queries) > count($top_queries)) : ?>
                <div>
                    <h3>All Search Queries</h3>
                    <div>
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
                                    <tr class="<?php echo esc_attr($row_class); ?>" <?php if ($has_clicks) : ?>data-query="<?php echo esc_attr($query); ?>" <?php endif; ?>>
                                        <td><?php echo esc_html($count); ?></td>
                                        <td><?php echo esc_html($total_clicks); ?></td>
                                        <td>
                                            <div>
                                                <div style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                                <div>
                                                    <span><?php echo esc_html($query); ?></span>
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

/**
 * Enqueue admin scripts for the plugin page
 */
function sqt_enqueue_admin_scripts($hook)
{
    if ('toplevel_page_search-tracker' !== $hook) {
        return;
    }

    // Enqueue our custom admin script
    wp_enqueue_script('sqt-admin', plugin_dir_url(SQT_PLUGIN_FILE) . 'assets/admin.js', ['jquery'], '1.0', true);

    // Add some basic styling
    wp_enqueue_style('sqt-admin-style', plugin_dir_url(SQT_PLUGIN_FILE) . 'assets/admin.css', [], '1.0');
}
add_action('admin_enqueue_scripts', 'sqt_enqueue_admin_scripts');

/**
 * Add admin menu page
 */
function sqt_admin_menu()
{
    add_menu_page('Search Tracker', 'Search Tracker', 'manage_options', 'search-tracker', 'sqt_display_stats', 'dashicons-search');
}
add_action('admin_menu', 'sqt_admin_menu');
