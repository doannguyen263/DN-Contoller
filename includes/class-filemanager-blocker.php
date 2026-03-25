<?php
/**
 * File Manager Blocker Class
 * Handles blocking File Manager plugins
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_FileManager_Blocker {
    
    private $option_name = 'disable_filemanager_plugins_enabled';
    
    /**
     * Check if File Manager blocking is enabled
     */
    public function is_enabled() {
        return get_option($this->option_name, '1') === '1';
    }
    
    /**
     * Check if plugin is a File Manager plugin
     */
    private function is_filemanager_plugin($plugin_slug, $plugin_name = '') {
        $filemanager_keywords = array(
            'file-manager',
            'filemanager',
            'file_manager',
            'wp-file-manager',
            'wp-filemanager',
            'wpfilemanager',
            'file-manager-advanced',
            'responsive-file-manager',
            'file-manager-pro',
            'advanced-file-manager',
            'cute-file-manager',
            'elfinder',
            'filebrowser',
            'file-browser'
        );
        
        $plugin_slug_lower = strtolower($plugin_slug);
        $plugin_name_lower = strtolower($plugin_name);
        
        foreach ($filemanager_keywords as $keyword) {
            if (strpos($plugin_slug_lower, $keyword) !== false || 
                strpos($plugin_name_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Apply File Manager plugin blocking filters
     */
    public function apply_filters() {
        // Create closure-friendly reference
        $self = $this;
        
        // Block File Manager plugin installation via upgrader
        add_filter('upgrader_pre_install', function ($return, $package, $upgrader = null) use ($self) {
            $plugin_slug = '';
            $plugin_name = '';
            
            // Get plugin info from different sources
            if ($upgrader && isset($upgrader->skin->plugin)) {
                $plugin_slug = $upgrader->skin->plugin;
            } elseif ($upgrader && isset($upgrader->skin->plugin_info) && !empty($upgrader->skin->plugin_info)) {
                if (isset($upgrader->skin->plugin_info['slug'])) {
                    $plugin_slug = $upgrader->skin->plugin_info['slug'];
                }
                if (isset($upgrader->skin->plugin_info['name'])) {
                    $plugin_name = $upgrader->skin->plugin_info['name'];
                }
            } elseif ($upgrader && isset($upgrader->skin->api) && !empty($upgrader->skin->api)) {
                if (isset($upgrader->skin->api->slug)) {
                    $plugin_slug = $upgrader->skin->api->slug;
                }
                if (isset($upgrader->skin->api->name)) {
                    $plugin_name = $upgrader->skin->api->name;
                }
            }
            
            // Check if it's a File Manager plugin
            if (!empty($plugin_slug) || !empty($plugin_name)) {
                if ($self->is_filemanager_plugin($plugin_slug, $plugin_name)) {
                    return new WP_Error('filemanager_blocked', 'Cài đặt plugin File Manager đã bị chặn vì lý do bảo mật.');
                }
            }

            // Fallback: if WP doesn't pass $upgrader (or we can't extract slug/name), try inspecting the ZIP.
            $package_lower = is_string($package) ? strtolower($package) : '';
            if (empty($plugin_slug) && empty($plugin_name) && is_string($package) && function_exists('zip_open') && substr($package_lower, -4) === '.zip' && @is_readable($package)) {
                $zip = zip_open($package);
                if (is_resource($zip)) {
                    while ($zip_entry = zip_read($zip)) {
                        $entry_name = zip_entry_name($zip_entry);
                        if ($entry_name && $self->is_filemanager_plugin($entry_name, $entry_name)) {
                            zip_close($zip);
                            return new WP_Error('filemanager_blocked', 'Cài đặt plugin File Manager đã bị chặn vì lý do bảo mật.');
                        }
                    }
                    zip_close($zip);
                }
            }
            
            return $return;
        }, 10, 3);
        
        // Block File Manager plugin installation via plugins_api
        add_filter('plugins_api_result', function ($result, $action, $args = null) use ($self) {
            if ($action === 'plugin_information' && isset($result->slug)) {
                if ($self->is_filemanager_plugin($result->slug, isset($result->name) ? $result->name : '')) {
                    return new WP_Error('filemanager_blocked', 'Plugin File Manager đã bị chặn vì lý do bảo mật.');
                }
            }
            return $result;
        }, 10, 3);
        
        // Block File Manager plugin search results
        add_filter('plugins_api_result', function ($result, $action, $args = null) use ($self) {
            if ($action === 'query_plugins' && isset($result->plugins) && is_array($result->plugins)) {
                $filtered_plugins = array();
                foreach ($result->plugins as $plugin) {
                    $plugin_slug = isset($plugin['slug']) ? $plugin['slug'] : '';
                    $plugin_name = isset($plugin['name']) ? $plugin['name'] : '';
                    if (!$self->is_filemanager_plugin($plugin_slug, $plugin_name)) {
                        $filtered_plugins[] = $plugin;
                    }
                }
                $result->plugins = $filtered_plugins;
                $result->info['results'] = count($filtered_plugins);
            }
            return $result;
        }, 10, 3);
        
        // Block File Manager plugin upload
        add_filter('wp_handle_upload_prefilter', function ($file) use ($self) {
            $filename = isset($file['name']) ? $file['name'] : '';
            $tmp_name = isset($file['tmp_name']) ? $file['tmp_name'] : '';
            
            // Check filename
            if ($self->is_filemanager_plugin($filename, $filename)) {
                $file['error'] = 'Cài đặt plugin File Manager đã bị chặn vì lý do bảo mật.';
                return $file;
            }
            
            // Check ZIP file contents if it's a ZIP
            if (strtolower(substr($filename, -4)) === '.zip' && !empty($tmp_name)) {
                $zip = zip_open($tmp_name);
                if (is_resource($zip)) {
                    while ($zip_entry = zip_read($zip)) {
                        $entry_name = zip_entry_name($zip_entry);
                        if ($self->is_filemanager_plugin($entry_name, $entry_name)) {
                            zip_close($zip);
                            $file['error'] = 'Cài đặt plugin File Manager đã bị chặn vì lý do bảo mật.';
                            return $file;
                        }
                    }
                    zip_close($zip);
                }
            }
            
            return $file;
        });
        
        // Block File Manager plugin activation if already installed
        add_filter('plugin_action_links', function ($actions, $plugin_file, $plugin_data, $context) use ($self) {
            $plugin_slug = dirname($plugin_file);
            $plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : '';
            
            if ($self->is_filemanager_plugin($plugin_slug, $plugin_name)) {
                // Remove activate link
                if (isset($actions['activate'])) {
                    unset($actions['activate']);
                }
                // Add warning message
                $actions['filemanager_blocked'] = '<span style="color: red;">File Manager plugins bị chặn</span>';
            }
            
            return $actions;
        }, 10, 4);
    }
}

