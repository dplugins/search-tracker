<?php
// ************************************************************************************************
// Updater Version: 1.0.1
// ************************************************************************************************

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('DPUpdateChecker')) {

    class DPUpdateChecker {

        public $slug;
        public $version;
        public $cache_key;
        public $remote_url;
        public $type;

        public function __construct($slug, $version, $cache_key, $remote_url, $type = 'plugin') {
            $this->slug = $slug;
            $this->version = $version;
            $this->cache_key = $cache_key;
            $this->remote_url = $remote_url;
            $this->type = $type;
            add_action('admin_init', array($this, 'check_update_conditions'));
            if ($this->type === 'plugin') {
                add_action('plugin_row_meta', array($this, 'add_check_updates_button'), 10, 2);
                add_action('admin_post_check_for_updates', array($this, 'check_for_updates'));
            } else {
                add_action('admin_notices', array($this, 'add_check_updates_notice'));
                add_action('admin_post_check_for_updates_theme', array($this, 'check_for_updates_theme'));
            }
        }

        public function check_update_conditions() {
            if ($this->type === 'plugin') {
                add_filter('plugins_api', array($this, 'info'), 20, 3);
                add_filter('site_transient_update_plugins', array($this, 'update_plugins'));
            } else {
                add_filter('site_transient_update_themes', array($this, 'update_themes'));
            }
            add_action('upgrader_process_complete', array($this, 'purge'), 10, 2);
        }

        public function request() {
            $remote = get_transient($this->cache_key);

            if (false === $remote) {
                $response = wp_remote_get(
                    $this->remote_url,
                    array(
                        'timeout' => 10,
                        'headers' => array('Accept' => 'application/json')
                    )
                );

                if (
                    is_wp_error($response)
                    || 200 !== wp_remote_retrieve_response_code($response)
                    || empty(wp_remote_retrieve_body($response))
                ) {
                    error_log('Hoster update check error: ' . wp_remote_retrieve_body($response) ?: $response->get_error_message());
                    return false;
                }

                $remote = wp_remote_retrieve_body($response);
                set_transient($this->cache_key, $remote, 6 * HOUR_IN_SECONDS); // Cache for 6 hours
            }

            if(!is_array($remote)){
                $remote = json_decode($remote);
            }
            return $remote;
        }

        public function info($res, $action, $args) {
            if ($this->type !== 'plugin' || 'plugin_information' !== $action || $this->slug !== $args->slug) {
                return $res;
            }

            $remote = $this->request();
            if (!$remote) return $res;

            $res = new stdClass();
            $res->name = $remote->name;
            $res->slug = $remote->slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author;
            $res->author_profile = $remote->author_profile;
            $res->download_link = $remote->download_url;
            $res->trunk = $remote->download_url;
            $res->requires_php = $remote->requires_php;
            $res->last_updated = $remote->last_updated;
            $res->sections = (array)$remote->sections;

            if (!empty($remote->banners)) {
                $res->banners = (array)$remote->banners;
            }

            return $res;
        }

        public function update_plugins($transient) {
            if (empty($transient->checked)) return $transient;

            $remote = $this->request();
            if (!$remote) return $transient;

            if (
                version_compare($this->version, $remote->version, '<')
                && version_compare($remote->requires, get_bloginfo('version'), '<=')
                && version_compare($remote->requires_php, PHP_VERSION, '<=')
            ) {
                $res = new stdClass();
                $res->slug = $this->slug;
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->download_url;

                $res->plugin = $this->slug;
                $transient->response[$res->plugin] = $res;
            }

            return $transient;
        }

        public function update_themes($transient) {
            if (empty($transient->checked)) return $transient;

            $remote = $this->request();
            if (!$remote) return $transient;

            if (
                version_compare($this->version, $remote->version, '<') &&
                version_compare($remote->requires, get_bloginfo('version'), '<=') &&
                version_compare($remote->requires_php, PHP_VERSION, '<=')
            ) {
                $res = array(
                    'theme' => $this->slug,
                    'new_version' => $remote->version,
                    'requires' => $remote->requires,
                    'requires_php' => $remote->requires_php,
                    'package' => $remote->download_url,
                    'url' => $remote->download_url
                );

                $transient->response[$this->slug] = $res;
            }

            return $transient;
        }

        public function purge($upgrader, $options) {
            if ('update' === $options['action'] && $this->type === $options['type']) {
                delete_transient($this->cache_key);
            }
        }

        public function add_check_updates_button($plugin_meta, $plugin_file) {
            if ($plugin_file === $this->slug) {
                $url = wp_nonce_url(admin_url('admin-post.php?action=check_for_updates&plugin=' . $this->slug), 'check_for_updates');
                $plugin_meta[] = '<a href="' . esc_url($url) . '">Check for updates</a>';
            }
            return $plugin_meta;
        }

        public function check_for_updates() {
            if (!current_user_can('update_plugins')) {
                wp_die(__('You do not have sufficient permissions to update plugins.'));
            }

            check_admin_referer('check_for_updates');

            // Delete the transient
            delete_transient($this->cache_key);
            // Trigger the update check
            $this->request();
            // Redirect back to the plugins page
            wp_redirect(admin_url('plugins.php'));
            exit;
        }

        public function add_check_updates_notice() {
            $current_screen = get_current_screen();
            if (current_user_can('update_themes') && !empty($current_screen) && $current_screen->id == "themes") {
                // Get the current theme data
                $theme = wp_get_theme($this->slug);
                $theme_name = $theme->get('Name');
        
                // Get the theme updates transient
                $theme_updates = get_site_transient('update_themes');
        
                // Check if there's an update available for the current theme
                $theme_update_available = false;
                if (isset($theme_updates->response[$this->slug])) {
                    $theme_update_available = true;
                }
        
                // Display the notice only if there's no update available
                if (!$theme_update_available) {
                    $url = wp_nonce_url(admin_url('admin-post.php?action=check_for_updates_theme&theme=' . $this->slug), 'check_for_updates_theme');
                    echo '<div class="notice notice-info is-dismissible">
                            <p><strong>Theme: ' . esc_html($theme_name) . '</strong> | <a href="' . esc_url($url) . '">Check for updates</a>.</p>
                        </div>';
                }
            }
        }

        public function check_for_updates_theme() {
            if (!current_user_can('update_themes')) {
                wp_die(__('You do not have sufficient permissions to update themes.'));
            }

            check_admin_referer('check_for_updates_theme');

            // Delete the transient
            delete_transient($this->cache_key);
            // Trigger the update check
            $this->request();
            // Redirect back to the themes page
            wp_redirect(admin_url('themes.php'));
            exit;
        }
    }
}