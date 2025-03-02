<?php

/**
 * Rendering functions for Search Query Tracker dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class to handle the Search Query Tracker dashboard rendering
 */
class SQT_Dashboard_Renderers
{
    /**
     * Render the main dashboard content
     */
    public function render_dashboard($search_queries, $search_clicks, $max_count)
    {
?>
        <div class="wrap">
            <div class="logo">
                <svg width="100pt" height="100pt" version="1.1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <path d="m47.102 32.602c-8.1016 0-14.602 6.6016-14.602 14.602 0 8.1016 6.6016 14.602 14.602 14.602 8.1016 0 14.602-6.6016 14.602-14.602-0.003906-8.0039-6.5039-14.602-14.602-14.602z" />
                        <path d="m50.102 1.3984c-26.902 0-48.703 21.801-48.703 48.703 0 26.898 21.801 48.699 48.699 48.699 26.902 0 48.703-21.801 48.703-48.699 0-26.902-21.801-48.703-48.699-48.703zm23.699 73.703c-0.69922 0.69922-1.8008 1.1016-3 1.1016s-2.1992-0.39844-3-1.1016l-9.1992-9.1992-0.89844 0.5c-3.3008 1.8008-6.8984 2.8008-10.602 2.8008-12.102 0-21.898-9.8008-21.898-21.898 0-12.102 9.8008-21.898 21.898-21.898 12.102 0 21.898 9.8008 21.898 21.898 0 4.3008-1.1992 8.3984-3.6016 12l-0.60156 1 9.1016 9.1016c0.69922 0.80078 1.1992 1.8008 1.1992 2.8984-0.097656 0.99609-0.49609 1.9961-1.2969 2.7969z" />
                    </g>
                </svg>

                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <button id="sqt-settings-button" class="button button-primary sqt-settings-button" style="margin-left: auto;">Settings</button>
            </div>

            <?php if (empty($search_queries)) : ?>
                <p>No search queries data available yet.</p>
            <?php else : ?>
                <!-- Only display all queries table -->
                <?php $this->render_all_queries_table($search_queries, $search_clicks, $max_count); ?>
            <?php endif; ?>

            <!-- Render the popups -->
            <?php $this->render_popups(); ?>

            <!-- Pass search clicks data to JavaScript -->
            <script type="text/javascript">
                var sqtSearchClicks = <?php echo json_encode($search_clicks); ?>;
            </script>
        </div>
    <?php
    }

    /**
     * Render all popups
     */
    public function render_popups()
    {
    ?>
        <!-- Unified popup structure for all popups -->
        <div class="sqt-popup-wrapper">
            <!-- Settings popup -->
            <div id="sqt-settings-popup" class="sqt-popup">
                <div class="sqt-popup-content">
                    <span class="sqt-popup-close">&times;</span>
                    <div class="sqt-popup-header">
                        <h2>Plugin Settings</h2>
                    </div>
                    <div class="sqt-popup-body">
                        <h3>Troubleshooting</h3>
                        <p>To make things simple we have hardcoded search trigger.</p>
                        <p>Add class <b><mark>.search-result-item</mark></b> to the search result item.</p>                             

                        <hr>

                        <h3>Clear All Data</h3>
                        <p class="sqt-reset-description">This will permanently delete all search query data and click tracking information. This action cannot be undone.</p>
                        
                        <form method="post" class="sqt-reset-form">
                            <?php wp_nonce_field('sqt_reset_data', 'sqt_reset_nonce'); ?>
                            <input type="hidden" name="sqt_reset_action" value="reset">
                            
                            <div class="sqt-reset-confirmation">
                                <label for="sqt-reset-confirm">Type "reset" to confirm:</label>
                                <input type="text" id="sqt-reset-confirm" name="sqt_reset_confirm" class="sqt-reset-input" placeholder="reset">
                                <button type="submit" id="sqt-reset-button" class="button button-primary sqt-reset-button" disabled>Clear All Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Clicked URLs popup -->
            <div id="sqt-clicks-popup" class="sqt-popup">
                <div class="sqt-popup-content">
                    <span class="sqt-popup-close">&times;</span>
                    <div class="sqt-popup-header">
                        <h2 id="sqt-clicks-title">Clicked URLs</h2>
                    </div>
                    <div class="sqt-popup-body">
                        <div id="sqt-clicks-data"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render the table with all search queries
     */
    public function render_all_queries_table($search_queries, $search_clicks, $max_count)
    {
        // Get current sort parameters
        $sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'search';
        $sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
        
        // Get search filter if set
        $search_filter = isset($_GET['sqt_filter']) ? sanitize_text_field($_GET['sqt_filter']) : '';
        
        // Filter queries based on search term
        if (!empty($search_filter)) {
            $filtered_queries = [];
            foreach ($search_queries as $query => $count) {
                if (stripos($query, $search_filter) !== false) {
                    $filtered_queries[$query] = $count;
                }
            }
            $search_queries = $filtered_queries;
        }
        
        // Sort the queries based on the selected column
        $sorted_queries = $this->sort_queries($search_queries, $search_clicks, $sort_by, $sort_order);
        
        // Generate sort URLs
        $current_url = remove_query_arg(['sort', 'order']);
        $search_url = add_query_arg([
            'sort' => 'search',
            'order' => ($sort_by === 'search' && $sort_order === 'desc') ? 'asc' : 'desc'
        ], $current_url);
        $term_url = add_query_arg([
            'sort' => 'term',
            'order' => ($sort_by === 'term' && $sort_order === 'desc') ? 'asc' : 'desc'
        ], $current_url);
        $clicks_url = add_query_arg([
            'sort' => 'clicks',
            'order' => ($sort_by === 'clicks' && $sort_order === 'desc') ? 'asc' : 'desc'
        ], $current_url);
        
        // Get sort indicators
        $search_indicator = $this->get_sort_indicator($sort_by, $sort_order, 'search');
        $term_indicator = $this->get_sort_indicator($sort_by, $sort_order, 'term');
        $clicks_indicator = $this->get_sort_indicator($sort_by, $sort_order, 'clicks');
?>
        <div class="sqt-table-controls">
            <div class="sqt-search-container">
                <input type="text" id="sqt-search-input" placeholder="Search terms..." class="sqt-search-input">
                <button type="button" id="sqt-clear-search" class="button sqt-clear-button">Clear</button>
            </div>
            
            <div id="sqt-filter-info" class="sqt-filter-info" style="display: none;">
                Showing results for: <strong id="sqt-filter-term"></strong> 
                (<span id="sqt-result-count"></span> results)
            </div>
        </div>

        <div id="sqt-no-results" class="sqt-no-results" style="display: none;">
            <p>No search queries found matching your filter.</p>
        </div>

        <table class="wp-list-table widefat fixed" id="sqt-queries-table">
            <thead>
                <tr>
                    <th style="width: 80px;">
                        <a href="<?php echo esc_url($search_url); ?>" class="sqt-sort-link">
                            Search <?php echo $search_indicator; ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?php echo esc_url($term_url); ?>" class="sqt-sort-link">
                            Term <?php echo $term_indicator; ?>
                        </a>
                    </th>
                    <th style="width: 120px;">
                        <a href="<?php echo esc_url($clicks_url); ?>" class="sqt-sort-link">
                            Clicks <?php echo $clicks_indicator; ?>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($sorted_queries as $query => $count) :
                    $row_data = $this->prepare_query_row_data($query, $count, $search_clicks, $max_count);
                ?>
                    <tr class="<?php echo esc_attr($row_data['row_class']); ?>" <?php if ($row_data['has_clicks']) : ?>data-query="<?php echo esc_attr($query); ?>" <?php endif; ?> data-search-term="<?php echo esc_attr(strtolower($query)); ?>">
                        <td><?php echo esc_html($count); ?></td>
                        <td>
                            <div class="sqt-bar-chart">
                                <div class="sqt-bar" style="width: <?php echo esc_attr($row_data['percentage']); ?>%;"></div>
                                <div class="sqt-bar-label">
                                    <span><?php echo esc_html($query); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo esc_html($row_data['total_clicks']); ?>
                            <?php if ($row_data['total_clicks'] > 0) : ?>
                                <span class="view-links">View →</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
<?php
    }

    /**
     * Calculate total clicks for a query
     */
    public function calculate_total_clicks($search_clicks, $query)
    {
        $total_clicks = 0;
        if (isset($search_clicks[$query]) && !empty($search_clicks[$query])) {
            foreach ($search_clicks[$query] as $url => $click_count) {
                $total_clicks += $click_count;
            }
        }
        return $total_clicks;
    }

    /**
     * Prepare common data for a query row
     */
    private function prepare_query_row_data($query, $count, $search_clicks, $max_count)
    {
        $percentage = ($max_count > 0) ? ($count / $max_count) * 100 : 0;
        $has_clicks = isset($search_clicks[$query]) && !empty($search_clicks[$query]);
        $row_class = $has_clicks ? 'sqt-row-clickable' : '';
        $total_clicks = $this->calculate_total_clicks($search_clicks, $query);

        return [
            'percentage' => $percentage,
            'has_clicks' => $has_clicks,
            'row_class' => $row_class,
            'total_clicks' => $total_clicks
        ];
    }

    /**
     * Sort the queries based on the selected column
     */
    private function sort_queries($search_queries, $search_clicks, $sort_by, $sort_order)
    {
        // Create a temporary array for sorting
        $temp_array = [];
        
        // Prepare the data for sorting
        foreach ($search_queries as $query => $count) {
            $total_clicks = $this->calculate_total_clicks($search_clicks, $query);
            $temp_array[] = [
                'query' => $query,
                'count' => $count,
                'total_clicks' => $total_clicks
            ];
        }
        
        // Sort the array based on the selected column
        usort($temp_array, function($a, $b) use ($sort_by) {
            if ($sort_by === 'search') {
                return $a['count'] <=> $b['count'];
            } elseif ($sort_by === 'term') {
                return strcasecmp($a['query'], $b['query']);
            } elseif ($sort_by === 'clicks') {
                return $a['total_clicks'] <=> $b['total_clicks'];
            }
            return 0;
        });
        
        // Reverse the array if descending order is selected
        if ($sort_order === 'desc') {
            $temp_array = array_reverse($temp_array);
        }
        
        // Rebuild the original array structure but in the new order
        $sorted_queries = [];
        foreach ($temp_array as $item) {
            $sorted_queries[$item['query']] = $item['count'];
        }
        
        return $sorted_queries;
    }

    /**
     * Get sort indicator
     */
    private function get_sort_indicator($sort_by, $sort_order, $column)
    {
        if (isset($_GET['sort']) && $_GET['sort'] === $column) {
            return $sort_order === 'desc' ? '↓' : '↑';
        }
        return '';
    }
}

// Initialize the renderers
$sqt_dashboard_renderers = new SQT_Dashboard_Renderers();
