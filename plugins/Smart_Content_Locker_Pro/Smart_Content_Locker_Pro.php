<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Professional content gating and membership management for WordPress sites
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL v2 or later
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
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->initHooks();
        $this->loadDependencies();
    }

    private function initHooks() {
        add_action('plugins_loaded', array($this, 'initPlugin'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_filter('the_content', array($this, 'applyContentLocking'));
        add_shortcode('scl_locked_content', array($this, 'renderLockedContent'));
        add_action('init', array($this, 'registerPostMeta'));
        add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
        add_action('save_post', array($this, 'savePostMeta'));
    }

    private function loadDependencies() {
        require_once SCL_PLUGIN_DIR . 'includes/class-database.php';
        require_once SCL_PLUGIN_DIR . 'includes/class-subscription.php';
    }

    public function initPlugin() {
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->createTables();
    }

    public function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}scl_subscriptions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            membership_level VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_date DATETIME NULL,
            renewal_date DATETIME NULL,
            payment_method VARCHAR(50),
            price DECIMAL(10, 2),
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function addAdminMenu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'scl-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-lock',
            25
        );

        add_submenu_page(
            'scl-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'scl-settings',
            array($this, 'renderSettings')
        );

        add_submenu_page(
            'scl-dashboard',
            'Subscriptions',
            'Subscriptions',
            'manage_options',
            'scl-subscriptions',
            array($this, 'renderSubscriptions')
        );
    }

    public function renderDashboard() {
        global $wpdb;
        $total_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}scl_subscriptions WHERE status = 'active'");
        $total_revenue = $wpdb->get_var("SELECT SUM(price) FROM {$wpdb->prefix}scl_subscriptions WHERE status = 'active'");
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Dashboard</h1>
            <div class="scl-dashboard-grid">
                <div class="scl-card">
                    <h3>Active Subscribers</h3>
                    <p class="scl-stat"><?php echo esc_html($total_subscribers ?: 0); ?></p>
                </div>
                <div class="scl-card">
                    <h3>Monthly Revenue</h3>
                    <p class="scl-stat"><?php echo '$' . number_format(floatval($total_revenue) ?: 0, 2); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderSettings() {
        if (isset($_POST['scl_save_settings'])) {
            check_admin_referer('scl_settings_nonce');
            update_option('scl_stripe_key', sanitize_text_field($_POST['stripe_key'] ?? ''));
            update_option('scl_paypal_email', sanitize_email($_POST['paypal_email'] ?? ''));
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Content Locker Settings</h1>
            <form method="post">
                <?php wp_nonce_field('scl_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="stripe_key">Stripe API Key</label></th>
                        <td><input type="text" id="stripe_key" name="stripe_key" value="<?php echo esc_attr(get_option('scl_stripe_key')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="paypal_email">PayPal Email</label></th>
                        <td><input type="email" id="paypal_email" name="paypal_email" value="<?php echo esc_attr(get_option('scl_paypal_email')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'scl_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    public function renderSubscriptions() {
        global $wpdb;
        $subscriptions = $wpdb->get_results("SELECT s.*, u.user_login FROM {$wpdb->prefix}scl_subscriptions s LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID ORDER BY s.start_date DESC");
        ?>
        <div class="wrap">
            <h1>Manage Subscriptions</h1>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Renewal Date</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><?php echo esc_html($sub->user_login); ?></td>
                            <td><?php echo esc_html($sub->membership_level); ?></td>
                            <td><span class="scl-status scl-status-<?php echo esc_attr($sub->status); ?>"><?php echo esc_html(ucfirst($sub->status)); ?></span></td>
                            <td><?php echo esc_html(date_format(date_create($sub->start_date), 'M d, Y')); ?></td>
                            <td><?php echo $sub->renewal_date ? esc_html(date_format(date_create($sub->renewal_date), 'M d, Y')) : 'N/A'; ?></td>
                            <td><?php echo '$' . esc_html(number_format($sub->price, 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function addMetaBoxes() {
        add_meta_box(
            'scl_content_settings',
            'Content Locker Settings',
            array($this, 'renderMetaBox'),
            'post',
            'normal',
            'high'
        );
    }

    public function renderMetaBox($post) {
        $is_locked = get_post_meta($post->ID, '_scl_is_locked', true);
        $required_level = get_post_meta($post->ID, '_scl_required_level', true);
        $preview_text = get_post_meta($post->ID, '_scl_preview_text', true);
        wp_nonce_field('scl_meta_nonce', 'scl_nonce');
        ?>
        <div class="scl-metabox">
            <p>
                <label><input type="checkbox" name="scl_is_locked" value="1" <?php checked($is_locked, 1); ?>> Lock this content</label>
            </p>
            <p>
                <label for="scl_required_level">Required Membership Level:</label>
                <select id="scl_required_level" name="scl_required_level">
                    <option value="basic" <?php selected($required_level, 'basic'); ?>>Basic</option>
                    <option value="premium" <?php selected($required_level, 'premium'); ?>>Premium</option>
                    <option value="elite" <?php selected($required_level, 'elite'); ?>>Elite</option>
                </select>
            </p>
            <p>
                <label for="scl_preview_text">Preview Text (shown before lock):</label>
                <textarea id="scl_preview_text" name="scl_preview_text" rows="3"><?php echo esc_textarea($preview_text); ?></textarea>
            </p>
        </div>
        <?php
    }

    public function savePostMeta($post_id) {
        if (!isset($_POST['scl_nonce']) || !wp_verify_nonce($_POST['scl_nonce'], 'scl_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        update_post_meta($post_id, '_scl_is_locked', isset($_POST['scl_is_locked']) ? 1 : 0);
        update_post_meta($post_id, '_scl_required_level', sanitize_text_field($_POST['scl_required_level'] ?? 'basic'));
        update_post_meta($post_id, '_scl_preview_text', wp_kses_post($_POST['scl_preview_text'] ?? ''));
    }

    public function applyContentLocking($content) {
        if (is_single() && is_user_logged_in()) {
            $is_locked = get_post_meta(get_the_ID(), '_scl_is_locked', true);
            if ($is_locked) {
                $preview = get_post_meta(get_the_ID(), '_scl_preview_text', true);
                $required_level = get_post_meta(get_the_ID(), '_scl_required_level', true);
                $user_level = get_user_meta(get_current_user_id(), 'scl_membership_level', true);
                $levels = array('basic' => 1, 'premium' => 2, 'elite' => 3);
                if ($levels[$user_level] < $levels[$required_level]) {
                    return $preview . '<div class="scl-locked-notice"><p>This content requires a ' . esc_html($required_level) . ' membership. <a href="#upgrade">Upgrade now</a></p></div>';
                }
            }
        }
        return $content;
    }

    public function renderLockedContent($atts) {
        $atts = shortcode_atts(array('level' => 'premium'), $atts);
        if (!is_user_logged_in()) {
            return '<p>Please log in to view this content.</p>';
        }
        $user_level = get_user_meta(get_current_user_id(), 'scl_membership_level', true);
        $levels = array('basic' => 1, 'premium' => 2, 'elite' => 3);
        if (!$user_level || $levels[$user_level] < $levels[$atts['level']]) {
            return '<p>You do not have access to this content.</p>';
        }
        return '';
    }

    public function enqueueAdminScripts() {
        wp_enqueue_style('scl-admin-css', SCL_PLUGIN_URL . 'css/admin.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-admin-js', SCL_PLUGIN_URL . 'js/admin.js', array('jquery'), SCL_VERSION, true);
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_style('scl-frontend-css', SCL_PLUGIN_URL . 'css/frontend.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-frontend-js', SCL_PLUGIN_URL . 'js/frontend.js', array('jquery'), SCL_VERSION, true);
    }
}

SmartContentLocker::getInstance();

register_activation_hook(__FILE__, function() {
    SmartContentLocker::getInstance()->createTables();
});

register_deactivation_hook(__FILE__, function() {
    // Cleanup code if needed
});
?>