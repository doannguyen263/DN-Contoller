<?php
/**
 * Updates Blocker Class
 * Handles blocking WordPress updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_Updates_Blocker {
    
    private $option_name = 'disable_wp_updates_enabled';
    
    /**
     * Check if updates blocking is enabled
     */
    public function is_enabled() {
        return get_option($this->option_name, '1') === '1';
    }
    
    /**
     * Apply all disable update filters
     */
    public function apply_filters() {
        // Disable automatic updater
        if (!defined('AUTOMATIC_UPDATER_DISABLED')) {
            define('AUTOMATIC_UPDATER_DISABLED', true);
        }
        if (!defined('WP_AUTO_UPDATE_CORE')) {
            define('WP_AUTO_UPDATE_CORE', false);
        }
        
        // Block Core updates
        add_filter('pre_site_transient_update_core', '__return_null');
        add_filter('site_transient_update_core', '__return_null');
        
        // Block Plugin updates
        add_filter('pre_site_transient_update_plugins', '__return_null');
        add_filter('site_transient_update_plugins', '__return_null');
        
        // Block Theme updates
        add_filter('pre_site_transient_update_themes', '__return_null');
        add_filter('site_transient_update_themes', '__return_null');
        
        // Block Translation updates
        add_filter('pre_site_transient_update_translations', '__return_null');
        add_filter('site_transient_update_translations', '__return_null');
        
        // Disable auto-update for Core
        add_filter('auto_update_core', '__return_false');
        add_filter('allow_major_auto_core_updates', '__return_false');
        add_filter('allow_minor_auto_core_updates', '__return_false');
        add_filter('allow_dev_auto_core_updates', '__return_false');
        
        // Disable auto-update for Plugin
        add_filter('auto_update_plugin', '__return_false');
        
        // Disable auto-update for Theme
        add_filter('auto_update_theme', '__return_false');
        
        // Disable auto-update for Translation
        add_filter('auto_update_translation', '__return_false');
        
        // Disable background update
        add_filter('automatic_updater_disabled', '__return_true');
        
        // Block WP-Cron update checks
        add_filter('wp_version_check', '__return_false');
        add_filter('wp_update_plugins', '__return_false');
        add_filter('wp_update_themes', '__return_false');
        
        // Block update checks via HTTP requests
        $install_disabled = get_option('disable_wp_install_enabled', '1') === '1';
        add_filter('pre_http_request', function ($preempt, $parsed_args, $url) use ($install_disabled) {
            // Block requests to WordPress API for updates only
            // Allow plugin installation requests if install is not disabled
            if (strpos($url, 'api.wordpress.org') !== false) {
                // Check if this is an update-related request
                $update_patterns = array(
                    '/core/update-check',
                    '/plugins/update-check',
                    '/themes/update-check',
                    '/translations/update-check',
                    '/core/version-check',
                    '/plugins/version-check',
                    '/themes/version-check'
                );
                
                $is_update_request = false;
                foreach ($update_patterns as $pattern) {
                    if (strpos($url, $pattern) !== false) {
                        $is_update_request = true;
                        break;
                    }
                }
                
                // Only block if it's an update request
                // If install is also disabled, block all api.wordpress.org requests
                if ($is_update_request || $install_disabled) {
                    return new WP_Error('update_disabled', 'Updates are disabled');
                }
            }
            return $preempt;
        }, 10, 3);
        
        // Disable update notification emails
        add_filter('auto_core_update_send_email', '__return_false');
        add_filter('send_core_update_notification_email', '__return_false');
        add_filter('automatic_updates_send_debug_email', '__return_false');
        
        // Remove update notices from admin
        add_action('admin_init', function () {
            remove_action('admin_notices', 'update_nag', 3);
            remove_action('admin_notices', 'maintenance_nag', 10);
            remove_action('network_admin_notices', 'update_nag', 3);
            remove_action('network_admin_notices', 'maintenance_nag', 10);
        });
        
        // Remove update submenu from Dashboard
        add_action('admin_menu', function () {
            remove_submenu_page('index.php', 'update-core.php');
        }, 999);
        
        // Remove update links from admin bar
        add_action('admin_bar_menu', function ($wp_admin_bar) {
            $wp_admin_bar->remove_node('updates');
        }, 999);
        
        // Block update checks on admin pages
        add_action('admin_init', function () {
            if (isset($_GET['force-check'])) {
                wp_redirect(admin_url());
                exit;
            }
        }, 1);
    }
}

