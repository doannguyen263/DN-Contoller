<?php
/**
 * Plugin Name: DN Contoller
 * Description: DN Contoller.
 * Version: 1.0.4
 * Author: You
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('DN_CONTROLLER_VERSION')) {
    define('DN_CONTROLLER_VERSION', '1.1');
}
if (!defined('DN_CONTROLLER_PATH')) {
    define('DN_CONTROLLER_PATH', plugin_dir_path(__FILE__));
}
if (!defined('DN_CONTROLLER_URL')) {
    define('DN_CONTROLLER_URL', plugin_dir_url(__FILE__));
}
if (!defined('DN_CONTROLLER_BASENAME')) {
    define('DN_CONTROLLER_BASENAME', plugin_basename(__FILE__));
}

// Load main plugin class
require_once DN_CONTROLLER_PATH . 'includes/class-dn-controller.php';

// Initialize plugin
function dn_controller_init() {
    return DN_Controller::get_instance();
}

// Initialize admin if in admin area
if (is_admin()) {
    require_once DN_CONTROLLER_PATH . 'admin/class-admin.php';
    new DN_Controller_Admin();
}

// Start the plugin
dn_controller_init();
