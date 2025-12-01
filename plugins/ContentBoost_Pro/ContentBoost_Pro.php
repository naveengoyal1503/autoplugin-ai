/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
*/
<?php
/**
 * Plugin Name: ContentBoost Pro
 * Plugin URI: https://contentboostpro.com
 * Description: AI-powered content optimization and monetization management
 * Version: 1.0.0
 * Author: ContentBoost
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostPro {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        add_shortcode('contentboost_stats', array($this, 'render_stats_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            20
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Ad Management',
            'Ad Management',
            'manage_options',
            'contentboost-ads',
            array($this, 'render_ads_management')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'contentboost-affiliates',
            array($this, 'render_affiliate_management')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentboost') === false) return;
        
        wp_enqueue_style('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/admin.css', array(), CONTENTBOOST_VERSION);
        wp_enqueue_script('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTBOOST_VERSION, true);
        
        wp_localize_script('contentboost-admin', 'ContentBoostData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_nonce'),
            'plugin_url' => CONTENTBOOST_PLUGIN_URL
        ));
    }

    public function enqueue_frontend_scripts() {
        $options = get_option('contentboost_settings', array());
        if (isset($options['enable_tracking']) && $options['enable_tracking']) {
            wp_enqueue_script('contentboost-tracking', CONTENTBOOST_PLUGIN_URL . 'assets/tracking.js', array(), CONTENTBOOST_VERSION, true);
        }
    }

    public function render_dashboard() {
        $stats = $this->get_revenue_stats();
        ?>
        <div class="wrap contentboost-dashboard">
            <h1>ContentBoost Pro Dashboard</h1>
            <div class="contentboost-stats-grid">
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Ads Impressions</h3>
                    <p class="stat-value"><?php echo number_format($stats['impressions']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Affiliate Clicks</h3>
                    <p class="stat-value"><?php echo number_format($stats['affiliate_clicks']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Conversion Rate</h3>
                    <p class="stat-value"><?php echo number_format($stats['conversion_rate'], 1); ?>%</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_ads_management() {
        ?>
        <div class="wrap contentboost-ads">
            <h1>Ad Management</h1>
            <form method="post" action="">
                <?php wp_nonce_field('contentboost_ads_nonce', 'contentboost_nonce'); ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Ad Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>After First Paragraph</td>
                            <td><input type="checkbox" name="ad_position_1" value="1" checked /></td>
                            <td><button class="button">Configure</button></td>
                        </tr>
                        <tr>
                            <td>In Sidebar</td>
                            <td><input type="checkbox" name="ad_position_2" value="1" checked /></td>
                            <td><button class="button">Configure</button></td>
                        </tr>
                        <tr>
                            <td>After Content</td>
                            <td><input type="checkbox" name="ad_position_3" value="1" checked /></td>
                            <td><button class="button">Configure</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    public function render_affiliate_management() {
        ?>
        <div class="wrap contentboost-affiliates">
            <h1>Affiliate Links Management</h1>
            <form method="post" action="">
                <?php wp_nonce_field('contentboost_affiliates_nonce', 'contentboost_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="amazon_id" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Shareasale ID</th>
                        <td><input type="text" name="shareasale_id" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>CJ Affiliate ID</th>
                        <td><input type="text" name="cj_id" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap contentboost-settings">
            <h1>ContentBoost Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentboost_settings'); ?>
                <?php do_settings_sections('contentboost_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Revenue Tracking</th>
                        <td><input type="checkbox" name="contentboost_settings[enable_tracking]" value="1" /></td>
                    </tr>
                    <tr>
                        <th>Enable A/B Testing</th>
                        <td><input type="checkbox" name="contentboost_settings[enable_ab_testing]" value="1" /></td>
                    </tr>
                    <tr>
                        <th>Ad Density</th>
                        <td>
                            <select name="contentboost_settings[ad_density]">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function render_stats_shortcode($atts) {
        $stats = $this->get_revenue_stats();
        return '<div class="contentboost-frontend-stats">Total Revenue: $' . number_format($stats['total_revenue'], 2) . '</div>';
    }

    public function register_rest_routes() {
        register_rest_route('contentboost/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_rest_stats'),
            'permission_callback' => array($this, 'check_rest_permission')
        ));
    }

    public function get_rest_stats() {
        return rest_ensure_response($this->get_revenue_stats());
    }

    public function check_rest_permission() {
        return current_user_can('manage_options');
    }

    private function get_revenue_stats() {
        $stats = get_option('contentboost_stats', array(
            'total_revenue' => 0,
            'impressions' => 0,
            'affiliate_clicks' => 0,
            'conversion_rate' => 0
        ));
        return $stats;
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentboost_stats (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            impressions bigint(20) DEFAULT 0,
            clicks bigint(20) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0,
            date_recorded datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('contentboost_settings', array(
            'enable_tracking' => 1,
            'enable_ab_testing' => 0,
            'ad_density' => 'medium'
        ));
    }

    public function deactivate_plugin() {
        // Clean up if needed
    }
}

// Initialize the plugin
ContentBoostPro::getInstance();
?>