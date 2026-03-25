<?php
/**
 * Install Blocker Class
 * Handles blocking plugin installation
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_Install_Blocker {
    
    private $option_name = 'disable_wp_install_enabled';
    
    /**
     * Check if installation blocking is enabled
     */
    public function is_enabled() {
        return get_option($this->option_name, '1') === '1';
    }
    
    /**
     * Apply plugin installation blocking filters
     */
    public function apply_filters() {
        // Remove "Add New" submenu from Plugins menu
        add_action('admin_menu', function () {
            remove_submenu_page('plugins.php', 'plugin-install.php');
        }, 999);
        
        // Block direct access to plugin install page
        add_action('admin_init', function () {
            if (basename($_SERVER['PHP_SELF']) === 'plugin-install.php') {
                wp_die('Cài đặt plugin đã bị vô hiệu hóa.');
            }
        });
        
        // Block plugin installation via AJAX
        add_filter('user_has_cap', function ($allcaps, $caps, $args) {
            if (isset($args[0]) && $args[0] === 'install_plugins') {
                $allcaps['install_plugins'] = false;
            }
            return $allcaps;
        }, 10, 3);
        
        // Block plugin upload
        add_filter('user_has_cap', function ($allcaps, $caps, $args) {
            if (isset($args[0]) && $args[0] === 'upload_plugins') {
                $allcaps['upload_plugins'] = false;
            }
            return $allcaps;
        }, 10, 3);
        
        // Block plugin installation actions
        add_action('admin_init', function () {
            if (isset($_GET['action']) && in_array($_GET['action'], array('install-plugin', 'upload-plugin'))) {
                if (basename($_SERVER['PHP_SELF']) === 'plugin-install.php' || basename($_SERVER['PHP_SELF']) === 'plugins.php') {
                    wp_die('Cài đặt plugin đã bị vô hiệu hóa.');
                }
            }
        });
        
        // Block plugin installation via REST API
        add_filter('rest_pre_dispatch', function ($result, $server, $request) {
            $route = $request->get_route();
            if (strpos($route, '/wp/v2/plugins') !== false && $request->get_method() === 'POST') {
                return new WP_Error('install_disabled', 'Cài đặt plugin đã bị vô hiệu hóa.', array('status' => 403));
            }
            return $result;
        }, 10, 3);
        
        // Block plugin installation hooks
        add_filter('plugins_api', '__return_false', 10, 3);
        
        // Block plugin installation process
        add_filter('pre_install_plugin', '__return_false', 10, 3);
        add_filter('pre_upgrade_plugin', '__return_false', 10, 3);
        
        // Block plugin upload process
        add_filter('pre_upload_plugin', '__return_false', 10, 3);
        
        // Block plugin installation via upgrader
        add_filter('upgrader_pre_install', function ($return, $package, $upgrader) {
            if (isset($upgrader->skin->plugin) || (isset($upgrader->skin->plugin_info) && !empty($upgrader->skin->plugin_info))) {
                return new WP_Error('install_disabled', 'Cài đặt plugin đã bị vô hiệu hóa.');
            }
            return $return;
        }, 10, 3);
        
        // Remove "Add New" button from plugins page
        add_action('admin_head', function () {
            if (get_current_screen()->id === 'plugins') {
                echo '<style>a.page-title-action { display: none !important; }</style>';
            }
        });
    }
}

