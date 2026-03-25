<?php
/**
 * Main Plugin Class
 * Manages all blocker modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_Controller {
    
    /**
     * Plugin version
     */
    const VERSION = '1.1';
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Blocker instances
     */
    private $updates_blocker;
    private $editor_blocker;
    private $install_blocker;
    private $filemanager_blocker;
    private $login_blocker;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize plugin
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_blockers();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once DN_CONTROLLER_PATH . 'includes/class-updates-blocker.php';
        require_once DN_CONTROLLER_PATH . 'includes/class-editor-blocker.php';
        require_once DN_CONTROLLER_PATH . 'includes/class-install-blocker.php';
        require_once DN_CONTROLLER_PATH . 'includes/class-filemanager-blocker.php';
        require_once DN_CONTROLLER_PATH . 'includes/class-login-blocker.php';
    }
    
    /**
     * Initialize blocker classes
     */
    private function init_blockers() {
        $this->updates_blocker = new DN_Updates_Blocker();
        $this->editor_blocker = new DN_Editor_Blocker();
        $this->install_blocker = new DN_Install_Blocker();
        $this->filemanager_blocker = new DN_FileManager_Blocker();
        $this->login_blocker = new DN_Login_Blocker();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Apply filters if enabled
        if ($this->updates_blocker->is_enabled()) {
            $this->updates_blocker->apply_filters();
        }
        
        if ($this->editor_blocker->is_enabled()) {
            $this->editor_blocker->apply_filters();
        }
        
        if ($this->install_blocker->is_enabled()) {
            $this->install_blocker->apply_filters();
        }
        
        if ($this->filemanager_blocker->is_enabled()) {
            $this->filemanager_blocker->apply_filters();
        }
        
        if ($this->login_blocker->is_enabled()) {
            $this->login_blocker->apply_filters();
        }
    }
    
    /**
     * Get updates blocker instance
     */
    public function get_updates_blocker() {
        return $this->updates_blocker;
    }
    
    /**
     * Get editor blocker instance
     */
    public function get_editor_blocker() {
        return $this->editor_blocker;
    }
    
    /**
     * Get install blocker instance
     */
    public function get_install_blocker() {
        return $this->install_blocker;
    }
    
    /**
     * Get filemanager blocker instance
     */
    public function get_filemanager_blocker() {
        return $this->filemanager_blocker;
    }
    
    /**
     * Get login blocker instance
     */
    public function get_login_blocker() {
        return $this->login_blocker;
    }
}

