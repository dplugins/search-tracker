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

        // AJAX handlers - Search tracking
        add_action('wp_ajax_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sqt_track_click', array($this, 'track_click'));
        add_action('wp_ajax_sqt_get_data', array($this, 'get_data'));
        add_action('wp_ajax_sqt_reset_data', array($this, 'reset_data'));
        add_action('wp_ajax_sqt_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_sqt_export_json', array($this, 'export_json'));

        // AJAX handlers - Analytics tracking
        add_action('wp_ajax_sqt_track_session', array($this, 'track_session'));
        add_action('wp_ajax_nopriv_sqt_track_session', array($this, 'track_session'));
        add_action('wp_ajax_sqt_track_pageview', array($this, 'track_pageview'));
        add_action('wp_ajax_nopriv_sqt_track_pageview', array($this, 'track_pageview'));
        add_action('wp_ajax_sqt_update_pageview', array($this, 'update_pageview'));
        add_action('wp_ajax_nopriv_sqt_update_pageview', array($this, 'update_pageview'));
        add_action('wp_ajax_sqt_get_analytics', array($this, 'get_analytics'));
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

        // Check if ALL tables exist
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';
        $sessions_table = $wpdb->prefix . 'sqt_sessions';
        $pageviews_table = $wpdb->prefix . 'sqt_pageviews';

        $tables_exist = (
            $wpdb->get_var("SHOW TABLES LIKE '$searches_table'") === $searches_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") === $clicks_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$pageviews_table'") === $pageviews_table
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

        // Sessions table (for analytics)
        $table_name = $wpdb->prefix . 'sqt_sessions';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            landing_page VARCHAR(500),
            landing_referrer VARCHAR(500),
            referrer_type ENUM('direct', 'search', 'social', 'referral', 'email', 'campaign') DEFAULT 'direct',
            search_engine VARCHAR(50),
            utm_source VARCHAR(100),
            utm_medium VARCHAR(100),
            utm_campaign VARCHAR(100),
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            duration INT UNSIGNED DEFAULT 0,
            pages_viewed INT DEFAULT 1,
            bounced BOOLEAN DEFAULT FALSE,
            converted BOOLEAN DEFAULT FALSE,
            ip_hash VARCHAR(64),
            user_agent TEXT,
            device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
            browser VARCHAR(50),
            os VARCHAR(50),
            UNIQUE KEY session_id (session_id),
            KEY idx_started_at (started_at),
            KEY idx_referrer_type (referrer_type),
            KEY idx_landing_page (landing_page(191)),
            KEY idx_search_engine (search_engine)
        ) $charset_collate;";

        dbDelta($sql);

        // Pageviews table (for flow tracking)
        $table_name = $wpdb->prefix . 'sqt_pageviews';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            page_url VARCHAR(500) NOT NULL,
            page_title VARCHAR(200),
            viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            time_on_page INT UNSIGNED DEFAULT 0,
            scroll_depth INT DEFAULT 0,
            sequence_number INT DEFAULT 1,
            exit_page BOOLEAN DEFAULT FALSE,
            KEY idx_session_id (session_id),
            KEY idx_page_url (page_url(191)),
            KEY idx_viewed_at (viewed_at),
            KEY idx_sequence (sequence_number)
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
        // Analytics tracking on all pages
        wp_enqueue_script(
            'sqt-analytics',
            SQT_PLUGIN_URL . 'build/analytics.js',
            array(),
            SQT_VERSION,
            true
        );

        // Make sure ajaxurl is available in the frontend
        wp_localize_script('sqt-analytics', 'sqtData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sqt_nonce'),
            'pluginUrl' => SQT_PLUGIN_URL
        ));

        // Search result click tracking only on search pages
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

    /**
     * Track session start/update
     */
    public function track_session() {
        // No nonce check - this is public tracking
        // But we validate and sanitize all inputs

        if (!isset($_POST['session_id'])) {
            wp_send_json_error('Missing required parameters');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sqt_sessions';

        // Check if table exists - if not, silently fail (tables will be created on next admin visit)
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            wp_send_json_success(); // Silent success to avoid frontend errors
            return;
        }

        $session_id = sanitize_text_field($_POST['session_id']);
        $landing_page = isset($_POST['landing_page']) ? esc_url_raw($_POST['landing_page']) : '';
        $landing_referrer = isset($_POST['landing_referrer']) ? esc_url_raw($_POST['landing_referrer']) : '';
        $utm_source = isset($_POST['utm_source']) ? sanitize_text_field($_POST['utm_source']) : '';
        $utm_medium = isset($_POST['utm_medium']) ? sanitize_text_field($_POST['utm_medium']) : '';
        $utm_campaign = isset($_POST['utm_campaign']) ? sanitize_text_field($_POST['utm_campaign']) : '';

        // Detect referrer type and search engine
        $referrer_data = $this->detect_referrer_type($landing_referrer, $utm_source, $utm_medium);

        // Get device and browser info
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        $device_info = $this->parse_user_agent($user_agent);

        // Anonymize IP
        $ip_hash = $this->anonymize_ip(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');

        // Insert or update session
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (
                session_id, landing_page, landing_referrer, referrer_type, search_engine,
                utm_source, utm_medium, utm_campaign, started_at, last_activity,
                ip_hash, user_agent, device_type, browser, os
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW(), %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                last_activity = NOW(),
                pages_viewed = pages_viewed + 1,
                duration = TIMESTAMPDIFF(SECOND, started_at, NOW()),
                bounced = IF(pages_viewed > 1, FALSE, bounced)",
            $session_id,
            $landing_page,
            $landing_referrer,
            $referrer_data['type'],
            $referrer_data['search_engine'],
            $utm_source,
            $utm_medium,
            $utm_campaign,
            $ip_hash,
            $user_agent,
            $device_info['device_type'],
            $device_info['browser'],
            $device_info['os']
        ));

        if ($result === false) {
            error_log('SQT Error in track_session: ' . $wpdb->last_error);
            wp_send_json_error('Failed to track session');
        }

        wp_send_json_success();
    }

    /**
     * Track individual pageview
     */
    public function track_pageview() {
        if (!isset($_POST['session_id']) || !isset($_POST['page_url'])) {
            wp_send_json_error('Missing required parameters');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sqt_pageviews';

        // Check if table exists - if not, silently fail
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            wp_send_json_success(); // Silent success to avoid frontend errors
            return;
        }

        $session_id = sanitize_text_field($_POST['session_id']);
        $page_url = esc_url_raw($_POST['page_url']);
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';
        $time_on_page = isset($_POST['time_on_page']) ? intval($_POST['time_on_page']) : 0;
        $scroll_depth = isset($_POST['scroll_depth']) ? intval($_POST['scroll_depth']) : 0;
        $sequence_number = isset($_POST['sequence']) ? intval($_POST['sequence']) : 1;

        // Insert pageview
        $result = $wpdb->insert(
            $table_name,
            [
                'session_id' => $session_id,
                'page_url' => $page_url,
                'page_title' => $page_title,
                'viewed_at' => current_time('mysql'),
                'time_on_page' => $time_on_page,
                'scroll_depth' => $scroll_depth,
                'sequence_number' => $sequence_number
            ],
            ['%s', '%s', '%s', '%s', '%d', '%d', '%d']
        );

        if ($result === false) {
            error_log('SQT Error in track_pageview: ' . $wpdb->last_error);
            wp_send_json_error('Failed to track pageview');
        }

        wp_send_json_success();
    }

    /**
     * Update pageview metrics (time on page, scroll depth)
     * Called when user leaves page or during long sessions
     */
    public function update_pageview() {
        if (!isset($_POST['session_id']) || !isset($_POST['page_url'])) {
            wp_send_json_error('Missing required parameters');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sqt_pageviews';

        // Check if table exists - if not, silently fail
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            wp_send_json_success(); // Silent success to avoid frontend errors
            return;
        }

        $session_id = sanitize_text_field($_POST['session_id']);
        $page_url = esc_url_raw($_POST['page_url']);
        $time_on_page = isset($_POST['time_on_page']) ? intval($_POST['time_on_page']) : 0;
        $scroll_depth = isset($_POST['scroll_depth']) ? intval($_POST['scroll_depth']) : 0;

        // Update the most recent pageview for this session and URL
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name
                 SET time_on_page = %d, scroll_depth = %d
                 WHERE session_id = %s AND page_url = %s
                 ORDER BY viewed_at DESC
                 LIMIT 1",
                $time_on_page,
                $scroll_depth,
                $session_id,
                $page_url
            )
        );

        if ($result === false) {
            error_log('SQT Error in update_pageview: ' . $wpdb->last_error);
            wp_send_json_error('Failed to update pageview');
        }

        wp_send_json_success();
    }

    /**
     * Detect referrer type and extract search engine
     */
    private function detect_referrer_type($referrer, $utm_source, $utm_medium) {
        $type = 'direct';
        $search_engine = null;

        // UTM parameters take priority
        if (!empty($utm_source)) {
            if (stripos($utm_medium, 'email') !== false || stripos($utm_source, 'email') !== false) {
                return ['type' => 'email', 'search_engine' => null];
            }
            if (stripos($utm_medium, 'social') !== false || stripos($utm_source, 'social') !== false) {
                return ['type' => 'social', 'search_engine' => null];
            }
            return ['type' => 'campaign', 'search_engine' => null];
        }

        // No referrer = direct traffic
        if (empty($referrer)) {
            return ['type' => 'direct', 'search_engine' => null];
        }

        $domain = parse_url($referrer, PHP_URL_HOST);
        if (!$domain) {
            return ['type' => 'direct', 'search_engine' => null];
        }

        // Search engines
        $search_engines = [
            'google.' => 'Google',
            'bing.' => 'Bing',
            'yahoo.' => 'Yahoo',
            'duckduckgo.' => 'DuckDuckGo',
            'baidu.' => 'Baidu',
            'yandex.' => 'Yandex',
            'ecosia.' => 'Ecosia',
            'ask.' => 'Ask'
        ];

        foreach ($search_engines as $pattern => $engine) {
            if (stripos($domain, $pattern) !== false) {
                return ['type' => 'search', 'search_engine' => $engine];
            }
        }

        // Social media platforms
        $social_platforms = [
            'facebook.', 'twitter.', 'linkedin.', 'instagram.',
            'tiktok.', 'pinterest.', 'reddit.', 'youtube.',
            'snapchat.', 'whatsapp.'
        ];

        foreach ($social_platforms as $platform) {
            if (stripos($domain, $platform) !== false) {
                return ['type' => 'social', 'search_engine' => null];
            }
        }

        // Everything else is referral traffic
        return ['type' => 'referral', 'search_engine' => null];
    }

    /**
     * Parse user agent to extract device, browser, and OS
     */
    private function parse_user_agent($user_agent) {
        if (empty($user_agent)) {
            return ['device_type' => 'unknown', 'browser' => 'Unknown', 'os' => 'Unknown'];
        }

        // Device type detection
        $device_type = 'desktop';
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $user_agent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $user_agent)) {
            $device_type = 'tablet';
        }

        // Browser detection
        $browser = 'Unknown';
        if (preg_match('/Edge/i', $user_agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/MSIE|Trident/i', $user_agent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Opera|OPR/i', $user_agent)) {
            $browser = 'Opera';
        }

        // OS detection
        $os = 'Unknown';
        if (preg_match('/Windows NT 10/i', $user_agent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6.3/i', $user_agent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6.2/i', $user_agent)) {
            $os = 'Windows 8';
        } elseif (preg_match('/Windows NT 6.1/i', $user_agent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/Windows/i', $user_agent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $user_agent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $user_agent)) {
            $os = 'iOS';
        }

        return [
            'device_type' => $device_type,
            'browser' => $browser,
            'os' => $os
        ];
    }

    /**
     * Anonymize IP address for privacy
     */
    private function anonymize_ip($ip) {
        if (empty($ip)) {
            return '';
        }

        // Hash the IP with a salt for anonymization
        // This allows counting unique visitors without storing actual IPs
        return hash('sha256', $ip . 'sqt_salt_' . AUTH_KEY);
    }

    /**
     * Get analytics data for dashboard
     */
    public function get_analytics() {
        // Check nonce for security
        check_ajax_referer('sqt_nonce', 'nonce');

        // Only allow administrators
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        global $wpdb;
        $sessions_table = $wpdb->prefix . 'sqt_sessions';
        $pageviews_table = $wpdb->prefix . 'sqt_pageviews';

        // Check if tables exist
        $tables_exist = (
            $wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table &&
            $wpdb->get_var("SHOW TABLES LIKE '$pageviews_table'") === $pageviews_table
        );

        if (!$tables_exist) {
            // Trigger creation
            $this->check_database();

            // Return empty data
            wp_send_json_success([
                'overview' => (object)[
                    'total_sessions' => 0,
                    'total_pageviews' => 0,
                    'avg_pages_per_session' => 0,
                    'avg_session_duration' => 0,
                    'bounce_rate' => 0
                ],
                'traffic_sources' => [],
                'landing_pages' => [],
                'search_engines' => [],
                'devices' => []
            ]);
            return;
        }

        // Get date range from POST (default: last 30 days)
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;

        // Overview stats
        $overview = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_sessions,
                SUM(pages_viewed) as total_pageviews,
                AVG(pages_viewed) as avg_pages_per_session,
                AVG(duration) as avg_duration,
                SUM(bounced) / COUNT(*) * 100 as bounce_rate
            FROM $sessions_table
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));

        // Traffic sources
        $traffic_sources = $wpdb->get_results($wpdb->prepare("
            SELECT
                referrer_type,
                COUNT(*) as sessions,
                AVG(pages_viewed) as avg_pages,
                AVG(duration) as avg_duration
            FROM $sessions_table
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY referrer_type
            ORDER BY sessions DESC
        ", $days));

        // Add labels to referrer types
        $referrer_labels = [
            'direct' => 'Direct',
            'search' => 'Search Engines',
            'social' => 'Social Media',
            'referral' => 'Referral',
            'email' => 'Email',
            'campaign' => 'Campaign'
        ];

        foreach ($traffic_sources as $source) {
            $source->referrer_type_label = isset($referrer_labels[$source->referrer_type])
                ? $referrer_labels[$source->referrer_type]
                : ucfirst($source->referrer_type);
        }

        // Top landing pages
        $landing_pages = $wpdb->get_results($wpdb->prepare("
            SELECT
                landing_page,
                COUNT(*) as sessions,
                AVG(duration) as avg_duration,
                SUM(bounced) / COUNT(*) * 100 as bounce_rate
            FROM $sessions_table
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY landing_page
            ORDER BY sessions DESC
            LIMIT 10
        ", $days));

        // Search engines breakdown
        $search_engines = $wpdb->get_results($wpdb->prepare("
            SELECT
                search_engine,
                COUNT(*) as sessions
            FROM $sessions_table
            WHERE referrer_type = 'search'
              AND started_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
              AND search_engine IS NOT NULL
            GROUP BY search_engine
            ORDER BY sessions DESC
        ", $days));

        // Device breakdown
        $devices = $wpdb->get_results($wpdb->prepare("
            SELECT
                device_type,
                COUNT(*) as sessions,
                AVG(pages_viewed) as avg_pages
            FROM $sessions_table
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY device_type
            ORDER BY sessions DESC
        ", $days));

        // Ensure overview has default values if null
        if (!$overview) {
            $overview = (object)[
                'total_sessions' => 0,
                'total_pageviews' => 0,
                'avg_pages_per_session' => 0,
                'avg_session_duration' => 0,
                'bounce_rate' => 0
            ];
        } else {
            // Convert avg_duration to integer (seconds)
            $overview->avg_session_duration = intval($overview->avg_duration ?? 0);
        }

        wp_send_json_success([
            'overview' => $overview,
            'traffic_sources' => $traffic_sources,
            'landing_pages' => $landing_pages,
            'search_engines' => $search_engines,
            'devices' => $devices
        ]);
    }
}

// Include dashboard functionality
require_once 'dashboard.php';

// Initialize the app using singleton pattern
add_action('plugins_loaded', array('SQT_App', 'get_instance'));
