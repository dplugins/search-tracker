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
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (empty($search_queries)) : ?>
                <p>No search queries data available yet.</p>
            <?php else : ?>
                <!-- Only display all queries table -->
                <?php $this->render_all_queries_table($search_queries, $search_clicks, $max_count); ?>
            <?php endif; ?>

            <?php $this->render_overlay(); ?>

            <!-- Pass search clicks data to JavaScript -->
            <script type="text/javascript">
                var sqtSearchClicks = <?php echo json_encode($search_clicks); ?>;
            </script>
        </div>
    <?php
    }

    /**
     * Render the table with all search queries
     */
    public function render_all_queries_table($search_queries, $search_clicks, $max_count)
    {
    ?>


        <table class="wp-list-table widefat fixed">
            <thead>
                <tr>
                    <th style="width: 80px;">Search</th>
                    <th style="width: 80px;">Clicks</th>
                    <th>Term</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($search_queries as $query => $count) :
                    $row_data = $this->prepare_query_row_data($query, $count, $search_clicks, $max_count);
                ?>
                    <tr class="<?php echo esc_attr($row_data['row_class']); ?>" <?php if ($row_data['has_clicks']) : ?>data-query="<?php echo esc_attr($query); ?>" <?php endif; ?>>
                        <td><?php echo esc_html($count); ?></td>
                        <td><?php echo esc_html($row_data['total_clicks']); ?></td>
                        <td>
                            <div class="sqt-bar-chart">
                                <div class="sqt-bar" style="width: <?php echo esc_attr($row_data['percentage']); ?>%;"></div>
                                <div class="sqt-bar-label">
                                    <span><?php echo esc_html($query); ?></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


    <?php
    }

    /**
     * Render the overlay for displaying clicked URLs
     */
    public function render_overlay()
    {
    ?>
        <!-- Overlay for displaying clicked URLs -->
        <div id="sqt-overlay" class="sqt-overlay">
            <div class="sqt-overlay-content">
                <span class="sqt-close">&times;</span>
                <h2 id="sqt-overlay-title"></h2>
                <div id="sqt-overlay-data"></div>
            </div>
        </div>
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
}

// Initialize the renderers
$sqt_dashboard_renderers = new SQT_Dashboard_Renderers();
