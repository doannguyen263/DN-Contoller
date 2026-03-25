<?php
/**
 * Login Blocker Class
 * Handles blocking login and registration for specific usernames
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_Login_Blocker {
    
    private $option_name = 'disable_login_blocker_enabled';
    private $blocked_usernames_option = 'blocked_usernames_list';
    
    /**
     * Default blocked usernames list
     */
    private function get_default_blocked_usernames() {
        return array(
            'admin',
            'administrator',
            'root',
            'test',
            'demo',
            'user',
            'guest',
            'wordpressauto',
            'WhoAdminKnows',
            'wadminw',
            'wp-manager'
        );
    }
    
    /**
     * Get default blocked usernames list (public)
     */
    public function get_default_blocked_usernames_list() {
        return $this->get_default_blocked_usernames();
    }
    
    /**
     * Get blocked usernames list
     */
    public function get_blocked_usernames() {
        $custom_list = get_option($this->blocked_usernames_option, '');
        $default_list = $this->get_default_blocked_usernames();
        
        // Merge default and custom list
        if (!empty($custom_list)) {
            $custom_array = array_map('trim', explode("\n", $custom_list));
            $custom_array = array_filter($custom_array); // Remove empty lines
            $merged = array_merge($default_list, $custom_array);
            return array_unique(array_map('strtolower', $merged));
        }
        
        return array_map('strtolower', $default_list);
    }
    
    /**
     * Check if login blocking is enabled
     */
    public function is_enabled() {
        return get_option($this->option_name, '1') === '1';
    }
    
    /**
     * Check if username is blocked
     */
    public function is_username_blocked($username) {
        if (empty($username)) {
            return false;
        }
        
        $blocked_list = $this->get_blocked_usernames();
        $username_lower = strtolower(trim($username));
        
        return in_array($username_lower, $blocked_list, true);
    }
    
    /**
     * Apply login and registration blocking filters
     */
    public function apply_filters() {
        // Block login
        add_filter('authenticate', array($this, 'block_login'), 30, 3);
        
        // Block registration
        add_filter('registration_errors', array($this, 'block_registration'), 10, 3);
        
        // Block registration via REST API
        add_filter('rest_pre_insert_user', array($this, 'block_rest_registration'), 10, 2);
        
        // Block user creation via admin
        add_action('user_profile_update_errors', array($this, 'block_admin_user_creation'), 10, 3);
    }
    
    /**
     * Block login attempt
     */
    public function block_login($user, $username, $password) {
        if (empty($username)) {
            return $user;
        }
        
        if ($this->is_username_blocked($username)) {
            // Return error
            return new WP_Error(
                'blocked_username',
                '<strong>Lỗi:</strong> Tên đăng nhập này đã bị chặn.'
            );
        }
        
        return $user;
    }
    
    /**
     * Block registration
     */
    public function block_registration($errors, $sanitized_user_login, $user_email) {
        if ($this->is_username_blocked($sanitized_user_login)) {
            $errors->add(
                'blocked_username',
                '<strong>Lỗi:</strong> Tên đăng nhập này đã bị chặn và không thể đăng ký.'
            );
        }
        
        return $errors;
    }
    
    /**
     * Block registration via REST API
     */
    public function block_rest_registration($user_data, $request) {
        $username = isset($user_data['user_login']) ? $user_data['user_login'] : '';
        
        if ($this->is_username_blocked($username)) {
            return new WP_Error(
                'blocked_username',
                'Tên đăng nhập này đã bị chặn và không thể đăng ký.',
                array('status' => 403)
            );
        }
        
        return $user_data;
    }
    
    /**
     * Block user creation via admin
     */
    public function block_admin_user_creation($errors, $update, $user) {
        if ($update) {
            // Don't block existing users being updated
            return;
        }
        
        $username = isset($user->user_login) ? $user->user_login : '';
        
        if ($this->is_username_blocked($username)) {
            $errors->add(
                'blocked_username',
                '<strong>Lỗi:</strong> Tên đăng nhập này đã bị chặn và không thể tạo tài khoản.'
            );
        }
    }
}

