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
     * Singleton instance
     * @var SQT_App|null
     */
    private static $instance = null;

    /**
     * Database version
     * @var string
     */
    const DB_VERSION = '1.0';

    /**
     * Get singleton instance
     * @return SQT_App
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor - use get_instance() instead
     */
    private function __construct() {
        // Check database on every load
        add_action('plugins_loaded', array($this, 'check_database'));

        // Add admin notice for database setup
        add_action('admin_notices', array($this, 'admin_notices'));

        // Add action hooks
        add_action('wp', array($this, 'track_search_query'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_sqt_get_data', array($this, 'get_data'));
        add_action('wp_ajax_sqt_reset_data', array($this, 'reset_data'));
        add_action('wp_ajax_sqt_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_sqt_export_json', array($this, 'export_json'));
    }

    /**
     * Display admin notices if needed
     */
    public function admin_notices() {
        global $wpdb;
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        $tables_exist = (
            $wpdb->get_var("SHOW TABLES LIKE '$searches_table'") === $searches_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") === $clicks_table
        );

        // Show notice only on plugin admin page and if tables don't exist
        $screen = get_current_screen();
        if (!$tables_exist && $screen && $screen->id === 'toplevel_page_search-tracker') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Search Query Tracker:</strong> Database tables are being created. Please refresh this page in a moment.</p>';
            echo '</div>';
        }
    }

    /**
     * Plugin activation hook
     * Called when plugin is activated
     */
    public static function activate_plugin() {
        $instance = self::get_instance();
        $instance->create_tables();
        $instance->migrate_data_to_tables();
        update_option('sqt_db_version', self::DB_VERSION);
    }

    /**
     * Check if database tables exist and are up to date
     */
    public function check_database() {
        global $wpdb;
        $installed_ver = get_option('sqt_db_version');

        // Check if tables actually exist
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        $tables_exist = (
            $wpdb->get_var("SHOW TABLES LIKE '$searches_table'") === $searches_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") === $clicks_table
        );

        // Create tables if version mismatch OR tables don't exist
        if ($installed_ver !== self::DB_VERSION || !$tables_exist) {
            $this->create_tables();

            // Migrate data if not already done
            if (!get_option('sqt_data_migrated')) {
                $this->migrate_data_to_tables();
            }

            update_option('sqt_db_version', self::DB_VERSION);
        }
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Searches table
        $table_name = $wpdb->prefix . 'sqt_searches';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            query VARCHAR(200) NOT NULL,
            count INT UNSIGNED DEFAULT 1,
            last_searched DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_query (query),
            KEY idx_count (count),
            KEY idx_last_searched (last_searched)
        ) $charset_collate;";

        dbDelta($sql);

        // Clicks table
        $table_name = $wpdb->prefix . 'sqt_clicks';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            search_query VARCHAR(200) NOT NULL,
            url VARCHAR(500) NOT NULL,
            count INT UNSIGNED DEFAULT 1,
            last_clicked DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_query_url (search_query(191), url(191)),
            KEY idx_query (search_query),
            KEY idx_count (count),
            KEY idx_last_clicked (last_clicked)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Migrate data from WordPress options to custom tables
     * One-time operation on activation or update
     */
    private function migrate_data_to_tables() {
        // Check if already migrated
        if (get_option('sqt_data_migrated')) {
            return;
        }

        global $wpdb;
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        // Migrate search queries
        $search_queries = get_option('sqt_search_queries', []);
        if (!empty($search_queries)) {
            foreach ($search_queries as $query => $count) {
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $searches_table (query, count, last_searched, created_at)
                     VALUES (%s, %d, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE count = %d, last_searched = NOW()",
                    $query,
                    $count,
                    $count
                ));
            }
        }

        // Migrate clicks
        $search_clicks = get_option('sqt_search_clicks', []);
        if (!empty($search_clicks)) {
            foreach ($search_clicks as $query => $urls) {
                foreach ($urls as $url => $count) {
                    $wpdb->query($wpdb->prepare(
                        "INSERT INTO $clicks_table (search_query, url, count, last_clicked, created_at)
                         VALUES (%s, %s, %d, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE count = %d, last_clicked = NOW()",
                        $query,
                        $url,
                        $count,
                        $count
                    ));
                }
            }
        }

        // Mark as migrated
        update_option('sqt_data_migrated', true);
    }

    /**
     * Track search queries using custom database table
     */
    public function track_search_query() {
        // Check if we're on a search page and have a query before proceeding
        if (!is_search() || empty(get_search_query())) {
            return;
        }

        // Use get_search_query() only once and store the result
        $query = sanitize_text_field(get_search_query());

        // Validate query length
        if (strlen($query) > 200) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sqt_searches';

        // Insert or update using ON DUPLICATE KEY UPDATE
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (query, count, last_searched, created_at)
             VALUES (%s, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE count = count + 1, last_searched = NOW()",
            $query
        ));
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
        // Verify nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');

        if (!isset($_POST['query']) || !isset($_POST['url'])) {
            wp_send_json_error('Missing required parameters');
        }

        // Rate limiting: max 10 clicks per minute per IP
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if ($ip) {
            $transient_key = 'sqt_rate_limit_' . md5($ip);
            $count = get_transient($transient_key);

            if ($count === false) {
                $count = 0;
            }

            if ($count > 10) {
                wp_send_json_error('Rate limit exceeded. Please try again later.');
            }

            set_transient($transient_key, $count + 1, 60); // 60 seconds
        }

        $query = sanitize_text_field($_POST['query']);
        $url = esc_url_raw($_POST['url']);

        // Validate data
        if (strlen($query) > 200) {
            wp_send_json_error('Search query too long');
        }

        if (strlen($url) > 500) {
            wp_send_json_error('URL too long');
        }

        // Only track internal links
        if (strpos($url, home_url()) !== 0) {
            wp_send_json_error('Invalid URL');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sqt_clicks';

        // Insert or update using ON DUPLICATE KEY UPDATE
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (search_query, url, count, last_clicked, created_at)
             VALUES (%s, %s, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE count = count + 1, last_clicked = NOW()",
            $query,
            $url
        ));

        if ($result === false) {
            error_log('SQT Error in track_click: ' . $wpdb->last_error);
            wp_send_json_error('Failed to track click');
        }

        wp_send_json_success();
    }
    
    /**
     * Get search data for the React app from custom tables
     */
    public function get_data() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');

        // Only allow administrators to access this data
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        global $wpdb;
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        // Verify tables exist
        $tables_exist = (
            $wpdb->get_var("SHOW TABLES LIKE '$searches_table'") === $searches_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") === $clicks_table
        );

        if (!$tables_exist) {
            // Tables don't exist - trigger creation
            $this->check_database();

            // Return empty data for now
            wp_send_json_success([
                'searchQueries' => [],
                'searchClicks' => []
            ]);
            return;
        }

        // Get all search queries
        $searches = $wpdb->get_results("SELECT query, count FROM $searches_table ORDER BY count DESC");

        if ($searches === false) {
            error_log('SQT Error in get_data: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }

        // Get all clicks
        $clicks = $wpdb->get_results("SELECT search_query, url, count FROM $clicks_table ORDER BY count DESC");

        if ($clicks === false) {
            error_log('SQT Error in get_data: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }

        // Format data to match existing structure (for backward compatibility with React app)
        $search_queries = [];
        if (is_array($searches)) {
            foreach ($searches as $search) {
                $search_queries[$search->query] = (int) $search->count;
            }
        }

        $search_clicks = [];
        if (is_array($clicks)) {
            foreach ($clicks as $click) {
                if (!isset($search_clicks[$click->search_query])) {
                    $search_clicks[$click->search_query] = [];
                }
                $search_clicks[$click->search_query][$click->url] = (int) $click->count;
            }
        }

        wp_send_json_success([
            'searchQueries' => $search_queries,
            'searchClicks' => $search_clicks
        ]);
    }
    
    /**
     * Reset all search data by truncating custom tables
     */
    public function reset_data() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');

        // Only allow administrators to reset data
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        global $wpdb;
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        // Truncate tables (faster than DELETE)
        $wpdb->query("TRUNCATE TABLE $searches_table");
        $wpdb->query("TRUNCATE TABLE $clicks_table");

        wp_send_json_success('All search data has been cleared successfully.');
    }

    /**
     * Export data as CSV
     */
    public function export_csv() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');

        // Only allow administrators to export
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        // Set headers for file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="search-tracker-data-' . date('Y-m-d') . '.csv"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write CSV headers
        fputcsv($output, ['Query', 'Search Count', 'Last Searched', 'URL', 'Click Count', 'Last Clicked']);

        // Get all data with LEFT JOIN
        $results = $wpdb->get_results("
            SELECT
                s.query,
                s.count as search_count,
                s.last_searched,
                c.url,
                c.count as click_count,
                c.last_clicked
            FROM $searches_table s
            LEFT JOIN $clicks_table c ON s.query = c.search_query
            ORDER BY s.count DESC, c.count DESC
        ");

        // Write data rows
        foreach ($results as $row) {
            fputcsv($output, [
                $row->query,
                $row->search_count,
                $row->last_searched,
                $row->url ?? '',
                $row->click_count ?? 0,
                $row->last_clicked ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export data as JSON
     */
    public function export_json() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');

        // Only allow administrators to export
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        global $wpdb;
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        // Set headers for file download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="search-tracker-data-' . date('Y-m-d') . '.json"');

        // Get all searches
        $searches = $wpdb->get_results("SELECT * FROM $searches_table ORDER BY count DESC");

        // Get all clicks
        $clicks = $wpdb->get_results("SELECT * FROM $clicks_table ORDER BY count DESC");

        // Format data
        $data = [
            'export_date' => current_time('mysql'),
            'plugin_version' => '1.0.4',
            'searches' => $searches,
            'clicks' => $clicks,
            'summary' => [
                'total_searches' => array_sum(array_column($searches, 'count')),
                'unique_queries' => count($searches),
                'total_clicks' => array_sum(array_column($clicks, 'count')),
                'unique_urls' => count($clicks)
            ]
        ];

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Include dashboard functionality
require_once 'dashboard.php';

// Initialize the app using singleton pattern
add_action('plugins_loaded', array('SQT_App', 'get_instance'));
