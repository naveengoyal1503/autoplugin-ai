<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Advanced content gating and monetization for WordPress with memberships, paywalls, and analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL2
Text Domain: smart-content-locker
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCL_VERSION', '1.0.0');

class SmartContentLocker {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_filter('the_content', array($this, 'applyContentLock'));
        add_shortcode('scl-locker', array($this, 'renderLocker'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->createTables();
    }

    public function activate() {
        $this->createTables();
        add_option('scl_version', SCL_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    private function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $lockers_table = $wpdb->prefix . 'scl_lockers';
        $analytics_table = $wpdb->prefix . 'scl_analytics';
        
        $sql_lockers = "CREATE TABLE IF NOT EXISTS $lockers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(50) NOT NULL,
            unlock_method varchar(50) NOT NULL,
            lock_message longtext,
            required_role varchar(100),
            unlock_button_text varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";
        
        $sql_analytics = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            locker_id bigint(20) NOT NULL,
            user_id bigint(20),
            action varchar(50) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY locker_id (locker_id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_lockers);
        dbDelta($sql_analytics);
    }

    public function addAdminMenu() {
        add_menu_page(
            __('Smart Content Locker', 'smart-content-locker'),
            __('Content Locker', 'smart-content-locker'),
            'manage_options',
            'scl-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-lock',
            30
        );
        
        add_submenu_page(
            'scl-dashboard',
            __('Settings', 'smart-content-locker'),
            __('Settings', 'smart-content-locker'),
            'manage_options',
            'scl-settings',
            array($this, 'renderSettings')
        );
        
        add_submenu_page(
            'scl-dashboard',
            __('Analytics', 'smart-content-locker'),
            __('Analytics', 'smart-content-locker'),
            'manage_options',
            'scl-analytics',
            array($this, 'renderAnalytics')
        );
    }

    public function renderDashboard() {
        global $wpdb;
        $lockers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}scl_lockers");
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Content Locker Dashboard', 'smart-content-locker'); ?></h1>
            <div class="scl-dashboard">
                <p><?php printf(__('You have %d active content lockers.', 'smart-content-locker'), count($lockers)); ?></p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Post', 'smart-content-locker'); ?></th>
                            <th><?php _e('Lock Type', 'smart-content-locker'); ?></th>
                            <th><?php _e('Method', 'smart-content-locker'); ?></th>
                            <th><?php _e('Actions', 'smart-content-locker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lockers as $locker): ?>
                            <tr>
                                <td><?php echo get_the_title($locker->post_id); ?></td>
                                <td><?php echo esc_html($locker->lock_type); ?></td>
                                <td><?php echo esc_html($locker->unlock_method); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($locker->post_id)); ?>"><?php _e('Edit', 'smart-content-locker'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function renderSettings() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Content Locker Settings', 'smart-content-locker'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('scl-settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="scl_default_message"><?php _e('Default Lock Message', 'smart-content-locker'); ?></label></th>
                        <td>
                            <textarea id="scl_default_message" name="scl_default_message" rows="4" cols="50"><?php echo esc_textarea(get_option('scl_default_message', 'This content is locked. Please unlock to view.')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="scl_button_color"><?php _e('Button Color', 'smart-content-locker'); ?></label></th>
                        <td>
                            <input type="color" id="scl_button_color" name="scl_button_color" value="<?php echo esc_attr(get_option('scl_button_color', '#0073aa')); ?>">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function renderAnalytics() {
        global $wpdb;
        $analytics = $wpdb->get_results(
            "SELECT l.id, p.post_title, COUNT(a.id) as unlock_attempts 
             FROM {$wpdb->prefix}scl_lockers l 
             LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID 
             LEFT JOIN {$wpdb->prefix}scl_analytics a ON l.id = a.locker_id 
             GROUP BY l.id"
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Content Locker Analytics', 'smart-content-locker'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Post', 'smart-content-locker'); ?></th>
                        <th><?php _e('Unlock Attempts', 'smart-content-locker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics as $stat): ?>
                        <tr>
                            <td><?php echo esc_html($stat->post_title); ?></td>
                            <td><?php echo intval($stat->unlock_attempts); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function applyContentLock($content) {
        if (is_singular() && !is_admin()) {
            global $post, $wpdb;
            $locker = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}scl_lockers WHERE post_id = %d",
                    $post->ID
                )
            );
            
            if ($locker) {
                $user_id = get_current_user_id();
                $can_unlock = false;
                
                if ($locker->required_role && !empty($locker->required_role)) {
                    $user = get_userdata($user_id);
                    $can_unlock = $user && in_array($locker->required_role, $user->roles);
                }
                
                if (!$can_unlock && !is_user_logged_in()) {
                    $button_text = $locker->unlock_button_text ?: __('Unlock Content', 'smart-content-locker');
                    $message = $locker->lock_message ?: get_option('scl_default_message', 'This content is locked.');
                    
                    $this->logAnalytics($locker->id, 'view_attempt');
                    
                    $locked_html = '<div class="scl-locker-wrapper">';
                    $locked_html .= '<div class="scl-locker-message">' . wp_kses_post($message) . '</div>';
                    $locked_html .= '<button class="scl-unlock-button" onclick="scl_unlock_content(event)">' . esc_html($button_text) . '</button>';
                    $locked_html .= '</div>';
                    $locked_html .= '<div class="scl-locked-content" style="display:none;">' . $content . '</div>';
                    
                    return $locked_html;
                }
            }
        }
        return $content;
    }

    public function renderLocker($atts) {
        $atts = shortcode_atts(array(
            'message' => __('This content is locked.', 'smart-content-locker'),
            'button_text' => __('Unlock', 'smart-content-locker'),
        ), $atts);
        
        $button_color = get_option('scl_button_color', '#0073aa');
        
        ob_start();
        ?>
        <div class="scl-locker-wrapper" style="text-align:center; padding:20px; background:#f5f5f5; border-radius:5px;">
            <p><?php echo esc_html($atts['message']); ?></p>
            <button class="scl-unlock-button" onclick="scl_unlock_content(event)" style="background-color:<?php echo esc_attr($button_color); ?>;">
                <?php echo esc_html($atts['button_text']); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function logAnalytics($locker_id, $action) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'scl_analytics',
            array(
                'locker_id' => $locker_id,
                'user_id' => get_current_user_id(),
                'action' => $action,
            ),
            array('%d', '%d', '%s')
        );
    }

    public function enqueueScripts() {
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SCL_VERSION, true);
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/css/frontend.css', array(), SCL_VERSION);
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'scl-') === false) return;
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/css/admin.css', array(), SCL_VERSION);
    }
}

SmartContentLocker::getInstance();
?>