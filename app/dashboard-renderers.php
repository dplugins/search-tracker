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
            </div>

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
                    <th style="width: 70px;">Search</th>
                    <th>Term</th>
                    <th style="width: 90px;">Clicks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($search_queries as $query => $count) :
                    $row_data = $this->prepare_query_row_data($query, $count, $search_clicks, $max_count);
                ?>
                    <tr class="<?php echo esc_attr($row_data['row_class']); ?>" <?php if ($row_data['has_clicks']) : ?>data-query="<?php echo esc_attr($query); ?>" <?php endif; ?>>
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
                                <span class="view-links">
                                    View
                                    <svg width="100pt" height="100pt" version="1.1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                        <path fill="currentColor" d="m58.332 20.832c-2.3008 0-4.1641-1.8633-4.1641-4.1641s1.8633-4.168 4.1641-4.168h25c1.1055 0 2.168 0.4375 2.9492 1.2188s1.2188 1.8438 1.2188 2.9492v25c0 2.3008-1.8672 4.1641-4.168 4.1641s-4.1641-1.8633-4.1641-4.1641v-14.941l-38.723 38.719c-1.625 1.6289-4.2656 1.6289-5.8906 0-1.6289-1.625-1.6289-4.2656 0-5.8906l38.719-38.723zm-45.832 8.3359c0-4.6055 3.7305-8.3359 8.332-8.3359h20.836c2.3008 0 4.1641 1.8672 4.1641 4.168s-1.8633 4.168-4.1641 4.168h-20.836v50h50v-20.836c0-2.3008 1.8672-4.1641 4.168-4.1641s4.168 1.8633 4.168 4.1641v20.836c0 4.6016-3.7305 8.332-8.3359 8.332h-50c-4.6016 0-8.332-3.7305-8.332-8.332z" />
                                    </svg>

                                </span>
                            <?php endif; ?>
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
