/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Analytics.php
*/
<?php
/**
 * Plugin Name: ContentBoost Analytics
 * Plugin URI: https://contentboostanalytics.com
 * Description: Track content performance and get AI-powered optimization recommendations
 * Version: 1.0.0
 * Author: ContentBoost
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostAnalytics {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
        add_action('wp_ajax_cb_get_analytics', array($this, 'getAnalytics'));
        add_action('wp_ajax_cb_save_settings', array($this, 'saveSettings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentboost_analytics';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            views mediumint(9) DEFAULT 0,
            clicks mediumint(9) DEFAULT 0,
            avg_time_on_page float DEFAULT 0,
            bounce_rate float DEFAULT 0,
            seo_score mediumint(3) DEFAULT 0,
            revenue_impact float DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('contentboost_settings', array(
            'license' => 'free',
            'tracking_enabled' => true,
            'ai_recommendations' => false
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentBoost Analytics',
            'ContentBoost',
            'manage_options',
            'contentboost-analytics',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            30
        );
    }

    public function enqueueAssets($hook) {
        if (strpos($hook, 'contentboost') === false) {
            return;
        }

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js');
        wp_enqueue_style('contentboost-style', CONTENTBOOST_PLUGIN_URL . 'assets/style.css', array(), CONTENTBOOST_VERSION);
        wp_enqueue_script('contentboost-script', CONTENTBOOST_PLUGIN_URL . 'assets/script.js', array('jquery'), CONTENTBOOST_VERSION, true);

        wp_localize_script('contentboost-script', 'contentboostVars', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_nonce')
        ));
    }

    public function getAnalytics() {
        check_ajax_referer('contentboost_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analytics';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY views DESC LIMIT 10");
        
        wp_send_json_success($results);
    }

    public function saveSettings() {
        check_ajax_referer('contentboost_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $settings = get_option('contentboost_settings');
        $settings['tracking_enabled'] = isset($_POST['tracking_enabled']) ? true : false;
        update_option('contentboost_settings', $settings);
        
        wp_send_json_success('Settings saved');
    }

    public function renderDashboard() {
        $settings = get_option('contentboost_settings');
        $license = $settings['license'];
        ?>
        <div class="wrap contentboost-dashboard">
            <h1>ContentBoost Analytics Dashboard</h1>
            
            <div class="contentboost-notice">
                <p>Current Plan: <strong><?php echo ucfirst($license); ?></strong></p>
                <?php if ($license === 'free'): ?>
                    <p><a href="#" class="button button-primary">Upgrade to Premium - $9.99/month</a></p>
                <?php endif; ?>
            </div>

            <div class="contentboost-container">
                <div class="contentboost-card">
                    <h2>Top Performing Posts</h2>
                    <div id="analytics-chart"></div>
                </div>

                <div class="contentboost-card">
                    <h2>Settings</h2>
                    <label>
                        <input type="checkbox" id="tracking-enabled" <?php echo $settings['tracking_enabled'] ? 'checked' : ''; ?> />
                        Enable Tracking
                    </label>
                    <button class="button button-primary" id="save-settings">Save Settings</button>
                </div>

                <?php if ($license === 'premium'): ?>
                <div class="contentboost-card">
                    <h2>AI Recommendations</h2>
                    <p>Optimize your content with AI-powered suggestions to boost SEO and revenue potential.</p>
                    <button class="button button-secondary">Generate Recommendations</button>
                </div>
                <?php else: ?>
                <div class="contentboost-card contentboost-locked">
                    <h2>🔒 AI Recommendations (Premium)</h2>
                    <p>Unlock AI-powered content optimization suggestions by upgrading to Premium.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

ContentBoostAnalytics::getInstance();
?>