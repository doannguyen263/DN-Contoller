<?php
/**
 * Admin Class
 * Handles admin area functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DN_Controller_Admin {
    
    /**
     * Initialize admin
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_form_submit'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'DN Contoller',
            'DN Contoller',
            'manage_options',
            'dn-controller',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submit() {
        if (!isset($_POST['disable_wp_updates_submit']) || !current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('disable_wp_updates_nonce');
        
        $enabled = isset($_POST['disable_wp_updates_enabled']) ? '1' : '0';
        $editor_disabled = isset($_POST['disable_wp_editor_enabled']) ? '1' : '0';
        $install_disabled = isset($_POST['disable_wp_install_enabled']) ? '1' : '0';
        $filemanager_blocked = isset($_POST['disable_filemanager_plugins_enabled']) ? '1' : '0';
        $login_blocked = isset($_POST['disable_login_blocker_enabled']) ? '1' : '0';
        $blocked_usernames = isset($_POST['blocked_usernames_list']) ? sanitize_textarea_field($_POST['blocked_usernames_list']) : '';
        
        update_option('disable_wp_updates_enabled', $enabled);
        update_option('disable_wp_editor_enabled', $editor_disabled);
        update_option('disable_wp_install_enabled', $install_disabled);
        update_option('disable_filemanager_plugins_enabled', $filemanager_blocked);
        update_option('disable_login_blocker_enabled', $login_blocked);
        update_option('blocked_usernames_list', $blocked_usernames);
        
        // Clear transients to apply changes immediately
        delete_site_transient('update_core');
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');
        
        $messages = array();
        $messages[] = $enabled === '1' ? 'Đã bật chặn cập nhật WordPress.' : 'Đã tắt chặn cập nhật WordPress.';
        $messages[] = $editor_disabled === '1' ? 'Đã bật chặn sửa giao diện và plugin.' : 'Đã tắt chặn sửa giao diện và plugin.';
        $messages[] = $install_disabled === '1' ? 'Đã bật chặn cài plugin.' : 'Đã tắt chặn cài plugin.';
        $messages[] = $filemanager_blocked === '1' ? 'Đã bật chặn plugin File Manager.' : 'Đã tắt chặn plugin File Manager.';
        $messages[] = $login_blocked === '1' ? 'Đã bật chặn đăng nhập/đăng ký username.' : 'Đã tắt chặn đăng nhập/đăng ký username.';
        
        add_settings_error(
            'disable_wp_updates',
            'settings_updated',
            implode(' ', $messages),
            'success'
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $controller = DN_Controller::get_instance();
        $enabled = $controller->get_updates_blocker()->is_enabled();
        $editor_disabled = $controller->get_editor_blocker()->is_enabled();
        $install_disabled = $controller->get_install_blocker()->is_enabled();
        $filemanager_blocked = $controller->get_filemanager_blocker()->is_enabled();
        $login_blocked = $controller->get_login_blocker()->is_enabled();
        $blocked_usernames_list = get_option('blocked_usernames_list', '');
        $default_blocked = $controller->get_login_blocker()->get_default_blocked_usernames_list();
        ?>
        <div class="wrap">
            <h1>DN Contoller Settings</h1>
            
            <?php settings_errors('disable_wp_updates'); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('disable_wp_updates_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Chặn cập nhật</th>
                        <td>
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="disable_wp_updates_enabled" 
                                    value="1" 
                                    <?php checked($enabled, true); ?>
                                />
                                Bật chặn cập nhật WordPress
                            </label>
                            <p class="description">
                                Khi bật, plugin sẽ chặn tất cả các cập nhật: Core, Plugin, Theme, Translation và auto-updates.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chặn sửa giao diện & Plugin</th>
                        <td>
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="disable_wp_editor_enabled" 
                                    value="1" 
                                    <?php checked($editor_disabled, true); ?>
                                />
                                Bật chặn Theme Editor và Plugin Editor
                            </label>
                            <p class="description">
                                Khi bật, sẽ ẩn và chặn truy cập vào Theme Editor và Plugin Editor trong admin.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chặn cài plugin</th>
                        <td>
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="disable_wp_install_enabled" 
                                    value="1" 
                                    <?php checked($install_disabled, true); ?>
                                />
                                Bật chặn cài đặt plugin mới
                            </label>
                            <p class="description">
                                Khi bật, sẽ ẩn menu "Add New" và chặn tất cả các cách cài đặt plugin mới.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chặn plugin File Manager</th>
                        <td>
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="disable_filemanager_plugins_enabled" 
                                    value="1" 
                                    <?php checked($filemanager_blocked, true); ?>
                                />
                                Bật chặn cài đặt plugin File Manager
                            </label>
                            <p class="description">
                                Khi bật, sẽ chặn tất cả các plugin liên quan đến File Manager (file-manager, wp-file-manager, etc.) vì lý do bảo mật.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chặn đăng nhập/đăng ký</th>
                        <td>
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="disable_login_blocker_enabled" 
                                    value="1" 
                                    <?php checked($login_blocked, true); ?>
                                />
                                Bật chặn đăng nhập và đăng ký cho username trong danh sách
                            </label>
                            <p class="description">
                                Khi bật, sẽ chặn đăng nhập và đăng ký cho các username trong danh sách bị chặn.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="blocked_usernames_list">Danh sách username bị chặn (tùy chỉnh)</label>
                        </th>
                        <td>
                            <textarea 
                                id="blocked_usernames_list"
                                name="blocked_usernames_list" 
                                rows="5" 
                                cols="50" 
                                class="large-text code"
                                placeholder="Mỗi username một dòng, ví dụ:&#10;hacker&#10;attacker&#10;malicious"
                            ><?php echo esc_textarea($blocked_usernames_list); ?></textarea>
                            <p class="description">
                                Nhập thêm các username muốn chặn (mỗi username một dòng). Danh sách này sẽ được kết hợp với danh sách mặc định.
                            </p>
                            <p class="description">
                                <strong>Danh sách mặc định:</strong> <?php echo esc_html(implode(', ', array_slice($default_blocked, 0, 7))); ?><?php echo count($default_blocked) > 7 ? '...' : ''; ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Lưu thay đổi', 'primary', 'disable_wp_updates_submit'); ?>
            </form>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Thông tin</h2>
                <p><strong>Chặn cập nhật:</strong> <?php echo $enabled ? '<span style="color: green;">Đang bật</span>' : '<span style="color: red;">Đang tắt</span>'; ?></p>
                <p><strong>Chặn sửa giao diện & Plugin:</strong> <?php echo $editor_disabled ? '<span style="color: green;">Đang bật</span>' : '<span style="color: red;">Đang tắt</span>'; ?></p>
                <p><strong>Chặn cài plugin:</strong> <?php echo $install_disabled ? '<span style="color: green;">Đang bật</span>' : '<span style="color: red;">Đang tắt</span>'; ?></p>
                <p><strong>Chặn plugin File Manager:</strong> <?php echo $filemanager_blocked ? '<span style="color: green;">Đang bật</span>' : '<span style="color: red;">Đang tắt</span>'; ?></p>
                <p><strong>Chặn đăng nhập/đăng ký:</strong> <?php echo $login_blocked ? '<span style="color: green;">Đang bật</span>' : '<span style="color: red;">Đang tắt</span>'; ?></p>
                
                <h3>Chặn cập nhật sẽ bao gồm:</h3>
                <ul>
                    <li>Cập nhật WordPress Core</li>
                    <li>Cập nhật Plugin</li>
                    <li>Cập nhật Theme</li>
                    <li>Cập nhật Translation</li>
                    <li>Auto-updates</li>
                    <li>Thông báo cập nhật trong admin</li>
                    <li>Kiểm tra cập nhật qua WP-Cron</li>
                </ul>
                
                <h3>Chặn sửa giao diện & Plugin sẽ bao gồm:</h3>
                <ul>
                    <li>Ẩn Theme Editor từ menu Appearance</li>
                    <li>Ẩn Plugin Editor từ menu Plugins</li>
                    <li>Chặn truy cập trực tiếp vào editor pages</li>
                    <li>Đặt DISALLOW_FILE_EDIT constant</li>
                </ul>
                
                <h3>Chặn cài plugin sẽ bao gồm:</h3>
                <ul>
                    <li>Ẩn menu "Add New" từ menu Plugins</li>
                    <li>Ẩn nút "Add New" trên trang Plugins</li>
                    <li>Chặn truy cập vào plugin-install.php</li>
                    <li>Chặn quyền install_plugins và upload_plugins</li>
                    <li>Chặn cài plugin qua REST API</li>
                    <li>Chặn các action install/upload plugin</li>
                </ul>
                
                <h3>Chặn plugin File Manager sẽ bao gồm:</h3>
                <ul>
                    <li>Chặn cài đặt plugin File Manager qua repository</li>
                    <li>Chặn upload plugin File Manager (ZIP)</li>
                    <li>Ẩn plugin File Manager khỏi kết quả tìm kiếm</li>
                    <li>Chặn activate plugin File Manager đã cài đặt</li>
                    <li>Áp dụng cho: file-manager, wp-file-manager, file-manager-advanced, responsive-file-manager, và các biến thể khác</li>
                </ul>
                
                <h3>Chặn đăng nhập/đăng ký sẽ bao gồm:</h3>
                <ul>
                    <li>Chặn đăng nhập cho username trong danh sách bị chặn</li>
                    <li>Chặn đăng ký tài khoản mới với username trong danh sách</li>
                    <li>Chặn tạo user qua admin panel với username bị chặn</li>
                    <li>Chặn đăng ký qua REST API</li>
                    <li>Danh sách mặc định: admin, administrator, root, test, demo, user, guest</li>
                    <li>Có thể thêm username tùy chỉnh trong phần cấu hình</li>
                </ul>
                
                <p><strong>Lưu ý:</strong> Khi tắt các tính năng, WordPress sẽ hoạt động bình thường trở lại.</p>
                <p><strong>Cảnh báo:</strong> Plugin File Manager thường có lỗ hổng bảo mật nghiêm trọng, nên chặn chúng là biện pháp bảo mật quan trọng.</p>
            </div>
        </div>
        <?php
    }
}

