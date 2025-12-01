<?php
/*
Plugin Name: ContentPulse Pro
Plugin URI: https://contentpulsepro.com
Description: AI-powered content performance analytics and monetization optimizer for WordPress blogs
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentPulse_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTPULSE_VERSION', '1.0.0');
define('CONTENTPULSE_DIR', plugin_dir_path(__FILE__));
define('CONTENTPULSE_URL', plugin_dir_url(__FILE__));

class ContentPulsePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_shortcode('contentpulse_dashboard', array($this, 'render_dashboard'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function init_plugin() {
        $this->create_tables();
        $this->register_post_meta();
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentpulse_analytics';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            views int(11) NOT NULL DEFAULT 0,
            clicks int(11) NOT NULL DEFAULT 0,
            avg_time_on_page int(11) NOT NULL DEFAULT 0,
            affiliate_clicks int(11) NOT NULL DEFAULT 0,
            estimated_revenue decimal(10,2) NOT NULL DEFAULT 0,
            monetization_score int(3) NOT NULL DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function register_post_meta() {
        register_post_meta('post', 'contentpulse_affiliate_links', array(
            'type' => 'array',
            'single' => true,
            'sanitize_callback' => array($this, 'sanitize_affiliate_links')
        ));

        register_post_meta('post', 'contentpulse_monetization_strategy', array(
            'type' => 'string',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }

    public function sanitize_affiliate_links($input) {
        if (!is_array($input)) {
            return array();
        }
        $sanitized = array();
        foreach ($input as $link) {
            $sanitized[] = array(
                'url' => esc_url($link['url'] ?? ''),
                'anchor_text' => sanitize_text_field($link['anchor_text'] ?? ''),
                'network' => sanitize_text_field($link['network'] ?? '')
            );
        }
        return $sanitized;
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentPulse Pro',
            'ContentPulse Pro',
            'manage_options',
            'contentpulse-dashboard',
            array($this, 'render_admin_dashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'contentpulse-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentpulse-dashboard',
            array($this, 'render_admin_dashboard')
        );

        add_submenu_page(
            'contentpulse-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentpulse-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'contentpulse-dashboard',
            'Upgrade',
            'Upgrade to Pro',
            'manage_options',
            'contentpulse-upgrade',
            array($this, 'render_upgrade_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentpulse') === false) {
            return;
        }

        wp_enqueue_style('contentpulse-admin', CONTENTPULSE_URL . 'assets/css/admin.css', array(), CONTENTPULSE_VERSION);
        wp_enqueue_script('contentpulse-admin', CONTENTPULSE_URL . 'assets/js/admin.js', array('jquery'), CONTENTPULSE_VERSION, true);

        wp_localize_script('contentpulse-admin', 'contentpulseData', array(
            'nonce' => wp_create_nonce('contentpulse_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'isPro' => get_option('contentpulse_is_pro', false)
        ));
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_script('contentpulse-tracking', CONTENTPULSE_URL . 'assets/js/tracking.js', array(), CONTENTPULSE_VERSION, true);
        wp_localize_script('contentpulse-tracking', 'contentpulseTracking', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'postId' => get_the_ID()
        ));
    }

    public function render_admin_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contentpulse_analytics';
        $analytics = $wpdb->get_results("SELECT * FROM $table_name ORDER BY views DESC LIMIT 10");
        $total_estimated_revenue = $wpdb->get_var("SELECT SUM(estimated_revenue) FROM $table_name");
        $avg_monetization_score = $wpdb->get_var("SELECT AVG(monetization_score) FROM $table_name");

        ?>
        <div class="wrap">
            <h1>ContentPulse Pro Dashboard</h1>
            <div class="contentpulse-dashboard">
                <div class="contentpulse-stats">
                    <div class="stat-card">
                        <h3>Total Estimated Revenue</h3>
                        <p class="stat-value">$<?php echo number_format(floatval($total_estimated_revenue), 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Avg Monetization Score</h3>
                        <p class="stat-value"><?php echo round(floatval($avg_monetization_score)); ?>/100</p>
                    </div>
                    <div class="stat-card">
                        <h3>Top Performing Posts</h3>
                        <p class="stat-value"><?php echo count($analytics); ?></p>
                    </div>
                </div>

                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <th>Views</th>
                            <th>Clicks</th>
                            <th>Est. Revenue</th>
                            <th>Monetization Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics as $row): ?>
                            <tr>
                                <td><?php echo get_the_title($row->post_id); ?></td>
                                <td><?php echo intval($row->views); ?></td>
                                <td><?php echo intval($row->clicks); ?></td>
                                <td>$<?php echo number_format(floatval($row->estimated_revenue), 2); ?></td>
                                <td><?php echo intval($row->monetization_score); ?>/100</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $affiliate_networks = get_option('contentpulse_affiliate_networks', array('Amazon Associates', 'Google AdSense'));
        $tracking_enabled = get_option('contentpulse_tracking_enabled', true);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contentpulse_settings_nonce'])) {
            if (!wp_verify_nonce($_POST['contentpulse_settings_nonce'], 'contentpulse_settings')) {
                wp_die('Nonce verification failed');
            }

            $tracking_enabled = isset($_POST['tracking_enabled']) ? 1 : 0;
            update_option('contentpulse_tracking_enabled', $tracking_enabled);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>ContentPulse Pro Settings</h1>
            <form method="post">
                <?php wp_nonce_field('contentpulse_settings', 'contentpulse_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="tracking_enabled">Enable Tracking</label></th>
                        <td>
                            <input type="checkbox" id="tracking_enabled" name="tracking_enabled" <?php checked($tracking_enabled); ?> />
                            <p class="description">Enable content analytics tracking</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_upgrade_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        ?>
        <div class="wrap">
            <h1>Upgrade to ContentPulse Pro</h1>
            <div class="contentpulse-upgrade-container">
                <h2>Pro Features Include:</h2>
                <ul>
                    <li>Advanced AI-powered monetization recommendations</li>
                    <li>Multi-channel revenue tracking (Affiliate, Ads, Sponsored Content)</li>
                    <li>Revenue forecasting and projections</li>
                    <li>A/B testing for monetization strategies</li>
                    <li>Priority email support</li>
                </ul>
                <a href="https://contentpulsepro.com/upgrade" class="button button-primary button-hero">Upgrade Now - $29/month</a>
            </div>
        </div>
        <?php
    }

    public function render_dashboard() {
        return '<div id="contentpulse-frontend-dashboard"><p>Your ContentPulse dashboard will appear here.</p></div>';
    }

    public function activate_plugin() {
        $this->create_tables();
        update_option('contentpulse_tracking_enabled', true);
    }

    public function deactivate_plugin() {
        // Clean up if needed
    }
}

ContentPulsePro::get_instance();
?>