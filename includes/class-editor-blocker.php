<?php
/**
 * Editor Blocker Class
 * Handles blocking theme and plugin editors
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_Editor_Blocker {
    
    private $option_name = 'disable_wp_editor_enabled';
    
    /**
     * Check if editor blocking is enabled
     */
    public function is_enabled() {
        return get_option($this->option_name, '1') === '1';
    }
    
    /**
     * Apply editor blocking filters
     */
    public function apply_filters() {
        // Disable file editing
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        // Remove Theme Editor from Appearance menu
        add_action('admin_menu', function () {
            remove_submenu_page('themes.php', 'theme-editor.php');
        }, 999);
        
        // Remove Plugin Editor from Plugins menu
        add_action('admin_menu', function () {
            remove_submenu_page('plugins.php', 'plugin-editor.php');
        }, 999);
        
        // Block direct access to theme editor
        add_action('admin_init', function () {
            if (isset($_GET['file']) && isset($_GET['theme']) && basename($_SERVER['PHP_SELF']) === 'theme-editor.php') {
                wp_die('Theme editor đã bị vô hiệu hóa.');
            }
        });
        
        // Block direct access to plugin editor
        add_action('admin_init', function () {
            if (isset($_GET['file']) && basename($_SERVER['PHP_SELF']) === 'plugin-editor.php') {
                wp_die('Plugin editor đã bị vô hiệu hóa.');
            }
        });
        
        // Remove editor links from admin bar
        add_action('admin_bar_menu', function ($wp_admin_bar) {
            $wp_admin_bar->remove_node('theme-editor');
        }, 999);
    }
}

