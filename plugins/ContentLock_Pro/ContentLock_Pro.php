<?php
/*
Plugin Name: ContentLock Pro
Plugin URI: https://contentlockpro.com
Description: Lock premium content behind paywalls and build recurring revenue
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentLock_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTLOCK_VERSION', '1.0.0');
define('CONTENTLOCK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTLOCK_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentLockPro {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init();
    }

    private function init() {
        add_action('init', array($this, 'registerPostType'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_filter('the_content', array($this, 'lockContent'));
        add_action('wp_ajax_unlock_content', array($this, 'handleUnlock'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'handleUnlock'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentlock_locks';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(50) NOT NULL,
            price decimal(10, 2),
            tier varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('contentlock_pro_version', CONTENTLOCK_VERSION);
        add_option('contentlock_pro_settings', array(
            'currency' => 'USD',
            'payment_method' => 'stripe',
            'enable_subscriptions' => true
        ));
    }

    public function deactivate() {
        wp_clear_scheduled_hook('contentlock_pro_daily_check');
    }

    public function registerPostType() {
        register_post_type('contentlock_lock', array(
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor')
        ));
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentLock Pro',
            'ContentLock Pro',
            'manage_options',
            'contentlock-pro',
            array($this, 'renderDashboard'),
            'dashicons-lock',
            80
        );

        add_submenu_page(
            'contentlock-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentlock-pro',
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            'contentlock-pro',
            'Manage Locks',
            'Manage Locks',
            'manage_options',
            'contentlock-locks',
            array($this, 'renderManageLocks')
        );

        add_submenu_page(
            'contentlock-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentlock-settings',
            array($this, 'renderSettings')
        );
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'contentlock') === false) {
            return;
        }
        wp_enqueue_style('contentlock-admin', CONTENTLOCK_PLUGIN_URL . 'assets/admin.css');
        wp_enqueue_script('contentlock-admin', CONTENTLOCK_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTLOCK_VERSION);
        wp_localize_script('contentlock-admin', 'contentlockAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentlock_nonce')
        ));
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_style('contentlock-frontend', CONTENTLOCK_PLUGIN_URL . 'assets/frontend.css');
        wp_enqueue_script('contentlock-frontend', CONTENTLOCK_PLUGIN_URL . 'assets/frontend.js', array('jquery'), CONTENTLOCK_VERSION);
        wp_localize_script('contentlock-frontend', 'contentlock', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentlock_unlock')
        ));
    }

    public function lockContent($content) {
        if (is_admin() || !is_singular('post')) {
            return $content;
        }

        $post_id = get_the_ID();
        $lock = $this->getPostLock($post_id);

        if (!$lock) {
            return $content;
        }

        if ($this->isUserAuthorized($post_id, $lock)) {
            return $content;
        }

        $preview = wp_trim_words($content, 50);
        $html = '<div class="contentlock-locked">';
        $html .= '<div class="contentlock-preview">' . $preview . '...</div>';
        $html .= '<div class="contentlock-paywall">';
        $html .= '<h3>This content is locked</h3>';

        if ($lock['lock_type'] === 'paid') {
            $html .= '<p>Price: $' . number_format($lock['price'], 2) . '</p>';
            $html .= '<button class="contentlock-unlock-btn" data-post-id="' . $post_id . '" data-type="paid">Unlock Now</button>';
        } else {
            $html .= '<p>Please log in to view this content</p>';
            $html .= '<button class="contentlock-unlock-btn" data-post-id="' . $post_id . '" data-type="login">Log In</button>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private function getPostLock($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_locks';
        $lock = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d LIMIT 1", $post_id), ARRAY_A);
        return $lock;
    }

    private function isUserAuthorized($post_id, $lock) {
        if ($lock['lock_type'] === 'login' && is_user_logged_in()) {
            return true;
        }

        if ($lock['lock_type'] === 'paid') {
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $has_access = get_user_meta($user_id, 'contentlock_post_' . $post_id, true);
                return !empty($has_access);
            }
        }

        return false;
    }

    public function handleUnlock() {
        check_ajax_referer('contentlock_unlock', 'nonce');

        $post_id = intval($_POST['post_id']);
        $type = sanitize_text_field($_POST['type']);

        if ($type === 'login') {
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Please log in first'), 403);
            } else {
                wp_send_json_success(array('message' => 'Content unlocked'));
            }
        }

        if ($type === 'paid') {
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Please log in to purchase'), 403);
            }

            $lock = $this->getPostLock($post_id);
            if (!$lock) {
                wp_send_json_error(array('message' => 'Content not found'), 404);
            }

            $user_id = get_current_user_id();
            update_user_meta($user_id, 'contentlock_post_' . $post_id, time());
            do_action('contentlock_purchase', $user_id, $post_id, $lock['price']);

            wp_send_json_success(array('message' => 'Content unlocked', 'redirect' => get_permalink($post_id)));
        }
    }

    public function renderDashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_locks';
        $total_locks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_revenue = $wpdb->get_var("SELECT SUM(price) FROM $table_name WHERE lock_type = 'paid'");

        echo '<div class="wrap">';
        echo '<h1>ContentLock Pro Dashboard</h1>';
        echo '<div class="contentlock-stats">';
        echo '<div class="stat-box"><h3>Total Locked Posts</h3><p>' . $total_locks . '</p></div>';
        echo '<div class="stat-box"><h3>Revenue Potential</h3><p>$' . number_format($total_revenue ?: 0, 2) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function renderManageLocks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_locks';
        $locks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        echo '<div class="wrap">';
        echo '<h1>Manage Content Locks</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Post ID</th><th>Lock Type</th><th>Price</th><th>Created</th></tr></thead>';
        echo '<tbody>';

        foreach ($locks as $lock) {
            echo '<tr>';
            echo '<td>' . $lock->post_id . '</td>';
            echo '<td>' . $lock->lock_type . '</td>';
            echo '<td>' . ($lock->price ? '$' . number_format($lock->price, 2) : '-') . '</td>';
            echo '<td>' . $lock->created_at . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function renderSettings() {
        $settings = get_option('contentlock_pro_settings');

        echo '<div class="wrap">';
        echo '<h1>ContentLock Pro Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('contentlock_pro_settings_group');
        echo '<table class="form-table">';
        echo '<tr><th scope="row">Currency</th><td><input type="text" name="contentlock_currency" value="' . esc_attr($settings['currency']) . '" /></td></tr>';
        echo '<tr><th scope="row">Payment Method</th><td><select name="contentlock_payment"><option value="stripe">Stripe</option><option value="paypal">PayPal</option></select></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

ContentLockPro::getInstance();
?>