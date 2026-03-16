<?php
/**
 * Uninstall script for Search Query Tracker
 *
 * This file is called automatically when the plugin is deleted through the WordPress admin.
 * It cleans up all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop custom database tables
$searches_table = $wpdb->prefix . 'sqt_searches';
$clicks_table = $wpdb->prefix . 'sqt_clicks';

$wpdb->query("DROP TABLE IF EXISTS $searches_table");
$wpdb->query("DROP TABLE IF EXISTS $clicks_table");

// Delete all plugin options
delete_option('sqt_search_queries');
delete_option('sqt_search_clicks');
delete_option('sqt_db_version');
delete_option('sqt_data_migrated');

// Clean up any transients (rate limiting)
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sqt_rate_limit_%' OR option_name LIKE '_transient_timeout_sqt_rate_limit_%'");

// For multisite installations, clean up all sites
if (is_multisite()) {
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);

        // Drop tables for this site
        $searches_table = $wpdb->prefix . 'sqt_searches';
        $clicks_table = $wpdb->prefix . 'sqt_clicks';

        $wpdb->query("DROP TABLE IF EXISTS $searches_table");
        $wpdb->query("DROP TABLE IF EXISTS $clicks_table");

        // Delete options
        delete_option('sqt_search_queries');
        delete_option('sqt_search_clicks');
        delete_option('sqt_db_version');
        delete_option('sqt_data_migrated');

        // Delete transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sqt_rate_limit_%' OR option_name LIKE '_transient_timeout_sqt_rate_limit_%'");

        restore_current_blog();
    }
}
