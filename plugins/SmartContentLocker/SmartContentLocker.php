<?php
/*
Plugin Name: SmartContentLocker
Plugin URI: https://smartcontentlocker.com
Description: Lock content behind email signup, social share, or payment actions
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartContentLocker.php
License: GPL v2 or later
Text Domain: smartcontentlocker
*/

if (!defined('ABSPATH')) exit;

define('SMARTCONTENTLOCKER_VERSION', '1.0.0');
define('SMARTCONTENTLOCKER_DIR', plugin_dir_path(__FILE__));
define('SMARTCONTENTLOCKER_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->register_hooks();
        $this->register_shortcodes();
        $this->load_admin();
    }
    
    public function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_unlock_content', array($this, 'handle_unlock'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'handle_unlock'));
    }
    
    public function register_shortcodes() {
        add_shortcode('content_locker', array($this, 'render_locker'));
    }
    
    public function load_admin() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'SmartContentLocker',
            'SmartContentLocker',
            'manage_options',
            'smartcontentlocker',
            array($this, 'render_settings_page'),
            'dashicons-lock',
            90
        );
    }
    
    public function register_settings() {
        register_setting('smartcontentlocker', 'scl_settings');
    }
    
    public function render_settings_page() {
        echo '<div class="wrap"><h1>SmartContentLocker Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('smartcontentlocker');
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label>Email Service (Freemium: Mailchimp only)</label></th>';
        echo '<td><input type="text" name="scl_settings[mailchimp_key]" value="' . esc_attr(get_option('scl_settings')['mailchimp_key'] ?? '') . '" placeholder="Enter API Key" /></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form></div>';
    }
    
    public function render_locker($atts, $content = '') {
        $atts = shortcode_atts(array(
            'type' => 'email',
            'id' => uniqid('locker_'),
            'message' => 'Unlock this content by subscribing',
        ), $atts);
        
        $locked_content = do_shortcode($content);
        $locker_id = esc_attr($atts['id']);
        $locker_type = esc_attr($atts['type']);
        $message = esc_html($atts['message']);
        
        ob_start();
        ?>
        <div class="scl-locker" data-locker-id="<?php echo $locker_id; ?>" data-type="<?php echo $locker_type; ?>">
            <div class="scl-locked-content" id="locked-<?php echo $locker_id; ?>"
                 style="position:relative;filter:blur(5px);pointer-events:none;user-select:none;">
                <?php echo $locked_content; ?>
            </div>
            <div class="scl-unlock-overlay" style="text-align:center;padding:20px;background:#f5f5f5;border:1px solid #ddd;margin:10px 0;">
                <p><?php echo $message; ?></p>
                <?php if ($locker_type === 'email'): ?>
                    <input type="email" class="scl-email-input" placeholder="Enter your email" data-locker-id="<?php echo $locker_id; ?>" />
                    <button class="scl-unlock-btn" data-locker-id="<?php echo $locker_id; ?>" style="padding:10px 20px;background:#0073aa;color:#fff;border:none;cursor:pointer;border-radius:3px;margin-top:10px;">Unlock Content</button>
                <?php elseif ($locker_type === 'share'): ?>
                    <button class="scl-share-btn" data-locker-id="<?php echo $locker_id; ?>" style="padding:10px 20px;background:#0073aa;color:#fff;border:none;cursor:pointer;border-radius:3px;margin:5px;">Share on Facebook</button>
                    <button class="scl-share-btn" data-network="twitter" data-locker-id="<?php echo $locker_id; ?>" style="padding:10px 20px;background:#1DA1F2;color:#fff;border:none;cursor:pointer;border-radius:3px;margin:5px;">Share on Twitter</button>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .scl-locker { position: relative; }
            .scl-unlock-btn, .scl-share-btn { transition: all 0.3s ease; }
            .scl-unlock-btn:hover, .scl-share-btn:hover { opacity: 0.8; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    public function handle_unlock() {
        check_ajax_referer('scl_nonce', 'nonce');
        
        $locker_id = sanitize_text_field($_POST['locker_id'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        
        if ($action === 'email' && !empty($email)) {
            $this->save_subscriber($email);
            wp_send_json_success(array('message' => 'Content unlocked!'));
        } elseif ($action === 'share') {
            wp_send_json_success(array('message' => 'Content unlocked!'));
        }
        
        wp_send_json_error(array('message' => 'Invalid action'));
    }
    
    private function save_subscriber($email) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'scl_subscribers', array(
            'email' => $email,
            'date_added' => current_time('mysql'),
        ));
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_script('scl-frontend', SMARTCONTENTLOCKER_URL . 'assets/frontend.js', array('jquery'), SMARTCONTENTLOCKER_VERSION, true);
        wp_localize_script('scl-frontend', 'scl_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce'),
        ));
        wp_enqueue_style('scl-frontend', SMARTCONTENTLOCKER_URL . 'assets/frontend.css', array(), SMARTCONTENTLOCKER_VERSION);
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style('scl-admin', SMARTCONTENTLOCKER_URL . 'assets/admin.css', array(), SMARTCONTENTLOCKER_VERSION);
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'scl_subscribers';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
}

SmartContentLocker::get_instance();

if (!function_exists('scl_plugin_action_links')) {
    function scl_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=smartcontentlocker') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scl_plugin_action_links');
}
?>