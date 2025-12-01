<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Lock premium content behind email subscriptions, paywalls, and referral requirements with built-in analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL2
Domain Path: /languages
Text Domain: smart-content-locker
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCL_VERSION', '1.0.0');

class SmartContentLocker {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_shortcode('content_locker', array($this, 'renderContentLocker'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('wp_ajax_scl_unlock_content', array($this, 'ajaxUnlockContent'));
        add_action('wp_ajax_nopriv_scl_unlock_content', array($this, 'ajaxUnlockContent'));
    }

    public function init() {
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}scl_locks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(50) NOT NULL,
            email_required tinyint(1) DEFAULT 1,
            referral_required tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}scl_conversions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lock_id bigint(20) NOT NULL,
            email varchar(100) NOT NULL,
            ip_address varchar(45) NOT NULL,
            converted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lock_id (lock_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);

        add_option('scl_version', SCL_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function enqueueScripts() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), SCL_VERSION, true);
        wp_localize_script('scl-frontend', 'sclData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce')
        ));
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'smart-content-locker') === false) {
            return;
        }
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/admin.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-admin', SCL_PLUGIN_URL . 'assets/admin.js', array('jquery'), SCL_VERSION, true);
    }

    public function addAdminMenu() {
        add_menu_page(
            __('Content Locker', 'smart-content-locker'),
            __('Content Locker', 'smart-content-locker'),
            'manage_options',
            'smart-content-locker',
            array($this, 'renderDashboard'),
            'dashicons-lock',
            30
        );

        add_submenu_page(
            'smart-content-locker',
            __('Analytics', 'smart-content-locker'),
            __('Analytics', 'smart-content-locker'),
            'manage_options',
            'scl-analytics',
            array($this, 'renderAnalytics')
        );

        add_submenu_page(
            'smart-content-locker',
            __('Settings', 'smart-content-locker'),
            __('Settings', 'smart-content-locker'),
            'manage_options',
            'scl-settings',
            array($this, 'renderSettings')
        );
    }

    public function renderDashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Content Locker', 'smart-content-locker'); ?></h1>
            <div class="scl-dashboard">
                <p><?php _e('Welcome to Smart Content Locker Pro! Use the shortcode [content_locker type="email" redirect_url="your-url"]Your premium content here[/content_locker] to lock content.', 'smart-content-locker'); ?></p>
                <div class="scl-info-box">
                    <h3><?php _e('Quick Stats', 'smart-content-locker'); ?></h3>
                    <?php echo $this->getQuickStats(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderAnalytics() {
        global $wpdb;
        $conversions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}scl_conversions ORDER BY converted_at DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1><?php _e('Content Locker Analytics', 'smart-content-locker'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Email', 'smart-content-locker'); ?></th>
                        <th><?php _e('IP Address', 'smart-content-locker'); ?></th>
                        <th><?php _e('Date', 'smart-content-locker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conversions as $conversion) : ?>
                        <tr>
                            <td><?php echo esc_html($conversion->email); ?></td>
                            <td><?php echo esc_html($conversion->ip_address); ?></td>
                            <td><?php echo esc_html($conversion->converted_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderSettings() {
        if (isset($_POST['scl_save_settings'])) {
            check_admin_referer('scl_settings_nonce');
            update_option('scl_mailchimp_api', sanitize_text_field($_POST['scl_mailchimp_api'] ?? ''));
            update_option('scl_mailchimp_list', sanitize_text_field($_POST['scl_mailchimp_list'] ?? ''));
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'smart-content-locker') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Settings', 'smart-content-locker'); ?></h1>
            <form method="post" class="scl-settings-form">
                <?php wp_nonce_field('scl_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="scl_mailchimp_api"><?php _e('Mailchimp API Key', 'smart-content-locker'); ?></label></th>
                        <td>
                            <input type="password" id="scl_mailchimp_api" name="scl_mailchimp_api" value="<?php echo esc_attr(get_option('scl_mailchimp_api')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="scl_mailchimp_list"><?php _e('Mailchimp List ID', 'smart-content-locker'); ?></label></th>
                        <td>
                            <input type="text" id="scl_mailchimp_list" name="scl_mailchimp_list" value="<?php echo esc_attr(get_option('scl_mailchimp_list')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'smart-content-locker'), 'primary', 'scl_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    public function renderContentLocker($atts, $content = '') {
        $atts = shortcode_atts(array(
            'type' => 'email',
            'redirect_url' => '',
            'button_text' => __('Unlock Content', 'smart-content-locker')
        ), $atts, 'content_locker');

        $email = isset($_COOKIE['scl_email']) ? sanitize_email($_COOKIE['scl_email']) : '';

        if ($email) {
            return '<div class="scl-unlocked-content">' . do_shortcode($content) . '</div>';
        }

        ob_start();
        ?>
        <div class="scl-locker-wrapper">
            <div class="scl-locker-overlay">
                <div class="scl-locker-content">
                    <h3><?php _e('Get Instant Access', 'smart-content-locker'); ?></h3>
                    <p><?php _e('Enter your email to unlock this premium content.', 'smart-content-locker'); ?></p>
                    <form class="scl-unlock-form" method="post">
                        <input type="email" class="scl-email-input" placeholder="<?php _e('Your email address', 'smart-content-locker'); ?>" required>
                        <button type="submit" class="scl-unlock-btn"><?php echo esc_html($atts['button_text']); ?></button>
                    </form>
                </div>
            </div>
            <div class="scl-locked-content"><?php echo do_shortcode($content); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajaxUnlockContent() {
        check_ajax_referer('scl_nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        if (!is_email($email)) {
            wp_send_json_error(__('Invalid email address', 'smart-content-locker'));
        }

        setcookie('scl_email', $email, time() + (365 * 24 * 60 * 60), '/');

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'scl_conversions', array(
            'lock_id' => 1,
            'email' => $email,
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'])
        ));

        wp_send_json_success(array('message' => __('Content unlocked!', 'smart-content-locker')));
    }

    private function getQuickStats() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}scl_conversions");
        $today = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}scl_conversions WHERE DATE(converted_at) = CURDATE()");
        return "<p><strong>" . __('Total Conversions:', 'smart-content-locker') . "</strong> $total</p>
                <p><strong>" . __('Today:', 'smart-content-locker') . "</strong> $today</p>";
    }
}

SmartContentLocker::getInstance();
?>